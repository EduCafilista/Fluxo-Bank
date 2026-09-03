<<<<<<< HEAD
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
=======
## 1. Requisitos Funcionais (RF)

### Acesso e Gestão de Conta
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF01** | Permitir cadastro informando nome completo, CPF, data de nascimento, e-mail, telefone e senha.[cite: 2] | Must[cite: 2] | UC01[cite: 2] |
| **RF02** | Validar o CPF por dígitos verificadores e recusar duplicidade na base.[cite: 2] | Must[cite: 2] | UC01[cite: 2] |
| **RF03** | Submeter o cadastro a uma verificação de identidade (KYC) simulada e automatizada.[cite: 2] | Must[cite: 2] | UC02[cite: 2] |
| **RF04** | Aprovar ou reprovar a abertura de conta automaticamente, registrando o motivo em caso de recusa.[cite: 2] | Must[cite: 2] | UC06[cite: 2] |
| **RF05** | Criar automaticamente conta corrente com agência e número únicos após aprovação.[cite: 2] | Must[cite: 2] | UC06[cite: 2] |
| **RF06** | Autenticar por e-mail e senha (sessão web / token na API mobile).[cite: 2] | Must[cite: 2] | UC03[cite: 2] |
| **RF07** | Permitir ativar autenticação em dois fatores (2FA) via e-mail.[cite: 2] | Should[cite: 2] | UC04[cite: 2] |
| **RF08** | Bloquear acesso após 5 tentativas incorretas em 15 minutos, exigindo desbloqueio via suporte.[cite: 2] | Must[cite: 2] | UC03[cite: 2] |
| **RF09** | Recuperação de senha por link com token de uso único (validade de 30 minutos).[cite: 2] | Must[cite: 2] | UC05[cite: 2] |
| **RF10** | Permitir ao cliente visualizar e alterar dados cadastrais (telefone, e-mail e endereço).[cite: 2] | Must[cite: 2] | UC07[cite: 2] |
| **RF11** | Exigir confirmação por senha para alteração de dados sensíveis.[cite: 2] | Should[cite: 2] | UC07[cite: 2] |
| **RF12** | Permitir encerramento de conta apenas com saldo zero e sem pendências.[cite: 2] | Should[cite: 2] | UC08[cite: 2] |

### Movimentações Financeiras
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF13** | Exibir saldo atual calculado a partir dos lançamentos.[cite: 2] | Must[cite: 2] | UC09[cite: 2] |
| **RF14** | Exibir extrato ordenado do mais recente para o mais antigo.[cite: 2] | Must[cite: 2] | UC09[cite: 2] |
| **RF15** | Permitir filtrar extrato por período, tipo de operação e faixa de valor.[cite: 2] | Should[cite: 2] | UC09[cite: 2] |
| **RF16** | Permitir registrar chaves Pix (CPF, e-mail, telefone, aleatória).[cite: 2] | Must[cite: 2] | UC10[cite: 2] |
| **RF17** | Impedir rigorosamente o registro de chave Pix já vinculada a outra conta.[cite: 2] | Must[cite: 2] | UC10[cite: 2] |
| **RF18** | Realizar transferência Pix informando chave e valor.[cite: 2] | Must[cite: 2] | UC11[cite: 2] |
| **RF19** | Exibir dados do favorecido para confirmação prévia.[cite: 2] | Must[cite: 2] | UC11[cite: 2] |
| **RF20** | Submeter transferências à análise de risco antes da efetivação.[cite: 2] | Must[cite: 2] | UC12[cite: 2] |
| **RF21** | Permitir transferência interna com liquidação imediata.[cite: 2] | Must[cite: 2] | UC13[cite: 2] |
| **RF22** | Agendar transferências para data futura, validando o saldo no dia da execução e disparando e-mail de falha se houver insuficiência.[cite: 2] | Should[cite: 2] | UC14[cite: 2] |
| **RF23** | Cancelar agendamentos até o dia anterior à execução.[cite: 2] | Should[cite: 2] | UC14[cite: 2] |
| **RF24** | Permitir depósito simulado via Pix ou boleto.[cite: 2] | Must[cite: 2] | UC15[cite: 2] |
| **RF25** | Generar comprovante em PDF com identificador único.[cite: 2] | Should[cite: 2] | UC16[cite: 2] |
| **RF26** | Salvar favorecidos frequentes para reuso.[cite: 2] | Could[cite: 2] | UC13[cite: 2] |

