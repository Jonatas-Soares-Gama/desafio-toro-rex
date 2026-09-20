# Plano 010 — Histórico de vendas e cancelamento orientado

Status: concluído.

## Objetivo

Permitir que o admin encontre uma venda pelo contexto visível da operação e a
cancele pela própria linha, sem precisar conhecer ou digitar o
`external_id`. O contrato atual de cancelamento será preservado no backend.

## Escopo

### Backend

Adicionar `GET /sales`, protegido por JWT e papel `admin`.

A resposta deve trazer as vendas mais recentes primeiro, com os dados
necessários para leitura operacional:

```json
{
  "sales": [
    {
      "id": 12,
      "external_id": "manual-uuid",
      "campaign_id": 1,
      "campaign_name": "Campanha de Setembro",
      "seller_id": 2,
      "seller_name": "Seller Um",
      "product_id": 3,
      "product_name": "Café Gourmet 500g",
      "quantity": 2,
      "unit_value": "149.90",
      "points": 20,
      "status": "approved",
      "created_at": "2026-09-19 12:00:00"
    }
  ]
}
```

O backend deve calcular `points` a partir de `quantity * products.points_per_unit`
na consulta. Nenhum novo campo ou migration é necessário.

### Frontend

Na tela **Vendas**:

1. manter o formulário de lançamento existente;
2. substituir o painel de cancelamento manual por uma tabela de histórico;
3. exibir seller, produto, campanha, quantidade, pontos, status e data;
4. mostrar o `external_id` apenas como informação técnica secundária;
5. exibir o botão **Cancelar** apenas para vendas `approved`;
6. pedir confirmação antes do cancelamento;
7. após cancelar, atualizar a linha sem recarregar a página inteira;
8. manter estados de carregamento, erro, lista vazia e operação em andamento.

## Decisões conservadoras

- `POST /sales/{external_id}/cancel` permanece igual;
- o frontend continua usando o `external_id` internamente para chamar o
  cancelamento;
- não haverá paginação, filtros ou busca nesta fatia;
- a API retorna somente dados necessários para a área administrativa;
- o histórico não expõe `password_hash` nem informações sensíveis;
- não será criado endpoint de edição ou exclusão de vendas.

## Implementação proposta

### Backend

1. Adicionar `SalesRepository::allWithContext()` usando `JOIN` com
   `users`, `products` e `campaigns`.
2. Mapear o resultado para uma estrutura de leitura, sem alterar a entidade
   `Sale` usada pelas regras transacionais.
3. Adicionar `SalesService::list()` para orquestrar a consulta.
4. Adicionar `SalesController::list()` com resposta `200`.
5. Registrar `GET /sales` no router com o middleware administrativo existente.
6. Cobrir autenticação, autorização, ordenação e campos retornados.

### Frontend

1. Adicionar `SaleListItem` aos tipos compartilhados.
2. Adicionar `api.listSales(token)`.
3. Carregar o histórico em paralelo com produtos, campanhas e sellers.
4. Criar a tabela responsiva na `SalesPage`.
5. Reutilizar `api.cancelSale()` com o `external_id` da linha.
6. Atualizar o item cancelado localmente para `canceled` e exibir os pontos
   estornados.
7. Remover o estado e o formulário de cancelamento manual.

## Verificação executada

- PHPUnit: 31 testes, 47 assertions;
- teste HTTP do histórico: `401`, `403`, `200`, contexto da venda e
  cancelamento pela linha;
- fluxos HTTP existentes de vendas, cancelamento e carteira mantidos verdes;
- frontend: testes, lint e build executados com sucesso;
- Docker Compose rebuild concluído com backend e frontend saudáveis.

## Critérios de aceite

- admin sem token recebe `401`;
- seller recebe `403` ao consultar o histórico;
- admin recebe `200` e as vendas vêm da mais recente para a mais antiga;
- cada linha mostra seller, produto, campanha, quantidade, pontos, status e
  data;
- `external_id` não é exigido como entrada do operador;
- venda aprovada pode ser cancelada pela linha;
- venda cancelada não apresenta novamente a ação de cancelamento;
- confirmação impede cancelamento acidental;
- erro no cancelamento mantém a linha aprovada e informa o problema;
- cancelamento bem-sucedido atualiza status e feedback sem duplicar requisições;
- estado vazio e estado de carregamento são tratados;
- o contrato e a idempotência do endpoint de cancelamento permanecem intactos.

## Testes e verificação

### Backend

- teste unitário do mapeamento da listagem;
- teste HTTP de `401`, `403`, `200` e ordenação;
- teste HTTP de cancelamento pela venda listada;
- PHPUnit completo.

### Frontend

- teste do formato dos dados exibidos quando houver vendas aprovadas e
  canceladas;
- lint e build;
- verificação manual da confirmação, erro, loading e lista vazia.

## Fora desta task

- paginação e filtros avançados;
- busca por seller ou produto;
- exportação CSV;
- criação de novos admins;
- geração de SKU no backend;
- alteração do contrato de `external_id`.
