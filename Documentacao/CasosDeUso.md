# 4.6 - Documentação Detalhada dos Casos de Uso

Este documento contém a especificação dos casos de uso, descrevendo as interações entre os atores e o sistema em aderência estrita aos requisitos e diagramas estabelecidos.

## ACC-01 - Cadastrar-se na plataforma

**Objetivo:**
Permitir que um visitante informe dados para abertura de conta.

**Ator principal:**
Visitante

**Pré-condições:**
Visitante não possui conta na plataforma.

**Gatilho:**
Visitante submete formulário de cadastro.

**Fluxo principal:**
1. Visitante acessa tela de cadastro.
2. Informa dados básicos, como e-mail, senha e CPF/CNPJ.
3. Sistema valida matematicamente o CPF/CNPJ.
4. Sistema cria a conta com `status = PENDENTE` e `status_abertura = PENDENTE_KYC`.
5. Sistema submete os dados ao processo automatizado de KYC.

**Fluxos alternativos/exceções:**
- CPF/CNPJ inválido matematicamente: sistema recusa a entrada.
- Conta ou documento já existente: sistema não cria nova conta.

**Pós-condições:**
A conta passa pela análise de KYC e permanece sem movimentação até sua aprovação.

**Requisitos relacionados:**
RF01, RF10

**Regras de negócio relacionadas:**
RN01, RN35


## ACC-02 - Validar identidade (KYC)

**Objetivo:**
Realizar a verificação dos dados do solicitante para aprovação da conta.

**Ator principal:**
Sistema

**Pré-condições:**
Solicitação de cadastro recebida.

**Gatilho:**
Envio dos dados de cadastro.

**Fluxo principal:**
1. Sistema processa os dados recebidos pelo KYC e muda `status_abertura` para `EM_ANALISE`.
2. Sistema avalia o cadastro e decide entre aprovação e reprovação.
3. Em aprovação, grava `status_abertura = APROVADA` e `status = ATIVA` na mesma transação.
4. Em reprovação, grava `status_abertura = REPROVADA` e mantém a conta não operacional.
5. A transição é registrada em `logs_auditoria`.

**Pós-condições:**
O status da abertura e o status operacional da conta estão coerentes.

**Requisitos relacionados:**
RF01

**Regras de negócio relacionadas:**
RN01


## ACC-03 - Aprovar/reprovar abertura de conta

**Objetivo:**
Decidir a ativação da conta.

**Ator principal:**
Analista de Backoffice

**Pré-condições:**
O cliente passou pela análise de KYC.

**Gatilho:**
Acesso à fila de cadastros.

**Fluxo principal:**
1. Analista seleciona um cadastro com `status_abertura = EM_ANALISE`.
2. Visualiza a análise do KYC.
3. Confirma a aprovação ou reprovação da conta.
4. O sistema grava a transição de `status_abertura` e o novo `status` operacional.
5. A alteração é registrada na trilha de auditoria.

**Pós-condições:**
A conta fica `ATIVA` quando aprovada ou permanece não operacional quando recusada.

**Requisitos relacionados:**
RF01, RF09

**Regras de negócio relacionadas:**
RN01


## ACC-04 - Autenticar-se no sistema

**Objetivo:**
Realizar login com e-mail e senha.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente possui conta ativa.

**Gatilho:**
Cliente insere e-mail e senha na tela de login.

**Fluxo principal:**
1. Cliente submete credenciais.
2. Sistema verifica a senha (hash).
3. Sistema emite o token de autenticação e concede acesso.

**Pós-condições:**
Sessão válida estabelecida.

**Requisitos relacionados:**
RF02, RF17


## ACC-05 - Configurar 2FA

**Objetivo:**
Configurar o segundo fator de autenticação.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente acessa configurações de segurança.

**Fluxo principal:**
1. Cliente opta por ativar o 2FA.
2. Sistema solicita um código temporário.
3. Cliente informa o código.
4. Sistema ativa o 2FA para a conta.

**Pós-condições:**
A conta passa a suportar 2FA.

**Requisitos relacionados:**
RF02


## ACC-06 - Recuperar senha

**Objetivo:**
Redefinir a senha de acesso.

