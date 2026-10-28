<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContaResource;
use App\Http\Resources\LancamentoResource;
use App\Models\Conta;
use App\Services\ContaService;
use App\Services\LimitePixService;
use App\Services\RelatorioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContaController extends Controller
{
    public function __construct(
        private ContaService $contas,
        private RelatorioService $relatorios,
        private LimitePixService $limites,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $conta = $this->contaDoUsuario($request);

        return response()->json([
            'conta' => new ContaResource($conta),
            'saldo_centavos' => $this->contas->saldo($conta),
            'resumo_do_dia' => $this->contas->resumoDoDia($conta),
            'limites_pix' => $this->limites->resumo($conta),
        ]);
    }

    public function extrato(Request $request): JsonResponse
    {
        $conta = $this->contaDoUsuario($request);

        $porPagina = (int) $request->query('per_page', 50);
        $paginado = $this->contas->extrato($conta, $porPagina);

        return response()->json([
            'saldo_centavos' => $this->contas->saldo($conta),
            'pagina' => $paginado->currentPage(),
            'por_pagina' => $paginado->perPage(),
            'total' => $paginado->total(),
            'extrato' => LancamentoResource::collection($paginado->items()),
        ]);
    }

    public function extratoCategorizado(Request $request): JsonResponse
    {
        $conta = $this->contaDoUsuario($request);

        return response()->json(
            $this->relatorios->extratoCategorizado($conta),
        );
    }

    public function bloquear(Request $request): JsonResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);

        $conta = $this->contas->bloquear($this->contaDoUsuario($request), $dados['motivo']);

        return response()->json([
            'mensagem' => 'Conta bloqueada preventivamente.',
            'conta' => new ContaResource($conta),
        ]);
    }

    public function desbloquear(Request $request): JsonResponse
    {
        $conta = $this->contas->desbloquear($this->contaDoUsuario($request));

        return response()->json([
            'mensagem' => 'Conta desbloqueada.',
            'conta' => new ContaResource($conta),
        ]);
    }

    public function encerrar(Request $request): JsonResponse
    {
        $conta = $this->contas->encerrar($this->contaDoUsuario($request));

        return response()->json([
            'mensagem' => 'Conta encerrada.',
            'conta' => new ContaResource($conta),
        ]);
    }

    private function contaDoUsuario(Request $request): Conta
    {
        return $request->user()->conta()->firstOrFail();
    }
}
