@php $credito = $movimento->tipo === 'credito'; $desc = $movimento->descricao ?: ($movimento->transacao?->descricao ?: str_replace('_',' ', $movimento->transacao?->tipo ?? 'Movimentação')); @endphp
<div class="movement-row"><div class="movement-icon {{ $credito ? 'positive' : 'neutral' }}">@include('web.components.icon',['name'=>$credito ? 'arrow-down-left' : 'arrow-up-right'])</div>
  <div class="movement-detail"><strong>{{ $desc }}</strong><small>{{ $movimento->created_at?->locale('pt_BR')->translatedFormat('d M Y · H:i') }} · {{ str_replace('_',' ', $movimento->transacao?->tipo ?? $movimento->tipo) }}</small></div>
  <div class="movement-value {{ $credito ? 'positive-text' : '' }}">{{ $credito ? '+' : '−' }} R$ {{ number_format(abs($movimento->valor_centavos)/100, 2, ',', '.') }}
    @if($movimento->transacao_id)<a href="{{ route('web.receipt',$movimento->transacao_id) }}" aria-label="Ver comprovante da transação {{ $movimento->transacao_id }}">Ver comprovante</a>@endif
  </div>
</div>
