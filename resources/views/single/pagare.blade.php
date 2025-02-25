<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Pagaré de Préstamo</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 13px;
      color: #212121;
      line-height: 1.8;
      margin: 20px;
    }

    .container {
      /*max-width: 780px;*/
      max-width: 1280px;
      margin: 0 auto;
      /* padding: 150px 0; */
    }

    .header {
      text-align: center;
    }

    .header .title,
    .header p {
      line-height: 0.25;
    }

    .header .subtitle {
      font-size: 1rem;
    }

    .text-center {
      text-align: center;
    }

    .underline {
      border-bottom: 1px solid #000;
      display: inline-block;
      font-weight: bold;
      padding: 0 10px;
      text-transform: uppercase;
    }

    .signatures {
      margin-top: 40px;
      display: flex;
      justify-content: space-between;
    }

    .signatures {
      margin-top: 100px;
      line-height: 0.25;
    }

    ul li {
      list-style-type: decimal;
    }
  </style>
  <?php
function numeroATexto($numero, $moneda = 'córdobas')
{
  $formatter = new NumberFormatter('es', NumberFormatter::SPELLOUT);

  // Convertimos el número a formato con dos decimales
  $partes = explode('.', number_format($numero, 2, '.', ''));

  $parteEntera = (int) $partes[0];
  $parteDecimal = (int) $partes[1];

  // Convertimos la parte entera a texto
  $textoEntero = ucfirst($formatter->format($parteEntera));

  // Convertimos la parte decimal a texto
  if ($parteDecimal > 0) {
    $textoDecimal = $formatter->format($parteDecimal);
    return "{$textoEntero} {$moneda} con {$textoDecimal} centavos";
  }

  return "{$textoEntero} {$moneda}";
}
?>
</head>

