# Atualização V11 — alertas, simulação iFood e central de configurações

## O que foi adicionado

- **App do motoboy instalável:** orientação para instalar o app no celular, ativar notificações e testar o alerta. Uma nova corrida gera notificação do aparelho, vibração e aviso visual sem precisar atualizar a tela.
- **Aviso pelo WhatsApp:** quando a Evolution API estiver conectada, o despacho também envia a corrida ao WhatsApp cadastrado do motoboy. É o canal de segurança quando o celular suspende o navegador.
- **Simulador iFood:** no topo de **Meus pedidos** é possível criar uma entrega ou uma coleta de teste, identificada como simulação iFood e seguindo todo o fluxo de aceite, cozinha e despacho.
- **Configuração do salão organizada:** Meu salão, Meus garçons, App Garçom, Comandas, PDV, Taxa de serviço, Impressora e Balança ficam separados em uma navegação própria.
- **Configurações gerais:** status dos canais, sequência do pedido, sons, impressão, cancelamento, dados do estabelecimento, entrega, cardápio, integrações e segurança.
- **Recuperador de vendas:** registra carrinhos abandonados, permite ativar a recuperação automática, definir o tempo de espera, editar a mensagem e enviar pelo Evolution/WhatsApp.
- **Conta do cliente:** cadastro por e-mail e senha, login, endereços salvos, dados preenchidos automaticamente no checkout e histórico de pedidos.

## Como testar o iFood

1. Entre em **Meus pedidos**.
2. Clique em **Simular entrega** ou **Simular coleta**.
3. O pedido aparecerá em Novos com a etiqueta **iFood · simulação**.
4. Aceite e acompanhe normalmente na cozinha e no despacho.

## Como ativar o alerta do motoboy

1. Abra o link do motoboy no celular e faça login.
2. Toque em **Instalar app** e adicione à tela inicial.
3. Abra pelo ícone instalado e toque em **Ativar notificações**.
4. Use **Testar alerta** para conferir notificação, vibração e som do aparelho.
5. Cadastre corretamente o WhatsApp do motoboy. Quando a Evolution estiver conectada, ele também receberá a corrida por mensagem.

> Navegadores de celular podem suspender páginas fechadas. Para aviso confiável fora da tela, use o aplicativo instalado com permissão de notificações e mantenha o WhatsApp/Evolution como segundo canal.

## Instalação sem apagar dados

1. Envie o ZIP para `rvautomacao.com.br/cardapio`.
2. Extraia na mesma pasta e confirme a substituição.
3. O pacote não contém `config.php`, `data/rvcardapios.sqlite` nem `error_log`.
4. Usuários, produtos, clientes, pedidos, senhas e caixa existentes são preservados.
5. Atualize a página completamente após a instalação para limpar o cache antigo.
