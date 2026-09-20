# Vendeu, Ganhou

Plataforma de incentivo de vendas desenvolvida para o desafio técnico de desenvolvedor(a) pleno. O backend usa PHP 8 puro, MySQL e PDO; o frontend usa React, TypeScript e Vite; toda a aplicação roda com Docker Compose.

## Estado atual

Já implementado:

- Estrutura inicial documentada com Clean Code, SDD e TDD;
- PHP 8.3 e Composer no container;
- PHPUnit configurado;
- Router e endpoint `GET /health`;
- MySQL 8.4 com schema versionado;
- Migration e seed idempotentes executados no boot;
- Conexão PDO com prepared statements;
- Login com JWT;
- Middleware JWT com principal autenticado;
- ACL por papel com respostas `401` e `403`;
- Pipeline de middlewares por rota no router;
- Rota administrativa protegida `GET /admin/ping` para verificação HTTP;
- CRUD de produtos com inativação lógica;
- Criação e listagem de campanhas com validação de período e orçamento;
- Registro transacional de vendas com cálculo de pontos, idempotência e consumo seguro de verba;
- Cancelamento idempotente com estorno de pontos e devolução transacional de verba;
- Carteira do seller com saldo derivado do ledger e extrato protegido por ownership;
- Histórico administrativo de vendas com cancelamento por linha e exportação CSV;
- Frontend React com login, área administrativa, carteira do seller e tratamento de estados de erro;
- Cadastro administrativo de sellers e seleção de seller por nome no lançamento de venda;
- Retry transacional para deadlocks MySQL (`1213`/`40001`) em vendas e cancelamentos concorrentes;
- Especificação de autorização e testes unitários do principal, autenticação, ACL e pipeline;
- Verificação de senha com `password_verify`;
- `firebase/php-jwt` 7.x com `composer.lock` versionado.

## Pré-requisitos

- Docker;
- Docker Compose;
- Git.

Não é necessário instalar PHP ou Composer na máquina host.

## Como executar

Na raiz do projeto:

```bash
cp .env.example .env
docker compose up --build
```

O arquivo `.env` concentra as credenciais locais do MySQL, os parâmetros de
conexão usados pelo backend e o segredo JWT. Ele é ignorado pelo Git; apenas o
`.env.example` é versionado. Em ambiente real, substitua todos os valores de
desenvolvimento por credenciais e um segredo aleatórios.

Serviços disponíveis:

- Backend: http://localhost:8080
- Frontend: http://localhost:5173
- MySQL: localhost:3306

O backend aguarda o MySQL ficar saudável, executa `backend/bin/migrate.php` e inicia o servidor PHP.

O frontend usa o proxy do Vite para encaminhar `/auth`, `/products`,
`/campaigns`, `/sales` e `/me` ao backend. Não é necessário configurar CORS
para o ambiente local.

Para parar os containers:

```bash
docker compose down
```

Para executar somente o frontend fora do Compose, instale Node.js 22 ou
superior e rode:

```bash
cd frontend
npm install
BACKEND_URL=http://localhost:8080 npm run dev
```

Para apagar também o volume local do banco e recriar o seed do zero:

```bash
docker compose down -v
```

## Credenciais de seed

As credenciais são criadas por `backend/bin/migrate.php`. As senhas são transformadas em hash com `password_hash`.

| Papel | Email | Senha |
|---|---|---|
| Admin | `admin@toro.local` | `admin123` |
| Seller | `seller1@toro.local` | `seller123` |
| Seller | `seller2@toro.local` | `seller123` |

O seed também cria dois produtos e uma campanha inicial com orçamento de 10.000 pontos.

## API disponível

### Health check

```bash
curl -i http://localhost:8080/health
```

Resposta:

```json
{"status":"ok"}
```

### Login

```bash
curl -i -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@toro.local","password":"admin123"}'
```

Resposta válida: `200` com um JWT no campo `token`.

Credenciais inválidas retornam `401`. Campos ausentes ou inválidos retornam `422`.

O token contém `sub`, `role`, `iat` e `exp`. O segredo é configurado por `JWT_SECRET` no ambiente; o Compose fornece um valor de desenvolvimento padrão. Em qualquer ambiente real, substitua esse valor por um segredo aleatório com pelo menos 32 caracteres.

O registro de vendas e o cancelamento com estorno estão disponíveis para
administradores. A venda aprovada só pode ser cancelada antes de completar 30
dias desde `created_at`; o limite e qualquer instante posterior retornam `422`.

Na interface, o admin pode consultar o histórico de vendas, cancelar uma venda
pela própria linha e exportar as vendas carregadas para CSV UTF-8 compatível com
Excel. O arquivo usa separador `;` e não cria uma nova requisição.

### Sellers administrativos

O admin pode cadastrar e listar sellers sem expor `password_hash`:

```text
GET  /users/sellers
POST /users
```

Essas rotas exigem token de admin. A tela de vendas usa a listagem para mostrar
nome e e-mail no seletor, mantendo o `seller_id` apenas no payload interno.

### Histórico administrativo de vendas

```bash
curl -i http://localhost:8080/sales \
  -H "Authorization: Bearer <admin-token>"
```

A resposta contém seller, produto, campanha, quantidade, pontos, status, data e
o identificador técnico da venda. A ordenação é da mais recente para a mais
antiga.

### Registro de venda

```bash
curl -i -X POST http://localhost:8080/sales \
  -H "Authorization: Bearer <admin-token>" \
  -H "Content-Type: application/json" \
  -d '{"external_id":"erp-sale-1001","campaign_id":1,"seller_id":2,"product_id":1,"quantity":3,"unit_value":"149.90"}'
```

