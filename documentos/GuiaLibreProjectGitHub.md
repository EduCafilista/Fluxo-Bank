# Guia prático — LibreProject (4.4.2) e GitHub (4.4.3)

Passo a passo do que **você precisa executar manualmente** para fechar o Checkpoint 1.

---

## Parte 1 — LibreProject (item 4.4.2)

O item pede a **criação do projeto no LibreProject com atualização semanal**. O arquivo `libreproject/cronograma-fluxo.csv` já contém as 49 tarefas do semestre, com datas, durações, responsáveis e dependências.

> **Nota:** o professor chama de "LibreProject"; a ferramenta gratuita de gestão de projetos com Gantt mais usada com esse nome é o **ProjectLibre** (ou o GanttProject, equivalente). O CSV funciona nos dois.

### Opção A — Importar o CSV (mais rápido)

1. Baixe e instale o ProjectLibre: https://www.projectlibre.com/product/projectlibre-open-source
2. Abra o programa → **Create Project**
   - *Project Name:* `Fluxo - Sistema Bancário Digital`
   - *Manager:* nome do líder do grupo
   - *Start date:* `18/08/2026`
3. Menu **File → Open** → selecione `cronograma-fluxo.csv` → confirme o mapeamento das colunas:

   | Coluna do CSV | Campo do ProjectLibre |
   |---|---|
   | Nome | Name |
   | Inicio | Start |
   | Duracao | Duration |
   | Responsavel | Resource Names |
   | Predecessora | Predecessors |

4. Use o botão **Indent** (seta para a direita) para transformar as tarefas de nível 3 em subtarefas dos blocos de nível 2 (os quatro checkpoints).
5. Salve como `Fluxo.pod` e **substitua** o arquivo `Fluxo - Sistema Bancário Digital.pod` que já está no repositório.

### Opção B — Digitar manualmente

Se a importação der problema (o ProjectLibre é exigente com CSV), digite as tarefas direto na grade — são 4 blocos principais. Estrutura resumida:

```
FLUXO - Sistema Bancário Digital
├── CHECKPOINT 1 - Concepção          18/08 a 03/09
├── CHECKPOINT 2 - Modelagem          04/09 a 17/09
├── CHECKPOINT 3 - Backend REST       18/09 a 22/10
└── CHECKPOINT 4 - Frontend Web/Mobile 23/10 a 03/12
```

### Como marcar o progresso (o que o professor avalia)

Para o Checkpoint 1, as tarefas T01–T12 devem estar com **100% de conclusão**:
1. Selecione a tarefa na grade.
2. Aba **Task → Information** (ou duplo clique).
3. Campo **% Complete** → `100`.

Isso desenha a barra de progresso no Gantt — é a evidência visual de que o projeto está sendo acompanhado.

### Rotina semanal

Toda semana (sugestão: sábado, junto com o fechamento do código):
1. Abrir o `.pod`.
2. Atualizar o **% Complete** das tarefas em andamento.
3. Ajustar datas do que atrasou.
4. Salvar e commitar: `docs(gestao): atualiza cronograma no LibreProject - semana X`

---

## Parte 2 — GitHub (item 4.4.3)

### 2.1 Dar acesso ao professor (faça isso primeiro!)

1. Acesse https://github.com/AlfredoVentura/Fluxo
2. **Settings → Collaborators → Add people**
3. Informe o e-mail: `marcus.colantoni@uniube.br`
4. Confirme o convite e **avise o professor**, pois o convite expira em 7 dias.

> Se o repositório já é público, o professor consegue ver o código, mas o item pede **acesso** — o convite como colaborador é a evidência segura.

### 2.2 Subir os artefatos do Checkpoint 1

No terminal, dentro da pasta do repositório clonado:

```bash
# clonar (se ainda não tiver local)
git clone https://github.com/AlfredoVentura/Fluxo.git
cd Fluxo

# copiar os arquivos gerados para dentro do repositório:
#   README.md, docs/, libreproject/, scripts/, Fluxo-Checkpoint1-Grupo1.pdf

git add .
git commit -m "docs(cp1): adiciona escopo, requisitos, casos de uso e quadro de gestao"
git push origin main
```

### 2.3 Sugestão de commits separados (mostra evolução, não um "commitão")

Em vez de um único commit gigante, distribua — fica muito melhor no gráfico de contribuições:

```bash
git add README.md
git commit -m "docs: atualiza README com stack e estrutura do projeto"

git add docs/01-escopo-do-projeto.md
git commit -m "docs(cp1): define escopo do projeto (4.1)"

git add docs/02-requisitos.md
git commit -m "docs(cp1): levantamento inicial de requisitos (4.2)"

git add docs/diagramas/
git commit -m "docs(cp1): adiciona diagramas de casos de uso 1,3,4,5,7 (4.3)"

git add docs/quadro-de-gestao.md libreproject/
git commit -m "docs(cp1): quadro de gestao e cronograma LibreProject (4.4)"

git add scripts/ Fluxo-Checkpoint1-Grupo1.pdf
git commit -m "chore: scripts geradores e PDF consolidado do checkpoint 1"

git push origin main
```

### 2.4 Corrigir o `.gitignore` ⚠️

**Problema detectado em 24/08:** o arquivo `.gitignore` foi criado no repositório, mas está **vazio (0 bytes)**. Ele não está ignorando nada — na prática é como se não existisse.

