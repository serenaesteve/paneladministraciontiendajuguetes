<?php
// admin/config.php

declare(strict_types=1);

// Ruta a tu BD
define('DB_PATH', dirname(__DIR__) . '/recortables.db');

// Usuario/clave del admin (cámbialo)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

function db(): SQLite3 {
  $db = new SQLite3(DB_PATH);
  $db->busyTimeout(3000);
  return $db;
}

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
// URL base del proyecto calculada automáticamente
// (sube 1 nivel desde /admin a la carpeta del proyecto)
define('BASE_URL', rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

