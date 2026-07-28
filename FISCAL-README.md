# Módulo Fiscal (NFC-e) — multi-emissor

O cliente escolhe o emissor na aba **⚙ Config** e cola a API dele. Trocar de emissor
é só trocar a seleção — o resto do sistema não muda.

## Emissores disponíveis (drivers)
| Emissor | Custo | Como ativa |
|---|---|---|
| PlugNotas | por nota/pacote | cola o **API Token** |
| Focus NFe | por nota/pacote | cola o **Token** |
| eNotas | por nota/pacote | cola **API Key + ID da empresa** |
| NFC-e Fácil | por nota/pacote | cola o **Token** |
| Direto SEFAZ (sped-nfe) | **0 por nota** (só o certificado A1) | precisa VPS + sped-nfe + certificado |

Hoje todos estão como **esqueleto pronto**: sem credencial, a nota fica `pendente`
(o sistema nunca quebra). Ao colar a API de um deles, aquele driver passa a emitir.

## Como funciona no painel
- Aba **Notas**: mostra o status de cada nota (autorizada / rejeitada / cancelada / pendente / erro),
  lista pedidos pagos sem nota, botões **Emitir**, **Tentar de novo**, **Cancelar** e link do **DANFE**.
- **Emissão automática**: se ligada em ⚙ Config, emite a NFC-e sozinha a cada venda paga
  (no admin e quando o motoboy marca entregue com pagamento na hora).

## Requisitos para emitir de verdade (do cliente)
1. CNPJ ativo + Inscrição Estadual
2. Credenciamento como emissor de NFC-e na SEFAZ do estado
3. CSC + ID Token (gerado no portal da SEFAZ)
4. Produtos com NCM, CFOP e CST/CSOSN corretos
5. Se for **Direto SEFAZ**: certificado A1 (.pfx) em `data/certificado.pfx`, extensão PHP `soap`,
   e a biblioteca `sped-nfe` em `vendor/` (via composer) — normalmente exige **VPS**.

## Onde plugar a API real (arquivo lib/fiscal.php)
Cada driver `_fiscal_emit_<emissor>()` já tem a chamada HTTP montada (URL + header do token).
Quando tiver a conta, é só conferir os campos do payload conforme a doc do emissor.
