<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarPerfilRequest;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Services\PerfilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private PerfilService $perfis) {}

    public function index(): View
    {
        return view('perfis.index', [
            'perfis' => Perfil::withCount(['usuarios', 'permissoes'])->orderBy('nome')->get(),
            'totalPermissoes' => count(Permissao::CATALOGO),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Perfil);
    }

    public function store(SalvarPerfilRequest $request): RedirectResponse
    {
        $perfil = $this->perfis->salvar(new Perfil, $request->validated());

        return redirect()->route('perfis.index')->with('sucesso', "Perfil {$perfil->nome} criado.");
    }

    public function edit(Perfil $perfil): View
    {
        return $this->formulario($perfil);
    }

    public function update(SalvarPerfilRequest $request, Perfil $perfil): RedirectResponse
    {
        $this->perfis->salvar($perfil, $request->validated());

        return redirect()->route('perfis.index')->with('sucesso', "Perfil {$perfil->nome} atualizado.");
    }

    public function destroy(Perfil $perfil): RedirectResponse
    {
        $this->perfis->excluir($perfil);

        return redirect()->route('perfis.index')->with('sucesso', "Perfil {$perfil->nome} excluído.");
    }

    private function formulario(Perfil $perfil): View
    {
        return view('perfis.form', [
            'perfil' => $perfil,
            'gruposPermissoes' => Permissao::orderBy('id')->get()->groupBy('grupo'),
            'permissoesMarcadas' => $perfil->exists ? $perfil->permissoes()->pluck('permissoes.id')->all() : [],
        ]);
    }
}
