<?php $base = BASE_URL; ?>
<div class="topbar">
  <div class="topbar-title">👤 Gestion des utilisateurs</div>
  <div class="topbar-actions">
    <a class="btn btn-sm" href="<?= $base ?>teams">👥 Gérer les équipes</a>
    <a class="btn btn-sm" href="<?= $base ?>admin/reset" style="color:var(--c);border-color:rgba(236,72,153,.4)">⚠ Remise à zéro</a>
  </div>
</div>
<div class="content">

  <div class="card">
    <div class="card-title">Créer un compte utilisateur</div>
    <form method="POST" action="<?= $base ?>admin/users/create">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-row">
        <div class="form-group"><label>IDENTIFIANT</label><input type="text" name="username" required placeholder="jdupont"></div>
        <div class="form-group"><label>NOM COMPLET</label><input type="text" name="name" required placeholder="Jean Dupont"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>MOT DE PASSE</label><input type="password" name="password" required placeholder="••••••••"></div>
        <div class="form-group"><label>RÔLE</label>
          <select name="role">
            <option value="user">Utilisateur</option>
            <option value="admin">Administrateur</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">+ Créer le compte</button>
    </form>
  </div>

  <div style="overflow-x:auto;background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad);margin-bottom:20px">
    <div style="padding:12px 16px;border-bottom:1px solid var(--bd);font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.06em">
      COMPTES AVEC ACCÈS (<?= count($users) ?>)
    </div>
    <table>
      <thead><tr><th>NOM</th><th>IDENTIFIANT</th><th>RÔLE</th><th>STATUT</th><th>CRÉÉ LE</th><th>ACTIONS</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($u['name']) ?></td>
        <td style="font-family:'DM Mono',monospace;color:var(--mu)">@<?= htmlspecialchars($u['username']) ?></td>
        <td><span class="badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
        <td><?= $u['active']
          ? '<span style="color:var(--t);font-size:12px">● Actif</span>'
          : '<span style="color:var(--c);font-size:12px">● Inactif</span>' ?></td>
        <td style="color:var(--mu);font-size:12px"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a class="btn btn-sm" href="<?= $base ?>view/<?= $u['id'] ?>/year/<?= date('Y') ?>">👁 Voir</a>
            <button class="btn btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">✏ Éditer</button>
            <?php if ($u['id'] !== $me['id']): ?>
            <button class="btn btn-sm btn-danger"
              onclick="confirmDelete('<?= $base ?>admin/users/<?= $u['id'] ?>/delete',
                'Supprimer <?= htmlspecialchars(addslashes($u['name'])) ?> ?\nToutes ses données seront supprimées définitivement.')">
              🗑 Supprimer
            </button>
            <?php else: ?>
            <span style="font-size:11px;color:var(--mu)">Votre compte</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="overflow-x:auto;background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad)">
    <div style="padding:12px 16px;border-bottom:1px solid var(--bd);font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);letter-spacing:.06em">
      COMPTES VIRTUELS — sans accès, gérés par leur responsable d'équipe (<?= count($virtuals) ?>)
    </div>
    <?php if (empty($virtuals)): ?>
    <div style="padding:24px;text-align:center;color:var(--mu);font-size:13px">
      Aucun compte virtuel. Ils sont créés depuis la gestion des équipes.
    </div>
    <?php else: ?>
    <table>
      <thead><tr><th>NOM</th><th>ÉQUIPE</th><th>RESPONSABLE</th><th>CRÉÉ LE</th><th>ACTIONS</th></tr></thead>
      <tbody>
      <?php foreach ($virtuals as $v): ?>
      <tr>
        <td style="font-weight:600">
          <?= htmlspecialchars($v['name']) ?>
          <span style="font-size:10px;background:rgba(245,158,11,.12);color:var(--r);border-radius:3px;padding:1px 6px;font-family:'DM Mono',monospace;margin-left:6px">VIRTUEL</span>
        </td>
        <td><?= $v['team_name'] ? htmlspecialchars($v['team_name']) : '<span style="color:var(--mu)">—</span>' ?></td>
        <td style="color:var(--mu);font-size:12px"><?= $v['owner_name'] ? htmlspecialchars($v['owner_name']) : '—' ?></td>
        <td style="color:var(--mu);font-size:12px"><?= date('d/m/Y', strtotime($v['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <?php if ($v['team_id']): ?>
            <a class="btn btn-sm" href="<?= $base ?>teams/member/<?= $v['id'] ?>/year/<?= date('Y') ?>">👁 Voir CRA</a>
            <?php endif; ?>
            <button class="btn btn-sm btn-danger"
              onclick="confirmDelete('<?= $base ?>admin/users/<?= $v['id'] ?>/delete-virtual',
                'Supprimer le compte virtuel de <?= htmlspecialchars(addslashes($v['name'])) ?> ?\nTout son CRA sera supprimé.')">
              🗑 Supprimer
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-backdrop" id="editModal">
  <div class="modal" style="width:420px">
    <h3>Modifier l'utilisateur</h3>
    <form id="editForm" method="POST">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-group"><label>NOM COMPLET</label><input type="text" name="name" id="editName" required></div>
      <div class="form-row">
        <div class="form-group"><label>RÔLE</label>
          <select name="role" id="editRole">
            <option value="user">Utilisateur</option>
            <option value="admin">Administrateur</option>
          </select>
        </div>
        <div class="form-group"><label>STATUT</label>
          <select name="active" id="editActive">
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>NOUVEAU MOT DE PASSE (laisser vide = inchangé)</label>
        <input type="password" name="password" placeholder="••••••••">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" onclick="closeEdit()">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<form id="deleteForm" method="POST" style="display:none">
  <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
</form>

<script>
function openEdit(u){
  document.getElementById('editName').value   = u.name;
  document.getElementById('editRole').value   = u.role;
  document.getElementById('editActive').value = u.active;
  document.getElementById('editForm').action  = '<?= $base ?>admin/users/'+u.id+'/edit';
  document.getElementById('editModal').classList.add('open');
}
function closeEdit(){ document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeEdit(); });

function confirmDelete(url, msg){
  if (confirm(msg)) {
    const f = document.getElementById('deleteForm');
    f.action = url;
    f.submit();
  }
}
</script>