### Cartões
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF27** | Emissão imediata de cartão virtual (número, validade e CVV).[cite: 2] | Must[cite: 2] | UC17[cite: 2] |
| **RF28** | Exibir número do cartão mascarado, revelando mediante reautenticação.[cite: 2] | Must[cite: 2] | UC17[cite: 2] |
| **RF29** | Solicitar cartão físico e acompanhar status do ciclo de vida.[cite: 2] | Should[cite: 2] | UC18[cite: 2] |
| **RF30** | Bloquear e desbloquear cartões (temporária ou definitivamente).[cite: 2] | Must[cite: 2] | UC19[cite: 2] |
| **RF31** | Ajustar limite do cartão respeitando o teto aprovado.[cite: 2] | Should[cite: 2] | UC20[cite: 2] |
| **RF32** | Processar autorizações de compra simuladas (limite e status).[cite: 2] | Should[cite: 2] | UC21[cite: 2] |
| **RF33** | Manter fatura com lançamentos, datas de fechamento e vencimento.[cite: 2] | Should[cite: 2] | UC22[cite: 2] |
| **RF34** | Permitir contestar lançamentos da fatura com justificativa.[cite: 2] | Could[cite: 2] | UC23[cite: 2] |
| **RF35** | Análise de contestação pelo backoffice (deferir/indeferir).[cite: 2] | Could[cite: 2] | UC24[cite: 2] |

### Pagamentos e Cobranças
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF36** | Pagar boleto por linha digitável ou código de barras.[cite: 2] | Must[cite: 2] | UC25[cite: 2] |
| **RF37** | Validar dígito verificador e exibir dados do boleto antes de confirmar.[cite: 2] | Must[cite: 2] | UC25[cite: 2] |
| **RF38** | Pagar cobrança Pix por QR Code (payload EMV).[cite: 2] | Should[cite: 2] | UC26[cite: 2] |
| **RF39** | Emitir cobrança Pix com valor, descrição e validade.[cite: 2] | Should[cite: 2] | UC27[cite: 2] |
| **RF40** | Agendar pagamentos recorrentes.[cite: 2] | Could[cite: 2] | UC28[cite: 2] |
| **RF41** | Creditar automaticamente o emissor na liquidação da cobrança.[cite: 2] | Should[cite: 2] | UC29[cite: 2] |
| **RF42** | Notificar cliente por e-mail e push sobre eventos financeiros.[cite: 2] | Should[cite: 2] | UC30[cite: 2] |

### Administração, Segurança e Suporte
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF43** | Gerenciar usuários internos (criar, editar, desativar).[cite: 2] | Must[cite: 2] | UC31[cite: 2] |
| **RF44** | Controle de acesso baseado em perfis (RBAC).[cite: 2] | Must[cite: 2] | UC31[cite: 2] |
| **RF45** | Trilha de auditoria imutável para operações sensíveis.[cite: 2] | Must[cite: 2] | UC32[cite: 2] |
| **RF46** | Impedir a alteração ou exclusão de registros da trilha de auditoria.[cite: 2] | Must[cite: 2] | UC32[cite: 2] |
| **RF47** | Bloquear contas suspeitas de fraude (administração).[cite: 2] | Must[cite: 2] | UC33[cite: 2] |
| **RF48** | Exibir requisições pendentes de desbloqueio de contas na visão de administração/suporte para liberação direta.[cite: 2] | Must[cite: 2] | UC33[cite: 2] |
| **RF49** | Consulta avançada à trilha de auditoria pelo auditor.[cite: 2] | Should[cite: 2] | UC34[cite: 2] |
| **RF50** | Parametrização de tarifas, limites e teto máximo de segurança.[cite: 2] | Should[cite: 2] | UC35[cite: 2] |
| **RF51** | Relatórios gerenciais (volume transacionado, contas ativas, chamados).[cite: 2] | Should[cite: 2] | UC36[cite: 2] |
| **RF52** | Abertura de chamados de suporte pelo cliente.[cite: 2] | Should[cite: 2] | UC38[cite: 2] |
| **RF53** | Atendimento e atualização de chamados pelo analista.[cite: 2] | Should[cite: 2] | UC37[cite: 2] |

---

## 2. Requisitos Não Funcionais (RNF) — ISO/IEC 25010

### Desempenho e Eficiência
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF01** | Telas de saldo e extrato devem responder em até 2 segundos (p95).[cite: 2] | Teste de carga com 50 usuários simultâneos.[cite: 2] |
| **RNF02** | Operações de escrita da API devem responder em até 3 segundos.[cite: 2] | Medição de tempo de resposta no log.[cite: 2] |
| **RNF03** | Suportar 100 usuários simultâneos sem degradação perceptível.[cite: 2] | Teste com k6 ou JMeter.[cite: 2] |
| **RNF04** | Aplicativo mobile deve iniciar em até 4 segundos em Android de entrada.[cite: 2] | Medição em dispositivo real.[cite: 2] |
| **RNF05** | Consultas de extrato devem usar paginação de no máximo 50 registros.[cite: 2] | Inspeção de código e da API.[cite: 2] |

