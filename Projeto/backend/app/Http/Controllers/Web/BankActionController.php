<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Assinatura;
use App\Models\AssinaturaCartao;
use App\Models\AvisoViagem;
use App\Models\Cartao;
use App\Models\ChamadoSuporte;
use App\Models\ChavePix;
use App\Models\CodigoVerificacao;
use App\Models\Conta;
use App\Models\Item;
use App\Models\Plano;
use App\Models\Transacao;
use App\Services\AgendamentoService;
use App\Services\AssinaturaService;
use App\Services\AuthService;
use App\Services\AvisoViagemService;
use App\Services\CartoesService;
use App\Services\ConfiguracaoLimitePixService;
use App\Services\ChavePixService;
use App\Services\CompraDebitoSimuladaService;
use App\Services\ContaService;
use App\Services\PagamentosService;
use App\Services\TransferenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Adaptador web: reaproveita os mesmos Services que a API REST, nunca replica operações financeiras. */
class BankActionController extends Controller
{
    private function conta(Request $request): Conta
    {
        return $request->user('web')->conta()->firstOrFail();
    }

    private function centavos(string $valor): int
    {
        $valor = trim($valor);
        if (! preg_match('/^\d{1,10}(?:[,.]\d{1,2})?$/', $valor)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['valor' => 'Informe um valor válido, por exemplo 250,00.']);
        }
        $partes = preg_split('/[,.]/', $valor, 2);
        return (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '0', 2, '0');
    }

    private function cartaoDoCliente(Request $request, Cartao $cartao): Cartao
    {
        abort_unless($cartao->conta_id === $this->conta($request)->id, 404);
        return $cartao;
    }

    public function ajustarLimitesPix(Request $request, ConfiguracaoLimitePixService $servico): RedirectResponse
    {
        $dados = $request->validate([
            'por_transacao' => 'required|string', 'diario' => 'required|string', 'noturno' => 'required|string',
        ]);
        $servico->ajustar($this->conta($request), $this->centavos($dados['por_transacao']),
            $this->centavos($dados['diario']), $this->centavos($dados['noturno']));
        return back()->with('sucesso', 'Limites Pix atualizados respeitando os tetos da instituição.');
    }

    public function revisarTransferencia(Request $request, ChavePixService $chaves): RedirectResponse
    {
        $dados = $request->validate([
            'tipo_destino' => 'required|in:PIX,CONTA',
            'destino' => 'required|string|max:120',
            'valor' => 'required|string|max:24',
            'descricao' => 'nullable|string|max:120',
        ]);
        $centavos = $this->centavos($dados['valor']);
        abort_if($centavos <= 0, 422, 'Informe um valor maior que zero.');

        $origem = $this->conta($request);
        if ($dados['tipo_destino'] === 'PIX') {
            $chave = $chaves->resolver($dados['destino']);
            $destino = $chave->conta()->with('cliente')->firstOrFail();
        } else {
            // Conta identificada pelo ID interno, que deve ser informado explicitamente.
            if (! ctype_digit($dados['destino'])) {
                return back()->withInput()->with('erro', 'O ID da conta de destino deve conter apenas números.');
            }
            $destino = Conta::with('cliente')->findOrFail($dados['destino']);
        }
        if ($origem->id === $destino->id) {
            return back()->withInput()->with('erro', 'Não é possível transferir para sua própria conta.');
        }
        $request->session()->put('fluxo_transferencia', [
            'tipo' => $dados['tipo_destino'],
            'destino' => $dados['destino'],
            'destinatario' => $destino->cliente?->nomeExibicao() ?? 'Titular não identificado',
            'conta_numero' => $destino->numero,
            'valor_centavos' => $centavos,
            'descricao' => $dados['descricao'] ?? null,
            'idempotencia' => (string) Str::uuid(),
        ]);
        return redirect()->route('web.transfer.confirm');
    }

    public function confirmarTransferencia(Request $request, TransferenciaService $transferencias): RedirectResponse
    {
        $pendente = $request->session()->get('fluxo_transferencia');
        if (! is_array($pendente) || ! isset($pendente['idempotencia'])) {
            return redirect()->route('web.transferencia')->with('erro', 'Revise os dados antes de confirmar.');
        }
        $origem = $this->conta($request);
        if ($pendente['tipo'] === 'PIX') {
            $transacao = $transferencias->transferirPorChavePix(
                $origem, $pendente['destino'], $pendente['valor_centavos'],
                $pendente['descricao'], $pendente['idempotencia'],
            );
        } else {
            $transacao = $transferencias->transferir(
                $origem, Conta::findOrFail($pendente['destino']), $pendente['valor_centavos'],
                $pendente['descricao'], Transacao::TIPO_TED_INTERNA, $pendente['idempotencia'],
            );
        }
        $request->session()->forget('fluxo_transferencia');
        return redirect()->route('web.receipt', $transacao)->with('sucesso', 'Transferência realizada.');
    }

    public function gerarCobrancaPix(Request $request, PagamentosService $pagamentos): RedirectResponse
    {
        $dados = $request->validate(['valor' => 'required|string', 'descricao' => 'nullable|string|max:120']);
        $cobranca = $pagamentos->gerarCobrancaPix($this->conta($request), $this->centavos($dados['valor']), $dados['descricao'] ?? null);
        $request->session()->put('fluxo_cobranca', $cobranca);
        return redirect()->route('web.pix.receive')->with('sucesso', 'Cobrança Pix gerada.');
    }

    public function criarChave(Request $request, ChavePixService $servico): RedirectResponse
    {
        $dados = $request->validate(['tipo_chave' => 'required|in:CPF,CNPJ,EMAIL,TELEFONE,ALEATORIA', 'valor_chave' => 'required_unless:tipo_chave,ALEATORIA|nullable|string|max:120']);
        $servico->cadastrar($this->conta($request), $dados['tipo_chave'], $dados['valor_chave'] ?? '');
        return back()->with('sucesso', 'Chave Pix cadastrada.');
    }

    public function excluirChave(Request $request, ChavePix $chave, ChavePixService $servico): RedirectResponse
    {
        abort_unless($chave->conta_id === $this->conta($request)->id, 404);
        $servico->revogar($chave);
        return back()->with('sucesso', 'Chave Pix revogada.');
    }

    public function emitirCartao(Request $request, CartoesService $servico): RedirectResponse
    {
        $dados = $request->validate(['modalidade' => 'required|in:DEBITO,CREDITO', 'endereco_entrega' => 'nullable|string|max:190']);
        $servico->emitirFisico($this->conta($request), $dados['modalidade'], $dados['endereco_entrega'] ?? null);
        return back()->with('sucesso', 'Cartão físico emitido.');
    }

    public function gerarVirtual(Request $request, Cartao $cartao, CartoesService $servico): RedirectResponse
    {
        $servico->gerarVirtual($this->cartaoDoCliente($request, $cartao));
        return back()->with('sucesso', 'Cartão virtual gerado.');
    }

    public function bloquearCartao(Request $request, Cartao $cartao, CartoesService $servico): RedirectResponse
    {
        $dados = $request->validate(['bloquear' => 'required|boolean']);
        $servico->bloqueio($this->cartaoDoCliente($request, $cartao), (bool) $dados['bloquear']);
        return back()->with('sucesso', 'Status do cartão atualizado.');
    }

    public function comprasOnline(Request $request, Cartao $cartao, CartoesService $servico): RedirectResponse
    {
        $dados = $request->validate(['permitir' => 'required|boolean']);
        $servico->definirComprasOnline($this->cartaoDoCliente($request, $cartao), (bool) $dados['permitir']);
        return back()->with('sucesso', 'Permissão de compras on-line atualizada.');
    }

    public function limiteCartao(Request $request, Cartao $cartao, CartoesService $servico): RedirectResponse
    {
        $dados = $request->validate(['valor' => 'required|string']);
        $servico->ajustarLimite($this->cartaoDoCliente($request, $cartao), $this->centavos($dados['valor']));
        return back()->with('sucesso', 'Limite atualizado.');
    }

    public function compraDebito(Request $request, Cartao $cartao, CompraDebitoSimuladaService $servico): RedirectResponse
    {
        abort_unless(config('services.fluxo.simulacoes_financeiras', false), 403, 'Simulações financeiras desativadas.');
        $dados = $request->validate(['valor' => 'required|string', 'estabelecimento' => 'required|string|min:2|max:120', 'online' => 'nullable|boolean']);
        $resultado = $servico->comprar(
            $this->cartaoDoCliente($request, $cartao), $this->centavos($dados['valor']),
            $dados['estabelecimento'], (string) Str::uuid(), (bool) ($dados['online'] ?? false),
        );
        return redirect()->route('web.receipt', $resultado['transacao'])->with('sucesso', 'Compra simulada registrada.');
    }

    public function agendar(Request $request, AgendamentoService $servico): RedirectResponse
    {
        $dados = $request->validate(['destino_id' => 'required|integer|exists:contas,id', 'valor' => 'required|string',
            'agendado_para' => 'required|date|after:now', 'descricao' => 'nullable|string|max:120']);
        $servico->agendar(
            $this->conta($request), Conta::findOrFail($dados['destino_id']), $this->centavos($dados['valor']),
            $dados['agendado_para'], $dados['descricao'] ?? null,
            Agendamento::TIPO_TED_INTERNA, (string) Str::uuid(),
        );
        return back()->with('sucesso', 'Transferência agendada.');
    }

    public function cancelarAgendamento(Request $request, Agendamento $agendamento, AgendamentoService $servico): RedirectResponse
    {
        $servico->cancelar($agendamento, $this->conta($request));
        return back()->with('sucesso', 'Agendamento cancelado.');
    }

    public function revisarBoleto(Request $request): RedirectResponse
    {
        $dados = $request->validate(['linha_digitavel' => 'required|string', 'valor' => 'required|string']);
        $digitos = preg_replace('/\D/', '', $dados['linha_digitavel']);
        if (! in_array(strlen($digitos), [47, 48], true)) {
            return back()->withInput()->with('erro', 'Linha digitável inválida: informe 47 ou 48 dígitos.');
        }
        $request->session()->put('fluxo_boleto', [
            'linha' => $digitos, 'valor_centavos' => $this->centavos($dados['valor']),
            'idempotencia' => (string) Str::uuid(),
        ]);
        return redirect()->route('web.payments.confirm');
    }

    public function pagarBoleto(Request $request, PagamentosService $servico): RedirectResponse
    {
        $boleto = $request->session()->get('fluxo_boleto');
        if (! is_array($boleto)) return redirect()->route('web.payments');
        $transacao = $servico->pagarBoleto($this->conta($request), $boleto['linha'], $boleto['valor_centavos'], $boleto['idempotencia']);
        $request->session()->forget('fluxo_boleto');
        return redirect()->route('web.receipt', $transacao)->with('sucesso', 'Boleto pago.');
    }

    public function contratarPlano(Request $request, AssinaturaService $servico): RedirectResponse
    {
        $dados = $request->validate(['plano_id' => 'required|integer|exists:planos,id', 'dia_cobranca' => 'nullable|integer|between:1,28']);
        $servico->contratar($request->user('web')->cliente()->firstOrFail(), Plano::findOrFail($dados['plano_id']), $dados['dia_cobranca'] ?? null);
        return back()->with('sucesso', 'Plano contratado.');
    }

    private function assinaturaDoCliente(Request $request, Assinatura $assinatura): Assinatura
    {
        abort_unless($assinatura->cliente_id === $request->user('web')->cliente?->id, 404);
        return $assinatura;
    }

    public function cancelarPlano(Request $request, Assinatura $assinatura, AssinaturaService $servico): RedirectResponse
    {
        $servico->cancelar($this->assinaturaDoCliente($request, $assinatura));
        return back()->with('sucesso', 'Assinatura cancelada.');
    }

    public function adicionarItem(Request $request, Assinatura $assinatura, AssinaturaService $servico): RedirectResponse
    {
        $dados = $request->validate(['item_id' => 'required|integer|exists:itens,id']);
        $servico->adicionarItem($this->assinaturaDoCliente($request, $assinatura), Item::findOrFail($dados['item_id']));
        return back()->with('sucesso', 'Item adicionado à assinatura.');
    }

    public function removerItem(Request $request, Assinatura $assinatura, Item $item, AssinaturaService $servico): RedirectResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);
        $servico->removerItem($this->assinaturaDoCliente($request, $assinatura), $item, $dados['motivo']);
        return back()->with('sucesso', 'Item removido.');
    }

    public function cancelarRecorrencia(Request $request, AssinaturaCartao $recorrencia): RedirectResponse
    {
        abort_unless($recorrencia->conta_id === $this->conta($request)->id, 404);
        $recorrencia->update(['status' => 'CANCELADA']);
        return back()->with('sucesso', 'Recorrência cancelada.');
    }

    public function criarAviso(Request $request, AvisoViagemService $servico): RedirectResponse
    {
        $dados = $request->validate(['cartao_id' => 'required|integer|exists:cartoes,id',
            'localidades' => 'required|string|max:240', 'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio']);
        $cartao = Cartao::findOrFail($dados['cartao_id']);
        $this->cartaoDoCliente($request, $cartao);
        $localidades = array_filter(array_map('trim', explode(',', $dados['localidades'])));
        if (! $localidades || count($localidades) > 10) {
            return back()->withInput()->with('erro', 'Informe até 10 localidades separadas por vírgula.');
        }
        $servico->criar($this->conta($request), $cartao, $localidades, $dados['data_inicio'], $dados['data_fim']);
        return back()->with('sucesso', 'Aviso de viagem registrado.');
    }

    public function cancelarAviso(Request $request, AvisoViagem $aviso, AvisoViagemService $servico): RedirectResponse
    {
        abort_unless($aviso->conta_id === $this->conta($request)->id, 404);
        $servico->cancelar($aviso);
        return back()->with('sucesso', 'Aviso de viagem cancelado.');
    }

    public function criarChamado(Request $request): RedirectResponse
    {
        $dados = $request->validate(['assunto' => 'required|string|max:120', 'mensagem' => 'required|string|max:2000']);
        $request->user('web')->chamados()->create($dados + ['status' => ChamadoSuporte::STATUS_ABERTO]);
        return back()->with('sucesso', 'Chamado aberto. Nossa equipe acompanhará sua solicitação.');
    }

    public function bloquearConta(Request $request, ContaService $servico): RedirectResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);
        $servico->bloquear($this->conta($request), $dados['motivo']);
        return back()->with('sucesso', 'Sua conta foi bloqueada preventivamente.');
    }

    public function desbloquearConta(Request $request, ContaService $servico): RedirectResponse
    {
        $servico->desbloquear($this->conta($request));
        return back()->with('sucesso', 'Conta desbloqueada.');
    }

    public function encerrarConta(Request $request, ContaService $servico): RedirectResponse
    {
        $dados = $request->validate(['confirmacao' => 'required|in:ENCERRAR']);
        $servico->encerrar($this->conta($request));
        return back()->with('sucesso', 'Conta encerrada.');
    }

    public function configurarDoisFatores(Request $request, AuthService $servico): RedirectResponse
    {
        $dados = $request->validate(['ativar' => 'required|boolean']);
        $user = $request->user('web');
        if ($dados['ativar'] && ! config('fluxo.dois_fatores.entrega_email_ativa', false)) {
            return back()->with('erro', 'A ativação do 2FA exige serviço de e-mail transacional configurado.');
        }
        if ($dados['ativar']) $servico->ativarDoisFatores($user);
        else $servico->desativarDoisFatores($user);
        return back()->with('sucesso', 'Configuração de verificação em dois fatores atualizada.');
    }
}
