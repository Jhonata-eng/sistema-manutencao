<?php

namespace Database\Seeders;

use App\Enums\StatusManutencao;
use App\Enums\TipoAnexo;
use App\Enums\TipoManutencao;
use App\Models\Anexo;
use App\Models\Ativo;
use App\Models\Cidade;
use App\Models\CorrecaoValor;
use App\Models\Fornecedor;
use App\Models\Manutencao;
use App\Models\Perfil;
use App\Models\PlanoManutencao;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Models\User;
use App\Services\GeradorOcorrencias;
use App\Services\Recorrencia;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Dados de exemplo para testar o painel: ~12 meses de histórico e 12 meses de previsão.
 * OPCIONAL e só para ambientes de teste: `php artisan db:seed --class=DemonstracaoSeeder`.
 * Não roda de novo se já houver ativos DEMO-*. Cria dois usuários de exemplo (senha demo12345).
 */
class DemonstracaoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** tipo => [prefixo, marcas/modelos, capacidade, planos: [serviço, meses, valor base]] */
    private const CATALOGO = [
        'Ar-condicionado' => ['AC', ['Carrier XPower', 'Daikin Inverter', 'LG Dual'], '18.000 BTUs', [
            ['Limpeza de filtros e serpentina', 1, '220.00'],
            ['Revisão completa e carga de gás', 6, '780.00'],
        ]],
        'Gerador' => ['GER', ['Stemac GTA 150', 'Cummins C150', 'Heimer GEH-120'], '150 kVA', [
            ['Revisão preventiva', 3, '1850.00'],
            ['Troca de óleo e filtros', 6, '950.00'],
        ]],
        'Nobreak' => ['NB', ['APC Smart-UPS 3000', 'SMS Sinus Triad', 'Eaton 9SX'], '3 kVA', [
            ['Teste de autonomia das baterias', 3, '320.00'],
            ['Troca do banco de baterias', 12, '4200.00'],
        ]],
        'Bebedouro industrial' => ['BEB', ['IBBL Inox 100', 'Libell Industrial'], '100 L', [
            ['Higienização e troca do elemento filtrante', 3, '160.00'],
        ]],
    ];

    private const CIDADES = ['Campinas', 'Sorocaba', 'Jundiaí', 'Piracicaba'];

    private const NAO_PROGRAMADAS = [
        'Troca do compressor queimado', 'Reparo de vazamento de gás', 'Substituição de placa eletrônica',
        'Troca do motor de partida', 'Reparo no quadro de transferência', 'Substituição de bateria estufada',
        'Troca da bomba d’água', 'Reparo do sensor de temperatura',
    ];

    private Randomizer $sorteio;

    private User $autor;

    public function run(GeradorOcorrencias $gerador): void
    {
        if (Ativo::withTrashed()->where('patrimonio', 'like', 'DEMO-%')->exists()) {
            $this->command?->info('Dados de demonstração já carregados.');

            return;
        }

        $autor = User::whereHas('perfil', fn ($q) => $q->where('nome', 'Administrador'))->first();
        if (! $autor) {
            $this->command?->error('Crie um administrador antes: php artisan app:criar-admin');

            return;
        }

        $this->autor = $autor;
        $this->sorteio = new Randomizer(new Mt19937(2026));

        DB::transaction(function () use ($gerador) {
            $cidades = collect(self::CIDADES)->map(fn ($nome) => Cidade::firstOrCreate(['nome' => $nome, 'uf' => 'SP']));
            $responsaveis = collect(['Carlos Lima', 'Fernanda Rocha', 'Paulo Mendes', 'Juliana Costa'])
                ->map(fn ($nome) => Responsavel::firstOrCreate(['nome' => $nome], ['setor' => 'Infraestrutura']));
            $fornecedores = collect(['Refrigeração Alfa Ltda', 'Energia Beta Serviços', 'Clima Total Manutenção', 'Força & Luz Geradores', 'Águas Claras Filtros'])
                ->map(fn ($nome) => Fornecedor::firstOrCreate(['nome' => $nome]));

            $numero = 0;
            foreach ($cidades as $cidade) {
                foreach (self::CATALOGO as $tipoNome => [$prefixo, $modelos, $capacidade, $planos]) {
                    $tipo = TipoEquipamento::firstOrCreate(['nome' => $tipoNome]);
                    for ($i = 0; $i < ($prefixo === 'AC' ? 2 : 1); $i++) {
                        $numero++;
                        $ativo = $this->ativo($cidade, $tipo, $prefixo, $numero, $modelos, $capacidade, $responsaveis->random());
                        foreach ($planos as [$servico, $meses, $valor]) {
                            $plano = $this->planoComHistorico($ativo, $servico, $meses, $valor, $fornecedores->random());
                            $gerador->gerar($plano);
                        }
                    }
                }
            }

            $this->naoProgramadas($fornecedores);
            $this->correcoes();
            $this->usuarios($cidades);
        });

        $this->command?->info('Demonstração carregada: '.Ativo::where('patrimonio', 'like', 'DEMO-%')->count().' ativos, '.Manutencao::count().' manutenções.');
        $this->command?->info('Usuários de exemplo (senha demo12345): tecnico.demo@exemplo.com.br (Técnico, Campinas) e gestor.demo@exemplo.com.br (Gestor, Sorocaba e Jundiaí).');
    }

    private function ativo(Cidade $cidade, TipoEquipamento $tipo, string $prefixo, int $numero, array $modelos, string $capacidade, Responsavel $responsavel): Ativo
    {
        [$marca, $modelo] = explode(' ', $modelos[$this->sorteio->getInt(0, count($modelos) - 1)], 2);
        $ativo = new Ativo([
            'patrimonio' => sprintf('DEMO-%s-%03d', $prefixo, $numero),
            'tipo_equipamento_id' => $tipo->id,
            'marca' => $marca,
            'modelo' => $modelo,
            'numero_serie' => 'SN'.$this->sorteio->getInt(100000, 999999),
            'ano_fabricacao' => $this->sorteio->getInt(2015, 2025),
            'capacidade' => $capacidade,
            'cidade_id' => $cidade->id,
            'localizacao' => 'POP '.['Centro', 'Norte', 'Sul', 'Industrial'][$this->sorteio->getInt(0, 3)].', sala técnica',
            'responsavel_id' => $responsavel->id,
            'status' => 'em_operacao',
        ]);
        $ativo->criado_por = $this->autor->id;
        $ativo->save();

        return $ativo;
    }

    /** Plano iniciado há ~12 meses, com as ocorrências passadas já resolvidas (ou atrasadas, nas mais recentes). */
    private function planoComHistorico(Ativo $ativo, string $servico, int $meses, string $valor, Fornecedor $fornecedor): PlanoManutencao
    {
        $hoje = CarbonImmutable::today();
        $inicio = $hoje->subMonthsNoOverflow(12)->setDay($this->sorteio->getInt(1, 28));

        $plano = new PlanoManutencao([
            'descricao' => $servico,
            'periodicidade_meses' => $meses,
            'data_inicio' => $inicio,
            'valor_estimado' => $valor,
            'fornecedor_id' => $fornecedor->id,
            'ativo' => true,
        ]);
        $plano->ativo_id = $ativo->id;
        $plano->save();

        foreach (Recorrencia::datas($inicio, $meses, null, $inicio, $hoje) as $data) {
            $manutencao = new Manutencao([
                'tipo' => TipoManutencao::Programada,
                'data_referencia' => $data,
                'data_prevista' => $data,
                'status' => StatusManutencao::Prevista,
                'valor_previsto' => $valor,
            ]);
            $manutencao->ativo_id = $ativo->id;
            $manutencao->plano_id = $plano->id;

            $sorte = $this->sorteio->getInt(1, 100);
            $recente = $data->gt($hoje->subDays(45));
            if ($recente && $sorte <= 40) {
                $manutencao->save(); // fica atrasada
            } elseif ($sorte <= 8) {
                $manutencao->forceFill([
                    'status' => StatusManutencao::Cancelada,
                    'motivo_cancelamento' => ['Equipamento em reforma', 'Fornecedor indisponível', 'Ativo desligado no período'][$this->sorteio->getInt(0, 2)],
                    'cancelado_por' => $this->autor->id,
                    'cancelado_em' => $data,
                ])->save();
            } else {
                $this->executar($manutencao, $data->addDays($this->sorteio->getInt(-3, 7))->min($hoje), $this->variar($valor), $servico, $fornecedor);
            }
        }

        return $plano;
    }

    private function naoProgramadas($fornecedores): void
    {
        $ativos = Ativo::where('patrimonio', 'like', 'DEMO-%')->get();
        $hoje = CarbonImmutable::today();

        for ($i = 0; $i < 16; $i++) {
            $manutencao = new Manutencao(['tipo' => TipoManutencao::NaoProgramada, 'status' => StatusManutencao::Prevista]);
            $manutencao->ativo_id = $ativos[$this->sorteio->getInt(0, $ativos->count() - 1)]->id;
            $valor = sprintf('%d.%02d', $this->sorteio->getInt(250, 6500), $this->sorteio->getInt(0, 99));
            $descricao = self::NAO_PROGRAMADAS[$this->sorteio->getInt(0, count(self::NAO_PROGRAMADAS) - 1)];
            $this->executar($manutencao, $hoje->subDays($this->sorteio->getInt(0, 360)), $valor, $descricao, $fornecedores->random());
        }
    }

    private function executar(Manutencao $manutencao, CarbonImmutable $data, string $valor, string $descricao, Fornecedor $fornecedor): void
    {
        $manutencao->fill([
            'status' => StatusManutencao::Realizada,
            'data_execucao' => $data,
            'valor_real' => $valor,
            'fornecedor_id' => $fornecedor->id,
            'descricao' => $descricao,
        ]);
        $manutencao->registrado_por = $this->autor->id;
        $manutencao->save();

        // ~70% com nota fiscal, ~10% com recibo, o resto fica "sem comprovante".
        $sorte = $this->sorteio->getInt(1, 100);
        if ($sorte <= 80) {
            $this->anexo($manutencao, $sorte <= 70 ? TipoAnexo::NotaFiscal : TipoAnexo::Recibo, $valor, $data);
        }
    }

    private function anexo(Manutencao $manutencao, TipoAnexo $tipo, string $valor, CarbonImmutable $data): Anexo
    {
        $caminho = 'anexos/demo/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($caminho, "%PDF-1.4\n% Documento de demonstração\n%%EOF\n");
        $numero = (string) $this->sorteio->getInt(1000, 99999);

        $anexo = new Anexo([
            'tipo' => $tipo,
            'numero_nota' => $numero,
            'data_emissao' => $data,
            'valor_nota' => $valor,
            'nome_original' => ($tipo === TipoAnexo::NotaFiscal ? 'nf-' : 'recibo-').$numero.'.pdf',
            'caminho' => $caminho,
            'mime' => 'application/pdf',
            'tamanho' => Storage::disk('local')->size($caminho),
        ]);
        $anexo->manutencao_id = $manutencao->id;
        $anexo->enviado_por = $this->autor->id;
        $anexo->save();

        return $anexo;
    }

    private function correcoes(): void
    {
        Manutencao::where('status', StatusManutencao::Realizada)
            ->whereHas('anexos', fn ($q) => $q->where('tipo', TipoAnexo::NotaFiscal))
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->each(function (Manutencao $manutencao) {
                $novo = $this->variar($manutencao->valor_real);
                CorrecaoValor::create([
                    'manutencao_id' => $manutencao->id,
                    'valor_anterior' => $manutencao->valor_real,
                    'valor_novo' => $novo,
                    'motivo' => 'Valor da nota fiscal diferente do lançado',
                    'anexo_id' => $manutencao->anexos()->value('id'),
                    'user_id' => $this->autor->id,
                ]);
                $manutencao->update(['valor_real' => $novo]);
            });
    }

    private function usuarios($cidades): void
    {
        foreach ([
            ['Técnico (demonstração)', 'tecnico.demo@exemplo.com.br', 'Técnico', ['Campinas']],
            ['Gestor (demonstração)', 'gestor.demo@exemplo.com.br', 'Gestor', ['Sorocaba', 'Jundiaí']],
        ] as [$nome, $email, $perfil, $nomesCidades]) {
            $usuario = User::firstOrCreate(['email' => $email], [
                'name' => $nome,
                'password' => 'demo12345',
                'perfil_id' => Perfil::where('nome', $perfil)->value('id'),
                'ativo' => true,
            ]);
            $usuario->cidades()->syncWithoutDetaching($cidades->whereIn('nome', $nomesCidades)->pluck('id'));
        }
    }

    /** Valor entre −15% e +25% do original, em centavos inteiros: nunca passa por float. */
    private function variar(string $valor): string
    {
        $centavos = (int) str_replace('.', '', $valor);
        $variado = intdiv($centavos * $this->sorteio->getInt(85, 125), 100);

        return sprintf('%d.%02d', intdiv($variado, 100), $variado % 100);
    }
}
