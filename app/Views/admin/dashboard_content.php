<?php /* Contenu injecté dans layout.php via view='admin_dashboard' */ ?>
<div class="topbar">
  <div class="topbar-title">📊 Tableau de bord — <?=$year?></div>
  <div class="topbar-actions">
    <a class="btn btn-sm" href="<?=BASE_URL?>admin/users">👤 Gérer les utilisateurs</a>
    <a class="btn btn-sm btn-primary" href="<?=BASE_URL?>teams">👥 Gérer les équipes</a>
  </div>
</div>
<div class="content">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px">
  <?php foreach ($users as $u):
    $s = $stats[$u['id']] ?? ['p'=>0,'t'=>0,'r'=>0,'c'=>0,'s'=>0,'f'=>0];
    $w = $s['p']+$s['t'];
  ?>
  <div class="card" style="cursor:pointer" onclick="location.href='<?=BASE_URL?>view/<?=$u['id']?>/year/<?=$year?>'">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <div>
        <div style="font-weight:700;font-size:14px"><?=htmlspecialchars($u['name'])?></div>
        <div style="font-size:11px;color:var(--mu);font-family:'DM Mono',monospace;margin-top:2px">@<?=htmlspecialchars($u['username'])?></div>
      </div>
      <span class="badge-<?=$u['role']?>"><?=$u['role']?></span>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <span class="badge bp"><?=$s['p']?>j prés.</span>
      <span class="badge bt"><?=$s['t']?>j TT</span>
      <span class="badge br"><?=$s['r']?>j RTT</span>
      <span class="badge bc"><?=$s['c']?>j CP</span>
    </div>
    <div style="margin-top:10px;font-size:12px;color:var(--mu)">Total travaillé : <strong style="color:var(--ac)"><?=$w?> j</strong></div>
    <?php if (!$u['active']): ?><div style="font-size:11px;color:var(--c);margin-top:6px">⚠ Compte désactivé</div><?php endif; ?>
  </div>
  <?php endforeach; ?>
  </div>
</div>
