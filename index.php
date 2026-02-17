<?php
session_start();

$db = new SQLite3(__DIR__ . '/recortables.db');

function hasCol(SQLite3 $db, string $table, string $col): bool {
  $res = $db->query("PRAGMA table_info($table)");
  while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
    if (($r['name'] ?? '') === $col) return true;
  }
  return false;
}

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function img_path(string $p): string {
  $p = trim($p);
  if ($p === '') return '';
  if (preg_match('#^(https?://|data:)#i', $p)) return $p;

  $p = ltrim($p, '/'); // IMPORTANTÍSIMO (para no romper subcarpetas)

  if (file_exists(__DIR__ . '/' . $p)) return $p;
  if (file_exists(__DIR__ . '/img/' . $p)) return 'img/' . $p;

  return $p;
}

$carritoCount = isset($_SESSION['carrito']) ? array_sum($_SESSION['carrito']) : 0;

$tieneCategoria    = hasCol($db, 'productos', 'categoria');
$tieneCalificacion = hasCol($db, 'productos', 'calificacion');
$tieneFecha        = hasCol($db, 'productos', 'fecha');
$tienePrecio       = hasCol($db, 'productos', 'precio');

$categorias = [];
if ($tieneCategoria) {
  $res = $db->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria");
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) $categorias[] = $row['categoria'];
} else {
  $categorias = ['Vehículos','Edificios','Robots','Animales','Fantasía'];
}

$orderDest = $tieneCalificacion ? "calificacion DESC, id ASC" : "id DESC";
$colsDest = "id, nombre, imagen" .
  ($tieneCalificacion ? ", calificacion" : "") .
  ($tienePrecio ? ", precio" : "") .
  ($tieneFecha ? ", fecha" : "");

$destacados = $db->query("SELECT $colsDest FROM productos ORDER BY $orderDest LIMIT 3");

$imgCat = [
  'Vehículos' => 'vehiculos.jpg',
  'Edificios' => 'edificios.jpg',
  'Robots'    => 'robots.jpg',
  'Animales'  => 'animales.jpg',
  'Fantasía'  => 'fantasia.jpg',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Juguetes Recortables</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="hero">

  <img src="img/banner.jpg" class="hero-img" alt="Recortables Creativos">

  <a href="carrito.php" class="cart-link">
    🛒
    <?php if ($carritoCount > 0): ?>
      <span class="cart-badge"><?= $carritoCount ?></span>
    <?php endif; ?>
  </a>

  <div class="hero-content">
    <div class="hero-box">
      <h1>Recortables Creativos</h1>

      <div class="hero-botones">
  <a href="#categoriasprincipales" class="btn">📁 Categorías</a>
  <a href="#recortablesdestacados" class="btn secundario">⭐ Destacados</a>
</div>

  </div>

</header>

<main>

  <section>
    <h3>Buscar</h3>
    <form class="buscador" action="buscar.php" method="get">
      <input type="text" name="q" placeholder="Buscar recortables...">
      <button class="btn" type="submit">🔎 Buscar</button>
    </form>
  </section>

  <section id="categoriasprincipales">
    <h3>Categorías</h3>

    <div class="contenedor">
      <?php foreach ($categorias as $c) {
        $img = $imgCat[$c] ?? 'vehiculos.jpg';
      ?>
        <a class="cardlink" href="categoria.php?c=<?= urlencode($c) ?>">
          <article>
            <img src="<?= h(img_path($img)) ?>" alt="<?= h($c) ?>">
            <p><?= h($c) ?></p>
          </article>
        </a>
      <?php } ?>
    </div>
  </section>

  <section id="recortablesdestacados">
    <h3>Destacados</h3>

    <div class="contenedor">
      <?php while ($p = $destacados->fetchArray(SQLITE3_ASSOC)) { ?>
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
    </div>
  </section>

</main>

<footer>
  <p>© 2026 Serena Sania Esteve</p>
  <p>Proyecto realizado con HTML, CSS y PHP</p>
</footer>

</body>
</html>

