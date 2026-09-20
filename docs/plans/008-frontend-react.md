# Plano 008 — Frontend React

Status: implementado — revisão final pendente.

## Objetivo

Construir o frontend React mínimo completo para os fluxos de admin e seller,
consumindo a API existente e mantendo a complexidade baixa.

## Decisões propostas

- Vite + React + TypeScript;
- CSS próprio, sem biblioteca visual;
- `fetch` encapsulado em um cliente HTTP pequeno;
- token em `localStorage`, removido em `401`;
- proxy do Vite para o backend, evitando CORS no desenvolvimento;
- navegação simples dentro do shell por papel, sem criar uma camada de estado
  global ou um sistema de rotas antes de existir necessidade concreta;
- nenhum cálculo de pontos ou orçamento no frontend.

## Sequência de implementação

### 1. Scaffold e execução

- criar `frontend/` com scripts de desenvolvimento, lint e build;
- adicionar serviço `frontend` ao `docker-compose.yml`;
- configurar proxy para o backend e documentar a porta do Vite;
- validar que o frontend abre junto com `docker compose up --build`.

### 2. Base compartilhada

- criar tipos mínimos para login, produto, campanha, venda e carteira;
- criar cliente HTTP com `Authorization: Bearer`, parse de JSON e erro
  normalizado por status;
- criar sessão mínima para guardar/remover token e expor o papel somente para
  navegação;
- criar shell, navegação, botão, campo, mensagem de erro e estados de loading
  apenas quando houver uso real em mais de uma feature.

### 3. Login e proteção de sessão

- implementar formulário de login;
- tratar `401`, `422` e falha de rede;
- redirecionar visualmente para a área do papel após sucesso;
- ao receber `401` em qualquer request protegida, limpar sessão e retornar ao
  login;
- impedir que uma sessão sem token renderize dados protegidos.

### 4. Fluxo admin

- produtos: listagem, criação, edição e inativação idempotente;
- campanhas: criação e listagem com orçamento usado/total e status;
- vendas: registro com campos necessários e cancelamento por `external_id`;
- mostrar feedback para `403`, `404`, `409`, `422` e `500` sem duplicar a regra
  de negócio do backend;
- atualizar a lista afetada somente após sucesso da API.

### 5. Fluxo seller

- carregar `GET /me/wallet` ao entrar na área;
- exibir saldo, estado vazio e extrato;
- diferenciar crédito e débito por texto, ícone/forma e cor semântica;
- permitir nova tentativa quando a leitura falhar.

### 6. Responsividade e acessibilidade

- revisar desktop e viewport estreita;
- garantir labels, foco, teclado, `aria-live` para feedback e contraste;
- trocar tabelas por layout empilhado quando a largura não comportar as colunas;
- incluir estados disabled/loading em todas as ações assíncronas.

### 7. Verificação e documentação

- executar lint e build do frontend;
- subir o Compose reconstruído e testar os fluxos com as credenciais seed;
- verificar manualmente `401`, `403`, `404`, `409`, `422`, erro de rede,
  carregamento e estado vazio;
- atualizar README com porta, comando, proxy e fluxo de uso;
- registrar neste plano as evidências e os arquivos finais.

## Arquivos previstos

- `frontend/package.json`, `frontend/tsconfig*.json` e configuração do Vite;
- `frontend/src/app/` para shell e sessão;
- `frontend/src/shared/` para HTTP e componentes realmente reutilizados;
- `frontend/src/features/auth/`;
- `frontend/src/features/products/`;
- `frontend/src/features/campaigns/`;
- `frontend/src/features/sales/`;
- `frontend/src/features/wallet/`;
- `frontend/Dockerfile`;
- `docker-compose.yml`;
- `README.md`;
- testes pontuais somente para lógica não trivial que surgir.

## Fora deste plano

- importação CSV;
- mudanças no contrato dos endpoints existentes;
- alterações de regra de pontuação, ledger ou autorização;
- biblioteca de componentes, estado global, cache remoto ou observabilidade
  frontend sem necessidade demonstrada.

## Definição de pronto

- spec confirmada e plano atualizado;
- frontend inicia pelo Compose e por comando local documentado;
- login, fluxos admin e carteira seller funcionam contra a API real;
- estados de erro e sessão cobertos;
- responsividade e acessibilidade básica verificadas;
- lint, build e verificação manual passam;
- README atualizado com evidências.

## Evidências atuais

- `cd frontend && npm run lint` → passou;
- `cd frontend && npm run test` → passou, smoke test do parser de papel do JWT;
- `cd frontend && npm run build` → passou;
- `docker compose config --quiet` → passou;
- `docker compose up --build -d` → database, backend e frontend saudáveis/em execução;
- login do admin via proxy do frontend → token retornado;
- request sem token para `/products` via proxy → `401 Unauthorized`;
- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 31 testes, 47 assertions.

### Correção de datas de campanhas

- campos de início e fim alterados de `datetime-local` para `date`;
- interface sem horário e com locale `pt-BR`;
- início serializado como `00:00:00` e fim como `23:59:59`;
- data final bloqueada quando anterior à data inicial.

## Pontos a confirmar antes da implementação

1. Manter TypeScript e Vite como a escolha do scaffold;
2. manter `localStorage` para sessão persistente;
3. manter navegação simples no shell, adicionando roteador somente se a
   implementação demonstrar uma necessidade concreta.
