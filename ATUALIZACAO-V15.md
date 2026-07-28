# ATUALIZAÇÃO V15 — Acerto do motoboy, PDV frente de caixa e visual premium

Pode subir por cima da instalação atual sem apagar dados: as colunas novas são
criadas automaticamente pelo `db_migrate()` no primeiro acesso.

## 1) Acerto do motoboy com o caixa (controle de recebimento na entrega)

**Antes:** quando o motoboy marcava "entregue", pedidos com pagamento na entrega
(dinheiro, cartão na entrega, pix na entrega) eram marcados como PAGOS
automaticamente — sem nenhum controle se o dinheiro/comprovante chegou ao caixa.

**Agora:**
- Ao entregar, o pedido fica com **acerto pendente** (não vira "pago" sozinho).
- Na tela **Meus pedidos**, coluna **🟢 Finalizados**, o card fica destacado em
  vermelho com o aviso **"⚠ AGUARDANDO ACERTO NO CAIXA"** e o botão
  **"✔ Confirmar acerto · marcar PAGO"**. Pendências de dias anteriores
  continuam aparecendo até serem acertadas.
- Novo indicador **"🔴 Acerto pendente"** no topo, com o valor total a receber
  dos motoboys.
- Na aba **Entregas**:
  - Painel **"💰 Acerto com o caixa"** com todas as pendências (dinheiro no
    caixa ou comprovante da maquininha), com botão de confirmação por pedido.
  - **"📒 Extrato do dia por motoboy"**: entregas, quanto foi em dinheiro,
    quanto em maquininha, total do dia e pendência acumulada, com botão
    **"Acertar tudo"** por motoboy.
- Ao confirmar o acerto, o pedido vira **PAGO**, a venda é **lançada no caixa
  aberto** e fica registrado **quem confirmou e quando** (`acerto_por`,
  `acerto_em`).
- Pagamentos online já confirmados (Pix online / cartão online) não geram
  pendência — o acerto fica "ok" automático.

Rota nova: `?r=admin_acerto_pago` (POST `id` ou `courier_id` para acertar tudo
de um motoboy). Liberada para os perfis caixa e admin.

## 2) PDV Balcão agora funciona como frente de caixa

- **Exige caixa aberto** para vender (mostra o status do caixa no topo e o
  atalho para abrir).
- Botão **"💳 COBRAR CLIENTE"** abre a tela de cobrança: total em destaque,
  forma de pagamento, e no dinheiro: **valor recebido**, atalhos de cédulas e
  **cálculo automático do troco** (bloqueia se o valor recebido for menor).
- Ao confirmar: venda lançada no caixa (movimento "venda"), pedido marcado como
  pago, comanda enviada à cozinha e tela de sucesso mostrando o **troco a
  devolver**.
- Ficam gravados no pedido: `valor_recebido`, `troco` e `recebido_por`
  (operador logado).

## 3) Visual premium (assets/v15.css)

- Painel central: sidebar com gradiente, cards de estatística com barra de
  destaque, kanban com cartões elevados e hover, botões com gradiente da marca.
- Cardápio do cliente: fundo com brilho radial, cartões de produto com foto
  arredondada e hover, preço em pílula, botão de adicionar e barra da sacola em
  gradiente, checkout refinado.
- Novos estilos do PDV (modal de cobrança/troco) e dos painéis de acerto.

## Colunas novas (criadas automaticamente)

- `orders.acerto_status` ('' | pendente | ok)
- `orders.acerto_em`, `orders.acerto_por`
- `orders.valor_recebido`, `orders.troco`

## V15.1 — Ajustes

- **Painel:** os cards de contagem (Novos, Em preparo, Prontos, Despachados,
  Finalizados e Acerto pendente) agora ficam compactos em **uma única linha**.
- **Cardápio:** em Configurações gerais → Cardápio digital, o lojista pode
  **subir uma capa** (vitrine do comércio, estilo landing page), escolher o
  **fundo** (Gourmet com textura discreta de comidas, Escuro liso ou Claro) e
  definir uma **faixa de promoção** no topo.
- **PDV:** produtos **separados por categoria** (com filtro por categoria e
  busca), e **teclas de atalho**: F10 finalizar venda, F9 consultar última
  venda, F8 cancelar venda (pede a senha do administrador). No pagamento:
  1 Dinheiro, 2 Pix, 3 Débito, 4 Crédito — ao escolher, o cursor desce direto
  para o valor recebido; Enter confirma. Rotas novas: `pdv_last` e
  `pdv_admin_check`.

## V15.2 — Correção do fundo do cardápio

- O fundo não trocava por **cache do navegador** (a folha de estilo tinha o mesmo
  número de versão da anterior). Todas as versões dos assets foram atualizadas
  (v=16) para forçar o recarregamento — não precisa fazer nada manual.
- Tema **Gourmet** (padrão) agora tem a textura de comidas e o brilho quente
  mais visíveis, mantendo o visual discreto.
- Dica: após subir, se ainda aparecer o fundo antigo no seu navegador, atualize
  a página com Ctrl+F5 uma vez (limpa o cache local).

## V15.3 — Mais temas de fundo

Sete temas disponíveis em Configurações gerais → Cardápio digital → Fundo do
cardápio: Gourmet, Carvão, Noite Âmbar, Madeira, Bistrô, Vinho, Escuro liso e
Claro (Marfim). A capa (vitrine) e a faixa de promoção ficam no mesmo painel.

