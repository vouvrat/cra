<?php
$km    = (float)($config['km']    ?? 40);
$dur   = (float)($config['duree'] ?? 60);
$indem = (float)($config['indem'] ?? 0);
$mnames = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$total  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDow = ((int)date('w', strtotime("$year-$month-01")) + 6) % 7; // Lun=0

function fmtTime($mins) {
    $h = floor($mins/60); $m = round($mins%60);
    return $h>0 ? "{$h}h".($m>0?str_pad($m,2,'0',STR_PAD_LEFT)."min":'') : "{$m}min";
}

$prevM = $month==1?12:$month-1; $prevY = $month==1?$year-1:$year;
$nextM = $month==12?1:$month+1; $nextY = $month==12?$year+1:$year;
$urlPfx = $readonly && $target ? "view/{$target['id']}/" : 'cra/';

// Nb jours ouvrables du mois (hors férié)
$ouvrables = array_reduce(range(1,$total), function($c,$d) use($year,$month,$feries){
    $date = sprintf('%04d-%02d-%02d',$year,$month,$d);
    $dow  = (int)date('w',strtotime($date));
    return $c + (!in_array($dow,[0,6]) && !in_array($date,$feries) ? 1 : 0);
}, 0);

$ys = $stats;
$ys['s']  = $ys['s']  ?? 0;
$ys['nr'] = max(0, $ouvrables - $ys['p'] - $ys['t'] - $ys['r'] - $ys['c'] - $ys['s'] - $ys['f']);
?>
<div class="topbar">
  <div class="topbar-title">
    <?= $readonly && $target ? '👁 '.htmlspecialchars($target['name']).' — ' : '' ?>
    <?= $mnames[$month] ?> <?= $year ?>
  </div>
  <div class="topbar-actions">
    <a class="btn btn-sm" href="<?=BASE_URL?><?=$urlPfx?>month/<?=$prevY?>/<?=$prevM?>">← <?=$mnames[$prevM]?></a>
    <a class="btn btn-sm" href="<?=BASE_URL?><?= $readonly && $target ? "view/{$target['id']}/" : 'cra/' ?>year/<?=$year?>">📅 <?=$year?></a>
    <a class="btn btn-sm" href="<?=BASE_URL?><?=$urlPfx?>month/<?=$nextY?>/<?=$nextM?>"><?=$mnames[$nextM]?> →</a>
    <?php if (!$readonly): ?>
    <a class="btn btn-sm" href="<?=BASE_URL?>cra/export/<?=$year?>">⬇ CSV <?=$year?></a>
    <?php endif; ?>
  </div>