<body>
  <div class="container">
    <!-- Encabezado -->
    <div class="header">
      <h1 class="title">COMPRA DE CAFÉ HERRERA BELLORÍN</h1>
      <p class="subtitle"><b>Prop. José Noel Herrera B.</b></p>
      <p><b>Dirección:</b> San Juan del Río Coco, Zona no. 2, <b>Cel. Claro:</b> 8636 6758 /
        <b>Cel. Tigo:</b> 7746 8363
      </p>
      <p><b>RUC No.</b> 1621709740002N - Madriz, Nicaragua</p>
    </div>

    <!-- Título del documento -->
    <div class="text-center">
      <h3 class="h3">PAGARÉ A LA ORDEN
        <span style="margin-left: 1rem; color: tomato;">N.º: <?php echo $prestamo->numero ?? '11955'; ?>
        </span>
      </h3>
    </div>

    <!-- Datos de vencimiento y montos -->
    <div>
      <p>
        <b>Fecha de Vencimiento:</b>
        <span class="underline"><?php echo date('d/m/Y', strtotime($prestamo->fecha_vencimiento)); ?></span>
      </p>
      <p>
        <span>C$:</span>
        <span class="underline"><?php echo number_format($prestamo->monto_total, 2); ?></span>
        <!-- Espacio extra para completar el campo visualmente -->
        <span class="underline"><?php echo numeroATexto($prestamo->monto_total, 'córdobas'); ?> </span>
      </p>
      <p>
        <span>US$:</span>
        <span class="underline"><?php echo number_format($prestamo->monto_total / $prestamo->tipo_cambio, 2); ?></span>
        <span class="underline"><?php echo numeroATexto($prestamo->monto_total / $prestamo->tipo_cambio, 'dólares'); ?>
        </span>
      </p>
    </div>

    <!-- Datos del deudor -->
    <div>
      <p>
        <span>Yo:</span>
        <span class="underline"><?php echo $prestamo->proveedor->nombrecompleto; ?></span>
        <!-- Si el nombre completo lo separas en dos partes, puedes ajustar aquí -->
        , mayor de edad, cédula de identidad número
        <span class="underline"><?php echo $prestamo->proveedor->cedula ?? 'SIN ESPECIFICAR'; ?></span>,
        y del domicilio de
        <span class="underline"><?php echo $prestamo->proveedor->direccion ?? 'SIN ESPECIFICAR'; ?></span>,
        jurisdicción de
        <span class="underline"><?php echo $prestamo->proveedor->ciudad ?? 'SIN ESPECIFICAR'; ?></span>
      </p>
    </div>

    <!-- Cláusula principal del pagaré -->
    <div>
      <p>
        Por el presente <b>PAGARÉ A LA ORDEN, pagaré a: JOSÉ NOEL HERRERA BELLORÍN,</b> mayor de edad,
        soltero, Agricultor,
        <b>Cédula de Identidad Número: 162-170974-0002N</b> y del domicilio de San Juan de Río Coco, a su orden. En esta
        ciudad
        de San Juan de Río Coco, en su casa de habitación, por mi cuenta y riesgo y por igual valor recibido en calidad
        de Compra de Café pergamino de buena calidad de futuro, la cantidad de:
        <span class="underline"><?php echo number_format($prestamo->monto_total, 2); ?></span>
        &nbsp;&nbsp; el día
        <span class="underline"><?php echo date('d/m/Y'); ?></span>
      </p>
    </div>

    <!-- Cláusulas del pagaré -->
    <div>
      <p>
        El presente <b>PAGARÉ A LA ORDEN</b>, será regido por las siguientes cláusulas:
      <ul>
        <li>En caso de faltar al pago en la fecha señalada, incurriré en mora por el solo hecho de incumplimiento, sin
          la
          necesidad de intimidación o requerimiento alguno judicial o extrajudicial y desde esa fecha hasta la solución
          efectiva de la deuda, reconoceré a mi acreedor un interés moratorio del
          <span class="underline"><?php echo $prestamo->interes_moratorio ?? 'SIN ESPECIFICAR'; ?></span> por ciento.
        </li>
        <li>
          Me comprometo también a pagar a mi acreedor, además del interés moratorio, los gastos que incurra por cobranza
          judicial o extrajudicial.
        </li>
        <li>
          Expresamente renuncio a mi domicilio que tengo establecido, sujetándome al que elija mi acreedor o sucesor de
          este y a las excepciones de caso fortuito o fuerza mayor en relación con el cumplimiento de lo aquí
          estipulado,
          cuyos riesgos asumo.
        </li>
        <li>
          En caso de ejecución, consiento que sea designado por mi acreedor el depositario de los bienes que se
          embarguen
          y para efecto de subasta, desde ahora admito que tenga plena aplicación de los artos 1777 y 1778, aun cuando
          sus
          efectos estuvieran suspendidos por la ley.
        </li>
        <li>
          Reconoceré también a mi acreedor un interés corriente del
          <span class="underline"><?php echo $prestamo->interes ?? 'SIN ESPECIFICAR'; ?></span> por ciento
          mensual,
          equivalente a
          <span class="underline"><?php echo $prestamo->intereses ?? 'SIN ESPECIFICAR'; ?></span> anual.
        </li>
        <li>
          <!-- Constitución de fiador -->
          <div>
            <p>
              <b>CONSTITUCIÓN DE FIADOR:</b> Yo,
              <span class="underline"><?php echo $prestamo->proveedor->nombrecompleto ?? 'SIN ESPECIFICAR'; ?></span>,
              mayor de edad, Cédula Número:
              <span class="underline"><?php echo $prestamo->proveedor->cedula ?? 'SIN ESPECIFICAR'; ?></span>,
              me constituyo fiador solidario y principal pagador de la deuda del Señor
              <span class="underline"><?php echo $prestamo->acreedor ?? 'JOSÉ NOEL HERRERA BELLORÍN'; ?></span>,
              para lo que me comprometo a pagar la totalidad de la deuda o lo que se deba al momento del pago de la
              deuda.
            </p>
          </div>
        </li>
      </ul>

      </b>
    </div>



    <!-- Ubicación -->
    <p class="text-center">
      San Juan de Río Coco, <span class="underline"><?php echo date('d/m/Y'); ?></span>
    </p>

    <!-- Firmas -->
    <!-- Firmas -->
    <table style="width: 100%; margin-top: 80px; text-align: center;">
      <tr>
        <td style="width: 50%;">
          <span>______________________________</span>
          <p>Deudor</p>
        </td>
        <td style="width: 50%;">
          <span>______________________________</span>
          <p>Fiador</p>
        </td>
      </tr>
    </table>
  </div>
</body>

</html>