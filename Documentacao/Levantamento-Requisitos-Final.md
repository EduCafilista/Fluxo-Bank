# 4.2 - Levantamento Final de Requisitos

**Projeto:** Fluxo - Banco Digital
**Data:** 08/10/2026
**Versão:** Final 4.0

Este documento consolida os requisitos e regras de negócio do sistema. A numeração das Regras de Negócio (RN) e Requisitos Funcionais (RF) foi preservada da documentação inicial e diagramas de modelagem. Os requisitos complementares desta revisão estão detalhados também em [`Requisitos-Complementares-2026-10.md`](Requisitos-Complementares-2026-10.md).

## 1. Requisitos Funcionais (RF)

| ID | Descrição do Requisito | Prioridade |
| :--- | :--- | :---: |
| **RF-SEG01** | **Log de Requisições Global (Middleware):** o sistema deve implementar um middleware universal no Laravel que registre 100% das requisições HTTP da API e Web, gravando: IP do cliente, User-Agent, Rota acessada, Payload (ofuscando dados sensíveis como senhas e CVV), Data/Hora exata e ID do usuário autenticado. | `Must` |
| **RF-SEG02** | **Auditoria de Banco de Dados (Triggers):** o banco de dados PostgreSQL deve utilizar triggers em todas as tabelas de domínio para capturar o estado anterior (OLD) e o novo estado (NEW) de qualquer operação de INSERT, UPDATE ou DELETE, armazenando o diff em uma coluna estruturada (JSONB). | `Must` |
| **RF-SEG03** | **Imutabilidade da Trilha:** os registros na trilha de auditoria devem ser do tipo *append-only*. O sistema deve impedir, via restrições e regras no banco de dados, qualquer deleção ou alteração desses registros. | `Must` |
| **RF-SEG04** | **Log para todo tipo de operação do aplicativo:** o motor de logging não se limita a escritas financeiras — deve registrar também entradas no app (login/logout), consultas de leitura (saldo, extrato, fatura, trilha) e movimentações, categorizando cada evento em `ACESSO`, `CONSULTA` ou `MOVIMENTACAO`. A estrutura de log espelha o esquema das tabelas de domínio. | `Must` |
| **RF01** | Permitir que um visitante se cadastre informando dados básicos e validando o CPF matematicamente. O cadastro passa por KYC simulado e automatizado para aprovação de conta. | `Must` |
| **RF02** | Autenticação robusta de usuários internos e clientes utilizando e-mail, senha (hash) e suporte a Verificação em Dois Fatores (2FA) por código de 6 dígitos temporário. | `Must` |
| **RF03** | Cálculo dinâmico e preciso do saldo atual da conta derivado exclusivamente da soma do histórico de lançamentos contábeis (partidas dobradas). | `Must` |
| **RF04** | Cadastro de chaves Pix (CPF, e-mail, telefone, aleatória) com validação de duplicidade rigorosa na base. | `Must` |
| **RF05** | Realização de transferências (Pix e internas) com exibição de tela de confirmação prévia dos dados do favorecido e submissão à análise de risco antes da efetivação. | `Must` |
| **RF06** | Agendamento de transferências/pagamentos para datas futuras, gerando comprovantes únicos em PDF e disparando notificações ao usuário no momento da execução. | `Should` |
| **RF07** | Gestão completa do ciclo de vida de cartões (emissão física, geração do cartão virtual a partir do físico, bloqueio/desbloqueio e ajuste de limites baseados em tetos pré-aprovados). | `Must` |
| **RF08** | Pagamento de cobranças via QR Code Pix (payload EMV) e boletos por código de barras/linha digitável. | `Must` |
| **RF09** | Backoffice gerencial contendo gestão de perfis (RBAC), bloqueio de contas suspeitas, atendimento de chamados de suporte técnico e consulta detalhada às trilhas de auditoria geradas. | `Must` |
| **RF10** | **Especialização de clientes:** o sistema deve suportar clientes **Pessoa Física** (CPF, validado matematicamente) e **Pessoa Jurídica** (CNPJ, validado matematicamente, com razão social e representante legal), cada um com regras e dados cadastrais próprios. | `Must` |
| **RF11** | **Especialização de contas:** a conta deve se especializar em **Corrente**, **Poupança**, **Salário** (associáveis a cliente PF) e **PJ** (associável a cliente PJ), cada uma com regras de uso específicas. | `Must` |
| **RF12** | **Especialização de cartões:** o cartão se especializa em **Débito** e **Crédito**; cada um existe nas formas **Física** e **Virtual**. Um cartão virtual só pode ser gerado a partir de um cartão físico ativo. | `Must` |
| **RF13** | **Bloqueio de conta pelo cliente:** o próprio cliente pode ativar um bloqueio preventivo (autobloqueio) na própria conta, controlado por uma flag booleana. | `Must` |
| **RF14** | **Limites configuráveis do Pix:** o cliente pode configurar, dentro de um teto máximo pré-aprovado pelo banco, o valor máximo por transação Pix e o limite diário. | `Must` |
| **RF15** | **Geração de QR Code para recebimento de Pix:** o sistema deve gerar uma imagem de QR Code a partir do payload EMV de uma cobrança Pix. | `Must` |
| **RF16** | **Arquitetura MVC:** o back-end deve seguir o padrão **Model-View-Controller**. | `Must` |
| **RF17** | **Token de autenticação no back-end:** toda sessão autenticada deve receber um token de acesso (Laravel Sanctum), emitido no login e validado em toda requisição subsequente. | `Must` |
| **RF18** | **Ciclo de status da conta:** o sistema deve separar `status` operacional (`PENDENTE`, `ATIVA`, `BLOQUEADA`, `ENCERRADA`) de `status_abertura`/KYC (`PENDENTE_KYC`, `EM_ANALISE`, `APROVADA`, `REPROVADA`) e registrar cada transição. | `Must` |
| **RF19** | **Cartão virtual vinculado:** todo cartão virtual deve possuir instância própria em `cartoes`, vínculo com a conta e referência obrigatória ao cartão físico ativo que o originou, mantendo a mesma modalidade. | `Must` |
| **RF20** | **Compras on-line no cartão físico:** a autorização de uma compra on-line deve respeitar a flag `cartoes_fisicos.permite_compras_online` (flag funcional `pg_online`), iniciada como `false` por segurança. | `Must` |
| **RF21** | **Consulta de assinaturas de cartão:** o sistema deve manter o read model `assinaturas_cartao` para consultar recorrências identificadas nas compras, separado da contratação de planos próprios (`assinaturas`). | `Should` |
| **RF22** | **Auditoria de alterações:** toda inclusão, alteração ou exclusão nas tabelas de domínio deve registrar operação, tabela, registro, estado anterior, estado posterior e diff sanitizado em `logs_auditoria`; acessos e consultas são registrados pelo middleware. | `Must` |
| **RF23** | **Limites Pix condicionais:** o sistema deve controlar limite por operação, por dia, por janela diurna/noturna e por quantidade; se a conta de origem não tiver chave Pix ativa, devem ser aplicados limites reduzidos específicos. | `Must` |
| **RF24** | **Aviso de viagem por cartão:** o aviso de viagem deve ser associado a um cartão e só neutralizar a divergência de localização para esse cartão e no período/localidade declarados. | `Must` |
| **RF25** | **Agendamento com status:** transferências e pagamentos agendados devem persistir em `agendamentos`, com idempotência, tentativas, data/hora de execução e estados de processamento. | `Must` |

