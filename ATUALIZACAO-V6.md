# RVCARDÁPIOS — Atualização V6

## Correções incluídas

- Taxas de entrega com um único cadastro de endereço e mapa desenhável no estilo iFood.
- Leaflet e Leaflet Draw incluídos no pacote; o desenho não depende mais do CDN bloqueado.
- Checkout aberto pelo QR Code da mesa sem endereço e sem formas de pagamento.
- Indicador separado `Pedidos prontos` no painel e selo verde dentro da coluna Em preparo.
- Notificação persistente do motoboy em segundo plano pelo navegador.
- Aviso genérico ao WhatsApp do motoboy pela Evolution API quando o app estiver fechado.
- Central WhatsApp Evolution com teste, configuração automática do webhook, QR Code, conversas e robô.
- Sinal visual e notificação de nova conversa no painel administrativo.

## Instalação

1. Faça uma cópia de segurança da pasta atual `cardapio`.
2. Extraia este ZIP dentro de `rvautomacao.com.br/cardapio` e substitua os arquivos existentes.
3. Não apague a pasta `data`, pois ela contém os dados atuais.
4. Abra **Taxas de entrega**, localize o restaurante e desenhe as áreas no mapa.
5. Abra **Central WhatsApp**, informe URL, instância e API Key da Evolution.
6. Clique em **Salvar conexão**, **Testar conexão**, **Configurar webhook** e, se necessário, **Conectar / gerar QR Code**.

## Observação sobre alertas

O navegador alerta enquanto o app do motoboy estiver aberto ou em segundo plano. Com o app totalmente fechado, o aviso é enviado pelo WhatsApp via Evolution API para o telefone cadastrado do motoboy. A mensagem de alerta não expõe nome, endereço ou dados do cliente; o motoboy consulta os detalhes somente após entrar no app.
