<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRA <?= $year ?? date('Y') ?> — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ── THEME DARK (défaut) ─────────────────────────── */
:root{
  --bg:#0f0f10;--bg2:#18181b;--bg3:#232328;--bd:#2e2e35;
  --tx:#e8e8ed;--mu:#7f7f8f;
  --ac:#6c63ff;
  --p:#3b82f6;--pb:#1e3a5f;
  --t:#22c55e;--tb:#14412a;
  --r:#f59e0b;--rb:#422d0a;
  --c:#ec4899;--cb:#4a1030;
  --f:#8b8b8b;--fb:#2a2a2a;
  --s:#a855f7;--sb:#3b1f6e;
  --rad:8px;
  color-scheme: dark;
}
/* ── THEME LIGHT ──────────────────────────────────── */
body.light{
  --bg:#f4f5f7;--bg2:#ffffff;--bg3:#eaecef;--bd:#d0d5dd;
  --tx:#1a1a2e;--mu:#6b7280;
  --ac:#5b50e8;
  --p:#2563eb;--pb:#dbeafe;
  --t:#16a34a;--tb:#dcfce7;
  --r:#d97706;--rb:#fef3c7;
  --c:#db2777;--cb:#fce7f3;
  --f:#6b7280;--fb:#f3f4f6;
  --s:#9333ea;--sb:#f3e8ff;
  color-scheme: light;
}
body.light .btn:hover{background:var(--bg3);border-color:var(--bd)}
body.light .cal-day.we{color:#c0c4cc;background:#f9fafb}
body.light .sb-link:hover{background:var(--bg3)}
body.light .month-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}
body.light .topbar{box-shadow:0 1px 3px rgba(0,0,0,.06)}
body.light .sidebar{box-shadow:1px 0 4px rgba(0,0,0,.06)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;font-size:14px}
a{color:inherit;text-decoration:none}

/* SIDEBAR */
.sidebar{width:220px;min-height:100vh;background:var(--bg2);border-right:1px solid var(--bd);position:fixed;top:0;left:0;display:flex;flex-direction:column;z-index:100;transform:none;transition:transform .25s cubic-bezier(.4,0,.2,1)}
.sb-logo{padding:20px 18px 16px;border-bottom:1px solid var(--bd)}
.sb-logo h1{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;letter-spacing:-.5px}
.sb-logo span{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.06em}
.sb-user{padding:14px 18px;border-bottom:1px solid var(--bd);display:flex;flex-direction:column;gap:2px}
.sb-user .name{font-weight:600;font-size:13px}
.sb-user .role{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.04em}
.sb-user .role.admin{color:var(--r)}
.sb-nav{flex:1;padding:10px 0;overflow-y:auto}
.sb-section{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.08em;padding:12px 18px 4px}
.sb-link{display:flex;align-items:center;gap:8px;padding:8px 18px;font-size:13px;color:var(--tx);transition:all .12s;border-left:2px solid transparent}
.sb-link:hover{background:var(--bg3);color:var(--tx)}
.sb-link.active{background:rgba(108,99,255,.12);border-left-color:var(--ac);color:var(--ac);font-weight:600}
.sb-link .ico{font-size:15px;width:18px;text-align:center;flex-shrink:0}
.sb-footer{padding:14px 18px;border-top:1px solid var(--bd)}
.sb-footer a{font-size:12px;color:var(--mu);display:flex;align-items:center;gap:6px}
.sb-footer a:hover{color:var(--tx)}

/* MAIN */
.main{margin-left:220px;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:var(--bg2);border-bottom:1px solid var(--bd);padding:12px 28px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;position:sticky;top:0;z-index:50}
.topbar-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.topbar-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.content{padding:24px 28px;flex:1}

/* DELEGATION BANNER */
.delegate-banner{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:var(--rad);padding:10px 16px;margin-bottom:16px;font-size:12px;color:var(--r);display:flex;align-items:center;gap:8px}

