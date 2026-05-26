<?php
$ys     = $yearStats;
$worked = $ys['p'] + $ys['t'];
$pct    = $worked ? round($ys['p'] / $worked * 100) : 0;
$km     = (float)($config['km']   ?? 40);
$dur    = (float)($config['duree']?? 60);
$indem  = (float)($config['indem'] ?? 0);
$urlPfx = $readonly && $target ? "view/{$target['id']}/" : '';
$mnames = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
?>
<div class="topbar">
  <div class="topbar-title">
    <?= $readonly && $target ? '👁 '.htmlspecialchars($target['name']).' — ' : '' ?>Vue annuelle <?= $year ?>
  </div>
  <div class="topbar-actions">
    <?php $navPfx = $urlPfx ?: 'cra/'; ?>
    <button class="btn btn-sm btn-year" onclick="location.href='<?=BASE_URL?><?=$navPfx?>year/<?=$year-1?>'">← <?=$year-1?></button>
    <button class="btn btn-sm btn-year" onclick="location.href='<?=BASE_URL?><?=$navPfx?>year/<?=$year+1?>'"><?=$year+1?> →</button>
    <?php if (!$readonly): ?>
    <a class="btn btn-sm" href="<?=BASE_URL?>cra/export/<?=$year?>">⬇ CSV</a>
    <?php else: ?>
    <a class="btn btn-sm" href="<?=BASE_URL?>view/<?=$target['id']?>/export/<?=$year?>">⬇ CSV</a>
    <?php endif; ?>
  </div>
