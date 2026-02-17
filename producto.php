<?php
session_start();

$db = new SQLite3(__DIR__ . '/recortables.db');

function hasCol(SQLite3 $db, string $table, string $col): bool {
  $res = $db->query("PRAGMA table_info($table)");
  while ($r = $res->fetchArray(SQLITE3_ASSOC)) if (($r['name'] ?? '') === $col) return true;
  return false;
}

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function img_path(string $p): string {
  $p = trim($p);
  if ($p === '') return '';
  if (preg_match('#^(https?://|data:)#i', $p)) return $p;
  $p = ltrim($p, '/');
  if (file_exists(__DIR__ . '/' . $p)) return $p;
  if (file_exists(__DIR__ . '/img/' . $p)) return 'img/' . $p;
  return $p;
}

$carritoCount = isset($_SESSION['carrito']) ? array_sum($_SESSION['carrito']) : 0;

$tienePrecio       = hasCol($db, 'productos', 'precio');
$tieneCalificacion = hasCol($db, 'productos', 'calificacion');
$tieneFecha        = hasCol($db, 'productos', 'fecha');
$tieneCategoria    = hasCol($db, 'productos', 'categoria');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo "ID no válido"; exit; }

$cols = "id, nombre, descripcion, imagen" .
  ($tienePrecio ? ", precio" : "") .
  ($tieneCalificacion ? ", calificacion" : "") .
  ($tieneFecha ? ", fecha" : "") .
  ($tieneCategoria ? ", categoria" : "");

$stmt = $db->prepare("SELECT $cols FROM productos WHERE id = :id LIMIT 1");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$res = $stmt->execute();
$producto = $res->fetchArray(SQLITE3_ASSOC);

if (!$producto) { echo "Producto no encontrado"; exit; }

if ($tieneCategoria && !empty($producto['categoria'])) {
  $stmtSim = $db->prepare("SELECT id, nombre, imagen" . ($tienePrecio ? ", precio" : "") . " FROM productos WHERE categoria = :c AND id <> :id ORDER BY RANDOM() LIMIT 4");
  $stmtSim->bindValue(':c', $producto['categoria'], SQLITE3_TEXT);
  $stmtSim->bindValue(':id', $id, SQLITE3_INTEGER);
} else {
  $stmtSim = $db->prepare("SELECT id, nombre, imagen" . ($tienePrecio ? ", precio" : "") . " FROM productos WHERE id <> :id ORDER BY RANDOM() LIMIT 4");
  $stmtSim->bindValue(':id', $id, SQLITE3_INTEGER);
}
$sim = $stmtSim->execute();
$similares = [];
while ($r = $sim->fetchArray(SQLITE3_ASSOC)) $similares[] = $r;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($producto['nombre']) ?></title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>Juguetes Recortables</h1>
  <p><?= h($producto['nombre']) ?></p>

  <a class="cart-link" href="carrito.php" aria-label="Carrito">🛒
    <?php if ($carritoCount > 0) { ?><span class="cart-badge"><?= (int)$carritoCount ?></span><?php } ?>
  </a>
</header>

<main>
  <section class="producto">
    <div class="producto-card">
      <div class="contenedor" style="align-items:flex-start;">
        <img class="producto-img" src="<?= h(img_path($producto['imagen'] ?? '')) ?>" alt="<?= h($producto['nombre'] ?? '') ?>">

        <div class="producto-info">
          <h3><?= h($producto['nombre'] ?? '') ?></h3>
          <p><?= nl2br(h($producto['descripcion'] ?? '')) ?></p>

          <?php if (isset($producto['calificacion'])) { ?>
            <p class="calificacion">⭐ <?= h((string)$producto['calificacion']) ?></p>
          <?php } ?>

          <?php if (isset($producto['precio'])) { ?>
            <p class="precio"><?= number_format((float)$producto['precio'], 2) ?> €</p>
          <?php } ?>

          <?php if (isset($producto['fecha']) && $producto['fecha']) { ?>
            <p class="fecha">📅 <?= h($producto['fecha']) ?></p>
          <?php } ?>

          <div style="margin-top:14px;">
            <a class="btn" href="index.php">⬅ Volver</a>

            <form action="carrito.php" method="post" style="display:inline-block;">
              <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">
              <button class="btn secundario" type="submit">🛒 Añadir al carrito</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php if (count($similares) > 0) { ?>
    <section>
      <h3>También te puede gustar</h3>
      <div class="contenedor">
        <?php foreach ($similares as $s) { ?>
          <a class="cardlink" href="producto.php?id=<?= (int)$s['id'] ?>">
            <article class="destacado">
              <img src="<?= h(img_path($s['imagen'] ?? '')) ?>" alt="<?= h($s['nombre'] ?? '') ?>">
              <h4><?= h($s['nombre'] ?? '') ?></h4>
              <?php if (isset($s['precio'])) { ?>
                <p class="precio"><?= number_format((float)$s['precio'], 2) ?> €</p>
              <?php } ?>
            </article>
          </a>
        <?php } ?>
      </div>
    </section>
  <?php } ?>
</main>

<footer>
  <p>© 2026 Serena Sania Esteve</p>
  <p>Proyecto realizado con HTML, CSS y PHP</p>
</footer>

</body>
</html>

