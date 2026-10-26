<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas. A CSP vale só para páginas HTML (não para PDFs e
 * imagens servidos inline): scripts apenas do próprio servidor (não há script inline; o JSON do
 * painel usa type="application/json" e não é executado) e estilos inline só por causa dos atributos
 * style das views. O HSTS fica na configuração HTTPS do servidor web (README).
 */
class CabecalhosSeguranca
{
    private const CSP = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; "
        ."font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('X-Frame-Options', 'DENY');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (str_starts_with((string) $resposta->headers->get('Content-Type'), 'text/html')) {
            $resposta->headers->set('Content-Security-Policy', self::CSP);
        }

        return $resposta;
    }
}
