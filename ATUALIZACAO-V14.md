# RV Cardápios — atualização V14

## O que mudou

- Pix online e cartão online pendentes bloqueiam o botão **Despachar**.
- O bloqueio acontece no painel do operador, antes de enviar a entrega ao motoboy.
- A caneta de pagamento permanece disponível até o despacho.
- Pela caneta, o operador pode trocar a forma de pagamento e marcar o pedido como **PAGO**.
- O card identifica claramente **AGUARDANDO**, **RECEBER** ou **PAGO**.
- O motoboy não precisa confirmar pagamento: ele só recebe a entrega depois que o operador a libera.
- O motoboy recebe uma notificação Web Push única por novo despacho, mesmo com outra entrega em andamento.
- Incluídos ícones próprios e instalador do painel para Windows.

## Atualizar sem perder os dados

1. Faça uma cópia de segurança da pasta `/cardapio` no cPanel.
2. Extraia o ZIP desta atualização por cima da pasta `/cardapio`.
3. Não apague nem substitua o arquivo `config.php` e a pasta `data` existentes no servidor.
4. Atualize a página com `Ctrl + F5`.

## Ativar os avisos do motoboy

No celular de cada motoboy:

1. Entre no app com o usuário do próprio motoboy.
2. Toque em **Ativar alertas de novas entregas** e permita as notificações.
3. Toque em **Testar alerta**.
4. Instale o app pela opção **Instalar app** ou pelo menu do navegador.
5. Nas configurações do Android, deixe as notificações do app com som e vibração ativados.

Com o app aberto, ele pode tocar o aviso personalizado. Em segundo plano ou fechado, o aviso é entregue pelo sistema do celular e usa o som de notificação configurado no Android. Se a Evolution estiver conectada, o WhatsApp também serve como reforço.

## Instalar o painel no Windows

O arquivo separado `RV-Cardapios-Instalador-Windows-V14.zip` cria um atalho com ícone próprio na Área de Trabalho e no menu Iniciar. O painel abre em uma janela de aplicativo, sem barra de endereço do navegador.

1. Extraia o ZIP no computador.
2. Dê dois cliques em `INSTALAR-RV-CARDAPIOS-PC.cmd`.
3. O atalho **RV Cardápios - Painel** será criado na Área de Trabalho.

O aplicativo continua 100% online e depende de internet.

## Observação sobre Pix

Se o Pix ainda for estático, a confirmação automática não existe. Nesse caso, o operador confere o recebimento e usa a caneta para marcar **PAGO**. Quando um gateway de pagamento chamar o webhook do sistema, a confirmação poderá ocorrer automaticamente.
