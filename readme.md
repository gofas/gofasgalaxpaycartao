# Módulo cel_cash Cartão para WHMCS

[![versão](https://img.shields.io/github/v/release/gofas/gofasgalaxpaycartao?label=vers%C3%A3o&color=005071&style=flat-square)](https://github.com/gofas/gofasgalaxpaycartao/releases/latest)
[![downloads](https://img.shields.io/github/downloads/gofas/gofasgalaxpaycartao/total?label=downloads&color=005071&style=flat-square)](https://github.com/gofas/gofasgalaxpaycartao/releases/latest)
[![licença](https://img.shields.io/badge/licen%C3%A7a-propriet%C3%A1ria-005071?style=flat-square)](https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/)
[![suporte](https://img.shields.io/badge/suporte-f%C3%B3rum%20gratuito-ff8700?style=flat-square)](https://gofas.net/foruns/)

Módulo gratuito de integração que permite receber pagamentos com cartão de crédito no WHMCS através da API cel_cash, com parcelamento configurável e confirmação automática das faturas. Desenvolvido pela Gofas Software.

## Sumário

- [Download](#download)
- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Informações importantes](#informações-importantes)
- [Suporte](#suporte)
- [Licença](#licença)

## Download

**[Baixar a versão mais recente](https://github.com/gofas/gofasgalaxpaycartao/releases/latest/download/gofasgalaxpaycartao.zip)**

O download é contabilizado no site pelo contador de instalações do módulo.

## Funcionalidades

- **Pagamento com cartão de crédito** direto na fatura do WHMCS
- **Parcelamento configurável**, com valor mínimo e número máximo de parcelas
- **Confirmação automática de pagamento** e baixa nas faturas
- **Valor mínimo** da fatura para permitir pagamento com cartão
- **Cálculo da tarifa** por transação confirmada, preenchendo o campo "Taxas" (fee) da lista de transações do WHMCS
- **Dispensa configuração de campos CPF/CNPJ**: o módulo detecta automaticamente os campos personalizados de clientes
- **Suporte a produção e a testes (sandbox)**
- **Logs de diagnóstico** configuráveis
- **Aviso de atualização** e verificação de versão na própria tela de configuração do módulo

## Requisitos

- WHMCS >= 8.0
- PHP >= 7.3
- Conta cel_cash com o módulo Webservice ativo
- Credenciais: Galax ID e Galax Hash (produção e testes)

## Instalação

1. Baixe o arquivo pelo link de download e descompacte. Será criada a pasta `gofasgalaxpaycartao`.
2. Copie as pastas `includes` e `modules` de dentro de `gofasgalaxpaycartao` para a raiz da instalação do WHMCS, mesclando com as pastas existentes.
3. Ative o módulo em `Opções > Pagamentos > Portais para Pagamentos > aba All Payment Gateways`.
4. Informe o Galax ID e o Galax Hash.

## Configuração

### Pré configuração na cel_cash

1. No painel administrativo, em `Módulos`, ative o módulo Webservice.
2. Em `Módulos > Webservice > Configurar`, copie as credenciais Galax ID e Galax Hash de produção.
3. Repita o processo no painel do modo de testes para obter as credenciais de sandbox.

### Pré configuração no WHMCS

Crie um campo personalizado de cliente para CPF e/ou CNPJ, ou dois campos distintos, um para cada documento. O módulo identifica os campos automaticamente.

### Opções do módulo

<img src="https://raw.githubusercontent.com/gofas/gofasgalaxpaycartao/master/docs/img/tela-configuracoes-modulo-1.2.0.png" alt="Tela de configuracoes do modulo" width="640">

- **Galax ID** e **Galax Hash**: credenciais do Webservice em produção.
- **Sandbox Galax ID** e **Sandbox Galax Hash**: credenciais do Webservice em modo de testes.
- **Administrador do WHMCS**: administrador com permissão para usar a API interna do WHMCS.
- **Sandbox**: gera cobranças em modo de testes.
- **Salvar Logs**: grava informações de diagnóstico em `Utilitários > Logs > Log de Módulo`.
- **Tarifa**: valor em % pago por transação, usado para preencher o campo "Taxas" (fee) da transação no WHMCS.
- **Valor mínimo**: valor mínimo da fatura para permitir pagamento com cartão.
- **Permitir parcelamento**: exibe as opções de parcelamento na fatura quando aplicável.
- **Valor mínimo para parcelamento**: valor mínimo da fatura para permitir parcelamento.
- **Máximo de parcelas**: número máximo de parcelas oferecidas ao cliente.
- **Enviar estatísticas de uso (opcional)**: controla o envio identificado das estatísticas de confirmação de pagamento. Desmarcado, as confirmações continuam sendo contabilizadas de forma anônima.

## Informações importantes

- A tarifa do cartão é paga separadamente à cel_cash, conforme o plano da sua conta.
- Sempre faça backup antes de mudar algo no seu sistema.

## Suporte

Fórum de suporte gratuito: https://gofas.net/foruns/

## Licença

Software proprietário da Gofas Software. O código é público apenas para transparência e consulta; isso não concede licença de uso, modificação ou redistribuição. É vedado modificar, redistribuir, sublicenciar ou realizar engenharia reversa sem autorização prévia por escrito. Veja [LICENSE](LICENSE) e o contrato completo em https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/.
