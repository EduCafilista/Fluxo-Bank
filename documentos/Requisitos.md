# Especificação de Requisitos — Sistema Bancário Digital (Checkpoint 1)

---

## Parte I — Requisitos Funcionais

### Módulo 1 — Acesso e Gestão de Conta
| ID | Requisito | Prioridade | Casos de uso |
| :--- | :--- | :---: | :---: |
| **RF01** | O sistema deve permitir que um visitante se cadastre informando nome completo, CPF, data de nascimento, e-mail, telefone e senha. | Must | UC01 |
| **RF02** | O sistema deve validar o CPF pelo algoritmo dos dígitos verificadores e recusar CPFs já cadastrados. | Must | UC01 |
| **RF03** | O sistema deve submeter o cadastro a uma verificação de identidade (KYC) simulada antes de liberar a conta. | Must | UC02 |
| **RF04** | O sistema deve permitir que o analista de backoffice aprove ou reprove a abertura de conta, registrando o motivo. | Must | UC06 |
| **RF05** | O sistema deve criar automaticamente uma conta corrente (agência e número únicos) quando o cadastro for aprovado. | Must | UC06 |
| **RF06** | O sistema deve autenticar o usuário por e-mail e senha, com sessão na web e token na API mobile. | Must | UC03 |
| **RF07** | O sistema deve permitir ativar a autenticação em dois fatores (2FA) por código enviado ao e-mail. | Should | UC04 |
| **RF08** | O sistema deve bloquear o acesso após 5 tentativas de login incorretas em 15 minutos. | Must | UC03 |
| **RF09** | O sistema deve permitir recuperar a senha por link com token de uso único e validade de 30 minutos. | Must | UC05 |
| **RF10** | O sistema deve permitir ao cliente visualizar e alterar seus dados cadastrais (telefone, e-mail e endereço). | Must | UC07 |
| **RF11** | O sistema deve exigir confirmação por senha para alterações em dados sensíveis. | Should | UC07 |
| **RF12** | O sistema deve permitir o encerramento da conta apenas se o saldo for zero e não houver pendências. | Should | UC08 |

### Módulo 2 — Movimentações Financeiras
| ID | Requisito | Prioridade | Casos de uso |
| :--- | :--- | :---: | :---: |
| **RF13** | O sistema deve exibir o saldo atual da conta, calculado a partir dos lançamentos. | Must | UC09 |
| **RF14** | O sistema deve exibir o extrato com data, descrição, contraparte, tipo e valor, ordenado do mais recente para o mais antigo. | Must | UC09 |
| **RF15** | O sistema deve permitir filtrar o extrato por período, tipo de operação e faixa de valor. | Should | UC09 |
| **RF16** | O sistema deve permitir ao cliente registrar chaves Pix nos tipos CPF, e-mail, telefone e aleatória. | Must | UC10 |
| **RF17** | O sistema deve impedir o registro de uma chave Pix já vinculada a outra conta. | Must | UC10 |
| **RF18** | O sistema deve permitir realizar transferência Pix informando a chave do favorecido e o valor. | Must | UC11 |
| **RF19** | O sistema deve exibir os dados do favorecido para confirmação antes de efetivar a transferência. | Must | UC11 |
| **RF20** | O sistema deve submeter toda transferência à análise de risco antes da efetivação. | Must | UC12 |
| **RF21** | O sistema deve permitir transferência interna entre contas Fluxo com liquidação imediata. | Must | UC13 |
| **RF22** | O sistema deve permitir agendar transferências para data futura, com execução automática. | Should | UC14 |
| **RF23** | O sistema deve permitir cancelar um agendamento até o dia anterior à execução. | Should | UC14 |
| **RF24** | O sistema deve permitir depósito simulado via Pix ou boleto, creditando o valor na conta. | Must | UC15 |
| **RF25** | O sistema deve gerar comprovante em PDF de qualquer transação efetivada, com identificador único. | Should | UC16 |
| **RF26** | O sistema deve permitir salvar favorecidos frequentes para reuso. | Could | UC13 |