Isso é grave num monorepo Laravel + Flutter: sem ele, o primeiro `composer install` e `flutter build` vão querer subir `vendor/`, `node_modules/` e `build/` — dezenas de milhares de arquivos. Pior: o `.env` com a senha do banco pode acabar versionado.

**Correção:** use o arquivo `gitignore-para-o-repo.txt` (gerado junto com esta documentação), renomeando-o:

```bash
cp gitignore-para-o-repo.txt .gitignore
git add .gitignore
git commit -m "chore: preenche .gitignore para monorepo Laravel + Flutter"
git push origin main
```

Ele cobre: `vendor/`, `node_modules/`, `storage/`, `bootstrap/cache/`, `.env`, build do Flutter, Pods do iOS, `.gradle/`, keystores, arquivos de IDE e do sistema operacional.

> **Se algo sensível já foi commitado**, adicionar ao `.gitignore` não remove do histórico. Use `git rm --cached <arquivo>` e, no caso de senhas, **troque a credencial** — ela continua visível no histórico do Git.

### 2.5 Proteger a branch `main`

**Settings → Branches → Add branch protection rule**
- *Branch name pattern:* `main`
- ✅ Require a pull request before merging
- ✅ Require approvals: 1

Isso obriga o time a revisar antes de integrar — e vira argumento de qualidade na apresentação.

---

### 2.6 Padronizar as mensagens de commit ⚠️

**Situação em 24/08 —** as mensagens atuais estão fora do padrão que o próprio grupo adotou:

| Commit atual | Deveria ser |
|---|---|
| `Initial commit` | (ok, é automático do GitHub) |
| `Add files via upload` | `docs: adiciona documento inicial do projeto` |
| `Revise README to enhance project description` | `docs: revisa descricao do projeto no README` |
| `cria pastas do projeto e adiciona o arquivo README` | `chore: cria estrutura monorepo backend e mobile` |
| `Ajuste do README` | `docs: detalha stack e estrutura de pastas` |
| `Delete .gitignore` + `Criação correta .gitignore` | `chore: configura .gitignore do monorepo` |

Não vale reescrever o histórico agora (dá mais problema que solução). **A partir do próximo commit**, siga o padrão Conventional Commits. Se o professor perguntar, a resposta honesta é: "padronizamos a convenção depois dos primeiros commits exploratórios".

Outro ponto: houve `Delete .gitignore` seguido de recriação. Para arquivos pequenos, prefira **editar** em vez de apagar e recriar — o histórico fica mais limpo e mostra evolução, que é justamente o que o item 4.4.3 avalia.

---

## Parte 3 — Checklist final antes de 03/09

- [ ] Convite de colaborador enviado ao professor e **confirmado**
- [ ] `.gitignore` preenchido (hoje está vazio — 0 bytes)
- [ ] Artefatos commitados e visíveis no GitHub
- [ ] Pastas `backend/` e `mobile/` com conteúdo (hoje o repo tem só README e .gitignore)
- [ ] `.pod` atualizado no LibreProject com T01–T12 em 100%
- [ ] Nomes reais dos integrantes preenchidos no `docs/quadro-de-gestao.md` e na capa do PDF
- [ ] PDF único enviado pelo **diário de bordo** (não basta estar no GitHub!)
- [ ] Slides ou roteiro da apresentação preparados
- [ ] Ensaio cronometrado da apresentação

---

## Parte 4 — Roteiro sugerido da apresentação (~10 min)

| Tempo | Conteúdo | Quem |
|---|---|---|
| 1 min | Abertura: o que é o Fluxo e por que banco digital | Líder |
| 2 min | Escopo: o que está dentro e — importante — **o que ficou de fora** | Líder |
| 2 min | Requisitos: números gerais (52 RF, 39 RNF, 17 RN) e destaque para 3 ou 4 requisitos críticos | QA/Doc |
| 3 min | Diagramas de casos de uso: percorrer os 5, explicando atores e as relações include/extend | Arquiteto |
| 1 min | Arquitetura e stack: Laravel, Blade+Tailwind, Flutter, PostgreSQL, Render | Backend |
| 1 min | Gestão: Kanban, cronograma no LibreProject, commits no GitHub | Líder |

### Perguntas prováveis do professor — e como responder

**"Por que 16 entidades se o mínimo é 8?"**
Porque o domínio bancário exige separar razão contábil (`lancamentos`) de operação (`transacoes`), e cada módulo traz entidades próprias. Preferimos modelar corretamente a inflar o modelo artificialmente.

**"Como vocês garantem que o saldo não fica errado?"**
Saldo não é campo armazenado: é a soma dos lançamentos. Toda transação usa partidas dobradas dentro de uma transação ACID, com chave de idempotência para evitar duplicidade (RNF15 a RNF18).

**"O Pix é real?"**
Não. É simulado por um serviço interno que reproduz o comportamento do SPI. Está explicitamente fora de escopo (seção 6 do documento de escopo).

**"O plano gratuito do Render dá conta?"**
Dá para a demonstração, com duas limitações conhecidas: hibernação após 15 min de inatividade e expiração do banco em 30 dias. Ambas estão mapeadas como riscos R1 e R3, com dump semanal e "aquecimento" antes da apresentação.

**"Onde está o código?"**
No Checkpoint 1 a entrega é de documentação — o cronograma prevê início do código na semana seguinte (T13 a T16), com o backend entregue no Checkpoint 3, conforme a própria estrutura da disciplina.
