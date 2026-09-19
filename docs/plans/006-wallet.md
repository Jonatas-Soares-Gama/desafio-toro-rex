# Plano 006 — Carteira e extrato do seller

Status: concluído.

## Objetivo

Implementar `GET /me/wallet` para que o seller autenticado consulte seu saldo
calculado pelo ledger e o extrato completo, preservando ownership e sem criar
um campo mutável de saldo.

## Escopo

Incluído:

1. leitura das entradas de `wallet_entries` filtradas pelo seller do JWT;
2. cálculo de `credit - debit` no caso de uso;
3. endpoint `GET /me/wallet` protegido por JWT e `role = seller`;
4. resposta com saldo inteiro e entradas ordenadas do mais recente para o mais
   antigo;
5. teste HTTP contra API e MySQL reais;
6. atualização de README, requests e memória.

Fora deste plano:

- alteração de schema;
- saldo cacheado;
- paginação ou filtros;
- carteira administrativa;
- frontend React;
- saque, transferência ou edição do ledger.

## Sequência TDD

### 1. Especificação e contrato

- manter `docs/specs/wallet.md` como contrato do endpoint;
- confirmar que o `seller_id` vem exclusivamente de
  `AuthenticatedPrincipal::userId`;
- transformar os critérios de aceite em verificações antes da implementação.

### 2. Leitura do ledger

- adicionar ao `SalesRepository` uma leitura preparada das entradas por
  `seller_id`, reutilizando o repository que já grava créditos e débitos;
- selecionar somente os campos do contrato;
- ordenar por `created_at DESC, id DESC`;
- retornar lista vazia para seller sem movimentações;
- não criar `WalletRepository`, interface ou tabela nova sem uma necessidade
  concreta.

### 3. Caso de uso

- criar o mínimo necessário em `Application/Wallet` para consultar as entradas
  e calcular o saldo;
- somar pontos de `credit` e subtrair pontos de `debit` usando exatamente a
  lista devolvida para o extrato;
- rejeitar identificador inválido apenas como defesa interna; a autenticação já
  valida o principal;
- manter a operação somente de leitura, sem transação de escrita e sem aceitar
  saldo do cliente.

### 4. HTTP e ACL

- criar o controller da carteira;
- obter o principal da request e usar seu `userId` como filtro;
- registrar `GET /me/wallet` com `AuthenticationMiddleware` e
  `RoleMiddleware('seller')`;
- serializar somente `balance` e `entries`;
- mapear ausência de autenticação para `401` e admin para `403`.

### 5. Verificação

- seller sem entradas: saldo zero e lista vazia;
- venda aprovada: crédito aparece e saldo aumenta;
- venda cancelada: débito aparece com os mesmos pontos e saldo é reduzido;
- cancelamento repetido: não há segundo débito;
- seller 1 não recebe entradas do seller 2;
- admin recebe `403` e request sem token recebe `401`;
- ordem do extrato permanece determinística;
- executar PHPUnit e criar `backend/bin/test-wallet-http.sh` contra o ambiente
  Dockerizado.

## Definição de pronto

- spec e plano versionados;
- `GET /me/wallet` funcionando contra MySQL real;
- saldo calculado exclusivamente do ledger;
- ownership derivado do JWT, sem parâmetro substituível;
- carteira vazia, crédito, débito e idempotência cobertos;
- respostas `401` e `403` verificadas;
- README, `requests/api.http` e `memory.md` atualizados;
- testes disponíveis executados e evidências registradas neste plano.

## Riscos controlados

- vazamento entre sellers: filtro obrigatório pelo `sub` autenticado;
- saldo divergente do extrato: cálculo sobre o mesmo conjunto de entradas;
- duplicação de estorno: responsabilidade permanece no cancelamento e na
  constraint existente `(sale_id, type)`;
- SQL inseguro: prepared statement com valor de seller parametrizado;
- complexidade prematura: sem cache, paginação ou novo repository neste ciclo.

## Evidências

- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 31 testes,
  47 assertions;
- `docker compose exec -T backend /app/bin/test-wallet-http.sh` → `401`,
  `403`, ownership, crédito, estorno e repetição idempotente aprovados;
- `docker compose run --rm --no-deps backend php -l ...` → arquivos PHP da
  task sem erros de sintaxe;
- `git diff --check` → sem erros de whitespace.

## Arquivos previstos

- `docs/specs/wallet.md`;
- `docs/plans/006-wallet.md`;
- `backend/src/Infrastructure/Persistence/SalesRepository.php`;
- `backend/src/Application/Wallet/WalletService.php`;
- `backend/src/Http/Controllers/WalletController.php`;
- `backend/public/index.php`;
- `backend/bin/test-wallet-http.sh`;
- testes e documentação de uso.
