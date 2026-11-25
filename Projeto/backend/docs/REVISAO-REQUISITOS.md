# Revisão de requisitos do Fluxo Bank

A revisão usou o backend fornecido e a documentação da revisão `db484a6`. O escopo da entrega é a API e a interface web Blade. [Requisitos finais 4.0](https://github.com/EduCafilista/Fluxo-Bank/blob/db484a6/Documentacao/Levantamento-Requisitos-Final.md) · [Escopo](https://github.com/EduCafilista/Fluxo-Bank/blob/db484a6/Documentacao/Escopo-Projeto.md) · [Implementação web](https://github.com/EduCafilista/Fluxo-Bank/blob/db484a6/Documentacao/Frontend-Web-Implementacao.md)

## Requisitos funcionais

| ID | Implementação e verificação |
| --- | --- |
| RF01 | Autocadastro web e API. O cadastro cria cliente e conta na mesma transação. KYC simulado automático ou encaminhamento para aprovação no painel. |
| RF02 | Login com bcrypt, bloqueio temporário, desafio de seis dígitos e recuperação manual por operador autorizado. A entrega do 2FA por e-mail requer SMTP. |
| RF03 | O saldo vem dos lançamentos em centavos. A suíte verifica a soma zero das partidas. |
| RF04 | Cadastro e revogação de chaves por CPF, CNPJ, e-mail, telefone e chave aleatória. |
| RF05 | Revisão de favorecido e valor na web. A efetivação verifica saldo, limites e status sob lock e grava partidas dobradas. A regra de localização do Pix permanece parcial, conforme a seção de regras de negócio. |
| RF06 | Transferências futuras, repetição mensal, pagamentos de boleto, notificação no painel após execução e comprovante PDF. Execução depende do scheduler. |
| RF07 | Emissão, geração de virtual, bloqueio e ajuste de limites nas telas e na API. |
| RF08 | Pagamento de cobranças Pix do Fluxo com leitura de imagem ou código EMV e boletos simulados por formato de 44, 47 ou 48 dígitos. A validação de boleto cobre formato e valor informado na simulação. |
| RF09 | Backoffice e administração no mesmo painel. Gestão de usuários com cadastro, edição, exclusão e ajuste de perfis. Contas e KYC, limites Pix, bloqueios, suporte e auditoria. |
| RF10 | Cadastro PF e PJ com validação matemática de CPF/CNPJ. PJ inclui razão social e representante legal. |
| RF11 | Corrente, Poupança, Salário e PJ com vínculo conforme o tipo de pessoa. Regras de movimentação próprias por modalidade além da segregação de cadastro permanecem parciais. |
| RF12 | Cartões físicos e virtuais de débito e crédito. A emissão virtual exige um físico ativo. |
| RF13 | O cliente pode bloquear preventivamente a própria conta. A administração pode reverter bloqueios por fraude. |
| RF14 | Ajuste de limites na web e API, dentro dos tetos aprovados. |
| RF15 | Geração de payload EMV e renderização da imagem do QR na web. |
| RF16 | Controllers, models, views, resources e services reutilizados. |
| RF17 | Sanctum na API e vínculo entre sessão web e token. A web invalida a sessão após 15 minutos sem atividade. |
| RF18 | Estado operacional separado de KYC. Transições verificadas e auditadas. O banco mantém as grafias `PENDENTE` e `RECUSADA` para KYC, usadas pelos dados e pela API existentes. |
| RF19 | Cada virtual tem conta própria de vínculo e referência a cartão físico da mesma modalidade e conta. |
| RF20 | A autorização da compra on-line no cartão físico respeita a configuração, que começa desativada. |
| RF21 | Consulta separada das recorrências já identificadas em `assinaturas_cartao`. A identificação automática de novas recorrências em compras permanece pendente. |
| RF22 | Triggers registram estado anterior, novo estado e diferenças sanitizadas. A trilha web oferece busca e detalhes. |
| RF23 | Limites por operação, total diário, faixa noturna e quantidade. Contas sem chave ativa seguem tetos reduzidos. |
| RF24 | Aviso de viagem por cartão, localidade e vigência. Utilizado no antifraude de compras simuladas com localidade informada. |
| RF25 | Agendamentos em PostgreSQL, com idempotência, tentativas, estados, lock e controle de próxima execução. |
| RF-SEG01 | Auditoria de requisições que chegam ao middleware da aplicação, com rota, IP, agente, data, ator quando disponível e projeção sanitizada do payload. Ativos servidos diretamente pelo servidor web e respostas anteriores ao middleware ficam fora dessa captura. |
| RF-SEG02 | A trilha central registra as mudanças nas tabelas de domínio. As tabelas de auditoria já existentes recebem novas projeções sanitizadas. |
| RF-SEG03 | Regras de banco preservam a trilha contra atualização, exclusão e truncamento em operação normal. |
| RF-SEG04 | Acesso, consultas e movimentações são classificados na trilha. As telas de auditoria mostram metadados e diferenças sanitizadas. |

## Regras de negócio

| ID | Cobertura |
| --- | --- |
| RN02, RN03 | Saldo derivado e lançamentos balanceados no mesmo commit. |
| RN18, RN32 | A sessão suporta 2FA. Os diagramas de retirada preveem um fluxo próprio de saque com 2FA por risco. A implementação desse fluxo e a obtenção de sinais confiáveis de dispositivo e localização para o Pix continuam pendentes. |
| RN22, RN23 | Remoção lógica de itens com motivo e histórico. A cobrança considera dias ativos na competência em centavos. Novas competências são geradas pelo scheduler. |
| RN27–RN30 | Conferência do saldo disponível e da política por operação, dia, quantidade e janela noturna. |
| RN31, RN44 | Aviso aplicado exclusivamente ao cartão associado, durante a vigência e na localidade informada na simulação. |
| RN33, RN38–RN40 | Dependência do cartão físico, controle de compras on-line e separação das recorrências de cartão. |
| RN34, RN37 | Bloqueio preventivo e por fraude. Conta encerrada ou sem aprovação fica indisponível para débito. |
| RN35, RN36 | Dígitos verificadores de CPF/CNPJ e CRC16 do QR Pix. |
| RN41 | Triggers e auditoria HTTP com contexto de requisição. Histórico anterior preservado conforme as regras append-only. |
| RN42, RN43 | Tetos reduzidos sem chave e consumo de limite protegido por transação. O saldo e o limite dos agendamentos são verificados no momento da execução. |
| RN45 | Worker executa apenas agendamentos elegíveis e controla tentativas, cancelamento, expiração e repetição mensal. |

Os IDs legados e as regras sem definição textual própria mantêm o mapeamento registrado no documento de requisitos original. [Rastreabilidade](https://github.com/EduCafilista/Fluxo-Bank/blob/db484a6/Documentacao/Levantamento-Requisitos-Final.md)

## Requisitos não funcionais

| ID | Evidência ou condição |
| --- | --- |
| RNF01–RNF04 | Metas de latência, concorrência e inicialização mobile exigem teste de carga ou dispositivo real. |
| RNF05 | Páginas de extrato com no máximo 50 lançamentos. |
| RNF06 | TLS depende da hospedagem. A URL de produção usa esquema HTTPS na aplicação. |
| RNF07–RNF13 | bcrypt com custo padrão 12, máscara de documentos, CSRF, autorização por papel e titular, tokens de acesso de 60 minutos com refresh rotativo, sessão web por inatividade e limite de 60 requisições por minuto nas rotas autenticadas. A verificação integral contra OWASP exige auditoria dedicada. |
| RNF14 | Exclusão lógica com anonimização do cadastro e revogação das sessões. Dados históricos e snapshots de versões anteriores requerem política de retenção e revisão de conformidade. |
| RNF15–RNF18 | Transações, partidas dobradas, idempotência e valores persistidos e calculados como inteiros em centavos. O esquema usa `bigint` em centavos, representação exata diferente do tipo `numeric(18,2)` citado na especificação. |
| RNF19 | Procedimento de backup e restauração no README. Restauração verificada em banco separado. A recorrência semanal depende de configuração no ambiente de hospedagem. |
| RNF20 | Disponibilidade mensal exige monitoramento na hospedagem. |
| RNF21–RNF23 | Layout verificado em Chromium de 320 a 1920 px nas telas revisadas. Pagamentos e transferências usam revisão ou confirmação. As novas mensagens de negócio são em português. |
| RNF24–RNF25 | Tempo de uso com participantes e conformidade integral WCAG AA ainda exigem validação. O layout recebeu ajustes de foco, contraste e navegação por teclado. |
| RNF26–RNF30 | Código PHP revisado com Pint, testes no PostgreSQL, migrations, configuração por ambiente e OpenAPI v1. A meta de 60% deve ser verificada com relatório de cobertura. |
| RNF31–RNF32.1 | O backend roda em Linux. Deploy automático no Render e compatibilidade de Android e iOS exigem os respectivos ambientes. |
| RNF33–RNF36 | Laravel com PHP 8.4, Blade com Tailwind e PostgreSQL 16. React Native faz parte do escopo mobile. |
| RNF37–RNF39 | Hospedagem Render, histórico no GitHub e prazos acadêmicos dependem da gestão do projeto. |
