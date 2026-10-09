# 4.7 — Diagrama Entidade-Relacionamento (DER)

## Objetivo

Este DER descreve o esquema de persistência do Fluxo após a revisão 4.0. A tabela `contas` mantém o saldo derivado dos lançamentos; as novas entidades atendem ao ciclo de abertura, cartões, limites Pix, aviso de viagem, consulta de assinaturas do cartão, auditoria e agendamento.

> **Implementação:** as tabelas novas estão em `Projeto/backend/database/migrations/2026_10_08_*.php`. O diagrama complementar em [`der-02-requisitos-complementares.svg`](diagramas/der-02-requisitos-complementares.svg) destaca somente as relações adicionadas nesta revisão.

## Estratégias de persistência

1. **Status da conta:** `contas.status` é operacional; `contas.status_abertura` é o fluxo KYC. Uma conta só fica `ATIVA` após abertura aprovada.
2. **Cartões:** `cartoes` representa cada instância lógica e mantém `conta_id`. `cartoes_fisicos` e `cartoes_virtuais` são tabelas de forma; o virtual tem `cartao_id` próprio e `cartao_fisico_id` obrigatório.
3. **Flag on-line:** `cartoes_fisicos.permite_compras_online` começa em `false` e é consultada apenas para autorização em canal `ONLINE`.
4. **Assinaturas:** `assinaturas`/`planos` continuam sendo o produto do Fluxo. `assinaturas_cartao` é um read model de recorrências detectadas nas compras do cartão.
5. **Limites Pix:** `limites_pix` guarda a política vigente; `consumos_limites_pix` guarda o valor/quantidade utilizado e reservado por conta, dia e janela.
6. **Aviso de viagem:** `avisos_viagem.cartao_id` é obrigatório, de modo que a exceção de localização não se espalhe para os demais cartões.
7. **Auditoria:** `logs_auditoria` é escrita por triggers PostgreSQL para alterações de estado e por `AuditoriaService` para acessos/consultas. A própria tabela é append-only.
8. **Agendamento:** `agendamentos` contém a máquina de estados e a chave de idempotência do worker.

