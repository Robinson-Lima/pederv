# RVCARDÁPIOS — Atualização V7

## Ajustes desta versão

- Pedido por QR Code da mesa mostra somente os itens, o total e o botão `Confirmar pedido da mesa`.
- Removido o texto informando que o cliente está no restaurante.
- O motoboy recebe somente um toque quando não possui nenhuma entrega aceita.
- Se o motoboy já tiver uma entrega aceita, novas entregas aparecem no app sem ficar tocando.
- O aviso via Evolution API segue a mesma regra e não é disparado durante uma rota aceita.
- Novo configurador de área de entrega inspirado no iFood.
- Botão grande para desenhar a área ponto a ponto.
- Opção para criar automaticamente uma área circular de 1, 3, 5, 8, 10 ou 15 km.
- Fonte alternativa de mapa quando o OpenStreetMap principal falhar.
- Modo de desenho geográfico continua ativo mesmo quando o navegador bloqueia o fundo com as ruas.

## Atualização

Extraia o ZIP dentro da pasta `rvautomacao.com.br/cardapio` e confirme a substituição. O pacote não inclui `config.php` nem `data/rvcardapios.sqlite`, portanto preserva os dados existentes.

Depois de atualizar, pressione `Ctrl + F5` no painel e no app do motoboy para descartar os arquivos antigos armazenados pelo navegador.