**Ator principal:**
Visitante

**Pré-condições:**
Visitante esqueceu sua senha.

**Gatilho:**
Ação de recuperação de senha.

**Fluxo principal:**
1. Visitante informa o e-mail cadastrado.
2. Sistema envia um meio de redefinição de senha.
3. Visitante informa nova senha.
4. Sistema atualiza a senha.

**Pós-condições:**
Senha é atualizada.

**Requisitos relacionados:**
RF02


## ACC-07 - Manter dados cadastrais

**Objetivo:**
Alterar informações de contato do cliente.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Acesso à edição de perfil.

**Fluxo principal:**
1. Cliente altera dados de contato.
2. Sistema atualiza o registro.

**Pós-condições:**
Dados atualizados.

**Requisitos relacionados:**
RF01


## ACC-08 - Encerrar conta

**Objetivo:**
Permitir o encerramento da conta e anonimização.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado com saldo zerado.

**Gatilho:**
Solicitação de encerramento.

**Fluxo principal:**
1. Sistema valida se não há pendências.
2. Cliente confirma encerramento.
3. Sistema encerra a conta e realiza a anonimização dos dados pessoais.

**Pós-condições:**
Conta inutilizável.

**Requisitos relacionados:**
RF01


## ACC-09 - Registrar aviso de viagem

**Objetivo:**
Informar período e localidade de viagem para regras de risco.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Acesso ao aviso de viagem.

**Fluxo principal:**
1. Cliente seleciona um cartão ativo, o destino, as localidades e o intervalo de datas.
2. Sistema confirma que o cartão pertence à conta autenticada.
3. Sistema registra o aviso em `avisos_viagem` com `cartao_id`, `conta_id` e `status = AGENDADO`.
4. Na data de início, o aviso passa a `ATIVO`; ao final, passa a `ENCERRADO`.

**Fluxos alternativos/exceções:**
- Cartão não pertence à conta: sistema recusa o cadastro.
- Data final anterior à inicial: sistema recusa o período.

**Pós-condições:**
A localização é considerada válida somente para o cartão vinculado, no período e localidades declarados.

**Requisitos relacionados:**
RF05, RF24

**Regras de negócio relacionadas:**
RN31, RN44


## ACC-10 - Bloquear/desbloquear a própria conta

**Objetivo:**
Ativar ou desativar o bloqueio preventivo da conta.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente aciona o autobloqueio.

**Fluxo principal:**
1. Cliente confirma a ação de bloqueio.
2. Sistema altera a flag de bloqueio na conta.
3. O sistema impede movimentos de débito.

**Pós-condições:**
Conta entra em modo bloqueado.

**Requisitos relacionados:**
RF13

**Regras de negócio relacionadas:**
RN34


## MOV-01 - Consultar saldo e extrato

**Objetivo:**
Visualizar o saldo e os lançamentos contábeis.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente abre a visão de saldo/extrato.

**Fluxo principal:**
1. Sistema calcula o saldo dinamicamente somando os lançamentos contábeis.
2. Sistema exibe a lista de lançamentos e o saldo.

**Pós-condições:**
Extrato exibido.

**Requisitos relacionados:**
RF03

**Regras de negócio relacionadas:**
RN02, RN03


## MOV-02 - Registrar chave Pix

**Objetivo:**
Cadastrar chaves vinculadas à conta.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente cadastra uma chave Pix.

**Fluxo principal:**
1. Cliente informa a chave Pix desejada.
2. Sistema valida rigorosamente a duplicidade da chave na base.
3. Chave é registrada.

**Fluxos alternativos/exceções:**
- Chave já existente: sistema recusa o cadastro.

**Pós-condições:**
Chave ativa para recebimentos.

**Requisitos relacionados:**
RF04


## MOV-03 - Realizar transferência Pix

**Objetivo:**
Transferir valores usando Pix com pré-visualização.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente preenche dados de transferência.