</div>
<div class="content">
  <?php if ($target && $target['id'] !== $me['id']): ?>
  <?php $isVirtual = !empty($target['virtual']); ?>
  <div class="delegate-banner">
    <?= $isVirtual ? '✏' : '⚠' ?>
    <?= $isVirtual ? 'Vous saisissez le CRA de' : 'Vous consultez le CRA de' ?>
    <strong><?= htmlspecialchars($target['name']) ?></strong>
    <?= $isVirtual ? '— compte virtuel' : '— lecture seule' ?>
  </div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card cp"><div class="stat-lbl">PRÉSENTIEL</div><div class="stat-val"><?=$ys['p']?></div><div class="stat-sub">jours</div></div>
    <div class="stat-card ct"><div class="stat-lbl">TÉLÉTRAVAIL</div><div class="stat-val"><?=$ys['t']?></div></div>
    <div class="stat-card cr"><div class="stat-lbl">RTT</div><div class="stat-val"><?=$ys['r']?></div></div>
    <div class="stat-card cc"><div class="stat-lbl">CONGÉS PAYÉS</div><div class="stat-val"><?=$ys['c']?></div></div>
    <div class="stat-card cs"><div class="stat-lbl">SANS SOLDE</div><div class="stat-val"><?=$ys['s']?></div></div>
    <div class="stat-card cf"><div class="stat-lbl">JOURS FÉRIÉS</div><div class="stat-val"><?=$ys['f']?></div></div>
    <div class="stat-card cw"><div class="stat-lbl">TRAVAILLÉ</div><div class="stat-val"><?=$worked?></div><div class="stat-sub"><?=$pct?>% présent.</div></div>
    <?php if ($km>0): ?><div class="stat-card ckm"><div class="stat-lbl">KM TRAJET</div><div class="stat-val"><?=number_format($ys['p']*$km,0,',',' ')?></div><div class="stat-sub">km</div></div><?php endif; ?>
    <?php if ($indem>0): ?><div class="stat-card ckm"><div class="stat-lbl">INDEMNITÉS</div><div class="stat-val"><?=number_format($ys['p']*$indem,2,',',' ')?> €</div></div><?php endif; ?>
  </div>

  <?php if (!$readonly): ?>
  <div style="background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:14px;margin-bottom:20px">
    <div style="font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.06em;margin-bottom:10px">PARAMÈTRES DE TRAJET</div>
    <div class="cfg-row">
      <div class="cfg-field"><label>KM A/R</label><input type="number" id="cfgKm" value="<?=$km?>" min="0"></div>
      <div class="cfg-field"><label>DURÉE A/R (MIN)</label><input type="number" id="cfgDur" value="<?=$dur?>" min="0"></div>
      <div class="cfg-field"><label>INDEMNITÉ/JOUR (€)</label><input type="number" id="cfgIndem" value="<?=$indem?>" min="0" step="0.5"></div>
      <button class="btn btn-sm" onclick="saveConfig(document.getElementById('cfgKm').value,document.getElementById('cfgDur').value,document.getElementById('cfgIndem').value)">Enregistrer</button>
    </div>
  </div>
  <?php endif; ?>

  <!-- YEAR GRID -->
  <div class="year-grid">
  <?php
  $typeColors = ['p'=>'var(--p)','t'=>'var(--t)','r'=>'var(--r)','c'=>'var(--c)','s'=>'var(--s)','f'=>'var(--f)'];
  for ($m=1; $m<=12; $m++):
    $s   = $stats[$m];
    $w   = $s['p'] + $s['t'];
    $tot = max(1, $s['p']+$s['t']+$s['r']+$s['c']+$s['s']+$s['f']);
    $url = $readonly && $target
      ? BASE_URL."view/{$target['id']}/month/{$year}/{$m}"
      : BASE_URL."cra/month/{$year}/{$m}";
  ?>
  <div class="month-card" onclick="location.href='<?=$url?>'">
    <div class="month-name"><?=$mnames[$m-1]?></div>
    <div class="month-bar">
      <?php foreach (['p','t','r','c','s','f'] as $k):
        $w2 = $s[$k] ? round($s[$k]/$tot*100) : 0; ?>
      <div class="month-bar-seg" style="background:<?=$typeColors[$k]?>;width:<?=$w2?>%;opacity:.8"></div>
      <?php endforeach; ?>
    </div>
    <div class="month-rows">
      <div class="month-row"><span class="lbl"><span class="dot" style="background:var(--p)"></span>Présentiel</span><span class="val" style="color:var(--p)"><?=$s['p']?>j</span></div>
      <div class="month-row"><span class="lbl"><span class="dot" style="background:var(--t)"></span>Télétravail</span><span class="val" style="color:var(--t)"><?=$s['t']?>j</span></div>
      <div class="month-row"><span class="lbl"><span class="dot" style="background:var(--r)"></span>RTT</span><span class="val" style="color:var(--r)"><?=$s['r']?>j</span></div>
      <div class="month-row"><span class="lbl"><span class="dot" style="background:var(--c)"></span>Congés</span><span class="val" style="color:var(--c)"><?=$s['c']?>j</span></div>
      <?php if ($s['s']>0): ?><div class="month-row"><span class="lbl"><span class="dot" style="background:var(--s)"></span>Sans solde</span><span class="val" style="color:var(--s)"><?=$s['s']?>j</span></div><?php endif; ?>
      <?php if ($s['nr']>0): ?><div class="month-row"><span class="lbl" style="color:rgba(127,127,143,.5)">Non saisi</span><span class="val" style="color:var(--mu)"><?=$s['nr']?>j</span></div><?php endif; ?>
    </div>
  </div>
  <?php endfor; ?>
  </div>

  <!-- RECAP TABLE -->
  <div style="margin-top:20px;overflow-x:auto;background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad)">
    <table>
      <thead><tr>
        <th>MOIS</th><th>OUVRÉS</th>
        <th style="color:var(--p)">PRÉS.</th><th style="color:var(--t)">TT</th>
        <th style="color:var(--r)">RTT</th><th style="color:var(--c)">CP</th>
        <th style="color:var(--s)">SS</th>
        <th style="color:var(--f)">FÉRIÉS</th><th>NR</th><th>TRAVAILLÉ</th>
        <?php if ($km>0): ?><th style="color:#a78bfa">KM</th><?php endif; ?>
        <?php if ($indem>0): ?><th style="color:#a78bfa">INDEM.</th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php for ($m=1; $m<=12; $m++):
        $s=$stats[$m]; $w=$s['p']+$s['t'];
        $url = $readonly && $target
          ? BASE_URL."view/{$target['id']}/month/{$year}/{$m}"
          : BASE_URL."cra/month/{$year}/{$m}";
      ?>
      <tr style="cursor:pointer" onclick="location.href='<?=$url?>'">
        <td style="font-weight:600"><?=$mnames[$m-1]?></td>
        <td style="font-family:'DM Mono',monospace"><?=$s['p']+$s['t']+$s['r']+$s['c']+$s['s']+$s['f']+$s['nr']?></td>
        <td><span class="badge bp"><?=$s['p']?></span></td>
        <td><span class="badge bt"><?=$s['t']?></span></td>
        <td><span class="badge br"><?=$s['r']?></span></td>
        <td><span class="badge bc"><?=$s['c']?></span></td>
        <td><span class="badge bs"><?=$s['s']?></span></td>
        <td style="color:var(--f);font-family:'DM Mono',monospace"><?=$s['f']?></td>
        <td style="color:var(--mu);font-family:'DM Mono',monospace"><?=$s['nr']?></td>
        <td style="font-weight:700;font-family:'DM Mono',monospace"><?=$w?></td>
        <?php if ($km>0): ?><td style="color:#a78bfa;font-family:'DM Mono',monospace"><?=round($s['p']*$km)?></td><?php endif; ?>
        <?php if ($indem>0): ?><td style="color:#a78bfa;font-family:'DM Mono',monospace"><?=number_format($s['p']*$indem,2,',',' ')?>€</td><?php endif; ?>
      </tr>
      <?php endfor; ?>
      </tbody>
      <tfoot>
      <?php $tw=$ys['p']+$ys['t']; ?>
      <tr class="total">
        <td>TOTAL <?=$year?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['p']+$ys['t']+$ys['r']+$ys['c']+$ys['s']+$ys['f']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['p']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['t']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['r']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['c']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['s']?></td>
        <td style="font-family:'DM Mono',monospace"><?=$ys['f']?></td>
        <td style="font-family:'DM Mono',monospace">—</td>
        <td style="font-family:'DM Mono',monospace"><?=$tw?></td>
        <?php if ($km>0): ?><td style="font-family:'DM Mono',monospace"><?=round($ys['p']*$km)?></td><?php endif; ?>
        <?php if ($indem>0): ?><td style="font-family:'DM Mono',monospace"><?=number_format($ys['p']*$indem,2,',',' ')?>€</td><?php endif; ?>
      </tr>
      </tfoot>
    </table>
  </div>
</div>
