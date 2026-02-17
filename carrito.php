<?php
session_start();

$db = new SQLite3(__DIR__ . '/recortables.db');

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function img_path(string $p): string {
  $p = trim((string)$p);
  if ($p === '') return '';
  if (preg_match('#^(https?://|data:)#i', $p)) return $p;

  $p = ltrim($p, '/');

  if (file_exists(__DIR__ . '/' . $p)) return $p;
  if (file_exists(__DIR__ . '/img/' . $p)) return 'img/' . $p;

  return $p;
}

/* Resolver ruta a fichero local (para el ZIP). Devuelve path absoluto o '' */
function img_local_abspath(string $img): string {
  $img = trim((string)$img);
  if ($img === '') return '';
  if (preg_match('#^(https?://|data:)#i', $img)) return '';

  $img = ltrim($img, '/');

  $candidates = [
    __DIR__ . '/' . $img,
    __DIR__ . '/img/' . $img,
  ];

  foreach ($candidates as $cand) {
    $real = realpath($cand);
    if ($real && is_file($real)) {
      // Seguridad: que esté dentro del proyecto
      $base = realpath(__DIR__);
      if ($base && str_starts_with($real, $base)) {
        return $real;
      }
    }
  }
  return '';
}

/* Inicializar carrito */
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
  $_SESSION['carrito'] = [];
}

/* ✅ AÑADIR DESDE PRODUCTO (POST) */
if (isset($_POST['id'])) {
  $id = (int)$_POST['id'];
  if ($id > 0) {
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
  }
  header("Location: carrito.php");
  exit;
}

/* ➕ SUMAR */
if (isset($_GET['mas'])) {
  $id = (int)$_GET['mas'];
  if ($id > 0) {
    $_SESSION['carrito'][$id] = ($_SESSION['carrito'][$id] ?? 0) + 1;
  }
  header("Location: carrito.php");
  exit;
}

/* ➖ RESTAR */
if (isset($_GET['menos'])) {
  $id = (int)$_GET['menos'];
  if (isset($_SESSION['carrito'][$id])) {
    $_SESSION['carrito'][$id]--;
    if ($_SESSION['carrito'][$id] <= 0) {
      unset($_SESSION['carrito'][$id]);
    }
  }
  header("Location: carrito.php");
  exit;
}

/* ❌ ELIMINAR */
if (isset($_GET['eliminar'])) {
  $id = (int)$_GET['eliminar'];
  unset($_SESSION['carrito'][$id]);
  header("Location: carrito.php");
  exit;
}

/* 🧹 VACIAR */
if (isset($_GET['vaciar'])) {
  $_SESSION['carrito'] = [];
  header("Location: carrito.php");
  exit;
}

/* 📦 DESCARGAR ZIP */
if (isset($_GET['zip'])) {
  if (empty($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit;
  }

  if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo "ZipArchive no está disponible en tu PHP.";
    exit;
  }

  $ids = array_map('intval', array_keys($_SESSION['carrito']));
  $in  = implode(',', $ids);

  $res = $db->query("SELECT id, nombre, imagen FROM productos WHERE id IN ($in)");
  if (!$res) {
    http_response_code(500);
    echo "Error SQL: " . h($db->lastErrorMsg());
    exit;
  }

  $items = [];
  while ($p = $res->fetchArray(SQLITE3_ASSOC)) {
    $id = (int)$p['id'];
    $p['cantidad'] = (int)($_SESSION['carrito'][$id] ?? 0);
    $items[] = $p;
  }

  $tmp = tempnam(sys_get_temp_dir(), 'carrito_');
  $zipPath = $tmp . '.zip';
  @unlink($tmp);

  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    echo "No se pudo crear el ZIP.";
    exit;
  }

  $manifest = [];
  $manifest[] = "Carrito - Recortables";
  $manifest[] = "Generado: " . date('Y-m-d H:i:s');
  $manifest[] = "----------------------------------------";

  $added = 0;

  foreach ($items as $p) {
    $id = (int)$p['id'];
    $nombre = (string)($p['nombre'] ?? '');
    $img = (string)($p['imagen'] ?? '');
    $cant = (int)($p['cantidad'] ?? 0);

    $abs = img_local_abspath($img);
    if ($abs === '') {
      $manifest[] = "ID $id | $nombre | Cantidad: $cant | IMAGEN NO ENCONTRADA: $img";
      continue;
    }

    $ext = pathinfo($abs, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nombre);
    $safeName = trim($safeName, '_');
    if ($safeName === '') $safeName = "producto";

    $zipName = "imagenes/{$id}_{$safeName}" . ($ext ? "." . $ext : "");

    // Evita duplicados en nombre dentro del ZIP
    $i = 2;
    $baseZipName = $zipName;
    while ($zip->locateName($zipName) !== false) {
      $zipName = preg_replace('/(\.[^.]*)?$/', "_$i$0", $baseZipName);
      $i++;
    }

    $zip->addFile($abs, $zipName);
    $added++;

    $manifest[] = "ID $id | $nombre | Cantidad: $cant | Imagen: $zipName";
  }

  $zip->addFromString("manifest.txt", implode("\n", $manifest) . "\n");
  $zip->close();

  $filename = "carrito_recortables_" . date('Ymd_His') . ".zip";

  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Content-Length: ' . filesize($zipPath));
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');

  readfile($zipPath);
  @unlink($zipPath);
  exit;
}

