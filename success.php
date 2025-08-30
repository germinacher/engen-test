<?php
include "db.php";
$id = $_GET['id'] ?? 0;
if ($id > 0) {
    $conn->query("UPDATE clientes SET estado='activo' WHERE id=$id");
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Gracias</title></head>
<body>
  <h1>¡Gracias por suscribirte a ENGEN! ✅</h1>
  <p>Tu suscripción se procesará automáticamente cada mes.</p>
</body>
</html>