### Segurança
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF06** | Comunicação entre cliente e servidor via HTTPS (TLS 1.2+).[cite: 2] | Verificação do certificado no Render.[cite: 2] |
| **RNF07** | Senhas armazenadas com hash bcrypt (custo >= 12).[cite: 2] | Inspeção da tabela `usuarios`.[cite: 2] |
| **RNF08** | Dados sensíveis (CPF, cartão) exibidos mascarados por padrão.[cite: 2] | Teste de interface.[cite: 2] |
| **RNF09** | Proteção contra vulnerabilidades do OWASP Top 10.[cite: 2] | Checklist OWASP + Eloquent + Blade escaping.[cite: 2] |
| **RNF10** | Tokens de API expiram em 60 minutos, com refresh token.[cite: 2] | Teste de expiração.[cite: 2] |
| **RNF11** | Sessão web encerra após 15 minutos de inatividade.[cite: 2] | Teste manual.[cite: 2] |
| **RNF12** | Rate limiting de 60 requisições por minuto por usuário.[cite: 2] | Teste de estresse no endpoint.[cite: 2] |
| **RNF13** | Nenhum dado pessoal gravado em logs de aplicação.[cite: 2] | Revisão de código e amostragem de logs.[cite: 2] |
| **RNF14** | Atendimento aos princípios da LGPD.[cite: 2] | Checklist de conformidade.[cite: 2] |

### Confiabilidade e Integridade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF15** | Movimentação financeira em transação ACID (rollback em falha).[cite: 2] | Teste de falha induzida.[cite: 2] |
| **RNF16** | Soma dos lançamentos de uma transação deve ser zero (partidas dobradas).[cite: 2] | Teste automatizado sobre o razão.[cite: 2] |
| **RNF17** | Requisições de transação idempotentes por chave de idempotência.[cite: 2] | Teste de reenvio da mesma requisição.[cite: 2] |
| **RNF18** | Valores monetários em `numeric(18,2)` e inteiros em centavos.[cite: 2] | Inspeção de migrations e código.[cite: 2] |
| **RNF19** | Backup semanal do banco de dados com restauração testada.[cite: 2] | Evidência de dump e restore.[cite: 2] |
| **RNF20** | Disponibilidade mensal >= 95%.[cite: 2] | Monitoramento por uptime checker.[cite: 2] |

### Usabilidade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF21** | Interface responsiva de 320px a 1920px.[cite: 2] | Teste em três resoluções.[cite: 2] |
| **RNF22** | Tela de confirmação obrigatória antes de efetivar operações financeiras.[cite: 2] | Teste de fluxo.[cite: 2] |
| **RNF23** | Mensagens de erro claras em português com instruções de correção.[cite: 2] | Revisão do catálogo de mensagens.[cite: 2] |
| **RNF24** | Concluir transferência sem treinamento em até 3 minutos.[cite: 2] | Teste com 5 usuários reais.[cite: 2] |
| **RNF25** | Atender nível AA da WCAG 2.1 em contraste e navegação por teclado.[cite: 2] | Auditoria com Lighthouse.[cite: 2] |

### Manutenibilidade e Portabilidade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF26** | Código segue o padrão PSR-12 (PHP) e convenções de estilo JavaScript/TypeScript (ESLint/Prettier) para o Expo. | Análise com Laravel Pint e linter configurado. |
| **RNF27** | Cobertura de testes automatizados >= 60% com TDD.[cite: 2] | Relatório de cobertura PHPUnit.[cite: 2] |
| **RNF28** | Alterações de esquema feitas por migration versionada.[cite: 2] | Histórico de `database/migrations`.[cite: 2] |
| **RNF29** | Configurações sensíveis em variáveis de ambiente (`.env`).[cite: 2] | Inspeção do `.gitignore` e `.env.example`.[cite: 2] |
| **RNF30** | API REST versionada (`/api/v1`) e documentada em OpenAPI/Swagger.[cite: 2] | Acesso à documentação publicada.[cite: 2] |
| **RNF31** | Sistema executável em Linux com deploy automático via GitHub.[cite: 2] | Deploy funcional no Render.[cite: 2] |
| **RNF32** | Suporte a Android 8.0+ (API 26) e compatibilidade com iOS configurados via `app.json` do Expo. | Configuração de build e propriedades do arquivo `app.json`. |

### Restrições de Projeto
| ID | Restrição |
|:---|:---|
| **RNF33** | Backend em Laravel (PHP 8.2+).[cite: 2] |
| **RNF34** | Frontend web em Blade com Tailwind CSS.[cite: 2] |
| **RNF35** | Aplicativo mobile desenvolvido em React Native com Expo. |
| **RNF36** | Banco de dados PostgreSQL.[cite: 2] |
| **RNF37** | Hospedagem no Render (plano gratuito).[cite: 2] |
| **RNF38** | Versionamento no GitHub com commits semanais.[cite: 2] |
| **RNF39** | Desenvolvimento integral durante o semestre, sem código legado.[cite: 2] |
>>>>>>> fd64bc41aec70c7c10a10bd47c2abbaa2638f15c