### Rastreabilidade de Requisitos Legados (Segurança)

A documentação da modelagem de segurança faz referência a requisitos antigos de trilha de auditoria (RF45 a RF48). Abaixo a correspondência com a nova numeração e escopo:

| ID Original | Ocorrência e Contexto Original | Correspondência Atual | Confiança da Equivalência |
| :--- | :--- | :--- | :--- |
| **RF45** | `seq-01`, `seq-04`, `seq-06`, `atv-03`. Contexto: Log, 2FA, segurança, cadastro. | **RF-SEG01** (Log global) | Possível correspondência (Contexto focado em log e segurança abrangente). |
| **RF46** | `seq-06-trilha-auditoria.svg`. Contexto: Trilha de auditoria junto com RF45 e RF48. | **RF-SEG02** (Auditoria de DB) | Possível correspondência. |
| **RF47** | Nenhuma ocorrência encontrada em nenhum diagrama ou markdown. | - | Nenhuma. Requisito ausente do repositório. |
| **RF48** | `seq-06-trilha-auditoria.svg`. Contexto textual: "Consulta pelo auditor - acesso somente leitura (RF48)". | **RF-SEG03** (Imutabilidade/Leitura) | Correspondência explícita (Refere-se ao acesso somente leitura à trilha). |

---