## Diagrama DER (Mermaid)

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string password
    }

    CLIENTES {
        uuid id PK
        bigint user_id FK,UK
        string tipo_cliente
        string nome
        string razao_social
        string cpf UK
        string cnpj UK
        date data_nascimento
        string representante_legal
        string status
    }

    CONTAS {
        uuid id PK
        uuid cliente_id FK
        string tipo_conta
        string agencia
        string numero UK
        string status
        string status_abertura
        timestamp status_abertura_atualizado_em
        boolean bloqueada
        string cnpj_vinculado
        date aniversario_rendimento
        boolean restricao_saque
    }

    CHAVES_PIX {
        uuid id PK
        uuid conta_id FK
        string tipo_chave
        string valor_chave UK
        string status
        timestamp desativada_em
        timestamp registrado_em
    }

    CARTOES {
        uuid id PK
        uuid conta_id FK
        string modalidade
        string numero_mascarado
        date validade
        string cvv_hash
        string status
        numeric limite_aprovado
        numeric limite_atual
        int dia_fechamento_fatura
    }

    CARTOES_FISICOS {
        uuid id PK
        uuid cartao_id FK,UK
        date data_emissao
        date data_entrega
        string endereco_entrega
        boolean permite_compras_online
    }

    CARTOES_VIRTUAIS {
        uuid id PK
        uuid cartao_id FK,UK
        uuid cartao_fisico_id FK
        timestamp gerado_em
        timestamp expira_em
    }

    TRANSACOES {
        uuid id PK
        string tipo
        string status
        string chave_idempotencia UK
        timestamp criado_em
    }

    LANCAMENTOS {
        uuid id PK
        uuid transacao_id FK
        uuid conta_id FK
        string natureza
        numeric valor
    }

    LIMITES_PIX {
        uuid id PK
        uuid conta_id FK
        numeric limite_por_transacao
        numeric limite_diario
        int quantidade_diaria
        numeric limite_noturno_por_transacao
        numeric limite_noturno_diario
        int quantidade_noturna
        numeric limite_sem_chave_por_transacao
        numeric limite_sem_chave_diario
        time hora_inicio_noturno
        time hora_fim_noturno
        boolean ativo
        timestamp vigente_desde
        timestamp vigente_ate
    }

    CONSUMOS_LIMITES_PIX {
        uuid id PK
        uuid conta_id FK
        uuid limite_pix_id FK
        date data_referencia
        string janela
        numeric valor_utilizado
        int quantidade_utilizada
        numeric valor_reservado
        int quantidade_reservada
    }

    AVISOS_VIAGEM {
        uuid id PK
        uuid conta_id FK
        uuid cartao_id FK
        date data_inicio
        date data_fim
        json localidades
        string status
        timestamp cancelado_em
    }

    PLANOS {
        uuid id PK
        string nome
        numeric mensalidade
        int limite_retiradas
        boolean ativo
    }

    ASSINATURAS {
        uuid id PK
        uuid cliente_id FK
        uuid plano_id FK
        date data_inicio
        date data_fim
        int dia_cobranca
        string status
        numeric valor_atual
    }

    ASSINATURAS_CARTAO {
        uuid id PK
        uuid cartao_id FK
        uuid conta_id FK
        uuid transacao_origem_id FK
        string identificador_estabelecimento
        string nome_estabelecimento
        string periodicidade
        numeric valor_estimado
        date ultima_cobranca
        date proxima_cobranca
        string status
        string origem
        numeric confianca
        json metadados
    }

    ITENS {
        uuid id PK
        string nome
        numeric valor_unitario
    }

    ITENS_ASSINATURA {
        uuid assinatura_id PK,FK
        uuid item_id PK,FK
        date data_exclusao
        string motivo_exclusao
    }

    COBRANCAS {
        uuid id PK
        uuid assinatura_id FK
        string competencia
        numeric valor
        date vencimento
        string status
    }

    AGENDAMENTOS {
        uuid id PK
        uuid conta_id FK
        uuid transacao_id FK
        string tipo_operacao
        numeric valor
        timestamp agendado_para
        json payload_operacao
        string status
        int tentativas
        string chave_idempotencia UK
        boolean recorrente
        timestamp proxima_execucao
    }

    LOGS_AUDITORIA {
        uuid id PK
        string tabela
        string registro_id
        string operacao
        string categoria
        bigint usuario_id FK
        string request_id
        string ip_address
        jsonb antes
        jsonb depois
        jsonb diff
        string hash_anterior
        string hash_registro
        timestamp registrado_em
    }

    CLIENTES ||--|| USERS : "possui credencial"
    CLIENTES ||--o{ CONTAS : "titular"
    CONTAS ||--o{ CHAVES_PIX : "possui"
    CONTAS ||--o{ CARTOES : "emite"
    CONTAS ||--o{ LANCAMENTOS : "recebe"
    CONTAS ||--o{ LIMITES_PIX : "configura"
    CONTAS ||--o{ CONSUMOS_LIMITES_PIX : "consome"
    CONTAS ||--o{ AVISOS_VIAGEM : "escopa"
    CONTAS ||--o{ ASSINATURAS_CARTAO : "consulta"
    CONTAS ||--o{ AGENDAMENTOS : "programa"

    CARTOES ||--|| CARTOES_FISICOS : "possui forma fisica"
    CARTOES ||--o| CARTOES_VIRTUAIS : "possui forma virtual"
    CARTOES_FISICOS ||--o| CARTOES_VIRTUAIS : "origina"
    CARTOES ||--o{ AVISOS_VIAGEM : "recebe aviso"
    CARTOES ||--o{ ASSINATURAS_CARTAO : "origina consulta"

    TRANSACOES ||--|{ LANCAMENTOS : "agrupa"
    TRANSACOES ||--o{ ASSINATURAS_CARTAO : "pode detectar"
    TRANSACOES ||--o{ AGENDAMENTOS : "efetiva"

    LIMITES_PIX ||--o{ CONSUMOS_LIMITES_PIX : "mede"
    CLIENTES ||--o{ ASSINATURAS : "contrata"
    PLANOS ||--o{ ASSINATURAS : "baseia"
    ASSINATURAS ||--o{ ITENS_ASSINATURA : "contem"
    ITENS ||--o{ ITENS_ASSINATURA : "participa"
    ASSINATURAS ||--o{ COBRANCAS : "fatura"
    USERS ||--o{ LOGS_AUDITORIA : "contextualiza"
```

## Dicionário das entidades adicionadas

| Entidade | Regra central |
|---|---|
| `contas.status` / `status_abertura` | Separam ciclo operacional e KYC. |
| `cartoes_virtuais` | `cartao_id` aponta para uma instância com `conta_id`; `cartao_fisico_id` é obrigatório. |
| `cartoes_fisicos.permite_compras_online` | Default `false`; somente canal on-line consulta a flag. |
| `assinaturas_cartao` | Consulta de recorrências de cartão; não substitui `assinaturas`. |
| `limites_pix` / `consumos_limites_pix` | Política e consumo diário/noturno, incluindo ausência de chave ativa. |
| `avisos_viagem` | Um aviso por cartão e período/localidade. |
| `agendamentos` | Fila persistida e idempotente com estados de execução. |
| `logs_auditoria` | OLD/NEW/diff sanitizados, append-only. |

## Rastreabilidade

| Requisito | Entidades / evidências |
|---|---|
| RF18/RN37 | `CONTAS.status`, `CONTAS.status_abertura` |
| RF19/RN38 | `CARTOES`, `CARTOES_FISICOS`, `CARTOES_VIRTUAIS` |
| RF20/RN39 | `CARTOES_FISICOS.permite_compras_online` |
| RF21/RN40 | `ASSINATURAS_CARTAO` separado de `ASSINATURAS` |
| RF22/RN41 | `LOGS_AUDITORIA` e triggers PostgreSQL |
| RF23/RN42–RN43 | `LIMITES_PIX`, `CONSUMOS_LIMITES_PIX`, `CHAVES_PIX.status` |
| RF24/RN44 | `AVISOS_VIAGEM.cartao_id` |
| RF25/RN45 | `AGENDAMENTOS.status`, `chave_idempotencia` |

**Registro de alterações**

| Versão | Data | Alteração |
|---|---|---|
| 3.0 | 10/09/2026 | Especializações de clientes, contas e cartões; auditoria conceitual e agendamento no modelo. |
| 4.0 | 08/10/2026 | Status de abertura, vínculo físico/virtual/conta, flag on-line, read model de assinaturas de cartão, limites Pix por janela/sem chave, aviso por cartão, auditoria implementável e status persistido de agendamento. |
