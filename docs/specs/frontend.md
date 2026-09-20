# Especificação — Frontend React

Status: implementada — revisão final pendente.

## Objetivo

Entregar uma interface React pequena, clara e funcional para consumir a API já
implementada do Vendeu, Ganhou. O frontend deve cobrir os fluxos avaliados do
desafio sem duplicar regras de negócio do backend.

## Usuários e fluxo principal

### Login

- A tela inicial solicita `email` e `password`.
- Em sucesso, guarda o JWT e abre a área correspondente ao papel do token.
- Em credenciais inválidas, mostra uma mensagem genérica e mantém o formulário.
- Campos obrigatórios e erros de validação aparecem junto dos campos ou em uma
  mensagem claramente associada ao formulário.

O papel usado para autorização continua sendo responsabilidade do backend. O
frontend pode ler o claim `role` do JWT somente para montar a navegação; isso não
é uma fronteira de segurança.

### Admin

O admin terá um shell autenticado com navegação para:

1. **Produtos** — listar produtos ativos e inativos, criar, editar e inativar;
2. **Campanhas** — listar campanhas e criar uma campanha com período e orçamento,
   exibindo `budget_used / budget_total`;
3. **Usuários** — cadastrar e listar sellers;
4. **Vendas** — lançar vendas, consultar o histórico, cancelar uma venda pela
   própria linha e exportar o histórico em CSV.

O frontend não calcula pontos, não altera `budget_used` e não permite editar
status ou dados do ledger.

No cadastro de produto, o SKU é gerado automaticamente a partir do nome e
enviado para a API como campo somente leitura. No lançamento manual de venda,
o frontend gera um `external_id` com `crypto.randomUUID()` e continua enviando
esse campo para preservar o contrato e a idempotência existentes.

O histórico pode ser exportado pelo navegador em CSV UTF-8 com BOM e separador
`;`, sem nova requisição ou dependência adicional.

Os campos de período usam somente data, no formato local `dd/mm/aaaa`, sem
entrada de horário. Ao enviar para a API, o início recebe `00:00:00` e o fim
recebe `23:59:59`.

### Seller

O seller terá uma área de carteira com:

- saldo atual em pontos;
- extrato completo ordenado pela API;
- distinção visual e textual entre `credit` e `debit`;
- estado vazio quando não houver movimentações.

## Contrato de integração

O cliente HTTP deve centralizar o token, o tratamento de JSON e os erros destes
endpoints:

| Fluxo | Endpoint |
|---|---|
| Login | `POST /auth/login` |
| Produtos | `GET/POST /products`, `PUT/DELETE /products/{id}` |
| Campanhas | `GET/POST /campaigns` |
| Sellers | `GET /users/sellers`, `POST /users` |
| Venda | `POST /sales` |
| Histórico de vendas | `GET /sales` |
| Cancelamento | `POST /sales/{external_id}/cancel` |
| Carteira | `GET /me/wallet` |

Regras de resposta:

- `401`: remover a sessão local e voltar ao login;
- `403`: mostrar “Você não tem permissão para esta ação” sem expor dados;
- `404`: informar que o recurso não foi encontrado;
- `409`: explicar conflito de SKU ou `external_id`;
- `422`: mostrar a mensagem de validação retornada pela API;
- `500` ou falha de rede: mostrar erro recuperável e opção de tentar novamente.

## Estados obrigatórios

Cada tela que lê ou grava dados deve possuir estados explícitos de carregamento,
sucesso, vazio, erro de validação, não autorizado e falha de rede. Ações em
andamento ficam desabilitadas e comunicam o estado sem depender apenas de cor.

## Qualidade de interface

- Modo de uso: **Operate**; prioridade para leitura rápida e conclusão das
  tarefas.
- Layout responsivo para desktop e telas estreitas, com tabelas que não
  quebrem a leitura em mobile.
- Formulários com `label`, foco visível, ordem de teclado e mensagens
  associadas aos campos.
- Mensagens de status anunciáveis por tecnologia assistiva quando necessário.
- Uma linguagem visual consistente, com cores semânticas para sucesso, erro,
  aviso e estado de carteira; cor nunca será o único indicador.
- Sem animações decorativas, gráficos ou componentes modais sem necessidade.

## Princípios de implementação React

- Organizar por feature (`auth`, `products`, `campaigns`, `sales`, `wallet`) e
  manter componentes compartilhados realmente genéricos.
- Manter estado mínimo; listas filtradas, totais e rótulos derivados serão
  calculados durante a renderização.
- Usar efeitos apenas para sincronização externa, como carregar dados ao entrar
  em uma tela ou reagir à sessão; submissões ficam em handlers de eventos.
- Manter fluxo de dados unidirecional e deixar o componente mais próximo dos
  consumidores possuir cada estado local.
- Tipar modelos de resposta e payloads da API; não criar uma camada de domínio
  duplicada no frontend.

## Fora de escopo do primeiro ciclo

- Importação de vendas por CSV;
- paginação, filtros avançados ou busca server-side;
- dashboard analítico, gráficos e notificações em tempo real;
- carteira administrativa;
- saque, transferência ou edição do ledger;
- biblioteca de estado global, cache remoto ou design system externo;
- regras de negócio calculadas no browser.

## Critérios de aceite

1. `docker compose up --build` sobe banco, backend e frontend com portas
   documentadas no README.
2. Admin consegue fazer login, executar o CRUD de produtos, criar/listar
   campanhas, lançar venda e cancelar venda.
3. Seller consegue fazer login e visualizar saldo e extrato somente da própria
   carteira.
4. Requisições sem sessão válida retornam visualmente ao login após `401`.
5. Tentativas sem permissão exibem `403` de forma clara e não vazam dados.
6. Erros `404`, `409`, `422`, `500` e falhas de rede têm feedback recuperável.
7. Todas as telas têm estados de loading, vazio e erro pertinentes.
8. Formulários são utilizáveis por teclado e possuem labels, foco e feedback
   acessíveis.
9. O frontend compila, passa lint e não adiciona dependências para resolver
   problemas que React, CSS ou o navegador já cobrem.

## Referências de boas práticas

- [Thinking in React](https://react.dev/learn/thinking-in-react)
- [You Might Not Need an Effect](https://react.dev/learn/you-might-not-need-an-effect)