## 2. Regras de Negócio (RN)

### 2.1. Regras Com Definição Explícita na Documentação Original e nesta revisão

| ID | Regra e Descrição | Fonte Documental |
| :--- | :--- | :--- |
| **RN02** | A Conta abstrata e o Núcleo Contábil operam como um sistema **Invariante**, ou seja, "sem coluna de saldo (RN02)". O saldo é sempre derivado. | `cls-01-assinatura.svg`, `cls-02-retirada-2fa.svg`, `cls-03-nucleo-contabil.svg`, `DiagramaComponentes.md`. |
| **RN03** | Atua em conjunto com a RN02 para garantir o modelo de partidas dobradas e a invariância do núcleo contábil. | `cls-03-nucleo-contabil.svg`, `DiagramaComponentes.md`. |
| **RN18** | **Gatilho de 2FA:** A verificação 2FA é exigida se o valor for maior que 30% do limite diário, o canal for inédito, o dispositivo não for confiável ou a operação ocorrer entre 20h e 6h. | `DiagramaSequencia.md`, `atv-03-retirada-2fa.svg`, `cls-02-retirada-2fa.svg`. |
| **RN22** | **Soft Delete em Assinatura:** o registro em `ItemAssinatura` não é apagado; o sistema preenche `dataExclusao` e `motivoExclusao` e recalcula `valorAtual`. | `DiagramaSequencia.md`, `ucd-04-assinaturas.svg`, `cls-01-assinatura.svg`. |
| **RN23** | **Cobrança Proporcional:** a cobrança do ciclo da assinatura é gerada com valor proporcional aos itens ativos na competência. | `DiagramaSequencia.md`, `cls-01-assinatura.svg`. |
| **RN27** | **Saldo disponível no Pix:** toda transferência Pix verifica o saldo disponível (saldo contábil menos reservados) antes da análise de risco. | `atv-02-transferencia-pix.svg`. |
| **RN28** | **Limite de valor por transação:** o cliente define um teto de valor por transação Pix, respeitando o teto aprovado para o seu perfil. | `atv-02-transferencia-pix.svg`. |
| **RN29** | **Limite diário — valor e quantidade:** a soma das transações Pix do dia não pode ultrapassar o limite diário configurado nem a quantidade máxima de transações. | `atv-02-transferencia-pix.svg`. |
| **RN30** | **Horário noturno Pix:** entre 20h e 6h, os limites de valor por transação e diário do Pix são reduzidos automaticamente. | `atv-02-transferencia-pix.svg`. |
| **RN31** | **Aviso de viagem:** transações realizadas dentro do período/local de viagem não elevam o score de risco por localização, mas somente para o cartão vinculado ao aviso nesta versão. | `Requisitos-Complementares-2026-10.md`, `atv-02-transferencia-pix.svg`. |
| **RN32** | **Localização divergente:** Pix em localização diferente do padrão sem aviso de viagem eleva o risco e exige 2FA ou retenção. | `atv-02-transferencia-pix.svg`. |
| **RN33** | **Cartão virtual depende do físico:** não é possível gerar um cartão virtual sem um cartão físico ativo correspondente à mesma modalidade e conta. | `Requisitos-Complementares-2026-10.md`, `ucd-03`. |
| **RN34** | **Bloqueio de conta pelo cliente:** flag `bloqueada` ativa impede qualquer movimentação de débito; apenas o próprio cliente ou Administrador podem reverter. | `cls-01`, `cls-02`, `atv-02`. |
| **RN35** | **Validação matemática de CPF/CNPJ:** o dígito verificador do CPF/CNPJ é conferido localmente antes de qualquer inserção. | `atv-01`. |
| **RN36** | **Payload do QR Code Pix:** o QR Code gerado segue o padrão EMV e é validado quanto à integridade antes de ser exibido/reimpresso. | `seq-08`. |
| **RN37** | **Status da conta:** uma conta só passa de `PENDENTE` para `ATIVA` após `status_abertura = APROVADA`; conta `BLOQUEADA` ou `ENCERRADA` não pode debitar. | `Requisitos-Complementares-2026-10.md`, migration `000006`, ACC-01–03. |
| **RN38** | **Origem do cartão virtual:** o virtual só pode ser criado se o cartão físico estiver `ATIVO`, tiver a mesma modalidade e pertencer à mesma conta. | `Requisitos-Complementares-2026-10.md`, migration `000007`, CRT-02. |
| **RN39** | **Pagamento on-line:** compra em canal `ONLINE` exige `permite_compras_online = true` no cartão físico associado; o default é `false`. | `Requisitos-Complementares-2026-10.md`, migration `000007`, CRT-05. |
| **RN40** | **Assinatura de cartão é consulta:** `assinaturas_cartao` é um read model de recorrências detectadas; cancelá-la não apaga transações nem substitui `assinaturas` de planos Fluxo. | `Requisitos-Complementares-2026-10.md`, migration `000010`, CRT-09. |
| **RN41** | **Auditoria em duas camadas:** triggers registram INSERT/UPDATE/DELETE; middleware registra ACESSO/CONSULTA e fornece contexto de usuário, requisição e dispositivo. `logs_auditoria` é append-only. | `Requisitos-Complementares-2026-10.md`, migration `000012`, AUD-02. |
| **RN42** | **Ausência de chave Pix:** sem chave Pix `ATIVA` na conta de origem, aplicar o menor teto entre a janela vigente e `limite_sem_chave_*`; valor zero bloqueia a transferência. | `Requisitos-Complementares-2026-10.md`, migrations `000006`/`000008`, MOV-04. |
| **RN43** | **Consumo de limite:** reservas e utilizações são acumuladas por conta, data e janela em `consumos_limites_pix` com lock transacional; cancelamento devolve a reserva. | `Requisitos-Complementares-2026-10.md`, migration `000008`, SEQ-09. |
| **RN44** | **Aviso por cartão:** um aviso de viagem só é válido quando `cartao_id`, período, status e localidade do evento coincidirem; não há aplicação global implícita para os demais cartões. | `Requisitos-Complementares-2026-10.md`, migration `000009`, ACC-09. |
| **RN45** | **Estado de agendamento:** apenas `AGENDADO` pode ser capturado pelo worker; a execução usa lock e idempotência, e termina em `EXECUTADO`, `FALHOU`, `CANCELADO` ou `EXPIRADO`. | `Requisitos-Complementares-2026-10.md`, migration `000011`, MOV-06. |

