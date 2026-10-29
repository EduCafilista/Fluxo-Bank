<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\CodigoVerificacao;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class Servico2FA
{
    /**
     * @return array{codigo:string, canal:string, expira_em:string}
     */
    public function gerar(User $user, string $finalidade): array
    {
        $canal = config('fluxo.dois_fatores.canal');
        $validade = (int) config('fluxo.dois_fatores.validade_minutos');

        CodigoVerificacao::where('user_id', $user->id)
            ->where('finalidade', $finalidade)
            ->whereNull('utilizado_em')
            ->update(['utilizado_em' => now()]);

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $registro = CodigoVerificacao::create([
            'user_id' => $user->id,
            'canal' => $canal,
            'finalidade' => $finalidade,
            'codigo_hash' => hash('sha256', $codigo),
            'expira_em' => now()->addMinutes($validade),
            'max_tentativas' => (int) config('fluxo.dois_fatores.max_tentativas'),
        ]);

        Log::info('Codigo 2FA gerado', [
            'user_id' => $user->id,
            'canal' => $canal,
            'finalidade' => $finalidade,
            'expira_em' => $registro->expira_em->toIso8601String(),
        ]);

        return [
            'codigo' => $codigo,
            'canal' => $canal,
            'expira_em' => $registro->expira_em->toIso8601String(),
        ];
    }

    public function validar(User $user, string $codigo, string $finalidade): CodigoVerificacao
    {
        $registro = CodigoVerificacao::where('user_id', $user->id)
            ->where('finalidade', $finalidade)
            ->whereNull('utilizado_em')
            ->latest('id')
            ->first();

        if (! $registro) {
            throw new RegraDeNegocioException('Nenhum código pendente. Solicite um novo.', 401);
        }

        if ($registro->expira_em->isPast()) {
            throw new RegraDeNegocioException('Código expirado. Solicite um novo.', 401);
        }

        if ($registro->tentativas >= $registro->max_tentativas) {
            throw new RegraDeNegocioException('Número de tentativas excedido. Solicite um novo código.', 429);
        }

        if (! hash_equals($registro->codigo_hash, hash('sha256', $codigo))) {
            $registro->increment('tentativas');

            throw new RegraDeNegocioException(
                'Código inválido. Tentativas restantes: '.max(0, $registro->max_tentativas - $registro->tentativas).'.',
                401,
            );
        }

        $registro->update(['utilizado_em' => now()]);

        return $registro;
    }
}
