<?php

namespace App\Components\GenApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Клиент GenAPI (gen-api.ru): POST к сети и синхронное получение результата через polling.
 */
class GenApiClient
{
    private Client $http;

    public function __construct()
    {
        // Do not type-hint Guzzle Client here: Laravel auto-resolves it and ignores base_uri/headers.
        $this->http = new Client([
            'base_uri' => $this->baseUrl() . '/',
            'timeout' => (int) config('genapi.timeout', 120),
            'verify' => $this->sslVerifyOption(),
            'headers' => [
                'Authorization' => 'Bearer ' . (string) config('genapi.api_key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function baseUrl(): string
    {
        $url = (string) config('genapi.base_url');
        if ($url === '') {
            throw new \RuntimeException('GENAPI_BASE_URL is not configured.');
        }

        return rtrim($url, '/');
    }

    /** @return bool|string */
    private function sslVerifyOption(): bool|string
    {
        $bundle = config('genapi.ca_bundle');
        if (is_string($bundle) && $bundle !== '') {
            if (! is_readable($bundle)) {
                throw new \RuntimeException('GENAPI_CA_BUNDLE is not a readable file: ' . $bundle);
            }

            return $bundle;
        }

        return (bool) config('genapi.verify_ssl', true);
    }

    /**
     * Выполняет запрос к выбранной «нейросети» и возвращает итоговый JSON-ответ GenAPI.
     *
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function runNetwork(string $networkId, array $body): array
    {
        if ($networkId === '') {
            throw new \RuntimeException('GenAPI network_id is empty.');
        }

        $path = 'api/v1/networks/' . rawurlencode($networkId);
        try {
            $res = $this->http->post($path, ['json' => $body]);
        } catch (ClientException $e) {
            throw $this->toReadableException($e);
        }
        $data = json_decode((string) $res->getBody(), true) ?? [];

        $requestId = $data['request_id'] ?? $data['id'] ?? null;
        if ($requestId && ($data['status'] ?? null) !== 'success' && ! isset($data['result'])) {
            return $this->pollRequest((string) $requestId);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    private function pollRequest(string $requestId): array
    {
        $path = 'api/v1/request/get/' . rawurlencode($requestId);
        $deadline = microtime(true) + max(30, (int) config('genapi.timeout', 120));

        while (microtime(true) < $deadline) {
            $res = $this->http->get($path);
            $data = json_decode((string) $res->getBody(), true) ?? [];
            $status = $data['status'] ?? null;
            if ($status === 'success' || $status === 'done' || isset($data['result']) || isset($data['output'])) {
                return is_array($data) ? $data : [];
            }
            if (in_array($status, ['failed', 'error'], true)) {
                Log::warning('GenAPI request failed', ['body' => $data]);

                throw new \RuntimeException('GenAPI вернул статус ошибки: ' . (string) ($data['message'] ?? $status));
            }
            usleep(400_000);
        }

        throw new \RuntimeException('GenAPI: превышено время ожидания результата.');
    }

    /**
     * Извлекает текстовый ответ модели из произвольной структуры GenAPI.
     */
    public static function extractModelText(array $response): string
    {
        foreach (['choices', 'full_response', 'result'] as $key) {
            $text = self::extractFromCompletionContainer($response[$key] ?? null);
            if ($text !== '') {
                return $text;
            }
        }

        $paths = [
            ['data', 'result'],
            ['data', 'output'],
            ['output'],
            ['message'],
            ['text'],
        ];
        foreach ($paths as $p) {
            $v = $response;
            foreach ($p as $key) {
                $v = is_array($v) ? ($v[$key] ?? null) : null;
            }
            if (is_string($v) && $v !== '') {
                return $v;
            }
            if (is_array($v)) {
                $nested = self::extractFromCompletionContainer($v);
                if ($nested !== '') {
                    return $nested;
                }
            }
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param mixed $node GenAPI result / full_response / choices (строка, completion или массив completions).
     */
    private static function extractFromCompletionContainer(mixed $node): string
    {
        if (is_string($node) && trim($node) !== '') {
            return trim($node);
        }
        if (! is_array($node)) {
            return '';
        }

        $content = $node['choices'][0]['message']['content'] ?? null;
        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        if (array_is_list($node)) {
            foreach ($node as $item) {
                $text = self::extractFromCompletionContainer($item);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }

    private function toReadableException(ClientException $e): \RuntimeException
    {
        $response = $e->getResponse();
        if ($response !== null) {
            $data = json_decode((string) $response->getBody(), true);
            if (is_array($data)) {
                $validation = $data['errors_validation']['messages'] ?? null;
                if (is_array($validation) && $validation !== []) {
                    return new \RuntimeException(
                        'GenAPI: ' . implode('; ', array_map('strval', $validation)),
                        0,
                        $e
                    );
                }
                if (! empty($data['message']) && is_string($data['message'])) {
                    return new \RuntimeException('GenAPI: ' . $data['message'], 0, $e);
                }
            }
        }

        return new \RuntimeException($e->getMessage(), 0, $e);
    }
}
