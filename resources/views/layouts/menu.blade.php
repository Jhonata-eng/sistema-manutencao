<ul class="nav flex-column py-2">
    <x-menu-item rota="inicio" icone="house">Início</x-menu-item>

    @can('ativos.visualizar')
        <x-menu-item rota="ativos.index" ativo="ativos.*" icone="hdd-rack">Ativos</x-menu-item>
    @endcan

    @can('manutencoes.lancar_nao_programada')
        <li class="menu-secao">Manutenções</li>
        <x-menu-item rota="manutencoes.nao-programada.create" ativo="manutencoes.nao-programada.*" icone="wrench-adjustable">Lançar não programada</x-menu-item>
    @endcan

    @can('cadastros.gerenciar')
        <li class="menu-secao">Cadastros</li>
        <x-menu-item rota="cadastros.cidades.index" ativo="cadastros.cidades.*" icone="geo-alt">Cidades</x-menu-item>
        <x-menu-item rota="cadastros.tipos.index" ativo="cadastros.tipos.*" icone="tags">Tipos de equipamento</x-menu-item>
        <x-menu-item rota="cadastros.responsaveis.index" ativo="cadastros.responsaveis.*" icone="person-badge">Responsáveis</x-menu-item>
        <x-menu-item rota="cadastros.fornecedores.index" ativo="cadastros.fornecedores.*" icone="truck">Fornecedores</x-menu-item>
    @endcan

    @canany(['usuarios.gerenciar', 'perfis.gerenciar', 'auditoria.visualizar'])
        <li class="menu-secao">Administração</li>
        @can('usuarios.gerenciar')
            <x-menu-item rota="usuarios.index" ativo="usuarios.*" icone="people">Usuários</x-menu-item>
        @endcan
        @can('perfis.gerenciar')
            <x-menu-item rota="perfis.index" ativo="perfis.*" icone="shield-lock">Perfis e permissões</x-menu-item>
        @endcan
        @can('auditoria.visualizar')
            <x-menu-item rota="auditoria.index" ativo="auditoria.*" icone="journal-text">Auditoria</x-menu-item>
        @endcan
    @endcanany
</ul>
