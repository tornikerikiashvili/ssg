<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Demo accounts can only be created in the local environment.');
        }

        $credentials = [];
        DB::transaction(function () use (&$credentials) {
            $company = Company::firstOrCreate(['name' => 'Demo Partner']);

            foreach (['admin@smartsoft.test' => true, 'client@smartsoft.test' => false] as $email => $admin) {
                if (User::where('email', $email)->exists()) {
                    continue;
                }

                $password = Str::password(24);
                $user = new User;
                $user->forceFill([
                    'name' => $admin ? 'SmartSoft Admin' : 'Demo Client',
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_admin' => $admin,
                    'is_active' => true,
                    'company_id' => $admin ? null : $company->id,
                ])->save();
                $credentials[] = $email.' | '.$password;
            }
        });

        if ($credentials !== []) {
            file_put_contents(base_path('.local-credentials'), implode(PHP_EOL, $credentials).PHP_EOL, FILE_APPEND | LOCK_EX);
            chmod(base_path('.local-credentials'), 0600);
        }

        $this->command?->info('Local accounts ready. Passwords are in the git-ignored .local-credentials file.');
    }
}