**Fluxo principal:**
1. Cliente informa chave e valor.
2. Sistema exibe a tela de confirmação do favorecido.
3. Cliente confirma.
4. Sistema verifica o saldo disponível.
5. Sistema verifica a chave Pix ativa da conta de origem e seleciona o limite aplicável.
6. Sistema reserva o consumo diário da conta na janela diurna/noturna.
7. Sistema submete os dados à análise de risco.
8. Sistema efetua a transferência e converte a reserva em uso.

**Fluxos alternativos/exceções:**
- Saldo disponível insuficiente: operação não avança.
- Sem chave Pix ativa: aplicar `limite_sem_chave_*`; se o teto for zero ou excedido, operação é recusada.
- Limite de valor, quantidade ou janela excedido: operação é recusada sem duplicar o consumo.

**Pós-condições:**
Transferência realizada, ou reserva liberada em caso de falha/cancelamento.

**Requisitos relacionados:**
RF03, RF05, RF23

**Regras de negócio relacionadas:**
RN27, RN42, RN43, RN32


## MOV-04 - Analisar risco da transação

**Objetivo:**
Verificar limites, histórico, localidade e horário antes da efetivação.

**Ator principal:**
Sistema

**Pré-condições:**
Transação submetida.

**Gatilho:**
Efetivação de transferência ou pagamento.

**Fluxo principal:**
1. Sistema verifica se existe chave Pix `ATIVA` na conta de origem.
2. Sistema escolhe `DIURNA` ou `NOTURNA` conforme o horário local.
3. Sistema verifica se valor, quantidade e reservas respeitam `limites_pix` e `consumos_limites_pix`.
4. Sem chave ativa, aplica os campos `limite_sem_chave_por_transacao` e `limite_sem_chave_diario`.
5. Sistema verifica a divergência de localidade contra o aviso de viagem do cartão utilizado.
6. Se o risco for elevado, o sistema exige verificação via 2FA ou retém a operação.

**Pós-condições:**
Operação é aprovada, retida ou recusada com a reserva liberada.

**Requisitos relacionados:**
RF05, RF23, RF24

**Regras de negócio relacionadas:**
RN28, RN29, RN30, RN31, RN32, RN42, RN43, RN44


## MOV-05 - Transferir entre contas Fluxo

**Objetivo:**
Efetuar uma transferência interna.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Cliente opta por transferência interna.

**Fluxo principal:**
1. Cliente informa a conta de destino e o valor.
2. Sistema confirma dados do favorecido na tela de confirmação.
3. Cliente confirma a operação.
4. Sistema insere lançamentos contábeis de débito e crédito.

**Pós-condições:**
Transferência efetuada.

**Requisitos relacionados:**
RF03, RF05

**Regras de negócio relacionadas:**
RN02, RN03


## MOV-06 - Agendar transferência/pagamento

**Objetivo:**
Programar uma operação para data futura.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Opção de agendamento selecionada.

**Fluxo principal:**
1. Cliente informa a data/hora futura, a operação e o payload.
2. Sistema cria `agendamentos` com `status = PENDENTE_VALIDACAO` e uma chave de idempotência.
3. Após validar saldo, limites e dados do favorecido, muda o registro para `AGENDADO`.
4. Sistema gera comprovante único de agendamento.
5. Worker captura o registro com lock, muda para `EM_PROCESSAMENTO` e executa a transação uma única vez.
6. Em sucesso grava `transacao_id`, `executado_em` e `status = EXECUTADO`; em falha grava tentativas e motivo.

**Fluxos alternativos/exceções:**
- Cliente cancela antes da execução: `status = CANCELADO`.
- Data/hora vencida sem possibilidade de execução: `status = EXPIRADO`.
- Falha recuperável: permanece/retorna a `AGENDADO` até o limite de tentativas.

**Pós-condições:**
Operação programada com status rastreável.

**Requisitos relacionados:**
RF06, RF25

**Regras de negócio relacionadas:**
RN45


## MOV-07 - Depositar via Pix/boleto

**Objetivo:**
Creditar saldo na conta.

**Ator principal:**
Sistema

**Pré-condições:**
Conta ativa.

**Gatilho:**
Recebimento de valor.

**Fluxo principal:**
1. Sistema recebe a confirmação do depósito.
2. Sistema insere lançamento contábil a crédito na conta do cliente.

**Pós-condições:**
Saldo do cliente recebe acréscimo.