$carritoCount = array_sum($_SESSION['carrito']);

/* Cargar productos del carrito */
$productos = [];
if (!empty($_SESSION['carrito'])) {
  $ids = array_map('intval', array_keys($_SESSION['carrito']));
  $in  = implode(',', $ids);

  $res = $db->query("SELECT id, nombre, imagen FROM productos WHERE id IN ($in)");
  while ($p = $res->fetchArray(SQLITE3_ASSOC)) {
    $id = (int)$p['id'];
    $p['cantidad'] = (int)($_SESSION['carrito'][$id] ?? 0);
    $productos[] = $p;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Carrito</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<header>
  <h1>Carrito</h1>
  <p>Recortables seleccionados</p>

  <a class="cart-link" href="carrito.php" aria-label="Carrito">🛒
    <?php if ($carritoCount > 0) { ?><span class="cart-badge"><?= (int)$carritoCount ?></span><?php } ?>
  </a>
</header>

<main>
  <section>
    <?php if (empty($productos)) { ?>
      <p style="text-align:center;">El carrito está vacío.</p>
      <div style="text-align:center; margin-top:20px;">
        <a class="btn" href="index.php">⬅ Volver a la tienda</a>
      </div>
    <?php } else { ?>

      <div class="contenedor">
        <?php foreach ($productos as $p) { ?>
          <article class="destacado">
            <a class="cardlink" href="producto.php?id=<?= (int)$p['id'] ?>">
              <img src="<?= h(img_path($p['imagen'] ?? '')) ?>" alt="<?= h($p['nombre'] ?? '') ?>">
              <h4><?= h($p['nombre'] ?? '') ?></h4>
            </a>

            <div style="margin-top:10px;">
              <a class="btn secundario" href="carrito.php?menos=<?= (int)$p['id'] ?>">➖</a>
              <span style="font-weight:bold; padding:0 10px;"><?= (int)$p['cantidad'] ?></span>
              <a class="btn secundario" href="carrito.php?mas=<?= (int)$p['id'] ?>">➕</a>
              <a class="btn secundario" href="carrito.php?eliminar=<?= (int)$p['id'] ?>">❌</a>
            </div>
          </article>
        <?php } ?>
      </div>

      <div style="text-align:center; margin-top:22px;">
        <a class="btn" href="index.php">⬅ Seguir viendo recortables</a>
        <a class="btn secundario" href="carrito.php?vaciar=1">Vaciar carrito</a>
        <a class="btn" href="carrito.php?zip=1">⬇ Descargar carrito (ZIP)</a>
      </div>

    <?php } ?>
  </section>
</main>

<footer>
  <p>© 2026 Serena Sania Esteve</p>
  <p>Proyecto realizado con HTML, CSS y PHP</p>
</footer>

</body>
</html>

