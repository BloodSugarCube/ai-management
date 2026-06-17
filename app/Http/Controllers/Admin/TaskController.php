<?php

namespace App\Http\Controllers\Admin;

use App\Components\RocketChat\RedmineClient;
use App\Http\Controllers\Controller;
use App\Models\RedmineIssue;
use App\Services\RedmineIssueAssignmentService;
use App\Services\TaskAssigneeRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const SORTABLE = [
        'project_name',
        'subject',
        'assigned_to_login',
        'priority_name',
        'status_name',
        'due_date',
        'estimated_hours',
        'tracker_name',
    ];

    public function index(Request $request)
    {
        $sort = (string) $request->query('sort', '');
        $dir = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = RedmineIssue::query()->with('assignee');

        if ($sort === '' || ! in_array($sort, self::SORTABLE, true)) {
            $query->orderByRaw('CASE WHEN assigned_to_redmine_id IS NULL THEN 0 ELSE 1 END ASC')
                ->orderByDesc('redmine_updated_on')
                ->orderByDesc('id');
        } else {
            if ($sort === 'assigned_to_login') {
                $query->orderByRaw('assigned_to_redmine_id IS NULL DESC');
            }
            $query->orderBy($sort, $dir);
        }

        $issues = $query->paginate(30)->withQueryString();

        return view('admin.tasks.index', [
            'issues' => $issues,
            'sort' => $sort,
            'direction' => $dir,
            'sortable' => self::SORTABLE,
        ]);
    }

    public function create(RedmineClient $redmine)
    {
        try {
            $formData = [
                'projects' => $redmine->fetchProjects(),
                'statuses' => $redmine->fetchIssueStatuses(),
                'priorities' => $redmine->fetchIssuePriorities(),
                'trackers' => $redmine->fetchTrackers(),
            ];
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('tasks.index')
                ->withErrors(['redmine' => 'Не удалось загрузить справочники Redmine: ' . $e->getMessage()]);
        }

        return view('admin.tasks.create', $formData);
    }

    public function store(Request $request, RedmineClient $redmine)
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:65535'],
            'status_id' => ['required', 'integer', 'min:1'],
            'priority_id' => ['required', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'tracker_id' => ['required', 'integer', 'min:1'],
            'related_issue_ids' => ['nullable', 'array'],
            'related_issue_ids.*' => ['integer', 'min:1'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:5120'],
        ]);

        $uploads = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (! $file->isValid()) {
                    continue;
                }
                $token = $redmine->uploadAttachment(
                    (string) file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                    $file->getMimeType() ?: 'application/octet-stream'
                );
                $uploads[] = [
                    'token' => $token,
                    'filename' => $file->getClientOriginalName(),
                    'content_type' => $file->getMimeType() ?: 'application/octet-stream',
                ];
            }
        }

        $payload = [
            'project_id' => (int) $data['project_id'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? '',
            'status_id' => (int) $data['status_id'],
            'priority_id' => (int) $data['priority_id'],
            'tracker_id' => (int) $data['tracker_id'],
        ];

        if (! empty($data['category_id'])) {
            $payload['category_id'] = (int) $data['category_id'];
        }
        if (! empty($data['due_date'])) {
            $payload['due_date'] = $data['due_date'];
        }
        if (isset($data['estimated_hours']) && $data['estimated_hours'] !== null && $data['estimated_hours'] !== '') {
            $payload['estimated_hours'] = (int) $data['estimated_hours'];
        }
        if ($uploads !== []) {
            $payload['uploads'] = $uploads;
        }

        $related = array_map('intval', $data['related_issue_ids'] ?? []);

        try {
            $issue = $redmine->createIssue($payload, $related);
            $normalized = RedmineClient::normalizeIssueFromApi($issue);
            RedmineIssue::query()->updateOrCreate(
                ['redmine_issue_id' => $normalized['redmine_issue_id']],
                $normalized
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'redmine' => 'Не удалось создать задачу в Redmine: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('tasks.index')->with('status', 'Задача создана в Redmine.');
    }

    public function categories(Request $request, RedmineClient $redmine)
    {
        $projectId = (int) $request->query('project_id', 0);
        if ($projectId <= 0) {
            return response()->json(['categories' => []]);
        }

        try {
            return response()->json([
                'categories' => $redmine->fetchIssueCategories($projectId),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function searchIssues(Request $request, RedmineClient $redmine)
    {
        $term = (string) $request->query('q', '');

        try {
            return response()->json([
                'results' => $redmine->searchIssues($term),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function recommendations(RedmineIssue $issue, TaskAssigneeRecommendationService $service)
    {
        if (! $issue->isUnassigned()) {
            return response()->json(['message' => 'Задача уже назначена.'], 422);
        }

        if (! config('genapi.step1_network_id')) {
            return response()->json(['message' => 'Не настроен GENAPI_STEP1_NETWORK_ID.'], 500);
        }

        try {
            $list = $service->getRecommendations($issue);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Ошибка GenAPI/Redmine: ' . $e->getMessage()], 502);
        }

        return response()->json(['recommendations' => $list]);
    }

    public function assign(Request $request, RedmineIssue $issue, RedmineIssueAssignmentService $service)
    {
        if (! $issue->isUnassigned()) {
            return response()->json(['message' => 'Задача уже назначена.'], 422);
        }

        $data = $request->validate([
            'login' => [
                'required',
                'string',
                'max:191',
                Rule::exists('employees', 'login')->where(fn ($q) => $q->where('ignored', false)),
            ],
        ], [
            'login.exists' => 'Сотрудник не найден или отмечен как «Игнорировать».',
        ]);

        try {
            $service->assignByLogin($issue, $data['login']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Не удалось назначить в Redmine: ' . $e->getMessage()], 502);
        }

        return response()->json(['ok' => true]);
    }
}
