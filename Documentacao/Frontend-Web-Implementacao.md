# Fluxo Bank — Frontend Web Laravel Blade + Tailwind CSS

**Atualização:** 17/11/2026. A implementação foi adicionada ao backend existente em `Projeto/backend` e mantém a API `/api/v1` e o banco PostgreSQL. As seis capturas em `Documentacao/Prototipos-Mobile-Referencia/` documentam a estética adotada, adaptada para desktop com menu lateral, cards, sidebar de ações, formulários e layout responsivo.

## Referência de requisitos

Fonte primária: `Documentacao/Levantamento-Requisitos-Final.md`, versão 4.0, e `Documentacao/Requisitos-Complementares-2026-10.md`. **RNF34:** Blade + Tailwind para Web. **RNF21:** responsividade de 320 a 1920 px. **RNF22:** confirmação de operações financeiras. O frontend mobile **não faz parte deste ZIP**; o documento final mais recente define React Native + Expo/TypeScript (RNF35), embora relatórios anteriores mencionem Flutter. Revisar a divergência antes da defesa.

## Estrutura

| Local | Conteúdo |
| --- | --- |
| `resources/views/web/layouts` | Layout responsivo para cliente/admin e páginas públicas |
| `resources/views/web/auth` | Login, cadastro PF/PJ, 2FA, recuperação de senha |
| `resources/views/web/customer` | Dashboard, saldo, extrato, Pix, transferências, cartões, boleto, agendamentos, assinaturas, viagens, perfil e suporte |
| `resources/views/web/admin` | Painel administrativo, contas/KYC, chamados, auditoria e controle de perfis |
| `resources/views/web/components` | Elementos Blade reutilizáveis |
| `app/Http/Controllers/Web` | Controllers web que reutilizam Services de negócio |
| `app/Http/Middleware/Web*` | Controle de sessão e papéis CLIENTE/ATENDENTE/ADMIN |
| `resources/css`, `resources/js` | Entradas do Vite, Tailwind e QR Code |
| `public/assets` | Identidade visual, estilos e funcionalidades leves disponíveis como fallback |
| `routes/web.php` | Rotas web sem alterar a interface da API |

## Matriz resumida de rastreabilidade

| Requisito | Tela ou operação | Observação |
| --- | --- | --- |
| RF01, RF10, RN35 | `/acesso/cadastro` | Cadastro PF/PJ; regras CPF/CNPJ reutilizadas do backend; KYC por configuração existente |
| RF02, RF17, RNF11 | `/acesso/entrar`, `/acesso/verificar` | Login via AuthService/Sanctum + sessão web; expiração por inatividade de 15 minutos |
| RF03 | `/dashboard`, `/extrato` | Saldo e lançamentos reais via ContaService, sem saldo fictício |
| RF04 | `/pix/chaves` | Cadastro, consulta e revogação de chaves |
| RF05, RNF22 | `/pix/transferir`, `/pix/confirmar`, `/comprovantes/{id}` | Duas etapas; idempotência UUID; reaproveita TransferenciaService e antifraude |
| RF06, RF25 | `/agendamentos` | Agendar, consultar e cancelar; execução depende de worker agendado |
| RF07, RF12, RF19, RF20 | `/cartoes` | Cartão físico/virtual, bloqueio, limite, compras on-line e débito simulado quando habilitado |
| RF08, RF15, RN36 | `/pix/receber`, `/pagamentos` | Geração de payload EMV/QR Code e pagamento por linha digitável; leitura/pagamento *direto* por QR EMV ainda não tem endpoint específico no backend atual |
| RF09, RF18 | `/administracao` e `/administracao/contas` | Segregação CLIENTE vs ATENDENTE/ADMIN, aprovação de KYC e bloqueio de conta |
| RF09, RF-SEG01/04 | `/administracao/auditoria`, `/administracao/chamados` | Consulta de metadados de auditoria, atendimento e histórico; não expõe snapshots integrais/senhas/CVV |
| RF14, RF23 | `/pix/limites` | Limites configuráveis dentro dos tetos preaprovados (Service acrescentado) |
| RF13 | `/perfil` | Bloqueio/desbloqueio preventivo, encerramento da conta e configuração de 2FA |
| RF21 | `/assinaturas` | Planos e recorrências identificadas nos cartões |
| RF24 | `/viagens` | Avisos por cartão e localidades |

