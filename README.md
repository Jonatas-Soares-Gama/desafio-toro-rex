# Vendeu, Ganhou

Plataforma de incentivo de vendas desenvolvida para o desafio técnico de desenvolvedor(a) pleno.

O sistema permite que administradores cadastrem produtos e campanhas, lancem vendas para sellers e acompanhem os pontos distribuídos. O seller acessa apenas a própria carteira e o respectivo extrato.

O foco da implementação está no motor de pontuação, na consistência transacional da verba e do ledger, na autorização por papel e em uma execução simples por Docker Compose.

## Resultado da entrega

O projeto contempla os fluxos principais do desafio:

- login com JWT e senhas protegidas por `password_hash`;
- autorização por papel, diferenciando `401 Unauthorized` de `403 Forbidden`;
- CRUD de produtos com inativação lógica;
- criação e acompanhamento de campanhas com orçamento;
- lançamento de vendas com cálculo de pontos, idempotência e controle de verba;
- cancelamento idempotente com estorno no ledger;
- carteira do seller calculada a partir do ledger;
- histórico administrativo de vendas, cancelamento por linha e exportação CSV;
- cadastro administrativo de sellers;
- proteção contra concorrência no consumo e na devolução de verba;
- frontend React para os fluxos de admin e seller;
- testes unitários, testes HTTP contra MySQL real e testes automatizados do frontend.

## Como a solução atende aos critérios de avaliação

| Critério | Como foi tratado |
|---|---|
| Motor de pontuação — 30% | Pontos calculados por `quantity * points_per_unit`; venda acima da verba é rejeitada integralmente; venda, crédito no ledger e consumo da campanha usam uma única transação. |
| Segurança e ACL — 20% | JWT assinado e validado, expiração, papéis `admin`/`seller`, ownership da carteira, prepared statements, validação no servidor e nenhuma exposição de `password_hash`. |
| Qualidade do código — 20% | PHP 8 puro, PDO, separação entre Domain, Application, Infrastructure e Http, responsabilidades pequenas e regras de negócio fora dos controllers. |
| Facilidade para rodar — 15% | Docker Compose, MySQL, migration/seed no boot, credenciais de demonstração, `.env.example` e comandos equivalentes no Makefile. |
| Frontend — 15% | React + TypeScript com login, área administrativa, carteira do seller, estados de carregamento/erro, tratamento de `401`/`403` e exportação CSV. |

## Regras de negócio importantes

### Pontuação e verba

No momento da aprovação da venda:

~~~text
points = quantity * product.points_per_unit
~~~

Se `budget_used + points` ultrapassar `budget_total`, a venda inteira é rejeitada com `422`. Não existe crédito parcial.

Quando a venda cabe na verba, estas três operações são confirmadas juntas:

1. criação da venda como `approved`;
2. criação do crédito no `wallet_entries`;
3. incremento de `campaigns.budget_used`.

Uma falha em qualquer etapa executa `ROLLBACK`.

### Idempotência

`sales.external_id` possui índice único. Repetir uma venda com o mesmo identificador e os mesmos dados retorna a venda já existente sem novo crédito ou consumo de verba. Reutilizar o identificador com dados diferentes retorna `409`.

### Cancelamento e estorno

O cancelamento:

- exige papel `admin`;
- bloqueia a venda com `SELECT ... FOR UPDATE`;
- usa os pontos do crédito original no ledger;
- cria um débito com o mesmo valor;
- devolve os pontos à verba da campanha;
- confirma status, débito e devolução na mesma transação.

O cancelamento é idempotente. Repetir a operação não cria outro débito nem devolve verba novamente.

Este projeto definiu uma janela adicional de 30 dias para cancelamento. O limite é exclusivo: uma venda com `now >= created_at + 30 dias` retorna `422`.

### Ledger e histórico

O saldo não é armazenado em um campo mutável. Ele é calculado como:

~~~text
saldo = soma dos créditos - soma dos débitos
~~~

Os pontos históricos vêm do crédito persistido. Portanto, editar ou inativar um produto não altera vendas já aprovadas, seu histórico ou seu estorno.

## Stack e arquitetura

- Backend: PHP 8.3 puro, sem framework full-stack;
- Banco: MySQL 8.4;
- Persistência: PDO com prepared statements e `ATTR_EMULATE_PREPARES = false`;
- Frontend: React 19, TypeScript e Vite;
- Autenticação: JWT com `firebase/php-jwt`;
- Infraestrutura: Docker Compose.

~~~text
backend/src/
├── Domain/           # Entidades e regras puras de negócio
├── Application/      # Casos de uso e orquestração
├── Infrastructure/   # PDO, repositories, JWT e persistência
└── Http/             # Router, controllers, middleware e respostas

frontend/src/
├── features/         # auth, products, campaigns, sales, users e wallet
├── components/       # componentes compartilhados
└── lib/              # API, autenticação, erros e CSV
~~~