/* BUTTONS */
.btn{background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:var(--rad);padding:7px 14px;cursor:pointer;font-size:12px;font-family:'Inter',sans-serif;font-weight:500;transition:all .12s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.btn:hover{border-color:#3e3e4a;background:var(--bg)}
.btn-primary{background:var(--ac);border-color:var(--ac);color:#fff}
.btn-primary:hover{background:#7c75ff;border-color:#7c75ff}
.btn-danger{border-color:rgba(236,72,153,.4);color:var(--c)}
.btn-danger:hover{background:var(--cb);border-color:var(--c)}
.btn-sm{padding:4px 10px;font-size:11px}
.btn-year{font-weight:700;font-size:13px;font-family:'DM Mono',monospace}

/* FLASH */
.flash{padding:10px 16px;border-radius:var(--rad);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.flash.success{background:var(--tb);color:var(--t);border:1px solid rgba(34,197,94,.3)}
.flash.error{background:var(--cb);color:var(--c);border:1px solid rgba(236,72,153,.3)}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:20px}
.stat-card{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:14px}
.stat-lbl{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.06em;margin-bottom:6px}
.stat-val{font-size:26px;font-weight:700;line-height:1;font-family:'DM Mono',monospace}
.stat-sub{font-size:10px;color:var(--mu);margin-top:3px}
.cp .stat-val{color:var(--p)} .ct .stat-val{color:var(--t)} .cr .stat-val{color:var(--r)}
.cc .stat-val{color:var(--c)} .cf .stat-val{color:var(--f)} .cs .stat-val{color:var(--s)} .cw .stat-val{color:var(--ac)}
.ckm .stat-val{color:#a78bfa;font-size:20px}

/* YEAR GRID */
.year-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.month-card{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:14px;transition:all .12s;cursor:pointer}
.month-card:hover{border-color:var(--ac);transform:translateY(-1px)}
.month-name{font-weight:700;font-size:13px;margin-bottom:10px}
.month-bar{display:flex;height:4px;border-radius:2px;overflow:hidden;gap:1px;margin-bottom:10px}
.month-bar-seg{height:100%;border-radius:1px}
.month-rows{display:flex;flex-direction:column;gap:4px}
.month-row{display:flex;justify-content:space-between;font-size:11px}
.month-row .lbl{color:var(--mu);display:flex;align-items:center;gap:4px}
.month-row .val{font-family:'DM Mono',monospace;font-weight:600}
.dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}

/* CALENDAR */
.cal-wrap{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);overflow:hidden}
.cal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid var(--bd)}
.cal-head h2{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.cal-dow{display:grid;grid-template-columns:repeat(7,1fr);padding:8px 12px 4px}
.cal-dow div{text-align:center;font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.04em}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;padding:4px 12px 12px}
.cal-day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:12px;font-family:'DM Mono',monospace;font-weight:500;border-radius:6px;cursor:pointer;border:1.5px solid transparent;transition:all .1s;user-select:none;position:relative;flex-direction:column;gap:1px}
.cal-day:hover:not(.we):not(.empty){opacity:.8}
.cal-day.empty,.cal-day.we{cursor:default}
.cal-day.we{color:#3a3a4a}
.cal-day.today{box-shadow:0 0 0 1.5px var(--mu)}
.cal-day.dp{background:var(--pb);color:var(--p);border-color:rgba(59,130,246,.3)}
.cal-day.dt{background:var(--tb);color:var(--t);border-color:rgba(34,197,94,.3)}
.cal-day.dr{background:var(--rb);color:var(--r);border-color:rgba(245,158,11,.3)}
.cal-day.dc{background:var(--cb);color:var(--c);border-color:rgba(236,72,153,.3)}
.cal-day.df{background:var(--fb);color:var(--f);border-color:rgba(139,139,139,.3)}
.cal-day.ds{background:var(--sb);color:var(--s);border-color:rgba(168,85,247,.3)}
.cal-day.has-note::after{content:'';position:absolute;bottom:2px;right:2px;width:4px;height:4px;border-radius:50%;background:var(--ac)}
.note-indicator{width:4px;height:4px;border-radius:50%;background:var(--ac)}

/* MONTH LAYOUT */
.month-layout{display:grid;grid-template-columns:1fr 260px;gap:16px}

/* TYPE PANEL */
.type-panel{display:flex;flex-direction:column;gap:10px}
.type-panel-lbl{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.06em}
.type-btns{display:flex;flex-direction:column;gap:5px}
.type-btn{display:flex;align-items:center;gap:10px;background:var(--bg2);border:1.5px solid var(--bd);border-radius:var(--rad);padding:9px 12px;cursor:pointer;font-size:12px;font-weight:600;transition:all .12s}
.type-btn:hover{opacity:.85}
.type-btn.sel-p{background:var(--pb);border-color:var(--p);color:var(--p)}
.type-btn.sel-t{background:var(--tb);border-color:var(--t);color:var(--t)}
.type-btn.sel-r{background:var(--rb);border-color:var(--r);color:var(--r)}
.type-btn.sel-c{background:var(--cb);border-color:var(--c);color:var(--c)}
.type-btn.sel-f{background:var(--fb);border-color:var(--f);color:var(--f)}
.type-btn.sel-s{background:var(--sb);border-color:var(--s);color:var(--s)}
.type-btn.sel-none{background:var(--bg3);border-color:var(--mu);color:var(--mu)}
.type-key{font-family:'DM Mono',monospace;font-size:10px;background:rgba(255,255,255,.06);border-radius:4px;padding:2px 5px;margin-left:auto}

/* TRAJET BOX */
.trajet-box{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:14px}
.trajet-title{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.06em;margin-bottom:10px}
.trajet-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--bd)}
.trajet-row:last-child{border-bottom:none}
.trajet-lbl{font-size:12px;color:var(--mu)}
.trajet-val{font-family:'DM Mono',monospace;font-size:13px;font-weight:600;color:#a78bfa}

/* NOTE MODAL */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:200;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .15s}
.modal-backdrop.open{opacity:1;pointer-events:all}
.modal{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:20px;width:360px;max-width:95vw;transform:scale(.96);transition:transform .15s}
.modal-backdrop.open .modal{transform:scale(1)}
.modal h3{font-size:14px;font-weight:700;margin-bottom:12px}
.modal textarea{width:100%;background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:var(--rad);padding:10px;font-family:'Inter',sans-serif;font-size:13px;resize:vertical;min-height:80px}
.modal textarea:focus{outline:none;border-color:var(--ac)}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}

