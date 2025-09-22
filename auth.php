<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function is_logged_in(){ return isset($_SESSION['user']); }
function current_user(){ return $_SESSION['user'] ?? null; }

function require_login(){
  if (!is_logged_in()) { header('Location: login.php'); exit; }
}

function require_role($roles){
  if (is_string($roles)) $roles = [$roles];
  $user = current_user();
  $role = $user['rol'] ?? null;
  if (!$role || !in_array($role, $roles)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><link rel="stylesheet" href="https://framework-gb.cdn.gob.mx/assets/styles/main.css"></head><body><div class="container"><div class="alert alert-danger">Acceso denegado</div><p><a href="index.php" class="btn btn-default">Volver</a></p></div></body></html>';
    exit;
  }
}
?>