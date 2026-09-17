# Plano 001 — Autenticação JWT e ACL

Status: concluído.

## Objetivo

Implementar o ciclo completo de proteção de rotas sem misturar autenticação, autorização e regras de negócio.

## Sequência TDD

### Etapa 1 — Modelo do principal

- Criar um objeto simples para representar `userId` e `role`.
- Testar que ID inválido e papel desconhecido são rejeitados.

### Etapa 2 — Leitura do header

- Extrair `Authorization` de forma case-insensitive.
- Aceitar somente o formato `Bearer <token>`.
- Testar header ausente, esquema incorreto, token vazio e token válido.

### Etapa 3 — Middleware de autenticação

- Usar `JwtTokenService` para decodificar o token.
- Converter falhas da biblioteca em uma resposta genérica `401`.
- Disponibilizar o principal ao handler da rota.
- Garantir que nenhum dado de identidade seja lido do body.

### Etapa 4 — Middleware de papel

- Criar uma verificação reutilizável para `admin`.
- Responder `403` quando o principal for seller.
- Permitir a execução do handler para admin.

### Etapa 5 — Integração com o router

- Adicionar suporte a middleware por rota ou grupo.
- Manter `/health` e `/auth/login` públicos.
- Aplicar middleware às rotas protegidas.
- Testar precedência: `401` antes de `403`.

### Etapa 6 — Verificação HTTP

- Testar com curl ou arquivo `.http`.
- Login admin → rota admin retorna sucesso ou erro de validação do recurso.
- Login seller → rota admin retorna `403`.
- Sem token → rota protegida retorna `401`.
- O teste HTTP executável está em `backend/bin/test-http.sh`.

## Fora deste ciclo

- CRUD de produtos;
- Campanhas;
- Registro de vendas;
- Carteira;
- Refresh token;
- Revogação de tokens e blacklist.

## Definição de pronto

- Spec atualizada;
- Testes unitários do principal, parser de header e middlewares;
- Pelo menos um teste HTTP para `401` e outro para `403`;
- `docker compose run --rm --no-deps backend vendor/bin/phpunit` passando;
- README e `memory.md` atualizados;
- Nenhum segredo real versionado.

## Evidências

- `docker compose run --rm --no-deps backend vendor/bin/phpunit` → 14 testes, 24 assertions;
- `backend/bin/test-http.sh` → verificou `401` sem token, `403` para seller e `200` para admin contra a API Dockerizada.
