<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarUsuarioRequest;
use App\Http\Requests\SenhaRequest;
use App\Models\Cidade;
use App\Models\Perfil;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private UsuarioService $usuarios) {}

    public function index(Request $request): View
    {
        return view('usuarios.index', [
            'usuarios' => User::query()
                ->with(['perfil.permissoes', 'cidades'])
                ->filtrar($request->only('busca', 'perfil', 'status'))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'perfis' => Perfil::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new User(['ativo' => true]));
    }

    public function store(SalvarUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarios->salvar(new User, $request->validated());

        return redirect()->route('usuarios.index')->with('sucesso', "Usuário {$usuario->name} criado.");
    }

    public function edit(User $usuario): View
    {
        return $this->formulario($usuario);
    }

    public function update(SalvarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarios->salvar($usuario, $request->validated());

        return redirect()->route('usuarios.index')->with('sucesso', "Usuário {$usuario->name} atualizado.");
    }

    public function editarSenha(Request $request, User $usuario): View|RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return redirect()->route('minha-senha.edit');
        }

        return view('usuarios.senha', ['usuario' => $usuario]);
    }

    public function redefinirSenha(SenhaRequest $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->is($request->user()), 403);

        $this->usuarios->redefinirSenha($usuario, $request->validated('password'));

        return redirect()->route('usuarios.index')->with('sucesso', "Senha de {$usuario->name} redefinida. As sessões abertas dele foram encerradas.");
    }

    private function formulario(User $usuario): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'perfis' => Perfil::orderBy('nome')->pluck('nome', 'id'),
            'cidades' => Cidade::orderBy('nome')->get(),
            'cidadesMarcadas' => $usuario->exists ? $usuario->cidades()->pluck('cidades.id')->all() : [],
        ]);
    }
}