/* TABLES */
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.04em;padding:8px 12px;border-bottom:1px solid var(--bd);font-weight:500}
td{padding:9px 12px;font-size:13px;border-bottom:1px solid #1e1e24}
tr:hover td{background:#1e1e24}
tr.total td{border-top:1px solid var(--bd);font-weight:700;color:var(--ac);background:rgba(108,99,255,.06)}
.badge{display:inline-block;border-radius:4px;padding:2px 8px;font-size:11px;font-family:'DM Mono',monospace;font-weight:600}
.bp{background:var(--pb);color:var(--p)} .bt{background:var(--tb);color:var(--t)}
.br{background:var(--rb);color:var(--r)} .bc{background:var(--cb);color:var(--c)}
.bf{background:var(--fb);color:var(--f)} .bs{background:var(--sb);color:var(--s)}
.badge-admin{background:rgba(245,158,11,.15);color:var(--r);border-radius:4px;padding:2px 7px;font-size:11px;font-family:'DM Mono',monospace}
.badge-user{background:rgba(108,99,255,.12);color:var(--ac);border-radius:4px;padding:2px 7px;font-size:11px;font-family:'DM Mono',monospace}
.badge-off{background:rgba(139,139,139,.12);color:var(--mu);border-radius:4px;padding:2px 7px;font-size:11px;font-family:'DM Mono',monospace}

/* FORMS */
.form-group{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
.form-group label{font-size:11px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.04em}
.form-group input,.form-group select,.form-group textarea{background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:var(--rad);padding:8px 12px;font-family:'Inter',sans-serif;font-size:13px;transition:border-color .12s;width:100%}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--ac)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.card{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);padding:20px;margin-bottom:16px}
.card-title{font-size:13px;font-weight:600;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--bd)}

/* CONFIG ROW */
.cfg-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.cfg-field{display:flex;flex-direction:column;gap:3px}
.cfg-field label{font-size:10px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.05em}
.cfg-field input{width:90px;background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:var(--rad);padding:5px 9px;font-family:'DM Mono',monospace;font-size:13px}
.cfg-field input:focus{outline:none;border-color:var(--ac)}

/* TOAST */
#toast{position:fixed;bottom:20px;right:20px;z-index:999;background:var(--bg3);border:1px solid var(--bd);border-radius:var(--rad);padding:10px 16px;font-size:12px;font-family:'DM Mono',monospace;opacity:0;transition:opacity .2s;pointer-events:none}
#toast.show{opacity:1}
#toast.ok{border-color:rgba(34,197,94,.5);color:var(--t)}
#toast.err{border-color:rgba(236,72,153,.5);color:var(--c)}

