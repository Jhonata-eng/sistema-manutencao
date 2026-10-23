@props(['rota', 'icone', 'ativo' => null])
<li class="nav-item">
    <a href="{{ route($rota) }}" @class(['nav-link', 'active' => request()->routeIs($ativo ?? $rota)])>
        <i class="bi bi-{{ $icone }} me-2"></i>{{ $slot }}
    </a>
</li>
