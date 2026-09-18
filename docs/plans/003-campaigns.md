# Plano 003 — Campanhas

Status: concluído.

## Objetivo

Implementar a criação e a listagem administrativa de campanhas. O frontend
React será iniciado somente depois que vendas, cancelamento e carteira também
estiverem concluídos.

## Escopo

Incluído:

1. `POST /campaigns` para criar campanhas;
2. `GET /campaigns` para listar campanhas;
3. validação de nome, orçamento e período;
4. ACL JWT exclusiva para admin;
5. persistência com PDO e prepared statements;
6. retorno de `budget_total`, `budget_used` e `status`;
7. testes unitários e teste HTTP com API/MySQL reais;
8. atualização do README, requests e memória.

Fora deste plano:

- edição ou exclusão de campanhas;
- encerramento manual ou automático;
- consumo e devolução de verba;
- registro de vendas;
- carteira e ledger;
- frontend React.

## Sequência de implementação

1. Confirmar esta spec e o plano;
2. Criar testes de domínio e validação;
3. Implementar entidade/validação de campanha;
4. Implementar repository, caso de uso e controller;
5. Adicionar as rotas protegidas no front controller;
6. Adicionar requests manuais e teste HTTP Dockerizado;
7. Executar PHPUnit e o teste HTTP real;
8. Atualizar documentação e marcar o plano como concluído;
9. Fazer commit e push;
10. Após concluir vendas, cancelamento e carteira, iniciar separadamente o plano
    do frontend React.

## Definição de pronto

- Spec atendida sem aceitar `budget_used` do cliente;
- `401` sem token e `403` para seller;
- `201` na criação válida e `422` nos erros de validação;
- `GET /campaigns` retorna campanhas persistidas no MySQL;
- PHPUnit e teste HTTP real passando;
- arquivos modificados/criados listados no encerramento da task;
- commit realizado e push confirmado.

## Evidências

- `docker compose run --rm --no-deps --build backend vendor/bin/phpunit` → 25 testes, 39 assertions;
- `backend/bin/test-campaigns-http.sh` → verificou `401`, `403`, `422`, criação e listagem contra a API e o MySQL Dockerizados.
