# 4.2 — Levantamento de Requisitos — Versão Consolidada 2.0

**Projeto:** Fluxo — Sistema Bancário Digital
**Disciplina:** Projetos de Software II — Prof. Marcus Colantoni (UNIUBE)
**Grupo:** 1 · **Checkpoint 2** — apresentação em 17/09
**Repositório:** https://github.com/AlfredoVentura/Fluxo

---

## 1. Convenções

- **RF** — Requisito Funcional · **RNF** — Requisito Não Funcional · **RN** — Regra de Negócio · **UC** — Caso de Uso.
- **Prioridade (MoSCoW):** `Must` (essencial), `Should` (importante), `Could` (desejável), `Won't` (fora desta versão).
- **Origem:** entrevista simulada com o usuário-tipo, análise de concorrentes (Nubank, Inter, C6), análise documental da disciplina e sessões de cenários de risco.

## 2. Personas

| Persona | Perfil | Necessidades principais | Requisitos atendidos |
|---|---|---|---|
| **Ana, 24 anos — correntista mobile-first** | Estudante, usa o celular para tudo, renda de R$ 1.800/mês | Abrir conta sem burocracia, transferir por Pix, controlar limites, pagar boletos, sacar quando precisa | RF01–RF26, RF53–RF64 |
| **Carlos, 38 anos — pequeno empreendedor** | Tem loja, recebe por Pix e boleto, contrata plano com benefícios | Emitir cobranças, acompanhar entradas, contratar e **retirar itens** do plano conforme o uso | RF27–RF42, RF65–RF72 |
| **Juliana, 32 anos — analista de backoffice** | Funcionária interna, aprova cadastros e contestações | Aprovar KYC com rapidez, ver histórico do cliente, agir com rastreabilidade | RF04, RF35, RF43–RF52 |
| **Rafael, 45 anos — auditor** | Consulta dados, nunca altera | Filtrar e exportar a trilha, comprovar que nada foi adulterado | RF45, RF46, RF48, RF73–RF76 |

## 3. Requisitos Funcionais

## Módulo 1 — Acesso e Gestão de Conta

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF01 | O sistema deve permitir que um visitante se cadastre informando nome completo, CPF, data de nascimento, e-mail, telefone e senha. | Must | UC01 |
| RF02 | O sistema deve validar o CPF pelo algoritmo dos dígitos verificadores e recusar CPFs já cadastrados. | Must | UC01 |
| RF03 | O sistema deve submeter o cadastro a uma verificação de identidade (KYC) simulada antes de liberar a conta. | Must | UC02 |
| RF04 | O sistema deve permitir que o analista de backoffice aprove ou reprove a abertura de conta, registrando o motivo. | Must | UC06 |
| RF05 | O sistema deve criar automaticamente uma conta corrente (agência e número únicos) quando o cadastro for aprovado. | Must | UC06 |
| RF06 | O sistema deve autenticar o usuário por e-mail e senha, com sessão na web e token na API mobile. | Must | UC03 |
| RF07 | O sistema deve permitir ativar a autenticação em dois fatores (2FA) por código enviado ao e-mail. | Should | UC04 |
| RF08 | O sistema deve bloquear o acesso após 5 tentativas de login incorretas em 15 minutos. | Must | UC03 |
| RF09 | O sistema deve permitir recuperar a senha por link com token de uso único e validade de 30 minutos. | Must | UC05 |
| RF10 | O sistema deve permitir ao cliente visualizar e alterar seus dados cadastrais (telefone, e-mail e endereço). | Must | UC07 |
| RF11 | O sistema deve exigir confirmação por senha para alterações em dados sensíveis. | Should | UC07 |
| RF12 | O sistema deve permitir o encerramento da conta apenas se o saldo for zero e não houver pendências. | Should | UC08 |

