<?php
$km    = (float)($config['km']    ?? 0);
$dur   = (float)($config['duree'] ?? 0);
$indem = (float)($config['indem'] ?? 0);
$mnames = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$total  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDow = ((int)date('w', strtotime("$year-$month-01")) + 6) % 7;

function fmtTime($mins) {
    $h = floor($mins/60); $m = round($mins%60);
    return $h>0 ? "{$h}h".($m>0?str_pad($m,2,'0',STR_PAD_LEFT)."min":'') : "{$m}min";
}
function fmtStat($v) { return $v == floor($v) ? (int)$v : number_format($v,1,'.',''); }

$prevM = $month==1?12:$month-1; $prevY = $month==1?$year-1:$year;
$nextM = $month==12?1:$month+1; $nextY = $month==12?$year+1:$year;

$ouvrables = array_reduce(range(1,$total), function($c,$d) use($year,$month,$feries){
    $date = sprintf('%04d-%02d-%02d',$year,$month,$d);
    $dow  = (int)date('w',strtotime($date));
    return $c + (!in_array($dow,[0,6]) && !in_array($date,$feries) ? 1 : 0);
}, 0);

$ys = $stats;
$ys['s']  = $ys['s']  ?? 0;
$ys['nr'] = max(0, $ouvrables - $ys['p'] - $ys['t'] - $ys['r'] - $ys['c'] - $ys['s'] - $ys['f']);
$navPfx   = ($readonly && $target) ? "view/{$target['id']}/" : 'cra/';
?>
<div class="topbar">
  <div class="topbar-title">
    <?= $readonly && $target ? '👁 '.htmlspecialchars($target['name']).' — ' : '' ?>
    <?= $mnames[$month] ?> <?= $year ?>
  </div>
  <div class="topbar-actions">
    <?php $navPfx2 = $navPfx; ?>
    <a class="btn btn-sm" href="<?=BASE_URL?><?=$navPfx2?>month/<?=$prevY?>/<?=$prevM?>">← <?=$mnames[$prevM]?></a>
    <a class="btn btn-sm" href="<?=BASE_URL?><?=$navPfx2?>year/<?=$year?>">📅 <?=$year?></a>
    <a class="btn btn-sm" href="<?=BASE_URL?><?=$navPfx2?>month/<?=$nextY?>/<?=$nextM?>"><?=$mnames[$nextM]?> →</a>
    <?php if (!$readonly): ?>
    <a class="btn btn-sm" href="<?=BASE_URL?>cra/export/<?=$year?>">⬇ CSV</a>
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

  <div class="stats-grid">
    <div class="stat-card cp"><div class="stat-lbl">PRÉSENTIEL</div><div class="stat-val"><?=fmtStat($ys['p'])?></div><div class="stat-sub">jours</div></div>
    <div class="stat-card ct"><div class="stat-lbl">TÉLÉTRAVAIL</div><div class="stat-val"><?=fmtStat($ys['t'])?></div></div>
    <div class="stat-card cr"><div class="stat-lbl">RTT</div><div class="stat-val"><?=fmtStat($ys['r'])?></div></div>
    <div class="stat-card cc"><div class="stat-lbl">CONGÉS</div><div class="stat-val"><?=fmtStat($ys['c'])?></div></div>
    <div class="stat-card cs"><div class="stat-lbl">SANS SOLDE</div><div class="stat-val"><?=fmtStat($ys['s'])?></div></div>
    <div class="stat-card cf"><div class="stat-lbl">FÉRIÉS</div><div class="stat-val"><?=fmtStat($ys['f'])?></div></div>
    <div class="stat-card"><div class="stat-lbl">NON SAISI</div><div class="stat-val" style="color:var(--mu)"><?=fmtStat($ys['nr'])?></div></div>
  </div>

  <div class="month-layout">
    <!-- CALENDAR -->
    <div class="cal-wrap">
      <div class="cal-head">
        <h2><?=$mnames[$month]?> <?=$year?></h2>
        <?php if (!$readonly): ?>
        <div style="font-size:11px;color:var(--mu);font-family:'DM Mono',monospace">
          Clic = journée · Clic droit = demi-journée · Dbl-clic = note
        </div>
        <?php endif; ?>
      </div>
      <div class="cal-dow">
        <?php foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $d): ?>
        <div><?=$d?></div>
        <?php endforeach; ?>
      </div>
      <div class="cal-grid">
        <?php for ($i=0;$i<$firstDow;$i++): ?><div class="cal-day empty"></div><?php endfor; ?>
        <?php for ($d=1; $d<=$total; $d++):
          $date   = sprintf('%04d-%02d-%02d',$year,$month,$d);
          $dow    = (int)date('w',strtotime($date));
          $isWe   = in_array($dow,[0,6]);
          $isFer  = in_array($date,$feries) && !$isWe;
          $dayData= $days[$date] ?? null;
          $type   = $dayData ? $dayData['type'] : ($isFer ? 'f' : null);
          $typeAm = $dayData ? $dayData['am'] : null;
          $typePm = $dayData ? $dayData['pm'] : null;
          $isHalf = $typeAm || $typePm;

          $cls = 'cal-day';
          if ($isWe) $cls .= ' we';
          elseif ($isHalf) $cls .= ' half';
          elseif ($type) $cls .= ' d'.$type;
          if ($date === date('Y-m-d')) $cls .= ' today';
          if (!empty($notes[$date])) $cls .= ' has-note';
        ?>
        <div class="<?=$cls?>"
          data-date="<?=$date?>"
          data-type="<?= $type ?? '' ?>"
          data-am="<?= $typeAm ?? '' ?>"
          data-pm="<?= $typePm ?? '' ?>"
          <?php if (!$isWe && !$readonly): ?>
            onclick="clickDay(this)"
            ondblclick="openNote(this.dataset.date)"
            oncontextmenu="openHalfMenu(event,this)"
          <?php endif; ?>
          title="<?=$date?><?=!empty($notes[$date])?' — '.htmlspecialchars($notes[$date]):''?>"
        ><?php if ($isHalf): ?>
          <span class="half-am d<?= $typeAm ?? $type ?>"></span>
          <span class="half-pm d<?= $typePm ?? $type ?>"></span>
        <?php else: ?><?=$d?><?php endif; ?></div>
        <?php endfor; ?>
      </div>
      <p class="hint">Clic = journée complète · Clic droit = demi-journée · Double-clic = note</p>
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

