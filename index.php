<?php include "db.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>ENGEN - Agencia de Programación</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header>
    <h1>ENGEN</h1>
    <p>Tu página web profesional por solo $100/mes</p>
  </header>

  <section class="section">
    <h2>Suscribite ahora</h2>
    <form action="subscribe.php" method="POST" style="max-width:400px;margin:auto;">
      <input type="text" name="nombre" placeholder="Tu nombre" required style="width:100%;margin:10px 0;padding:10px;">
      <input type="email" name="email" placeholder="Tu email" required style="width:100%;margin:10px 0;padding:10px;">
      <button type="submit">Suscribirme por $100/mes</button>
    </form>
  </section>

  <footer style="text-align:center; padding:2rem; background:#004aad; color:white;">
    <p>© 2025 ENGEN - Agencia de Programación</p>
  </footer>
</body>
</html>