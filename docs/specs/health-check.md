# Especificação — Health check

## Objetivo

Disponibilizar um endpoint mínimo para validar que o container do backend está respondendo.

## Contrato

```http
GET /health
```

Resposta esperada:

```json
{
  "status": "ok"
}
```

Status HTTP: `200`.

## Critérios de aceite

- O endpoint não exige autenticação.
- A resposta possui `Content-Type: application/json`.
- O teste deve falhar antes da implementação e passar depois dela.
