<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Recibo de Entrega</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 1em;
      color: #212121
    }

    .receipt {
      width: 300px;
      border: 1px solid #ccc;
      border-radius: 0.5em;
      padding: 10px;
      margin: 0 auto;
    }

    .center {
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="receipt bg-slate-900">
    <h2 class="center">Recibo de Entrega</h2>
    <p><strong>Proveedor:</strong> {{ $entrega->proveedor->nombrecompleto }}</p>
    <p><strong>Fecha:</strong> {{ $entrega->fecha_entrega }}</p>
    <p><strong>Tipo de café:</strong> {{ $entrega->tipo_cafe }}</p>
    <p><strong>Peso bruto:</strong> {{ $entrega->peso_bruto }}</p>
    <p><strong>Quintalaje liquidable:</strong> {{ $entrega->quintalaje_liquidable }} quintales</p>
    <p><strong>Precio compra por quintal:</strong> ${{ number_format($entrega->precio_compra, 2) }}</p>
  </div>
</body>

</html>