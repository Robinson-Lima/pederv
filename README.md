# RVCARDÁPIOS — Sistema (MVP funcional)

Sistema em **PHP + SQLite**, sem framework nem dependências. Roda direto na **HostGator compartilhada** e depois migra pro VPS (é só trocar o DSN do banco por MySQL).

## Instalar na HostGator
1. Suba a pasta `rvcardapios/` para dentro de `public_html` (ou aponte o domínio para ela).
2. Dê permissão de escrita na pasta `data/` (chmod **755** ou **775**).
3. Abra `config.php` e edite: nome do restaurante, chave PIX, WhatsApp e as **senhas de acesso**.
4. Acesse o domínio — o banco e o cardápio de exemplo são criados sozinhos no primeiro acesso.

## Acessos
| Área | URL | Senha (config.php) |
|---|---|---|
| Cardápio (cliente) | `/` ou `/?r=menu` | — |
| Placa QR da mesa | `/?r=placa&mesa=06` | — |
| Painel admin | `/?r=admin` | `admin_senha` |
| App do garçom | `/?r=waiter` | `garcom_senha` |
| App do motoboy | `/?r=courier` | `motoboy_senha` |

## O que já funciona
- Cardápio digital preto+laranja RV, carrinho e checkout.
- **PIX real**: gera o "copia e cola" (BR Code EMV com CRC16) e o QR na hora — 0% de taxa.
- Finalizar pelo **WhatsApp** (monta a mensagem com o pedido).
- **Painel** com kanban (Novos → Em preparo → Saiu p/ entrega → Concluído) atualizando em **tempo real** (polling a cada 4s).
- **Pagamentos**: pagos × pendentes, com botão "marcar pago".
- **App do motoboy**: lista de entregas + botão **"Pedido entregue"** que muda o status e **o card cai em "Concluído" no painel automaticamente** (integração pedida).
- App do garçom (mapa de mesas) e placa de QR por mesa.

## O que fica como próximo passo (pontos de integração já preparados)
- **Mercado Pago**: endpoint `/?r=webhook_mercadopago` pronto para receber a confirmação (preencher credenciais em `config.php`).
- **iFood**: endpoint `/?r=webhook_ifood` pronto para inserir pedidos com `canal=ifood` (aparecem no mesmo painel).
- **n8n / WhatsApp automático**: cada mudança de status já dispara `n8n_event()` para o webhook do n8n (confirmação, saiu p/ entrega, entregue). Basta ativar em `config.php`.
- **Tempo real no VPS**: hoje é polling (funciona no compartilhado). No VPS, trocar por WebSocket (Pusher/Reverb) sem mexer no resto.

## Estrutura
```
rvcardapios/
├── index.php          roteador + controllers
├── config.php         configurações do restaurante
├── lib/
│   ├── db.php         SQLite + criação de tabelas + seed
│   ├── pix.php        gerador do BR Code PIX (CRC16 validado)
│   └── helpers.php    sessão, n8n, mudança de status
├── views/             cardápio, painel, motoboy, garçom, placa, login
├── assets/            style.css (identidade RV) + logo
└── data/              banco SQLite (gerado no 1º acesso)
```

## Migração para MySQL (VPS)
Em `lib/db.php`, troque o DSN:
```php
$pdo = new PDO('mysql:host=localhost;dbname=rvcardapios;charset=utf8mb4', 'usuario', 'senha');
```
O restante do código é o mesmo (usa PDO).