## Módulo 2 — Movimentações Financeiras

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF13 | O sistema deve exibir o saldo atual da conta, calculado a partir dos lançamentos. | Must | UC09 |
| RF14 | O sistema deve exibir o extrato com data, descrição, contraparte, tipo e valor, ordenado do mais recente para o mais antigo. | Must | UC09 |
| RF15 | O sistema deve permitir filtrar o extrato por período, tipo de operação e faixa de valor. | Should | UC09 |
| RF16 | O sistema deve permitir ao cliente registrar chaves Pix nos tipos CPF, e-mail, telefone e aleatória. | Must | UC10 |
| RF17 | O sistema deve impedir o registro de uma chave Pix já vinculada a outra conta. | Must | UC10 |
| RF18 | O sistema deve permitir realizar transferência Pix informando a chave do favorecido e o valor. | Must | UC11 |
| RF19 | O sistema deve exibir os dados do favorecido para confirmação antes de efetivar a transferência. | Must | UC11 |
| RF20 | O sistema deve submeter toda transferência à análise de risco antes da efetivação. | Must | UC12 |
| RF21 | O sistema deve permitir transferência interna entre contas Fluxo com liquidação imediata. | Must | UC13 |
| RF22 | O sistema deve permitir agendar transferências para data futura, com execução automática. | Should | UC14 |
| RF23 | O sistema deve permitir cancelar um agendamento até o dia anterior à execução. | Should | UC14 |
| RF24 | O sistema deve permitir depósito simulado via Pix ou boleto, creditando o valor na conta. | Must | UC15 |
| RF25 | O sistema deve gerar comprovante em PDF de qualquer transação efetivada, com identificador único. | Should | UC16 |
| RF26 | O sistema deve permitir salvar favorecidos frequentes para reuso. | Could | UC13 |

## Módulo 3 — Cartões

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF27 | O sistema deve permitir a emissão imediata de cartão virtual, com número, validade e CVV gerados. | Must | UC17 |
| RF28 | O sistema deve exibir o número do cartão mascarado, revelando-o apenas mediante reautenticação. | Must | UC17 |
| RF29 | O sistema deve permitir solicitar cartão físico e acompanhar o status (solicitado, produção, enviado, entregue). | Should | UC18 |
| RF30 | O sistema deve permitir bloquear e desbloquear cartões, de forma temporária ou definitiva. | Must | UC19 |
| RF31 | O sistema deve permitir ajustar o limite do cartão respeitando o teto aprovado para o cliente. | Should | UC20 |
| RF32 | O sistema deve processar autorizações de compra simuladas, verificando limite e status do cartão. | Should | UC21 |
| RF33 | O sistema deve manter a fatura do cartão com lançamentos, data de fechamento e vencimento. | Should | UC22 |
| RF34 | O sistema deve permitir contestar um lançamento da fatura informando o motivo. | Could | UC23 |
| RF35 | O sistema deve permitir ao backoffice analisar a contestação, deferindo ou indeferindo com justificativa. | Could | UC24 |

## Módulo 4 — Pagamentos e Cobranças

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF36 | O sistema deve permitir o pagamento de boleto por linha digitável ou código de barras. | Must | UC25 |
| RF37 | O sistema deve validar o dígito verificador do boleto e exibir beneficiário, vencimento e valor antes da confirmação. | Must | UC25 |
| RF38 | O sistema deve permitir o pagamento de cobrança Pix por leitura ou colagem de QR Code (payload EMV). | Should | UC26 |
| RF39 | O sistema deve permitir ao cliente emitir cobrança Pix com valor, descrição e prazo de validade. | Should | UC27 |
| RF40 | O sistema deve permitir agendar pagamentos recorrentes com periodicidade definida. | Could | UC28 |
| RF41 | O sistema deve creditar automaticamente o emissor quando uma cobrança for liquidada. | Should | UC29 |
| RF42 | O sistema deve notificar o cliente por e-mail e push a cada evento financeiro relevante. | Should | UC30 |

