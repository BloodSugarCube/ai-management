<?php

namespace App\Components\GenApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Клиент GenAPI (gen-api.ru): POST к сети и синхронное получение результата через polling.
 */
class GenApiClient
{
    private Client $http;

    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => $this->baseUrl() . '/',
            'timeout' => (int) config('genapi.timeout', 120),
            'verify' => (bool) config('genapi.verify_ssl', true),
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
        $res = $this->http->post($path, ['json' => $body]);
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
        $paths = [
            ['result'],
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
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}
