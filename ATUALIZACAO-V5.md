# RVCARDÁPIOS — atualização V5

## Instalação

1. Faça backup da pasta atual `/rvautomacao.com.br/cardapio`.
2. Extraia todo o conteúdo deste ZIP dentro da pasta `cardapio`, substituindo os arquivos existentes.
3. Preserve a pasta `data` do servidor caso ela já contenha pedidos, produtos e usuários reais.
4. Acesse o painel como administrador uma vez. As novas tabelas são criadas automaticamente.
5. No aplicativo do motoboy, toque uma vez em **Ativar alertas de novas entregas**. Os navegadores exigem esse primeiro toque para liberar som.

## O que mudou

- App do motoboy atualiza a cada 3 segundos e alerta quando a entrega é despachada, antes do aceite.
- QR Code da mesa abre fluxo de salão: sem telefone, endereço ou pagamento; a mesa só fica ocupada ao confirmar.
- Pedidos de QR da mesa vão direto à cozinha com identificação da mesa.
- KDS mostra o garçom ou a origem do pedido.
- Garçom possui saída real para troca de usuário no tablet.
- Caixa pode abrir, movimentar e fechar sem ser redirecionado; extrato detalhado por venda.
- Fechamentos anteriores aparecem somente para administrador.
- Cadastro do restaurante por CEP, rua, número, bairro, cidade e UF; CEP usa ViaCEP.
- Mapa ganhou mensagem/fallback caso a biblioteca de desenho seja bloqueada pelo navegador.
- Nova Central WhatsApp com inbox, webhook, envio oficial e respostas automáticas.

## Central WhatsApp

O WhatsApp Web não pode ser incorporado em outro site por bloqueios de segurança do próprio WhatsApp. A Central usa a API oficial da Meta:

1. Abra **Central WhatsApp** no painel.
2. Informe o ID do número, token de acesso, token de verificação e versão da Graph API.
3. Copie o webhook exibido e cadastre-o no painel da Meta.
4. Ative as respostas automáticas se desejar usar os gatilhos já cadastrados no robô.

Sem essas credenciais a tela funciona como preparação, mas não envia nem recebe conversas reais.
