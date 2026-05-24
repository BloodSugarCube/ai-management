<?php

namespace App\Http\Controllers\Admin;

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
        'due_date',
        'labels',
        'estimated_hours',
        'tracker_name',
    ];

    public function index(Request $request)
    {
        $sort = (string) $request->query('sort', '');
        $dir = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = RedmineIssue::query();

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
            'login' => ['required', 'string', 'max:191', Rule::exists('employees', 'login')],
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
