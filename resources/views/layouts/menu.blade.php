<ul class="nav flex-column py-2">
    <x-menu-item rota="inicio" icone="house">Início</x-menu-item>

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
