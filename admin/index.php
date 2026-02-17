<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';

$db = db();
$res = $db->query("SELECT id, nombre, categoria, calificacion, imagen FROM productos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Productos</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap{max-width:1100px;margin:20px auto;padding:0 20px}
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 10px rgba(0,0,0,.08)}
    th,td{padding:12px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
    th{background:#fafafa}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn-mini{padding:8px 12px;border-radius:16px}
    .btn-danger{background:#ff3b30}
    .topbar{display:flex;justify-content:space-between;align-items:center;margin:18px 0}
    .thumb{width:70px;height:50px;object-fit:cover;border-radius:8px}
  </style>
</head>
<body>

<header>
  <h1>Panel de Administración</h1>
</header>

<div class="admin-wrap">
  <div class="topbar">
    <div>
      <a class="btn" href="edit.php">➕ Nuevo recortable</a>
	<a class="btn secundario" href="../index.php">🏠 Ver web</a>



    </div>
    <a class="btn secundario" href="logout.php">Salir</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Imagen</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>⭐</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($p = $res->fetchArray(SQLITE3_ASSOC)): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td>
          <?php if (!empty($p['imagen'])): ?>
            <img class="thumb" src="../<?= h(ltrim($p['imagen'],'/')) ?>" alt="">
          <?php endif; ?>
        </td>
        <td><?= h($p['nombre'] ?? '') ?></td>
        <td><?= h($p['categoria'] ?? '') ?></td>
        <td><?= h($p['calificacion'] ?? '') ?></td>
        <td class="actions">
          <a class="btn btn-mini" href="edit.php?id=<?= (int)$p['id'] ?>">Editar</a>
          <a class="btn btn-mini btn-danger" href="delete.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('¿Borrar este producto?')">Borrar</a>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>