### Módulo 3 — Cartões
| ID | Requisito | Prioridade | Casos de uso |
| :--- | :--- | :---: | :---: |
| **RF27** | O sistema deve permitir a emissão imediata de cartão virtual, com número, validade e CVV gerados. | Must | UC17 |
| **RF28** | O sistema deve exibir o número do cartão mascarado, revelando-o apenas mediante reautenticação. | Must | UC17 |
| **RF29** | O sistema deve permitir solicitar cartão físico e acompanhar o status (solicitado, produção, enviado, entregue). | Should | UC18 |
| **RF30** | O sistema deve permitir bloquear e desbloquear cartões, de forma temporária ou definitiva. | Must | UC19 |
| **RF31** | O sistema deve permitir ajustar o limite do cartão respeitando o teto aprovado para o cliente. | Should | UC20 |
| **RF32** | O sistema deve processar autorizações de compra simuladas, verificando limite e status do cartão. | Should | UC21 |
| **RF33** | O sistema deve manter a fatura do cartão com lançamentos, data de fechamento e vencimento. | Should | UC22 |
| **RF34** | O sistema deve permitir contestar um lançamento da fatura informando o motivo. | Could | UC23 |
| **RF35** | O sistema deve permitir ao backoffice analisar a contestação, deferindo ou indeferindo com justificativa. | Could | UC24 |

### Módulo 4 — Pagamentos e Cobranças
| ID | Requisito | Prioridade | Casos de uso |
| :--- | :--- | :---: | :---: |
| **RF36** | O sistema deve permitir o pagamento de boleto por linha digitável ou código de barras. | Must | UC25 |
| **RF37** | O sistema deve validar o dígito verificador do boleto e exibir beneficiário, vencimento e valor antes da confirmação. | Must | UC25 |
| **RF38** | O sistema deve permitir o pagamento de cobrança Pix por leitura ou colagem de QR Code (payload EMV). | Should | UC26 |
| **RF39** | O sistema deve permitir ao cliente emitir cobrança Pix com valor, descrição e prazo de validade. | Should | UC27 |
| **RF40** | O sistema deve permitir agendar pagamentos recorrentes com periodicidade definida. | Could | UC28 |
| **RF41** | O sistema deve creditar automaticamente o emissor quando uma cobrança for liquidada. | Should | UC29 |
| **RF42** | O sistema deve notificar o cliente por e-mail e push a cada evento financeiro relevante. | Should | UC30 |

### Módulo 5 — Administração, Segurança e Suporte
| ID | Requisito | Prioridade | Casos de uso |
| :--- | :--- | :---: | :---: |
| **RF43** | O sistema deve permitir ao administrador criar, editar e desativar usuários internos. | Must | UC31 |
| **RF44** | O sistema deve implementar controle de acesso por perfil (cliente, suporte, backoffice, administrador, auditor). | Must | UC31 |
| **RF45** | O sistema deve registrar em trilha de auditoria toda operação sensível, com autor, data/hora, IP e dados alterados. | Must | UC32 |
| **RF46** | O sistema deve impedir a alteração ou exclusão de registros da trilha de auditoria. | Must | UC32 |
| **RF47** | O sistema deve permitir ao administrador bloquear contas suspeitas, impedindo novas movimentações. | Must | UC33 |
| **RF48** | O sistema deve permitir ao auditor consultar a trilha com filtros por autor, período e tipo de operação. | Should | UC34 |
| **RF49** | O sistema deve permitir parametrizar tarifas, limites por operação e limites diários. | Should | UC35 |
| **RF50** | O sistema deve gerar relatórios gerenciais de volume transacionado, contas ativas e chamados. | Should | UC36 |
| **RF51** | O sistema deve permitir ao cliente abrir chamado de suporte com assunto, descrição e anexo. | Should | UC38 |
| **RF52** | O sistema deve permitir ao analista responder o chamado e alterar seu status. | Should | UC37 |

