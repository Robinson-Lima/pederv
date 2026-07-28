# Atualização V10 — operação unificada e robô WhatsApp

## Principais mudanças

- **Meus pedidos unificado:** pedidos do cardápio, iFood, salão, QR da mesa e balcão aparecem no mesmo painel, identificados pela origem.
- **Filtros rápidos:** Todos, Entregas, Mesas/Balcão e pesquisa por cliente ou comanda.
- **iFood no fluxo normal:** aceite, cozinha e despacho seguem a mesma operação. Quando a entrega for do iFood, existe a opção **Pronto para coleta**.
- **Pedido pronto:** o aviso verde só aparece e pisca quando existe pelo menos um pedido realmente pronto.
- **WhatsApp no topo:** conexão, situação do robô, mensagens recebidas, WhatsApp Web e criação de pedido ficam no botão do canto superior direito.
- **Mensagens do robô:** nova tela exclusiva para respostas pré-prontas, gatilhos, ativação individual, prévia da conversa e campos automáticos de nome, saudação e link do cardápio.
- **Combos:** produtos podem ser cadastrados como combo e mostrar os itens incluídos no cardápio.
- **Configuração do salão:** Meu salão, Meus garçons, App Garçom, Comandas, PDV, Impressora e Balança.
- **Relatórios:** geral, caixas, clientes, pedidos, itens, entregadores, garçons e área de entrega, com período e impressão/PDF.
- **Motoboy:** atualização automática, alerta visual, vibração, notificação do aparelho e tentativa de toque quando o app está em segundo plano. O aviso acontece somente quando chega uma nova entrega e o motoboy ainda não possui entrega aceita.
- **Mapa:** os arquivos locais do mapa e do desenho de áreas continuam incluídos para evitar a tela vazia causada por bloqueio do carregamento externo.

## Instalação sem apagar dados

1. Envie o ZIP para `rvautomacao.com.br/cardapio`.
2. Extraia nessa mesma pasta e confirme a substituição dos arquivos.
3. O pacote **não contém** `config.php` nem `data/rvcardapios.sqlite`.
4. Assim, usuários, senhas, produtos, clientes, pedidos, caixa e configurações existentes são preservados.
5. Depois de extrair, abra o painel e faça uma atualização completa da página para limpar o cache antigo.

## Observação sobre o WhatsApp

O botão do robô no sistema abre o WhatsApp Web e permite iniciar um pedido pelo PDV. O WhatsApp Web não permite que um site PHP coloque botões dentro da própria tela dele; isso exigiria uma extensão de navegador ou aplicativo instalado no computador. A integração de mensagens e notificações é feita pela Evolution API e pelo webhook informado na tela de conexão.

## Observação sobre o alerta do motoboy

No navegador, o som em segundo plano depende da permissão de notificações e de o motoboy tocar uma vez em **Ativar alertas**. Se o navegador for encerrado ou o celular suspender totalmente a página, quem mantém o aviso é a notificação do aparelho e a mensagem de WhatsApp enviada pela integração. Para alerta sonoro garantido com o aplicativo fechado, a etapa futura é publicar um aplicativo Android com push nativo.
