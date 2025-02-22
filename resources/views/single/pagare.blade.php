<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Recibo de Pagare</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 1em;
      color: #212121
    }
  </style>
</head>

<body>
  <div class="receipt bg-slate-900">
    <h2 class="center">PAGARÉ</h2>
    <p><strong>Proveedor:</strong> {{ $prestamo->proveedor->nombrecompleto }}</p>
  </div>
</body>

</html>