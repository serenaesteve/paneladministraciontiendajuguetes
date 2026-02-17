<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: index.php');
  exit;
}

$db = db();
$st = $db->prepare("DELETE FROM productos WHERE id = :id");
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$st->execute();

header('Location: index.php');
exit;

