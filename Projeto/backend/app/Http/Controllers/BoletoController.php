<?php

namespace App\Http\Controllers;

use App\Exceptions\RegraDeNegocioException;
use App\Services\PagamentosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoletoController extends Controller
{
    public function __construct(private PagamentosService $pagamentos) {}

    public function pagar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'linha_digitavel' => 'required|string',
            'valor_centavos' => 'required|integer|min:1',
        ], [
            'linha_digitavel.required' => 'Informe a linha digitável do boleto.',
            'valor_centavos.required' => 'Informe o valor do boleto.',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $transacao = $this->pagamentos->pagarBoleto(
            $conta,
            $dados['linha_digitavel'],
            (int) $dados['valor_centavos'],
            $request->header('Idempotency-Key'),
        );

        return response()->json([
            'mensagem' => 'Boleto pago.',
            'transacao_id' => $transacao->id,
            'valor_centavos' => (int) $dados['valor_centavos'],
            'saldo_centavos' => $conta->refresh()->saldo(),
        ], 201);
    }

    public function validar(Request $request): JsonResponse
    {
        $dados = $request->validate(['linha_digitavel' => 'required|string']);

        try {
            $digitos = $this->pagamentos->validarCodigoBoleto($dados['linha_digitavel']);
        } catch (RegraDeNegocioException $e) {
            return response()->json([
                'valido' => false,
                'digitos' => strlen(preg_replace('/\D/', '', $dados['linha_digitavel'])),
                'mensagem' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'valido' => true, 'digitos' => strlen($digitos), 'mensagem' => 'Código válido.',
        ]);
    }
}
