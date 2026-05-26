<?php $base = BASE_URL; ?>
<div class="topbar">
  <div class="topbar-title">🔗 Délégations de visualisation</div>
</div>
<div class="content">
  <div class="card" style="margin-bottom:16px">
    <div style="font-size:13px;color:var(--mu);margin-bottom:16px;line-height:1.6">
      Une délégation permet à un utilisateur de <strong style="color:var(--tx)">visualiser</strong> (lecture seule) le CRA d'un autre utilisateur.<br>
      L'admin voit toujours tous les CRA.
    </div>
    <div class="card-title">Ajouter une délégation</div>
    <form method="POST" action="<?= $base ?>admin/delegations/save" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
        <label>PROPRIÉTAIRE DU CRA</label>
        <select name="owner_id" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="font-size:18px;padding-bottom:6px;color:var(--mu)">→</div>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
        <label>PEUT ÊTRE VU PAR</label>
        <select name="viewer_id" required>
          <option value="">— Sélectionner —</option>
          <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">+ Ajouter</button>
    </form>
  </div>

  <?php if (empty($delegations)): ?>
  <div style="text-align:center;color:var(--mu);padding:40px;font-family:'DM Mono',monospace;font-size:13px">Aucune délégation configurée.</div>
  <?php else: ?>
  <div style="overflow-x:auto;background:var(--bg2);border:1px solid var(--bd);border-radius:var(--rad)">
    <table>
      <thead><tr><th>PROPRIÉTAIRE</th><th></th><th>PEUT ÊTRE VU PAR</th><th>ACTION</th></tr></thead>
      <tbody>
      <?php foreach ($delegations as $d): ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($d['owner']) ?></td>
        <td style="color:var(--mu);text-align:center">→</td>
        <td style="font-weight:600"><?= htmlspecialchars($d['viewer']) ?></td>
        <td>
          <form method="POST" action="<?= $base ?>admin/delegations/delete">
            <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette délégation ?')">✕ Supprimer</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
