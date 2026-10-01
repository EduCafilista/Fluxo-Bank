# 4.5 - Prototipação de Telas (Figma)

## Objetivo
Especificar e detalhar o escopo para a construção do protótipo navegável de alta fidelidade do aplicativo mobile (área do cliente) e web (área administrativa) do **Fluxo - Banco Digital**, assegurando que os Casos de Uso aprovados sejam visualmente representados sem necessidade de implementação em código.

## Plataforma e Estrutura do Figma
O Figma deve ser organizado em 4 páginas (Pages) lógicas:
1. **01 - Design System:** Variantes de cores, botões, inputs, tipografia e ícones.
2. **02 - Cliente Mobile:** Frames na proporção de smartphone (ex: 390x844px) para as interfaces voltadas ao cliente final.
3. **03 - Administrativo Web:** Frames em formato Desktop (ex: 1440x900px) contendo Sidebar e views em tabelas para analistas e auditores.
4. **04 - Fluxos / Prototype:** Conexões de prototipação (`Navigate To` / `Open Overlay`) validando a navegabilidade do app.

## Tabela de Mapeamento: Tela vs Casos de Uso

| N. | Tela | Plataforma | Caso(s) de Uso | Objetivo Principal | Ator |
|---|---|---|---|---|---|
| **01** | Login | Mobile | ACC-04, ACC-06 | Autenticação inicial e recuperação de senha. | Cliente |
| **02** | Cadastro PF/PJ | Mobile | ACC-01, ACC-02 | Inserção de dados iniciais e documentos KYC. | Cliente / Visitante |
| **03** | Dashboard | Mobile | MOV-01 | Visão geral de saldo, atalhos principais e resumo do extrato. | Cliente |
| **04** | Extrato | Mobile | MOV-01 | Listagem de lançamentos com filtros de período. | Cliente |
| **05** | Área Pix | Mobile | MOV-02 | Central Pix (cadastrar chaves, pagar, receber). | Cliente |
| **06** | Transferência Pix | Mobile | MOV-03, MOV-06 | Tela para inserir chave, valor, agendamento. | Cliente |
| **07** | Confirmação | Mobile | MOV-03, MOV-04 | Overlay revisando dados e solicitando senha / 2FA. | Cliente |
| **08** | Comprovante | Mobile | MOV-08 | Recibo de sucesso com opção de PDF/Compartilhar. | Cliente |
| **09** | Receber Pix | Mobile | PAG-03 | QR Code EMV em tela (estático ou dinâmico). | Cliente |
| **10** | Meus Cartões | Mobile | CRT-01, CRT-03 | Listagem e status de cartões físicos e virtuais. | Cliente |
| **11** | Detalhes do Cartão | Mobile | CRT-02, CRT-04 | Bloquear/desbloquear, gerar cartão virtual, ver fatura. | Cliente |
| **12** | Segurança / Limites | Mobile | ACC-10, MOV-09 | Configurar limites Pix, 2FA, e autobloqueio preventivo. | Cliente |
| **13** | Configurações/Perfil | Mobile | ACC-07, ACC-09 | Manter dados, cadastrar aviso de viagem, encerrar conta. | Cliente |
| **14** | Assinaturas | Mobile | ASS-01, ASS-02 | Contratação de Planos do banco. | Cliente |
| **15** | Dashboard Admin | Web | ADM-06 | KPI de volume financeiro e listagem de chamados. | Admin |
| **16** | Pesquisa de Clientes | Web | ADM-01 | Busca e tabela de clientes da base. | Suporte/Admin |
| **17** | Gestão de Conta | Web | ADM-02, ADM-03 | Bloqueio/desbloqueio antifraude e visualização de status. | Suporte/Admin |
| **18** | Auditoria | Web | ADM-05 | Tela da trilha de eventos do MotorAntifraude (append-only). | Auditor/Admin |
| **19** | Suporte Técnico | Web | ADM-07, ADM-08 | Caixa de entrada de tickets e ferramenta de resposta. | Analista Suporte |

## Design System Resumido