**Requisitos relacionados:**
RF03

**Regras de negócio relacionadas:**
RN02, RN03


## MOV-08 - Exportar comprovante (PDF)

**Objetivo:**
Gerar documento da transação em PDF.

**Ator principal:**
Cliente

**Pré-condições:**
Transação executada ou agendada.

**Gatilho:**
Solicitação do comprovante.

**Fluxo principal:**
1. Sistema recupera os dados da transação.
2. Sistema gera um comprovante exclusivo em PDF.
3. Arquivo PDF é disponibilizado ao cliente.

**Pós-condições:**
PDF gerado.

**Requisitos relacionados:**
RF06


## MOV-09 - Configurar limites do Pix

**Objetivo:**
Ajustar tetos transacionais da conta dentro do teto pré-aprovado.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado.

**Gatilho:**
Acesso às configurações de limite.

**Fluxo principal:**
1. Cliente visualiza limites diurnos, noturnos, por quantidade e os limites aplicáveis sem chave.
2. Cliente solicita alteração de limites.
3. Sistema verifica se os valores respeitam o teto máximo da instituição e não reduzem abaixo do consumo reservado.
4. Limites são atualizados em `limites_pix`, com vigência e auditoria.

**Pós-condições:**
Limites salvos e prontos para seleção por horário/chave ativa.

**Requisitos relacionados:**
RF14, RF23

**Regras de negócio relacionadas:**
RN28, RN29, RN30, RN42, RN43


## CRT-01 - Solicitar cartão físico (Débito ou Crédito)

**Objetivo:**
Iniciar a emissão do cartão físico.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente elegível.

**Gatilho:**
Solicitação de cartão físico.

**Fluxo principal:**
1. Cliente solicita a emissão do cartão físico.
2. Sistema registra a solicitação e providencia a emissão.
3. Cartão é criado em `cartoes` e `cartoes_fisicos`.
4. Sistema grava `permite_compras_online = false` por padrão; o cliente pode habilitar depois.

**Pós-condições:**
Cartão físico registrado com política de compras on-line explícita.

**Requisitos relacionados:**
RF07, RF20

**Regras de negócio relacionadas:**
RN39


## CRT-02 - Gerar cartão virtual a partir do físico

**Objetivo:**
Emitir cartão virtual.

**Ator principal:**
Cliente

**Pré-condições:**
Cartão físico da modalidade correspondente ativo.

**Gatilho:**
Solicitação de cartão virtual.

**Fluxo principal:**
1. Sistema verifica a existência de cartão físico `ATIVO` correspondente à mesma modalidade e conta.
2. Sistema cria uma nova instância em `cartoes` com o mesmo `conta_id`.
3. Sistema cria `cartoes_virtuais` apontando para o novo cartão e para `cartao_fisico_id`.
4. Cartão virtual é disponibilizado ao cliente.

**Fluxos alternativos/exceções:**
- Físico bloqueado, cancelado ou pertencente a outra conta: geração recusada.

**Pós-condições:**
Cartão virtual gerado, vinculado à conta e ao físico de origem.

**Requisitos relacionados:**
RF07, RF12, RF19

**Regras de negócio relacionadas:**
RN33, RN38


## CRT-03 - Bloquear/desbloquear cartão

**Objetivo:**
Alterar o status de bloqueio do cartão físico ou virtual.

**Ator principal:**
Cliente

**Pré-condições:**
Cartão emitido.

**Gatilho:**
Ação de bloqueio/desbloqueio.

**Fluxo principal:**
1. Cliente opta por bloquear ou desbloquear um cartão específico.
2. Sistema atualiza o status de bloqueio do cartão.
3. Compras passam a ser autorizadas ou negadas conforme o status do cartão.

**Pós-condições:**
Status do cartão atualizado.

**Requisitos relacionados:**
RF07


## CRT-04 - Ajustar limite do cartão

**Objetivo:**
Ajustar o limite de uso de cartão com base nos tetos pré-aprovados.

**Ator principal:**
Cliente

**Pré-condições:**
Cartão de crédito habilitado.

**Gatilho:**
Modificação de limites.