As transações de venda e cancelamento são coordenadas nos casos de uso. O bloqueio pessimista da campanha impede que vendas concorrentes ultrapassem a verba disponível. Deadlocks transitórios (`1213`/`40001`) recebem retry limitado.

## Como executar

### Pré-requisitos

- Docker;
- Docker Compose;
- Git.

Não é necessário instalar PHP ou Composer na máquina host para executar o backend.

### Subir o ambiente

Na raiz do projeto:

~~~bash
cp .env.example .env
docker compose up --build
~~~

Serviços disponíveis:

- Frontend: http://localhost:5173
- Backend: http://localhost:8080
- MySQL: localhost:3306

O backend aguarda o MySQL ficar saudável, executa `backend/bin/migrate.php` e inicia o servidor PHP. O script cria o schema e aplica o seed de demonstração.

O frontend usa o proxy do Vite para encaminhar as chamadas de `/auth`, `/products`, `/campaigns`, `/users`, `/sales` e `/me` para o backend. Não é necessário configurar CORS no ambiente local.

### Configuração

O arquivo `.env` concentra as credenciais locais do MySQL, os parâmetros de conexão e o segredo JWT. Ele não deve ser versionado.

Para uso real, substitua todos os valores de desenvolvimento, principalmente `JWT_SECRET`, por credenciais e um segredo aleatórios. O segredo JWT precisa ter pelo menos 32 caracteres.

### Parar e reiniciar

~~~bash
docker compose down
~~~

Para remover também o volume do banco e recriar tudo do zero:

~~~bash
docker compose down -v
docker compose up --build
~~~

O segundo comando apaga os dados locais persistidos no volume do MySQL.

## Credenciais de demonstração

As credenciais são criadas por `backend/bin/migrate.php`. As senhas são armazenadas usando `password_hash`.

| Papel | E-mail | Senha |
|---|---|---|
| Admin | `admin@toro.local` | `admin123` |
| Seller | `seller1@toro.local` | `seller123` |
| Seller | `seller2@toro.local` | `seller123` |

O seed também cria dois produtos e uma campanha inicial com orçamento de 10.000 pontos.

## Makefile

O Makefile é um bônus para reduzir os comandos repetitivos durante a avaliação. Todos os comandos abaixo devem ser executados na raiz:

| Comando | Função |
|---|---|
| `make up` | Sobe o ambiente e reconstrói as imagens. |
| `make up-d` | Sobe o ambiente em segundo plano. |
| `make build` | Apenas reconstrói as imagens. |
| `make down` | Para e remove os containers, preservando o volume do banco. |
| `make logs` | Acompanha os logs do backend. |
| `make test-unit` | Executa o PHPUnit dentro de um container temporário. |
| `make test-http` | Executa todos os fluxos HTTP contra a API Dockerizada. |
| `make test` | Executa `test-unit` e `test-http`. |

O fluxo mais direto para uma avaliação é:

~~~bash
cp .env.example .env
make up-d
make test
~~~

## Testes

### Testes unitários do backend

Os testes unitários usam PHPUnit e não dependem de um banco externo. O PHPUnit é executado dentro de um container que contém PHP, Composer e as dependências do projeto.

~~~bash
make test-unit
~~~

Comando equivalente:

~~~bash
docker compose run --rm --no-deps backend vendor/bin/phpunit
~~~

Também é possível executar diretamente dentro de um backend já iniciado:

~~~bash
docker compose exec -T backend vendor/bin/phpunit
~~~

A suíte cobre, entre outros pontos:

- regras de produto, campanha e venda;
- cálculo de pontos e janela de cancelamento;
- emissão e validação de JWT;
- login com credenciais válidas e inválidas;
- principal autenticado e validação de papel;
- middleware de autenticação e autorização;
- router e composição dos middlewares.

### Testes HTTP e integração

Os testes HTTP usam a API real, o PHP em execução e o MySQL do Compose. Eles criam dados com identificadores próprios para poderem ser repetidos sem depender de fixtures frágeis.

Com o ambiente em execução:

~~~bash
make test-http
~~~

O comando executa estes fluxos:

| Script | Cenários verificados |
|---|---|
| `bin/test-http.sh` | `401`, `403` e `200` na rota administrativa protegida. |
| `bin/test-users-http.sh` | Cadastro/listagem de sellers, validações, duplicidade e ausência de `password_hash`. |
| `bin/test-products-http.sh` | CRUD, SKU duplicado, validação e inativação lógica. |
| `bin/test-campaigns-http.sh` | Autorização, validação, criação e listagem de campanhas. |
| `bin/test-sales-http.sh` | Lançamento, idempotência, conflito de `external_id` e verba insuficiente. |
| `bin/test-sales-history-http.sh` | Histórico protegido, contexto da venda, pontos do crédito original após edição do produto e cancelamento. |
| `bin/test-cancellations-http.sh` | Cancelamento, estorno, venda inexistente e repetição idempotente. |
| `bin/test-wallet-http.sh` | Saldo derivado do ledger, crédito, débito, ownership e proteção contra `seller_id` arbitrário. |
| `bin/test-concurrency-http.sh` | Duas vendas concorrentes disputando a verba e dois cancelamentos concorrentes da mesma venda. |

