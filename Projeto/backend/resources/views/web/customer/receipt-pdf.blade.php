<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante Fluxo {{ $comprovante['transacao_id'] }}</title>
    <style>
        body { color: #15233b; font: 12px DejaVu Sans, sans-serif; margin: 38px; }
        h1 { color: #2159d7; font-size: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { text-align: left; border-bottom: 1px solid #dce5f1; padding: 10px 5px; }
        dt { color: #586983; margin-top: 12px; }
        dd { margin: 2px 0 0; }
        footer { color: #586983; margin-top: 25px; }
    </style>
</head>
<body>
    <h1>Fluxo Bank</h1>
    <h2>Comprovante de operação</h2>
    <dl>
        <dt>Identificador</dt><dd>{{ $comprovante['transacao_id'] }}</dd>
        <dt>Autenticação</dt><dd>{{ $comprovante['autenticacao'] }}</dd>
        <dt>Data e hora</dt><dd>{{ \Illuminate\Support\Carbon::parse($comprovante['data'])->format('d/m/Y H:i:s') }}</dd>
        <dt>Operação e situação</dt><dd>{{ $comprovante['tipo'] }} · {{ $comprovante['status'] }}</dd>
        <dt>Descrição</dt><dd>{{ $comprovante['descricao'] ?? '—' }}</dd>
        <dt>Valor</dt><dd>R$ {{ number_format($comprovante['valor_centavos'] / 100, 2, ',', '.') }}</dd>
    </dl>
    <h3>Lançamentos</h3>
    <table>
        <thead><tr><th>Conta</th><th>Natureza</th><th>Valor</th></tr></thead>
        <tbody>
        @foreach($comprovante['lancamentos'] as $lancamento)
            <tr><td>{{ $lancamento['conta'] }}</td><td>{{ $lancamento['tipo'] === 'credito' ? 'Crédito' : 'Débito' }}</td><td>R$ {{ number_format($lancamento['valor_centavos'] / 100, 2, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <footer>Fluxo Bank · Operação simulada</footer>
</body>
</html>