> **Resumo Funcional:** 52 requisitos funcionais — 28 Must, 20 Should, 4 Could.

---

## Parte II — Requisitos Não Funcionais
Classificados pela norma ISO/IEC 25010 (qualidade de produto de software).

### Desempenho e eficiência
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF01** | As telas de saldo e extrato devem responder em até 2 segundos no percentil 95. | Teste de carga com 50 usuários simultâneos. |
| **RNF02** | As operações de escrita da API (transferência, pagamento) devem responder em até 3 segundos. | Medição de tempo de resposta no log. |
| **RNF03** | O sistema deve suportar 100 usuários simultâneos sem degradação perceptível. | Teste com k6 ou JMeter. |
| **RNF04** | O aplicativo mobile deve iniciar em até 4 segundos em um aparelho Android de entrada. | Medição em dispositivo real. |
| **RNF05** | Consultas de extrato devem usar paginação de no máximo 50 registros por página. | Inspeção de código e da API. |

### Segurança
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF06** | Toda comunicação entre cliente e servidor deve usar HTTPS (TLS 1.2+). | Verificação do certificado no Render. |
| **RNF07** | Senhas devem ser armazenadas com hash bcrypt (custo $\ge$ 12), nunca em texto claro. | Inspeção da tabela usuarios. |
| **RNF08** | Dados sensíveis (CPF, número de cartão) devem ser exibidos mascarados por padrão. | Teste de interface. |
| **RNF09** | O sistema deve estar protegido contra as vulnerabilidades do OWASP Top 10 (SQL injection, XSS, CSRF, IDOR). | Checklist OWASP + uso de Eloquent, Blade escaping e tokens CSRF. |
| **RNF10** | Tokens de API devem expirar em 60 minutos, com renovação por refresh token. | Teste de expiração. |
| **RNF11** | A sessão web deve encerrar após 15 minutos de inatividade. | Teste manual. |
| **RNF12** | O sistema deve aplicar rate limiting de 60 requisições por minuto por usuário. | Teste de estresse no endpoint. |
| **RNF13** | Nenhum dado pessoal deve ser gravado em logs de aplicação. | Revisão de código e amostragem de logs. |
| **RNF14** | O sistema deve atender aos princípios da LGPD: finalidade, minimização, consentimento e direito de exclusão. | Checklist de conformidade. |

### Confiabilidade e integridade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF15** | Toda movimentação financeira deve ocorrer dentro de uma transação ACID; falha parcial implica rollback total. | Teste de falha induzida no meio da operação. |
| **RNF16** | A soma dos lançamentos de uma transação deve ser sempre zero (partidas dobradas). | Teste automatizado sobre o razão. |
| **RNF17** | Requisições de transação devem ser idempotentes por chave de idempotência, evitando duplicidade em reenvios. | Teste de reenvio da mesma requisição. |
| **RNF18** | Valores monetários devem usar `numeric(18,2)` no banco e inteiros em centavos na aplicação — nunca ponto flutuante. | Inspeção das migrations e do código. |
| **RNF19** | O banco de dados deve ter backup semanal, com procedimento de restauração documentado e testado. | Evidência do dump e teste de restore. |
| **RNF20** | O sistema deve ter disponibilidade mensal $\ge$ 95%, considerando as limitações do plano gratuito. | Monitoramento por uptime checker. |

### Usabilidade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF21** | Interface responsiva, funcional de 320 px (celular) a 1920 px (desktop). | Teste em três resoluções. |
| **RNF22** | Toda operação financeira deve exigir uma tela de confirmação antes da efetivação. | Teste de fluxo. |
| **RNF23** | Mensagens de erro devem ser claras, em português, indicando como corrigir o problema. | Revisão do catálogo de mensagens. |
| **RNF24** | Um usuário novo deve concluir uma transferência sem treinamento em até 3 minutos. | Teste com 5 usuários reais. |
| **RNF25** | O sistema deve atender ao nível AA da WCAG 2.1 em contraste e navegação por teclado. | Auditoria com Lighthouse. |

