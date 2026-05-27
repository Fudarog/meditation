<?php
session_start();
$_SESSION = [];        // Очистка
session_destroy();     // Удаление
setcookie(session_name(), '', time()-3600);  // Cookie
header('Location: ../index.php');
exit;
?>