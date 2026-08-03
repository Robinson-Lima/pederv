<div class="wrap dash">
  <h2><?= e(cfg('restaurante')) ?></h2>
  <?= admin_subnav('admin_caixa') ?>

  <?php if(!$cx): ?>
    <!-- CAIXA FECHADO -->
    <div class="cxcard">
      <div class="cxst fechado">● Caixa fechado</div>
      <form method="post" action="?r=caixa_abrir" class="cxform">
        <label>Operador do caixa</label>
        <?php if(!empty($operadores)): ?>
          <select name="operador" required style="width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;margin-bottom:10px">
            <option value="">Quem vai operar?</option>
            <?php foreach($operadores as $op): ?><option><?= e($op['nome']) ?></option><?php endforeach; ?>
          </select>
        <?php else: ?>
          <input name="operador" placeholder="Nome do operador" required style="width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;margin-bottom:10px">
          <p style="font-size:11px;color:#727884;margin:0 0 8px">Cadastre operadores em ⚙ Config para escolher numa lista.</p>
        <?php endif; ?>
        <label>Saldo inicial (troco em dinheiro)</label>
        <div class="cxrow">
          <input name="saldo_inicial" placeholder="100,00" required>
          <button class="cxgo">Abrir caixa</button>
        </div>
      </form>
    </div>

  <?php else:
    $esperado = $cx['saldo_inicial'] + ($tot['venda']??0) + ($tot['suprimento']??0) - ($tot['retirada']??0);
  ?>
    <!-- CAIXA ABERTO -->
    <div class="cxcard">
      <div class="cxst aberto">● Caixa aberto desde <?= e(substr($cx['aberto_em'],11,5)) ?> · <?= e(substr($cx['aberto_em'],0,10)) ?>
        <?php if(!empty($cx['operador'])): ?><span style="color:var(--brand)"> · Operador: <?= e($cx['operador']) ?></span><?php endif; ?></div>
      <div class="cxgrid">
        <div class="stat"><div class="l">Saldo na abertura</div><div class="v"><?= money($cx['saldo_inicial']) ?></div></div>
        <div class="stat"><div class="l">Vendas (+)</div><div class="v" style="color:#22A06B"><?= money($tot['venda']??0) ?></div></div>
        <div class="stat"><div class="l">Retiradas / gastos (−)</div><div class="v" style="color:#c8342b"><?= money($tot['retirada']??0) ?></div></div>
        <div class="stat"><div class="l">Suprimentos (+)</div><div class="v"><?= money($tot['suprimento']??0) ?></div></div>
      </div>
      <div class="cxesper">Esperado em caixa: <b><?= money($esperado) ?></b></div>
      <?php if(!empty($isAdminUser)): ?><a href="?r=admin_cash_detail&id=<?= (int)$cx['id'] ?>" class="cxgo" style="margin-right:8px">📋 Ver extrato e vendas</a><?php endif; ?>
      <a href="javascript:void(0)" onclick="document.getElementById('fechar').scrollIntoView({behavior:'smooth'})" class="cxgo close-cash-main">🔒 FECHAR CAIXA</a>
    </div>

    <div class="mtgrid cash-workspace">
      <div>
        <div class="paysec">− Lançar retirada / gasto</div>
        <form method="post" action="?r=caixa_mov" class="cxlanc">
          <input type="hidden" name="tipo" value="retirada">
          <select name="categoria">
            <option>Mercadoria</option><option>Combustível</option><option>Embalagens</option>
            <option>Salário/Diária</option><option>Sangria</option><option>Outros</option>
          </select>
          <input name="descricao" placeholder="Descrição (ex: carne p/ estoque)">
          <input name="valor" placeholder="Valor (ex: 50,00)" required>
          <button class="cxgo neg">Lançar gasto</button>
        </form>

        <div class="paysec" style="margin-top:16px">+ Lançar suprimento (reforço)</div>
        <form method="post" action="?r=caixa_mov" class="cxlanc">
          <input type="hidden" name="tipo" value="suprimento">
          <input type="hidden" name="categoria" value="Suprimento">
          <input name="descricao" placeholder="Descrição (ex: troco)">
          <input name="valor" placeholder="Valor" required>
          <button class="cxgo">Lançar suprimento</button>
        </form>

        <div class="paysec" style="margin-top:16px" id="fechar">🔒 Fechar caixa</div>
        <form method="post" action="?r=caixa_fechar" class="cxlanc"
              onsubmit="return confirm('Fechar o caixa do dia?')">
          <input name="saldo_informado" placeholder="Quanto tem em caixa? (contagem)" required>
          <button class="cxgo close-cash-button">FECHAR CAIXA</button>
        </form>
        <p style="font-size:11.5px;color:#727884">Ao fechar, o sistema compara o contado com o esperado (<?= money($esperado) ?>) e guarda a diferença no histórico.</p>
      </div>

      <div>
        <div class="paysec">📋 Extrato do caixa — movimentos de hoje</div>
        <p class="cash-help">Clique em uma venda para conferir produtos, horário, forma de pagamento e valor.</p>
        <?php if(!$movs): ?><p style="color:#727884;font-size:13px">Nenhum movimento ainda.</p><?php endif; ?>
        <?php foreach($movs as $m): ?>
          <?php if($m['tipo']==='venda' && !empty($m['order_id']) && !empty($itensVenda[$m['order_id']])): ?>
            <details class="vrow">
              <summary>
                <div class="w"><div class="nm"><?= e($m['descricao']) ?> <span style="font-size:10px;color:#727884">(ver itens)</span></div>
                  <div class="mt"><?= e($m['categoria']) ?> · <?= e(substr($m['criado_em'],11,5)) ?></div></div>
                <div class="amt" style="color:#22A06B">+ <?= money($m['valor']) ?></div>
              </summary>
              <div class="vitens">
                <?php foreach($itensVenda[$m['order_id']] as $iv): ?>
                  <div class="vi"><span><?= (int)$iv['qtd'] ?>× <?= e($iv['nome']) ?></span><b><?= money($iv['qtd']*$iv['preco']) ?></b></div>
                <?php endforeach; ?>
              </div>
            </details>
          <?php else: ?>
            <div class="pcol">
              <div class="w">
                <div class="nm"><?= e($m['descricao'] ?: ucfirst($m['tipo'])) ?></div>
                <div class="mt"><?= e($m['categoria']) ?> · <?= e(substr($m['criado_em'],11,5)) ?></div>
              </div>
              <div class="amt" style="color:<?= $m['tipo']==='retirada'?'#c8342b':'#22A06B' ?>">
                <?= $m['tipo']==='retirada'?'−':'+' ?> <?= money($m['valor']) ?></div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if(!empty($isAdminUser) && $hist): ?>
    <div class="paysec" style="margin-top:20px">🗂 Fechamentos anteriores</div>
    <?php foreach($hist as $h):
      $m=db()->prepare("SELECT tipo, SUM(valor) v FROM caixa_mov WHERE caixa_id=? GROUP BY tipo"); $m->execute([$h['id']]);
      $t=['venda'=>0,'retirada'=>0,'suprimento'=>0]; foreach($m->fetchAll() as $x) $t[$x['tipo']]=$x['v'];
      $esp=$h['saldo_inicial']+$t['venda']+$t['suprimento']-$t['retirada'];
      $dif=($h['saldo_informado']??0)-$esp;
    ?>
      <div class="pcol">
        <div class="w"><div class="nm"><?= e(substr($h['aberto_em'],0,10)) ?><?= !empty($h['operador'])?' · '.e($h['operador']):'' ?></div>
          <div class="mt">vendas <?= money($t['venda']) ?> · gastos <?= money($t['retirada']) ?></div></div>
        <div class="amt">esperado <?= money($esp) ?><br>
          <small style="color:<?= abs($dif)<0.01?'#22A06B':'#c8342b' ?>;font-weight:800">
            <?= abs($dif)<0.01 ? 'caixa bateu ✓' : 'diferença '.money($dif) ?></small><br><a class="btn-mini" href="?r=admin_cash_detail&id=<?= (int)$h['id'] ?>">Ver vendas e itens</a></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