### Manutenibilidade e portabilidade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF26** | O código deve seguir o padrão PSR-12 (PHP) e as convenções do Dart/Flutter. | Análise com Laravel Pint e dart analyze. |
| **RNF27** | O backend deve ter cobertura de testes automatizados $\ge$ 60% nas regras de negócio, desenvolvidas com TDD. | Relatório de cobertura do PHPUnit. |
| **RNF28** | Toda alteração de esquema deve ser feita por migration versionada. | Histórico do diretório `database/migrations`. |
| **RNF29** | Configurações sensíveis devem residir em variáveis de ambiente, nunca no repositório. | Inspeção do `.gitignore` e do `.env.example`. |
| **RNF30** | A API REST deve ser versionada (`/api/v1`) e documentada em OpenAPI/Swagger. | Acesso à documentação publicada. |
| **RNF31** | O sistema deve rodar em Linux e ser implantável por deploy automático a partir do GitHub. | Deploy funcional no Render. |
| **RNF32** | O aplicativo mobile deve suportar Android 8.0 (API 26) ou superior. | Configuração do `build.gradle`. |
| **RNF32.1** | O código Flutter deve manter compatibilidade com iOS 13+, mesmo que a compilação para iOS não seja demonstrada nesta versão. | Ausência de dependências exclusivas de Android no `pubspec.yaml`. |

### Restrições de projeto
| ID | Restrição |
| :--- | :--- |
| **RNF33** | O backend deve ser desenvolvido em Laravel (PHP 8.2+). |
| **RNF34** | O frontend web deve usar Blade com Tailwind CSS. |
| **RNF35** | O aplicativo mobile deve ser desenvolvido em Flutter. |
| **RNF36** | O banco de dados deve ser PostgreSQL. |
| **RNF37** | A hospedagem deve ocorrer no Render, com plano gratuito. |
| **RNF38** | O código deve ser versionado no GitHub, com commits semanais e acesso ao professor. |
| **RNF39** | Todo o código deve ser desenvolvido durante o semestre, sem reaproveitamento de código legado. |

> **Resumo Não Funcional:** 39 requisitos não funcionais distribuídos em 6 categorias.

---

## Parte III — Regras de Negócio

| ID | Regra de Negócio |
| :--- | :--- |
| **RN01** | Uma conta só é ativada após aprovação do KYC pelo backoffice. |
| **RN02** | O saldo de uma conta é sempre a soma dos seus lançamentos — nunca um valor editável diretamente. |
| **RN03** | Toda transação gera no mínimo dois lançamentos, cuja soma é zero (partidas dobradas). |
| **RN04** | Não é permitido saldo negativo: transações sem saldo suficiente são rejeitadas (não há cheque especial nesta versão). |
| **RN05** | O valor mínimo de uma transferência é R$ 0,01 e o máximo padrão é R$ 5.000,00 por operação. |
| **RN06** | O limite diário padrão de transferências é R$ 10.000,00, parametrizável pelo administrador. |
| **RN07** | Entre 20h e 6h, o limite por operação cai para R$ 1.000,00 (limite noturno). |
| **RN08** | Um cliente pode ter no máximo 5 chaves Pix, sendo no máximo uma por tipo (exceto aleatória). |
| **RN09** | Transferências para favorecido cadastrado há menos de 24h passam por análise de risco reforçada. |
| **RN10** | Transações efetivadas não podem ser excluídas; correções ocorrem por lançamento de estorno. |
| **RN11** | Um cartão bloqueado não autoriza compras nem gera lançamentos. |
| **RN12** | O limite total dos cartões de um cliente não pode ultrapassar o teto aprovado no cadastro. |
| **RN13** | A contestação de um lançamento só pode ser aberta em até 90 dias após a data da compra. |
| **RN14** | Uma cobrança Pix expira automaticamente após o prazo de validade definido pelo emissor. |
| **RN15** | Contas com saldo diferente de zero não podem ser encerradas. |
| **RN16** | Contas bloqueadas por suspeita de fraude não realizam movimentações até liberação pelo administrador. |
| **RN17** | Todo acesso ou alteração a dados de terceiros por usuário interno é registrado na trilha de auditoria. |

