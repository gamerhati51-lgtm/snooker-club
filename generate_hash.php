<?php
// generate_hash.php
$password = "admin123";  // your password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
