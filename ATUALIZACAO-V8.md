# RVCARDÁPIOS — Atualização V8

## Correções

- Mapa com altura e estrutura incorporadas diretamente na tela, evitando cache antigo do servidor.
- Arquivos do mapa e arquivos visuais agora usam versão nova na URL para forçar a atualização do navegador.
- Se o motor do mapa não carregar, a própria área exibe uma mensagem de diagnóstico em vez de desaparecer.
- Motoboy recebe bip e notificação persistente somente quando o app estiver em segundo plano.
- Quando o motoboy estiver olhando a tela do próprio app, a entrega aparece sem bip.
- O alerta continua sendo disparado apenas quando o motoboy não possui entrega aceita.
- Impressão de conferência refeita em janela própria, no formato de comprovante de 80 mm.
- A conferência agora imprime restaurante, mesa, comanda, atendente, produtos, quantidades, valores unitários, totais por item e total geral.

## Atualização

Extraia o ZIP dentro de `rvautomacao.com.br/cardapio` e confirme a substituição de todos os arquivos e pastas, principalmente `assets` e `views`.

O pacote não inclui `config.php` nem `data/rvcardapios.sqlite`, portanto preserva dados, usuários, produtos e pedidos.

No celular do motoboy, abra o app, clique em `Ativar alertas de novas entregas` e permita notificações e som para o navegador. O aviso sobre outros aplicativos depende dessa permissão do Android/Chrome.