---

## Parte IV — Rastreabilidade

### Requisitos $\times$ Caso de Uso
| Diagrama / Módulo | Casos de uso | Requisitos atendidos |
| :--- | :--- | :--- |
| **1 — Acesso e Gestão de Conta** | UC01–UC08 | RF01–RF12 |
| **3 — Movimentações Financeiras** | UC09–UC16 | RF13–RF26 |
| **4 — Cartões** | UC17–UC24 | RF27–RF35 |
| **5 — Pagamentos e Cobranças** | UC25–UC30 | RF36–RF42 |
| **7 — Administração, Segurança e Suporte** | UC31–UC38 | RF43–RF52 |

### Técnicas de Levantamento Utilizadas
* **Análise de concorrentes:** Estudo dos fluxos de Nubank, Banco Inter e C6 Bank para identificar as funcionalidades esperadas de um banco digital.
* **Entrevista simulada:** Roteiro aplicado ao usuário-tipo (correntista jovem, 18–35 anos, mobile-first).
* **Análise documental:** Requisitos da disciplina (mínimo de 8 entidades, duas interfaces, implantação em nuvem) e material das aulas 01 e 02.
* **Brainstorming da equipe:** Sessão de levantamento livre, seguida de priorização MoSCoW.
* **Prototipação em papel:** Esboço das telas principais para validar a completude dos requisitos.


## Parte II — Requisitos Não Funcionais
Classificados pela norma ISO/IEC 25010 (qualidade de produto de software).

### Desempenho e eficiência
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF01** | As telas de saldo e extrato devem responder em até 2 segundos no percentil 95. | Teste de carga com 50 usuários simultâneos. |
| **RNF02** | As operações de escrita da API (transferência, pagamento) devem responder em até 3 segundos. | Medição de tempo de resposta no log. |
| **RNF03** | O sistema deve suportar 100 usuários simultâneos sem degradação perceptível. | Teste com k6 ou JMeter. |
| **RNF04** | O aplicativo mobile deve iniciar em até 4 segundos em um aparelho Android de entrada. | Medição em dispositivo real. |
| **RNF05** | Consultas de extrato devem usar paginação de no máximo 50 registros por página. | Inspeção de código e da API. |

### Segurança
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF06** | Toda comunicação entre cliente e servidor deve usar HTTPS (TLS 1.2+). | Verificação do certificado no Render. |
| **RNF07** | Senhas devem ser armazenadas com hash bcrypt (custo $\ge$ 12), nunca em texto claro. | Inspeção da tabela `usuarios`. |
| **RNF08** | Dados sensíveis (CPF, número de cartão) devem ser exibidos mascarados por padrão. | Teste de interface. |
| **RNF09** | O sistema deve estar protegido contra as vulnerabilidades do OWASP Top 10 (SQL injection, XSS, CSRF, IDOR). | Checklist OWASP + uso de Eloquent, Blade escaping e tokens CSRF. |
| **RNF10** | Tokens de API devem expirar em 60 minutos, com renovação por refresh token. | Teste de expiração. |
| **RNF11** | A sessão web deve encerrar após 15 minutos de inatividade. | Teste manual. |
| **RNF12** | O sistema deve aplicar rate limiting de 60 requisições por minuto por usuário. | Teste de estresse no endpoint. |
| **RNF13** | Nenhum dado pessoal deve ser gravado em logs de aplicação. | Revisão de código e amostragem de logs. |
| **RNF14** | O sistema deve atender aos princípios da LGPD: finalidade, minimização, consentimento e direito de exclusão. | Checklist de conformidade. |