### 2.2. Regras referenciadas na modelagem sem especificação textual original

As regras listadas abaixo constam nos diagramas de modelagem para propósitos de amarração lógica e controle de versão, mas não dispõem de uma definição textual formal em outro documento da equipe: RN01 (cadastro/KYC), RN17 (trilha), RN19–RN20 (2FA), RN24–RN25 (assinaturas). A revisão 4.0 não reutiliza esses identificadores para novos comportamentos.

---

## 3. Requisitos Não Funcionais (RNF)

### 3.0 Resumo por categoria

| Categoria | Restrição / Métrica de Qualidade |
| :--- | :--- |
| **Desempenho** | Resposta a leituras em até 2 segundos (P95). Escritas e logs executados assincronamente (filas), exceto o registro mínimo de auditoria transacional. |
| **Segurança** | HTTPS (TLS 1.2+), senhas em bcrypt, proteção OWASP, sessões com expiração de 15 min; nunca auditar CVV, senha ou token. |
| **Confiabilidade** | Transações ACID rigorosas, uso de `numeric(18,2)` para valores financeiros e idempotência para Pix/agendamentos. |
| **LGPD / Privacidade** | Ofuscação de CPF/CNPJ e cartão. Anonimização em encerramento de conta. JSON de auditoria sanitizado. |
| **Arquitetura** | Padrão MVC no back-end, serviços isolados, token de sessão (Laravel Sanctum), worker de agendamentos e triggers PostgreSQL. |
| **Stack Técnica** | Frontend: Blade/Tailwind (Web), React Native (Mobile). Backend: Laravel (PHP). Banco: PostgreSQL 16. |

