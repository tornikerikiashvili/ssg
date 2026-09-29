<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'smartsoft:create-admin';

    protected $description = 'Create a new SmartSoft platform administrator';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Name'),
            'email' => Str::lower(trim((string) $this->ask('Email address'))),
            'password' => $this->secret('Password (at least 12 characters)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->forceFill([...$data, 'is_admin' => true, 'is_active' => true])->save();
        $this->info('Administrator created. Sign in at '.url('/admin'));

        return self::SUCCESS;
    }
}
