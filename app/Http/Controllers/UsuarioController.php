<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarUsuarioRequest;
use App\Http\Requests\SenhaRequest;
use App\Models\Perfil;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
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

    public function create(Request $request): View
    {
        return $this->formulario($request->user(), new User(['ativo' => true]));
    }

    public function store(SalvarUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarios->salvar(new User, $request->validated());

        return redirect()->route('usuarios.index')->with('sucesso', "Usuário {$usuario->name} criado.");
    }

    #[Authorize('update', 'usuario')]
    public function edit(Request $request, User $usuario): View
    {
        return $this->formulario($request->user(), $usuario);
    }

    public function update(SalvarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarios->salvar($usuario, $request->validated());

        return redirect()->route('usuarios.index')->with('sucesso', "Usuário {$usuario->name} atualizado.");
    }

    #[Authorize('update', 'usuario')]
    public function editarSenha(Request $request, User $usuario): View|RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return redirect()->route('minha-senha.edit');
        }

        return view('usuarios.senha', ['usuario' => $usuario]);
    }

    #[Authorize('update', 'usuario')]
    public function redefinirSenha(SenhaRequest $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->is($request->user()), 403);

        $this->usuarios->redefinirSenha($usuario, $request->validated('password'));

        return redirect()->route('usuarios.index')->with('sucesso', "Senha de {$usuario->name} redefinida. As sessões abertas dele foram encerradas.");
    }

    /** Só oferece perfis que o autor pode conceder e cidades às quais ele tem acesso. */
    private function formulario(User $autor, User $usuario): View
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'perfis' => Perfil::with('permissoes')->orderBy('nome')->get()
                ->filter(fn (Perfil $perfil) => $autor->podeConcederPerfil($perfil))
                ->pluck('nome', 'id'),
            'cidades' => $autor->cidadesAcessiveis()->orderBy('nome')->get(),
            'cidadesMarcadas' => $usuario->exists ? $usuario->cidades()->pluck('cidades.id')->all() : [],
        ]);
    }
}
