<div class="page-heading">
<div>
<span class="eyebrow">CATÁLOGO EDITORIAL</span>
<h1>Biblioteca de títulos<span class="dot">.</span>
</h1>
<p>Cada libro, su historia. Toda tu biblioteca en un lugar.</p>
</div>
<a class="button" href="?action=new">
<span aria-hidden="true">＋</span> Nuevo título</a>
</div>
<form class="toolbar" method="get" role="search">
<div class="search">
<span aria-hidden="true">
</span>
<label class="sr-only" for="q">Buscar títulos</label>
<input id="q" name="q" value="<?= e($search) ?>" placeholder="Buscar por código, título, autor o ISBN">
</div>
<label class="sr-only" for="status">Estado</label>
<select id="status" name="status">
<?php foreach(['active'=>'Activos','inactive'=>'Inactivos','all'=>'Todos los estados'] as $v=>$t): ?>
<option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>>
<?= $t ?>
</option>
<?php endforeach; ?>
</select>
<label class="sr-only" for="sort">Ordenar</label>
<select id="sort" name="sort">
<?php foreach(['title'=>'Título A–Z','code'=>'Código A–Z','pvp'=>'PVP menor a mayor','pvp_desc'=>'PVP mayor a menor'] as $v=>$t): ?>
<option value="<?= $v ?>" <?= $sort===$v?'selected':'' ?>>
<?= $t ?>
</option>
<?php endforeach; ?>
</select>
<button class="button secondary">Buscar</button>
</form>
<div class="results">
<strong>
<?= $count ?> <?= $count===1?'título':'títulos' ?>
</strong>
<span>
<?= $status==='active'?'En tu biblioteca activa':($status==='inactive'?'Títulos inactivos':'Toda la biblioteca') ?>
</span>
</div>
<?php if(!$books): ?>
<section class="empty panel">
<div class="book-icon" aria-hidden="true">▤</div>
<span class="eyebrow">ESPACIO PARA NUEVAS HISTORIAS</span>
<h2>
<?= $search!=='' || $status!=='active'?'No encontramos títulos':'Tu biblioteca empieza acá' ?>
</h2>
<p>
<?= $search!=='' || $status!=='active'?'Probá con otra búsqueda o cambiá los filtros.':'Agregá tu primer libro. Podés completar su ficha de a poco.' ?>
</p>
<a class="button" href="<?= $search!=='' || $status!=='active'?url('?status=all'):'?action=new' ?>">
<?= $search!=='' || $status!=='active'?'Ver todos los títulos':'Crear primer título' ?>
</a>
</section>
<?php else: ?>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Código</th>
<th>Título</th>
<th>Autor</th>
<th>ISBN</th>
<th class="price">PVP</th>
<th>Estado</th>
<th>
<span class="sr-only">Acciones</span>
</th>
</tr>
</thead>
<tbody>
<?php foreach($books as $b): ?>
<tr>
<td>
<span class="code">
<?= e($b['code']) ?>
</span>
</td>
<td>
<a class="book-cell" href="?action=detail&id=<?= $b['id'] ?>">
<?php if($b['cover']): ?>
<img class="thumbnail" src="?action=cover&id=<?= $b['id'] ?>" alt="" loading="lazy">
<?php else: ?>
<span class="thumbnail placeholder" aria-hidden="true">g.</span>
<?php endif; ?>
<span>
<strong>
<?= e($b['title']) ?>
</strong>
<?php if($b['subtitle']): ?>
<small>
<?= e($b['subtitle']) ?>
</small>
<?php endif; ?>
</span>
</a>
</td>
<td data-label="Autor">
<?= e($b['author']?:'—') ?>
</td>
<td class="isbn" data-label="ISBN">
<?= e($b['isbn']?:'—') ?>
</td>
<td data-label="PVP" class="price <?= $b['pvp']===null?'muted':'' ?>">
<?= pvp($b['pvp']) ?>
</td>
<td>
<span class="badge <?= $b['active']?'':'inactive' ?>">
<?= $b['active']?'Activo':'Inactivo' ?>
</span>
</td>
<td>
<a class="row-action" href="?action=detail&id=<?= $b['id'] ?>" aria-label="Ver <?= e($b['title']) ?>">Ver ficha →</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if($count>20): ?>
<nav class="pagination" aria-label="Paginación">
<?php if($page>1): ?>
<a class="button secondary" href="?<?= e(http_build_query(['q'=>$search,'status'=>$status,'sort'=>$sort,'page'=>$page-1])) ?>">← Anterior</a>
<?php endif; ?>
<span>Página <?= $page ?> de <?= (int)ceil($count/20) ?>
</span>
<?php if($page*20<$count): ?>
<a class="button secondary" href="?<?= e(http_build_query(['q'=>$search,'status'=>$status,'sort'=>$sort,'page'=>$page+1])) ?>">Siguiente →</a>
<?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>