# RVCARDÁPIOS — PedeRV

Sistema de **cardápio digital + gestão de restaurante** feito em PHP puro, sem framework. Roda na HostGator compartilhada.

---

## Contexto para IA (Cole isso no início de cada sessão)

Você está trabalhando no projeto **PedeRV / RVCARDÁPIOS**.

- Dois sócios desenvolvem em paralelo, cada um com seu próprio assistente IA
- O código vive no GitHub: `https://github.com/Robinson-Lima/pederv` (branch `main`)
- A versão de produção está no HostGator: conta `robin440`, pasta `public_html/pederv.com.br/`

### Regras obrigatórias de sincronização

**ANTES de qualquer alteração:**
```bash
git pull
```

**APÓS qualquer alteração:**
```bash
git add <arquivos alterados>
git commit -m "descrição da mudança"
git push
```
E também fazer upload via FTP no HostGator: conta `robin440`, pasta `public_html/pederv.com.br/`

> GitHub e HostGator devem estar sempre iguais. Nunca alterar só um lado.

> `config.php` fica APENAS no servidor — não vai pro Git (está no `.gitignore`). Use `config.sample.php` como modelo.

---

## Stack

- **Backend:** PHP puro, sem framework
- **Banco:** SQLite (padrão, arquivo `data/db.sqlite`) ou PostgreSQL/Supabase (configurável)
- **Frontend:** HTML/CSS/JS puro — sem React, sem Vue
- **Automação:** n8n (pasta `/n8n/`)
- **Pagamentos:** PIX BR Code EMV (CRC16), MercadoPago webhook, iFood webhook
- **Notificações:** Web Push (`lib/webpush.php`)

---

## Estrutura de arquivos

```
pederv/
├── index.php              aplicação principal (~135KB, roteador + controllers)
├── config.php             configurações do restaurante (só no servidor, fora do Git)
├── config.sample.php      modelo do config.php
├── webhook.php            webhooks MercadoPago e iFood
├── sw.js                  service worker (PWA)
│
├── views/                 55 templates PHP
│   ├── admin_*.php        painel admin (pedidos, produtos, caixa, PDV, iFood, financeiro…)
│   ├── waiter*.php        app do garçom e comanda
│   ├── kds.php            tela da cozinha (KDS)
│   ├── courier.php        app do motoboy/entregador
│   ├── saas_*.php         painel SaaS multi-cliente
│   └── menu.php, login.php, layout.php…
│
├── lib/
│   ├── db.php             conexão PDO, criação de tabelas, seed
│   ├── helpers.php        sessão, n8n, mudança de status
│   ├── pix.php            gerador BR Code PIX (CRC16 validado)
│   ├── fiscal.php         módulo fiscal
│   └── webpush.php        notificações push
│
├── assets/
│   ├── style.css          estilos principais
│   ├── v2.css … v17.css   estilos por versão
│   ├── admin-shell.css    shell do painel admin
│   ├── saas.css           estilos do painel SaaS
│   ├── admin-notify.js    notificações do admin
│   └── pederv-logo.png, icon-192.png, icon-512.png…
│
├── CAIXA/                 módulo caixa
├── Cozinha/               módulo cozinha
├── garcom/                app do garçom
├── motoboy/               app do motoboy
├── painel/                dashboard administrativo
├── app/                   versão anterior da aplicação
└── n8n/                   configurações de automação n8n
```

---

## Acessos

| Área | URL | Credencial (config.php) |
|---|---|---|
| Cardápio (cliente) | `/?r=menu` | — |
| Mesa QR | `/?r=placa&mesa=06` | — |
| Painel admin | `/?r=admin` | `admin_senha` |
| App do garçom | `/?r=waiter` | `garcom_senha` |
| App do motoboy | `/?r=courier` | `motoboy_senha` |
| Cozinha (KDS) | `/?r=kds` | — |
| Painel SaaS | `/?r=saas` | `saas_senha` |

---

## Funcionalidades implementadas (v17)

- Cardápio digital com carrinho e checkout
- PIX real: gera copia-e-cola (BR Code EMV + CRC16) e QR Code — 0% de taxa
- Finalização de pedido via WhatsApp
- Painel Kanban: Novos → Em preparo → Saiu p/ entrega → Concluído (polling 4s)
- Pagamentos: pagos × pendentes, marcar como pago
- App do motoboy com botão "Pedido entregue" (atualiza status no painel automaticamente)
- App do garçom com mapa de mesas e comanda
- KDS (tela da cozinha)
- QR Code por mesa
- PDV (ponto de venda)
- Caixa e financeiro
- Módulo fiscal (NF)
- Clientes e histórico de pedidos
- Painel SaaS multi-cliente
- iFood: endpoint `/?r=webhook_ifood` pronto
- MercadoPago: endpoint `/?r=webhook_mercadopago` pronto
- n8n: `n8n_event()` já dispara em cada mudança de status
- Importação de produtos

---

## Instalação na HostGator

1. Suba os arquivos para `public_html/pederv.com.br/`
2. Copie `config.sample.php` para `config.php` e preencha os dados
3. Dê permissão de escrita na pasta `data/` (chmod 755 ou 775)
4. Acesse o domínio — banco e cardápio de exemplo são criados no primeiro acesso

---

## Próximos passos planejados

- Ativar MercadoPago (preencher credenciais em `config.php`)
- Ativar iFood (configurar webhook)
- Ativar n8n para notificações automáticas de status via WhatsApp
- Migrar para WebSocket (Pusher/Reverb) no VPS para substituir o polling de 4s