### 3.1 Especificação detalhada (RNF01–RNF39)

Classificados pela norma **ISO/IEC 25010** (qualidade de produto de software).

### RNF — Desempenho e eficiência

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF01 | As telas de saldo e extrato devem responder em até 2 segundos no percentil 95. | Teste de carga com 50 usuários simultâneos. |
| RNF02 | As operações de escrita da API (transferência, pagamento) devem responder em até 3 segundos. | Medição de tempo de resposta no log. |
| RNF03 | O sistema deve suportar 100 usuários simultâneos sem degradação perceptível. | Teste com k6 ou JMeter. |
| RNF04 | O aplicativo mobile deve iniciar em até 4 segundos em um aparelho Android de entrada. | Medição em dispositivo real. |
| RNF05 | Consultas de extrato devem usar paginação de no máximo 50 registros por página. | Inspeção de código e da API. |

### RNF — Segurança

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF06 | Toda comunicação entre cliente e servidor deve usar HTTPS (TLS 1.2+). | Verificação do certificado no Render. |
| RNF07 | Senhas devem ser armazenadas com hash bcrypt (custo ≥ 12), nunca em texto claro. | Inspeção da tabela `users`. |
| RNF08 | Dados sensíveis (CPF, número de cartão) devem ser exibidos mascarados por padrão. | Teste de interface. |
| RNF09 | O sistema deve estar protegido contra as vulnerabilidades do OWASP Top 10 (SQL injection, XSS, CSRF, IDOR). | Checklist OWASP + uso de Eloquent, Blade escaping e tokens CSRF. |
| RNF10 | Tokens de API devem expirar em 60 minutos, com renovação por refresh token. | Teste de expiração. |
| RNF11 | A sessão web deve encerrar após 15 minutos de inatividade. | Teste manual. |
| RNF12 | O sistema deve aplicar rate limiting de 60 requisições por minuto por usuário. | Teste de estresse no endpoint. |
| RNF13 | Nenhum dado pessoal deve ser gravado em logs de aplicação sem mascaramento. | Revisão de código e amostragem de logs. |
| RNF14 | O sistema deve atender aos princípios da LGPD: finalidade, minimização, consentimento e direito de exclusão. | Checklist de conformidade. |

