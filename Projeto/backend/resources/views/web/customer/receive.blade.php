@extends('web.layouts.app',['title'=>'Receber Pix'])
@section('content')
@include('web.components.heading',['heading'=>'Receber com Pix','subtitle'=>'Gere um código Pix copia e cola para compartilhar com quem vai pagar.'])
<div class="grid grid-main"><section class="panel"><div class="panel-header"><h2>Criar cobrança Pix</h2></div>
<form method="post" action="{{ route('web.pix.charge') }}">@csrf<div class="form-grid"><div class="field"><label for="expiracao_minutos">Validade da cobrança</label><select id="expiracao_minutos" name="expiracao_minutos">@foreach([5, 10, 15, 30, 60] as $minutos)<option value="{{ $minutos }}" @selected((int)old('expiracao_minutos',5)===$minutos)>{{ $minutos }} minutos</option>@endforeach</select></div><div class="field"><label for="valor">Valor (R$)</label><input id="valor" name="valor" inputmode="decimal" placeholder="250,00" required></div><div class="field"><label for="descricao">Descrição <span class="optional">(opcional)</span></label><input id="descricao" name="descricao" placeholder="Ex.: prestação de serviço" maxlength="120"></div></div><div class="form-actions"><button class="btn btn-primary">Gerar cobrança</button></div></form>
@if($cobranca)<div class="divider"></div><span class="pill green">Cobrança gerada</span><h3 style="margin:15px 0 8px">Pix copia e cola</h3><p class="muted sm">Compartilhe o código com o pagador. A cobrança vence em {{ $cobranca['expiracao_minutos'] ?? 5 }} minutos.</p><div style="display:flex;justify-content:center;margin:24px auto 8px"><canvas data-pix-qr="{{ $cobranca['payload_emv'] }}" width="220" height="220" aria-label="QR Code da cobrança Pix"></canvas></div><div class="field"><label for="payload">Código EMV</label><textarea id="payload" readonly rows="5">{{ $cobranca['payload_emv'] }}</textarea></div><div class="form-actions"><button class="btn btn-outline" type="button" data-copy-target="payload">@include('web.components.icon',['name'=>'copy']) Copiar código</button></div><div class="detail-list"><div class="detail-item"><span class="key">Identificador TXID</span><strong>{{ $cobranca['txid'] }}</strong></div><div class="detail-item"><span class="key">Valor</span><strong>R$ {{ number_format($cobranca['valor_centavos']/100,2,',','.') }}</strong></div></div>@endif
<section class="panel" style="margin-top:24px"><div class="panel-header"><h2>Histórico de cobranças</h2><span class="pill blue">{{ $historico->total() }} solicitações</span></div>
@forelse($historico as $registro)
@php($situacao = $registro->situacao())
<div class="detail-list" style="margin-bottom:18px;border-bottom:1px solid #e2e8f0;padding-bottom:14px">
<div class="detail-item"><span class="key">TXID</span><strong>{{ $registro->txid }}</strong></div>
<div class="detail-item"><span class="key">Valor</span><strong>R$ {{ number_format($registro->valor_centavos/100,2,',','.') }}</strong></div>
<div class="detail-item"><span class="key">Situação</span><span class="pill {{ $situacao==='PAGA'?'green':($situacao==='ATIVA'?'blue':'orange') }}">{{ $situacao }}</span></div>
<div class="detail-item"><span class="key">Expira em</span><span>{{ $registro->expira_em->format('d/m/Y H:i') }}</span></div>
@if($registro->descricao)<div class="detail-item"><span class="key">Descrição</span><span>{{ $registro->descricao }}</span></div>@endif
@if($situacao==='ATIVA' && $registro->payload_emv)
<div class="field"><label for="pix-historico-{{ $registro->id }}">Código Pix</label><textarea id="pix-historico-{{ $registro->id }}" rows="2" readonly>{{ $registro->payload_emv }}</textarea></div>
<div class="form-actions"><button class="btn btn-outline" type="button" data-copy-target="pix-historico-{{ $registro->id }}">Copiar código</button></div>
@endif
@if($situacao==='ATIVA')<form method="post" action="{{ route('web.pix.charge.cancel',$registro) }}" data-confirm="Cancelar esta cobrança?">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Cancelar solicitação</button></form>@endif
</div>
@empty @include('web.components.empty',['heading'=>'Nenhuma cobrança ainda']) @endforelse
@include('web.components.pagination',['paginator'=>$historico])
</section>
</section><div class="stack"><div class="panel"><h3>Dados da conta</h3><div class="detail-list"><div class="detail-item"><span class="key">Agência</span><strong>{{ $conta->agencia }}</strong></div><div class="detail-item"><span class="key">Conta</span><strong>{{ $conta->numero }}</strong></div></div></div><div class="info-box">@include('web.components.icon',['name'=>'info'])<p>O pagador pode ler o QR ou colar o código na área de Pagamentos do Fluxo.</p></div></div></div>
@endsection