## Módulo 5 — Administração, Segurança e Suporte

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF43 | O sistema deve permitir ao administrador criar, editar e desativar usuários internos. | Must | UC31 |
| RF44 | O sistema deve implementar controle de acesso por perfil (cliente, suporte, backoffice, administrador, auditor). | Must | UC31 |
| RF45 | O sistema deve registrar em trilha de auditoria toda operação sensível, com autor, data/hora, IP e dados alterados. | Must | UC32 |
| RF46 | O sistema deve impedir a alteração ou exclusão de registros da trilha de auditoria. | Must | UC32 |
| RF47 | O sistema deve permitir ao administrador bloquear contas suspeitas, impedindo novas movimentações. | Must | UC33 |
| RF48 | O sistema deve permitir ao auditor consultar a trilha com filtros por autor, período e tipo de operação. | Should | UC34 |
| RF49 | O sistema deve permitir parametrizar tarifas, limites por operação e limites diários. | Should | UC35 |
| RF50 | O sistema deve gerar relatórios gerenciais de volume transacionado, contas ativas e chamados. | Should | UC36 |
| RF51 | O sistema deve permitir ao cliente abrir chamado de suporte com assunto, descrição e anexo. | Should | UC38 |
| RF52 | O sistema deve permitir ao analista responder o chamado e alterar seu status. | Should | UC37 |

## Módulo 6 — Verificação em dois fatores (2FA)

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF53 | O sistema deve permitir ao cliente ativar e desativar a verificação em dois fatores, exigindo a senha atual e um código válido para desativar. | Must | UC39 |
| RF54 | O sistema deve gerar código de verificação de 6 dígitos, de uso único e validade de 5 minutos (3 minutos para retirada), sempre vinculado a uma finalidade (login, retirada ou alteração sensível). | Must | UC40 |
| RF55 | O sistema deve armazenar apenas o hash (SHA-256) do código de verificação, nunca o código em texto claro. | Must | UC40 |
| RF56 | O sistema deve limitar a 3 tentativas de validação por código, invalidando o desafio e registrando o evento ao exceder. | Must | UC40 |
| RF57 | O sistema deve permitir reenviar o código respeitando intervalo mínimo de 60 segundos entre envios. | Should | UC40 |
| RF58 | O sistema deve permitir marcar dispositivos como confiáveis por 30 dias, dispensando o 2FA nesses dispositivos, com opção de revogação. | Could | UC41 |

## Módulo 7 — Retirada (saque)

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF59 | O sistema deve permitir ao cliente solicitar retirada informando conta de origem, valor e canal (caixa parceiro, agência ou transferência). | Must | UC42 |
| RF60 | O sistema deve exigir verificação em dois fatores quando o valor for superior a 30% do limite diário, o canal for inédito, o dispositivo não for confiável ou a operação ocorrer entre 20h e 6h. | Must | UC43 |
| RF61 | O sistema deve verificar saldo disponível e limites antes de liberar a retirada, recusando a operação quando insuficiente. | Must | UC42 |
| RF62 | O sistema deve gerar um código de retirada de uso único, com validade de 30 minutos, após a liberação da operação. | Must | UC43 |
| RF63 | O sistema deve permitir cancelar uma retirada enquanto o código não for utilizado, estornando o valor por lançamento inverso. | Should | UC44 |
| RF64 | O sistema deve registrar na trilha de auditoria a solicitação, o desafio de 2FA, a liberação, a utilização e o cancelamento da retirada. | Must | UC43 |

## Módulo 8 — Assinatura e planos

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF65 | O sistema deve permitir ao cliente contratar um plano de assinatura entre os planos disponíveis, definindo o dia do vencimento. | Must | UC45 |
| RF66 | O sistema deve gerar automaticamente a cobrança mensal da assinatura no dia de vencimento escolhido. | Must | UC48 |
| RF67 | O sistema deve permitir fazer upgrade e downgrade de plano, com efeito no ciclo seguinte, preservando a vigência anterior. | Should | UC47 |
| RF68 | O sistema deve permitir incluir e retirar itens da assinatura, registrando data e motivo em cada movimentação. | Must | UC46 |
| RF69 | O sistema deve manter o histórico dos itens retirados, sem exclusão física do registro. | Must | UC46 |
| RF70 | O sistema deve recalcular o valor da cobrança proporcionalmente quando houver inclusão ou retirada de item no meio do ciclo. | Should | UC48 |
| RF71 | O sistema deve suspender a assinatura quando a cobrança não for paga após 5 dias do vencimento, bloqueando os itens até a regularização. | Should | UC48 |
| RF72 | O sistema deve permitir cancelar a assinatura a qualquer momento, com efeito ao final do ciclo já pago. | Must | UC47 |

