# Vendeu, Ganhou

## Objetivo

Construir uma plataforma enxuta de incentivo de vendas. Administradores configuram produtos e campanhas, registram vendas e cancelamentos; vendedores acompanham seus pontos por meio de uma carteira baseada em ledger.

## Escopo do MVP

- Autenticação por JWT.
- Papéis `admin` e `seller`.
- CRUD de produtos.
- Criação e listagem de campanhas.
- Registro idempotente de vendas.
- Cancelamento idempotente com estorno.
- Carteira e extrato do vendedor autenticado.
- Seed com um administrador e pelo menos dois vendedores.
- Execução completa via Docker Compose.

## Fora do escopo

- Saque ou pagamento real.
- Integração com ERP ou e-commerce.
- Notificações.
- Importação CSV no núcleo inicial.

## Critério de sucesso

O projeto deve subir a partir de um clone limpo, permitir testar os fluxos principais com as credenciais de seed e manter as invariantes de verba, ledger, autenticação e ownership.
