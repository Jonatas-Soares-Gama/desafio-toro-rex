# Especificação — Criação e listagem de campanhas

## Escopo

Administradores podem criar e listar campanhas de incentivo. Cada campanha
possui uma verba total em pontos e um período de validade. O campo
`budget_used` é controlado pelo backend e começa em zero.

Esta task não implementa ainda vendas, consumo de verba, encerramento manual ou
alteração de campanhas. Esses comportamentos serão tratados junto do motor de
pontuação.

## Autorização

As rotas exigem `Authorization: Bearer <jwt>` com `role = admin`.

- Sem token ou token inválido: `401`;
- Token válido de seller: `403`;
- O papel nunca é aceito no body da requisição.

## Endpoints

```text
POST /campaigns
GET  /campaigns
```

### Criar campanha

Request:

```json
{
  "name": "Campanha de Primavera",
  "budget_total": 10000,
  "starts_at": "2026-10-01 00:00:00",
  "ends_at": "2026-10-31 23:59:59"
}
```

Regras:

- `name` é texto não vazio, com no máximo 160 caracteres;
- `budget_total` é inteiro positivo em pontos;
- `starts_at` e `ends_at` são datas no formato `Y-m-d H:i:s`;
- `ends_at` deve ser posterior a `starts_at`;
- `budget_used` não pode ser enviado pelo cliente;
- a campanha é criada com `budget_used = 0` e `status = active`;
- sucesso retorna `201` com a campanha criada;
- payload ausente, tipos inválidos ou período inválido retornam `422`.

Não há unicidade obrigatória para o nome da campanha. Campanhas com nomes iguais
são permitidas porque o schema não define essa restrição.

### Listar campanhas

Retorna `200` com todas as campanhas, ordenadas por `id`, incluindo:

- `id`;
- `name`;
- `budget_total`;
- `budget_used`;
- `starts_at`;
- `ends_at`;
- `status`;
- `created_at`.

O endpoint não aceita `budget_used` calculado pelo cliente e não expõe campos de
outras entidades.

## Persistência

O repository usa PDO com prepared statements. A constraint do banco continua
reforçando:

- `budget_used <= budget_total`;
- `ends_at > starts_at`.

O caso de uso não inicia transação para esta task porque cada operação grava uma
única linha. As transações serão coordenadas pelo caso de uso de venda quando a
campanha e o ledger forem alterados juntos.

## Critérios de aceite

- Admin consegue criar uma campanha válida;
- seller e requisições sem token não conseguem criar nem listar campanhas;
- toda campanha nova começa com verba usada zero e status `active`;
- dados inválidos não geram registro parcial;
- listagem retorna o orçamento total e o orçamento usado;
- o teste HTTP executa o fluxo contra a API e o MySQL Dockerizados.
