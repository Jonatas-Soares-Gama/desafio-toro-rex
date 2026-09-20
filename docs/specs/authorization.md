# Especificação — Middleware JWT e ACL

## Objetivo

Proteger rotas da API com autenticação JWT e autorização por papel, mantendo a identidade da requisição exclusivamente derivada do token validado.

## Vocabulário

- **Autenticação**: confirmar que o token foi emitido pelo sistema e ainda é válido.
- **Autorização**: confirmar que o usuário autenticado pode executar a ação.
- **Principal**: identidade autenticada disponibilizada para o restante da requisição (`id` e `role`).

## Header obrigatório

Rotas protegidas devem receber:

```http
Authorization: Bearer <jwt>
```

O esquema deve ser `Bearer`, sem aceitar o token no query string ou no body.

## Respostas de erro

### `401 Unauthorized`

Usado quando a requisição não apresenta uma identidade autenticada:

- Header ausente;
- Header sem esquema `Bearer`;
- Token vazio ou malformado;
- Assinatura inválida;
- Token expirado;
- Claims obrigatórias ausentes ou inválidas;
- Usuário não encontrado ou inativo, quando essa verificação for aplicada.

A resposta não deve revelar qual parte do token falhou:

```json
{
  "error": "Unauthenticated"
}
```

### `403 Forbidden`

Usado quando o token é válido, mas o papel não tem permissão para a rota:

```json
{
  "error": "Forbidden"
}
```

## Claims aceitas

O middleware deve exigir:

- `sub`: inteiro positivo com o ID do usuário;
- `role`: `admin` ou `seller`;
- `exp`: validado pela biblioteca JWT.

O middleware não deve aceitar `role` vindo do body, query string ou header adicional.

## ACL inicial

| Recurso | Admin | Seller |
|---|---:|---:|
| Login | sim | sim |
| Health check | sim | sim |
| CRUD de produtos | sim | não |
| Criar/listar campanhas administrativas | sim | não |
| Registrar venda | sim | não |
| Listar vendas administrativas | sim | não |
| Cancelar venda | sim | não |
| Cadastrar seller | sim | não |
| Listar sellers | sim | não |
| Consultar própria carteira | sim | sim |
| Consultar carteira de outro vendedor | não aplicável | nunca |

## Ownership

O endpoint de carteira do seller deve usar o `sub` do token como `seller_id`. Não deve aceitar um ID arbitrário do cliente para substituir essa identidade.

Se uma rota administrativa precisar consultar dados de um vendedor, ela deve ter uma regra explícita de autorização. O fato de um cliente enviar `seller_id` no body não concede acesso.

## Rotas públicas e protegidas

Públicas:

```text
GET  /health
POST /auth/login
```

Protegidas por JWT:

```text
GET  /admin/ping             admin (smoke check)
```

Rotas de negócio protegidas por JWT:

```text
POST /products             admin
GET  /products             admin
PUT  /products/{id}        admin
DELETE /products/{id}      admin
POST /campaigns            admin
GET  /campaigns            admin
POST /sales                admin
GET  /sales                admin
POST /sales/{external_id}/cancel  admin
POST /users                    admin
GET  /users/sellers            admin
GET  /me/wallet            seller autenticado
```

## Critérios de aceite

- Uma rota pública continua acessível sem token.
- Uma rota protegida sem token retorna `401`.
- Uma rota protegida com token adulterado retorna `401`.
- Uma rota admin com token de seller retorna `403`.
- Uma rota admin com token de admin prossegue para o controller.
- O principal contém somente a identidade derivada do JWT validado.
- O seller não consegue substituir seu próprio ID pelo ID de outro seller.
- Erros de autenticação não expõem detalhes criptográficos ou existência de usuários.
