<?php

namespace App\Console\Commands;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:criar-admin {--nome= : Nome do administrador} {--email= : E-mail de login} {--senha= : Senha (se omitida, é perguntada sem eco)}')]
#[Description('Cria um usuário com o perfil Administrador (use no primeiro acesso)')]
class CriarAdministrador extends Command
{
    public function handle(): int
    {
        // Garante permissões e perfis padrão (o seeder é idempotente).
        $this->callSilently('db:seed', ['--force' => true]);

        $dados = [
            'name' => $this->option('nome') ?? $this->ask('Nome'),
            'email' => $this->option('email') ?? $this->ask('E-mail'),
            'password' => $this->option('senha') ?? $this->secret('Senha (mínimo de 8 caracteres)'),
        ];

        $validador = Validator::make($dados, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        User::create([
            ...$dados,
            'perfil_id' => Perfil::where('nome', 'Administrador')->value('id'),
            'ativo' => true,
        ]);

        $this->info("Administrador {$dados['email']} criado.");

        return self::SUCCESS;
    }
}
