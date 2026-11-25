@extends('web.layouts.app',['title'=>'Extrato'])
@section('content')
@include('web.components.heading',['heading'=>'Seu extrato','subtitle'=>'Confira todas as entradas e saídas da sua conta com transparência.'])
<div class="grid grid-3"><div class="stat-card"><div><p class="stat-label">Conta</p><div class="stat-number" style="font-size:20px">{{ $conta->numero }}</div><p class="stat-note">Agência {{ $conta->agencia }}</p></div><span class="stat-icon">@include('web.components.icon',['name'=>'wallet'])</span></div><div class="stat-card"><div><p class="stat-label">Lançamentos encontrados</p><div class="stat-number">{{ $extrato->total() }}</div><p class="stat-note">Histórico completo</p></div><span class="stat-icon">@include('web.components.icon',['name'=>'history'])</span></div><div class="stat-card"><div><p class="stat-label">Página atual</p><div class="stat-number">{{ $extrato->currentPage() }}</div><p class="stat-note">20 lançamentos por página</p></div><span class="stat-icon">@include('web.components.icon',['name'=>'chart'])</span></div></div>
<section class="panel" style="margin-top:22px"><div class="panel-header"><h2>Histórico de movimentações</h2><span class="pill blue">Atualizado em tempo real</span></div>
@forelse($extrato as $movimento)@include('web.components.movement',['movimento'=>$movimento])@empty
     @include('web.components.empty',['heading'=>'Sem movimentações','description'=>'As operações feitas em sua conta aparecerão aqui.'])@endforelse
@include('web.components.pagination',['paginator'=>$extrato])</section>
@endsection
