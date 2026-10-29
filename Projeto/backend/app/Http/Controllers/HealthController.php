<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => 'indisponivel'], 503);
        }
    }
}
