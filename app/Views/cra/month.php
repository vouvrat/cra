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
          data-day="<?=$d?>"
          data-type="<?= $type ?? '' ?>"
          data-am="<?= $typeAm ?? '' ?>"
          data-pm="<?= $typePm ?? '' ?>"
          <?php if (!$isWe && !$readonly): ?>
            onclick="clickDay(this, event)"
            ondblclick="dblClickDay(this)"
          <?php endif; ?>
          title="<?=$date?><?=!empty($notes[$date])?' — '.htmlspecialchars($notes[$date]):''?>"
        ><?php if ($isHalf):
          $BG = ['p'=>'#1e3a5f','t'=>'#14412a','r'=>'#422d0a','c'=>'#4a1030','s'=>'#3b1f6e','f'=>'#2a2a2a'];
          $FG = ['p'=>'#3b82f6','t'=>'#22c55e','r'=>'#f59e0b','c'=>'#ec4899','s'=>'#a855f7','f'=>'#8b8b8b'];
          $bgAm = $typeAm ? ($BG[$typeAm]??'#232328') : '#232328';
          $fgAm = $typeAm ? ($FG[$typeAm]??'#7f7f8f') : '#7f7f8f';
          $bgPm = $typePm ? ($BG[$typePm]??'#232328') : '#232328';
          $fgPm = $typePm ? ($FG[$typePm]??'#7f7f8f') : '#7f7f8f';
          $lblAm = $typeAm ? strtoupper($typeAm) : $d;
          $lblPm = $typePm ? strtoupper($typePm) : '';
        ?><svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 40 40" style="display:block;border-radius:inherit">
            <rect x="0" y="0"  width="40" height="20" fill="<?=$bgAm?>"/>
            <rect x="0" y="20" width="40" height="20" fill="<?=$bgPm?>"/>
            <line x1="0" y1="20" x2="40" y2="20" stroke="rgba(128,128,128,.3)" stroke-width="0.5"/>
            <text x="20" y="14" text-anchor="middle" dominant-baseline="middle" fill="<?=$fgAm?>" font-size="13" font-family="monospace" font-weight="bold"><?=$lblAm?></text>
            <text x="20" y="30" text-anchor="middle" dominant-baseline="middle" fill="<?=$fgPm?>" font-size="13" font-family="monospace" font-weight="bold"><?=$lblPm?></text>
          </svg><?php else: ?><?=$d?><?php endif; ?></div>
        <?php endfor; ?>
      </div>
      <p class="hint">Clic = journée complète · Double-clic = basculer en demi-journées · Sur demi : clic haut = matin, clic bas = après-midi</p>
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
/* ── CELLULE DEMI-JOURNÉE ─────────────────────────────────── */
/* Approche positionnement absolu — contourne aspect-ratio */
.cal-day.half{
  padding:0 !important;
  overflow:hidden;
  position:relative;
}
.cal-day.half .half-am,
.cal-day.half .half-pm{
  position:absolute;
  left:0;right:0;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:9px;
  font-family:'DM Mono',monospace;
  font-weight:700;
  line-height:1;
}
.cal-day.half .half-am{top:0;bottom:50%;border-bottom:1px solid rgba(128,128,128,.2)}
.cal-day.half .half-pm{top:50%;bottom:0}
/* Couleurs */
.half-am.dp,.half-pm.dp{background:var(--pb);color:var(--p)}
.half-am.dt,.half-pm.dt{background:var(--tb);color:var(--t)}
.half-am.dr,.half-pm.dr{background:var(--rb);color:var(--r)}
.half-am.dc,.half-pm.dc{background:var(--cb);color:var(--c)}
.half-am.ds,.half-pm.ds{background:var(--sb);color:var(--s)}
.half-am.df,.half-pm.df{background:var(--fb);color:var(--f)}
.half-am.dempty,.half-pm.dempty{background:transparent;color:var(--mu);font-size:8px}


</style>

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

// ── CLIC SUR UNE CELLULE ─────────────────────────────────────────────────────
// Comportement :
//  • Cellule vide ou type complet → applique le type en journée complète
//  • Clic sur moitié AM d'une cellule half → change AM
//  • Clic sur moitié PM d'une cellule half → change PM
//  • mode 'none' → efface (remet en vide)
function clickDay(el, event){
  const date    = el.dataset.date;
  const isHalf  = el.classList.contains('half');

  if (isHalf) {
    // Déterminer si le clic est sur la moitié haute ou basse
    const rect  = el.getBoundingClientRect();
    const relY  = event.clientY - rect.top;
    const half  = relY < rect.height / 2 ? 'am' : 'pm';

    const newType = mode === 'none' ? null : mode;
    if (half === 'am') el.dataset.am = newType || '';
    else               el.dataset.pm = newType || '';

    const am = el.dataset.am || null;
    const pm = el.dataset.pm || null;

    // Si tout est effacé → journée vide
    if (!am && !pm) {
      el.dataset.type = '';
      el.classList.remove('half');
      renderDayEl(el, null, null, null);
      saveDay(date, null);
      return;
    }

    const fullType = am || pm || el.dataset.type || null;
    el.dataset.type = fullType || '';
    renderDayEl(el, fullType, am, pm);
    saveHalfDay(date, half, newType);

  } else {
    // Journée complète
    const current = el.dataset.type || null;
    const newType = (mode === 'none' || current === mode) ? null : mode;
    el.dataset.am = '';
    el.dataset.pm = '';
    el.dataset.type = newType || '';
    renderDayEl(el, newType, null, null);
    saveDay(date, newType);
  }
}

