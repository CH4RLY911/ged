<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>
<?= e($title) ?> · GED</title>
<link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
<script src="<?= e(url('assets/app.js')) ?>" defer>
</script>
</head>
<body>
<?php if(!empty($_SESSION['uid'])): ?>
<header class="header">
<a class="brand" href="<?= e(url()) ?>">ged<span>GESTIÓN EDITORIAL</span>
</a>
<div class="publisher">
<?= e($config['publisher']) ?>
</div>
<nav aria-label="Cuenta">
<a href="?action=password">Cambiar contraseña</a>
<form method="post" action="?action=logout">
<?= csrf() ?>
<button class="link">Cerrar sesión <span aria-hidden="true">→</span>
</button>
</form>
</nav>
</header>
<?php endif; ?>
<main class="<?= $name==='login'?'login-shell':'container' ?>">
<?php if(!empty($_SESSION['flash'])): ?>
<div class="notice" role="status">
<?= e($_SESSION['flash']) ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>
<?php require ROOT.'/views/'.$name.'.php'; ?>
</main>
<?php if($name!=='login'): ?>
<footer>GED <span>Un lugar para cada historia.</span>
</footer>
<?php endif; ?>
</body>
</html>