### Confiabilidade e integridade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF15** | Toda movimentação financeira deve ocorrer dentro de uma transação ACID; falha parcial implica rollback total. | Teste de falha induzida no meio da operação. |
| **RNF16** | A soma dos lançamentos de uma transação deve ser sempre zero (partidas dobradas). | Teste automatizado sobre o razão. |
| **RNF17** | Requisições de transação devem ser idempotentes por chave de idempotência, evitando duplicidade em reenvios. | Teste de reenvio da mesma requisição. |
| **RNF18** | Valores monetários devem usar `numeric(18,2)` no banco e inteiros em centavos na aplicação — nunca ponto flutuante. | Inspeção das migrations e do código. |
| **RNF19** | O banco de dados deve ter backup semanal, com procedimento de restauração documentado e testado. | Evidência do dump e teste de restore. |
| **RNF20** | O sistema deve ter disponibilidade mensal $\ge$ 95%, considerando as limitações do plano gratuito. | Monitoramento por uptime checker. |

### Usabilidade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF21** | Interface responsiva, funcional de 320 px (celular) a 1920 px (desktop). | Teste em três resoluções. |
| **RNF22** | Toda operação financeira deve exigir uma tela de confirmação antes da efetivação. | Teste de fluxo. |
| **RNF23** | Mensagens de erro devem ser claras, em português, indicando como corrigir o problema. | Revisão do catálogo de mensagens. |
| **RNF24** | Um usuário novo deve concluir uma transferência sem treinamento em até 3 minutos. | Teste com 5 usuários reais. |
| **RNF25** | O sistema deve atender ao nível AA da WCAG 2.1 em contraste e navegação por teclado. | Auditoria com Lighthouse. |

### Manutenibilidade e portabilidade
| ID | Requisito | Métrica de verificação |
| :--- | :--- | :--- |
| **RNF26** | O código deve seguir o padrão PSR-12 (PHP) e as convenções do Dart/Flutter. | Análise com Laravel Pint e `dart analyze`. |
| **RNF27** | O backend deve ter cobertura de testes automatizados $\ge$ 60% nas regras de negócio, desenvolvidas com TDD. | Relatório de cobertura do PHPUnit. |
| **RNF28** | Toda alteração de esquema deve ser feita por migration versionada. | Histórico do diretório `database/migrations`. |
| **RNF29** | Configurações sensíveis devem residir em variáveis de ambiente, nunca no repositório. | Inspeção do `.gitignore` e do `.env.example`. |
| **RNF30** | A API REST deve ser versionada (`/api/v1`) e documentada em OpenAPI/Swagger. | Acesso à documentação publicada. |
| **RNF31** | O sistema deve rodar em Linux e ser implantável por deploy automático a partir do GitHub. | Deploy funcional no Render. |
| **RNF32** | O aplicativo mobile deve suportar Android 8.0 (API 26) ou superior. | Configuração do `build.gradle`. |
| **RNF32.1** | O código Flutter deve manter compatibilidade com iOS 13+, mesmo que a compilação para iOS não seja demonstrada nesta versão. | Ausência de dependências exclusivas de Android no `pubspec.yaml`. |

### Restrições de projeto
| ID | Restrição |
| :--- | :--- |
| **RNF33** | O backend deve ser desenvolvido em Laravel (PHP 8.2+). |
| **RNF34** | O frontend web deve usar Blade com Tailwind CSS. |
| **RNF35** | O aplicativo mobile deve ser desenvolvido em Flutter. |
| **RNF36** | O banco de dados deve ser PostgreSQL. |
| **RNF37** | A hospedagem deve ocorrer no Render, com plano gratuito. |
| **RNF38** | O código deve ser versionado no GitHub, com commits semanais e acesso ao professor. |
| **RNF39** | Todo o código deve ser desenvolvido durante o semestre, sem reaproveitamento de código legado. |

