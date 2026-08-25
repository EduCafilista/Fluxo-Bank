## 1. Requisitos Funcionais (RF)

### Acesso e Gestão de Conta
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF01** | Permitir cadastro informando nome completo, CPF, data de nascimento, e-mail, telefone e senha. | Must | UC01 |
| **RF02** | Validar o CPF por dígitos verificadores e recusar duplicidade na base. | Must | UC01 |
| **RF03** | Submeter o cadastro a uma verificação de identidade (KYC) simulada e automatizada. | Must | UC02 |
| **RF04** | Aprovar ou reprovar a abertura de conta automaticamente, registrando o motivo em caso de recusa. | Must | UC06 |
| **RF05** | Criar automaticamente conta corrente com agência e número únicos após aprovação. | Must | UC06 |
| **RF06** | Autenticar por e-mail e senha (sessão web / token na API mobile). | Must | UC03 |
| **RF07** | Permitir ativar autenticação em dois fatores (2FA) via e-mail. | Should | UC04 |
| **RF08** | Bloquear acesso após 5 tentativas incorretas em 15 minutos, exigindo desbloqueio via suporte. | Must | UC03 |
| **RF09** | Recuperação de senha por link com token de uso único (validade de 30 minutos). | Must | UC05 |
| **RF10** | Permitir ao cliente visualizar e alterar dados cadastrais (telefone, e-mail e endereço). | Must | UC07 |
| **RF11** | Exigir confirmação por senha para alteração de dados sensíveis. | Should | UC07 |
| **RF12** | Permitir encerramento de conta apenas com saldo zero e sem pendências. | Should | UC08 |

### Movimentações Financeiras
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF13** | Exibir saldo atual calculado a partir dos lançamentos. | Must | UC09 |
| **RF14** | Exibir extrato ordenado do mais recente para o mais antigo. | Must | UC09 |
| **RF15** | Permitir filtrar extrato por período, tipo de operação e faixa de valor. | Should | UC09 |
| **RF16** | Permitir registrar chaves Pix (CPF, e-mail, telefone, aleatória). | Must | UC10 |
| **RF17** | Impedir rigorosamente o registro de chave Pix já vinculada a outra conta. | Must | UC10 |
| **RF18** | Realizar transferência Pix informando chave e valor. | Must | UC11 |
| **RF19** | Exibir dados do favorecido para confirmação prévia. | Must | UC11 |
| **RF20** | Submeter transferências à análise de risco antes da efetivação. | Must | UC12 |
| **RF21** | Permitir transferência interna com liquidação imediata. | Must | UC13 |
| **RF22** | Agendar transferências para data futura, validando o saldo no dia da execução e disparando e-mail de falha se houver insuficiência. | Should | UC14 |
| **RF23** | Cancelar agendamentos até o dia anterior à execução. | Should | UC14 |
| **RF24** | Permitir depósito simulado via Pix ou boleto. | Must | UC15 |
| **RF25** | Generar comprovante em PDF com identificador único. | Should | UC16 |
| **RF26** | Salvar favorecidos frequentes para reuso. | Could | UC13 |

### Cartões
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF27** | Emissão imediata de cartão virtual (número, validade e CVV). | Must | UC17 |
| **RF28** | Exibir número do cartão mascarado, revelando mediante reautenticação. | Must | UC17 |
| **RF29** | Solicitar cartão físico e acompanhar status do ciclo de vida. | Should | UC18 |
| **RF30** | Bloquear e desbloquear cartões (temporária ou definitivamente). | Must | UC19 |
| **RF31** | Ajustar limite do cartão respeitando o teto aprovado. | Should | UC20 |
| **RF32** | Processar autorizações de compra simuladas (limite e status). | Should | UC21 |
| **RF33** | Manter fatura com lançamentos, datas de fechamento e vencimento. | Should | UC22 |
| **RF34** | Permitir contestar lançamentos da fatura com justificativa. | Could | UC23 |
| **RF35** | Análise de contestação pelo backoffice (deferir/indeferir). | Could | UC24 |

### Pagamentos e Cobranças
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF36** | Pagar boleto por linha digitável ou código de barras. | Must | UC25 |
| **RF37** | Validar dígito verificador e exibir dados do boleto antes de confirmar. | Must | UC25 |
| **RF38** | Pagar cobrança Pix por QR Code (payload EMV). | Should | UC26 |
| **RF39** | Emitir cobrança Pix com valor, descrição e validade. | Should | UC27 |
| **RF40** | Agendar pagamentos recorrentes. | Could | UC28 |
| **RF41** | Creditar automaticamente o emissor na liquidação da cobrança. | Should | UC29 |
| **RF42** | Notificar cliente por e-mail e push sobre eventos financeiros. | Should | UC30 |

### Administração, Segurança e Suporte
| ID | Descrição | Prioridade | Casos de Uso |
|:---|:---|:---:|:---:|
| **RF43** | Gerenciar usuários internos (criar, editar, desativar). | Must | UC31 |
| **RF44** | Controle de acesso baseado em perfis (RBAC). | Must | UC31 |
| **RF45** | Trilha de auditoria imutável para operações sensíveis. | Must | UC32 |
| **RF46** | Impedir a alteração ou exclusão de registros da trilha de auditoria. | Must | UC32 |
| **RF47** | Bloquear contas suspeitas de fraude (administração). | Must | UC33 |
| **RF48** | Exibir requisições pendentes de desbloqueio de contas na visão de administração/suporte para liberação direta. | Must | UC33 |
| **RF49** | Consulta avançada à trilha de auditoria pelo auditor. | Should | UC34 |
| **RF50** | Parametrização de tarifas, limites e teto máximo de segurança. | Should | UC35 |
| **RF51** | Relatórios gerenciais (volume transacionado, contas ativas, chamados). | Should | UC36 |
| **RF52** | Abertura de chamados de suporte pelo cliente. | Should | UC38 |
| **RF53** | Atendimento e atualização de chamados pelo analista. | Should | UC37 |

