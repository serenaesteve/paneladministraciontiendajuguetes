<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';

$db = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modoEditar = $id > 0;

$producto = [
  'nombre' => '',
  'descripcion' => '',
  'categoria' => '',
  'calificacion' => '',
  'imagen' => ''
];

if ($modoEditar) {
  $st = $db->prepare("SELECT * FROM productos WHERE id = :id");
  $st->bindValue(':id', $id, SQLITE3_INTEGER);
  $r = $st->execute()->fetchArray(SQLITE3_ASSOC);
  if ($r) $producto = array_merge($producto, $r);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');
  $calificacion = trim($_POST['calificacion'] ?? '');
  $imagenActual = trim($_POST['imagen_actual'] ?? '');

  if ($nombre === '') {
    $error = 'El nombre es obligatorio';
  }

  // Subida de imagen (opcional)
  $imagenRuta = $imagenActual;

  if (!$error && !empty($_FILES['imagen']['name'])) {
    $tmp = $_FILES['imagen']['tmp_name'] ?? '';
    $err = $_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($err === UPLOAD_ERR_OK && is_uploaded_file($tmp)) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $tmp);
      finfo_close($finfo);

      $permitidos = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if (!isset($permitidos[$mime])) {
        $error = 'Formato de imagen no permitido (solo JPG/PNG/WEBP)';
      } else {
        $ext = $permitidos[$mime];
        $nombreSeguro = preg_replace('/[^a-zA-Z0-9_-]/','_', pathinfo($_FILES['imagen']['name'], PATHINFO_FILENAME));
        $finalName = $nombreSeguro . '_' . time() . '.' . $ext;

        $dir = dirname(__DIR__) . '/img';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $dest = $dir . '/' . $finalName;
        if (move_uploaded_file($tmp, $dest)) {
          $imagenRuta = 'img/' . $finalName;
        } else {
          $error = 'No se pudo guardar la imagen';
        }
      }
    } elseif ($err !== UPLOAD_ERR_NO_FILE) {
      $error = 'Error subiendo la imagen';
    }
  }

  if (!$error) {
    if ($modoEditar) {
      $st = $db->prepare("UPDATE productos
        SET nombre=:n, descripcion=:d, categoria=:c, calificacion=:cal, imagen=:i
        WHERE id=:id");
      $st->bindValue(':id', $id, SQLITE3_INTEGER);
    } else {
      $st = $db->prepare("INSERT INTO productos (nombre, descripcion, categoria, calificacion, imagen)
        VALUES (:n, :d, :c, :cal, :i)");
    }

    $st->bindValue(':n', $nombre, SQLITE3_TEXT);
    $st->bindValue(':d', $descripcion, SQLITE3_TEXT);
    $st->bindValue(':c', $categoria, SQLITE3_TEXT);
    $st->bindValue(':cal', $calificacion === '' ? null : (float)$calificacion, SQLITE3_FLOAT);
    $st->bindValue(':i', $imagenRuta, SQLITE3_TEXT);

    $st->execute();
    header('Location: index.php');
    exit;
  }

  // repoblar form
  $producto = [
    'nombre' => $nombre,
    'descripcion' => $descripcion,
    'categoria' => $categoria,
    'calificacion' => $calificacion,
    'imagen' => $imagenRuta
  ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - <?= $modoEditar ? 'Editar' : 'Nuevo' ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-box{max-width:820px;margin:20px auto;background:#fff;padding:18px;border-radius:14px;box-shadow:0 4px 10px rgba(0,0,0,.08)}
    label{display:block;margin-top:12px;margin-bottom:6px;font-weight:bold}
    input, textarea{width:100%;padding:10px 14px;border-radius:12px;border:1px solid #ddd;outline:none}
    textarea{min-height:120px;resize:vertical}
    .row{display:flex;gap:12px;flex-wrap:wrap}
    .row > div{flex:1;min-width:220px}
    .error{color:#b00020;margin-top:10px;text-align:center}
    .preview{max-width:240px;margin-top:10px}
    .actions{display:flex;gap:10px;justify-content:center;margin-top:16px;flex-wrap:wrap}
  </style>
</head>
<body>

<header>
  <h1><?= $modoEditar ? 'Editar recortable' : 'Nuevo recortable' ?></h1>
</header>

<main>
  <div class="admin-box">
    <form method="post" enctype="multipart/form-data">
      <label>Nombre *</label>
      <input type="text" name="nombre" value="<?= h($producto['nombre']) ?>" required>

      <label>Descripción</label>
      <textarea name="descripcion"><?= h($producto['descripcion']) ?></textarea>

      <div class="row">
        <div>
          <label>Categoría</label>
          <input type="text" name="categoria" value="<?= h($producto['categoria']) ?>" placeholder="Ej: Robots">
        </div>
        <div>
          <label>Calificación (0-5)</label>
          <input type="number" step="0.1" min="0" max="5" name="calificacion" value="<?= h($producto['calificacion']) ?>">
        </div>
      </div>

      <label>Imagen (subir archivo)</label>
      <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
      <input type="hidden" name="imagen_actual" value="<?= h($producto['imagen']) ?>">

      <?php if (!empty($producto['imagen'])): ?>
        <img class="preview" src="../<?= h(ltrim($producto['imagen'],'/')) ?>" alt="">
      <?php endif; ?>

      <div class="actions">
        <button class="btn" type="submit">Guardar</button>
        <a class="btn secundario" href="index.php">Volver</a>
      </div>

      <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
      <?php endif; ?>
    </form>
  </div>
</main>

</body>
</html>

