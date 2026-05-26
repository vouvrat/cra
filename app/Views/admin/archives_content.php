<?php $base = BASE_URL; ?>
<div class="topbar">
  <div class="topbar-title">🗄️ Archives</div>
</div>
<div class="content">
  <div class="card">
    <div class="card-title">Créer une archive</div>
    <div style="font-size:12px;color:var(--mu);margin-bottom:14px">
      Une archive est un snapshot JSON des données d'une année (un utilisateur ou tous) stocké sur le serveur.
    </div>
    <form method="POST" action="<?= $base ?>admin/archives/create" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-group" style="margin-bottom:0">
        <label>ANNÉE</label>
        <input type="number" name="year" value="<?= date('Y') ?>" min="2020" max="2099" style="width:90px">
      </div>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px">
        <label>UTILISATEUR (vide = tous)</label>
        <select name="user_id">
          <option value="">— Tous les utilisateurs —</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;flex:2;min-width:200px">
        <label>LIBELLÉ (optionnel)</label>
        <input type="text" name="label" placeholder="Archive de fin d'année">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">🗄 Créer</button>
    </form>
  </div>

  <?php if (empty($archives)): ?>
  <div style="text-align:center;color:var(--mu);padding:40px;font-family:'DM Mono',monospace;font-size:13px">Aucune archive.</div>
  <?php else: ?>
  <div style="overflow-x:auto;background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad)">
    <table>
      <thead><tr><th>LIBELLÉ</th><th>ANNÉE</th><th>UTILISATEUR</th><th>CRÉÉE PAR</th><th>DATE</th><th>ACTIONS</th></tr></thead>
      <tbody>
      <?php foreach ($archives as $a): ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($a['label']) ?></td>
        <td style="font-family:'DM Mono',monospace;color:var(--ac)"><?= $a['year'] ?></td>
        <td><?= $a['user_name'] ? htmlspecialchars($a['user_name']) : '<span style="color:var(--mu)">Tous</span>' ?></td>
        <td style="color:var(--mu);font-size:12px"><?= htmlspecialchars($a['creator_name']) ?></td>
        <td style="color:var(--mu);font-size:12px"><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <a class="btn btn-sm" href="<?= $base ?>admin/archives/download/<?= $a['id'] ?>">⬇ JSON</a>
            <form method="POST" action="<?= $base ?>admin/archives/delete/<?= $a['id'] ?>" onsubmit="return confirm('Supprimer cette archive ?')">
              <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
              <button type="submit" class="btn btn-sm btn-danger">✕</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
