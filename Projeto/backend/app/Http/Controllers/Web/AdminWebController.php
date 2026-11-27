<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChamadoSuporte;
use App\Models\Cliente;
use App\Models\Conta;
use App\Models\LogAuditoria;
use App\Services\ConfiguracaoLimitePixService;
use App\Services\ContaService;
use App\Services\LimitePixService;
use App\Services\RecuperacaoSenhaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWebController extends Controller
{
    public function dashboard()
    {
        return view('web.admin.dashboard', [
            'clientes' => Cliente::count(),
            'contas' => Conta::where('tipo_conta', '!=', 'INSTITUCIONAL')->count(),
            'pendentes' => Conta::whereIn('status_abertura', [Conta::ABERTURA_PENDENTE, Conta::ABERTURA_EM_ANALISE])->where('status', '!=', Conta::STATUS_ENCERRADA)->count(),
            'transacoesHoje' => DB::table('transacoes')->whereDate('created_at', now()->toDateString())->count(),
            'chamadosAbertos' => ChamadoSuporte::where('status', 'ABERTO')->count(),
            'registrosAuditoria' => LogAuditoria::count(),
            'recentes' => Conta::with(['cliente', 'user'])->where('tipo_conta', '!=', 'INSTITUCIONAL')->latest('id')->limit(6)->get(),
            'ultimosLogs' => LogAuditoria::latest('id')->limit(6)->get(),
        ]);
    }

    public function solicitacoes()
    {
        $pendentes = Conta::with(['cliente', 'user'])
            ->where('tipo_conta', '!=', 'INSTITUCIONAL')
            ->whereIn('status_abertura', [Conta::ABERTURA_PENDENTE, Conta::ABERTURA_EM_ANALISE])
            ->where('status', '!=', Conta::STATUS_ENCERRADA)
            ->oldest('id')->paginate(15);
        return view('web.admin.requests', ['solicitacoes' => $pendentes]);
    }

    public function contas(Request $request)
    {
        $filtros = $request->validate([
            'busca' => 'nullable|string|max:100',
            'status' => 'nullable|in:PENDENTE,ATIVA,BLOQUEADA,ENCERRADA',
            'status_abertura' => 'nullable|in:PENDENTE,EM_ANALISE,APROVADA,RECUSADA',
        ]);
        $consulta = Conta::with(['cliente', 'user'])->where('tipo_conta', '!=', 'INSTITUCIONAL');

        foreach (['status', 'status_abertura'] as $campo) {
            if ($valor = $filtros[$campo] ?? null) {
                $consulta->where($campo, $valor);
            }
        }

        if ($termo = $filtros['busca'] ?? null) {
            $consulta->where(fn ($q) => $q->where('numero', 'ilike', '%'.$termo.'%')
                ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', '%'.$termo.'%')->orWhere('email', 'ilike', '%'.$termo.'%')));
        }

        return view('web.admin.accounts', ['contas' => $consulta->latest('id')->paginate(15)->withQueryString()]);
    }

    public function conta(Conta $conta, LimitePixService $limites)
    {
        $conta->load(['cliente', 'user']);

        return view('web.admin.account', [
            'conta' => $conta,
            'politica' => $limites->politica($conta),
        ]);
    }

    public function desbloquear(Conta $conta, ContaService $servico): RedirectResponse
    {
        $servico->desbloquear($conta, porAdministrador: true);

        return back()->with('sucesso', 'Conta desbloqueada.');
    }

    public function encerrar(Conta $conta, ContaService $servico): RedirectResponse
    {
        $servico->encerrar($conta);

        return back()->with('sucesso', 'Conta encerrada.');
    }

    public function ajustarLimites(Request $request, Conta $conta, ConfiguracaoLimitePixService $servico): RedirectResponse
    {
        $dados = $request->validate([
            'por_transacao' => 'required|integer|min:1',
            'diario' => 'required|integer|min:1',
            'noturno' => 'required|integer|min:1',
        ]);
        $servico->ajustar($conta, (int) $dados['por_transacao'], (int) $dados['diario'], (int) $dados['noturno']);

        return back()->with('sucesso', 'Limites Pix atualizados.');
    }

    public function aprovar(Conta $conta, ContaService $servico): RedirectResponse
    {
        $servico->aprovarAbertura($conta);

        return back()->with('sucesso', 'KYC aprovado para a conta #'.$conta->id.'.');
    }

    public function recusar(Request $request, Conta $conta, ContaService $servico): RedirectResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);
        $servico->recusarAbertura($conta, $dados['motivo']);

        return back()->with('sucesso', 'Solicitação de abertura recusada.');
    }

    public function bloquear(Request $request, Conta $conta, ContaService $servico): RedirectResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);
        $servico->bloquearPorFraude($conta, $dados['motivo']);

        return back()->with('sucesso', 'Conta bloqueada por suspeita de fraude.');
    }

    public function chamados(Request $request)
    {
        $consulta = ChamadoSuporte::with('user:id,name,email');
        if ($request->filled('status')) {
            $consulta->where('status', $request->query('status'));
        }

        return view('web.admin.tickets', ['chamados' => $consulta->latest('id')->paginate(15)->withQueryString()]);
    }

    public function responder(Request $request, ChamadoSuporte $chamado): RedirectResponse
    {
        $dados = $request->validate(['resposta' => 'required|string|max:4000', 'status' => 'required|in:EM_ATENDIMENTO,RESOLVIDO']);
        $chamado->update(['resposta' => $dados['resposta'], 'status' => $dados['status'],
            'atendente_id' => $request->user('web')->id, 'respondido_em' => now()]);

        return back()->with('sucesso', 'Chamado atualizado.');
    }

    public function auditoria(Request $request)
    {
        $consulta = LogAuditoria::query()->with('usuario:id,name,email');
        foreach (['categoria', 'tabela', 'operacao', 'metodo'] as $campo) {
            if ($request->filled($campo)) {
                $consulta->where($campo, $request->query($campo));
            }
        }
        if ($request->filled('de')) {
            $consulta->whereDate('registrado_em', '>=', $request->query('de'));
        }
        if ($request->filled('ate')) {
            $consulta->whereDate('registrado_em', '<=', $request->query('ate'));
        }

        return view('web.admin.audit', [
            'registros' => $consulta->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function detalheLog(LogAuditoria $log)
    {
        $log->load('usuario:id,name,email');
        $diff = $log->diff
            ? DB::selectOne('select public.fn_auditoria_sanitizar(?::jsonb) as diff', [json_encode($log->diff)])->diff
            : null;

        return view('web.admin.audit-detail', ['log' => $log, 'diff' => $diff ? json_decode($diff, true) : []]);
    }

    public function codigoRecuperacao(Request $request, RecuperacaoSenhaService $recuperacao): RedirectResponse
    {
        abort_unless($request->user('web')->perfil === 'ADMIN', 403);
        $dados = $request->validate(['conta_id' => 'required|integer|exists:contas,id']);
        $conta = Conta::findOrFail($dados['conta_id']);
        $codigo = $recuperacao->gerarCodigoParaConta($conta, $request->user('web'));

        // Segredo exibido apenas ao ADMIN solicitante, uma única vez, nunca gravado no flash em logs.
        return back()->with('codigo_recuperacao', $codigo['codigo'] ?? null)
            ->with('sucesso', 'Código gerado para entrega manual ao titular; não compartilhe em canais públicos.');
    }
}
