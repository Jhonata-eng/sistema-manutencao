@props(['pendentes', 'exibirSe', 'valores', 'mensagem'])
{{-- Confirmação obrigatória (validada no backend) antes de cancelar manutenções pendentes. Aparece quando o campo indicado assume um dos valores. --}}
@if ($pendentes > 0)
    <div class="alert alert-warning" data-exibir-se="{{ $exibirSe }}" data-valores="{{ $valores }}">
        <div class="form-check mb-0">
            <input type="checkbox" name="confirmar_cancelamento" id="confirmar_cancelamento" value="1"
                   @class(['form-check-input', 'is-invalid' => $errors->has('confirmar_cancelamento')]) @checked(old('confirmar_cancelamento'))>
            <label class="form-check-label" for="confirmar_cancelamento">
                {{ $mensagem }} <strong>{{ $pendentes }} {{ $pendentes === 1 ? 'manutenção pendente será cancelada' : 'manutenções pendentes serão canceladas' }}</strong>, inclusive as atrasadas. Marque para confirmar.
            </label>
            @error('confirmar_cancelamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
@endif