/* SCROLL */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:var(--bd);border-radius:3px}

/* HINT */
.hint{font-size:11px;color:var(--mu);text-align:center;padding:6px 0;font-family:'DM Mono',monospace;opacity:.6}

@media(max-width:900px){
  .sidebar{width:200px}
  .main{margin-left:200px}
  .year-grid{grid-template-columns:repeat(3,1fr)}
  .month-layout{grid-template-columns:1fr}
}
@media(max-width:700px){
  /* Barre de navigation mobile */
  .mobile-topbar{
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;background:var(--bg2);border-bottom:1px solid var(--bd);
    position:sticky;top:0;z-index:150;
  }
  .mobile-topbar-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
  /* Sidebar masquée par défaut sur mobile — s'ouvre via hamburger */
  .sidebar{
    transform:translateX(-100%);
    transition:transform .25s cubic-bezier(.4,0,.2,1);
    width:260px;
    z-index:200;
    box-shadow:4px 0 20px rgba(0,0,0,.4);
  }
  .sidebar.open{transform:translateX(0)}
  /* Overlay sombre derrière la sidebar */
  .sb-overlay{
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.5);z-index:199;
    backdrop-filter:blur(2px);
  }
  .sb-overlay.open{display:block}
  /* Bouton hamburger dans la topbar */
  .hamburger{
    display:flex;flex-direction:column;justify-content:center;gap:5px;
    width:36px;height:36px;cursor:pointer;
    background:var(--bg3);border:1px solid var(--bd);
    border-radius:var(--rad);padding:8px;flex-shrink:0;
  }
  .hamburger span{
    display:block;height:2px;background:var(--tx);
    border-radius:1px;transition:all .2s;
  }
  .hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
  .hamburger.open span:nth-child(2){opacity:0;transform:scaleX(0)}
  .hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
  .main{margin-left:0}
  .year-grid{grid-template-columns:repeat(2,1fr)}
  .stats-grid{grid-template-columns:repeat(3,1fr)}
  .content{padding:14px}
  .topbar{padding:10px 14px}
}
@media(min-width:701px){
  .hamburger{display:none}
  .sb-overlay{display:none!important}
  .mobile-topbar{display:none}
}

