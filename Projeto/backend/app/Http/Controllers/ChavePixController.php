<?php

namespace App\Http\Controllers;

use App\Models\ChavePix;
use App\Services\ChavePixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** CRUD de chaves Pix (RF04). */
class ChavePixController extends Controller
{
    public function __construct(private ChavePixService $chaves) {}

    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        return response()->json([
            'chaves' => $conta->chavesPix()->latest('id')->get()->map(fn (ChavePix $c) => [
                'id' => $c->id,
                'tipo' => $c->tipo_chave,
                'valor' => $c->valor_chave,
                'status' => $c->status,
                'registrada_em' => $c->registrado_em?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo_chave' => 'required|string|in:CPF,CNPJ,EMAIL,TELEFONE,ALEATORIA',
            'valor_chave' => 'required_unless:tipo_chave,ALEATORIA|nullable|string|max:120',
        ], [
            'tipo_chave.in' => 'Tipo de chave inválido.',
            'valor_chave.required_unless' => 'Informe o valor da chave.',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $chave = $this->chaves->cadastrar($conta, $dados['tipo_chave'], $dados['valor_chave'] ?? '');

        return response()->json([
            'mensagem' => 'Chave Pix registrada.',
            'chave' => [
                'id' => $chave->id,
                'tipo' => $chave->tipo_chave,
                'valor' => $chave->valor_chave,
                'status' => $chave->status,
            ],
        ], 201);
    }

    public function destroy(Request $request, ChavePix $chave): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        abort_unless($chave->conta_id === $conta->id, 404, 'Chave não encontrada.');

        $this->chaves->revogar($chave);

        return response()->json(['mensagem' => 'Chave Pix revogada.']);
    }
}