## Módulo 9 — Trilha de auditoria ampliada

| ID | Requisito | Prioridade | Casos de uso |
|---|---|---|---|
| RF73 | O sistema deve registrar em trilha toda tentativa de autenticação, bem-sucedida ou não, com IP, dispositivo e resultado. | Must | UC39, UC40 |
| RF74 | O sistema deve registrar a inclusão, a alteração e a retirada de itens da assinatura, com o valor anterior e o novo. | Must | UC46 |
| RF75 | O sistema deve permitir ao auditor exportar o resultado da consulta da trilha em CSV. | Should | UC34 |
| RF76 | O sistema deve encadear os registros da trilha por hash SHA-256 do registro anterior, permitindo detectar adulteração. | Should | UC34 |

**Resumo da versão 2.0:** 76 requisitos funcionais — 24 `Must`, 12 `Should`, 4 `Could` nos módulos novos; 52 requisitos (28 `Must`, 20 `Should`, 4 `Could`) nos módulos 1 a 5.
## 4. Histórias de usuário e critérios de aceite (módulos novos)

### Épico E6 — Verificação em dois fatores

| ID | História | Critérios de aceite |
|---|---|---|
| US-53 | **Como** cliente **quero** ativar o 2FA **para** proteger minha conta mesmo que descubram minha senha. | Dado que estou autenticado, quando ativo o 2FA, então o sistema envia um código de confirmação e passa a exigir o segundo fator nos próximos logins. |
| US-54 | **Como** cliente **quero** receber um código de 6 dígitos por e-mail **para** concluir o login. | Dado credenciais válidas e 2FA ativo, quando informo o código correto em até 5 minutos, então recebo o token de acesso; se o código expirar, o desafio é invalidado. |
| US-56 | **Como** cliente **quero** que o código seja invalidado após 3 erros **para** impedir tentativa forçada. | Dado um desafio ativo, quando erro o código pela terceira vez, então o desafio é cancelado, o evento é registrado na trilha e preciso solicitar novo código. |
| US-58 | **Como** cliente **quero** marcar meu celular como confiável **para** não digitar o código em todo acesso. | Dado que marquei o dispositivo, quando faço login por ele em até 30 dias, então o 2FA é dispensado; posso revogar o dispositivo a qualquer momento. |

### Épico E7 — Retirada (saque)

| ID | História | Critérios de aceite |
|---|---|---|
| US-59 | **Como** cliente **quero** solicitar uma retirada **para** sacar dinheiro em um caixa parceiro. | Dado saldo suficiente e conta ativa, quando solicito a retirada, então o sistema verifica saldo, limites e se a operação exige 2FA. |
| US-60 | **Como** cliente **quero** que retiradas altas exijam confirmação extra **para** evitar saque indevido. | Dado um pedido acima de 30% do meu limite diário, então o sistema exige o código de verificação antes de liberar o valor. |
| US-62 | **Como** cliente **quero** receber um código de retirada **para** apresentar no caixa parceiro. | Dado que a retirada foi liberada, então recebo um código de 6 dígitos válido por 30 minutos e de uso único. |
| US-63 | **Como** cliente **quero** cancelar uma retirada não utilizada **para** manter o dinheiro na conta. | Dado que o código não foi utilizado, quando cancelo, então o valor é estornado por lançamento inverso e o evento é registrado na trilha. |

### Épico E8 — Assinatura e retirada de itens

