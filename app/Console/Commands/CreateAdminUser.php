<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
        {--name= : Nama administrator}
        {--email= : Email login administrator}
        {--password= : Password (min. 8 karakter; jika dikosongkan akan diminta interaktif)}';

    protected $description = 'Buat akun administrator baru (untuk setup production tanpa akun demo).';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama administrator');
        $email = $this->option('email') ?: $this->ask('Email login');
        $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
            ['email.unique' => 'Email sudah terdaftar.']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'password' => Hash::make($password),
        ]);

        $this->info("Akun administrator '{$user->email}' berhasil dibuat.");

        return self::SUCCESS;
    }
}