### RNF — Confiabilidade e integridade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF15 | Toda movimentação financeira deve ocorrer dentro de uma transação ACID; falha parcial implica rollback total. | Teste de falha induzida no meio da operação. |
| RNF16 | A soma dos lançamentos de uma transação deve ser sempre zero (partidas dobradas). | Teste automatizado sobre o razão. |
| RNF17 | Requisições de transação devem ser idempotentes por chave de idempotência, evitando duplicidade em reenvios. | Teste de reenvio da mesma requisição. |
| RNF18 | Valores monetários devem usar `numeric(18,2)` no banco e inteiros em centavos na aplicação — nunca ponto flutuante. | Inspeção das migrations e do código. |
| RNF19 | O banco de dados deve ter backup semanal, com procedimento de restauração documentado e testado. | Evidência do dump e teste de restore. |
| RNF20 | O sistema deve ter disponibilidade mensal ≥ 95%, considerando as limitações do plano gratuito. | Monitoramento por uptime checker. |

### RNF — Usabilidade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF21 | Interface responsiva, funcional de 320 px (celular) a 1920 px (desktop). | Teste em três resoluções. |
| RNF22 | Toda operação financeira deve exigir uma tela de confirmação antes da efetivação. | Teste de fluxo. |
| RNF23 | Mensagens de erro devem ser claras, em português, indicando como corrigir o problema. | Revisão do catálogo de mensagens. |
| RNF24 | Um usuário novo deve concluir uma transferência sem treinamento em até 3 minutos. | Teste com 5 usuários reais. |
| RNF25 | O sistema deve atender ao nível AA da WCAG 2.1 em contraste e navegação por teclado. | Auditoria com Lighthouse. |

### RNF — Manutenibilidade e portabilidade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF26 | O código deve seguir o padrão PSR-12 (PHP) e as convenções do TypeScript/React Native. | Análise com Laravel Pint e ESLint. |
| RNF27 | O backend deve ter cobertura de testes automatizados ≥ 60% nas regras de negócio, desenvolvidas com TDD. | Relatório de cobertura do PHPUnit. |
| RNF28 | Toda alteração de esquema deve ser feita por migration versionada. | Histórico do diretório `database/migrations`. |
| RNF29 | Configurações sensíveis devem residir em variáveis de ambiente, nunca no repositório. | Inspeção do `.gitignore` e do `.env.example`. |
| RNF30 | A API REST deve ser versionada (`/api/v1`) e documentada em OpenAPI/Swagger. | Acesso à documentação publicada. |
| RNF31 | O sistema deve rodar em Linux e ser implantável por deploy automático a partir do GitHub. | Deploy funcional no Render. |
| RNF32 | O aplicativo mobile deve suportar Android 8.0 (API 26) ou superior. | Configuração do build. |
| RNF32.1 | O aplicativo React Native deve manter compatibilidade com iOS 13 ou superior, mesmo que a compilação para iOS não seja demonstrada nesta versão. | Configuração do Expo e teste de compatibilidade. |

### RNF — Restrições de projeto

| ID | Requisito |
|---|---|
| RNF33 | O backend deve ser desenvolvido em Laravel (PHP 8.3+). |
| RNF34 | O frontend web deve usar Blade com Tailwind CSS. |
| RNF35 | O aplicativo mobile deve ser desenvolvido em React Native com Expo/TypeScript. |
| RNF36 | O banco de dados de produção deve ser PostgreSQL 16. |
| RNF37 | A hospedagem deve ocorrer no Render, com plano gratuito. |
| RNF38 | O código deve ser versionado no GitHub, com commits semanais e acesso ao professor. |
| RNF39 | Todo o código deve ser desenvolvido durante o semestre, sem reaproveitamento de código legado. |

**Resumo:** 25 requisitos funcionais principais, 4 requisitos de segurança transversal, 45 regras de negócio e 39 requisitos não funcionais.

**Registro de alterações**

| Versão | Data | Alteração |
|---|---|---|
| 1.0 | 31/08/2026 | Versão inicial. |
| 3.0 | 10/09/2026 | Especialização de clientes, contas e cartões; auditoria e arquitetura MVC. |
| 4.0 | 08/10/2026 | Status da conta, cartão virtual vinculado à conta/físico, flag de compras on-line, consulta de assinaturas do cartão, auditoria por triggers, limites Pix sem chave e por horário, aviso por cartão e agendamento com status. |