**Paleta de Cores (Fluxo Digital):**
- **Primária:** `#2563eb` (Azul Vibrante) - CTAs (Call to Actions), ícones ativos, marcações de seleção.
- **Secundária:** `#0f172a` (Azul Escuro/Slate) - Títulos, barra de navegação principal.
- **Background App:** `#f8fafc` (Cinza claro para fundos) e `#ffffff` (Branco para cards e inputs).
- **Sucesso:** `#10b981` (Verde) - Inputs de crédito, status ativo.
- **Alerta/Aviso:** `#f59e0b` (Laranja) - Retenções antifraude, limites atingidos.
- **Erro/Destrutivo:** `#ef4444` (Vermelho) - Débitos no extrato, botões de exclusão, conta bloqueada.

**Tipografia:**
- Família principal: **Inter** (ou equivalente como Roboto).
- Títulos de Tela (H1): 22px a 24px, Peso: 700 (Bold).
- Body / Textos base: 14px a 16px, Peso: 400 ou 500.

**Componentes Básicos (Specs):**
- **Botão Primário:** Preenchimento `#2563eb`, texto Branco, border-radius `8px`, altura `48px`.
- **Botão Secundário:** Sem preenchimento, borda `1px solid #cbd5e1`, texto secundário.
- **Input Field:** Borda `1px solid #e2e8f0`, fundo `#ffffff`, altura `48px`, padding horizontal `12px`.
- **Input de Senha:** Possui ícone do "olho" para `toggle` de visibilidade (ex: componente nativo com `secureTextEntry`).
- **Card de Saldo:** Fundo usando gradiente linear da cor primária, texto branco. Include ícone de olhinho. Border-radius `12px`.
- **Item de Transação (List):** Flex-row. Esquerda: ícone de seta. Centro: Destinatário e Data. Direita: Valor em cor condicional.
- **Menu Inferior Mobile:** Fundo branco com `box-shadow`, 4 botões de navegação (Início, Pix, Cartões, Perfil).

## Fluxos Demonstráveis Obrigatórios

**1. Fluxo Principal de Transferência (Mobile)**
`01. Login` → [Entrar] → `03. Dashboard` → [Área Pix] → `05. Área Pix` → [Transferir] → `06. Transferência Pix` (preenchimento mockado) → [Continuar] → `07. Confirmação` (overlay de senha) → [Confirmar] → `08. Comprovante` → [Voltar ao Dashboard].

**2. Fluxos Secundários (Mobile)**
- **Ver Extrato:** `03. Dashboard` → [Ver Extrato Completo] → `04. Extrato`.
- **Gerenciar Cartões:** `03. Dashboard` → [Tab Cartões] → `10. Meus Cartões` → [Ver Detalhes] → `11. Detalhes do Cartão`.
- **Segurança:** `03. Dashboard` → [Tab Perfil/Opções] → `12. Segurança / Limites`.

**3. Fluxo Administrativo (Web Desktop)**
`Login Admin` → `15. Dashboard Admin` → [Sidebar: Clientes] → `16. Pesquisa de Clientes` → [Clicar na linha de um usuário] → `17. Gestão de Conta` → [Bloquear Conta].

## Restrições de Dados Mockados (LGPD / Demonstração)
> **Aviso:** Nenhum dado real de identificação pessoal, CPF, CNPJ, hash, token ou chave bancária válida deve ser embutido nos protótipos do Figma. 

**Dados que deverão constar nos Protótipos:**
- **Nome do Cliente:** Gabriel Henrique (Mock)
- **Agência:** 0001 / **Conta:** 12345-6
- **Saldo:** R$ 12.450,00
- **Exemplo Transferência Pix:**
  - Destino: João Silva
  - Valor: R$ 250,00
  - Chave: `joao@emailmock.com`

---
*Observação de Validação: O presente projeto visual materializa o escopo dos casos de uso arquitetados sem envolver escrita de código Blade/Vue ou React Native. A URL final do projeto Figma será vinculada neste mesmo documento após a transposição pelo designer responsável.*

**Link do Figma:** https://www.figma.com/design/qlMt1TssshlJmFvT1Ueqm2/Fluxo---Banco-Digital-%7C-Checkpoint-2

**Status do Figma: PARCIAL — Design System validado visualmente; Mobile, Administrativo e Prototype aguardam validação visual final.**

## Checklist de Montagem
[x] Criar página 01 - Design System
[ ] Criar página 02 - Cliente Mobile
[ ] Criar página 03 - Administrativo Web
[ ] 14 telas Mobile validadas
[ ] 5 telas Admin validadas
[ ] Prototype navegável validado
