# Especificação — CRUD de produtos

## Escopo

Administradores podem cadastrar, listar, editar e inativar produtos. Produtos não
são removidos fisicamente porque vendas futuras precisarão preservar o histórico.

## Autorização

Todas as rotas exigem `Authorization: Bearer <jwt>` com `role = admin`.

- Sem token ou token inválido: `401`;
- Token válido de seller: `403`.

## Endpoints

```text
POST   /products
GET    /products
PUT    /products/{id}
DELETE /products/{id}
```

### Criar

Request:

```json
{
  "name": "Produto A",
  "sku": "PROD-A",
  "points_per_unit": 100
}
```

Regras:

- `name` é texto não vazio;
- `sku` é texto não vazio e único; nesta interface ele é gerado automaticamente
  a partir do nome, mas a API continua recebendo o campo para preservar o
  contrato atual;
- `points_per_unit` é inteiro positivo;
- sucesso retorna `201` com o produto criado;
- SKU duplicado retorna `409`.

Na interface administrativa, o SKU é normalizado em maiúsculas, sem acentos e
com hífens enquanto o nome é digitado. O campo é somente leitura. Durante esta
fase a geração é do frontend; o backend continua protegendo a unicidade com a
constraint existente.

### Listar

Retorna `200` com todos os produtos, incluindo inativos, e os campos `id`,
`name`, `sku`, `points_per_unit`, `active` e `created_at`.

### Editar

Aceita os mesmos campos de criação. O SKU pode ser alterado, mas deve continuar
único. Produto inexistente retorna `404` e conflito de SKU retorna `409`.

### Inativar

`DELETE` altera `active` para `false` e mantém o registro no banco. A operação é
idempotente: repetir a chamada retorna `200` sem criar outro registro ou apagar
o histórico.

## Critérios de aceite

- Seller não consegue executar nenhuma operação de produto;
- dados inválidos retornam `422` sem escrita parcial;
- produto criado aparece na listagem;
- edição persiste os novos dados;
- exclusão lógica mantém o produto na listagem com `active = false`;
- o teste HTTP executa o fluxo contra MySQL e a API Dockerizados.
