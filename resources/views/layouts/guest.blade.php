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
<main class="container" style="max-width: 420px">
    <h1 class="h4 text-center mb-4"><i class="bi bi-tools me-1"></i> {{ config('app.name') }}</h1>
    @yield('conteudo')
</main>
</body>
</html>
