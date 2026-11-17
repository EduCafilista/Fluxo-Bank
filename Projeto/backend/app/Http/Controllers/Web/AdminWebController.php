<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChamadoSuporte;
use App\Models\Cliente;
use App\Models\Conta;
use App\Models\LogAuditoria;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\ContaService;
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
            'contas' => Conta::count(),
            'pendentes' => Conta::where('status_abertura', '!=', 'APROVADA')->count(),
            'transacoesHoje' => DB::table('transacoes')->whereDate('created_at', now()->toDateString())->count(),
            'chamadosAbertos' => ChamadoSuporte::where('status', 'ABERTO')->count(),
            'registrosAuditoria' => LogAuditoria::count(),
            'recentes' => Conta::with(['cliente', 'user'])->latest('id')->limit(6)->get(),
            'ultimosLogs' => LogAuditoria::latest('id')->limit(6)->get(),
        ]);
    }

    public function contas(Request $request)
    {
        $consulta = Conta::with(['cliente', 'user']);
        if ($request->filled('status')) $consulta->where('status', $request->query('status'));
        if ($request->filled('status_abertura')) $consulta->where('status_abertura', $request->query('status_abertura'));
        if ($request->filled('busca')) {
            $termo = mb_substr((string) $request->query('busca'), 0, 100);
            $consulta->where(function ($q) use ($termo) {
                $q->where('numero', 'ilike', '%'.$termo.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', '%'.$termo.'%')->orWhere('email', 'ilike', '%'.$termo.'%'));
            });
        }
        return view('web.admin.accounts', ['contas' => $consulta->latest('id')->paginate(15)->withQueryString()]);
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
        if ($request->filled('status')) $consulta->where('status', $request->query('status'));
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
            if ($request->filled($campo)) $consulta->where($campo, $request->query($campo));
        }
        if ($request->filled('de')) $consulta->whereDate('registrado_em', '>=', $request->query('de'));
        if ($request->filled('ate')) $consulta->whereDate('registrado_em', '<=', $request->query('ate'));
        return view('web.admin.audit', [
            'registros' => $consulta->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function detalheLog(LogAuditoria $log)
    {
        return view('web.admin.audit-detail', ['log' => $log->load('usuario:id,name,email')]);
    }

    public function perfis(Request $request)
    {
        abort_unless($request->user('web')->perfil === 'ADMIN', 403);
        $consulta = User::query();
        if ($request->filled('busca')) {
            $termo = mb_substr((string) $request->query('busca'), 0, 100);
            $consulta->where(fn ($q) => $q->where('name', 'ilike', '%'.$termo.'%')->orWhere('email', 'ilike', '%'.$termo.'%'));
        }
        return view('web.admin.roles', ['usuarios' => $consulta->orderBy('id')->paginate(20)->withQueryString()]);
    }

    public function atualizarPerfil(Request $request, User $user, AuditoriaService $auditoria): RedirectResponse
    {
        abort_unless($request->user('web')->perfil === 'ADMIN', 403);
        $dados = $request->validate(['perfil' => 'required|in:CLIENTE,ATENDENTE,ADMIN']);
        if ($user->id === $request->user('web')->id) {
            return back()->with('erro', 'Você não pode alterar o próprio perfil.');
        }
        $anterior = $user->perfil;
        $user->update(['perfil' => $dados['perfil']]);
        $auditoria->registrar('MOVIMENTACAO', 'PERFIL_ATUALIZADO', [
            'usuario_alterado' => $user->id, 'anterior' => $anterior, 'novo' => $user->perfil,
        ], 'users', (string) $user->id, usuarioId: $request->user('web')->id);
        return back()->with('sucesso', 'Perfil do usuário atualizado.');
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
