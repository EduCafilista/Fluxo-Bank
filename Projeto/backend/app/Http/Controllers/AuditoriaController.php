<?php

namespace App\Http\Controllers;

use App\Models\LogAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $consulta = LogAuditoria::query()->with('usuario:id,name,email');

        foreach (['categoria', 'tabela', 'operacao', 'metodo'] as $filtro) {
            if ($request->filled($filtro)) {
                $consulta->where($filtro, $request->query($filtro));
            }
        }

        if ($request->filled('usuario_id')) {
            $consulta->where('usuario_id', (int) $request->query('usuario_id'));
        }

        if ($request->filled('de')) {
            $consulta->where('registrado_em', '>=', $request->date('de')->startOfDay());
        }

        if ($request->filled('ate')) {
            $consulta->where('registrado_em', '<=', $request->date('ate')->endOfDay());
        }

        $logs = $consulta->latest('id')->paginate(min((int) $request->query('per_page', 50), 50));

        return response()->json([
            'pagina' => $logs->currentPage(),
            'total' => $logs->total(),
            'append_only' => true,
            'registros' => $logs->items(),
        ]);
    }

    public function show(LogAuditoria $log): JsonResponse
    {
        return response()->json($log->load('usuario:id,name,email'));
    }
}