**Fluxo principal:**
1. Cliente ajusta o valor do limite desejado.
2. Sistema valida o novo limite frente ao teto concedido.
3. Limite do cartão é atualizado.

**Pós-condições:**
Limite vigente ajustado.

**Requisitos relacionados:**
RF07


## CRT-05 - Autorizar compra

**Objetivo:**
Validar uma transação de cartão.

**Ator principal:**
Sistema

**Pré-condições:**
Compra submetida.

**Gatilho:**
Recebimento da autorização.

**Fluxo principal:**
1. Sistema recebe os dados da compra associados ao cartão.
2. Verifica se o cartão está bloqueado e se há limite.
3. Se o canal for `ONLINE` e o cartão de origem for físico, verifica `permite_compras_online`.
4. Registra a decisão e a compra na trilha de auditoria.
5. Responde autorizando ou negando o pedido.

**Fluxos alternativos/exceções:**
- Flag on-line desligada: compra negada sem lançamento financeiro.
- Cartão bloqueado ou limite insuficiente: compra negada.

**Pós-condições:**
Transação validada e decisão auditável.

**Requisitos relacionados:**
RF07, RF20, RF22

**Regras de negócio relacionadas:**
RN39, RN41


## CRT-06 - Consultar fatura

**Objetivo:**
Exibir as despesas lançadas.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente possui cartão de crédito.

**Gatilho:**
Acesso à fatura.

**Fluxo principal:**
1. Sistema agrupa as compras da fatura.
2. Exibe o detalhamento das compras e totais.

**Pós-condições:**
Fatura exibida.

**Requisitos relacionados:**
RF07


## CRT-07 - Contestar lançamento

**Objetivo:**
Sinalizar uma transação não reconhecida na fatura.

**Ator principal:**
Cliente

**Pré-condições:**
Compra confirmada.

**Gatilho:**
Ação de contestar lançamento.

**Fluxo principal:**
1. Cliente seleciona a despesa e informa a contestação.
2. Sistema cria o registro para o Backoffice avaliar.

**Pós-condições:**
Contestação encaminhada.

**Requisitos relacionados:**
RF07


## CRT-08 - Tratar contestação

**Objetivo:**
Avaliar contestação enviada pelo cliente.

**Ator principal:**
Analista de Backoffice

**Pré-condições:**
Existe um pedido de contestação.

**Gatilho:**
Avaliação pelo backoffice.

**Fluxo principal:**
1. Analista visualiza o ticket de contestação.
2. Confirma ou rejeita a solicitação.

**Pós-condições:**
Contestação resolvida.

**Requisitos relacionados:**
RF07, RF09


## CRT-09 - Consultar assinaturas identificadas no cartão

**Objetivo:**
Listar as assinaturas recorrentes detectadas a partir das compras de um cartão.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente autenticado e cartão pertencente à conta.

**Gatilho:**
Cliente acessa a área de assinaturas do cartão.

**Fluxo principal:**
1. Cliente seleciona um cartão físico ou virtual.
2. Sistema consulta `assinaturas_cartao` filtrando por `cartao_id` e `conta_id`.
3. Sistema exibe estabelecimento, periodicidade, valor estimado, última/próxima cobrança e status.
4. O evento de consulta é registrado como `CONSULTA` na trilha.

**Fluxos alternativos/exceções:**
- Cartão de outra conta: sistema retorna `403` e não expõe dados.
- Nenhuma recorrência identificada: sistema exibe lista vazia, sem criar assinatura do Fluxo.

**Pós-condições:**
Consulta apresentada sem alterar transações ou a contratação de planos próprios.

**Requisitos relacionados:**
RF21, RF22

**Regras de negócio relacionadas:**
RN40, RN41


## ASS-01 - Contratar plano/assinatura

**Objetivo:**
Vincular um plano.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente apto a contratar plano.

**Gatilho:**
Adesão ao plano.

**Fluxo principal:**
1. Cliente seleciona o plano de assinatura.
2. Sistema associa a assinatura à conta com status ativo.

**Pós-condições:**
Plano contratado.

**Requisitos relacionados:**
Nenhum requisito específico identificado.


## ASS-02 - Incluir item na assinatura

