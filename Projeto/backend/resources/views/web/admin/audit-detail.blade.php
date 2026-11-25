@extends('web.layouts.app',['title'=>'Detalhe do evento'])
@section('content')
@include('web.components.heading',['heading'=>'Evento de auditoria #'.$log->id,'subtitle'=>'Consulta segura aos metadados do registro.','eyebrow'=>'BACKOFFICE · AUDITORIA'])
<section class="panel" style="max-width:880px"><div class="panel-header"><h2>Registro</h2><span class="pill blue">{{ $log->categoria }}</span></div><div class="detail-list">
@foreach(['Operação'=>$log->operacao,'Tabela'=>$log->tabela,'ID do registro'=>$log->registro_id,'Usuário'=>$log->usuario?->name ?? 'Sistema','Data e hora'=>$log->registrado_em?->format('d/m/Y H:i:s'),'Método HTTP'=>$log->metodo,'Rota'=>$log->rota,'Status HTTP'=>$log->status_http,'Request ID'=>$log->request_id,'IP'=>$log->ip_address,'Hash do registro'=>$log->hash_registro] as $campo=>$valor)
<div class="detail-item"><span class="key">{{ $campo }}</span><strong class="value">{{ $valor ?: '—' }}</strong></div>
@endforeach</div>
@if($diff)
    <hr class="panel-divider">
    <h2>Campos alterados</h2>
    <div class="table-scroll" role="region" aria-label="Campos alterados" tabindex="0">
        <table class="data-table">
            <thead><tr><th scope="col">Campo</th><th scope="col">Antes</th><th scope="col">Depois</th></tr></thead>
            <tbody>
            @foreach($diff as $campo => $alteracao)
                <tr>
                    <td>{{ $campo }}</td>
                    <td>{{ is_array($alteracao) ? json_encode($alteracao['antes'] ?? null, JSON_UNESCAPED_UNICODE) : $alteracao }}</td>
                    <td>{{ is_array($alteracao) ? json_encode($alteracao['depois'] ?? null, JSON_UNESCAPED_UNICODE) : $alteracao }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
<div class="form-actions"><a class="btn btn-outline" href="{{ route('web.admin.audit') }}">Voltar à trilha</a></div>
</section>
@endsection
