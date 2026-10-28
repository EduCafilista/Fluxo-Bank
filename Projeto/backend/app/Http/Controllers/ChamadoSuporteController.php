<?php

namespace App\Http\Controllers;

use App\Models\ChamadoSuporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChamadoSuporteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'chamados' => $request->user()->chamados()->latest('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'assunto' => 'required|string|max:120',
            'mensagem' => 'required|string|max:2000',
        ]);

        $chamado = ChamadoSuporte::create([
            'user_id' => $request->user()->id,
            'assunto' => $dados['assunto'],
            'mensagem' => $dados['mensagem'],
            'status' => ChamadoSuporte::STATUS_ABERTO,
        ]);

        return response()->json(['mensagem' => 'Chamado aberto.', 'chamado' => $chamado], 201);
    }

    public function show(Request $request, ChamadoSuporte $chamado): JsonResponse
    {
        abort_unless($chamado->user_id === $request->user()->id, 404, 'Chamado não encontrado.');

        return response()->json($chamado);
    }
}
