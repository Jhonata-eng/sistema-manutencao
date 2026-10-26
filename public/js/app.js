// Formulários com data-confirmar="mensagem" pedem confirmação antes de enviar.
document.addEventListener('submit', (evento) => {
    const mensagem = evento.target.dataset.confirmar;
    if (mensagem && !window.confirm(mensagem)) {
        evento.preventDefault();
    }
});

// Elementos com data-exibir-se="#campo" data-valores="a,b" só aparecem quando o campo tem um desses
// valores (para checkbox, os valores são "marcado" e "desmarcado").
function atualizarCamposCondicionais() {
    document.querySelectorAll('[data-exibir-se]').forEach((elemento) => {
        const campo = document.querySelector(elemento.dataset.exibirSe);
        if (!campo) {
            return;
        }
        const valor = campo.type === 'checkbox' ? (campo.checked ? 'marcado' : 'desmarcado') : campo.value;
        elemento.hidden = !elemento.dataset.valores.split(',').includes(valor);
    });
}

document.addEventListener('change', atualizarCamposCondicionais);
document.addEventListener('DOMContentLoaded', atualizarCamposCondicionais);
