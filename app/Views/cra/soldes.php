<?php
if (!function_exists('fmtStat')) { function fmtStat($v) { return $v == floor($v) ? (int)$v : number_format($v,1,',',''); } }
$isOther = !empty($target) && $target['id'] !== $me['id'];
$qs      = $isOther ? '?u=' . $target['id'] : '';
$periodLabel = Models\Leave::label($period);
[$pFrom, $pTo] = Models\Leave::bounds($period);
$fr = fn($d) => date('d/m/Y', strtotime($d));
?>
<div class="topbar">
  <div class="topbar-title">
    <?= $isOther ? '👁 ' . htmlspecialchars($target['name']) . ' — ' : '' ?>Soldes RTT / congés — <?= $periodLabel ?>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-sm btn-year" onclick="location.href='<?= BASE_URL ?>cra/soldes/<?= $period - 1 ?><?= $qs ?>'">← <?= Models\Leave::label($period - 1) ?></button>
    <button class="btn btn-sm btn-year" onclick="location.href='<?= BASE_URL ?>cra/soldes/<?= $period + 1 ?><?= $qs ?>'"><?= Models\Leave::label($period + 1) ?> →</button>
  </div>
</div>
<div class="content">
  <?php if ($isOther): ?>
  <div class="delegate-banner">
    <?= $readonly ? '⚠ Vous consultez les soldes de' : '✏ Vous gérez les soldes de' ?>
    <strong><?= htmlspecialchars($target['name']) ?></strong>
    <?= $readonly ? '— lecture seule' : '— compte virtuel' ?>
  </div>
  <?php endif; ?>

  <div style="font-size:12px;color:var(--mu);margin-bottom:16px">
    Exercice du <?= $fr($pFrom) ?> au <?= $fr($pTo) ?>. Les jours pris sont calculés automatiquement à partir des jours saisis dans le CRA
    (demi-journée = 0,5). Seuls les droits sont à saisir ici.
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-bottom:24px">
  <?php foreach (['r' => 'cr', 'c' => 'cc'] as $t => $cls): $L = $leave[$t]; ?>
    <div style="background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:16px">
      <div style="font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.06em;margin-bottom:12px">
        <?= mb_strtoupper($L['label']) ?>
        <?php if (!$L['configured']): ?><span style="margin-left:8px;color:var(--r)">droits non saisis</span><?php endif; ?>
      </div>

      <div class="stats-grid" style="margin-bottom:14px">
        <div class="stat-card <?= $cls ?>">
          <div class="stat-lbl">SOLDE À CE JOUR</div>
          <div class="stat-val" style="<?= $L['now'] < 0 ? 'color:var(--c)' : '' ?>"><?= fmtStat($L['now']) ?></div>
          <div class="stat-sub">droits − jours pris</div>
        </div>
        <div class="stat-card <?= $cls ?>">
          <div class="stat-lbl">SOLDE PRÉVISIONNEL</div>
          <div class="stat-val" style="<?= $L['final'] < 0 ? 'color:var(--c)' : '' ?>"><?= fmtStat($L['final']) ?></div>
          <div class="stat-sub">si tous les jours posés sont pris</div>
        </div>
      </div>

      <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:14px">
        <tr><td style="padding:5px 0;color:var(--mu)">Droits de l'exercice</td><td style="text-align:right;font-family:'DM Mono',monospace"><?= fmtStat($L['acquis']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--mu)">Report</td><td style="text-align:right;font-family:'DM Mono',monospace"><?= fmtStat($L['report']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--mu)">Jours pris (jusqu'à aujourd'hui)</td><td style="text-align:right;font-family:'DM Mono',monospace">− <?= fmtStat($L['taken']) ?></td></tr>
        <tr><td style="padding:5px 0;color:var(--mu)">Jours posés (à venir)</td><td style="text-align:right;font-family:'DM Mono',monospace">− <?= fmtStat($L['planned']) ?></td></tr>
      </table>

      <?php if (!$readonly): ?>
      <form method="POST" action="<?= BASE_URL ?>cra/soldes/save">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
        <input type="hidden" name="type" value="<?= $t ?>">
        <input type="hidden" name="period" value="<?= $period ?>">
        <?php if ($isOther): ?><input type="hidden" name="target_id" value="<?= $target['id'] ?>"><?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>DROITS DE L'EXERCICE (J)</label>
            <input type="number" name="acquis" min="0" max="365" step="0.5" value="<?= $L['acquis'] ?>">
          </div>
          <div class="form-group">
            <label>REPORT (J)</label>
            <input type="number" name="report" id="rep_<?= $t ?>" min="0" max="365" step="0.5"
                   value="<?= $L['configured'] ? $L['report'] : ($t === 'c' && $L['suggested'] !== null ? max(0, $L['suggested']) : 0) ?>">
          </div>
        </div>
        <?php if ($L['suggested'] !== null): ?>
        <div style="font-size:11px;color:var(--mu);margin:-6px 0 12px">
          Solde restant de l'exercice <?= Models\Leave::label($period - 1) ?> : <strong><?= fmtStat($L['suggested']) ?></strong> j
          <?= $t === 'r' ? '(les RTT ne sont reportés que si votre employeur l\'autorise : à vous de saisir la valeur).' : '(proposé comme report, modifiable).' ?>
          <?php if ($t === 'c' && $L['configured'] && abs($L['suggested'] - $L['report']) > 0.01): ?>
          <a href="#" onclick="document.getElementById('rep_<?= $t ?>').value=<?= max(0, $L['suggested']) ?>;return false">Utiliser cette valeur</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </div>

  <?php if (!empty($history)): ?>
  <div style="background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid var(--bd);font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.06em">HISTORIQUE DES EXERCICES</div>
    <table style="width:100%;border-collapse:collapse">
      <thead><tr>
        <?php foreach (['EXERCICE','RTT DROITS+REPORT','RTT PRIS/POSÉS','RTT RESTE','CP DROITS+REPORT','CP PRIS/POSÉS','CP RESTE'] as $i => $h): ?>
        <th style="text-align:<?= $i ? 'center' : 'left' ?>;font-size:10px;font-family:'DM Mono',monospace;color:var(--mu);padding:8px 14px;border-bottom:1px solid var(--bd)"><?= $h ?></th>
        <?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($history as $p => $h): ?>
        <tr style="<?= $p === $period ? 'background:rgba(108,99,255,.05)' : '' ?>">
          <td style="padding:9px 14px;border-bottom:1px solid var(--bd);font-size:13px;font-weight:600">
            <a href="<?= BASE_URL ?>cra/soldes/<?= $p ?><?= $qs ?>"><?= Models\Leave::label($p) ?></a>
          </td>
          <?php foreach (['r', 'c'] as $t): $x = $h[$t]; ?>
          <td style="padding:9px 14px;border-bottom:1px solid var(--bd);text-align:center;font-family:'DM Mono',monospace;font-size:12px"><?= fmtStat($x['acquis'] + $x['report']) ?></td>
          <td style="padding:9px 14px;border-bottom:1px solid var(--bd);text-align:center;font-family:'DM Mono',monospace;font-size:12px"><?= fmtStat($x['taken']) ?> / <?= fmtStat($x['planned']) ?></td>
          <td style="padding:9px 14px;border-bottom:1px solid var(--bd);text-align:center;font-family:'DM Mono',monospace;font-size:13px;font-weight:600;<?= $x['final'] < 0 ? 'color:var(--c)' : '' ?>"><?= fmtStat($x['final']) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
