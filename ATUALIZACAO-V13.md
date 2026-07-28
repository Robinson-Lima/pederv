# RV Cardápios — atualização V13

## Novidades

- Pix separado em **Pix online** e **Pix na entrega**.
- Situação clara: **PAGO**, **aguardando confirmação online** ou **receber do cliente**.
- Bloqueio de despacho/conclusão quando o pagamento online ainda não foi confirmado.
- Webhook seguro para confirmação de Pix e cartão por gateway ou n8n.
- Extrato detalhado do caixa atual e dos fechamentos anteriores, exclusivo do administrador.
- Lista de cada venda com cliente, horário, pagamento, total e itens.
- Instalação como aplicativo (PWA) no cardápio, painel, caixa, cozinha, garçom e motoboy.
- Fluxo n8n importável para avisos de status pelo WhatsApp/Evolution API.

## Instalação sem perder dados

1. Faça backup da pasta `cardapio` do servidor.
2. Extraia este ZIP sobre a pasta existente.
3. Não apague `config.php` nem `data/rvcardapios.sqlite` do servidor.
4. Atualize a página com `Ctrl + F5`.

Este ZIP de atualização não inclui o banco de dados, o `config.php` nem o `error_log`.
