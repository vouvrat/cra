<?php $base = BASE_URL; ?>
<div class="topbar">
  <div class="topbar-title" style="color:var(--c)">⚠ Remise à zéro de la base de données</div>
</div>
<div class="content">
  <div style="max-width:560px;margin:0 auto">
    <div style="background:rgba(236,72,153,.08);border:1px solid rgba(236,72,153,.3);border-radius:var(--rad);padding:20px;margin-bottom:24px">
      <div style="font-size:15px;font-weight:700;color:var(--c);margin-bottom:10px">⚠ Action irréversible</div>
      <p style="font-size:13px;line-height:1.7;color:var(--tx)">Cette action va <strong>supprimer définitivement</strong> :</p>
      <ul style="margin:10px 0 0 20px;font-size:13px;line-height:2;color:var(--tx)">
        <li>Tous les jours saisis (CRA) de tous les utilisateurs</li>
        <li>Toutes les notes et configurations de trajet</li>
        <li>Toutes les délégations</li>
        <li>Toutes les équipes et leurs membres virtuels</li>
        <li>Tous les comptes utilisateurs (sauf votre compte admin)</li>
        <li>Toutes les archives (fichiers JSON inclus)</li>
      </ul>
      <p style="font-size:13px;margin-top:12px;color:var(--r)">
        <strong>Votre compte administrateur (<?= htmlspecialchars($me['name']) ?>) sera conservé.</strong>
      </p>
    </div>

    <div class="card">
      <div class="card-title">Confirmer la remise à zéro</div>
      <form method="POST" action="<?= $base ?>admin/reset/confirm" onsubmit="return validateReset()">
        <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
        <div class="form-group">
          <label>TAPEZ "RESET" POUR CONFIRMER</label>
          <input type="text" name="confirm" id="confirmInput" placeholder="RESET"
            autocomplete="off" style="font-family:'DM Mono',monospace;font-size:15px;letter-spacing:.1em;border-color:rgba(236,72,153,.4)">
        </div>
        <div style="font-size:12px;color:var(--mu);margin-bottom:16px">
          Cette action ne peut pas être annulée. Créez une archive avant si vous voulez conserver les données.
        </div>
        <div style="display:flex;gap:10px">
          <a class="btn" href="<?= $base ?>admin">← Annuler</a>
          <a class="btn" href="<?= $base ?>admin/archives" style="color:var(--ac)">🗄 Archiver d'abord</a>
          <button type="submit" class="btn btn-danger" style="margin-left:auto;background:rgba(236,72,153,.15)">
            ⚠ Réinitialiser
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function validateReset(){
  if (document.getElementById('confirmInput').value.trim() !== 'RESET') {
    alert('Vous devez taper exactement "RESET" pour confirmer.');
    return false;
  }
  return confirm('Dernière confirmation : supprimer TOUTES les données ? Irréversible.');
}
</script>
