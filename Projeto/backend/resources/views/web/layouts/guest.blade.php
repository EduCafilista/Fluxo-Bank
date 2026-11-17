<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#245df0"><title>{{ $title ?? 'Acesso' }} · Fluxo Bank</title>
<link rel="icon" href="{{ asset('assets/fluxo-mark.svg') }}" type="image/svg+xml"><link rel="stylesheet" href="{{ asset('assets/fluxo.css') }}"><script defer src="{{ asset('assets/fluxo.js') }}"></script>
</head><body class="guest-body">
<div class="guest-layout">
 <section class="guest-feature"><a class="brand brand-light" href="{{ route('web.login') }}"><img src="{{ asset('assets/fluxo-mark.svg') }}" alt=""><span>fluxo<span class="brand-period">.</span></span></a>
   <div class="guest-feature-content"><span class="pill-light">BANCO DIGITAL, DO SEU JEITO</span><h1>Seu dinheiro,<br><em>no seu ritmo.</em></h1><p>Mais controle para realizar o que importa. Simples, seguro e sempre ao seu lado.</p><div class="guest-feature-card"><span>✦</span><div><strong>Uma experiência mais fluida.</strong><small>Contas, Pix e cartões em um só lugar.</small></div></div></div>
   <p class="guest-disclaimer">Protótipo acadêmico • Operações simuladas</p>
 </section>
 <main class="guest-main"><div class="guest-card">
    <div class="guest-mobile-brand brand"><img src="{{ asset('assets/fluxo-mark.svg') }}" alt=""><span>fluxo<span class="brand-period">.</span></span></div>
    @if(session('sucesso'))<div class="alert alert-success" role="status">{{ session('sucesso') }}</div>@endif
    @if(session('erro'))<div class="alert alert-error" role="alert">{{ session('erro') }}</div>@endif
    @if($errors->any())<div class="alert alert-error" role="alert"><ul>@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>@endif
    @yield('content')
 </div><p class="guest-bottom">© {{ now()->year }} Fluxo Bank. Ambiente de demonstração.</p></main>
</div>
</body></html>
