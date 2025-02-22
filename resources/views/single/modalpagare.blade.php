<div class="p-4">
  <iframe id="pagareIframe" src="{{ route('single.pagare', ['id' => $record->id]) }}" width="100%"
    height="500px"></iframe>

  <div class="mt-4 flex justify-end">
    <button onclick="verPagare()" class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow">
      Imprimir Pagare
    </button>
  </div>
</div>

<script>
  function verPagare() {
    var iframe = document.getElementById('pagareIframe');
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
  }
</script>