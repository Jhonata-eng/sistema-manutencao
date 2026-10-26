// Gráficos do painel (Chart.js). Os dados vêm de <script type="application/json" id="dados-graficos">.
// Especificação visual: barras de até 24px com ponta arredondada de 4px e base reta, linha de 2px com
// marcadores de 8px e anel na cor da superfície, grade fina e discreta, um só eixo de valores,
// cores por série em ordem fixa (validadas para daltonismo) e textos nas cores de texto.
(() => {
    const elemento = document.getElementById('dados-graficos');
    if (!elemento || !window.Chart) {
        return;
    }
    const dados = JSON.parse(elemento.textContent);

    const cores = {
        serie1: '#2a78d6',
        serie2: '#eb6834',
        serie3: '#1baf7a',
        superficie: '#ffffff',
        grade: '#e1e0d9',
        eixo: '#c3c2b7',
        texto: '#52514e',
        textoSuave: '#898781',
    };
    const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const moedaCompacta = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', notation: 'compact', maximumFractionDigits: 1 });

    Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", sans-serif';
    Chart.defaults.color = cores.texto;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.plugins.tooltip.callbacks.label = (ctx) => `${ctx.dataset.label}: ${moeda.format(ctx.parsed[ctx.chart.options.indexAxis === 'y' ? 'x' : 'y'])}`;

    const eixoValor = {
        beginAtZero: true,
        grid: { color: cores.grade },
        border: { display: false },
        ticks: { color: cores.textoSuave, callback: (valor) => moedaCompacta.format(valor) },
    };
    const eixoCategoria = { grid: { display: false }, border: { color: cores.eixo }, ticks: { color: cores.textoSuave } };
    const legenda = {
        position: 'top',
        align: 'start',
        labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, color: cores.texto, sort: (a, b) => a.datasetIndex - b.datasetIndex },
    };

    // Clique numa barra abre a visão unificada com o mesmo recorte; o cursor indica que é clicável.
    const navegacao = (urls) => ({
        onClick: (evento, elementos) => {
            const alvo = elementos[0];
            const url = alvo && urls[alvo.datasetIndex]?.[alvo.index];
            if (url) {
                window.location.href = url;
            }
        },
        onHover: (evento, elementos) => {
            evento.native.target.style.cursor = elementos.length ? 'pointer' : 'default';
        },
    });

    const pontaArredondada = { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 };

    new Chart(document.getElementById('grafico-previsao'), {
        type: 'bar',
        data: {
            labels: dados.previsao.rotulos,
            datasets: [{
                label: 'Previsto',
                data: dados.previsao.valores,
                backgroundColor: cores.serie1,
                maxBarThickness: 24,
                borderRadius: pontaArredondada,
                borderSkipped: false,
            }],
        },
        options: {
            ...navegacao([dados.previsao.urls]),
            plugins: { legend: { display: false } },
            scales: { x: eixoCategoria, y: eixoValor },
        },
    });

    // Realizado: barras empilhadas (programadas embaixo, não programadas em cima, separadas por 2px
    // na cor da superfície) e a linha do previsto no mesmo eixo.
    const realizado = dados.realizado;
    new Chart(document.getElementById('grafico-realizado'), {
        data: {
            labels: realizado.rotulos,
            datasets: [
                {
                    type: 'bar',
                    label: 'Programadas',
                    data: realizado.programadas,
                    stack: 'realizado',
                    order: 2,
                    backgroundColor: cores.serie1,
                    maxBarThickness: 24,
                    borderSkipped: false,
                    borderRadius: (ctx) => (realizado.naoProgramadas[ctx.dataIndex] > 0 ? 0 : pontaArredondada),
                },
                {
                    type: 'bar',
                    label: 'Não programadas',
                    data: realizado.naoProgramadas,
                    stack: 'realizado',
                    order: 2,
                    backgroundColor: cores.serie2,
                    maxBarThickness: 24,
                    borderSkipped: false,
                    borderRadius: pontaArredondada,
                    borderColor: cores.superficie,
                    borderWidth: (ctx) => ({ top: 0, left: 0, right: 0, bottom: realizado.programadas[ctx.dataIndex] > 0 ? 2 : 0 }),
                },
                {
                    type: 'line',
                    label: 'Previsto',
                    data: realizado.previsto,
                    stack: 'previsto',
                    order: 1,
                    borderColor: cores.serie3,
                    backgroundColor: cores.serie3,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    pointBorderColor: cores.superficie,
                    pointBorderWidth: 2,
                    tension: 0,
                },
            ],
        },
        options: {
            ...navegacao(realizado.urls),
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: legenda },
            scales: { x: { ...eixoCategoria, stacked: true }, y: { ...eixoValor, stacked: true } },
        },
    });

    // Distribuição: barras horizontais de uma série (identidade pelo rótulo do eixo, não pela cor).
    const distribuicao = (id, serie) => {
        const canvas = document.getElementById(id);
        if (!canvas || serie.valores.length === 0) {
            return;
        }
        canvas.parentElement.style.height = `${Math.max(140, serie.valores.length * 34 + 40)}px`;
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: serie.rotulos,
                datasets: [{
                    label: 'Realizado',
                    data: serie.valores,
                    backgroundColor: cores.serie1,
                    maxBarThickness: 24,
                    borderRadius: { topLeft: 0, bottomLeft: 0, topRight: 4, bottomRight: 4 },
                    borderSkipped: false,
                }],
            },
            options: {
                indexAxis: 'y',
                ...navegacao([serie.urls]),
                plugins: { legend: { display: false } },
                scales: { x: eixoValor, y: { ...eixoCategoria, ticks: { color: cores.texto } } },
            },
        });
    };
    distribuicao('grafico-por-tipo', dados.porTipo);
    distribuicao('grafico-por-cidade', dados.porCidade);
})();
