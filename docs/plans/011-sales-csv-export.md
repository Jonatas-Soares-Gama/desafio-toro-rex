# Plano 011 — Exportação de vendas em CSV

Status: concluído.

## Objetivo

Permitir que o admin baixe o histórico de vendas exibido na tela em um arquivo
CSV compatível com Excel e outras ferramentas de análise.

## Decisão

- A exportação será feita no frontend a partir do `GET /sales` já protegido.
- Não será criado endpoint adicional nem dependência externa.
- O arquivo usará UTF-8 com BOM e separador `;`, adequado ao Excel em locale
  português.
- Todos os valores serão escapados com aspas duplas; aspas internas serão
  duplicadas conforme o formato CSV.
- O arquivo conterá todas as vendas carregadas pelo histórico atual.
- Paginação e exportação de grandes volumes ficam fora desta fatia.

## Conteúdo

O CSV terá as colunas:

```text
id;external_id;seller;produto;campanha;quantidade;valor_unitario;pontos;status;criado_em
```

## Critérios de aceite

- admin consegue baixar o CSV pela tela de vendas;
- botão fica desabilitado quando não há vendas;
- arquivo possui nome com data;
- acentos são preservados;
- campos com `;`, aspas ou quebra de linha não quebram as colunas;
- exportação não faz nova requisição;
- conversor possui teste automatizado;
- frontend passa em testes, lint e build.

## Verificação executada

- teste automatizado do conversor CSV passou;
- frontend passou em testes, lint e build;
- exportação usa somente as vendas já carregadas pelo histórico.

## Fora de escopo

- exportação server-side;
- filtros antes da exportação;
- paginação;
- exportação de carteira ou campanhas.
