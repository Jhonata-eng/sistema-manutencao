<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/5.3.8/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/1.13.2/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset_versionado('css/app.css') }}">
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
<main class="container text-center" style="max-width: 520px">
    <div class="display-6 text-body-secondary mb-2">@yield('codigo')</div>
    <h1 class="h4">@yield('titulo')</h1>
    <p class="text-body-secondary">@yield('mensagem')</p>
    <a href="{{ url('/') }}" class="btn btn-primary">Voltar ao início</a>
</main>
</body>
</html>
