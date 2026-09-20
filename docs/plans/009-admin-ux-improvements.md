# Plano 009 — Melhorias de cadastro e operação do admin

Status: fatias 1 e 2 concluídas — melhorias conservadoras de cadastro e
operação.

## Problemas atuais

### SKU do produto

Hoje o backend exige que o admin informe manualmente um SKU. Isso cria trabalho
duplicado e permite valores inconsistentes. O SKU também precisa continuar
único no banco.

### Identificador da venda

`sales.id` é o identificador interno gerado pelo banco. Já `external_id` foi
modelado como identificador vindo de um ERP ou sistema externo, usado para
idempotência. Por isso a API atual exige esse campo.

Na venda manual pela interface não existe um sistema externo fornecendo esse
valor. Nesta primeira fatia, o frontend gera um identificador técnico antes do
envio e mantém o contrato atual da API. Uma geração no backend fica como
evolução posterior, sem retirar a possibilidade de integrações enviarem seu
próprio `external_id`.

### Seller da venda

Hoje o formulário exige `seller_id`, mas não existe tela nem endpoint para o
admin consultar os sellers. O usuário precisa conhecer um ID interno, o que é
uma falha de fluxo.

### Usuários

O schema já possui `users`, mas a API só consulta usuários internamente para
login e validação de sellers. Não há cadastro ou listagem administrativa.

## Decisões propostas

### 1. SKU automático

- No cadastro, o campo SKU deixa de ser editável e vira uma prévia automática.
- Enquanto o nome é digitado, a interface mostra uma prévia como:
  `Café Gourmet 500g` → `CAFE-GOURMET-500G`.
- Nesta primeira fatia, o frontend gera a prévia e continua enviando o campo
  `sku` já exigido pela API; a geração no backend fica para a fatia 3.
- A normalização proposta é: remover acentos, converter para maiúsculas,
  trocar grupos de caracteres não alfanuméricos por `-`, remover hífens das
  pontas e respeitar o limite de 80 caracteres.
- Em colisão, o backend acrescenta um sufixo determinístico, por exemplo
  `CAFE-GOURMET-500G-2`.
- Depois que o produto for criado, o SKU não muda automaticamente quando o nome
  for editado, preservando referências externas e histórico.

### 2. `external_id` automático para venda manual (fatia conservadora)

- O campo deixa de aparecer no formulário de lançamento manual.
- `POST /sales` continua exigindo `external_id` e não muda seu contrato.
- O frontend gera um `manual-<UUID>` antes de enviar a venda.
- O backend continua sendo a autoridade da constraint única e da idempotência.
- Quando uma integração enviar seu próprio valor, o comportamento atual é
  preservado.

### 3. Seller selecionável

- O formulário passa a exibir um `<select>` com nome e e-mail do seller.
- O ID permanece invisível para o usuário e é enviado internamente no payload.
- A tela deve impedir o lançamento enquanto não houver seller cadastrado.

### 4. Gestão administrativa de usuários

Primeira versão mínima:

- `GET /users/sellers`: lista sellers sem `password_hash`;
- `POST /users`: cria seller com nome, e-mail e senha; o backend fixa o papel
  como `seller`;
- admin pode criar sellers;
- criação de novos admins permanece fora do primeiro ciclo para reduzir risco de
  escalada de privilégio;
- não haverá exclusão física de usuários, pois já existem foreign keys para
  vendas e ledger.

A tela **Usuários** terá cadastro de seller e listagem com nome, e-mail, papel e
data de criação.

## Fases de execução

### Fatia 1 — Cadastro de sellers, SKU e venda manual

1. Especificar e testar `GET /users/sellers` e `POST /users`;
2. adicionar validação de e-mail, senha, nome e papel;
3. impedir exposição de `password_hash`;
4. adicionar tela administrativa de usuários;
5. trocar o campo numérico de seller por seleção de nome/e-mail;
6. cobrir `401`, `403`, `409` e `422`;
7. gerar SKU no frontend enquanto o nome é digitado, mantendo o contrato atual;
8. gerar `external_id` no frontend antes de enviar a venda;
9. validar o fluxo completo de seleção sem expor o ID interno.

### Fatia 2 — Histórico e cancelamento orientado

O detalhamento executável desta fatia está em
`docs/plans/010-sales-history-cancellation.md`.

O cancelamento atual exige que o operador saiba o `external_id`. Depois que esse
valor for automático, isso deixa de ser uma boa interface. Portanto:

1. adicionar `GET /sales` protegido para admin;
2. listar venda, seller, produto, campanha, status, pontos e data;
3. trocar o campo manual de cancelamento por uma ação na linha da venda;
4. manter o `external_id` visível apenas como detalhe técnico quando necessário.

### Verificação da fatia 1

- PHPUnit: 31 testes, 47 assertions;
- teste HTTP de usuários: `401`, `403`, `422`, `201`, `409` e ausência de
  `password_hash` verificados;
- frontend: testes de autenticação e SKU, lint e build executados com sucesso;
- Docker Compose rebuild concluído com backend e frontend saudáveis.

### Fatia 3 — SKU automático no backend

1. criar a regra de normalização no domínio/backend;
2. permitir criação sem SKU no contrato;
3. tratar colisão sem depender somente de tentativa no frontend;
4. exibir a prévia automática no formulário;
5. preservar SKU existente durante edição de nome;
6. cobrir acentos, símbolos, nomes vazios, nomes longos e colisões.

### Fatia 4 — Verificação e documentação

- atualizar `docs/specs/products.md`, `docs/specs/sales.md` e uma nova spec de
  usuários;
- atualizar README e requests;
- executar PHPUnit, testes HTTP, lint e build do frontend;
- verificar o fluxo completo: criar seller → selecionar seller → lançar venda →
  visualizar histórico → cancelar venda.

## Fora do primeiro ciclo

- edição de senha e perfil pelo próprio usuário;
- exclusão ou desativação de usuários;
- criação de novos admins pela interface;
- importação CSV;
- geração de SKU editável pelo operador;
- filtros e paginação avançados no histórico.

## Pontos para confirmar

1. O admin deve criar somente sellers na primeira versão? Esta é a proposta mais
   segura e está sendo aplicada nesta fatia.
2. O SKU deve permanecer fixo após a criação, mesmo que o nome seja editado?
   Esta é a proposta para preservar histórico e integrações.
3. A tela de vendas deve substituir o cancelamento por `external_id` por uma
   lista com botão **Cancelar**? Esta é a proposta para tornar o ID automático
   realmente transparente e permanece para a fatia 2.
