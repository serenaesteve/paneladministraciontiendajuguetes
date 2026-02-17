<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['user'] ?? '');
  $pass = trim($_POST['pass'] ?? '');

  if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
    $_SESSION['admin_ok'] = true;
    header('Location: index.php');
    exit;
  } else {
    $error = 'Usuario o contraseña incorrectos';
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Login</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-box{max-width:420px;margin:40px auto;background:#fff;padding:18px;border-radius:14px;box-shadow:0 4px 10px rgba(0,0,0,.1)}
    .admin-box label{display:block;margin:10px 0 6px}
    .admin-box input{width:100%;padding:10px 14px;border-radius:20px;border:1px solid #ddd;outline:none}
    .error{color:#b00020;margin-top:10px;text-align:center}
  </style>
</head>
<body>

<header>
  <h1>Panel de Administración</h1>
</header>

<main>
  <div class="admin-box">
    <h3 style="text-align:center;margin-bottom:10px;">Iniciar sesión</h3>

    <form method="post">
      <label>Usuario</label>
      <input type="text" name="user" required>

      <label>Contraseña</label>
      <input type="password" name="pass" required>

      <div style="text-align:center;margin-top:14px;">
        <button class="btn" type="submit">Entrar</button>
      </div>

      <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
      <?php endif; ?>
    </form>
  </div>
</main>

</body>
</html>