> **Resumo Não Funcional:** 39 requisitos não funcionais em 6 categorias.

---

## Parte III — Regras de Negócio

| ID | Regra de Negócio |
| :--- | :--- |
| **RN01** | Uma conta só é ativada após aprovação do KYC pelo backoffice. |
| **RN02** | O saldo de uma conta é sempre a soma dos seus lançamentos — nunca um valor editável diretamente. |
| **RN03** | Toda transação gera no mínimo dois lançamentos, cuja soma é zero (partidas dobradas). |
| **RN04** | Não é permitido saldo negativo: transações sem saldo suficiente são rejeitadas (não há cheque especial nesta versão). |
| **RN05** | O valor mínimo de uma transferência é R$ 0,01 e o máximo padrão é R$ 5.000,00 por operação. |
| **RN06** | O limite diário padrão de transferências é R$ 10.000,00, parametrizável pelo administrador. |
| **RN07** | Entre 20h e 6h, o limite por operação cai para R$ 1.000,00 (limite noturno). |
| **RN08** | Um cliente pode ter no máximo 5 chaves Pix, sendo no máximo uma por tipo (exceto aleatória). |
| **RN09** | Transferências para favorecido cadastrado há menos de 24h passam por análise de risco reforçada. |
| **RN10** | Transações efetivadas não podem ser excluídas; correções ocorrem por lançamento de estorno. |
| **RN11** | Um cartão bloqueado não autoriza compras nem gera lançamentos. |
| **RN12** | O limite total dos cartões de um cliente não pode ultrapassar o teto aprovado no cadastro. |
| **RN13** | A contestação de um lançamento só pode ser aberta em até 90 dias após a data da compra. |
| **RN14** | Uma cobrança Pix expira automaticamente após o prazo de validade definido pelo emissor. |
| **RN15** | Contas com saldo diferente de zero não podem ser encerradas. |
| **RN16** | Contas bloqueadas por suspeita de fraude não realizam movimentações até liberação pelo administrador. |
| **RN17** | Todo acesso ou alteração a dados de terceiros por usuário interno é registrado na trilha de auditoria. |

---

## Parte IV — Rastreabilidade (Requisitos $\times$ Caso de Uso)

| Diagrama / Módulo | Casos de uso | Requisitos atendidos |
| :--- | :--- | :--- |
| **1 — Acesso e Gestão de Conta** | UC01–UC08 | RF01–RF12 |
| **3 — Movimentações Financeiras** | UC09–UC16 | RF13–RF26 |
| **4 — Cartões** | UC17–UC24 | RF27–RF35 |
| **5 — Pagamentos e Cobranças** | UC25–UC30 | RF36–RF42 |
| **7 — Administração, Segurança e Suporte** | UC31–UC38 | RF43–RF52 |

### Técnicas de Levantamento Utilizadas
* **Análise de concorrentes:** Estudo dos fluxos de Nubank, Banco Inter e C6 Bank para identificar as funcionalidades esperadas de um banco digital.
* **Entrevista simulada:** Roteiro aplicado ao usuário-tipo (correntista jovem, 18–35 anos, mobile-first).
* **Análise documental:** Requisitos da disciplina (mínimo de 8 entidades, duas interfaces, implantação em nuvem) e material das aulas 01 e 02.
* **Brainstorming da equipe:** Sessão de levantamento livre, seguida de priorização MoSCoW.
* **Prototipação em papel:** Esboço das telas principais para validar a completude dos requisitos.

---
> **Observação:** Este é o levantamento inicial (Checkpoint 1). A versão consolidada, com os requisitos finais e os documentos detalhados de casos de uso, será entregue no Checkpoint 2 (17/09).