Para executar um fluxo isolado:

~~~bash
docker compose exec -T backend sh -c \
  'BASE_URL=http://127.0.0.1:8080 sh bin/test-sales-http.sh'
~~~

### Testes do frontend

O frontend possui testes automatizados para autenticação, geração de SKU e conversão/exportação CSV.

Com Node.js 22 ou superior instalado:

~~~bash
cd frontend
npm ci
npm run test
npm run lint
npm run build
~~~

Também é possível executar os comandos usando a imagem do frontend:

~~~bash
docker compose run --rm --no-deps frontend npm run test
docker compose run --rm --no-deps frontend npm run lint
docker compose run --rm --no-deps frontend npm run build
~~~

## API principal

| Método | Rota | Acesso | Finalidade |
|---|---|---|---|
| `GET` | `/health` | Público | Health check. |
| `POST` | `/auth/login` | Público | Login e emissão do JWT. |
| `GET/POST/PUT/DELETE` | `/products` | Admin | Produtos e inativação lógica. |
| `GET/POST` | `/campaigns` | Admin | Campanhas e acompanhamento da verba. |
| `GET/POST` | `/users` | Admin | Cadastro/listagem de sellers. |
| `GET/POST` | `/sales` | Admin | Histórico e lançamento de vendas. |
| `POST` | `/sales/{external_id}/cancel` | Admin | Cancelamento e estorno. |
| `GET` | `/me/wallet` | Seller | Saldo e extrato do seller autenticado. |

O arquivo [`requests/api.http`](requests/api.http) reúne exemplos das principais requisições para uso em clientes compatíveis com o formato `.http`.

### Login

~~~bash
curl -i -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@toro.local","password":"admin123"}'
~~~

Uma resposta válida retorna `200` com um JWT no campo `token`. Credenciais inválidas retornam `401`; campos ausentes ou inválidos retornam `422`.

### Lançamento de venda

~~~bash
curl -i -X POST http://localhost:8080/sales \
  -H "Authorization: Bearer <admin-token>" \
  -H "Content-Type: application/json" \
  -d '{"external_id":"erp-sale-1001","campaign_id":1,"seller_id":2,"product_id":1,"quantity":3,"unit_value":"149.90"}'
~~~

O retorno inclui a venda persistida e `points`. Repetir o mesmo `external_id` retorna `200` sem duplicar o crédito.

### Cancelamento

~~~bash
curl -i -X POST http://localhost:8080/sales/erp-sale-1001/cancel \
  -H "Authorization: Bearer <admin-token>"
~~~

O retorno contém a venda cancelada e `reversed_points`, sempre baseado no crédito original do ledger.

### Carteira

~~~bash
curl -i http://localhost:8080/me/wallet \
  -H "Authorization: Bearer <seller-token>"
~~~

O endpoint usa o seller presente no JWT. Não aceita um `seller_id` enviado pelo cliente para consultar a carteira de outra pessoa. Admin recebe `403`.

## Frontend

O frontend apresenta áreas diferentes conforme o papel autenticado:

### Admin

- Produtos: criação, edição e inativação;
- Campanhas: criação e acompanhamento de `budget_used / budget_total`;
- Vendas: seleção de seller, produto e campanha, lançamento, histórico, cancelamento e exportação CSV;
- Usuários: cadastro e listagem de sellers.

### Seller

- acesso somente à própria carteira;
- saldo calculado pelo ledger;
- extrato com créditos e débitos;
- tratamento de sessão expirada e ausência de permissão.

## Banco e persistência

- Schema: `backend/database/schema.sql`;
- Bootstrap/migration e seed: `backend/bin/migrate.php`;
- Conexão PDO: `backend/src/Infrastructure/Database/ConnectionFactory.php`;
- Foreign keys, índices, constraints de valores positivos e unicidade de `external_id`;
- valores monetários armazenados em `DECIMAL`, nunca em `float`;
- pontos e quantidades armazenados como inteiros positivos.

## Decisões relevantes

As decisões de domínio mais importantes estão documentadas em [`docs/decisions/`](docs/decisions/):

- política de rejeição integral quando a verba é insuficiente;
- ledger como fonte da verdade do saldo;
- idempotência de vendas e cancelamentos;
- autenticação JWT;
- pontos de estorno vindos do crédito original;
- janela de cancelamento de 30 dias.

## Próximos passos possíveis

Com mais tempo, os próximos incrementos seriam paginação e filtros no histórico, OpenAPI/Swagger, auditoria de quem lançou cada venda e uma estratégia de migration versionada para ambientes já existentes.