// ── DOUBLE-CLIC : bascule journée complète ↔ demi-journées ─────────────────
// Sur journée complète → divise : AM = type actuel, PM = vide
// Sur demi-journée → ouvre la note
function dblClickDay(el){
  const isHalf = el.classList.contains('half');
  if (!isHalf && el.dataset.type) {
    // Bascule en mode demi-journée
    const t = el.dataset.type;
    el.dataset.am = t;
    el.dataset.pm = '';
    renderDayEl(el, t, t, null);
    saveHalfDay(el.dataset.date, 'am', t);
    // Supprimer la journée complète pour ne garder que am
    saveDay(el.dataset.date, null);
  } else {
    openNote(el.dataset.date);
  }
}

// ── COULEURS ────────────────────────────────────────────────────────────────
const TYPE_BG = {p:'#1e3a5f',t:'#14412a',r:'#422d0a',c:'#4a1030',s:'#3b1f6e',f:'#2a2a2a'};
const TYPE_FG = {p:'#3b82f6',t:'#22c55e',r:'#f59e0b',c:'#ec4899',s:'#a855f7',f:'#8b8b8b'};

// ── RENDU CELLULE (SVG inline — zéro dépendance CSS) ────────────────────────
function renderDayEl(el, type, am, pm){
  const d = el.dataset.day || el.getAttribute('data-date').split('-')[2].replace(/^0/,'');

  el.className = el.className.split(' ')
    .filter(cls => !/^d[ptrcfs]$/.test(cls) && cls !== 'half')
    .join(' ');
  el.innerHTML = '';

  if (am || pm) {
    el.classList.add('half');
    const bgAm  = am ? TYPE_BG[am] : '#232328';
    const fgAm  = am ? TYPE_FG[am] : '#7f7f8f';
    const bgPm  = pm ? TYPE_BG[pm] : '#232328';
    const fgPm  = pm ? TYPE_FG[pm] : '#7f7f8f';
    const lblAm = am ? am.toUpperCase() : d;
    const lblPm = pm ? pm.toUpperCase() : '';

    const ns  = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('width','100%');
    svg.setAttribute('height','100%');
    svg.setAttribute('viewBox','0 0 40 40');
    svg.style.cssText = 'display:block;border-radius:inherit;';

    const mkRect = (y,h,fill) => {
      const r = document.createElementNS(ns,'rect');
      r.setAttribute('x','0'); r.setAttribute('y',y);
      r.setAttribute('width','40'); r.setAttribute('height',h);
      r.setAttribute('fill',fill);
      return r;
    };
    const mkLine = () => {
      const l = document.createElementNS(ns,'line');
      l.setAttribute('x1','0'); l.setAttribute('y1','20');
      l.setAttribute('x2','40'); l.setAttribute('y2','20');
      l.setAttribute('stroke','rgba(128,128,128,.3)');
      l.setAttribute('stroke-width','0.5');
      return l;
    };
    const mkText = (label, y, fill) => {
      const t = document.createElementNS(ns,'text');
      t.setAttribute('x','20'); t.setAttribute('y', y);
      t.setAttribute('text-anchor','middle');
      t.setAttribute('dominant-baseline','middle');
      t.setAttribute('fill', fill);
      t.setAttribute('font-size','13');
      t.setAttribute('font-family','monospace');
      t.setAttribute('font-weight','bold');
      t.textContent = label;
      return t;
    };

    svg.appendChild(mkRect(0,  20, bgAm));
    svg.appendChild(mkRect(20, 20, bgPm));
    svg.appendChild(mkLine());
    svg.appendChild(mkText(lblAm, 14, fgAm));
    if (lblPm) svg.appendChild(mkText(lblPm, 30, fgPm));
    el.appendChild(svg);

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
  if (e.key === 'Escape'){ closeNote(); return; }
  const map = {p:'p',t:'t',r:'r',c:'c',s:'s',f:'f','0':'none'};
  if (map[e.key.toLowerCase()]) setMode(map[e.key.toLowerCase()]);
});
document.getElementById('noteModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeNote();
});
</script>
