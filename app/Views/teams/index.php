<?php
$base    = BASE_URL;
$allReal = \Models\User::all(false);
?>
<div class="topbar">
  <div class="topbar-title">👥 Mes équipes</div>
  <div class="topbar-actions">
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('createTeamModal').classList.add('open')">+ Nouvelle équipe</button>
  </div>
</div>
<div class="content">
  <?php if (empty($teams)): ?>
  <div style="text-align:center;padding:60px;color:var(--mu);font-family:'DM Mono',monospace">
    <div style="font-size:32px;margin-bottom:12px">👥</div>
    Aucune équipe. Créez la première pour commencer.
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">
  <?php foreach ($teams as $t): ?>
  <div class="card" style="cursor:pointer" onclick="location.href='<?= $base ?>teams/<?= $t['id'] ?>'">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
      <div>
        <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($t['name']) ?></div>
        <div style="font-size:11px;color:var(--mu);margin-top:2px">Responsable : <?= htmlspecialchars($t['owner_name']) ?></div>
      </div>
      <span style="background:rgba(108,99,255,.12);color:var(--ac);border-radius:4px;padding:3px 8px;font-size:11px;font-family:'DM Mono',monospace">
        <?= $t['member_count'] ?> membre<?= $t['member_count'] > 1 ? 's' : '' ?>
      </span>
    </div>
    <div style="font-size:12px;color:var(--mu)">Créée le <?= date('d/m/Y', strtotime($t['created_at'])) ?></div>
  </div>
  <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="modal-backdrop" id="createTeamModal">
  <div class="modal">
    <h3>Nouvelle équipe</h3>
    <form method="POST" action="<?= $base ?>teams/create">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-group">
        <label>NOM DE L'ÉQUIPE</label>
        <input type="text" name="name" required autofocus placeholder="Ex: Équipe Support">
      </div>
      <?php if ($me['role'] === 'admin'): ?>
      <div class="form-group">
        <label>RESPONSABLE</label>
        <select name="owner_id">
          <?php foreach ($allReal as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $u['id'] === $me['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="document.getElementById('createTeamModal').classList.remove('open')">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('createTeamModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) e.target.classList.remove('open');
});
</script>