| ID | História | Critérios de aceite |
|---|---|---|
| US-65 | **Como** cliente **quero** contratar um plano **para** pagar menos tarifas. | Dado um plano ativo, quando confirmo a contratação, então a assinatura é criada com vigência imediata e a primeira cobrança é gerada. |
| US-68 | **Como** cliente **quero** retirar um item da minha assinatura **para** parar de pagar por algo que não uso. | Dado um item ativo, quando o retiro informando o motivo, então o registro **não é apagado**: recebe data e motivo de exclusão, e o histórico permanece consultável. |
| US-70 | **Como** cliente **quero** que a cobrança seja proporcional **para** pagar apenas pelos dias de uso. | Dado que incluí ou retirei um item no meio do ciclo, então a próxima cobrança é recalculada proporcionalmente. |
| US-72 | **Como** cliente **quero** cancelar a assinatura **para** encerrar o serviço quando quiser. | Dado que solicito o cancelamento, então a assinatura permanece ativa até o fim do ciclo já pago e é encerrada depois, com registro na trilha. |

### Épico E9 — Trilha de auditoria

| ID | História | Critérios de aceite |
|---|---|---|
| US-73 | **Como** administrador **quero** registrar todas as tentativas de login **para** identificar ataques. | Dado um login com senha incorreta, então um registro `LOGIN_FALHOU` é gravado com IP, dispositivo e horário. |
| US-75 | **Como** auditor **quero** exportar a trilha em CSV **para** analisar os dados fora do sistema. | Dado um filtro aplicado, quando exporto, então o arquivo contém exatamente os registros do filtro, com os mesmos campos da tela. |
| US-76 | **Como** auditor **quero** garantir que a trilha não foi adulterada **para** confiar nela como evidência. | Dado que nenhum registro foi alterado, quando a verificação diária roda, então o hash encadeado é válido em 100% dos registros; caso contrário, um alerta `TRILHA_ADULTERADA` é gerado. |

## 5. Requisitos Não Funcionais

Classificados pela norma **ISO/IEC 25010** (qualidade de produto de software).

Classificados pela norma **ISO/IEC 25010** (qualidade de produto de software).

## RNF — Desempenho e eficiência

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF01 | As telas de saldo e extrato devem responder em até 2 segundos no percentil 95. | Teste de carga com 50 usuários simultâneos. |
| RNF02 | As operações de escrita da API (transferência, pagamento) devem responder em até 3 segundos. | Medição de tempo de resposta no log. |
| RNF03 | O sistema deve suportar 100 usuários simultâneos sem degradação perceptível. | Teste com k6 ou JMeter. |
| RNF04 | O aplicativo mobile deve iniciar em até 4 segundos em um aparelho Android de entrada. | Medição em dispositivo real. |
| RNF05 | Consultas de extrato devem usar paginação de no máximo 50 registros por página. | Inspeção de código e da API. |

## RNF — Segurança

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF06 | Toda comunicação entre cliente e servidor deve usar HTTPS (TLS 1.2+). | Verificação do certificado no Render. |
| RNF07 | Senhas devem ser armazenadas com hash bcrypt (custo ≥ 12), nunca em texto claro. | Inspeção da tabela `usuarios`. |
| RNF08 | Dados sensíveis (CPF, número de cartão) devem ser exibidos mascarados por padrão. | Teste de interface. |
| RNF09 | O sistema deve estar protegido contra as vulnerabilidades do OWASP Top 10 (SQL injection, XSS, CSRF, IDOR). | Checklist OWASP + uso de Eloquent, Blade escaping e tokens CSRF. |
| RNF10 | Tokens de API devem expirar em 60 minutos, com renovação por refresh token. | Teste de expiração. |
| RNF11 | A sessão web deve encerrar após 15 minutos de inatividade. | Teste manual. |
| RNF12 | O sistema deve aplicar rate limiting de 60 requisições por minuto por usuário. | Teste de estresse no endpoint. |
| RNF13 | Nenhum dado pessoal deve ser gravado em logs de aplicação. | Revisão de código e amostragem de logs. |
| RNF14 | O sistema deve atender aos princípios da LGPD: finalidade, minimização, consentimento e direito de exclusão. | Checklist de conformidade. |