## Executar localmente

1. Requisitos: PHP 8.4, Composer 2, Node.js 22, PostgreSQL e extensões PHP do projeto.
2. Em `Projeto/backend`, configure `.env` com conexão PostgreSQL, `APP_KEY`, `APP_URL`, `SESSION_DRIVER=database`, `SESSION_LIFETIME=15` e SMTP quando necessário.
3. Instale dependências: `composer install` e `npm install`.
4. Prepare o Laravel: `php artisan key:generate` **apenas para um ambiente novo**, `php artisan migrate`, `npm run build`.
5. Rode `php artisan serve` e abra `/acesso/entrar`. Para CSS em desenvolvimento, execute `npm run dev` em outro terminal (Vite).
6. Não altere `APP_KEY` no Render já existente: isso pode invalidar dados criptografados e sessões.

## Implantação no Render

O Dockerfile usa dois estágios: **Node** compila Vite/Tailwind/QR Code; **PHP** instala Composer, copia os assets compilados e inicia migrations/config/rotas/servidor. Suba o ZIP no repositório e faça deploy. Mantenha o PostgreSQL, o `DB_URL` e demais segredos já existentes.

Configurações relevantes: `SESSION_LIFETIME=15`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<seu-dominio>` e `TRUSTED_PROXIES` conforme a plataforma. A aplicação fornece um fallback CSS/JS estático mesmo sem manifesto Vite, mas o build recomendado é o do Dockerfile. Mantenha HTTPS.

**2FA:** `FLUXO_2FA_EMAIL_ENTREGA=false` por padrão. Para habilitar o 2FA de novas contas, configure SMTP transacional verdadeiro (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, credenciais e remetente) e ative `FLUXO_2FA_EMAIL_ENTREGA=true`. Não usar driver `log` como entrega de códigos em produção. Para usuários que já possuem 2FA ativo, confira a entrega antes de implantar.

**Compras simuladas de débito:** permanecem condicionadas à variável já existente `FLUXO_SIMULACOES_FINANCEIRAS=true`; use apenas ambiente de testes.

**Agendamentos:** a interface cria e cancela, mas o processamento futuro requer configurar o worker/scheduler Laravel (`agendamentos:processar`) no ambiente. O deploy web sozinho não executa jobs continuamente no plano gratuito.

## Testar antes da apresentação/produção

- Cadastrar PF, PJ, efetuar login CLIENTE, logout e timeout após 15 minutos; conferir CSRF e erros de formulário.
- Fazer login como ATENDENTE/ADMIN; verificar ausência de acesso de CLIENTE ao backoffice e privilégio ADMIN para RBAC.
- Consultar saldo/extrato, criar e revogar chave, transferir com confirmação, testar saldo insuficiente, idempotência, comprovante e auditoria.
- Testar cartões físicos/virtuais, bloqueio, compras on-line, débito simulado, Pix por chave, recebimento e boleto; verificar as tabelas de auditoria no PostgreSQL.
- Criar/cancelar agendamento e comprovar execução com worker; gerar e ler QR Code com navegador; imprimir/salvar comprovante em PDF.
- Verificar telas a 320, 390, 768, 1280 e 1920 px, uso por teclado e navegador com HTTPS.

## Observações e limitações

- Não foram inventadas integrações com banco real, BACEN, liquidação Pix externa, envio real de notificações ou emissão bancária formal de boletos.
- O QR Code é gerado no navegador via biblioteca `qrcode`, a partir do payload EMV calculado pelo backend; a geração **não movimenta dinheiro**.
- A opção "imprimir / salvar em PDF" usa o recurso de impressão do navegador; **não é** um gerador PDF autônomo no backend.
- A etapa de revisão de transferência foi implementada com identificação do favorecido via chave Pix ou ID de conta interna; Pix QR de terceiro ainda não tem rota de pagamento específica no serviço existente.
- O frontend preserva os registros de auditoria e mostra somente metadados não sensíveis na interface; não interpreta logs íntegros como comprovante de conformidade LGPD sem auditoria adicional.
- Não foram executados testes de carga, testes de navegador, testes de integração com PostgreSQL ou deploy neste ambiente, pois as dependências Laravel/npm não estavam instaladas; essas etapas são necessárias antes de afirmar que está validado em produção.