</div>
<div class="content">
  <?php if ($target && $target['id'] !== $me['id']): ?>
  <?php $isVirtual = !empty($target['virtual']); ?>
  <div class="delegate-banner">
    <?= $isVirtual ? '✏' : '⚠' ?>
    <?= $isVirtual ? 'Vous saisissez le CRA de' : 'Vous consultez (lecture seule) le CRA de' ?>
    <strong><?= htmlspecialchars($target['name']) ?></strong>
  </div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card cp"><div class="stat-lbl">PRÉSENTIEL</div><div class="stat-val"><?=$ys['p']?></div></div>
    <div class="stat-card ct"><div class="stat-lbl">TÉLÉTRAVAIL</div><div class="stat-val"><?=$ys['t']?></div></div>
    <div class="stat-card cr"><div class="stat-lbl">RTT</div><div class="stat-val"><?=$ys['r']?></div></div>
    <div class="stat-card cc"><div class="stat-lbl">CONGÉS</div><div class="stat-val"><?=$ys['c']?></div></div>
    <div class="stat-card cs"><div class="stat-lbl">SANS SOLDE</div><div class="stat-val"><?=$ys['s']?></div></div>
    <div class="stat-card cf"><div class="stat-lbl">FÉRIÉS</div><div class="stat-val"><?=$ys['f']?></div></div>
    <div class="stat-card"><div class="stat-lbl">NON SAISI</div><div class="stat-val" style="color:var(--mu)"><?=$ys['nr']?></div></div>
  </div>

  <div class="month-layout">
    <!-- CALENDAR -->
    <div class="cal-wrap">
      <div class="cal-head">
        <h2><?=$mnames[$month]?> <?=$year?></h2>
      </div>
      <div class="cal-dow">
        <?php foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $d): ?>
        <div><?=$d?></div>
        <?php endforeach; ?>
      </div>
      <div class="cal-grid">
        <?php for ($i=0;$i<$firstDow;$i++): ?><div class="cal-day empty"></div><?php endfor; ?>
        <?php for ($d=1; $d<=$total; $d++):
          $date  = sprintf('%04d-%02d-%02d',$year,$month,$d);
          $dow   = (int)date('w',strtotime($date));
          $isWe  = in_array($dow,[0,6]);
          $isFer = in_array($date,$feries) && !$isWe;
          $type  = $days[$date] ?? ($isFer ? 'f' : null);
          $cls   = 'cal-day';
          if ($isWe) $cls .= ' we';
          elseif ($type) $cls .= ' d'.$type;
          if ($date === date('Y-m-d')) $cls .= ' today';
          if (!empty($notes[$date])) $cls .= ' has-note';
        ?>
        <div class="<?=$cls?>"
          <?php if (!$isWe && !$readonly): ?>
            onclick="clickDay('<?=$date?>',<?=json_encode($type)?>)"
            ondblclick="openNote('<?=$date?>')"
          <?php endif; ?>
          title="<?=$date?><?=!empty($notes[$date])?' — '.htmlspecialchars($notes[$date]):''?>"
        ><?=$d?></div>
        <?php endfor; ?>
      </div>
      <p class="hint">Clic = saisir type · Double-clic = note</p>
    </div>

    <!-- RIGHT PANEL -->
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php if (!$readonly): ?>
      <div class="type-panel">
        <div class="type-panel-lbl">TYPE DE JOURNÉE</div>
        <div class="type-btns" id="typeBtns">
          <div class="type-btn" id="btn-p" onclick="setMode('p')"><span class="dot" style="background:var(--p)"></span>Présentiel<span class="type-key">P</span></div>
          <div class="type-btn" id="btn-t" onclick="setMode('t')"><span class="dot" style="background:var(--t)"></span>Télétravail<span class="type-key">T</span></div>
          <div class="type-btn" id="btn-r" onclick="setMode('r')"><span class="dot" style="background:var(--r)"></span>RTT<span class="type-key">R</span></div>
          <div class="type-btn" id="btn-c" onclick="setMode('c')"><span class="dot" style="background:var(--c)"></span>Congé payé<span class="type-key">C</span></div>
          <div class="type-btn" id="btn-s" onclick="setMode('s')"><span class="dot" style="background:var(--s)"></span>Sans solde<span class="type-key">S</span></div>
          <div class="type-btn" id="btn-f" onclick="setMode('f')"><span class="dot" style="background:var(--f)"></span>Férié<span class="type-key">F</span></div>
          <div class="type-btn" id="btn-none" onclick="setMode('none')"><span>⌫</span>Effacer<span class="type-key">0</span></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- TRAJET -->
      <div class="trajet-box">
        <div class="trajet-title">TRAJETS DU MOIS</div>
        <?php if ($km>0): ?><div class="trajet-row"><span class="trajet-lbl">Km A/R</span><span class="trajet-val"><?=round($ys['p']*$km)?> km</span></div><?php endif; ?>
        <?php if ($dur>0): ?><div class="trajet-row"><span class="trajet-lbl">Temps trajet</span><span class="trajet-val"><?=fmtTime($ys['p']*$dur)?></span></div><?php endif; ?>
        <?php if ($indem>0): ?><div class="trajet-row"><span class="trajet-lbl">Indemnités</span><span class="trajet-val"><?=number_format($ys['p']*$indem,2,',',' ')?> €</span></div><?php endif; ?>
        <?php if (!$km && !$dur && !$indem): ?><div style="font-size:11px;color:var(--mu)">Configurer les paramètres sur la vue annuelle.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- NOTE MODAL -->
<div class="modal-backdrop" id="noteModal">
  <div class="modal">
    <h3>Note — <span id="noteDate"></span></h3>
    <textarea id="noteContent" placeholder="Ajouter une note pour ce jour…" rows="4"></textarea>
    <div class="modal-actions">
      <button class="btn" onclick="closeNote()">Annuler</button>
      <button class="btn btn-primary" onclick="submitNote()">Enregistrer</button>
    </div>
  </div>
</div>

<script>
let mode = 'p';
const notes = <?= json_encode($notes) ?>;

function setMode(m){
  mode = m;
  ['p','t','r','c','s','f','none'].forEach(k => {
    const btn = document.getElementById('btn-'+k);
    if (btn) btn.className = 'type-btn' + (k===m ? ' sel-'+k : '');
  });
}
setMode('p');

function clickDay(date, current){
  let newType = (mode === 'none' || current === mode) ? null : mode;
  document.querySelectorAll('.cal-day').forEach(el => {
    if (el.title && el.title.startsWith(date)) {
      el.className = el.className.replace(/\s*d[ptrcfs]/g,'');
      if (newType) el.classList.add('d'+newType);
    }
  });
  saveDay(date, newType);
}

let activeNoteDate = null;
function openNote(date){
  activeNoteDate = date;
  document.getElementById('noteDate').textContent = date;
  document.getElementById('noteContent').value = notes[date] || '';
  document.getElementById('noteModal').classList.add('open');
  document.getElementById('noteContent').focus();
}
function closeNote(){ document.getElementById('noteModal').classList.remove('open'); }
function submitNote(){
  const content = document.getElementById('noteContent').value;
  notes[activeNoteDate] = content;
  saveNote(activeNoteDate, content);
  document.querySelectorAll('.cal-day').forEach(el => {
    if (el.title && el.title.startsWith(activeNoteDate))
      el.classList.toggle('has-note', !!content.trim());
  });
  closeNote();
}

document.addEventListener('keydown', e => {
  if (['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
  const map = {p:'p',t:'t',r:'r',c:'c',s:'s',f:'f','0':'none'};
  if (map[e.key.toLowerCase()]) setMode(map[e.key.toLowerCase()]);
  if (e.key === 'Escape') closeNote();
});
document.getElementById('noteModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeNote();
});
</script>
