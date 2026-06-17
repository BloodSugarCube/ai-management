<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSystemUser extends Command
{
    protected $signature = 'user:create
        {email : Логин / email}
        {password : Пароль}
        {name : ФИО}
        {--role=admin : Роль: manager или admin}';

    protected $description = 'Создать пользователя системы (менеджер или администратор)';

    public function handle(): int
    {
        $data = [
            'email' => (string) $this->argument('email'),
            'password' => (string) $this->argument('password'),
            'name' => (string) $this->argument('name'),
            'role' => (string) $this->option('role'),
        ];

        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:500'],
            'name' => ['required', 'string', 'max:191'],
            'role' => ['required', 'in:' . User::ROLE_MANAGER . ',' . User::ROLE_ADMIN],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        $this->info("Пользователь #{$user->id} создан: {$user->email} ({$user->roleLabel()})");

        return self::SUCCESS;
    }
}