/* ── THEME TOGGLE SLIDER ──────────────────────────── */
.theme-toggle{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;margin-bottom:2px}
.theme-toggle-label{font-size:11px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.04em;display:flex;align-items:center;gap:6px}
.theme-icon{font-size:13px;line-height:1;transition:opacity .2s}
.toggle-track{width:40px;height:22px;background:var(--bg3);border:1.5px solid var(--bd);border-radius:11px;position:relative;cursor:pointer;transition:background .2s,border-color .2s;flex-shrink:0}
.toggle-track:hover{border-color:var(--ac)}
.toggle-thumb{position:absolute;top:2px;left:2px;width:15px;height:15px;border-radius:50%;background:var(--mu);transition:transform .2s,background .2s}
body.light .toggle-track{background:#e0e7ff;border-color:#a5b4fc}
body.light .toggle-thumb{background:var(--ac);transform:translateX(18px)}
</style>
</head>
<body>
<script>
// Anti-FOUC : appliquer le thème avant le rendu
(function(){ if(localStorage.getItem('cra_theme')==='light') document.body.classList.add('light'); })();
</script>

<?php
$uid = ($target ?? null) ? $target['id'] : ($me['id'] ?? 0);
$base = $readonly && ($target ?? null) ? "view/{$target['id']}/" : '';
$curYear = $year ?? (int)date('Y');
$isAdmin = ($me['role'] ?? '') === 'admin';
?>

<!-- SIDEBAR -->
<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
  <div class="sb-logo">
    <h1>CRA <?= $curYear ?></h1>
    <span>SUIVI D'ACTIVITÉ</span>
  </div>
  <div class="sb-user">
    <span class="name"><?= htmlspecialchars($me['name']) ?></span>
    <span class="role <?= $me['role'] ?>"><?= strtoupper($me['role']) ?></span>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">MON CRA</div>
    <a class="sb-link <?= (!$readonly && ($view??'')==='year') ? 'active':'' ?>"
       href="<?= BASE_URL ?>cra/year/<?= $curYear ?>">
      <span class="ico">📅</span> Vue annuelle
    </a>
    <?php $months=['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
    for ($m=1;$m<=12;$m++): ?>
    <a class="sb-link <?= (!$readonly && ($view??'')==='month' && ($month??0)===$m) ? 'active':'' ?>"
       href="<?= BASE_URL ?>cra/month/<?= $curYear ?>/<?= $m ?>">
      <span class="ico">·</span> <?= $months[$m-1] ?>
    </a>
    <?php endfor; ?>

    <?php if (!empty($accessible)): ?>
    <div class="sb-section">DÉLÉGATIONS</div>
    <?php foreach ($accessible as $u):
      if ($u['id'] === $me['id']) continue; ?>
    <a class="sb-link <?= ($readonly && ($target['id']??0)===$u['id']) ? 'active':'' ?>"
       href="<?= BASE_URL ?>view/<?= $u['id'] ?>/year/<?= $curYear ?>">
      <span class="ico">👤</span> <?= htmlspecialchars($u['name']) ?>
    </a>
    <?php endforeach; endif; ?>

    <?php
    // Section ÉQUIPES — visible par tous les utilisateurs actifs
    $myTeams = \Models\Team::ownedBy($me['id']);
    $route = $_GET['route'] ?? '';
    ?>
    <div class="sb-section">ÉQUIPES</div>
    <a class="sb-link <?= ($route === 'teams') ? 'active':'' ?>"
       href="<?= BASE_URL ?>teams"><span class="ico">👥</span> Mes équipes</a>
    <?php foreach ($myTeams as $team): ?>
    <a class="sb-link <?= str_contains($route,'teams/'.$team['id']) ? 'active':'' ?>"
       href="<?= BASE_URL ?>teams/<?= $team['id'] ?>">
      <span class="ico">·</span> <?= htmlspecialchars($team['name']) ?>
    </a>
    <?php endforeach; ?>

    <?php if ($isAdmin): ?>
    <div class="sb-section">ADMINISTRATION</div>
    <a class="sb-link <?= ($route==='' || $route==='admin') ? 'active':'' ?>"
       href="<?= BASE_URL ?>admin"><span class="ico">📊</span> Tableau de bord</a>
    <a class="sb-link <?= str_contains($route,'admin/users') ? 'active':'' ?>"
       href="<?= BASE_URL ?>admin/users"><span class="ico">👤</span> Utilisateurs</a>
    <a class="sb-link <?= str_contains($route,'admin/delegations') ? 'active':'' ?>"
       href="<?= BASE_URL ?>admin/delegations"><span class="ico">🔗</span> Délégations</a>
    <a class="sb-link <?= str_contains($route,'admin/archives') ? 'active':'' ?>"
       href="<?= BASE_URL ?>admin/archives"><span class="ico">🗄️</span> Archives</a>
    <a class="sb-link <?= str_contains($route,'admin/reset') ? 'active':'' ?>"
       href="<?= BASE_URL ?>admin/reset" style="color:var(--c)"><span class="ico">⚠</span> Remise à zéro</a>
    <?php endif; ?>
  </nav>
  <div class="theme-toggle">
    <span class="theme-toggle-label">
      <span class="theme-icon" id="themeIconMoon">🌙</span>
      <span class="theme-icon" id="themeIconSun" style="display:none">☀️</span>
      <span id="themeLabel">Thème sombre</span>
    </span>
    <div class="toggle-track" id="themeTrack" onclick="toggleTheme()" role="switch" aria-label="Basculer le thème" tabindex="0">
      <div class="toggle-thumb" id="themeThumb"></div>
    </div>
  </div>
  <div class="sb-footer">
    <a href="<?= BASE_URL ?>logout">⎋ Déconnexion</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <!-- Barre mobile avec bouton hamburger (visible uniquement < 700px) -->
  <div class="mobile-topbar">
    <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <span class="mobile-topbar-title">CRA <?= $curYear ?></span>
    <div style="width:36px"></div><!-- spacer pour centrer le titre -->
  </div>
  <div id="toast"></div>

  <?php if (!empty($flash)): ?>
  <script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($flash['msg']) ?>,<?= $flash['type']==='error'?'false':'true' ?>));</script>
  <?php endif; ?>

  <?php
  $viewMap = [
    'year'            => '/Views/cra/year.php',
    'month'           => '/Views/cra/month.php',
    'admin_dashboard' => '/Views/admin/dashboard_content.php',
    'admin_users'     => '/Views/admin/users_content.php',
    'admin_deleg'     => '/Views/admin/deleg_content.php',
    'admin_archives'  => '/Views/admin/archives_content.php',
    'admin_reset'     => '/Views/admin/reset_content.php',
    'teams_index'     => '/Views/teams/index.php',
    'teams_show'      => '/Views/teams/show.php',
  ];
  $viewFile = $viewMap[$view ?? ''] ?? null;
  if ($viewFile && file_exists(APP.$viewFile)) require APP.$viewFile;
  ?>
