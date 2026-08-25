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
