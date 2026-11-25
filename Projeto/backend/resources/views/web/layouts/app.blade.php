<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f9fc">
    <meta name="description" content="Fluxo — seu dinheiro, no seu ritmo.">
    <title>{{ $title ?? 'Minha conta' }} · Fluxo Bank</title>
    <link rel="icon" href="{{ asset('assets/fluxo-mark.svg') }}" type="image/svg+xml">
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script defer src="{{ asset('assets/fluxo.js') }}"></script>
    @endif
    <link rel="stylesheet" href="{{ asset('assets/fluxo.css') }}">
    @stack('head')
</head>
<body class="app-body">
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
@php
    $usuario = auth('web')->user();
    $administrador = $usuario->ehBackoffice();
    $nav = $administrador ? [
        ['title'=>'Visão geral','route'=>'web.admin.dashboard','icon'=>'home'],
        ['title'=>'Contas e KYC','route'=>'web.admin.accounts','icon'=>'wallet'],
        ['title'=>'Chamados','route'=>'web.admin.tickets','icon'=>'headphones'],
        ['title'=>'Auditoria','route'=>'web.admin.audit','icon'=>'shield'],
        ['title'=>'Usuários','route'=>'web.admin.users.index','pattern'=>'web.admin.users.*','icon'=>'users'],
    ] : [
        ['title'=>'Início','route'=>'web.dashboard','icon'=>'home'],
        ['title'=>'Extrato','route'=>'web.extrato','icon'=>'history'],
        ['title'=>'Pix','route'=>'web.pix','icon'=>'pix'],
        ['title'=>'Cartões','route'=>'web.cards','icon'=>'card'],
        ['title'=>'Pagamentos','route'=>'web.payments','icon'=>'receipt'],
        ['title'=>'Agendamentos','route'=>'web.schedules','icon'=>'calendar'],
        ['title'=>'Assinaturas','route'=>'web.subscriptions','icon'=>'repeat'],
        ['title'=>'Viagens','route'=>'web.travel','icon'=>'plane'],
        ['title'=>'Suporte','route'=>'web.support','icon'=>'headphones'],
        ['title'=>'Meu perfil','route'=>'web.profile','icon'=>'user'],
    ];
    $tituloAtual = $title ?? ($administrador ? 'Administração' : 'Início');
    $mobileNav = $administrador ? array_slice($nav, 0, 4) : array_values(array_filter($nav, fn ($v) => in_array($v['route'], ['web.dashboard','web.extrato','web.pix','web.cards','web.profile'])));
@endphp
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand brand-sidebar" href="{{ route('web.home') }}" aria-label="Fluxo, ir para o início">
            <span class="brand-symbol"><img src="{{ asset('assets/fluxo-mark.svg') }}" alt=""></span>
            <span class="brand-name">fluxo<span class="brand-period">.</span><small>bank</small></span>
        </a>
        <div class="nav-eyebrow">MENU PRINCIPAL</div>
        <nav class="side-nav" aria-label="Navegação principal">
        @foreach($nav as $item)
            @if($item['route'] === 'web.admin.users.index' && $usuario->perfil !== 'ADMIN') @continue @endif
            <a class="nav-item {{ request()->routeIs($item['pattern'] ?? $item['route'], $item['route'].'.*') ? 'active' : '' }}" href="{{ route($item['route']) }}">
                @include('web.components.icon',['name'=>$item['icon']])
                <span>{{ $item['title'] }}</span>
                @if(request()->routeIs($item['pattern'] ?? $item['route'], $item['route'].'.*'))<i class="active-dot"></i>@endif
            </a>
        @endforeach
        </nav>
        <div class="sidebar-bottom">
            <form method="post" action="{{ route('web.logout') }}">@csrf
                <button class="nav-item nav-logout" type="submit">@include('web.components.icon',['name'=>'logout'])<span>Sair da conta</span></button>
            </form>
        </div>
    </aside>
    <div class="shell-main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-button mobile-menu-toggle" type="button" data-toggle-sidebar aria-label="Menu" aria-controls="sidebar" aria-expanded="false">@include('web.components.icon',['name'=>'menu'])</button>
                <div class="topbar-breadcrumb"><span>{{ $administrador ? 'Administração' : 'Minha conta' }}</span>@include('web.components.icon',['name'=>'chevron-right'])<strong>{{ $tituloAtual }}</strong></div>
            </div>
            <div class="topbar-right">
                <span class="topbar-date">{{ now()->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y') }}</span>
                <span class="topbar-divider"></span>
                <div class="avatar">{{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}</div>
                <div class="topbar-account"><strong>{{ $usuario->name }}</strong><small>{{ $administrador ? ($usuario->perfil === 'ADMIN' ? 'Administrador' : 'Atendente') : 'Cliente Fluxo' }}</small></div>
            </div>
        </header>
        <main class="page-container" id="conteudo" tabindex="-1">
            @if(session('sucesso')) <div class="alert alert-success" role="status">@include('web.components.icon',['name'=>'check'])<span>{{ session('sucesso') }}</span></div> @endif
            @if(session('erro')) <div class="alert alert-error" role="alert">@include('web.components.icon',['name'=>'info'])<span>{{ session('erro') }}</span></div> @endif
            @if($errors->any())
                <div class="alert alert-error" role="alert">@include('web.components.icon',['name'=>'info'])<div><strong>Confira os dados informados.</strong><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div></div>
            @endif
            @yield('content')
            <footer class="page-footer">© {{ now()->year }} Fluxo Bank</footer>
        </main>
    </div>
</div>
<nav class="mobile-bottom-nav" aria-label="Navegação rápida">
    @foreach($mobileNav as $item)
        <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['pattern'] ?? $item['route'], $item['route'].'.*') ? 'active' : '' }}">@include('web.components.icon',['name'=>$item['icon']])<span>{{ $item['title'] }}</span></a>
    @endforeach
</nav>
<div class="sidebar-overlay" data-sidebar-overlay></div>
<dialog id="action-confirm" class="confirm-dialog" aria-labelledby="confirm-title">
  <div class="confirm-icon">@include('web.components.icon',['name'=>'shield'])</div>
  <h2 id="confirm-title">Confirme esta operação</h2>
  <p id="confirm-message">Confira os dados antes de continuar.</p>
  <div class="form-actions"><button class="btn btn-muted" type="button" data-cancel-confirm>Cancelar</button><button class="btn btn-primary" type="button" data-accept-confirm>Sim, confirmar</button></div>
</dialog>
@stack('scripts')
</body>
</html>
