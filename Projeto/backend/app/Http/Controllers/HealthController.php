<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health check que testa o BANCO de verdade (e não só o processo PHP).
 * GET /up        → estado simples (Laravel)
 * GET /api/v1/health → JSON com driver, versão do PostgreSQL, migrations e fila
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $inicio = microtime(true);

        try {
            DB::select('select 1');

            $versao = DB::selectOne('select version() as versao')->versao;
            $migrationsPendentes = $this->migrationsPendentes();

            return response()->json([
                'status' => $migrationsPendentes > 0 ? 'degradado' : 'ok',
                'app' => config('app.name'),
                'ambiente' => app()->environment(),
                'api' => config('fluxo.api.versao'),
                'banco' => [
                    'conexao' => config('database.default'),
                    'database' => DB::getDatabaseName(),
                    'versao' => strtok($versao, ','),
                    'migrations_pendentes' => $migrationsPendentes,
                ],
                'latencia_ms' => round((microtime(true) - $inicio) * 1000, 2),
                'horario' => now()->toIso8601String(),
            ], $migrationsPendentes > 0 ? 503 : 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'erro',
                'banco' => [
                    'conexao' => config('database.default'),
                    'erro' => $e->getMessage(),
                ],
            ], 503);
        }
    }

    private function migrationsPendentes(): int
    {
        $rodadas = DB::table('migrations')->pluck('migration')->all();
        $arquivos = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($arquivo) => basename($arquivo, '.php'))
            ->all();

        return count(array_diff($arquivos, $rodadas));
    }
}