**Objetivo:**
Adicionar itens extras à assinatura.

**Ator principal:**
Cliente

**Pré-condições:**
Assinatura ativa.

**Gatilho:**
Adição de item.

**Fluxo principal:**
1. Cliente seleciona um item da assinatura.
2. Sistema adiciona o item ao escopo da assinatura.

**Pós-condições:**
Item incluído.

**Requisitos relacionados:**
Nenhum requisito específico identificado.


## ASS-03 - Retirar item da assinatura

**Objetivo:**
Remover item sem apagar do histórico.

**Ator principal:**
Cliente

**Pré-condições:**
Assinatura possui itens.

**Gatilho:**
Exclusão do item.

**Fluxo principal:**
1. Cliente seleciona item para remover.
2. Sistema preenche a exclusão e o motivo no registro do item.

**Pós-condições:**
Item inativo na assinatura.

**Requisitos relacionados:**
Nenhum requisito específico identificado.

**Regras de negócio relacionadas:**
RN22


## ASS-04 - Trocar de plano (upgrade/downgrade)

**Objetivo:**
Modificar o pacote principal da assinatura.

**Ator principal:**
Cliente

**Pré-condições:**
Assinatura ativa.

**Gatilho:**
Troca de plano.

**Fluxo principal:**
1. Cliente seleciona o novo plano.
2. Sistema agenda a alteração.

**Pós-condições:**
Nova categoria definida.

**Requisitos relacionados:**
Nenhum requisito específico identificado.


## ASS-05 - Gerar cobrança do ciclo

**Objetivo:**
Gerar a cobrança do período de uso.

**Ator principal:**
Sistema

**Pré-condições:**
Fechamento do ciclo da assinatura.

**Gatilho:**
Geração das cobranças.

**Fluxo principal:**
1. Sistema lista as assinaturas ativas.
2. Gera o valor cobrado de forma proporcional aos itens vigentes no ciclo.
3. Salva a cobrança do ciclo.

**Pós-condições:**
Cobrança gerada.

**Requisitos relacionados:**
Nenhum requisito específico identificado.

**Regras de negócio relacionadas:**
RN23


## ASS-06 - Pagar fatura da assinatura

**Objetivo:**
Realizar o débito do valor.

**Ator principal:**
Sistema

**Pré-condições:**
Cobrança gerada.

**Gatilho:**
Execução de pagamentos.

**Fluxo principal:**
1. Sistema tenta debitar o valor da cobrança na conta do cliente.
2. Atualiza a cobrança se liquidada.

**Pós-condições:**
Cobrança paga.

**Requisitos relacionados:**
RF03


## ASS-07 - Cancelar assinatura

**Objetivo:**
Encerrar a assinatura.

**Ator principal:**
Cliente

**Pré-condições:**
Assinatura ativa.

**Gatilho:**
Cancelamento do plano.

**Fluxo principal:**
1. Cliente confirma o encerramento do plano.
2. Sistema registra o encerramento que ocorrerá após o ciclo atual.

**Pós-condições:**
Assinatura programada para cancelamento.

**Requisitos relacionados:**
Nenhum requisito específico identificado.

**Regras de negócio relacionadas:**
RN24


## ASS-08 - Suspender assinatura por atraso

**Objetivo:**
Suspender serviços por atraso de pagamento.

**Ator principal:**
Sistema

**Pré-condições:**
Cobrança com pendência.

**Gatilho:**
Verificação de atrasos.

**Fluxo principal:**
1. Sistema constata atraso.
2. Atualiza o status da assinatura correspondente suspendendo o serviço.

**Pós-condições:**
Assinatura suspensa.

**Requisitos relacionados:**
Nenhum requisito específico identificado.

**Regras de negócio relacionadas:**
RN25


## PAG-01 - Pagar boleto/código de barras

**Objetivo:**
Realizar o pagamento de cobrança com código de barras.

**Ator principal:**
Cliente

**Pré-condições:**
Cliente possui código de barras.

**Gatilho:**
Cliente preenche o código de barras.

**Fluxo principal:**
1. Cliente informa o código de barras ou linha digitável.
2. Sistema reconhece a cobrança.
3. Cliente confere e confirma o pagamento.
4. Sistema deduz o saldo e efetua o pagamento.