## RNF — Confiabilidade e integridade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF15 | Toda movimentação financeira deve ocorrer dentro de uma transação ACID; falha parcial implica rollback total. | Teste de falha induzida no meio da operação. |
| RNF16 | A soma dos lançamentos de uma transação deve ser sempre zero (partidas dobradas). | Teste automatizado sobre o razão. |
| RNF17 | Requisições de transação devem ser idempotentes por chave de idempotência, evitando duplicidade em reenvios. | Teste de reenvio da mesma requisição. |
| RNF18 | Valores monetários devem usar `numeric(18,2)` no banco e inteiros em centavos na aplicação — nunca ponto flutuante. | Inspeção das migrations e do código. |
| RNF19 | O banco de dados deve ter backup semanal, com procedimento de restauração documentado e testado. | Evidência do dump e teste de restore. |
| RNF20 | O sistema deve ter disponibilidade mensal ≥ 95%, considerando as limitações do plano gratuito. | Monitoramento por uptime checker. |

## RNF — Usabilidade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF21 | Interface responsiva, funcional de 320 px (celular) a 1920 px (desktop). | Teste em três resoluções. |
| RNF22 | Toda operação financeira deve exigir uma tela de confirmação antes da efetivação. | Teste de fluxo. |
| RNF23 | Mensagens de erro devem ser claras, em português, indicando como corrigir o problema. | Revisão do catálogo de mensagens. |
| RNF24 | Um usuário novo deve concluir uma transferência sem treinamento em até 3 minutos. | Teste com 5 usuários reais. |
| RNF25 | O sistema deve atender ao nível AA da WCAG 2.1 em contraste e navegação por teclado. | Auditoria com Lighthouse. |

## RNF — Manutenibilidade e portabilidade

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF26 | O código deve seguir o padrão PSR-12 (PHP) e as convenções do Dart/Flutter. | Análise com Laravel Pint e `dart analyze`. |
| RNF27 | O backend deve ter cobertura de testes automatizados ≥ 60% nas regras de negócio, desenvolvidas com TDD. | Relatório de cobertura do PHPUnit. |
| RNF28 | Toda alteração de esquema deve ser feita por migration versionada. | Histórico do diretório `database/migrations`. |
| RNF29 | Configurações sensíveis devem residir em variáveis de ambiente, nunca no repositório. | Inspeção do `.gitignore` e do `.env.example`. |
| RNF30 | A API REST deve ser versionada (`/api/v1`) e documentada em OpenAPI/Swagger. | Acesso à documentação publicada. |
| RNF31 | O sistema deve rodar em Linux e ser implantável por deploy automático a partir do GitHub. | Deploy funcional no Render. |
| RNF32 | O aplicativo mobile deve suportar Android 8.0 (API 26) ou superior. | Configuração do `build.gradle`. |
| RNF32.1 | O código Flutter deve manter compatibilidade com iOS 13+, mesmo que a compilação para iOS não seja demonstrada nesta versão. | Ausência de dependências exclusivas de Android no `pubspec.yaml`. |

## RNF — Restrições de projeto

| ID | Requisito |
|---|---|
| RNF33 | O backend deve ser desenvolvido em Laravel (PHP 8.2+). |
| RNF34 | O frontend web deve usar Blade com Tailwind CSS. |
| RNF35 | O aplicativo mobile deve ser desenvolvido em Flutter. |
| RNF36 | O banco de dados deve ser PostgreSQL. |
| RNF37 | A hospedagem deve ocorrer no Render, com plano gratuito. |
| RNF38 | O código deve ser versionado no GitHub, com commits semanais e acesso ao professor. |
| RNF39 | Todo o código deve ser desenvolvido durante o semestre, sem reaproveitamento de código legado. |

## RNF — Auditoria e verificação em dois fatores

| ID | Requisito | Métrica de verificação |
|---|---|---|
| RNF40 | O código de verificação em dois fatores deve ser gerado por gerador criptograficamente seguro (`random_int`) e armazenado apenas como hash SHA-256. | Inspeção do modelo `VerificacaoDoisFatores` |
| RNF41 | A gravação da trilha de auditoria não deve acrescentar mais de 100 ms ao tempo de resposta da operação de negócio. | Medição com e sem o job em fila |
| RNF42 | O sistema deve detectar adulteração da trilha pela verificação do hash encadeado, executada diariamente de forma agendada. | Teste de violação induzida |
| RNF43 | Nenhum dado sensível (senha, hash de senha, código 2FA, token de sessão ou CVV) deve constar no payload de auditoria. | Revisão do payload + teste automatizado |
| RNF44 | A trilha de auditoria deve reter os registros por 5 anos para operações financeiras. | Política de retenção documentada |

