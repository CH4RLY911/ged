<?php
function field(string $key,string $label,array $d,array $errors,string $type='text',string $attrs=''): void { ?>
<div class="field <?= in_array($key,['title','subtitle','synopsis','notes'])?'wide':'' ?>">
<label for="<?= $key ?>">
<?= $label ?>
<?= in_array($key,['code','title'])?' <span aria-label="obligatorio">*</span>':'' ?>
</label>
<?php if($type==='textarea'): ?>
<textarea id="<?= $key ?>" name="<?= $key ?>" rows="5" <?php if(isset($errors[$key])): ?>aria-invalid="true" aria-describedby="error-<?= $key ?>"<?php endif; ?>>
<?= e($d[$key]??'') ?>
</textarea>
<?php else: ?>
<input id="<?= $key ?>" name="<?= $key ?>" type="<?= $type ?>" value="<?= e($d[$key]??'') ?>" <?= $attrs ?> <?php if(isset($errors[$key])): ?>aria-invalid="true" aria-describedby="error-<?= $key ?>"<?php endif; ?>>
<?php endif; ?>
<?php if(isset($errors[$key])): ?>
<small class="error" id="error-<?= $key ?>">
<?= e($errors[$key]) ?>
</small>
<?php endif; ?>
</div>
<?php }
?>
<a class="back" href="<?= $book?'?action=detail&id='.$book['id']:url() ?>">← <?= $book?'Volver a la ficha':'Biblioteca' ?>
</a>
<div class="page-heading">
<div>
<span class="eyebrow">BIBLIOTECA / <?= $book?'EDICIÓN':'ALTA' ?>
</span>
<h1>
<?= e($title) ?><span class="dot">.</span>
</h1>
<p>Empezá por lo esencial. Los campos con * son obligatorios.</p>
</div>
</div>
<?php if($errors): ?>
<div class="error-banner" role="alert">Revisá los campos indicados. Conservamos lo que escribiste. Si seleccionaste una tapa nueva, volvé a adjuntarla.</div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="book-form" data-save-form>
<?= csrf() ?>
<input type="hidden" name="submission" value="<?= e($token) ?>">
<div class="form-main">
<section class="panel">
<div class="section-heading">
<span class="step">01</span>
<div>
<h2>El libro</h2>
<p>Los datos que identifican esta edición.</p>
</div>
</div>
<div class="fields">
<?php field('code','Código corto',$d,$errors,'text','required maxlength="40" placeholder="Ej. EAC"'); field('author','Autor',$d,$errors); field('title','Título',$d,$errors,'text','required maxlength="500"'); field('subtitle','Subtítulo',$d,$errors); field('isbn','ISBN',$d,$errors,'text','placeholder="ISBN-10 o ISBN-13"'); field('publisher','Editorial o sello',$d,$errors); field('pvp','PVP · pesos argentinos',$d,$errors,'number','min="1" max="999999999999" step="1" placeholder="Pendiente"'); ?>
</div>
<p class="subtle">PVP sin decimales. Dejalo vacío si todavía está pendiente.</p>
</section>
<details class="panel" <?= array_intersect(array_keys($errors),['year','width','height','pages','edition','format','synopsis','notes'])?'open':'' ?>>
<summary>
<span class="step">02</span>
<span>Datos editoriales<small>Edición, formato, sinopsis y observaciones</small>
</span>
<span class="chevron">＋</span>
</summary>
<div class="fields detail-fields">
<?php field('edition','Edición',$d,$errors); field('year','Año de publicación',$d,$errors,'number','min="1000" max="2100" step="1"'); field('format','Formato o encuadernación',$d,$errors); field('pages','Cantidad de páginas',$d,$errors,'number','min="1" max="100000" step="1"'); field('width','Ancho · cm',$d,$errors,'text','inputmode="decimal" placeholder="Ej. 14,5"'); field('height','Alto · cm',$d,$errors,'text','inputmode="decimal" placeholder="Ej. 21"'); field('synopsis','Sinopsis',$d,$errors,'textarea'); field('notes','Observaciones',$d,$errors,'textarea'); ?>
</div>
</details>
</div>
<aside class="panel cover-panel">
<h2>Imagen de tapa</h2>
<p>La primera impresión de tu libro.</p>
<div class="cover-preview" id="cover-preview">
<?php if($book && $book['cover']): ?>
<img src="?action=cover&id=<?= $book['id'] ?>" alt="Tapa actual">
<?php else: ?>
<div class="cover-empty">
<span aria-hidden="true">▤</span>
<span>Tu próxima historia</span>
<small>Vista previa de tapa</small>
</div>
<?php endif; ?>
</div>
<label class="file-label" for="cover">Seleccionar imagen</label>
<input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" aria-describedby="cover-help <?= isset($errors['cover'])?'error-cover':'' ?>">
<span class="file-name" id="cover-filename" aria-live="polite">Ninguna imagen seleccionada</span>
<small id="cover-help">JPEG, PNG o WebP · Hasta 5 MB.<br>La tapa se muestra completa, sin recortes.</small>
<?php if(isset($errors['cover'])): ?>
<small id="error-cover" class="error">
<?= e($errors['cover']) ?>
</small>
<?php endif; ?>
<?php if($book && $book['cover']): ?>
<label class="checkbox">
<input type="checkbox" name="remove_cover" value="1" <?= !empty($_POST['remove_cover'])?'checked':'' ?>> Quitar tapa actual</label>
<?php endif; ?>
</aside>
<div class="form-actions form-footer">
<a class="cancel" href="<?= $book?'?action=detail&id='.$book['id']:url() ?>">Cancelar</a>
<button class="button secondary" name="after" value="another">Guardar y cargar otro</button>
<button class="button" name="after" value="detail">Guardar y abrir ficha →</button>
</div>
</form>