<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GestaoUsuariosService
{
    public function __construct(
        private CadastroService $cadastro,
        private ContaService $contas,
        private AuditoriaService $auditoria,
    ) {}

    public function criar(array $dados, User $autor): User
    {
        return DB::transaction(function () use ($dados, $autor) {
            $this->validarAdministrador($autor);

            $usuario = $dados['perfil'] === User::PERFIL_CLIENTE
                ? $this->cadastro->cadastrarCliente($dados)->user
                : User::create([
                    'name' => $dados['nome'],
                    'email' => $dados['email'],
                    'password' => $dados['password'],
                    'perfil' => $dados['perfil'],
                    'dois_fatores_ativo' => config('fluxo.dois_fatores.ativo_por_padrao'),
                ]);

            $this->registrar('USUARIO_CRIADO', $usuario, $autor, ['perfil' => $usuario->perfil]);

            return $usuario;
        });
    }

    public function editar(User $usuario, array $dados, User $autor): void
    {
        DB::transaction(function () use ($usuario, $dados, $autor) {
            if (! $usuario->is($autor)) {
                $this->validarAdministrador($autor);
            }

            $usuario = User::query()->lockForUpdate()->findOrFail($usuario->id);
            if ($usuario->contas()->where('tipo_conta', 'INSTITUCIONAL')->exists()) {
                throw new RegraDeNegocioException('O usuário da conta institucional é protegido.');
            }

            $cliente = $usuario->cliente;
            $perfil = $dados['perfil'] ?? $usuario->perfil;

            if ($perfil !== $usuario->perfil) {
                if ($usuario->is($autor)) {
                    throw new RegraDeNegocioException('Você não pode alterar o próprio perfil.');
                }

                if ($perfil === User::PERFIL_CLIENTE && ! $cliente) {
                    throw new RegraDeNegocioException('Para criar um cliente, use o cadastro com abertura de conta.');
                }
            }

            $nome = $dados['name'] ?? $usuario->name;
            if ($cliente?->tipo_cliente === Cliente::TIPO_PJ) {
                $nome = $dados['razao_social'] ?? $cliente->nomeExibicao();
            }

            $usuario->fill(Arr::only($dados, ['email', 'password']) + ['name' => $nome, 'perfil' => $perfil]);

            if ($usuario->isDirty('email')) {
                $usuario->email_verified_at = null;
            }

            if ($usuario->isDirty(['email', 'password', 'perfil'])) {
                $usuario->remember_token = null;
                $this->revogarAcessos($usuario);
            }

            if ($usuario->isDirty('password')) {
                $usuario->forceFill(['bloqueado_ate' => null, 'tentativas_login' => 0]);
            }

            $campos = array_keys($usuario->getDirty());
            $usuario->save();

            if ($cliente) {
                $cliente->fill(Arr::only($dados, ['razao_social', 'representante_legal', 'data_nascimento']) + ['nome' => $nome]);
                $campos = array_unique(array_merge($campos, array_keys($cliente->getDirty())));
                $cliente->save();
            }

            if ($campos) {
                $this->registrar('USUARIO_ATUALIZADO', $usuario, $autor, ['campos' => array_values($campos)]);
            }
        });
    }

    public function excluir(User $usuario, User $autor): void
    {
        if ($usuario->is($autor)) {
            throw new RegraDeNegocioException('Você não pode excluir o próprio usuário.');
        }

        DB::transaction(function () use ($usuario, $autor) {
            $this->validarAdministrador($autor);

            $usuario = User::query()->lockForUpdate()->findOrFail($usuario->id);

            foreach ($usuario->contas()->orderBy('id')->get() as $conta) {
                if ($conta->tipo_conta === 'INSTITUCIONAL') {
                    throw new RegraDeNegocioException('O usuário da conta institucional é protegido.');
                }

                $this->contas->encerrar($conta);
                $conta->chavesPix()->select('id')->chunkById(100, function ($chaves) {
                    foreach ($chaves as $chave) {
                        $chave->update(['valor_chave' => 'revogada-'.Str::uuid()]);
                    }
                });
            }

            $this->revogarAcessos($usuario);
            $usuario->cliente?->assinaturas()->where('status', 'ATIVA')->update([
                'status' => 'CANCELADA', 'data_fim' => now()->toDateString(),
            ]);
            $usuario->cliente?->update([
                'nome' => 'Cliente excluído',
                'cpf' => null,
                'cnpj' => null,
                'data_nascimento' => null,
                'razao_social' => null,
                'representante_legal' => null,
                'status' => Cliente::STATUS_ENCERRADO,
            ]);
            $usuario->forceFill([
                'name' => 'Usuário excluído',
                'email' => 'excluido-'.Str::uuid().'@invalid.example',
                'email_verified_at' => null,
                'password' => Str::random(48),
                'remember_token' => null,
                'dois_fatores_ativo' => false,
                'tentativas_login' => 0,
                'bloqueado_ate' => null,
            ])->save();
            $usuario->delete();
            $this->registrar('USUARIO_EXCLUIDO', $usuario, $autor);
        });
    }

    private function validarAdministrador(User $autor): void
    {
        DB::select("select pg_advisory_xact_lock(hashtext('fluxo:gestao-usuarios'))");

        if (User::whereKey($autor->id)->value('perfil') !== User::PERFIL_ADMIN) {
            throw new RegraDeNegocioException('Esta operação exige acesso administrativo.', 403);
        }
    }

    private function revogarAcessos(User $usuario): void
    {
        $usuario->tokens()->delete();
        $usuario->codigosVerificacao()->delete();
        DB::table('sessions')->where('user_id', $usuario->id)->delete();
        DB::table('password_reset_tokens')->where('email', $usuario->getOriginal('email'))->delete();
    }

    private function registrar(string $evento, User $usuario, User $autor, array $detalhes = []): void
    {
        $this->auditoria->registrar(
            'MOVIMENTACAO',
            $evento,
            $detalhes,
            'users',
            (string) $usuario->id,
            usuarioId: $autor->id,
        );
    }
}