**Resumo:** 44 requisitos não funcionais em 7 categorias (39 da versão 1.0 + 5 novos).

## 6. Regras de Negócio

| ID | Regra |
|---|---|
| RN01 | Uma conta só é ativada após aprovação do KYC pelo backoffice. |
| RN02 | O saldo de uma conta é sempre a soma dos seus lançamentos — nunca um valor editável diretamente. |
| RN03 | Toda transação gera no mínimo dois lançamentos, cuja soma é zero (partidas dobradas). |
| RN04 | Não é permitido saldo negativo: transações sem saldo suficiente são rejeitadas (não há cheque especial nesta versão). |
| RN05 | O valor mínimo de uma transferência é R$ 0,01 e o máximo padrão é R$ 5.000,00 por operação. |
| RN06 | O limite diário padrão de transferências é R$ 10.000,00, parametrizável pelo administrador. |
| RN07 | Entre 20h e 6h, o limite por operação cai para R$ 1.000,00 (limite noturno). |
| RN08 | Um cliente pode ter no máximo 5 chaves Pix, sendo no máximo uma por tipo (exceto aleatória). |
| RN09 | Transferências para favorecido cadastrado há menos de 24 h passam por análise de risco reforçada. |
| RN10 | Transações efetivadas não podem ser excluídas; correções ocorrem por lançamento de estorno. |
| RN11 | Um cartão bloqueado não autoriza compras nem gera lançamentos. |
| RN12 | O limite total dos cartões de um cliente não pode ultrapassar o teto aprovado no cadastro. |
| RN13 | A contestação de um lançamento só pode ser aberta em até 90 dias após a data da compra. |
| RN14 | Uma cobrança Pix expira automaticamente após o prazo de validade definido pelo emissor. |
| RN15 | Contas com saldo diferente de zero não podem ser encerradas. |
| RN16 | Contas bloqueadas por suspeita de fraude não realizam movimentações até liberação pelo administrador. |
| RN17 | Todo acesso ou alteração a dados de terceiros por usuário interno é registrado na trilha de auditoria. |

---

## Regras de negócio — versão 2.0 (módulos novos)

| ID | Regra |
|---|---|
| RN18 | A retirada exige verificação em dois fatores quando o valor for superior a 30% do limite diário, o canal for inédito, o dispositivo não for confiável ou a operação ocorrer entre 20h e 6h. |
| RN19 | O limite diário de retirada é de R$ 2.000,00, parametrizável pelo administrador; entre 20h e 6h cai para R$ 500,00. |
| RN20 | O código de retirada é de uso único e expira em 30 minutos; ao expirar sem uso, o valor retorna à conta por estorno automático. |
| RN21 | Uma assinatura só pode ter um plano ativo por vez; a troca de plano encerra a vigência atual e inicia nova, preservando o histórico. |
| RN22 | A retirada de item da assinatura não exclui o registro: preenche `dataExclusao` e `motivoExclusao`, mantendo histórico, valor da fatura do ciclo e trilha de auditoria. |
| RN23 | A cobrança é gerada no dia do vencimento com base nos itens ativos naquela data; inclusões e retiradas no meio do ciclo geram cobrança proporcional. |
| RN24 | O cancelamento da assinatura tem efeito ao final do ciclo já pago; não há reembolso proporcional nesta versão. |
| RN25 | A assinatura é suspensa após 5 dias de atraso, bloqueando os itens até a regularização do pagamento. |
| RN26 | O código de verificação em dois fatores tem uso único, validade de 5 minutos (3 minutos para retirada) e no máximo 3 tentativas. |
| RN27 | Todo registro da trilha de auditoria é encadeado por hash SHA-256 e não pode ser alterado nem excluído; a correção de erro é feita por novo lançamento. |

**Resumo:** 27 regras de negócio (17 da versão 1.0 + 10 novas).
## 7. Matriz de rastreabilidade (versão 2.0)

