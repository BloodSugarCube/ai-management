<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminHashPassword extends Command
{
    protected $signature = 'admin:hash-password {password : Пароль для хранения в .env как ADMIN_PASSWORD_HASH}';

    protected $description = 'Сгенерировать bcrypt-хэш пароля администратора для ADMIN_PASSWORD_HASH';

    public function handle(): int
    {
        $this->line(Hash::make((string) $this->argument('password')));

        return self::SUCCESS;
    }
}
