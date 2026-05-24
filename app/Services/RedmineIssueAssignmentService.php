<?php

namespace App\Services;

use App\Components\RocketChat\RedmineClient;
use App\Models\RedmineIssue;
use Illuminate\Support\Facades\DB;

class RedmineIssueAssignmentService
{
    public function __construct(private RedmineClient $redmine)
    {
    }

    public function assignByLogin(RedmineIssue $issue, string $login): RedmineIssue
    {
        $employee = \App\Models\Employee::query()->where('login', $login)->firstOrFail();

        DB::transaction(function () use ($issue, $employee) {
            $this->redmine->assignIssue((int) $issue->redmine_issue_id, (int) $employee->redmine_user_id);
            $fresh = RedmineClient::normalizeIssueFromApi($this->redmine->getIssue((int) $issue->redmine_issue_id));
            $issue->update($fresh);
        });

        return $issue->fresh();
    }
}
