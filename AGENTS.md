# Instruções do projeto

## Objetivo

Este repositório implementa a plataforma de incentivo de vendas "Vendeu, Ganhou", descrita em `DESAFIO-TORO.md`.

O núcleo do sistema é registrar vendas, calcular pontos, controlar a verba das campanhas e manter uma carteira baseada em ledger. A correção transacional, a segurança e a clareza do código são mais importantes que quantidade de funcionalidades.

## Princípios de desenvolvimento

- Use PHP 8.x puro no backend, sem framework full-stack.
- Use PDO com prepared statements para todo acesso ao MySQL.
- Use React no frontend e Docker Compose para executar o projeto.
- Mantenha `declare(strict_types=1);` nos arquivos PHP aplicáveis.
- Prefira nomes explícitos, funções pequenas e uma responsabilidade por classe.
- Controllers devem cuidar de HTTP; regras de negócio pertencem ao domínio e aos casos de uso.
- Não introduza abstrações, interfaces ou padrões sem uma necessidade concreta.
- Não altere o contrato de uma API silenciosamente; atualize a especificação e o README quando necessário.
- Nunca confie em papel ou identidade enviados no body: use os dados do JWT validado.

## Organização arquitetural

O backend deve separar responsabilidades desta forma:

- `src/Domain`: regras e modelos de negócio, sem dependência de HTTP, PDO ou JWT.
- `src/Application`: casos de uso e orquestração das regras de negócio.
- `src/Infrastructure`: PDO, repositories, transações, JWT e integrações externas.
- `src/Http`: router, controllers, middleware, validação de entrada e respostas.
- `tests/Unit`: testes rápidos de regras isoladas.
- `tests/Integration`: testes com MySQL e transações reais.
- `tests/Feature`: testes de fluxos HTTP quando aplicável.
- `docs/specs`: especificações funcionais e critérios de aceite.
- `docs/decisions`: decisões arquiteturais e de domínio relevantes.

No frontend, organize o código por funcionalidade (`auth`, `products`, `campaigns`, `sales` e `wallet`) e mantenha componentes compartilhados realmente genéricos.

## SDD e TDD

Para cada funcionalidade relevante:

1. Escreva ou atualize a especificação em `docs/specs`.
2. Registre critérios de aceite e casos de erro.
3. Escreva um teste que falha.
4. Implemente o mínimo necessário para fazê-lo passar.
5. Refatore sem mudar o comportamento.
6. Execute os testes e registre decisões importantes.

O motor de pontuação deve ser desenvolvido prioritariamente com TDD. Os cenários mínimos incluem venda válida, verba insuficiente, idempotência, cancelamento, estorno, rollback e concorrência.

## Regras de domínio importantes

- O saldo é calculado pelo ledger: créditos menos débitos.
- `sales.external_id` é único e impede pontuação duplicada.
- Uma venda acima da verba disponível deve ser rejeitada integralmente.
- Crédito da venda e atualização de `budget_used` devem ocorrer na mesma transação.
- Use bloqueio pessimista (`SELECT ... FOR UPDATE`) ao consumir ou devolver verba.
- Cancelamento é idempotente e não pode gerar mais de um débito para a mesma venda.
- Produto removido deve ser inativado para preservar histórico.
- Seller só pode consultar a própria carteira e extrato.
- Rotas administrativas exigem `role = admin`.
- Senhas devem usar `password_hash` e `password_verify`.

## Segurança

- JWT deve ser validado no middleware, incluindo assinatura e expiração.
- Nunca exponha `password_hash` em respostas.
- Diferencie `401 Unauthorized` de `403 Forbidden`.
- Valide tipos, campos obrigatórios, limites e valores positivos no servidor.
- Não concatene entrada do usuário em SQL.
- Evite vazar dados de outros vendedores ao responder erros de ownership.

## Banco e persistência

- Alterações de schema devem ser reproduzíveis por migrations ou scripts versionados.
- Use foreign keys, índices e constraints para reforçar invariantes.
- Valores de pontos e quantidades devem ser inteiros positivos.
- Valores monetários devem usar `DECIMAL`, nunca `float`.
- Repositories não devem conter regras de negócio complexas.
- Transações devem ser iniciadas no caso de uso que coordena múltiplas escritas.

## Verificação local

Antes de considerar uma alteração concluída, execute os comandos disponíveis no projeto, normalmente:

```bash
docker compose up --build
docker compose exec backend composer test
```

Quando existirem, execute também lint, análise estática e testes de integração. Se algum comando não puder ser executado, documente o motivo.

## Commits

Faça commits pequenos e focados, preferencialmente seguindo Conventional Commits:

```text
feat: add sale registration use case
test: cover budget exhaustion
refactor: extract campaign budget policy
docs: describe sale idempotency
```

Não misture refatoração ampla, mudança de schema e funcionalidade em um único commit sem necessidade.

## Antes de alterar código

- Leia a especificação relacionada e os arquivos impactados.
- Preserve alterações existentes do usuário.
- Verifique o status do Git.
- Atualize testes e documentação quando o comportamento mudar.
- Explique decisões não óbvias em `docs/decisions`.
