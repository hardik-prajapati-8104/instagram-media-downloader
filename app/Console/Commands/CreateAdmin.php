<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'media:admin {email : Admin e-mail} {--name=Administrator}';

    protected $description = 'Create (or reset the password of) an admin user. No default credentials are ever seeded.';

    public function handle(): int
    {
        $password = (string) $this->secret('Password (min 12 characters)');
        if ($password !== (string) $this->secret('Confirm password')) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $v = Validator::make(['email' => $this->argument('email'), 'password' => $password], [
            'email' => ['required', 'email'], 'password' => ['required', 'string', 'min:12'],
        ]);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }

        Admin::updateOrCreate(['email' => $this->argument('email')], [
            'name' => $this->option('name'), 'password' => $password, 'status' => true,
        ]);
        $this->info('Admin saved. Login at /admin/login');

        return self::SUCCESS;
    }
}
