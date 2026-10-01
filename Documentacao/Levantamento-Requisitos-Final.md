# 4.2 - Levantamento Final de Requisitos

**Projeto:** Fluxo - Banco Digital
**Data:** 01/10/2026
**Versão:** Final

Este documento consolida os requisitos e regras de negócio do sistema. A numeração das Regras de Negócio (RN) e Requisitos Funcionais (RF) foi preservada da documentação inicial e diagramas de modelagem. Regras adicionais descobertas nos diagramas foram documentadas conforme as fontes originais, sem invenções.

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

### 2.1. Regras Com Definição Explícita na Documentação Original

| ID | Regra e Descrição Original | Fonte Documental |
| :--- | :--- | :--- |
| **RN02** | A Conta abstrata e o Núcleo Contábil operam como um sistema **Invariante**, ou seja, "sem coluna de saldo (RN02)". O saldo é sempre derivado. | `cls-01-assinatura.svg`, `cls-02-retirada-2fa.svg`, `cls-03-nucleo-contabil.svg`, `DiagramaComponentes.md`. |
| **RN03** | Atua em conjunto com a RN02 para garantir o modelo de partidas dobradas e a invariância do núcleo contábil. | `cls-03-nucleo-contabil.svg`, `DiagramaComponentes.md`. |
| **RN18** | **Gatilho de 2FA:** A verificação 2FA é exigida se o valor for maior que 30% do limite diário, o canal for inédito, o dispositivo não for confiável ou a operação ocorrer entre 20h e 6h. | `DiagramaSequencia.md` (L81), `atv-03-retirada-2fa.svg`, `cls-02-retirada-2fa.svg`. |
| **RN22** | **Soft Delete em Assinatura:** O registro em ItemAssinatura não é apagado (Nunca DELETE). O sistema preenche `dataExclusao` e `motivoExclusao` e recalcula `valorAtual`. | `DiagramaSequencia.md` (L128), `ucd-04-assinaturas.svg`, `cls-01-assinatura.svg`. |
| **RN23** | **Cobrança Proporcional:** A cobrança do ciclo da assinatura é gerada com valor proporcional aos itens ativos na competência. | `DiagramaSequencia.md` (L129), `cls-01-assinatura.svg`. |
| **RN27** | **Saldo disponível no Pix:** toda transferência Pix verifica o saldo disponível (saldo contábil menos reservados) antes da análise de risco. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02-transferencia-pix.svg`. |
| **RN28** | **Limite de valor por transação:** o cliente define um teto de valor por transação Pix, respeitando o teto aprovado para o seu perfil. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02`. |
| **RN29** | **Limite diário — valor e quantidade:** a soma das transações Pix do dia não pode ultrapassar o limite diário configurado nem a quantidade máxima de transações. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02`. |
| **RN30** | **Horário noturno Pix:** entre 20h e 6h, os limites de valor por transação e diário do Pix são reduzidos automaticamente. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02`. |
| **RN31** | **Aviso de viagem:** transações realizadas dentro do período/local de viagem não elevam o score de risco por localização. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02`. |
| **RN32** | **Localização divergente:** Pix em localização diferente do padrão sem aviso de viagem eleva o risco e exige 2FA ou retenção. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-02`. |
| **RN33** | **Cartão virtual depende do físico:** não é possível gerar um cartão virtual sem um cartão físico ativo correspondente à mesma modalidade. | Original do *Levantamento-Requisitos-Inicial.md* e `ucd-03`. |
| **RN34** | **Bloqueio de conta pelo cliente:** flag `bloqueada` ativa (pelo cliente) impede qualquer movimentação de débito; apenas o próprio cliente ou Administrador podem reverter. | Original do *Levantamento-Requisitos-Inicial.md*, `cls-01`, `cls-02`, `atv-02`. |
| **RN35** | **Validação matemática de CPF/CNPJ:** o dígito verificador do CPF/CNPJ é conferido localmente antes de qualquer inserção. | Original do *Levantamento-Requisitos-Inicial.md* e `atv-01`. |
| **RN36** | **Payload do QR Code Pix:** o QR Code gerado segue o padrão EMV e é validado quanto à integridade antes de ser exibido/reimpresso. | Original do *Levantamento-Requisitos-Inicial.md* e `seq-08`. |

### 2.2. Regras referenciadas na modelagem sem especificação textual original

As Regras de Negócio listadas abaixo constam nos diagramas de modelagem e arquitetura para propósitos de amarração lógica e controle de versão, contudo não dispõem de uma definição textual formal, detalhada ou unívoca em nenhum outro documento da equipe de especificação original.

*   **RN01** — Referenciada exclusivamente no Diagrama de Sequência de Cadastro (`seq-01-cadastro-kyc.svg` e `DiagramaSequencia.md` na seção "Cadastro, KYC e abertura de conta"), operando em conjunto com o RF01, porém sem especificação de suas políticas exatas.
*   **RN17** — Referenciada no Diagrama de Sequência de Trilha de Auditoria (`seq-06-trilha-auditoria.svg`) como título geral. Não há texto detalhando a regra na modelagem, apenas sua citação isolada.
*   **RN19 e RN20** — Referenciadas de forma agrupada (`RN18-RN20`) no Diagrama de Atividades de Retirada com 2FA (`atv-03-retirada-2fa.svg`). Sabendo que RN18 é o Gatilho do 2FA, RN19 e RN20 não possuem sua finalidade individual documentada explicitamente.
*   **RN24 e RN25** — Referenciadas de forma agrupada no pacote de regras do serviço de Assinaturas (`RN22-RN25`) dentro do Diagrama de Componentes (`DiagramaComponentes.md`). Sabendo que RN22 e RN23 dizem respeito à retirada e cobrança proporcional, as regras RN24 e RN25 permanecem sem definição de seu texto e comportamento individual.

---

## 3. Requisitos Não Funcionais (RNF)

| Categoria | Restrição / Métrica de Qualidade |
| :--- | :--- |
| **Desempenho** | Resposta a leituras em até 2 segundos (P95). Escritas e logs executados assincronamente (filas). |
| **Segurança** | HTTPS (TLS 1.2+), senhas em bcrypt, proteção OWASP, sessões com expiração de 15 min. |
| **Confiabilidade** | Transações ACID rigorosas, uso de `numeric(18,2)` para evitar anomalias de arredondamento em regras financeiras. |
| **LGPD / Privacidade** | Ofuscação de CPF/CNPJ e cartão. Anonimização em encerramento de conta. |
| **Arquitetura** | Padrão MVC no back-end (Models Eloquent, Controllers, Views/Resources), serviços isolados, token de sessão (Laravel Sanctum). |
| **Stack Técnica** | Frontend: Blade/Tailwind (Web), React Native (Mobile). Backend: Laravel (PHP). Banco: PostgreSQL 16. |