**Pós-condições:**
Pagamento efetuado.

**Requisitos relacionados:**
RF08


## PAG-02 - Pagar QR Code Pix

**Objetivo:**
Pagar a partir da leitura de QR Code Pix.

**Ator principal:**
Cliente

**Pré-condições:**
Possui QR Code.

**Gatilho:**
Leitura de QR Code Pix.

**Fluxo principal:**
1. Cliente informa os dados gerados pelo QR Code.
2. Sistema lê as informações atreladas.
3. Cliente confere a tela de confirmação.
4. Sistema realiza o pagamento e deduz do saldo.

**Pós-condições:**
Pagamento realizado.

**Requisitos relacionados:**
RF08, RF15


## PAG-03 - Gerar QR Code para recebimento de Pix

**Objetivo:**
Gerar imagem no padrão EMV de cobrança Pix.

**Ator principal:**
Cliente

**Pré-condições:**
Conta apta a recebimentos via Pix.

**Gatilho:**
Geração do QR Code.

**Fluxo principal:**
1. Cliente informa o valor a receber.
2. Sistema gera o payload da cobrança no padrão EMV.
3. Exibe o formato em tela.

**Pós-condições:**
QR Code gerado.

**Requisitos relacionados:**
RF08, RF15

**Regras de negócio relacionadas:**
RN36


## PAG-04 - Agendar pagamento recorrente

**Objetivo:**
Programar repetição do pagamento.

**Ator principal:**
Cliente

**Pré-condições:**
Pagamento realizado.

**Gatilho:**
Seleção da recorrência.

**Fluxo principal:**
1. Cliente indica a repetição e a data da próxima execução.
2. Sistema grava `agendamentos` com `recorrente = true`, `frequencia` e `status = AGENDADO`.
3. Cada ciclo cria uma transação idempotente e atualiza `proxima_execucao`.

**Pós-condições:**
Pagamentos futuros registrados com status individual por execução.

**Requisitos relacionados:**
RF08, RF25

**Regras de negócio relacionadas:**
RN45


## PAG-05 - Liquidar cobrança emitida

**Objetivo:**
Registrar a compensação da cobrança gerada.

**Ator principal:**
Sistema

**Pré-condições:**
Cobrança gerada aguardando liquidação.

**Gatilho:**
Aviso de liquidação.

**Fluxo principal:**
1. Sistema constata a liquidação da cobrança gerada.
2. Atualiza a situação da cobrança informando seu pagamento.

**Pós-condições:**
Cobrança liquidada.

**Requisitos relacionados:**
RF08, RF15


## PAG-06 - Visualizar comprovante de pagamento

**Objetivo:**
Exibir dados do pagamento efetuado.

**Ator principal:**
Cliente

**Pré-condições:**
Pagamento executado.

**Gatilho:**
Solicitação do histórico.

**Fluxo principal:**
1. Cliente solicita ver dados do pagamento.
2. Sistema exibe o detalhamento do pagamento.

**Pós-condições:**
Informações em tela.

**Requisitos relacionados:**
RF08


## ADM-01 - Gerenciar perfis de acesso

**Objetivo:**
Atribuir perfis do backoffice.

**Ator principal:**
Administrador

**Pré-condições:**
Acesso administrativo habilitado.

**Gatilho:**
Gerenciamento de acessos.

**Fluxo principal:**
1. Administrador gerencia as permissões atribuídas a um perfil.
2. Sistema atualiza as permissões de acesso associadas.

**Pós-condições:**
Permissões aplicadas.

**Requisitos relacionados:**
RF09


## ADM-02 - Registrar log de operação

**Objetivo:**
Registrar operação na trilha de log e banco de dados.

**Ator principal:**
Sistema

**Pré-condições:**
Execução de requisições ou alterações.

**Gatilho:**
Processamento de rotina.