| Módulo | Requisitos | Casos de uso | Diagramas |
|---|---|---|---|
| 1 — Acesso e Gestão de Conta | RF01–RF12 | UC01–UC08 | UCD-01, SEQ-01, ATV-01 |
| 2 — Movimentações Financeiras | RF13–RF26 | UC09–UC16 | UCD-03, SEQ-03, ATV-02 |
| 3 — Cartões | RF27–RF35 | UC17–UC24 | UCD-04 |
| 4 — Pagamentos e Cobranças | RF36–RF42 | UC25–UC30 | UCD-05, SEQ-05, ATV-04 |
| 5 — Administração, Segurança e Suporte | RF43–RF52 | UC31–UC38 | UCD-07, SEQ-06 |
| 6 — Verificação em dois fatores | RF53–RF58 | UC39–UC41 | SEQ-02, SEQ-04, ATV-03, CLS-02 |
| 7 — Retirada (saque) | RF59–RF64 | UC42–UC44 | SEQ-04, ATV-03, CLS-02 |
| 8 — Assinatura e planos | RF65–RF72 | UC45–UC48 | CLS-01, ATV-05 |
| 9 — Trilha de auditoria ampliada | RF73–RF76 | UC34, UC39–UC46 | SEQ-06, aud-01, CLS-01, CLS-02 |

### 7.1 Novos casos de uso incorporados ao diagrama de casos de uso

| UC | Nome | Ator principal |
|---|---|---|
| UC39 | Ativar ou desativar verificação em dois fatores | Cliente |
| UC40 | Validar código de verificação (login ou operação sensível) | Cliente |
| UC41 | Gerenciar dispositivos confiáveis | Cliente |
| UC42 | Solicitar retirada | Cliente |
| UC43 | Confirmar retirada com verificação em dois fatores | Cliente |
| UC44 | Cancelar retirada | Cliente |
| UC45 | Contratar assinatura | Cliente |
| UC46 | Gerenciar itens da assinatura (incluir e retirar) | Cliente |
| UC47 | Alterar ou cancelar plano | Cliente |
| UC48 | Acompanhar cobranças da assinatura | Cliente |

> Com a inclusão destes 10 casos de uso, o diagrama passa de **38 para 48 casos de uso**, mantendo os 13 atores já documentados.

## 8. Priorização MoSCoW consolidada

| Prioridade | Módulos 1–5 | Módulos 6–9 | Total |
|---|---|---:|---:|
| `Must` | 28 | 17 | **45** |
| `Should` | 20 | 6 | **26** |
| `Could` | 4 | 1 | **5** |
| **Total** | **52** | **24** | **76** |

## 9. Técnicas de levantamento utilizadas

1. **Análise de concorrentes** — Nubank, Inter e C6 Bank, para identificar o conjunto de funcionalidades esperado de um banco digital.
2. **Entrevista simulada** com o usuário-tipo (correntista de 18 a 45 anos, mobile-first).
3. **Análise documental** — material das aulas, requisitos da disciplina (mínimo de 8 entidades, duas interfaces, implantação em nuvem) e checkpoints.
4. **Brainstorming da equipe** com priorização MoSCoW.
5. **Prototipação em papel** das telas principais.
6. **Cenários de risco** — sessão específica para derivar os requisitos de 2FA e de retirada a partir de ameaças (roubo de senha, saque indevido, dispositivo desconhecido).
7. **Matriz de eventos auditáveis** — sessão para derivar RF73–RF76 e RN27 a partir da pergunta "o que um auditor precisaria provar?".

**Registro de alterações**

| Versão | Data | Alteração |
|---|---|---|
| 1.0 | 20/08/2026 | Levantamento inicial (Checkpoint 1): RF01–RF52, RNF01–RNF39, RN01–RN17 |
| 2.0 | 31/08/2026 | Consolidação: inclusão dos módulos de 2FA, Retirada, Assinatura e Auditoria ampliada (RF53–RF76, RNF40–RNF44, RN18–RN27), histórias de usuário com critérios de aceite, novos casos de uso UC39–UC48 e matriz de rastreabilidade |