<!-- MENU CONTEXTUEL DEMI-JOURNÉE -->
<div id="halfMenu" style="
  display:none;position:fixed;z-index:500;
  background:var(--bg2);border:1px solid var(--bd);
  border-radius:var(--rad);padding:6px;
  box-shadow:0 8px 24px rgba(0,0,0,.35);min-width:180px">
  <div style="font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;padding:4px 8px 6px;letter-spacing:.06em" id="halfMenuDate"></div>
  <div id="halfMenuAm" style="padding:2px 0">
    <div style="font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;padding:2px 8px">MATIN</div>
    <div id="halfMenuAmBtns"></div>
  </div>
  <div style="height:1px;background:var(--bd);margin:4px 0"></div>
  <div id="halfMenuPm" style="padding:2px 0">
    <div style="font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;padding:2px 8px">APRÈS-MIDI</div>
    <div id="halfMenuPmBtns"></div>
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

<style>
/* Cellule demi-journée */
.cal-day.half{
  padding:0;overflow:hidden;cursor:pointer;
  display:flex;flex-direction:column;position:relative;
  font-size:9px;font-family:'DM Mono',monospace;font-weight:600;
}
.cal-day.half .half-am,
.cal-day.half .half-pm{
  display:flex;align-items:center;justify-content:center;
  flex:1;width:100%;font-size:9px;
}
/* Couleurs des demi-journées */
.half-am.dp,.half-pm.dp{background:var(--pb);color:var(--p)}
.half-am.dt,.half-pm.dt{background:var(--tb);color:var(--t)}
.half-am.dr,.half-pm.dr{background:var(--rb);color:var(--r)}
.half-am.dc,.half-pm.dc{background:var(--cb);color:var(--c)}
.half-am.ds,.half-pm.ds{background:var(--sb);color:var(--s)}
.half-am.df,.half-pm.df{background:var(--fb);color:var(--f)}
.half-am.d,.half-pm.d{background:var(--bg3);color:var(--mu)}
/* Séparateur entre les deux moitiés */
.cal-day.half .half-am{border-bottom:1px solid rgba(255,255,255,.1)}

/* Menu contextuel items */
.half-menu-item{
  display:flex;align-items:center;gap:8px;
  padding:6px 10px;cursor:pointer;border-radius:5px;
  font-size:12px;font-weight:500;transition:background .1s;
}
.half-menu-item:hover{background:var(--bg3)}
.half-menu-item.active{font-weight:700}
.half-menu-item .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.half-menu-item .erase{font-size:13px}
</style>

<script>
let mode = 'p';
const notes = <?= json_encode($notes) ?>;
const TYPES = {
  p:{label:'Présentiel',color:'var(--p)'},
  t:{label:'Télétravail',color:'var(--t)'},
  r:{label:'RTT',color:'var(--r)'},
  c:{label:'Congé payé',color:'var(--c)'},
  s:{label:'Sans solde',color:'var(--s)'},
  f:{label:'Férié',color:'var(--f)'},
};

function setMode(m){
  mode = m;
  ['p','t','r','c','s','f','none'].forEach(k => {
    const btn = document.getElementById('btn-'+k);
    if (btn) btn.className = 'type-btn' + (k===m ? ' sel-'+k : '');
  });
}
setMode('p');

// ── JOURNÉE COMPLÈTE ────────────────────────────────────────────────────────
function clickDay(el){
  const date    = el.dataset.date;
  const current = el.dataset.type || null;
  const newType = (mode === 'none' || (current === mode && !el.dataset.am && !el.dataset.pm)) ? null : mode;

  // Reset demi-journées
  el.dataset.am = '';
  el.dataset.pm = '';
  el.dataset.type = newType || '';

  // Reconstruire le rendu
  renderDayEl(el, newType, null, null);
  saveDay(date, newType);
}

