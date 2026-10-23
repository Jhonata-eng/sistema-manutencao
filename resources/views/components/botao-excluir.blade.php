@props(['acao', 'confirmar', 'rotulo' => null])
<form method="POST" action="{{ $acao }}" class="d-inline" data-confirmar="{{ $confirmar }}">
    @csrf @method('DELETE')
    <button {{ $attributes->class(['btn btn-outline-danger', 'btn-sm' => ! $rotulo]) }} title="Excluir">
        <i class="bi bi-trash"></i>@if ($rotulo)<span class="ms-1">{{ $rotulo }}</span>@endif
    </button>
</form>
