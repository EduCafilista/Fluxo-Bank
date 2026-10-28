<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Cartao;
use App\Models\CartaoFisico;
use App\Models\CartaoVirtual;
use App\Models\Conta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CartoesService
{
    public function emitirFisico(Conta $conta, string $modalidade, ?string $enderecoEntrega = null): Cartao
    {
        $modalidade = mb_strtoupper($modalidade);

        if (! in_array($modalidade, [Cartao::MODALIDADE_DEBITO, Cartao::MODALIDADE_CREDITO], true)) {
            throw new RegraDeNegocioException('Modalidade inválida. Use DEBITO ou CREDITO.');
        }

        if (! $conta->podeDebitar()) {
            throw new RegraDeNegocioException('Conta precisa estar ativa e desbloqueada para emitir cartão.');
        }

        $limite = (int) config('fluxo.cartoes.limite_aprovado_padrao');

        return DB::transaction(function () use ($conta, $modalidade, $enderecoEntrega, $limite) {
            $cartao = Cartao::create([
                'conta_id' => $conta->id,
                'modalidade' => $modalidade,
                'numero_mascarado' => $this->numeroMascarado(),
                'validade' => now()->addYears((int) config('fluxo.cartoes.validade_anos'))->startOfMonth(),
                'cvv_hash' => Hash::make((string) random_int(100, 999)),
                'status' => Cartao::STATUS_ATIVO,
                'limite_aprovado_centavos' => $limite,
                'limite_atual_centavos' => $limite,
                'dia_fechamento_fatura' => 1,
            ]);

            CartaoFisico::create([
                'cartao_id' => $cartao->id,
                'data_emissao' => now()->toDateString(),
                'endereco_entrega' => $enderecoEntrega,
                'permite_compras_online' => false,
            ]);

            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO',
                'CARTAO_FISICO_EMITIDO',
                ['cartao_id' => $cartao->id, 'modalidade' => $modalidade],
                'cartoes',
                (string) $cartao->id,
            );

            return $cartao->load(['fisico']);
        });
    }

    public function gerarVirtual(Cartao $cartaoFisico): Cartao
    {
        $cartaoFisico->loadMissing('fisico', 'conta');

        if (! $cartaoFisico->ativo()) {
            throw new RegraDeNegocioException('O cartão físico precisa estar ativo para gerar o virtual.');
        }

        if (! $cartaoFisico->fisico) {
            throw new RegraDeNegocioException('Este cartão não possui instância física.');
        }

        if ($cartaoFisico->fisico->cartaoVirtual()->exists() || $cartaoFisico->virtual()->exists()) {
            throw new RegraDeNegocioException('Este cartão já possui um cartão virtual.', 409);
        }

        return DB::transaction(function () use ($cartaoFisico) {
            $cartaoVirtual = Cartao::create([
                'conta_id' => $cartaoFisico->conta_id,
                'modalidade' => $cartaoFisico->modalidade,
                'numero_mascarado' => $this->numeroMascarado(),
                'validade' => now()->addDays((int) config('fluxo.cartoes.dias_virtual')),
                'cvv_hash' => Hash::make((string) random_int(100, 999)),
                'status' => Cartao::STATUS_ATIVO,
                'limite_aprovado_centavos' => $cartaoFisico->limite_aprovado_centavos,
                'limite_atual_centavos' => $cartaoFisico->limite_atual_centavos,
                'dia_fechamento_fatura' => $cartaoFisico->dia_fechamento_fatura,
            ]);

            CartaoVirtual::create([
                'cartao_id' => $cartaoVirtual->id,
                'cartao_fisico_id' => $cartaoFisico->fisico->id,
                'gerado_em' => now(),
                'expira_em' => now()->addDays((int) config('fluxo.cartoes.dias_virtual')),
            ]);

            app(AuditoriaService::class)->registrar(
                'MOVIMENTACAO',
                'CARTAO_VIRTUAL_GERADO',
                ['cartao_id' => $cartaoVirtual->id, 'origem' => $cartaoFisico->id],
                'cartoes_virtuais',
                (string) $cartaoVirtual->id,
            );

            return $cartaoVirtual->load('virtual');
        });
    }

    public function bloqueio(Cartao $cartao, bool $bloquear): Cartao
    {
        if ($cartao->status === Cartao::STATUS_CANCELADO) {
            throw new RegraDeNegocioException('Cartão cancelado não pode ser alterado.');
        }

        $cartao->update(['status' => $bloquear ? Cartao::STATUS_BLOQUEADO : Cartao::STATUS_ATIVO]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            $bloquear ? 'CARTAO_BLOQUEADO' : 'CARTAO_DESBLOQUEADO',
            ['cartao_id' => $cartao->id],
            'cartoes',
            (string) $cartao->id,
        );

        return $cartao->refresh();
    }

    public function ajustarLimite(Cartao $cartao, int $novoLimiteCentavos): Cartao
    {
        if ($novoLimiteCentavos < 0) {
            throw new RegraDeNegocioException('O limite não pode ser negativo.');
        }

        if ($novoLimiteCentavos > $cartao->limite_aprovado_centavos) {
            throw new RegraDeNegocioException(
                'Limite acima do teto pré-aprovado (máx. '.number_format($cartao->limite_aprovado_centavos / 100, 2, ',', '.').').',
            );
        }

        $cartao->update(['limite_atual_centavos' => $novoLimiteCentavos]);

        return $cartao->refresh();
    }

    public function definirComprasOnline(Cartao $cartao, bool $permitir): CartaoFisico
    {
        $fisico = $cartao->fisico;

        if (! $fisico) {
            throw new RegraDeNegocioException('Somente o cartão físico possui a flag de compras on-line.');
        }

        $fisico->update(['permite_compras_online' => $permitir]);

        return $fisico->refresh();
    }

    private function numeroMascarado(): string
    {
        $final = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return '**** **** **** '.$final;
    }
}
