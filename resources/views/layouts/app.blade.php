<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/5.3.8/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.13.2/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset_versionado('css/app.css') }}">
    @stack('estilos')
</head>
<body>
<header class="navbar navbar-dark bg-dark sticky-top shadow-sm px-3 py-2 flex-nowrap">
    <button class="navbar-toggler d-lg-none border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu-lateral" aria-label="Abrir menu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand fw-semibold me-auto text-truncate" href="{{ route('inicio') }}">
        <i class="bi bi-tools me-1"></i> {{ config('app.name') }}
    </a>
    <div class="dropdown">
        <button class="btn btn-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i>
            <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-body-secondary">{{ auth()->user()->perfil->nome }}</span></li>
            <li><a class="dropdown-item" href="{{ route('minha-senha.edit') }}"><i class="bi bi-key me-2"></i>Alterar senha</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sair</button>
                </form>
            </li>
        </ul>
    </div>
</header>

<div class="d-flex">
    <nav class="menu-lateral offcanvas-lg offcanvas-start bg-body border-end" tabindex="-1" id="menu-lateral" aria-label="Menu">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold">Menu</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menu-lateral" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('layouts.menu')
        </div>
    </nav>

    <main class="conteudo flex-grow-1 p-3 p-lg-4">
        <x-alertas />
        @yield('conteudo')
    </main>
</div>

<script src="{{ asset('vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset_versionado('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
