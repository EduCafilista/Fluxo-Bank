@extends('web.layouts.app', ['title' => 'Agendamentos'])
@section('content')
@include('web.components.heading', ['heading' => 'Agendamentos', 'subtitle' => 'Programe transferências e pagamentos de boletos.'])
<div class="grid grid-main">
    <div class="stack">
        @if($notificacoes->isNotEmpty())
            <section class="panel">
                <div class="panel-header"><h2>Notificações de execução</h2></div>
                @foreach($notificacoes as $notificacao)
                    <a class="detail-item linked-row" href="{{ route('web.receipt', $notificacao->data['transacao_id']) }}">
                        <div><strong>{{ $notificacao->data['mensagem'] }}</strong><div class="muted sm">{{ $notificacao->created_at->format('d/m/Y H:i') }}</div></div>
                        @include('web.components.icon', ['name' => 'chevron-right'])
                    </a>
                @endforeach
            </section>
        @endif
        <section class="panel">
            <div class="panel-header"><h2>Suas operações programadas</h2></div>
            @forelse($agendamentos as $ag)
                <div class="detail-item schedule-row">
                    <div>
                        <strong>R$ {{ number_format($ag->valor_centavos / 100, 2, ',', '.') }}</strong>
                        <div class="muted sm">{{ $ag->tipo_operacao === 'BOLETO' ? 'Boleto' : 'Transferência' }} · {{ $ag->agendado_para->format('d/m/Y H:i') }}</div>
                        @if($ag->recorrente)<div class="muted tiny">Repete mensalmente</div>@endif
                        @if($ag->erro)<div class="muted sm">{{ $ag->erro }}</div>@endif
                    </div>
                    <div class="table-actions">
                        <span class="pill {{ $ag->status === 'EXECUTADO' ? 'green' : ($ag->status === 'AGENDADO' ? 'blue' : 'orange') }}">{{ ucfirst(strtolower($ag->status)) }}</span>
                        @if($ag->status === 'AGENDADO')
                            <form method="post" action="{{ route('web.schedules.delete', $ag) }}" data-confirm="Cancelar este agendamento?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">Cancelar</button>
                            </form>
                        @elseif($ag->transacao_id)
                            <a class="btn btn-outline btn-sm" href="{{ route('web.receipt.pdf', $ag->transacao_id) }}">Baixar PDF</a>
                        @endif
                    </div>
                </div>
            @empty
                @include('web.components.empty', ['icon' => 'calendar', 'heading' => 'Nenhum agendamento'])
            @endforelse
            @include('web.components.pagination', ['paginator' => $agendamentos])
        </section>
    </div>
    <div class="stack">
        <section class="panel">
            <div class="panel-header"><h2>Agendar transferência</h2></div>
            <form method="post" action="{{ route('web.schedules.store') }}" class="stack" data-confirm="Programar a transferência com os dados informados?">
                @csrf
                <input type="hidden" name="idempotencia" value="{{ \Illuminate\Support\Str::uuid() }}">
                <div class="field"><label for="destino_id">ID da conta de destino</label><input id="destino_id" name="destino_id" type="number" min="1" value="{{ old('destino_id') }}" required></div>
                <div class="form-grid">
                    <div class="field"><label for="valor">Valor (R$)</label><input id="valor" name="valor" inputmode="decimal" placeholder="150,00" value="{{ old('valor') }}" required></div>
                    <div class="field"><label for="agendado_para">Data e hora</label><input id="agendado_para" name="agendado_para" type="datetime-local" value="{{ old('agendado_para') }}" required></div>
                </div>
                <div class="field"><label for="descricao">Descrição <span class="optional">(opcional)</span></label><input id="descricao" name="descricao" maxlength="120" value="{{ old('descricao') }}"></div>
                <label class="check-label"><input name="recorrente" type="checkbox" value="1" @checked(old('recorrente'))> Repetir mensalmente</label>
                <button class="btn btn-primary" type="submit">Agendar transferência</button>
            </form>
        </section>
        <section class="panel">
            <div class="panel-header"><h2>Agendar boleto</h2></div>
            <form method="post" action="{{ route('web.schedules.boleto') }}" class="stack" data-confirm="Programar o pagamento deste boleto?">
                @csrf
                <input type="hidden" name="idempotencia" value="{{ \Illuminate\Support\Str::uuid() }}">
                <div class="field"><label for="linha_digitavel">Linha digitável ou código de barras</label><input id="linha_digitavel" name="linha_digitavel" inputmode="numeric" maxlength="70" value="{{ old('linha_digitavel') }}" required></div>
                <div class="form-grid">
                    <div class="field"><label for="valor_boleto">Valor (R$)</label><input id="valor_boleto" name="valor_boleto" inputmode="decimal" placeholder="150,00" value="{{ old('valor_boleto') }}" required></div>
                    <div class="field"><label for="data_boleto">Data e hora</label><input id="data_boleto" name="data_boleto" type="datetime-local" value="{{ old('data_boleto') }}" required></div>
                </div>
                <button class="btn btn-outline" type="submit">Agendar boleto</button>
            </form>
        </section>
    </div>
</div>
@endsection