---

## 2. Requisitos Não Funcionais (RNF) — ISO/IEC 25010

### Desempenho e Eficiência
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF01** | Telas de saldo e extrato devem responder em até 2 segundos (p95). | Teste de carga com 50 usuários simultâneos. |
| **RNF02** | Operações de escrita da API devem responder em até 3 segundos. | Medição de tempo de resposta no log. |
| **RNF03** | Suportar 100 usuários simultâneos sem degradação perceptível. | Teste com k6 ou JMeter. |
| **RNF04** | Aplicativo mobile deve iniciar em até 4 segundos em Android de entrada. | Medição em dispositivo real. |
| **RNF05** | Consultas de extrato devem usar paginação de no máximo 50 registros. | Inspeção de código e da API. |

### Segurança
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF06** | Comunicação entre cliente e servidor via HTTPS (TLS 1.2+). | Verificação do certificado no Render. |
| **RNF07** | Senhas armazenadas com hash bcrypt (custo $\ge 12$). | Inspeção da tabela `usuarios`. |
| **RNF08** | Dados sensíveis (CPF, cartão) exibidos mascarados por padrão. | Teste de interface. |
| **RNF09** | Proteção contra vulnerabilidades do OWASP Top 10. | Checklist OWASP + Eloquent + Blade escaping. |
| **RNF10** | Tokens de API expiram em 60 minutos, com refresh token. | Teste de expiração. |
| **RNF11** | Sessão web encerra após 15 minutos de inatividade. | Teste manual. |
| **RNF12** | Rate limiting de 60 requisições por minuto por usuário. | Teste de estresse no endpoint. |
| **RNF13** | Nenhum dado pessoal gravado em logs de aplicação. | Revisão de código e amostragem de logs. |
| **RNF14** | Atendimento aos princípios da LGPD. | Checklist de conformidade. |

### Confiabilidade e Integridade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF15** | Movimentação financeira em transação ACID (rollback em falha). | Teste de falha induzida. |
| **RNF16** | Soma dos lançamentos de uma transação deve ser zero (partidas dobradas). | Teste automatizado sobre o razão. |
| **RNF17** | Requisições de transação idempotentes por chave de idempotência. | Teste de reenvio da mesma requisição. |
| **RNF18** | Valores monetários em `numeric(18,2)` e inteiros em centavos. | Inspeção de migrations e código. |
| **RNF19** | Backup semanal do banco de dados com restauração testada. | Evidência de dump e restore. |
| **RNF20** | Disponibilidade mensal $\ge 95\%$. | Monitoramento por uptime checker. |

### Usabilidade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF21** | Interface responsiva de 320px a 1920px. | Teste em três resoluções. |
| **RNF22** | Tela de confirmação obrigatória antes de efetivar operações financeiras. | Teste de fluxo. |
| **RNF23** | Mensagens de erro claras em português com instruções de correção. | Revisão do catálogo de mensagens. |
| **RNF24** | Concluir transferência sem treinamento em até 3 minutos. | Teste com 5 usuários reais. |
| **RNF25** | Atender nível AA da WCAG 2.1 em contraste e navegação por teclado. | Auditoria com Lighthouse. |

### Manutenibilidade e Portabilidade
| ID | Descrição | Métrica de Verificação |
|:---|:---|:---|
| **RNF26** | Código segue padrão PSR-12 (PHP) e convenções Dart/Flutter. | Análise com Laravel Pint e `dart analyze`. |
| **RNF27** | Cobertura de testes automatizados $\ge 60\%$ com TDD. | Relatório de cobertura PHPUnit. |
| **RNF28** | Alterações de esquema feitas por migration versionada. | Histórico de `database/migrations`. |
| **RNF29** | Configurações sensíveis em variáveis de ambiente (`.env`). | Inspeção do `.gitignore` e `.env.example`. |
| **RNF30** | API REST versionada (`/api/v1`) e documentada em OpenAPI/Swagger. | Acesso à documentação publicada. |
| **RNF31** | Sistema executável em Linux com deploy automático via GitHub. | Deploy funcional no Render. |
| **RNF32** | Suporte a Android 8.0+ (API 26) e compatibilidade com iOS 13+. | Configuração de build e `pubspec.yaml`. |

### Restrições de Projeto
| ID | Restrição |
|:---|:---|
| **RNF33** | Backend em Laravel (PHP 8.2+). |
| **RNF34** | Frontend web em Blade com Tailwind CSS. |
| **RNF35** | Aplicativo mobile em Flutter. |
| **RNF36** | Banco de dados PostgreSQL. |
| **RNF37** | Hospedagem no Render (plano gratuito). |
| **RNF38** | Versionamento no GitHub com commits semanais. |
| **RNF39** | Desenvolvimento integral durante o semestre, sem código legado. |
