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

$c = isset($_GET['c']) ? trim($_GET['c']) : '';
if ($c === '') { echo "Categoría no válida"; exit; }

$tieneCategoria    = hasCol($db, 'productos', 'categoria');
$tieneCalificacion = hasCol($db, 'productos', 'calificacion');
$tienePrecio       = hasCol($db, 'productos', 'precio');
$tieneFecha        = hasCol($db, 'productos', 'fecha');

$cols = "id, nombre, imagen" .
  ($tieneCalificacion ? ", calificacion" : "") .
  ($tienePrecio ? ", precio" : "") .
  ($tieneFecha ? ", fecha" : "");

if ($tieneCategoria) {
  $stmt = $db->prepare("SELECT $cols FROM productos WHERE categoria = :c ORDER BY " . ($tieneCalificacion ? "calificacion DESC, id ASC" : "id DESC"));
  $stmt->bindValue(':c', $c, SQLITE3_TEXT);
} else {
  $like = '%' . $c . '%';
  $stmt = $db->prepare("SELECT $cols FROM productos WHERE nombre LIKE :q OR descripcion LIKE :q ORDER BY id DESC");
  $stmt->bindValue(':q', $like, SQLITE3_TEXT);
}
$res = $stmt->execute();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categoría: <?= h($c) ?></title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <h1>Juguetes Recortables</h1>
  <p>Categoría: <?= h($c) ?></p>

  <a class="cart-link" href="carrito.php" aria-label="Carrito">🛒
    <?php if ($carritoCount > 0) { ?><span class="cart-badge"><?= (int)$carritoCount ?></span><?php } ?>
  </a>
</header>

<main>
  <section>
    <div class="contenedor">
      <?php
      $hay = false;
      while ($p = $res->fetchArray(SQLITE3_ASSOC)) {
        $hay = true;
      ?>
        <a class="cardlink" href="producto.php?id=<?= (int)$p['id'] ?>">
          <article class="destacado">
            <img src="<?= h(img_path($p['imagen'] ?? '')) ?>" alt="<?= h($p['nombre'] ?? '') ?>">
            <h4><?= h($p['nombre'] ?? '') ?></h4>

            <?php if (isset($p['calificacion'])) { ?>
              <p class="calificacion">⭐ <?= h((string)$p['calificacion']) ?></p>
            <?php } ?>

            <?php if (isset($p['precio'])) { ?>
              <p class="precio"><?= number_format((float)$p['precio'], 2) ?> €</p>
            <?php } ?>

            <?php if (isset($p['fecha']) && $p['fecha']) { ?>
              <p class="fecha">📅 <?= h($p['fecha']) ?></p>
            <?php } ?>
          </article>
        </a>
      <?php } ?>

      <?php if (!$hay) { ?>
        <p>No hay recortables en esta categoría.</p>
      <?php } ?>
    </div>

    <div style="text-align:center; margin-top:20px;">
      <a class="btn" href="index.php">⬅ Volver</a>
    </div>
  </section>
</main>

<footer>
  <p>© 2026 Serena Sania Esteve</p>
  <p>Proyecto realizado con HTML, CSS y PHP</p>
</footer>

</body>
</html>

