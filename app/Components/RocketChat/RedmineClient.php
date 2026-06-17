<?php

namespace App\Components\RocketChat;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * HTTP-клиент REST API Redmine (каталог по ТЗ: RocketChat).
 */
class RedmineClient
{
    private Client $http;

    public function __construct()
    {
        // Do not type-hint Guzzle Client here: Laravel auto-resolves it and ignores base_uri/headers.
        $this->http = new Client([
            'base_uri' => $this->baseUrl() . '/',
            'timeout' => (int) config('redmine.timeout', 60),
            'verify' => $this->sslVerifyOption(),
            'headers' => [
                'X-Redmine-API-Key' => (string) config('redmine.api_key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function baseUrl(): string
    {
        $url = (string) config('redmine.base_url');
        if ($url === '') {
            throw new \RuntimeException('REDMINE_BASE_URL is not configured.');
        }

        return rtrim($url, '/');
    }

    /** @return bool|string */
    private function sslVerifyOption(): bool|string
    {
        $bundle = config('redmine.ca_bundle');
        if (is_string($bundle) && $bundle !== '') {
            if (! is_readable($bundle)) {
                throw new \RuntimeException('REDMINE_CA_BUNDLE is not a readable file: ' . $bundle);
            }

            return $bundle;
        }

        return (bool) config('redmine.verify_ssl', true);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws GuzzleException
     */
    public function fetchAllUsers(): array
    {
        $all = [];
        $limit = (int) config('redmine.page_size', 100);
        $offset = 0;

        while (true) {
            $res = $this->http->get('users.json', [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'status' => 1,
                ],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $users = $data['users'] ?? [];
            foreach ($users as $u) {
                $all[] = $u;
            }
            if (count($users) < $limit) {
                break;
            }
            $offset += $limit;
        }

        return $all;
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     *
     * @throws GuzzleException
     */
    public function iterateIssues(string $statusFilter = 'open'): \Generator
    {
        $limit = (int) config('redmine.page_size', 100);
        $offset = 0;

        while (true) {
            $query = [
                'limit' => $limit,
                'offset' => $offset,
                'include' => 'watchers',
            ];
            if ($statusFilter !== '' && $statusFilter !== '*') {
                $query['status_id'] = $statusFilter;
            }

            $res = $this->http->get('issues.json', ['query' => $query]);
            $data = json_decode((string) $res->getBody(), true);
            $issues = $data['issues'] ?? [];

            foreach ($issues as $issue) {
                yield $issue;
            }

            if (count($issues) < $limit) {
                break;
            }
            $offset += $limit;
        }
    }

    /**
     * @throws GuzzleException
     */
    public function getIssue(int $issueId): array
    {
        $res = $this->http->get('issues/' . $issueId . '.json', [
            'query' => ['include' => 'watchers'],
        ]);
        $data = json_decode((string) $res->getBody(), true);

        return $data['issue'] ?? [];
    }

    /**
     * @return array<int, string>
     *
     * @throws GuzzleException
     */
    public function fetchClosedIssueTitlesForUser(int $redmineUserId, int $limit = 100): array
    {
        $res = $this->http->get('issues.json', [
            'query' => [
                'assigned_to_id' => $redmineUserId,
                'status_id' => 'closed',
                'sort' => 'updated_on:desc',
                'limit' => $limit,
            ],
        ]);
        $data = json_decode((string) $res->getBody(), true);
        $issues = $data['issues'] ?? [];
        $titles = [];
        foreach ($issues as $issue) {
            if (! empty($issue['subject'])) {
                $titles[] = (string) $issue['subject'];
            }
        }

        return $titles;
    }

    /**
     * @throws GuzzleException
     */
    public function assignIssue(int $issueId, int $assigneeRedmineUserId): void
    {
        $this->http->put('issues/' . $issueId . '.json', [
            'json' => [
                'issue' => [
                    'assigned_to_id' => $assigneeRedmineUserId,
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     *
     * @throws GuzzleException
     */
    public function fetchProjects(): array
    {
        $all = [];
        $limit = (int) config('redmine.page_size', 100);
        $offset = 0;

        while (true) {
            $res = $this->http->get('projects.json', [
                'query' => ['limit' => $limit, 'offset' => $offset],
            ]);
            $data = json_decode((string) $res->getBody(), true);
            $items = $data['projects'] ?? [];
            foreach ($items as $p) {
                $all[] = [
                    'id' => (int) ($p['id'] ?? 0),
                    'name' => (string) ($p['name'] ?? ''),
                ];
            }
            if (count($items) < $limit) {
                break;
            }
            $offset += $limit;
        }

        usort($all, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $all;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     *
     * @throws GuzzleException
     */
    public function fetchIssueStatuses(): array
    {
        $res = $this->http->get('issue_statuses.json');
        $data = json_decode((string) $res->getBody(), true);
        $out = [];
        foreach ($data['issue_statuses'] ?? [] as $s) {
            $out[] = [
                'id' => (int) ($s['id'] ?? 0),
                'name' => (string) ($s['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     *
     * @throws GuzzleException
     */
    public function fetchIssuePriorities(): array
    {
        $res = $this->http->get('enumerations/issue_priorities.json');
        $data = json_decode((string) $res->getBody(), true);
        $out = [];
        foreach ($data['issue_priorities'] ?? [] as $p) {
            $out[] = [
                'id' => (int) ($p['id'] ?? 0),
                'name' => (string) ($p['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     *
     * @throws GuzzleException
     */
    public function fetchTrackers(): array
    {
        $res = $this->http->get('trackers.json');
        $data = json_decode((string) $res->getBody(), true);
        $out = [];
        foreach ($data['trackers'] ?? [] as $t) {
            $out[] = [
                'id' => (int) ($t['id'] ?? 0),
                'name' => (string) ($t['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     *
     * @throws GuzzleException
     */
    public function fetchIssueCategories(int $projectId): array
    {
        $res = $this->http->get('projects/' . $projectId . '/issue_categories.json');
        $data = json_decode((string) $res->getBody(), true);
        $out = [];
        foreach ($data['issue_categories'] ?? [] as $c) {
            $out[] = [
                'id' => (int) ($c['id'] ?? 0),
                'name' => (string) ($c['name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{id: int, text: string}>
     *
     * @throws GuzzleException
     */
    public function searchIssues(string $term = '', int $limit = 50): array
    {
        $query = [
            'limit' => min($limit, 100),
            'sort' => 'updated_on:desc',
        ];
        if ($term !== '') {
            $query['subject'] = '~' . $term;
        }

        $res = $this->http->get('issues.json', ['query' => $query]);
        $data = json_decode((string) $res->getBody(), true);
        $out = [];
        foreach ($data['issues'] ?? [] as $issue) {
            $id = (int) ($issue['id'] ?? 0);
            $subject = (string) ($issue['subject'] ?? '');
            $project = (string) ($issue['project']['name'] ?? '');
            $out[] = [
                'id' => $id,
                'text' => '#' . $id . ' — ' . $subject . ($project !== '' ? ' (' . $project . ')' : ''),
            ];
        }

        return $out;
    }

    /**
     * @throws GuzzleException
     */
    public function uploadAttachment(string $contents, string $filename, string $contentType): string
    {
        $res = $this->http->post('uploads.json', [
            'query' => ['filename' => $filename],
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $contents,
        ]);
        $data = json_decode((string) $res->getBody(), true);
        $token = $data['upload']['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Redmine upload did not return a token.');
        }

        return $token;
    }

    /**
     * @param array<string, mixed> $issuePayload
     * @param array<int, int> $relatedIssueIds
     * @return array<string, mixed>
     *
     * @throws GuzzleException
     */
    public function createIssue(array $issuePayload, array $relatedIssueIds = []): array
    {
        $res = $this->http->post('issues.json', [
            'json' => ['issue' => $issuePayload],
        ]);
        $data = json_decode((string) $res->getBody(), true);
        $issue = $data['issue'] ?? [];
        $issueId = (int) ($issue['id'] ?? 0);

        foreach ($relatedIssueIds as $relatedId) {
            if ($relatedId <= 0 || $relatedId === $issueId) {
                continue;
            }
            $this->http->post('issues/' . $issueId . '/relations.json', [
                'json' => [
                    'relation' => [
                        'issue_to_id' => $relatedId,
                        'relation_type' => 'relates',
                    ],
                ],
            ]);
        }

        if ($issueId > 0) {
            return $this->getIssue($issueId);
        }

        return $issue;
    }

    /**
     * @param array<string, mixed> $issue
     * @return array<string, mixed>
     */
    public static function normalizeIssueFromApi(array $issue): array
    {
        $assigned = $issue['assigned_to'] ?? null;
        $assignedId = is_array($assigned) && isset($assigned['id']) ? (int) $assigned['id'] : null;
        $assignedLogin = is_array($assigned)
            ? ($assigned['login'] ?? $assigned['name'] ?? null)
            : null;

        $labels = self::extractLabels($issue);

        return [
            'redmine_issue_id' => (int) ($issue['id'] ?? 0),
            'project_name' => (string) ($issue['project']['name'] ?? ''),
            'subject' => (string) ($issue['subject'] ?? ''),
            'description' => (string) ($issue['description'] ?? ''),
            'assigned_to_redmine_id' => $assignedId,
            'assigned_to_login' => $assignedLogin !== null ? (string) $assignedLogin : null,
            'priority_name' => isset($issue['priority']['name']) ? (string) $issue['priority']['name'] : null,
            'due_date' => ! empty($issue['due_date']) ? (string) $issue['due_date'] : null,
            'labels' => json_encode($labels, JSON_UNESCAPED_UNICODE),
            'estimated_hours' => isset($issue['estimated_hours']) ? (float) $issue['estimated_hours'] : null,
            'tracker_name' => isset($issue['tracker']['name']) ? (string) $issue['tracker']['name'] : null,
            'status_name' => isset($issue['status']['name']) ? (string) $issue['status']['name'] : null,
            'status_is_closed' => ! empty($issue['status']['is_closed']),
            'done_ratio' => isset($issue['done_ratio']) ? (int) $issue['done_ratio'] : 0,
            'redmine_updated_on' => ! empty($issue['updated_on']) ? (string) $issue['updated_on'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $issue
     * @return array<int, string>
     */
    private static function extractLabels(array $issue): array
    {
        $out = [];
        $candidates = ['Tags', 'Метки', 'Labels', 'Теги'];
        foreach ($issue['custom_fields'] ?? [] as $cf) {
            $name = $cf['name'] ?? '';
            if (! in_array($name, $candidates, true)) {
                continue;
            }
            $value = $cf['value'] ?? null;
            if (is_array($value)) {
                foreach ($value as $v) {
                    if ($v !== null && $v !== '') {
                        $out[] = (string) $v;
                    }
                }
            } elseif ($value !== null && $value !== '') {
                $out[] = (string) $value;
            }
        }

        return array_values(array_unique($out));
    }
}
