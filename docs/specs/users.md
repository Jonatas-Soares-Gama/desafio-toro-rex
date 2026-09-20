# Especificação — Cadastro de sellers pelo admin

## Escopo

Administradores podem cadastrar sellers e listar os sellers disponíveis para
lançamento de vendas. Esta primeira versão não cria novos admins, não exclui
usuários e não altera usuários existentes.

## Autorização

As rotas exigem JWT com `role = admin`:

- sem token ou token inválido: `401`;
- token válido de seller: `403`.

## Endpoints

```text
POST /users
GET  /users/sellers
```

### Criar seller

Request:

```json
{
  "name": "Seller Novo",
  "email": "seller.novo@toro.local",
  "password": "seller123"
}
```

Regras:

- `name` é texto não vazio com no máximo 120 caracteres;
- `email` é válido e único;
- `password` tem pelo menos 8 caracteres;
- o papel persistido é sempre `seller`, sem aceitar `role` do cliente;
- a senha é persistida somente como hash;
- sucesso retorna `201` com `id`, `name`, `email`, `role` e `created_at`;
- e-mail duplicado retorna `409`;
- dados inválidos retornam `422`.

### Listar sellers

Retorna `200` com sellers ordenados por nome. A resposta nunca expõe
`password_hash`.

```json
{
  "sellers": [
    {
      "id": 2,
      "name": "Seller Um",
      "email": "seller1@toro.local",
      "role": "seller",
      "created_at": "2026-09-19 12:00:00"
    }
  ]
}
```

## Critérios de aceite

- somente admin cria e lista sellers;
- seller recebe `403`;
- request sem token recebe `401`;
- e-mail duplicado não cria novo usuário;
- senha nunca aparece na resposta;
- seller criado aparece na listagem e pode ser selecionado no lançamento de
  venda.
