# Atualização V9 — mapa da área de entrega

## Correção principal

- O painel do mapa agora possui largura e altura definidas diretamente no elemento HTML.
- A exibição não depende mais do arquivo de estilos do servidor para ganhar altura.
- Foi adicionada uma mensagem visível durante o carregamento.
- Caso a biblioteca do mapa não seja encontrada, o sistema mostra um aviso claro no próprio quadro.
- Os arquivos do Leaflet e os estilos receberam versão 9 para impedir o uso do cache antigo.

## Instalação

1. Extraia o ZIP diretamente dentro da pasta `rvautomacao.com.br/cardapio`.
2. Confirme a substituição dos arquivos existentes.
3. É importante substituir também a pasta `assets/vendor`, além da pasta `views`.
4. Abra novamente **Taxas de entrega** e faça uma atualização completa da página.

O arquivo `config.php` e os dados do restaurante não fazem parte deste pacote, portanto não são substituídos.
