<?php

namespace App\Console\Commands;

use App\Models\Conta;
use App\Services\RecuperacaoSenhaService;
use Illuminate\Console\Command;

class GerarCodigoRecuperacaoSenha extends Command
{
    protected $signature = 'senha:gerar-codigo {identificador : CPF, e-mail, telefone ou ID interno da conta}';

    protected $description = 'Gera manualmente um código temporário de 6 dígitos para recuperação de senha vinculado internamente à conta';

    public function handle(RecuperacaoSenhaService $recuperacao): int
    {
        $entrada = trim((string) $this->argument('identificador'));

        $conta = $recuperacao->localizarContaPorIdentificador($entrada);

        if (! $conta && ctype_digit($entrada)) {
            $conta = Conta::with('user')->find((int) $entrada);
        }

        if (! $conta || ! $conta->user) {
            $this->error("Conta não encontrada para '{$entrada}'.");

            return self::FAILURE;
        }

        $resultado = $recuperacao->gerarCodigoParaConta($conta);

        $this->info("Código de recuperação emitido para a conta #{$resultado['conta_id']} ({$resultado['numero_conta']}):");
        $this->line("Código (6 dígitos): {$resultado['codigo']}");
        $this->line("Expira em: {$resultado['expira_em']} ({$resultado['validade_minutos']} min)");
        $this->line("Máximo de tentativas: {$resultado['max_tentativas']}");

        return self::SUCCESS;
    }
}
