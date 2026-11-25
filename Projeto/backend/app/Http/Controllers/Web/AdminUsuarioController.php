<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroWebRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Services\GestaoUsuariosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => 'nullable|string|max:100',
            'perfil' => 'nullable|in:CLIENTE,ATENDENTE,ADMIN',
        ]);
        $consulta = User::with(['cliente', 'conta']);

        if ($termo = $filtros['busca'] ?? null) {
            $consulta->where(fn ($q) => $q->where('name', 'ilike', '%'.$termo.'%')
                ->orWhere('email', 'ilike', '%'.$termo.'%')
                ->orWhereHas('contas', fn ($contas) => $contas->where('numero', 'ilike', '%'.$termo.'%')));
        }

        if ($perfil = $filtros['perfil'] ?? null) {
            $consulta->where('perfil', $perfil);
        }

        return view('web.admin.users.index', ['usuarios' => $consulta->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('web.admin.users.create');
    }

    public function store(CadastroWebRequest $request, GestaoUsuariosService $servico): RedirectResponse
    {
        $usuario = $servico->criar($request->validated(), $request->user('web'));

        return redirect()->route('web.admin.users.edit', $usuario)->with('sucesso', 'Usuário criado.');
    }

    public function edit(User $user): View
    {
        return view('web.admin.users.edit', ['usuario' => $user->load(['cliente', 'contas'])]);
    }

    public function update(Request $request, User $user, GestaoUsuariosService $servico): RedirectResponse
    {
        $pj = $user->cliente?->tipo_cliente === Cliente::TIPO_PJ;
        $rules = [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => 'nullable|string|min:8|max:72|confirmed',
            'perfil' => 'sometimes|required|in:CLIENTE,ATENDENTE,ADMIN',
        ];
        $rules += $pj
            ? ['razao_social' => 'required|string|max:150', 'representante_legal' => 'required|string|max:150']
            : ['name' => 'required|string|max:120'];

        if ($user->cliente && ! $pj) {
            $rules['data_nascimento'] = 'required|date|before:today';
        }

        $dados = $request->validate($rules);
        $servico->editar($user, array_filter($dados, fn ($valor) => $valor !== null), $request->user('web'));

        return back()->with('sucesso', 'Cadastro atualizado.');
    }

    public function updateRole(Request $request, User $user, GestaoUsuariosService $servico): RedirectResponse
    {
        $dados = $request->validate(['perfil' => 'required|in:CLIENTE,ATENDENTE,ADMIN']);
        $servico->editar($user, $dados, $request->user('web'));

        return redirect()->route('web.admin.users.index')->with('sucesso', 'Perfil atualizado.');
    }

    public function destroy(Request $request, User $user, GestaoUsuariosService $servico): RedirectResponse
    {
        $request->validate(['confirmacao' => 'accepted']);
        $servico->excluir($user, $request->user('web'));

        return redirect()->route('web.admin.users.index')->with('sucesso', 'Usuário excluído.');
    }
}
