@if (session('sucesso'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('sucesso') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
@if (session('erro'))
    <div class="alert alert-danger" role="alert">{{ session('erro') }}</div>
@endif
@error('protecao')
    <div class="alert alert-danger" role="alert">{{ $message }}</div>
@enderror
