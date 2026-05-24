<?php

namespace App\Console\Commands;

use App\Components\RocketChat\RedmineClient;
use App\Models\Employee;
use Illuminate\Console\Command;

class SyncRedmineEmployees extends Command
{
    protected $signature = 'redmine:sync-employees';

    protected $description = 'Выгрузить пользователей Redmine в локальную таблицу employees';

    public function handle(RedmineClient $redmine): int
    {
        $this->info('Запрос пользователей из Redmine...');
        try {
            $users = $redmine->fetchAllUsers();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar(count($users));
        foreach ($users as $u) {
            $id = (int) ($u['id'] ?? 0);
            $login = (string) ($u['login'] ?? '');
            if ($id === 0 || $login === '') {
                $bar->advance();
                continue;
            }
            Employee::query()->updateOrCreate(
                ['redmine_user_id' => $id],
                ['login' => $login]
            );
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Готово. Всего обработано: ' . count($users));

        return self::SUCCESS;
    }
}
