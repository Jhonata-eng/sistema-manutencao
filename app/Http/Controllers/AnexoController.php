<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnviarAnexoRequest;
use App\Models\Anexo;
use App\Models\Manutencao;
use App\Services\AnexoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    public function __construct(private AnexoService $anexos) {}

    public function store(EnviarAnexoRequest $request, Manutencao $manutencao): RedirectResponse
    {
        $this->anexos->enviar($manutencao, $request->anexo(), $request->user());

        return redirect()->route('manutencoes.show', $manutencao)->with('sucesso', 'Anexo enviado.');
    }

    /**
     * PDF e imagens abrem no navegador; XML é sempre baixado (um XML aberto no navegador
     * poderia executar conteúdo ativo). O Content-Type é o MIME conferido no envio.
     */
    #[Authorize('view', 'anexo')]
    public function show(Anexo $anexo): StreamedResponse
    {
        $abreNoNavegador = $anexo->mime === 'application/pdf' || str_starts_with($anexo->mime, 'image/');

        return Storage::disk('local')->response(
            $anexo->caminho,
            $anexo->nome_original,
            ['Content-Type' => $anexo->mime, 'X-Content-Type-Options' => 'nosniff'],
            $abreNoNavegador ? 'inline' : 'attachment',
        );
    }

    #[Authorize('delete', 'anexo')]
    public function destroy(Anexo $anexo): RedirectResponse
    {
        $this->anexos->excluir($anexo);

        return redirect()->route('manutencoes.show', $anexo->manutencao_id)->with('sucesso', 'Anexo excluído.');
    }
}
