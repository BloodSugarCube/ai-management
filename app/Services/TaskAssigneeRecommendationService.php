<?php

namespace App\Services;

use App\Components\GenApi\GenApiClient;
use App\Components\RocketChat\RedmineClient;
use App\Models\Employee;
use App\Models\RedmineIssue;
use Illuminate\Support\Collection;

class TaskAssigneeRecommendationService
{
    public function __construct(
        private GenApiClient $genApi,
        private RedmineClient $redmine
    ) {
    }

    /**
     * @return array<int, array{login: string, match_percent: float, reasons: array<int, string>}>
     */
    public function getRecommendations(RedmineIssue $issue): array
    {
        $employees = Employee::query()->orderBy('login')->get();
        $employeePayload = $this->buildEmployeePayload($employees);

        $taskJson = json_encode($issue->toTaskPayloadArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $employeesJson = json_encode($employeePayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $network1 = (string) config('genapi.step1_network_id');
        $network2 = (string) (config('genapi.step2_network_id') ?: $network1);

        $prompt1 = <<<PROMPT
Ты помощник по подбору исполнителей задачи из Redmine.
Нагрузка: после 40 запланированных часов на невыполненных назначенных задачах приоритет сотрудника снижается.

Задача (JSON):
{$taskJson}

Сотрудники (JSON, поля: login, grades, competencies, experience_achievements, planned_hours_incomplete_assigned):
{$employeesJson}

Верни СТРОГО один JSON-объект без markdown и без комментариев формата:
{"candidates":["login1","login2","login3","login4","login5"]}
где candidates — до 5 логинов в порядке убывания пригодности (если меньше 5 подходящих — меньше элементов). Только логины из входного списка.
PROMPT;

        $body1 = $this->buildGenApiBody($prompt1);
        $resp1 = $this->genApi->runNetwork($network1, $body1);
        $text1 = GenApiClient::extractModelText($resp1);
        $candidates = $this->parseCandidatesJson($text1, $employees);

        $titlesByLogin = [];
        foreach ($candidates as $login) {
            $emp = $employees->firstWhere('login', $login);
            if (! $emp) {
                continue;
            }
            try {
                $titlesByLogin[$login] = $this->redmine->fetchClosedIssueTitlesForUser((int) $emp->redmine_user_id, 100);
            } catch (\Throwable $e) {
                $titlesByLogin[$login] = [];
            }
        }

        $prompt2 = <<<PROMPT
Ты оцениваешь соответствие сотрудников задаче. Учитывай нагрузку (после 40 запланированных часов на невыполненных задачах вес ниже), компетенции, грейд, опыт из поля experience_achievements и похожесть прошлых задач по названиям.

Задача (JSON):
{$taskJson}

Кандидаты из первого этапа (логины): {$this->jsonEncode($candidates)}

Сотрудники с деталями и последними выполненными задачами (JSON):
{$this->jsonEncode($this->buildSecondStepPayload($employees, $candidates, $titlesByLogin))}

Верни СТРОГО один JSON без markdown:
{"recommendations":[{"login":"...","match_percent":82.5,"reasons":["...","..."]}]}
Не более 5 элементов, match_percent от 0 до 100, reasons — короткие фразы-объяснения на русском.
PROMPT;

        $body2 = $this->buildGenApiBody($prompt2);
        $resp2 = $this->genApi->runNetwork($network2, $body2);
        $text2 = GenApiClient::extractModelText($resp2);
        $parsed = $this->parseRecommendationsJson($text2);

        return $this->normalizeRecommendations($parsed, $candidates, $employees);
    }

    /**
     * @param Collection<int, Employee> $employees
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeePayload(Collection $employees): array
    {
        return $employees->map(function (Employee $e) {
            return [
                'login' => $e->login,
                'grades' => $e->grades,
                'competencies' => $e->competencies,
                'experience_achievements' => $e->experience_achievements,
                'planned_hours_incomplete_assigned' => round($e->plannedHoursOnIncomplete(), 2),
            ];
        })->values()->all();
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function buildGenApiBody(string $text): array
    {
        $base = [
            'is_sync' => ! filter_var(config('genapi.is_async', false), FILTER_VALIDATE_BOOLEAN),
            'text' => $text,
        ];
        $merge = config('genapi.extra_json_body');
        if (is_array($merge)) {
            $base = array_replace_recursive($base, $merge);
        }

        return $base;
    }

    private function jsonEncode(mixed $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param Collection<int, Employee> $employees
     * @param array<int, string> $candidates
     * @param array<string, array<int, string>> $titlesByLogin
     * @return array<int, array<string, mixed>>
     */
    private function buildSecondStepPayload(Collection $employees, array $candidates, array $titlesByLogin): array
    {
        $out = [];
        foreach ($candidates as $login) {
            $e = $employees->firstWhere('login', $login);
            if (! $e) {
                continue;
            }
            $out[] = [
                'login' => $e->login,
                'grades' => $e->grades,
                'competencies' => $e->competencies,
                'experience_achievements' => $e->experience_achievements,
                'planned_hours_incomplete_assigned' => round($e->plannedHoursOnIncomplete(), 2),
                'last_completed_issue_titles' => $titlesByLogin[$login] ?? [],
            ];
        }

        return $out;
    }

    /**
     * @param Collection<int, Employee> $employees
     * @return array<int, string>
     */
    private function parseCandidatesJson(string $text, Collection $employees): array
    {
        $data = $this->decodeJsonLoose($text);
        $logins = [];
        if (isset($data['candidates']) && is_array($data['candidates'])) {
            foreach ($data['candidates'] as $l) {
                $logins[] = (string) $l;
            }
        } elseif (isset($data['employees']) && is_array($data['employees'])) {
            foreach ($data['employees'] as $row) {
                if (is_array($row) && isset($row['login'])) {
                    $logins[] = (string) $row['login'];
                }
            }
        } elseif (isset($data['logins']) && is_array($data['logins'])) {
            foreach ($data['logins'] as $l) {
                $logins[] = (string) $l;
            }
        }

        $valid = $employees->pluck('login')->all();
        $logins = array_values(array_unique(array_filter($logins, fn ($l) => in_array($l, $valid, true))));

        if ($logins === []) {
            return $this->fallbackCandidates($employees);
        }

        return array_slice($logins, 0, 5);
    }

    /**
     * @param Collection<int, Employee> $employees
     * @return array<int, string>
     */
    private function fallbackCandidates(Collection $employees): array
    {
        return $employees
            ->sortBy(fn (Employee $e) => $e->plannedHoursOnIncomplete())
            ->take(5)
            ->pluck('login')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{login: string, match_percent: float, reasons: array<int, string>}>
     */
    private function parseRecommendationsJson(string $text): array
    {
        $data = $this->decodeJsonLoose($text);
        $rows = $data['recommendations'] ?? $data['result'] ?? null;
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['login'])) {
                continue;
            }
            $reasons = $row['reasons'] ?? $row['explanations'] ?? [];
            if (! is_array($reasons)) {
                $reasons = [];
            }
            $out[] = [
                'login' => (string) $row['login'],
                'match_percent' => isset($row['match_percent']) ? (float) $row['match_percent'] : (isset($row['percent']) ? (float) $row['percent'] : 0.0),
                'reasons' => array_values(array_filter(array_map('strval', $reasons))),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array{login: string, match_percent: float, reasons: array<int, string>}> $parsed
     * @param array<int, string> $candidates
     * @param Collection<int, Employee> $employees
     * @return array<int, array{login: string, match_percent: float, reasons: array<int, string>}>
     */
    private function normalizeRecommendations(array $parsed, array $candidates, Collection $employees): array
    {
        $valid = $employees->pluck('login')->flip()->all();
        $clean = [];
        foreach ($parsed as $row) {
            if (! isset($valid[$row['login']])) {
                continue;
            }
            $clean[] = [
                'login' => $row['login'],
                'match_percent' => max(0, min(100, $row['match_percent'])),
                'reasons' => $row['reasons'] !== [] ? $row['reasons'] : ['Модель не вернула пояснения; ориентируйтесь на процент.'],
            ];
        }

        $clean = array_slice($clean, 0, 5);
        if ($clean !== []) {
            return $clean;
        }

        $fallback = [];
        foreach ($candidates as $i => $login) {
            if (! isset($valid[$login])) {
                continue;
            }
            $e = $employees->firstWhere('login', $login);
            $hours = $e ? $e->plannedHoursOnIncomplete() : 0.0;
            $fallback[] = [
                'login' => $login,
                'match_percent' => max(10, 90 - min(80, $hours)),
                'reasons' => [
                    $hours >= 40
                        ? 'Высокая текущая загрузка по запланированным часам.'
                        : 'Текущая загрузка ниже порога 40 часов.',
                    'Резервная оценка без ответа GenAPI по второму шагу.',
                ],
            ];
        }

        return array_slice($fallback, 0, 5);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonLoose(string $text): array
    {
        $text = trim($text);
        $try = json_decode($text, true);
        if (is_array($try)) {
            return $try;
        }
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $try2 = json_decode($m[0], true);
            if (is_array($try2)) {
                return $try2;
            }
        }

        return [];
    }
}
