# Plano 007 — Testes de concorrência do motor

Status: concluído.

## Objetivo

Adicionar uma verificação reproduzível para as invariantes transacionais de
vendas e cancelamentos concorrentes, sem alterar as regras de negócio já
implementadas.

## Escopo

Incluído:

1. teste de duas vendas concorrentes disputando uma campanha de 100 pontos;
2. teste de dois cancelamentos concorrentes da mesma venda;
3. validação do orçamento e do ledger após as requisições;
4. configuração do servidor PHP com múltiplos workers no Compose;
5. documentação da execução e das evidências.

Fora deste plano:

- mudança no algoritmo transacional;
- nova API ou alteração de payload;
- teste de carga/performance;
- stress test prolongado;
- concorrência do frontend.

## Sequência

### 1. Especificação

- registrar os cenários em `docs/specs/concurrency.md`;
- reutilizar as decisões existentes de rejeição integral, ledger e locks;
- criar o teste antes de alterar configuração de execução.

### 2. Harness HTTP

- criar `backend/bin/test-concurrency-http.sh`;
- criar produto e campanhas com IDs únicos;
- disparar as requisições com processos `curl` em paralelo;
- aceitar somente o resultado `201 + 422` para a disputa de verba;
- aceitar somente `200 + 200` e um único débito para o cancelamento;
- consultar campanhas e carteira para validar o estado persistido.

### 3. Ambiente

- configurar `PHP_CLI_SERVER_WORKERS` no Compose com padrão 4;
- manter o valor ajustável pelo ambiente;
- não introduzir dependência externa ou ferramenta adicional.

### 4. Verificação

- executar PHPUnit;
- subir o Compose reconstruído;
- executar o teste de concorrência pelo container backend;
- repetir o teste para verificar que IDs únicos evitam interferência;
- executar `git diff --check`.

## Definição de pronto

- spec e plano versionados;
- duas vendas concorrentes nunca excedem a verba;
- dois cancelamentos concorrentes produzem no máximo um débito;
- PHPUnit e teste de concorrência passam;
- README e `memory.md` apontam para a próxima tarefa;
- nenhuma alteração é commitada.

## Evidências

- `docker compose exec -T backend /app/bin/test-concurrency-http.sh` → passou
  duas vezes consecutivas;
- vendas concorrentes → exatamente uma `201` e uma `422`, orçamento final em
  100 pontos;
- cancelamentos concorrentes → dois `200`, um único débito e orçamento final
  em zero;
- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 31 testes,
  47 assertions;
- `git diff --check` → sem erros de whitespace.

## Arquivos previstos

- `docs/specs/concurrency.md`;
- `docs/plans/007-concurrency-tests.md`;
- `backend/bin/test-concurrency-http.sh`;
- `docker-compose.yml`;
- `README.md`;
- `memory.md`.
