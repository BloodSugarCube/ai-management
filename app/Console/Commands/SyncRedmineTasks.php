<?php

namespace App\Console\Commands;

use App\Components\RocketChat\RedmineClient;
use App\Models\RedmineIssue;
use Illuminate\Console\Command;

class SyncRedmineTasks extends Command
{
    protected $signature = 'redmine:sync-tasks';

    protected $description = 'Выгрузить задачи (issues) Redmine в таблицу redmine_issues';

    public function handle(RedmineClient $redmine): int
    {
        $filter = (string) config('redmine.issue_status_filter', 'open');
        $this->info('Загрузка задач, status_id=' . ($filter === '' ? '*' : $filter));

        $count = 0;
        $syncedIds = [];
        try {
            foreach ($redmine->iterateIssues($filter) as $issue) {
                $normalized = RedmineIssue::enrichNormalizedAssignee(
                    RedmineClient::normalizeIssueFromApi($issue)
                );
                if ($normalized['redmine_issue_id'] === 0) {
                    continue;
                }
                $syncedIds[] = $normalized['redmine_issue_id'];
                RedmineIssue::query()->updateOrCreate(
                    ['redmine_issue_id' => $normalized['redmine_issue_id']],
                    $normalized
                );
                $count++;
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $deleted = $syncedIds === []
            ? RedmineIssue::query()->delete()
            : RedmineIssue::query()->whereNotIn('redmine_issue_id', $syncedIds)->delete();

        $this->info('Синхронизировано записей: ' . $count);
        if ($deleted > 0) {
            $this->info('Удалено устаревших записей: ' . $deleted);
        }

        return self::SUCCESS;
    }
}