**Fluxo principal:**
1. O middleware cria o contexto `request_id`, usuário, IP e User-Agent, mascarando senha, token e CVV.
2. Para `INSERT`, `UPDATE` e `DELETE`, os triggers PostgreSQL salvam `OLD`, `NEW` e `diff` sanitizados em `logs_auditoria`.
3. Para login/logout e leituras de saldo, extrato, fatura e auditoria, o `AuditoriaService` salva evento `ACESSO` ou `CONSULTA`.
4. O trigger append-only rejeita `UPDATE` e `DELETE` na própria trilha.

**Pós-condições:**
Registro de alteração ou consulta salvo em trilha imutável e correlacionado à requisição.

**Requisitos relacionados:**
RF-SEG01, RF-SEG02, RF-SEG03, RF-SEG04, RF22

**Regras de negócio relacionadas:**
RN41


## ADM-03 - Bloquear conta (fraude/decisão interna)

**Objetivo:**
Bloquear as movimentações da conta de um cliente.

**Ator principal:**
Analista de Backoffice

**Pré-condições:**
Conta passível de bloqueio.

**Gatilho:**
Decisão de bloqueio.

**Fluxo principal:**
1. Analista seleciona a conta do cliente.
2. Bloqueia a conta relatando motivo interno.
3. A conta perde acesso às opções de movimentações.

**Pós-condições:**
Conta retida.

**Requisitos relacionados:**
RF09


## ADM-04 - Atender chamado de suporte

**Objetivo:**
Prestar suporte aos tickets enviados.

**Ator principal:**
Analista de Suporte

**Pré-condições:**
Ticket aberto pelo cliente.

**Gatilho:**
Acesso ao painel.

**Fluxo principal:**
1. Analista avalia os detalhes da solicitação de suporte.
2. Informa a resposta do atendimento e realiza os ajustes cabíveis.
3. O ticket de suporte é concluído.

**Pós-condições:**
Chamado fechado.

**Requisitos relacionados:**
RF09


## ADM-05 - Consultar trilha de auditoria

**Objetivo:**
Acessar registros operacionais para auditoria.

**Ator principal:**
Auditor

**Pré-condições:**
Acesso de auditoria habilitado.

**Gatilho:**
Acesso às trilhas.

**Fluxo principal:**
1. Auditor seleciona os parâmetros desejados.
2. O sistema possibilita o acesso da consulta estritamente como somente leitura.
3. O acesso do auditor à visualização gera automaticamente um log de leitura na trilha correspondente.

**Pós-condições:**
Informações fornecidas e lidas.

**Requisitos relacionados:**
RF09, RF-SEG03

**Regras de negócio relacionadas:**
RN17


## ADM-06 - Desbloquear conta retida

**Objetivo:**
Restabelecer as atividades de uma conta.

**Ator principal:**
Analista de Backoffice

**Pré-condições:**
Conta em estado bloqueado.

**Gatilho:**
Aprovação de liberação.

**Fluxo principal:**
1. Analista seleciona a conta.
2. Libera as restrições registrando a deliberação.
3. Sistema volta a habilitar o estado operacional do cliente.

**Pós-condições:**
Conta ativada novamente.

**Requisitos relacionados:**
RF09


## ADM-07 - Gerar relatório de conciliação diária

**Objetivo:**
Obter informações financeiras consolidadas.

**Ator principal:**
Conciliador

**Pré-condições:**
Dados contábeis presentes.

**Gatilho:**
Geração do relatório.

**Fluxo principal:**
1. O sistema compila e consolida as contas.
2. Gera o informe considerando as premissas de saldo em partidas dobradas e a invariância das contas.
3. A conciliação é providenciada para verificação externa.

**Pós-condições:**
Relatório fornecido.

**Requisitos relacionados:**
RF09

**Regras de negócio relacionadas:**
RN02, RN03


## ADM-08 - Configurar limites institucionais

**Objetivo:**
Adaptar parâmetros de risco gerais.

**Ator principal:**
Gestor de Riscos

**Pré-condições:**
Acesso de gestão habilitado.

**Gatilho:**
Configuração no painel.

**Fluxo principal:**
1. Gestor informa os novos limites e configurações das categorias.
2. O sistema adapta internamente as variáveis que baseiam os tetos de perfil cliente.

**Pós-condições:**
Tetos reajustados no sistema.

**Requisitos relacionados:**
RF09
