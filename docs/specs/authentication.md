# Especificação — Autenticação e ACL

## Login

```http
POST /auth/login
Content-Type: application/json

{
  "email": "admin@toro.local",
  "password": "admin123"
}
```

Resposta válida: `200` com um JWT no campo `token`.

Credenciais inválidas devem retornar `401` sem revelar se o email existe.

## Autenticação

Rotas protegidas exigem:

```http
Authorization: Bearer <token>
```

Token ausente, malformado, inválido ou expirado retorna `401`.

## Autorização

- Rotas administrativas exigem `role = admin`.
- Seller acessando uma rota administrativa recebe `403`.
- A identidade e o papel devem vir do token validado, nunca do body.

## Critérios de aceite

- A senha é verificada com `password_verify`.
- O token contém o ID do usuário e o papel.
- `password_hash` nunca aparece nas respostas.
- Um token alterado não é aceito.
- O middleware separa autenticação (`401`) de autorização (`403`).
