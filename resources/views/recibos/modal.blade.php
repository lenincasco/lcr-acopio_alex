<div class="p-4">
  <iframe id="reciboIframe" src="{{ route('imprimir.recibo', ['id' => $record->id]) }}" width="100%"
    height="500px"></iframe>

  <div class="mt-4 flex justify-end">
    <button onclick="imprimirRecibo()" class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow">
      Imprimir Recibo
    </button>
  </div>
</div>

<script>
  function imprimirRecibo() {
    var iframe = document.getElementById('reciboIframe');
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  }
</script>