</div>

<script>
// Toast
function showToast(msg,ok=true){const t=document.getElementById('toast');t.textContent=msg;t.className='show '+(ok?'ok':'err');setTimeout(()=>t.className='',3000)}

// CSRF token pour les appels AJAX
const CSRF_TOKEN = <?= json_encode($_csrf ?? '') ?>;
const TARGET_ID  = <?= json_encode($target['id'] ?? null) ?>;

function ajaxPost(url, params) {
  params._csrf = CSRF_TOKEN;
  if (TARGET_ID) params.target_id = TARGET_ID;
  return fetch(url, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams(params)
  }).then(r => r.json());
}

function saveDay(date, type){
  ajaxPost('<?=BASE_URL?>cra/day', {date, type: type||''})
    .then(d => d.ok ? showToast('Sauvegardé ✓') : showToast('Erreur',false))
    .catch(() => showToast('Erreur réseau',false));
}

function saveNote(date, content){
  ajaxPost('<?=BASE_URL?>cra/note', {date, content})
    .then(d => d.ok ? showToast('Note sauvegardée ✓') : showToast('Erreur',false))
    .catch(() => showToast('Erreur réseau',false));
}
// AJAX save config
function saveConfig(km,duree,indem){
  fetch('<?=BASE_URL?>cra/config',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({km,duree,indem})})
  .then(r=>r.json()).then(d=>d.ok?showToast('Config sauvegardée ✓'):showToast('Erreur',false));
}

// ── MENU MOBILE ──────────────────────────────────────────────────────────────
function toggleSidebar(){
  const sb  = document.getElementById('sidebar');
  const ov  = document.getElementById('sbOverlay');
  const hb  = document.getElementById('hamburger');
  const open = sb.classList.toggle('open');
  ov.classList.toggle('open', open);
  hb.classList.toggle('open', open);
  document.body.style.overflow = open ? 'hidden' : '';
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sbOverlay').classList.remove('open');
  document.getElementById('hamburger').classList.remove('open');
  document.body.style.overflow = '';
}
// Fermer la sidebar sur clic d'un lien (navigation mobile)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sb-link, .sb-footer a').forEach(el => {
    el.addEventListener('click', () => {
      if (window.innerWidth <= 700) closeSidebar();
    });
  });
});

// ── THEME TOGGLE ──────────────────────────────────────────────────────────────
(function(){
  const saved = localStorage.getItem('cra_theme') || 'dark';
  applyTheme(saved, false);
})();

function applyTheme(theme, animate) {
  const body   = document.body;
  const moon   = document.getElementById('themeIconMoon');
  const sun    = document.getElementById('themeIconSun');
  const label  = document.getElementById('themeLabel');

  if (animate) body.style.transition = 'background .25s, color .25s';

  if (theme === 'light') {
    body.classList.add('light');
    if (moon)  moon.style.display  = 'none';
    if (sun)   sun.style.display   = '';
    if (label) label.textContent   = 'Thème clair';
  } else {
    body.classList.remove('light');
    if (moon)  moon.style.display  = '';
    if (sun)   sun.style.display   = 'none';
    if (label) label.textContent   = 'Thème sombre';
  }

  if (animate) setTimeout(() => body.style.transition = '', 300);
  localStorage.setItem('cra_theme', theme);
}

function toggleTheme() {
  const current = document.body.classList.contains('light') ? 'light' : 'dark';
  applyTheme(current === 'light' ? 'dark' : 'light', true);
}

// Accessibilité clavier sur le toggle
document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('themeTrack');
  if (track) {
    track.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleTheme(); }
    });
  }
});
</script>
</body></html>
