<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — CRA</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f0f10;--bg2:#18181b;--bg3:#232328;--bd:#2e2e35;--tx:#e8e8ed;--mu:#7f7f8f;--ac:#6c63ff;--c:#ec4899;--cb:#4a1030;--t:#22c55e;--tb:#14412a}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{width:360px;max-width:95vw}
.logo{text-align:center;margin-bottom:32px}
.logo h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;letter-spacing:-1px}
.logo span{font-size:11px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.1em}
.card{background:var(--bg2);border:1px solid var(--bd);border-radius:12px;padding:28px}
.field{display:flex;flex-direction:column;gap:5px;margin-bottom:16px}
.field label{font-size:11px;color:var(--mu);font-family:'DM Mono',monospace;letter-spacing:.05em}
.field input{background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:8px;padding:10px 14px;font-family:'Inter',sans-serif;font-size:14px;transition:border-color .12s;width:100%}
.field input:focus{outline:none;border-color:var(--ac)}
.btn{width:100%;background:var(--ac);border:none;color:#fff;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer;transition:background .12s;margin-top:4px;font-family:'Inter',sans-serif}
.btn:hover{background:#7c75ff}
.flash{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px;background:var(--cb);color:var(--c);border:1px solid rgba(236,72,153,.3)}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <h1>CRA</h1>
    <span>COMPTE RENDU D'ACTIVITÉ</span>
  </div>
  <div class="card">
    <?php if (!empty($flash)): ?>
    <div class="flash">⚠ <?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>login">
      <input type="hidden" name="_csrf" value="<?= $_csrf ?? '' ?>">
      <div class="field">
        <label>IDENTIFIANT</label>
        <input type="text" name="username" required autofocus placeholder="Identifiant">
      </div>
      <div class="field">
        <label>MOT DE PASSE</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn">Connexion</button>
    </form>
  </div>
</div>
</body></html>
