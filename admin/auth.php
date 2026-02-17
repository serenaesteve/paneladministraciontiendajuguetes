<?php
// admin/auth.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config.php';

function require_admin(): void {
  if (empty($_SESSION['admin_ok'])) {
    header('Location: login.php');
    exit;
  }
}