## V15.4 — Seletor visual de fundo

O fundo do cardápio agora é escolhido por uma galeria de miniaturas clicáveis
(em vez do menu suspenso), com marcação visual do selecionado. Incluídas as
opções de cor base "Preto liso" e "Branco liso", além do "Marfim" e dos estilos
prontos (Gourmet, Carvão, Noite Âmbar, Madeira, Bistrô, Vinho).

## V15.5 — Marca do cliente, paleta e prévia do fundo

- **Cache resolvido de vez:** os arquivos CSS/JS agora são versionados
  automaticamente pela data de modificação. Nunca mais precisa Ctrl+F5 ao
  atualizar.
- **Painel:** removido o "Burger do Zé / Central de operação" duplicado no topo.
  A logo e o nome do cliente aparecem no canto superior esquerdo (sidebar).
- **Logo do cliente:** upload em Configurações gerais → Cardápio digital. Aparece
  no painel e no topo do cardápio (no lugar da inicial).
- **Paleta de cor do app:** escolha a cor de destaque (botões, preços, realces)
  por sugestões clicáveis ou cor personalizada. Aplica no cardápio e no painel.
- **Seletor de fundo:** agora tem um quadradão de **prévia ao vivo** que muda ao
  clicar em cada fundo, miniaturas mais limpas e legíveis. Opções Preto liso e
  Branco liso incluídas.

## V15.6 — Cores separadas, fundo por upload, anúncios e robô

1. Texto dos botões agora tem contraste automático: se a cor escolhida for
   escura, a letra fica branca (resolve "botões pretos com texto ilegível").
2. Paletas separadas em Configurações gerais → Marca, cores e cardápio:
   - Cor do cardápio (cliente)
   - Cor do painel (sistema)
   - Cor da barra lateral (com presets escuros)
3. PDV: no pagamento, o valor não recebe mais o foco automático — dá para
   escolher a forma primeiro pelas teclas 1–4 (ou clicando) e só então o cursor
   vai para "Valor recebido".
4. Fundo do cardápio por upload: o cliente pode enviar a própria imagem de fundo
   (cobre a página com leve escurecimento). Substitui o tema enquanto ativa.
5. A faixa de promoção virou "Anúncios que passam": um por linha, rolando
   automaticamente no topo do cardápio (pausa ao passar o mouse).
6. Emoji do robô do WhatsApp trocado por um ícone de robô desenhado (limpo).

## V16 — Cores do texto, painel profissional e Central SaaS

### 1) Cor do texto (manual)
Em Configurações gerais → Marca, cores e cardápio, além do contraste automático,
agora dá para escolher a cor das letras: botões do cardápio, texto dos anúncios
(promoção) e botões do painel — Automático, Branca, Preta ou Personalizada.

### 2) Painel mais profissional
Camada v16.css: texto maior, menu lateral com cara de botões, cartões e kanban
mais definidos, barra superior encorpada e campos de configuração maiores.

### 3) Central SaaS (painel do dono da plataforma)
Novo painel separado, com login próprio, em **?r=saas_login** (senha padrão:
"rvsaas" — troque em Planos e ajustes).
- Visão geral: MRR, recebido no mês, ativos, em teste, bloqueados/inadimplentes,
  planos mais vendidos, próximos vencimentos, últimos pagamentos.
- Clientes: cadastro completo (restaurante, responsável, WhatsApp, e-mail,
  domínio, cidade/UF, plano, valor, dia de vencimento), busca e filtros.
- Ficha do cliente: registrar pagamento (empurra o vencimento 1 mês e reativa),
  bloquear/desbloquear manual, cancelar, histórico de pagamentos.
- Regras: 7 dias de teste grátis → assinatura mensal → bloqueio automático 15
  dias após o vencimento não pago. Dois planos: Pró e Premium (preços
  configuráveis).
- Tabelas novas: saas_clients e saas_payments (criadas automaticamente).

Observação: o painel SaaS controla o cadastro, a cobrança e o status. A ligação
do bloqueio com cada instalação/tenant do restaurante depende da hospedagem
(multi-tenant) e pode ser conectada ao publicar na internet.

## V17 — Domínio próprio + Supabase (PostgreSQL)

### URLs amigáveis (domínio na raiz)
.htaccess da raiz + base href dinâmica. Endereços:
- /cardapio (cliente) · /motoboy · /painel · /cozinha · /garcom · /caixa · /pdv
- /admin → Central SaaS

### Banco de dados: SQLite OU Supabase (por config)
- `db()` agora suporta PostgreSQL/Supabase, escolhido por `db_driver` no config.php.
- Padrão continua SQLite (nada muda até você trocar e testar).
- `supabase-schema.sql`: schema Postgres completo (24 tabelas) + funções de
  compatibilidade (datetime/date/strftime) para o app rodar sem reescrever queries.
- `config.sample.php`: modelo com as chaves do banco/Supabase.
- `GUIA-SUPABASE.md`: passo a passo da migração.

Importante: a estrutura para Postgres está pronta, mas precisa de um teste real
apontando para o seu Supabase (não consigo testar isso aqui). Ao migrar, rode o
schema no Supabase, ajuste o config.php e valide os fluxos principais — qualquer
ajuste fino de SQL eu corrijo.
