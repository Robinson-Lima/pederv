# CLAUDE.md — PedeRV / RVCARDÁPIOS

## Regra principal: sincronização obrigatória

Este projeto é desenvolvido por dois sócios em paralelo, cada um com seu próprio Claude Code.

**ANTES de qualquer alteração, sempre executar:**
```bash
git pull
```

**APÓS qualquer alteração, sempre:**
1. `git add` nos arquivos alterados
2. `git commit -m "descrição da mudança"`
3. `git push`
4. Upload via FTP no HostGator: `public_html/pederv.com.br/` (conta robin440)

Nunca alterar só um lado. GitHub e HostGator devem estar sempre iguais.

## Repositório
- GitHub: `https://github.com/Robinson-Lima/pederv`
- Branch principal: `main`

## Produção (HostGator)
- Conta: robin440
- Caminho: `public_html/pederv.com.br/`
- `config.php` fica APENAS no servidor — não vai pro Git (está no `.gitignore`)

## Stack
- PHP puro, sem framework
- Banco: SQLite (padrão) ou PostgreSQL/Supabase
- Frontend: HTML/CSS/JS puro

## Estrutura principal
- `index.php` — aplicação principal (arquivo único, ~135KB)
- `views/` — 55 templates PHP (admin, garçom, cozinha, motoboy, SaaS)
- `lib/` — db.php, helpers.php, pix.php, fiscal.php, webpush.php
- `assets/` — CSS, imagens, JS
- `config.sample.php` — modelo do config.php (preencher no servidor)
