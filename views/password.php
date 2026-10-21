<a class="back" href="<?= e(url()) ?>">← Biblioteca</a>
<div class="page-heading">
<div>
<span class="eyebrow">TU CUENTA</span>
<h1>Cambiar contraseña</h1>
<p>Mantené protegido el acceso a tu editorial.</p>
</div>
</div>
<form class="panel narrow" method="post">
<?= csrf() ?>
<?php foreach(['current'=>'Contraseña actual','password'=>'Nueva contraseña','confirm'=>'Repetir nueva contraseña'] as $f=>$label): ?>
<div class="field">
<label for="<?= $f ?>">
<?= $label ?>
</label>
<input id="<?= $f ?>" name="<?= $f ?>" type="password" required maxlength="72" autocomplete="<?= $f==='current'?'current-password':'new-password' ?>" <?= $f!=='current'?'minlength="8"':'' ?> <?php if(isset($errors[$f])): ?>aria-invalid="true" aria-describedby="error-<?= $f ?>"<?php endif; ?>>
<?php if(isset($errors[$f])): ?>
<small class="error" id="error-<?= $f ?>">
<?= e($errors[$f]) ?>
</small>
<?php endif; ?>
</div>
<?php endforeach; ?>
<p class="subtle">Entre 8 y 72 caracteres. Las otras sesiones se cerrarán.</p>
<div class="form-actions">
<button class="button">Guardar contraseña</button>
<a class="button secondary" href="<?= e(url()) ?>">Cancelar</a>
</div>
</form>