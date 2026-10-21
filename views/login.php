<section class="login-intro">
<a class="brand" href="<?= e(url()) ?>">ged<span>GESTIÓN EDITORIAL</span>
</a>
<div>
<span class="eyebrow">TU CATÁLOGO, EN ORDEN</span>
<h1>Las historias crecen.<br>Tu editorial también.</h1>
<p>Un espacio simple para cuidar cada título<br>y todo lo que lo hace único.</p>
</div>
<small>Biblioteca editorial · Primera edición</small>
</section>
<section class="login-panel">
<div class="login-box">
<span class="eyebrow">BIENVENIDO A GED</span>
<h2>Tu próxima página.</h2>
<p>Ingresá para administrar tu biblioteca de títulos.</p>
<?php if($error): ?>
<div class="error-banner" role="alert">
<?= e($error) ?>
</div>
<?php endif; ?>
<form method="post" action="?action=login">
<?= csrf() ?>
<label for="email">Correo electrónico</label>
<input id="email" name="email" type="email" autocomplete="username" required value="<?= e($_POST['email']??'') ?>">
<label for="password">Contraseña</label>
<input id="password" name="password" type="password" autocomplete="current-password" required maxlength="72">
<button class="button full">Ingresar <span aria-hidden="true">→</span>
</button>
</form>
<p class="subtle">Acceso exclusivo para la administración editorial.</p>
</div>
</section>