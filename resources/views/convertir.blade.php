<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TUP · Conversor & Promedio</title>
  <!-- Tailwind CDN (sin npm) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: {
      colors: { brand:{600:'#4f46e5',700:'#4338ca'} },
      boxShadow:{ soft:'0 10px 25px rgba(0,0,0,.25)'}
    } } }
  </script>
</head>
<body class="bg-neutral-900 text-neutral-100 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="bg-neutral-800/60 rounded-3xl shadow-soft p-6 md:p-8 backdrop-blur">
      <!-- Encabezado -->
      <div class="flex justify-center mb-6">
        <img src="{{ asset('logo.png') }}" alt="Logo TUP" class="h-16 opacity-90"
             onerror="this.style.display='none'">
      </div>
      <div class="text-center space-y-1 mb-6">
        <h1 class="text-xl font-semibold">Tecnicatura Universitaria en Programación</h1>
        <p class="text-neutral-300">Puede fallar</p>
      </div>

      <!-- Tabs -->
      <div class="grid grid-cols-2 gap-2 mb-6">
        <button id="tabConv" class="rounded-xl py-2 bg-brand-600 hover:bg-brand-700 transition font-medium">Convertir</button>
        <button id="tabAvg"  class="rounded-xl py-2 bg-neutral-700 hover:bg-neutral-600 transition font-medium">Promedio</button>
      </div>

      <!-- FORM CONVERTIR -->
      <section id="panelConv">
        <form id="formConvertir" class="space-y-5">
          <div>
            <label class="block text-sm text-neutral-300 mb-2">Tipo de cotización</label>
            <div class="relative">
              <select id="tipoConv" name="tipo"
                class="w-full appearance-none rounded-xl bg-neutral-900/60 border border-neutral-700 px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-brand-600">
                <option value="oficial" selected>Oficial</option>
                <option value="blue">Blue</option>
                <option value="mep">MEP</option>
                <option value="ccl">CCL</option>
                <option value="tarjeta">Tarjeta</option>
              </select>
              <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">▾</span>
            </div>
          </div>

          <div>
            <label for="monto" class="block text-sm text-neutral-300 mb-2">Monto en USD</label>
            <div class="flex items-center gap-2 rounded-xl bg-neutral-900/60 border border-neutral-700 px-4">
              <span class="text-neutral-400">$</span>
              <input id="monto" name="monto" type="number" step="0.01" min="0.01" placeholder="Ej: 120.50"
                class="w-full bg-transparent py-3 focus:outline-none" required>
            </div>
            <p class="text-xs text-neutral-400 mt-1">Usa punto (.) para decimales.</p>
          </div>

          <!-- 🔹 NUEVO: check para usar API en vivo -->
          <label class="flex items-center gap-3 text-sm text-neutral-300 select-none cursor-pointer">
            <input id="live" type="checkbox"
              class="h-4 w-4 rounded bg-neutral-900 border-neutral-700 focus:ring-2 focus:ring-brand-600">
            <span>Usar cotización en vivo (API) y guardar hoy en BD</span>
          </label>

          <button id="btnSubmitConv" type="submit"
            class="w-full rounded-2xl bg-brand-600 hover:bg-brand-700 transition py-3 font-medium">
            Convertir a ARS
          </button>
        </form>

        <div id="resultadoConv" class="hidden mt-6 rounded-2xl border border-neutral-700 bg-neutral-900/60 p-4">
          <div class="text-sm text-neutral-300">Resultado</div>
          <div class="mt-2">
            <div class="text-2xl font-semibold" id="txtTotalConv">—</div>
            <div class="text-xs text-neutral-400 mt-1" id="txtDetalleConv">—</div>
          </div>
        </div>

        <div id="mensajeConv" class="hidden mt-4 text-sm text-red-400"></div>
      </section>

      <!-- FORM PROMEDIO -->
      <section id="panelAvg" class="hidden">
        <form id="formPromedio" class="space-y-5">
          <div>
            <label class="block text-sm text-neutral-300 mb-2">Tipo de cotización</label>
            <div class="relative">
              <select id="tipoAvg" name="tipo"
                class="w-full appearance-none rounded-xl bg-neutral-900/60 border border-neutral-700 px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-brand-600">
                <option value="oficial" selected>Oficial</option>
                <option value="blue">Blue</option>
                <option value="mep">MEP</option>
                <option value="ccl">CCL</option>
                <option value="tarjeta">Tarjeta</option>
              </select>
              <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">▾</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm text-neutral-300 mb-2">Desde</label>
              <input id="desde" name="desde" type="date"
                class="w-full rounded-xl bg-neutral-900/60 border border-neutral-700 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-600" required>
            </div>
            <div>
              <label class="block text-sm text-neutral-300 mb-2">Hasta</label>
              <input id="hasta" name="hasta" type="date"
                class="w-full rounded-xl bg-neutral-900/60 border border-neutral-700 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-600" required>
            </div>
          </div>

          <button id="btnSubmitAvg" type="submit"
            class="w-full rounded-2xl bg-brand-600 hover:bg-brand-700 transition py-3 font-medium">
            Calcular promedio
          </button>
        </form>

        <div id="resultadoAvg" class="hidden mt-6 rounded-2xl border border-neutral-700 bg-neutral-900/60 p-4">
          <div class="text-sm text-neutral-300">Promedio</div>
          <div class="mt-2">
            <div class="text-2xl font-semibold" id="txtPromedio">—</div>
            <div class="text-xs text-neutral-400 mt-1" id="txtDetalleAvg">—</div>
          </div>
        </div>

        <div id="mensajeAvg" class="hidden mt-4 text-sm text-red-400"></div>
      </section>

      <div class="mt-6 text-center text-xs text-neutral-500">
        Demo simple · Llama a <code>/api/convertir</code> y <code>/api/cotizaciones/promedio</code>
      </div>
    </div>
  </div>

  <script>
    // Helpers
    const $ = (s)=>document.querySelector(s);

    // Tabs
    const tabConv = $('#tabConv'), tabAvg = $('#tabAvg');
    const panelConv = $('#panelConv'), panelAvg = $('#panelAvg');
    function activar(tab) {
      const on = (b)=>{ b.classList.remove('bg-neutral-700'); b.classList.add('bg-brand-600'); };
      const off = (b)=>{ b.classList.remove('bg-brand-600'); b.classList.add('bg-neutral-700'); };
      if (tab === 'conv') { on(tabConv); off(tabAvg); panelConv.classList.remove('hidden'); panelAvg.classList.add('hidden'); }
      else { on(tabAvg); off(tabConv); panelAvg.classList.remove('hidden'); panelConv.classList.add('hidden'); }
    }
    tabConv.addEventListener('click', ()=>activar('conv'));
    tabAvg.addEventListener('click', ()=>activar('avg'));

    // Conversión
    const formConv = $('#formConvertir');
    const btnConv  = $('#btnSubmitConv');
    const resConv  = $('#resultadoConv');
    const msgConv  = $('#mensajeConv');
    const txtTotalConv = $('#txtTotalConv');
    const txtDetalleConv = $('#txtDetalleConv');
    const liveChk = $('#live'); // <- nuevo

    formConv.addEventListener('submit', async (e) => {
      e.preventDefault();
      msgConv.classList.add('hidden'); msgConv.textContent = '';
      resConv.classList.add('hidden');

      const tipo = $('#tipoConv').value;
      const monto = $('#monto').value;
      if (!monto || parseFloat(monto) <= 0) {
        msgConv.textContent = 'Ingresá un monto válido en USD.';
        msgConv.classList.remove('hidden'); return;
      }

      btnConv.disabled = true; btnConv.textContent = 'Convirtiendo...';
      try {
        const live = liveChk && liveChk.checked ? '&live=1' : '';
        const url = `/api/convertir?tipo=${encodeURIComponent(tipo)}&monto=${encodeURIComponent(monto)}${live}`;
        const r = await fetch(url);
        const d = await r.json();
        if (!r.ok) throw new Error(d?.message || 'Error al convertir.');

        const ARS = (n)=>new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS'}).format(n);
        txtTotalConv.textContent = ARS(d.total_ars);
        // si el backend devuelve "source", lo mostramos. Si no, lo omitimos.
        const fuente = d.source ? ` · Fuente: ${d.source}` : '';
        txtDetalleConv.textContent = `Tipo: ${d.tipo} · Valor USD: ${d.valor_usd} · Fecha: ${d.fecha}${fuente}`;
        resConv.classList.remove('hidden');
      } catch (err) {
        msgConv.textContent = err.message ?? 'Error inesperado.';
        msgConv.classList.remove('hidden');
      } finally {
        btnConv.disabled = false; btnConv.textContent = 'Convertir a ARS';
      }
    });

    // Promedio
    const formAvg = $('#formPromedio');
    const btnAvg  = $('#btnSubmitAvg');
    const resAvg  = $('#resultadoAvg');
    const msgAvg  = $('#mensajeAvg');
    const txtProm = $('#txtPromedio');
    const txtDetAvg = $('#txtDetalleAvg');

    formAvg.addEventListener('submit', async (e) => {
      e.preventDefault();
      msgAvg.classList.add('hidden'); msgAvg.textContent = '';
      resAvg.classList.add('hidden');

      const tipo = $('#tipoAvg').value;
      const desde = $('#desde').value;
      const hasta = $('#hasta').value;

      if (!desde || !hasta) {
        msgAvg.textContent = 'Completá las fechas.';
        msgAvg.classList.remove('hidden'); return;
      }

      btnAvg.disabled = true; btnAvg.textContent = 'Calculando...';
      try {
        const url = `/api/cotizaciones/promedio?tipo=${encodeURIComponent(tipo)}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}`;
        const r = await fetch(url);
        const d = await r.json();
        if (!r.ok) throw new Error(d?.message || 'Error al calcular promedio.');

        const num = (n)=> (n==null? '—' : new Intl.NumberFormat('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(n));
        txtProm.textContent = (d.promedio==null) ? '—' : `ARS ${num(d.promedio)} por USD`;
        txtDetAvg.textContent = `Tipo: ${d.tipo} · Registros: ${d.cantidad} · Rango: ${d.desde} → ${d.hasta}`;
        resAvg.classList.remove('hidden');
      } catch (err) {
        msgAvg.textContent = err.message ?? 'Error inesperado.';
        msgAvg.classList.remove('hidden');
      } finally {
        btnAvg.disabled = false; btnAvg.textContent = 'Calcular promedio';
      }
    });

    // Tab por defecto
    activar('conv');
  </script>
</body>
</html>