// ── MENU CLIC DROIT ─────────────────────────────────────────────────────────
let activeHalfEl = null;

function openHalfMenu(e, el){
  e.preventDefault();
  activeHalfEl = el;
  const date = el.dataset.date;
  const curAm = el.dataset.am || null;
  const curPm = el.dataset.pm || null;

  document.getElementById('halfMenuDate').textContent = date;
  buildHalfBtns('halfMenuAmBtns', 'am', curAm);
  buildHalfBtns('halfMenuPmBtns', 'pm', curPm);

  const menu = document.getElementById('halfMenu');
  menu.style.display = 'block';

  // Positionnement
  const vw = window.innerWidth, vh = window.innerHeight;
  let x = e.clientX, y = e.clientY;
  menu.style.left = '0px'; menu.style.top = '0px';
  const mw = menu.offsetWidth, mh = menu.offsetHeight;
  if (x + mw > vw - 8) x = vw - mw - 8;
  if (y + mh > vh - 8) y = vh - mh - 8;
  menu.style.left = x + 'px';
  menu.style.top  = y + 'px';
}

function buildHalfBtns(containerId, half, current){
  const div = document.getElementById(containerId);
  div.innerHTML = '';

  // Bouton effacer
  const erase = document.createElement('div');
  erase.className = 'half-menu-item' + (!current ? ' active' : '');
  erase.innerHTML = '<span class="erase">⌫</span> Effacer';
  erase.onclick = () => selectHalf(half, null);
  div.appendChild(erase);

  Object.entries(TYPES).forEach(([k,v]) => {
    const item = document.createElement('div');
    item.className = 'half-menu-item' + (current === k ? ' active' : '');
    item.innerHTML = `<span class="dot" style="background:${v.color}"></span>${v.label}`;
    item.onclick = () => selectHalf(half, k);
    div.appendChild(item);
  });
}

function selectHalf(half, type){
  closeHalfMenu();
  if (!activeHalfEl) return;

  const el   = activeHalfEl;
  const date = el.dataset.date;

  if (half === 'am') el.dataset.am = type || '';
  else               el.dataset.pm = type || '';

  const am = el.dataset.am || null;
  const pm = el.dataset.pm || null;

  // Type pleine journée = am en priorité, sinon pm, sinon type existant
  const fullType = am || pm || el.dataset.type || null;
  el.dataset.type = fullType || '';

  renderDayEl(el, fullType, am, pm);
  saveHalfDay(date, half, type);
}

function closeHalfMenu(){
  document.getElementById('halfMenu').style.display = 'none';
  activeHalfEl = null;
}

// Fermer le menu sur clic ailleurs
document.addEventListener('click', e => {
  if (!document.getElementById('halfMenu').contains(e.target)) closeHalfMenu();
});

// ── RENDU CELLULE ───────────────────────────────────────────────────────────
function renderDayEl(el, type, am, pm){
  const d = el.textContent.trim() || el.getAttribute('data-date').split('-')[2].replace(/^0/,'');

  // Supprimer toutes les classes de type
  el.className = el.className.split(' ')
    .filter(cls => !/^d[ptrcfs]$/.test(cls) && cls !== 'half')
    .join(' ');

  el.innerHTML = '';

  if (am || pm) {
    // Demi-journées
    el.classList.add('half');
    const spanAm = document.createElement('span');
    spanAm.className = 'half-am d' + (am || type || '');
    spanAm.textContent = am ? am.toUpperCase() : '·';
    const spanPm = document.createElement('span');
    spanPm.className = 'half-pm d' + (pm || type || '');
    spanPm.textContent = pm ? pm.toUpperCase() : '·';
    el.appendChild(spanAm);
    el.appendChild(spanPm);
  } else if (type) {
    el.classList.add('d' + type);
    el.textContent = d;
  } else {
    el.textContent = d;
  }
}

// ── AJAX ────────────────────────────────────────────────────────────────────
function saveHalfDay(date, half, type){
  const params = {date, half, type: type||''};
  if (TARGET_ID) params.target_id = TARGET_ID;
  ajaxPost('<?=BASE_URL?>cra/halfday', params)
    .then(d => d.ok ? showToast('Demi-journée sauvegardée ✓') : showToast('Erreur',false))
    .catch(() => showToast('Erreur réseau',false));
}

// ── NOTES ────────────────────────────────────────────────────────────────────
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
  const noteEl = document.querySelector(`.cal-day[data-date="${activeNoteDate}"]`);
  if (noteEl) noteEl.classList.toggle('has-note', !!content.trim());
  closeNote();
}

document.addEventListener('keydown', e => {
  if (['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
  if (e.key === 'Escape'){ closeNote(); closeHalfMenu(); return; }
  const map = {p:'p',t:'t',r:'r',c:'c',s:'s',f:'f','0':'none'};
  if (map[e.key.toLowerCase()]) setMode(map[e.key.toLowerCase()]);
});
document.getElementById('noteModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeNote();
});
</script>
