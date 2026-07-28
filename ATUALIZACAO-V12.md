# Atualização V12

## O que mudou

**1) App instalável em cada área**
Cardápio, Cozinha (KDS), Garçom, Painel e Motoboy agora instalam como app próprio,
cada um abrindo direto na tela certa (o ícone da Cozinha abre o KDS, o do Painel
abre Meus Pedidos, etc). Aparece um botão "📲 Instalar app" no canto da tela quando
o navegador permite instalar; no celular, o Chrome também mostra a opção sozinho.

**2) Telefone e endereço no card do pedido**
Na coluna de pedidos, cada comanda de entrega agora mostra o telefone e o endereço
do cliente, para facilitar escolher o motoboy mais próximo na hora de despachar.

**3) Configurações gerais — abas 6 a 10 corrigidas**
As abas Estabelecimento, Entrega, Cardápio digital, Integrações e Segurança não
abriam: a lista de abas permitidas no servidor só reconhecia as 5 primeiras.
Corrigido.

**4) Mensagens automáticas de WhatsApp por etapa do pedido**
Em **Mensagens do robô**, uma nova seção "Acompanhamento automático do pedido"
permite editar o texto enviado ao cliente em 4 momentos: pedido recebido, em
preparo, saiu para entrega e entregue. Sai automaticamente pela Central WhatsApp
(Evolution API) assim que o status muda — sem precisar de nenhuma ação manual.
Pedidos de mesa (QR do salão) não recebem essas mensagens, pois o cliente já está
no restaurante.

## Sobre o item da campainha do motoboy (ainda em aberto)

Fica combinado de decidir o caminho técnico antes de programar — veja a mensagem
de acompanhamento no chat.

## Instalação sem apagar dados

1. Envie o ZIP para `rvautomacao.com.br/cardapio`.
2. Extraia nessa mesma pasta e confirme a substituição dos arquivos.
3. O pacote **não contém** `config.php` nem `data/rvcardapios.sqlite`.
4. Depois de extrair, dê um Ctrl+F5 (atualização completa) no navegador para
   pegar os arquivos novos de CSS/JS e o manifesto do app.

## Item 2 — notificação push de verdade para o motoboy (implementado)

Agora, quando o motoboy toca em **Ativar alertas** ou **Testar alerta** dentro do
app dele, o navegador cria uma inscrição de notificação push (a mesma tecnologia
que faz o WhatsApp e o Instagram avisarem mesmo com o app fechado). A partir daí,
toda vez que um pedido é despachado para ele, o servidor manda uma notificação
push diretamente — sem precisar do app aberto na tela.

**Antes de usar:** vá em **Entregas → Motoboy** e clique em **Gerar chave agora**
(aparece um aviso amarelo se ainda não tiver sido feito). É um passo único.

**Limitações honestas, pra você não ter surpresa:**
- O som tocado é o som **padrão de notificação do celular**, não é possível o
  site escolher um som customizado — isso é uma trava de segurança do próprio
  Android/iOS, nenhum site consegue contornar.
- Esse recurso requer PHP 8.1 ou mais novo no servidor (a HostGator normalmente
  já vem assim, mas se aparecer o aviso amarelo dizendo que não é compatível,
  é só trocar a versão do PHP no MultiPHP Manager do cPanel).
- **Este código foi escrito sem acesso a um serviço de push real para testar.**
  Se ao clicar em "Testar alerta" a notificação não chegar, o aviso por
  WhatsApp continua garantido normalmente — o motoboy não fica sem alerta em
  nenhum cenário, só nesse caso específico perde a camada extra.