Os pontos são calculados como `quantity * product.points_per_unit`. A venda
rejeita integralmente quando não há verba suficiente. Venda, crédito no ledger
e atualização de `budget_used` são persistidos na mesma transação. Repetir o
mesmo `external_id` retorna a venda existente sem pontuar novamente.

### Cancelamento e estorno

```bash
curl -i -X POST http://localhost:8080/sales/erp-sale-1001/cancel \
  -H "Authorization: Bearer <admin-token>"
```

O cancelamento marca a venda como `canceled`, cria um débito com os pontos do
crédito original e devolve esses pontos à verba da campanha na mesma transação.
Repetir a chamada retorna `200` sem criar outro débito. Uma venda inexistente
retorna `404`; uma venda aprovada fora da janela de 30 dias retorna `422`.

### Carteira do seller

```bash
curl -i http://localhost:8080/me/wallet \
  -H "Authorization: Bearer <seller-token>"
```

A resposta contém `balance` e `entries`. O saldo é calculado como créditos
menos débitos do ledger; o endpoint usa o seller do JWT e não aceita um
`seller_id` arbitrário. Admin recebe `403`.

### Verificação de autorização

```bash
backend/bin/test-http.sh
```

O script usa a API Dockerizada e verifica a rota `GET /admin/ping` sem token (`401`), com token de seller (`403`) e com token de admin (`200`).

O fluxo `backend/bin/test-products-http.sh` verifica autorização, validação, criação, listagem, edição, SKU duplicado e inativação idempotente contra a API e o MySQL Dockerizados.

O fluxo `backend/bin/test-campaigns-http.sh` verifica autorização, validação, criação e listagem de campanhas contra a API e o MySQL Dockerizados.

O fluxo `backend/bin/test-sales-http.sh` verifica autorização, validação,
criação, idempotência, conflito de identificador e verba insuficiente.

O fluxo `backend/bin/test-cancellations-http.sh` verifica autorização,
cancelamento, estorno, venda inexistente e repetição idempotente.

O fluxo `backend/bin/test-wallet-http.sh` verifica autorização, cálculo do
saldo, ownership entre sellers, crédito, estorno e repetição idempotente.

O admin pode cadastrar sellers pela tela **Usuários**. O formulário de venda
gera automaticamente o `external_id` técnico e permite escolher o seller por
nome e e-mail; a API continua recebendo o campo para preservar a idempotência.

O fluxo `backend/bin/test-users-http.sh` verifica cadastro e listagem de sellers,
incluindo `401`, `403`, `409`, `422` e ausência de `password_hash`.

O fluxo `backend/bin/test-sales-history-http.sh` verifica a listagem protegida,
os nomes contextuais, os pontos calculados e o cancelamento pela venda listada.

O fluxo `backend/bin/test-concurrency-http.sh` verifica duas vendas concorrentes
disputando a mesma verba e dois cancelamentos concorrentes da mesma venda. O
caso de uso repete transações que recebem deadlock transitório e retorna `503`
em JSON se a concorrência persistir.

## Testes

Verificar o frontend:

```bash
cd frontend
npm run test
npm run lint
npm run build
```

O build é servido pelo Vite em desenvolvimento e o proxy mantém as chamadas
da interface no mesmo host da aplicação.

Executar a suíte PHPUnit dentro do container:

```bash
docker compose run --rm --no-deps backend vendor/bin/phpunit
```

O projeto também possui testes unitários para:

- Health controller;
- Router;
- Emissão e validação de JWT;
- Login válido e inválido.
- Principal autenticado e validação de papel;
- Autenticação de rotas com Bearer token;
- Pipeline de middlewares do router, incluindo os cenários `401` e `403`.
- Verificação HTTP real da rota protegida com `curl`.
- CRUD de produtos e inativação lógica com teste HTTP real.
- Criação e listagem de campanhas com teste HTTP real.
- Registro de vendas e regras transacionais pelo teste HTTP Dockerizado.
- Cancelamento, estorno e idempotência pelo teste HTTP Dockerizado.
- Carteira, extrato, ownership e saldo derivado do ledger pelo teste HTTP Dockerizado.
- Concorrência de verba e cancelamento pelo teste HTTP Dockerizado.
- Conversor CSV do histórico com teste automatizado do frontend.

## Banco de dados

- Schema: `backend/database/schema.sql`;
- Migration e seed: `backend/bin/migrate.php`;
- Conexão PDO: `backend/src/Infrastructure/Database/ConnectionFactory.php`.

O saldo da carteira é calculado pelo ledger. Pontos e atualização de verba da
venda são persistidos na mesma transação.

## Arquitetura

```text
backend/src/
├── Application/      # Casos de uso
├── Domain/           # Regras de negócio
├── Http/             # Router, controllers e middleware
└── Infrastructure/  # PDO, repositories, JWT e integrações
```

Documentação complementar:

- `DESAFIO-TORO.md`: enunciado original;
- `docs/specs/`: especificações funcionais;
- `docs/decisions/`: decisões arquiteturais;
- `AGENTS.md`: regras de desenvolvimento do projeto;
- `memory.md`: estado e próximas tarefas.

## Próximas etapas opcionais

- gerar o SKU também no backend e permitir omissão do campo na API;
- adicionar OpenAPI/Swagger;
- adicionar paginação e filtros ao histórico;
- exportar grandes volumes de vendas pelo backend.
