<?php
$base    = BASE_URL;
$curYear = $year;
$allReal = \Models\User::all(false);
?>
<div class="topbar">
  <div class="topbar-title">👥 <?= htmlspecialchars($team['name']) ?></div>
  <div class="topbar-actions">
    <select onchange="location.href='<?= $base ?>teams/<?= $team['id'] ?>?year='+this.value"
      style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:var(--rad);padding:5px 10px;font-family:'DM Mono',monospace;font-size:12px">
      <?php for ($y = date('Y')+1; $y >= 2023; $y--): ?>
      <option value="<?= $y ?>" <?= $y === $curYear ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <a class="btn btn-sm" href="<?= $base ?>teams/<?= $team['id'] ?>/export/<?= $curYear ?>">⬇ CSV équipe <?= $curYear ?></a>
    <button class="btn btn-sm" onclick="document.getElementById('archiveModal').classList.add('open')">🗄 Archiver</button>
    <button class="btn btn-sm" onclick="document.getElementById('editTeamModal').classList.add('open')">✏ Équipe</button>
  </div>
</div>
<div class="content">

  <!-- MEMBRES -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;margin-bottom:20px">
  <?php foreach ($members as $m):
    $s = $stats[$m['id']] ?? ['p'=>0,'t'=>0,'r'=>0,'c'=>0,'s'=>0,'f'=>0];
    $w = $s['p'] + $s['t'];
    $cm = (int)date('m'); $cy = (int)date('Y');
  ?>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
      <div>
        <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($m['name']) ?></div>
        <?php if ($m['virtual']): ?>
        <span style="font-size:10px;background:rgba(245,158,11,.12);color:var(--r);border-radius:3px;padding:1px 6px;font-family:'DM Mono',monospace">VIRTUEL</span>
        <?php else: ?>
        <span style="font-size:10px;color:var(--mu);font-family:'DM Mono',monospace">@<?= htmlspecialchars($m['username']) ?></span>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:5px">
        <?php if ($m['virtual']): ?>
        <button class="btn btn-sm" onclick="openRename(<?= $m['id'] ?>, <?= json_encode($m['name']) ?>)">✏</button>
        <?php endif; ?>
        <form method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/remove-member"
          onsubmit="return confirm('Retirer <?= htmlspecialchars(addslashes($m['name'])) ?> ?')">
          <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
          <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger">✕</button>
        </form>
      </div>
    </div>
    <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:10px">
      <span class="badge bp"><?= $s['p'] ?>j P</span>
      <span class="badge bt"><?= $s['t'] ?>j TT</span>
      <span class="badge br"><?= $s['r'] ?>j RTT</span>
      <span class="badge bc"><?= $s['c'] ?>j CP</span>
      <?php if ($s['s'] > 0): ?><span class="badge bs"><?= $s['s'] ?>j SS</span><?php endif; ?>
    </div>
    <div style="font-size:12px;color:var(--mu);margin-bottom:10px">
      Travaillé : <strong style="color:var(--ac)"><?= $w ?> j</strong> / <?= $curYear ?>
    </div>
    <div style="display:flex;gap:6px">
      <a class="btn btn-sm btn-primary" href="<?= $base ?>teams/member/<?= $m['id'] ?>/year/<?= $curYear ?>">📅 Année</a>
      <a class="btn btn-sm" href="<?= $base ?>teams/member/<?= $m['id'] ?>/month/<?= $cy ?>/<?= $cm ?>">📆 Mois</a>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <!-- AJOUTER MEMBRES -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="card">
      <div class="card-title">+ Membre virtuel (sans compte)</div>
      <div style="font-size:12px;color:var(--mu);margin-bottom:12px">Compte sans accès à l'application. Vous saisissez son CRA.</div>
      <form method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/add-virtual" style="display:flex;gap:8px;align-items:flex-end">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
        <div class="form-group" style="flex:1;margin-bottom:0">
          <label>NOM COMPLET</label>
          <input type="text" name="name" required placeholder="Ex: Marie Dupont">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
      </form>
    </div>
    <div class="card">
      <div class="card-title">+ Membre avec compte existant</div>
      <div style="font-size:12px;color:var(--mu);margin-bottom:12px">Utilisateur ayant déjà un compte. Il garde son accès normal.</div>
      <form method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/add-member" style="display:flex;gap:8px;align-items:flex-end">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
        <div class="form-group" style="flex:1;margin-bottom:0">
          <label>UTILISATEUR</label>
          <select name="user_id" required>
            <option value="">— Sélectionner —</option>
            <?php
            $memberIds = array_column($members, 'id');
            foreach ($allReal as $u):
              if (in_array($u['id'], $memberIds)) continue;
            ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-sm">Ajouter</button>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ÉDITION ÉQUIPE -->
<div class="modal-backdrop" id="editTeamModal">
  <div class="modal">
    <h3>Modifier l'équipe</h3>
    <form method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/edit">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-group"><label>NOM</label><input type="text" name="name" value="<?= htmlspecialchars($team['name']) ?>" required></div>
      <?php if ($me['role'] === 'admin'): ?>
      <div class="form-group"><label>RESPONSABLE</label>
        <select name="owner_id">
          <?php foreach ($allReal as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $u['id'] === (int)$team['owner_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="modal-actions">
        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteTeam()">🗑 Supprimer l'équipe</button>
        <button type="button" class="btn" onclick="document.getElementById('editTeamModal').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL RENOMMER MEMBRE -->
<div class="modal-backdrop" id="renameModal">
  <div class="modal">
    <h3>Renommer le membre</h3>
    <form method="POST" id="renameForm">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
      <div class="form-group"><label>NOM COMPLET</label><input type="text" name="name" id="renameName" required></div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('renameModal').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn btn-primary">Renommer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ARCHIVE -->
<div class="modal-backdrop" id="archiveModal">
  <div class="modal">
    <h3>Archiver l'équipe</h3>
    <form method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/archive">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-row">
        <div class="form-group"><label>ANNÉE</label><input type="number" name="year" value="<?= $curYear ?>" min="2020" max="2099"></div>
        <div class="form-group"><label>LIBELLÉ</label><input type="text" name="label" placeholder="Archive <?= $curYear ?>"></div>
      </div>
      <div style="font-size:12px;color:var(--mu);margin-bottom:12px">Snapshot JSON de toute l'équipe pour l'année choisie.</div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('archiveModal').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn btn-primary">🗄 Archiver</button>
      </div>
    </form>
  </div>
</div>

<form id="deleteTeamForm" method="POST" action="<?= $base ?>teams/<?= $team['id'] ?>/delete" style="display:none">
  <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
</form>

<script>
['editTeamModal','renameModal','archiveModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', e => {
    if (e.target === e.currentTarget) e.target.classList.remove('open');
  });
});
function openRename(uid, name) {
  document.getElementById('renameName').value = name;
  document.getElementById('renameForm').action = '<?= $base ?>teams/member/' + uid + '/rename';
  document.getElementById('renameModal').classList.add('open');
  document.getElementById('renameName').focus();
}
function confirmDeleteTeam() {
  if (confirm('Supprimer l\'équipe "<?= htmlspecialchars(addslashes($team['name'])) ?>" ?\nLes membres virtuels sans autre équipe seront aussi supprimés.')) {
    document.getElementById('deleteTeamForm').submit();
  }
}
</script>
