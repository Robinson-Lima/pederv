# Guia rápido — n8n + Evolution API

Este pacote inclui o fluxo `n8n/rvcardapios-status-whatsapp-v13.json` para enviar ao cliente as mudanças do pedido.

## 1. Importar no n8n

1. Abra o n8n e escolha **Import from File**.
2. Selecione `rvcardapios-status-whatsapp-v13.json`.
3. Abra o bloco **Enviar pela Evolution API**.
4. Troque `SUA-EVOLUTION-API`, `SUA-INSTANCIA` e `SUA-CHAVE-EVOLUTION` pelos dados da sua Evolution API.
5. Salve e ative o fluxo.

## 2. Ligar ao RV Cardápios

1. No bloco inicial do n8n, copie a **Production URL** do webhook.
2. No painel RV Cardápios, abra **Usuários e integrações**.
3. Cole essa URL no campo do webhook n8n e salve.
4. Faça um pedido de teste e altere o status para verificar as mensagens.

O fluxo já prepara mensagens para: pedido recebido, aceito, em preparo, pronto, saiu para entrega, concluído e cancelado.

## 3. Confirmação de Pix/cartão online

Na aba **Pagamentos**, o sistema mostra a URL segura de confirmação. O gateway de pagamento ou um segundo fluxo do n8n deve chamar essa URL somente depois de receber a confirmação real do banco/gateway.

Exemplo de corpo JSON:

```json
{
  "order_id": 123,
  "status": "paid",
  "provider_id": "transacao-123"
}
```

Envie também o segredo configurado no cabeçalho `X-RV-Webhook-Secret`. Enquanto essa confirmação não chegar, Pix online e cartão online continuam como **PAGAMENTO PENDENTE** e o sistema bloqueia a conclusão/entrega.

> Um QR Pix estático sozinho não informa automaticamente ao sistema que o pagamento caiu. Para detecção automática é necessário usar um gateway/API bancária que envie webhook, ou consultar essa API pelo n8n.
