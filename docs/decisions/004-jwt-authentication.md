# ADR 004 — JWT para autenticação

## Status

Aceita

## Decisão

Usaremos `firebase/php-jwt` para assinar e validar tokens HMAC-SHA256. O segredo será fornecido por `JWT_SECRET` no ambiente e nunca ficará versionado.

O payload mínimo terá `sub`, `role`, `iat` e `exp`.

## Motivo

A biblioteca resolve a parte criptográfica de forma pequena e explícita, mantendo a implementação de middleware e ACL sob controle do projeto, conforme exigido pelo desafio.
