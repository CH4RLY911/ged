<a class="back" href="<?= e(url()) ?>">← Biblioteca</a>
<div class="detail-heading">
<span class="eyebrow">FICHA DEL TÍTULO · #<?= $book['id'] ?>
</span>
<div class="detail-actions">
<a class="button" href="?action=edit&id=<?= $book['id'] ?>">Editar título</a>
<form method="post" action="?action=toggle&id=<?= $book['id'] ?>" <?= $book['active']?'data-confirm="¿Desactivar este título? Se conservará toda su información y podrás reactivarlo."':'' ?>>
<?= csrf() ?>
<button class="button secondary <?= $book['active']?'danger':'' ?>">
<?= $book['active']?'Desactivar':'Reactivar' ?>
</button>
</form>
</div>
</div>
<article class="detail-layout">
<aside>
<div class="detail-cover">
<?php if($book['cover']): ?>
<img src="?action=cover&id=<?= $book['id'] ?>" alt="Tapa de <?= e($book['title']) ?>">
<?php else: ?>
<div class="cover-empty">
<span aria-hidden="true">▤</span>
<span>Sin tapa todavía</span>
</div>
<?php endif; ?>
</div>
<p class="subtle">Creado: <?= e(localDate($book['created_at'])) ?><br>Actualizado: <?= e(localDate($book['updated_at'])) ?></p>
</aside>
<div>
<div class="title-meta">
<span class="code">
<?= e($book['code']) ?>
</span>
<span class="badge <?= $book['active']?'':'inactive' ?>">
<?= $book['active']?'Activo':'Inactivo' ?>
</span>
</div>
<h1 class="book-title">
<?= e($book['title']) ?>
</h1>
<?php if($book['subtitle']): ?>
<p class="subtitle">
<?= e($book['subtitle']) ?>
</p>
<?php endif; ?>
<p class="author">
<?= e($book['author']?:'Autor pendiente') ?>
</p>
<div class="commercial">
<div>
<span>ISBN</span>
<strong>
<?= e($book['isbn']?:'Pendiente') ?>
</strong>
</div>
<div>
<span>PVP</span>
<strong>
<?= pvp($book['pvp']) ?>
</strong>
</div>
<div>
<span>EDITORIAL / SELLO</span>
<strong>
<?= e($book['publisher']?:'Pendiente') ?>
</strong>
</div>
</div>
<section class="panel editorial">
<h2>Sobre esta edición</h2>
<dl>
<?php foreach(['edition'=>'Edición','year'=>'Año de publicación','format'=>'Formato','pages'=>'Páginas','width'=>'Ancho · cm','height'=>'Alto · cm'] as $f=>$label): ?>
<div>
<dt>
<?= $label ?>
</dt>
<dd>
<?= e($book[$f]??'Pendiente') ?>
</dd>
</div>
<?php endforeach; ?>
</dl>
</section>
<?php foreach(['synopsis'=>'Sinopsis','notes'=>'Observaciones'] as $f=>$label): ?>
<section class="prose">
<h2>
<?= $label ?>
</h2>
<p>
<?= nl2br(e($book[$f]?:'Todavía sin información.')) ?>
</p>
</section>
<?php endforeach; ?>
</div>
</article>