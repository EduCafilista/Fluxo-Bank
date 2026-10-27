<?php

namespace App\Http\Controllers;

use App\Models\AvisoViagem;
use App\Models\Cartao;
use App\Services\AvisoViagemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Aviso de viagem sempre por cartão (RF24 / RN44). */
class AvisoViagemController extends Controller
{
    public function __construct(private AvisoViagemService $avisos) {}

    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        return response()->json([
            'avisos' => $conta->avisosViagem()->latest('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'cartao_id' => 'required|integer|exists:cartoes,id',
            'localidades' => 'required|array|min:1',
            'localidades.*' => 'string|max:60',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $aviso = $this->avisos->criar(
            $conta,
            Cartao::findOrFail($dados['cartao_id']),
            $dados['localidades'],
            $dados['data_inicio'],
            $dados['data_fim'],
        );

        return response()->json(['mensagem' => 'Aviso de viagem registrado.', 'aviso' => $aviso], 201);
    }

    public function cancelar(Request $request, AvisoViagem $aviso): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        abort_unless($aviso->conta_id === $conta->id, 404, 'Aviso não encontrado.');

        return response()->json([
            'mensagem' => 'Aviso de viagem cancelado.',
            'aviso' => $this->avisos->cancelar($aviso),
        ]);
    }
}
