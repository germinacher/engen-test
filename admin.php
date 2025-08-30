<?php
include "db.php";
$result = $conn->query("SELECT * FROM clientes");
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Panel ENGEN</title></head>
<body>
  <h1>Clientes Suscriptos</h1>
  <table border="1" cellpadding="8">
    <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Estado</th><th>Fecha</th></tr>
    <?php while($row = $result->fetch_assoc()) { ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['nombre'] ?></td>
        <td><?= $row['email'] ?></td>
        <td><?= $row['estado'] ?></td>
        <td><?= $row['fecha_alta'] ?></td>
      </tr>
    <?php } ?>
  </table>
</body>
</html>