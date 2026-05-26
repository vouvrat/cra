<?php
$ys     = $yearStats;
$worked = $ys['p'] + $ys['t'];
$pct    = $worked ? round($ys['p'] / $worked * 100) : 0;
// Config courante (pour les stats annuelles globales = config active aujourd'hui)
$km     = (float)($config['km']   ?? 0);
$dur    = (float)($config['duree']?? 0);
$indem  = (float)($config['indem'] ?? 0);
// Totaux annuels pondérés par config historique
$totalKm    = 0; $totalDur = 0; $totalIndem = 0;
if (!empty($configByMonth)) {
    foreach ($configByMonth as $mIdx => $mcfg) {
        $ms = $stats[$mIdx] ?? ['p'=>0];
        $totalKm    += $ms['p'] * $mcfg['km'];
        $totalDur   += $ms['p'] * $mcfg['duree'];
        $totalIndem += $ms['p'] * $mcfg['indem'];
    }
}
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
    <?php if ($totalKm>0): ?><div class="stat-card ckm"><div class="stat-lbl">KM TRAJET</div><div class="stat-val"><?=number_format($totalKm,0,',',' ')?></div><div class="stat-sub">km sur l'année</div></div><?php endif; ?>
    <?php if ($totalIndem>0): ?><div class="stat-card ckm"><div class="stat-lbl">INDEMNITÉS</div><div class="stat-val"><?=number_format($totalIndem,2,',',' ')?> €</div></div><?php endif; ?>
  </div>

  <?php if (!$readonly): ?>
  <!-- ── PÉRIODES DE CONFIGURATION ── -->
  <div style="background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);margin-bottom:20px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid var(--bd);display:flex;justify-content:space-between;align-items:center">
      <div>
        <span style="font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.06em">PARAMÈTRES DE TRAJET</span>
        <span style="font-size:11px;color:var(--mu);margin-left:10px">— Historique des distances/durées par période</span>
      </div>
      <button class="btn btn-sm btn-primary" onclick="openPeriodModal()">+ Nouvelle période</button>
    </div>

    <?php if (empty($configPeriods)): ?>
    <div style="padding:16px;font-size:12px;color:var(--mu);text-align:center">
      Aucune configuration. Ajoutez une période pour calculer vos trajets.
    </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="text-align:left;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd);letter-spacing:.04em">LIBELLÉ</th>
          <th style="text-align:left;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)">DU</th>
          <th style="text-align:left;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)">AU</th>
          <th style="text-align:center;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)">KM A/R</th>
          <th style="text-align:center;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)">DURÉE (MIN)</th>
          <th style="text-align:center;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)">INDEM./J</th>
          <th style="padding:8px 14px;border-bottom:1px solid var(--bd)"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($configPeriods as $period): ?>
      <?php $isCurrent = is_null($period['valid_to']); ?>
      <tr style="<?= $isCurrent ? 'background:rgba(108,99,255,.05)' : '' ?>">
        <td style="padding:9px 14px;font-size:13px;font-weight:600;border-bottom:1px solid #1e1e24">
          <?= htmlspecialchars($period['label']) ?>
          <?php if ($isCurrent): ?>
          <span style="font-size:10px;background:rgba(34,197,94,.12);color:var(--t);border-radius:3px;padding:1px 6px;font-family:'DM Mono',monospace;margin-left:6px;font-weight:400">ACTUELLE</span>
          <?php endif; ?>
        </td>
        <td style="padding:9px 14px;font-size:12px;font-family:'DM Mono',monospace;border-bottom:1px solid #1e1e24;color:var(--mu)">
          <?= date('d/m/Y', strtotime($period['valid_from'])) ?>
        </td>
        <td style="padding:9px 14px;font-size:12px;font-family:'DM Mono',monospace;border-bottom:1px solid #1e1e24;color:var(--mu)">
          <?= $period['valid_to'] ? date('d/m/Y', strtotime($period['valid_to'])) : '<span style="color:var(--t)">En cours</span>' ?>
        </td>
        <td style="padding:9px 14px;font-size:13px;font-family:'DM Mono',monospace;text-align:center;border-bottom:1px solid #1e1e24"><?= $period['km'] ?></td>
        <td style="padding:9px 14px;font-size:13px;font-family:'DM Mono',monospace;text-align:center;border-bottom:1px solid #1e1e24"><?= $period['duree'] ?></td>
        <td style="padding:9px 14px;font-size:13px;font-family:'DM Mono',monospace;text-align:center;border-bottom:1px solid #1e1e24"><?= $period['indem'] > 0 ? $period['indem'].' €' : '—' ?></td>
        <td style="padding:9px 14px;border-bottom:1px solid #1e1e24">
          <div style="display:flex;gap:6px;justify-content:flex-end">
            <button class="btn btn-sm" onclick='openPeriodModal(<?= htmlspecialchars(json_encode($period)) ?>)'>✏</button>
            <?php if (count($configPeriods) > 1): ?>
            <form method="POST" action="<?= BASE_URL ?>cra/config/period/delete" onsubmit="return confirm('Supprimer cette période ?')">
              <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
              <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
              <input type="hidden" name="year" value="<?= $year ?>">
              <?php if (!empty($target)): ?><input type="hidden" name="target_id" value="<?= $target['id'] ?>"><?php endif; ?>
              <button type="submit" class="btn btn-sm btn-danger">✕</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- MODAL PÉRIODE -->
  <div class="modal-backdrop" id="periodModal">
    <div class="modal" style="width:480px">
      <h3 id="periodModalTitle">Nouvelle période de configuration</h3>
      <form method="POST" action="<?= BASE_URL ?>cra/config/period" id="periodForm">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
        <input type="hidden" name="period_id" id="periodId" value="0">
        <input type="hidden" name="year" value="<?= $year ?>">
        <?php if (!empty($target)): ?><input type="hidden" name="target_id" value="<?= $target['id'] ?>"><?php endif; ?>
        <div class="form-group">
          <label>LIBELLÉ (ex: Après déménagement, Nouveau poste…)</label>
          <input type="text" name="label" id="periodLabel" placeholder="Description de ce changement" required>
        </div>
        <div class="form-group">
          <label>DATE D'EFFET</label>
          <input type="date" name="valid_from" id="periodFrom" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>DISTANCE A/R (KM)</label>
            <input type="number" name="km" id="periodKm" min="0" max="9999" step="0.5" value="0">
          </div>
          <div class="form-group">
            <label>DURÉE A/R (MIN)</label>
            <input type="number" name="duree" id="periodDuree" min="0" max="999" value="0">
          </div>
          <div class="form-group">
            <label>INDEMNITÉ/JOUR (€)</label>
            <input type="number" name="indem" id="periodIndem" min="0" max="999" step="0.5" value="0">
          </div>
        </div>
        <div style="font-size:11px;color:var(--mu);margin-bottom:14px">
          Les périodes précédentes sont automatiquement clôturées à la date d'effet - 1 jour.
        </div>
        <div class="modal-actions">
          <button type="button" class="btn" onclick="closePeriodModal()">Annuler</button>
          <button type="submit" class="btn btn-primary" id="periodSubmitBtn">Ajouter</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openPeriodModal(period) {
    const modal = document.getElementById('periodModal');
    if (period) {
      document.getElementById('periodModalTitle').textContent = 'Modifier la période';
      document.getElementById('periodId').value    = period.id;
      document.getElementById('periodLabel').value = period.label;
      document.getElementById('periodFrom').value  = period.valid_from;
      document.getElementById('periodKm').value    = period.km;
      document.getElementById('periodDuree').value = period.duree;
      document.getElementById('periodIndem').value = period.indem;
      document.getElementById('periodSubmitBtn').textContent = 'Enregistrer';
    } else {
      document.getElementById('periodModalTitle').textContent = 'Nouvelle période';
      document.getElementById('periodId').value    = '0';
      document.getElementById('periodLabel').value = '';
      document.getElementById('periodFrom').value  = new Date().toISOString().split('T')[0];
      document.getElementById('periodKm').value    = '0';
      document.getElementById('periodDuree').value = '0';
      document.getElementById('periodIndem').value = '0';
      document.getElementById('periodSubmitBtn').textContent = 'Ajouter';
    }
    modal.classList.add('open');
  }
  function closePeriodModal() {
    document.getElementById('periodModal').classList.remove('open');
  }
  document.getElementById('periodModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closePeriodModal();
  });
  </script>

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
        <?php if ($totalKm>0): ?><th style="color:#a78bfa">KM</th><?php endif; ?>
        <?php if ($totalIndem>0): ?><th style="color:#a78bfa">INDEM.</th><?php endif; ?>
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
        <?php $mc=$configByMonth[$m]??['km'=>0,'indem'=>0]; ?>
        <?php if ($totalKm>0): ?><td style="color:#a78bfa;font-family:'DM Mono',monospace"><?=round($s['p']*$mc['km'])?></td><?php endif; ?>
        <?php if ($totalIndem>0): ?><td style="color:#a78bfa;font-family:'DM Mono',monospace"><?=number_format($s['p']*$mc['indem'],2,',',' ')?>€</td><?php endif; ?>
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
        <?php if ($totalKm>0): ?><td style="font-family:'DM Mono',monospace"><?=round($totalKm)?></td><?php endif; ?>
        <?php if ($totalIndem>0): ?><td style="font-family:'DM Mono',monospace"><?=number_format($totalIndem,2,',',' ')?>€</td><?php endif; ?>
      </tr>
      </tfoot>
    </table>
  </div>
</div>
