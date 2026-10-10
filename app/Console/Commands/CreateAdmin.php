<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email : Email admin} {--name=Administrator : Nama admin}';
    protected $description = 'Buat akun admin dengan password yang diminta secara interaktif (aman untuk production).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->secret('Password (min. 12 karakter)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        if ($password !== $this->secret('Ulangi password')) {
            $this->error('Konfirmasi password tidak cocok.');
            return self::FAILURE;
        }

        $user = new User([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->info("Admin {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
