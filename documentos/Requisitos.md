# Levantamento de Requisitos Funcionais (Inicial)

**Projeto:** Fluxo — Sistema Bancário Digital
**Disciplina:** Projetos de Software II — UNIUBE · Prof. Marcus Colantoni
**Grupo:** 1 · **Checkpoint 1** — apresentação em 03/09/2026
**Versão:** 1.0 — levantamento inicial (consolidação final no Checkpoint 2)

---

## Sumário

- [Convenções](#convenções)
- [Visão geral](#visão-geral)
- [Módulo 1 — Acesso e Gestão de Conta](#módulo-1--acesso-e-gestão-de-conta)
- [Módulo 2 — Movimentações Financeiras](#módulo-2--movimentações-financeiras)
- [Módulo 3 — Cartões](#módulo-3--cartões)
- [Módulo 4 — Pagamentos e Cobranças](#módulo-4--pagamentos-e-cobranças)
- [Módulo 5 — Administração, Segurança e Suporte](#módulo-5--administração-segurança-e-suporte)
- [Rastreabilidade](#rastreabilidade-requisito-×-caso-de-uso)
- [Técnicas de levantamento](#técnicas-de-levantamento-utilizadas)

---

## Convenções

**Identificação:** `RF` = Requisito Funcional · `RNF` = Requisito Não Funcional · `RN` = Regra de Negócio · `UC` = Caso de Uso

**Prioridade (método MoSCoW):**

| Prioridade | Significado |
|:---:|---|
| **Must** | Essencial — o sistema não é viável sem este requisito. |
| **Should** | Importante — agrega valor significativo, mas há alternativa temporária. |
| **Could** | Desejável — implementado se houver tempo hábil. |
| **Won't** | Fora do escopo desta versão. |

**Origem dos requisitos:** entrevista simulada com o usuário-tipo, análise de concorrentes (Nubank, Banco Inter, C6 Bank), requisitos da disciplina e brainstorming da equipe.

---

## Visão geral

| Métrica | Valor |
|---|---|
| Total de requisitos funcionais | **52** |
| Prioridade `Must` | 28 (53,8%) |
| Prioridade `Should` | 20 (38,5%) |
| Prioridade `Could` | 4 (7,7%) |
| Módulos funcionais | 5 |
| Casos de uso cobertos | 38 (UC01 – UC38) |

> Todos os 38 casos de uso modelados nos diagramas do item 4.3 possuem ao menos um requisito funcional associado — não há caso de uso órfão nem requisito sem rastreabilidade.

**Distribuição por módulo:**

| Módulo | Requisitos | Faixa | Casos de uso |
|---|:---:|---|---|
| 1 — Acesso e Gestão de Conta | 12 | RF01 – RF12 | UC01 – UC08 |
| 2 — Movimentações Financeiras | 14 | RF13 – RF26 | UC09 – UC16 |
| 3 — Cartões | 9 | RF27 – RF35 | UC17 – UC24 |
| 4 — Pagamentos e Cobranças | 7 | RF36 – RF42 | UC25 – UC30 |
| 5 — Administração, Segurança e Suporte | 10 | RF43 – RF52 | UC31 – UC38 |

---

## Módulo 1 — Acesso e Gestão de Conta

Cobre o ciclo de vida da conta: desde o cadastro do visitante, passando pela verificação de identidade e aprovação pelo backoffice, até a manutenção dos dados e o encerramento.

| ID | Requisito | Prioridade | Casos de uso |
|:---:|---|:---:|:---:|
| **RF01** | O sistema deve permitir que um visitante se cadastre informando nome completo, CPF, data de nascimento, e-mail, telefone e senha. | `Must` | UC01 |
| **RF02** | O sistema deve validar o CPF pelo algoritmo dos dígitos verificadores e recusar CPFs já cadastrados. | `Must` | UC01 |
| **RF03** | O sistema deve submeter o cadastro a uma verificação de identidade (KYC) simulada antes de liberar a conta. | `Must` | UC02 |
| **RF04** | O sistema deve permitir que o analista de backoffice aprove ou reprove a abertura de conta, registrando o motivo. | `Must` | UC06 |
| **RF05** | O sistema deve criar automaticamente uma conta corrente (agência e número únicos) quando o cadastro for aprovado. | `Must` | UC06 |
| **RF06** | O sistema deve autenticar o usuário por e-mail e senha, com sessão na web e token na API mobile. | `Must` | UC03 |
| **RF07** | O sistema deve permitir ativar a autenticação em dois fatores (2FA) por código enviado ao e-mail. | `Should` | UC04 |
| **RF08** | O sistema deve bloquear o acesso após 5 tentativas de login incorretas em 15 minutos. | `Must` | UC03 |
| **RF09** | O sistema deve permitir recuperar a senha por link com token de uso único e validade de 30 minutos. | `Must` | UC05 |
| **RF10** | O sistema deve permitir ao cliente visualizar e alterar seus dados cadastrais (telefone, e-mail e endereço). | `Must` | UC07 |
| **RF11** | O sistema deve exigir confirmação por senha para alterações em dados sensíveis. | `Should` | UC07 |
| **RF12** | O sistema deve permitir o encerramento da conta apenas se o saldo for zero e não houver pendências. | `Should` | UC08 |

---

## Módulo 2 — Movimentações Financeiras

Núcleo transacional do sistema. Todas as operações deste módulo geram lançamentos no razão contábil por partidas dobradas, garantindo que o saldo seja sempre a soma dos lançamentos da conta.

| ID | Requisito | Prioridade | Casos de uso |
|:---:|---|:---:|:---:|
| **RF13** | O sistema deve exibir o saldo atual da conta, calculado a partir dos lançamentos. | `Must` | UC09 |
| **RF14** | O sistema deve exibir o extrato com data, descrição, contraparte, tipo e valor, ordenado do mais recente para o mais antigo. | `Must` | UC09 |
| **RF15** | O sistema deve permitir filtrar o extrato por período, tipo de operação e faixa de valor. | `Should` | UC09 |
| **RF16** | O sistema deve permitir ao cliente registrar chaves Pix nos tipos CPF, e-mail, telefone e aleatória. | `Must` | UC10 |
| **RF17** | O sistema deve impedir o registro de uma chave Pix já vinculada a outra conta. | `Must` | UC10 |
| **RF18** | O sistema deve permitir realizar transferência Pix informando a chave do favorecido e o valor. | `Must` | UC11 |
| **RF19** | O sistema deve exibir os dados do favorecido para confirmação antes de efetivar a transferência. | `Must` | UC11 |
| **RF20** | O sistema deve submeter toda transferência à análise de risco antes da efetivação. | `Must` | UC12 |
| **RF21** | O sistema deve permitir transferência interna entre contas Fluxo com liquidação imediata. | `Must` | UC13 |
| **RF22** | O sistema deve permitir agendar transferências para data futura, com execução automática. | `Should` | UC14 |
| **RF23** | O sistema deve permitir cancelar um agendamento até o dia anterior à execução. | `Should` | UC14 |
| **RF24** | O sistema deve permitir depósito simulado via Pix ou boleto, creditando o valor na conta. | `Must` | UC15 |
| **RF25** | O sistema deve gerar comprovante em PDF de qualquer transação efetivada, com identificador único. | `Should` | UC16 |
| **RF26** | O sistema deve permitir salvar favorecidos frequentes para reuso. | `Could` | UC13 |

---

## Módulo 3 — Cartões

Emissão e gestão de cartões virtuais e físicos, controle de limites, faturas e o fluxo de contestação de lançamentos.

| ID | Requisito | Prioridade | Casos de uso |
|:---:|---|:---:|:---:|
| **RF27** | O sistema deve permitir a emissão imediata de cartão virtual, com número, validade e CVV gerados. | `Must` | UC17 |
| **RF28** | O sistema deve exibir o número do cartão mascarado, revelando-o apenas mediante reautenticação. | `Must` | UC17 |
| **RF29** | O sistema deve permitir solicitar cartão físico e acompanhar o status (solicitado, produção, enviado, entregue). | `Should` | UC18 |
| **RF30** | O sistema deve permitir bloquear e desbloquear cartões, de forma temporária ou definitiva. | `Must` | UC19 |
| **RF31** | O sistema deve permitir ajustar o limite do cartão respeitando o teto aprovado para o cliente. | `Should` | UC20 |
| **RF32** | O sistema deve processar autorizações de compra simuladas, verificando limite e status do cartão. | `Should` | UC21 |
| **RF33** | O sistema deve manter a fatura do cartão com lançamentos, data de fechamento e vencimento. | `Should` | UC22 |
| **RF34** | O sistema deve permitir contestar um lançamento da fatura informando o motivo. | `Could` | UC23 |
| **RF35** | O sistema deve permitir ao backoffice analisar a contestação, deferindo ou indeferindo com justificativa. | `Could` | UC24 |

---

## Módulo 4 — Pagamentos e Cobranças

Pagamento de boletos e QR Codes Pix, emissão de cobranças pelo próprio cliente e notificação dos eventos financeiros.

| ID | Requisito | Prioridade | Casos de uso |
|:---:|---|:---:|:---:|
| **RF36** | O sistema deve permitir o pagamento de boleto por linha digitável ou código de barras. | `Must` | UC25 |
| **RF37** | O sistema deve validar o dígito verificador do boleto e exibir beneficiário, vencimento e valor antes da confirmação. | `Must` | UC25 |
| **RF38** | O sistema deve permitir o pagamento de cobrança Pix por leitura ou colagem de QR Code (payload EMV). | `Should` | UC26 |
| **RF39** | O sistema deve permitir ao cliente emitir cobrança Pix com valor, descrição e prazo de validade. | `Should` | UC27 |
| **RF40** | O sistema deve permitir agendar pagamentos recorrentes com periodicidade definida. | `Could` | UC28 |
| **RF41** | O sistema deve creditar automaticamente o emissor quando uma cobrança for liquidada. | `Should` | UC29 |
| **RF42** | O sistema deve notificar o cliente por e-mail e push a cada evento financeiro relevante. | `Should` | UC30 |

---

## Módulo 5 — Administração, Segurança e Suporte

Área interna do banco: gestão de usuários e perfis, trilha de auditoria imutável, parametrizações operacionais, relatórios gerenciais e central de chamados.

| ID | Requisito | Prioridade | Casos de uso |
|:---:|---|:---:|:---:|
| **RF43** | O sistema deve permitir ao administrador criar, editar e desativar usuários internos. | `Must` | UC31 |
| **RF44** | O sistema deve implementar controle de acesso por perfil (cliente, suporte, backoffice, administrador, auditor). | `Must` | UC31 |
| **RF45** | O sistema deve registrar em trilha de auditoria toda operação sensível, com autor, data/hora, IP e dados alterados. | `Must` | UC32 |
| **RF46** | O sistema deve impedir a alteração ou exclusão de registros da trilha de auditoria. | `Must` | UC32 |
| **RF47** | O sistema deve permitir ao administrador bloquear contas suspeitas, impedindo novas movimentações. | `Must` | UC33 |
| **RF48** | O sistema deve permitir ao auditor consultar a trilha com filtros por autor, período e tipo de operação. | `Should` | UC34 |
| **RF49** | O sistema deve permitir parametrizar tarifas, limites por operação e limites diários. | `Should` | UC35 |
| **RF50** | O sistema deve gerar relatórios gerenciais de volume transacionado, contas ativas e chamados. | `Should` | UC36 |
| **RF51** | O sistema deve permitir ao cliente abrir chamado de suporte com assunto, descrição e anexo. | `Should` | UC38 |
| **RF52** | O sistema deve permitir ao analista responder o chamado e alterar seu status. | `Should` | UC37 |

---

## Rastreabilidade Requisito × Caso de Uso

Cada diagrama de casos de uso do item 4.3 corresponde a um módulo funcional:

| Diagrama | Módulo | Casos de uso | Requisitos atendidos |
|:---:|---|:---:|:---:|
| [1](diagramas/ucd-01-acesso-e-conta.svg) | Acesso e Gestão de Conta | UC01 – UC08 | RF01 – RF12 |
| [3](diagramas/ucd-03-movimentacoes.svg) | Movimentações Financeiras | UC09 – UC16 | RF13 – RF26 |
| [4](diagramas/ucd-04-cartoes.svg) | Cartões | UC17 – UC24 | RF27 – RF35 |
| [5](diagramas/ucd-05-pagamentos.svg) | Pagamentos e Cobranças | UC25 – UC30 | RF36 – RF42 |
| [7](diagramas/ucd-07-administracao.svg) | Administração, Segurança e Suporte | UC31 – UC38 | RF43 – RF52 |

---

## Técnicas de levantamento utilizadas

1. **Análise de concorrentes** — estudo dos fluxos de Nubank, Banco Inter e C6 Bank para identificar as funcionalidades esperadas de um banco digital.
2. **Entrevista simulada** — roteiro aplicado ao usuário-tipo (correntista jovem, 18–35 anos, mobile-first).
3. **Análise documental** — requisitos da disciplina (mínimo de 8 entidades, duas interfaces, implantação em nuvem) e material das aulas 01 e 02.
4. **Brainstorming da equipe** — sessão de levantamento livre, seguida de priorização MoSCoW.
5. **Prototipação em papel** — esboço das telas principais para validar a completude dos requisitos.

---

## Documentos relacionados

| Documento | Conteúdo |
|---|---|
| [`01-escopo-do-projeto.md`](Escopo.md) | 4.1 — Escopo, objetivos, arquitetura e riscos |
| [`02-requisitos.md`](RequisitosCompleto.md) | 4.2 — Documento completo: RF + RNF + regras de negócio |
| [`quadro-de-gestao.md`](QuadroDeGestao.md) | 4.4 — Quadro de gestão e cronograma |
| [`05-stakeholders.md`](Stakeholders.md) | Análise de stakeholders |
| [`diagramas/`](diagramas/) | 4.3 — Diagramas de casos de uso |

> **Nota sobre versionamento:** este é o levantamento **inicial**, entregue no Checkpoint 1. Os requisitos não funcionais (39) e as regras de negócio (17) estão no documento completo `02-requisitos.md`. A versão consolidada, com requisitos finais e documentos detalhados de casos de uso, será entregue no **Checkpoint 2 (17/09/2026)**.
