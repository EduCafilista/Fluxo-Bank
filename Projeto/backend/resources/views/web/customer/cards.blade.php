@extends('web.layouts.app', ['title' => 'Cartões'])

@section('content')
@include('web.components.heading', ['heading' => 'Meus cartões', 'subtitle' => 'Gerencie seus cartões físicos e virtuais.'])

<div class="grid grid-main">
    <div class="stack">
        @forelse($cartoes as $cartao)
            @php
                $modalidade = $cartao->modalidade === 'DEBITO' ? 'Débito' : 'Crédito';
                $formato = $cartao->fisico ? 'físico' : 'virtual';
                $ativo = $cartao->ativo();
            @endphp
            <section class="panel">
                <div class="panel-header">
                    <h2>Cartão de {{ mb_strtolower($modalidade) }} {{ $formato }}</h2>
                    <span class="pill {{ $ativo ? 'green' : 'red' }}">{{ ucfirst(mb_strtolower($cartao->status)) }}</span>
                </div>
                <div class="card-overview">
                    <div class="bank-card">
                        <div class="card-head"><span class="card-label">fluxo.</span><span class="card-chip" aria-hidden="true"></span></div>
                        <div class="card-number">{{ $cartao->numero_mascarado }}</div>
                        <div class="card-bottom">
                            <span>{{ mb_strtoupper(\Illuminate\Support\Str::before(auth('web')->user()->name, ' ')) }}</span>
                            <span>Validade {{ $cartao->validade?->format('m/Y') ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="detail-list">
                        <div class="detail-item"><span class="key">Modalidade</span><strong>{{ $modalidade }}</strong></div>
                        <div class="detail-item"><span class="key">Formato</span><strong>{{ ucfirst($formato) }}</strong></div>
                        <div class="detail-item"><span class="key">Limite aprovado</span><strong>R$ {{ number_format($cartao->limite_aprovado_centavos / 100, 2, ',', '.') }}</strong></div>
                        <div class="detail-item"><span class="key">Limite atual</span><strong>R$ {{ number_format($cartao->limite_atual_centavos / 100, 2, ',', '.') }}</strong></div>
                        @if($cartao->fisico)
                            <div class="detail-item">
                                <span class="key">Compras on-line</span>
                                <span class="pill {{ $cartao->fisico->permite_compras_online ? 'green' : 'orange' }}">{{ $cartao->fisico->permite_compras_online ? 'Ativas' : 'Desativadas' }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if($cartao->status !== 'CANCELADO')
                    <div class="card-manage">
                        <form method="post" action="{{ route('web.cards.block', $cartao) }}" data-confirm="Deseja alterar o bloqueio deste cartão?">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="bloquear" value="{{ $ativo ? 1 : 0 }}">
                            <button class="btn btn-outline btn-full" type="submit">{{ $ativo ? 'Bloquear cartão' : 'Desbloquear cartão' }}</button>
                        </form>
                        @if($cartao->fisico && $ativo)
                            <form method="post" action="{{ route('web.cards.virtual', $cartao) }}" data-confirm="Deseja gerar um cartão virtual para este cartão físico?">
                                @csrf
                                <button class="btn btn-outline btn-full" type="submit">Gerar virtual</button>
                            </form>
                        @endif
                        @if($cartao->fisico)
                            <form method="post" action="{{ route('web.cards.online', $cartao) }}" data-confirm="Alterar a permissão de compras on-line?">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="permitir" value="{{ $cartao->fisico->permite_compras_online ? 0 : 1 }}">
                                <button class="btn btn-outline btn-full" type="submit">{{ $cartao->fisico->permite_compras_online ? 'Desativar compras on-line' : 'Ativar compras on-line' }}</button>
                            </form>
                        @endif
                    </div>
                    <hr class="panel-divider">
                    <form class="form-actions form-end" method="post" action="{{ route('web.cards.limit', $cartao) }}">
                        @csrf
                        @method('PATCH')
                        <div class="field">
                            <label for="limite-{{ $cartao->id }}">Limite desejado (R$)</label>
                            <input id="limite-{{ $cartao->id }}" name="valor" inputmode="decimal" placeholder="1000,00" required>
                        </div>
                        <button class="btn btn-outline" type="submit">Ajustar limite</button>
                    </form>
                @endif
            </section>
        @empty
            <section class="panel">
                @include('web.components.empty', ['icon' => 'card', 'heading' => 'Você ainda não tem cartões'])
            </section>
        @endforelse
    </div>

    <div class="stack">
        @if(config('services.fluxo.simulacoes_financeiras', false))
            <section class="panel simulation-panel" aria-labelledby="simulation-title">
                <div class="panel-header">
                    <h2 id="simulation-title">Simular compra</h2>
                    @include('web.components.icon', ['name' => 'card'])
                </div>
                @if($cartoesSimulacao->isEmpty())
                    <p class="muted">Para simular, use uma conta ativa com cartão de débito válido e desbloqueado.</p>
                @else
                    <form class="stack" method="post" action="{{ route('web.cards.simulation') }}" data-confirm="Confirmar a compra simulada no débito?">
                        @csrf
                        <input type="hidden" name="idempotencia" value="{{ old('idempotencia', (string) \Illuminate\Support\Str::uuid()) }}">
                        <div class="field">
                            <label for="cartao-simulacao">Cartão de débito</label>
                            <select id="cartao-simulacao" name="cartao_id" required>
                                @foreach($cartoesSimulacao as $opcao)
                                    <option value="{{ $opcao->id }}" @selected((int) old('cartao_id') === $opcao->id)>
                                        {{ $opcao->fisico ? 'Físico' : 'Virtual' }} · final {{ substr($opcao->numero_mascarado, -4) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="valor-simulacao">Valor da compra (R$)</label>
                            <input id="valor-simulacao" name="valor_simulacao" value="{{ old('valor_simulacao') }}" placeholder="25,00" inputmode="decimal" required>
                        </div>
                        <div class="field">
                            <label for="estabelecimento">Estabelecimento</label>
                            <input id="estabelecimento" name="estabelecimento" value="{{ old('estabelecimento') }}" maxlength="120" required>
                        </div>
                        <div class="field">
                            <label for="localidade">Localidade <span class="optional">(opcional)</span></label>
                            <input id="localidade" name="localidade" value="{{ old('localidade') }}" maxlength="120" placeholder="Ex.: São Paulo">
                        </div>
                        <label class="check-label"><input type="checkbox" name="online" value="1" @checked(old('online'))> Compra on-line</label>
                        <button class="btn btn-primary btn-full" type="submit">Simular compra</button>
                    </form>
                @endif
            </section>
        @endif

        <section class="panel" aria-labelledby="new-card-title">
            <div class="panel-header"><h2 id="new-card-title">Solicitar novo cartão</h2></div>
            <form class="stack" method="post" action="{{ route('web.cards.store') }}" data-confirm="Confirma a solicitação de um novo cartão físico?">
                @csrf
                <div class="field">
                    <label for="modalidade">Modalidade</label>
                    <select id="modalidade" name="modalidade">
                        <option value="DEBITO" @selected(old('modalidade') === 'DEBITO')>Débito</option>
                        <option value="CREDITO" @selected(old('modalidade') === 'CREDITO')>Crédito</option>
                    </select>
                </div>
                <div class="field">
                    <label for="endereco_entrega">Endereço de entrega <span class="optional">(opcional)</span></label>
                    <textarea id="endereco_entrega" name="endereco_entrega" rows="3" maxlength="190" placeholder="Rua, número e cidade">{{ old('endereco_entrega') }}</textarea>
                </div>
                <button class="btn btn-primary btn-full" type="submit">Emitir cartão</button>
            </form>
        </section>
    </div>
</div>
@endsection
