<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\AssinaturaCartao;
use App\Models\Cartao;
use App\Models\Conta;
use App\Models\Item;
use App\Models\Plano;
use App\Models\Transacao;
use App\Services\ContaService;
use App\Services\LimitePixService;
use App\Services\RelatorioService;
use Illuminate\Http\Request;

class BankWebController extends Controller
{
    private function conta(Request $request): Conta
    {
        return $request->user('web')->conta()->with('cliente')->firstOrFail();
    }

    public function dashboard(Request $request, ContaService $servico)
    {
        $conta = $this->conta($request);
        $lancamentos = $conta->lancamentos()->with('transacao')->latest('id')->limit(7)->get();
        return view('web.customer.dashboard', [
            'conta' => $conta, 'saldo' => $servico->saldo($conta),
            'resumo' => $servico->resumoDoDia($conta), 'lancamentos' => $lancamentos,
            'cartoes' => $conta->cartoes()->count(), 'chaves' => $conta->chavesPix()->where('status', 'ATIVA')->count(),
        ]);
    }

    public function extrato(Request $request, ContaService $servico)
    {
        $conta = $this->conta($request);
        $extrato = $servico->extrato($conta, 20)->withQueryString();
        return view('web.customer.extrato', compact('conta', 'extrato'));
    }

    public function pix(Request $request, LimitePixService $limites)
    {
        $conta = $this->conta($request);
        $recentes = $conta->lancamentos()->with('transacao')->whereHas('transacao', fn ($q) => $q->where('tipo', 'PIX'))->latest('id')->limit(5)->get();
        return view('web.customer.pix', [
            'conta' => $conta, 'recentes' => $recentes, 'limites' => $limites->resumo($conta),
            'totalChaves' => $conta->chavesPix()->where('status', 'ATIVA')->count(),
        ]);
    }

    public function limitesPix(Request $request, LimitePixService $limites)
    {
        return view('web.customer.limits', [
            'conta' => $this->conta($request),
            'politica' => $limites->politica($this->conta($request)),
            'resumo' => $limites->resumo($this->conta($request)),
            'tetos' => config('fluxo.limites_pix'),
        ]);
    }

    public function transferencia(Request $request)
    {
        return view('web.customer.transfer', ['conta' => $this->conta($request)]);
    }

    public function confirmarTransferencia(Request $request)
    {
        $pendente = $request->session()->get('fluxo_transferencia');
        if (! is_array($pendente)) return redirect()->route('web.transferencia');
        return view('web.customer.transfer-confirm', ['pendente' => $pendente]);
    }

    public function receberPix(Request $request)
    {
        return view('web.customer.receive', [
            'conta' => $this->conta($request), 'cobranca' => $request->session()->get('fluxo_cobranca'),
        ]);
    }

    public function chaves(Request $request)
    {
        $conta = $this->conta($request);
        return view('web.customer.keys', [
            'conta' => $conta,
            'chaves' => $conta->chavesPix()->latest('id')->get(),
        ]);
    }

    public function comprovante(Request $request, Transacao $transacao, RelatorioService $relatorios)
    {
        $conta = $this->conta($request);
        abort_unless($transacao->lancamentos()->where('conta_id', $conta->id)->exists(), 404);
        return view('web.customer.receipt', ['comprovante' => $relatorios->comprovante($transacao)]);
    }

    public function cartoes(Request $request)
    {
        $conta = $this->conta($request);
        return view('web.customer.cards', [
            'conta' => $conta,
            'cartoes' => $conta->cartoes()->with(['fisico', 'virtual'])->latest('id')->get(),
        ]);
    }

    public function agendamentos(Request $request)
    {
        $conta = $this->conta($request);
        return view('web.customer.schedules', [
            'conta' => $conta,
            'agendamentos' => $conta->agendamentos()->latest('id')->get(),
        ]);
    }

    public function pagamentos(Request $request)
    {
        return view('web.customer.payments', ['conta' => $this->conta($request)]);
    }

    public function confirmarBoleto(Request $request)
    {
        $pendente = $request->session()->get('fluxo_boleto');
        if (! is_array($pendente)) return redirect()->route('web.payments');
        return view('web.customer.payments-confirm', compact('pendente'));
    }

    public function assinaturas(Request $request)
    {
        $cliente = $request->user('web')->cliente;
        return view('web.customer.subscriptions', [
            'assinaturas' => $cliente ? $cliente->assinaturas()->with(['plano', 'itens.item', 'cobrancas'])->get() : collect(),
            'planos' => Plano::where('ativo', true)->get(),
            'itensCatalogo' => Item::all(),
            'recorrencias' => AssinaturaCartao::where('conta_id', $this->conta($request)->id)->latest('id')->get(),
        ]);
    }

    public function viagens(Request $request)
    {
        $conta = $this->conta($request);
        return view('web.customer.travel', [
            'avisos' => $conta->avisosViagem()->with('cartao')->latest('id')->get(),
            'cartoes' => $conta->cartoes()->with('fisico')->get(),
        ]);
    }

    public function suporte(Request $request)
    {
        return view('web.customer.support', [
            'chamados' => $request->user('web')->chamados()->latest('id')->get(),
        ]);
    }

    public function perfil(Request $request)
    {
        return view('web.customer.profile', ['conta' => $this->conta($request)]);
    }
}
