<template>
  <q-page class="q-pa-sm dashboard">

    <!-- Sin permiso de panel: solo la bienvenida y el acceso a ventas -->
    <div v-if="!canDashboard" class="column items-center justify-center q-gutter-md"
         style="min-height:78vh">
      <img src="/logo.png" alt="Clínica URME" class="bienvenida__logo" />
      <div class="text-h6 text-weight-bold text-center">Clínica URME</div>
      <div class="text-body2 text-grey-6 text-center">
        Bienvenido, {{ proxy.$store.user.name || proxy.$store.user.username }}
      </div>
      <q-btn unelevated no-caps rounded size="lg" color="primary" icon="point_of_sale"
             label="Ir a ventas" to="/ventas" />
    </div>

    <template v-else>

    <!-- ── Encabezado ────────────────────────────────────────────── -->
    <div class="row items-end q-col-gutter-sm q-mb-sm">
      <div class="col">
        <div class="text-h6 text-weight-bold">Panel general</div>
        <div class="text-caption text-grey-6">
          Bienvenido, {{ proxy.$store.user.name || proxy.$store.user.username }}
          <span v-if="data.rango.desde"> · {{ fechaCorta(data.rango.desde) }} al {{ fechaCorta(data.rango.hasta) }}</span>
        </div>
      </div>
      <div class="col-auto">
        <q-btn-toggle
          v-model="dias"
          dense unelevated no-caps
          toggle-color="primary"
          color="white"
          text-color="grey-7"
          class="dashboard__rango"
          :options="[
            { label: '7 días', value: 7 },
            { label: '30 días', value: 30 },
            { label: '90 días', value: 90 },
            { label: '1 año', value: 365 }
          ]"
          @update:model-value="cargar"
        />
      </div>
      <div class="col-auto">
        <q-btn flat dense round icon="refresh" color="grey-7" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>
      <div class="col-auto">
        <q-btn unelevated no-caps rounded color="primary" icon="point_of_sale"
               label="Ir a ventas" to="/ventas" />
      </div>
    </div>

    <!-- Sin módulos habilitados -->
    <div v-if="!loading && !hayModulos" class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="insights" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin indicadores disponibles</div>
      <div class="text-body2 text-grey-6">Su usuario aún no tiene permisos sobre los módulos del panel</div>
    </div>

    <!-- ── Indicadores ───────────────────────────────────────────── -->
    <div class="row q-col-gutter-xs q-mb-sm">
      <div v-if="permisos.ventas" class="col-6 col-md-3">
        <q-card flat class="kpi kpi--brand full-height">
          <div class="kpi__label">Ventas de hoy</div>
          <div class="kpi__value">{{ money(resumen.ventas_hoy) }} <span class="kpi__unit">Bs</span></div>
          <div class="kpi__foot">{{ resumen.ventas_hoy_cantidad || 0 }} venta(s) registradas</div>
        </q-card>
      </div>

      <div v-if="permisos.ventas" class="col-6 col-md-3">
        <q-card flat bordered class="kpi full-height">
          <div class="kpi__label">Ventas del mes</div>
          <div class="kpi__value">{{ money(resumen.ventas_mes) }} <span class="kpi__unit">Bs</span></div>
          <div class="kpi__foot row items-center no-wrap">
            <template v-if="resumen.ventas_variacion !== null && resumen.ventas_variacion !== undefined">
              <q-icon
                :name="resumen.ventas_variacion >= 0 ? 'arrow_upward' : 'arrow_downward'"
                size="13px"
                :class="resumen.ventas_variacion >= 0 ? 'kpi__delta--up' : 'kpi__delta--down'"
              />
              <span :class="resumen.ventas_variacion >= 0 ? 'kpi__delta--up' : 'kpi__delta--down'" class="q-mr-xs">
                {{ Math.abs(resumen.ventas_variacion) }}%
              </span>
              <span>vs. mes anterior</span>
            </template>
            <span v-else>{{ resumen.ventas_mes_cantidad || 0 }} venta(s) en el mes</span>
          </div>
        </q-card>
      </div>

      <div v-if="permisos.ventas" class="col-6 col-md-3">
        <q-card flat bordered class="kpi full-height">
          <div class="kpi__label">Ticket promedio</div>
          <div class="kpi__value">{{ money(resumen.ticket_promedio) }} <span class="kpi__unit">Bs</span></div>
          <div class="kpi__foot">Sobre {{ resumen.ventas_mes_cantidad || 0 }} venta(s) del mes</div>
        </q-card>
      </div>

      <div v-if="permisos.productos" class="col-6 col-md-3">
        <q-card flat bordered class="kpi full-height">
          <div class="kpi__label">Stock valorizado</div>
          <div class="kpi__value">{{ money(resumen.stock_valorizado) }} <span class="kpi__unit">Bs</span></div>
          <div class="kpi__foot">{{ resumen.productos || 0 }} productos en catálogo</div>
        </q-card>
      </div>
    </div>

    <!-- ── Chips secundarios ─────────────────────────────────────── -->
    <div class="row q-col-gutter-xs q-mb-sm">
      <div v-for="chip in chips" :key="chip.label" class="col-6 col-sm-4 col-md-2">
        <q-card flat bordered class="mini full-height" :class="chip.alerta ? 'mini--alerta' : ''"
                v-ripple @click="chip.to && router.push(chip.to)" style="cursor:pointer">
          <q-icon :name="chip.icon" size="18px" :color="chip.alerta ? 'negative' : 'primary'" />
          <div class="mini__body">
            <div class="mini__value">{{ chip.value }}</div>
            <div class="mini__label">{{ chip.label }}</div>
          </div>
        </q-card>
      </div>
    </div>

    <!-- ── Ventas vs compras por día ─────────────────────────────── -->
    <div v-if="permisos.ventas || permisos.compras" class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Movimiento diario</div>
              <div class="panel__sub">Ingresos por ventas y egresos por compras, últimos {{ data.rango.dias }} días</div>
            </div>
            <div class="panel__total">
              {{ money(totalSerieVentas) }} <span class="kpi__unit">Bs vendidos</span>
            </div>
          </div>
          <q-inner-loading :showing="loading" />
          <apexchart v-if="!loading" type="area" height="270" :options="opcionesDiario" :series="serieDiaria" />
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <!-- Evolución mensual -->
      <div v-if="permisos.ventas || permisos.compras" class="col-12 col-md-8">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Evolución mensual</div>
              <div class="panel__sub">Comparativo de ventas y compras de los últimos 12 meses</div>
            </div>
          </div>
          <apexchart type="bar" height="280" :options="opcionesMensual" :series="serieMensual" />
        </q-card>
      </div>

      <!-- Tipo de pago -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Forma de pago</div>
              <div class="panel__sub">Distribución del monto vendido</div>
            </div>
          </div>
          <apexchart v-if="data.tipo_pago.length" type="donut" height="280"
                     :options="opcionesTipoPago" :series="data.tipo_pago.map(f => f.total)" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <!-- Top productos -->
      <div v-if="permisos.ventas" class="col-12 col-md-7">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Productos y servicios más vendidos</div>
              <div class="panel__sub">Monto facturado en los últimos {{ data.rango.dias }} días</div>
            </div>
          </div>
          <apexchart v-if="data.top_productos.length" type="bar"
                     :height="Math.max(240, data.top_productos.length * 34 + 60)"
                     :options="opcionesTopProductos" :series="serieTopProductos" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Ventas por categoría -->
      <div v-if="permisos.ventas" class="col-12 col-md-5">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Ventas por categoría</div>
              <div class="panel__sub">Farmacia, servicios y estudios</div>
            </div>
          </div>
          <apexchart v-if="data.por_tipo_producto.length" type="donut" height="330"
                     :options="opcionesTipoProducto" :series="data.por_tipo_producto.map(f => f.total)" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <!-- Actividad por hora -->
      <div v-if="permisos.ventas" class="col-12 col-md-5">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Actividad por hora</div>
              <div class="panel__sub">Cantidad de ventas según hora de atención</div>
            </div>
          </div>
          <apexchart type="bar" height="250" :options="opcionesHora" :series="serieHora" />
        </q-card>
      </div>

      <!-- Vendedores -->
      <div v-if="permisos.ventas" class="col-12 col-md-4">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Ventas por usuario</div>
              <div class="panel__sub">Monto atendido por cada operador</div>
            </div>
          </div>
          <apexchart v-if="data.top_vendedores.length" type="bar" height="250"
                     :options="opcionesVendedores" :series="serieVendedores" />
          <div v-else class="vacio">Sin ventas en el período</div>
        </q-card>
      </div>

      <!-- Solicitudes de laboratorio -->
      <div v-if="permisos.laboratorio" class="col-12 col-md-3">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Laboratorio</div>
              <div class="panel__sub">Solicitudes por estado</div>
            </div>
          </div>
          <apexchart v-if="data.solicitudes_estado.length" type="bar" height="250"
                     :options="opcionesSolicitudes" :series="serieSolicitudes" />
          <div v-else class="vacio">Sin solicitudes en el período</div>
        </q-card>
      </div>
    </div>

    <div v-if="permisos.productos" class="row q-col-gutter-sm">
      <!-- Vencimientos -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Lotes por vencer</div>
              <div class="panel__sub">Lotes de farmacia con existencia, agrupados por urgencia</div>
            </div>
          </div>
          <apexchart type="bar" height="250" :options="opcionesVencimientos" :series="serieVencimientos" />
        </q-card>
      </div>

      <!-- Stock crítico -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="panel">
          <div class="panel__head">
            <div>
              <div class="panel__title">Stock crítico</div>
              <div class="panel__sub">Productos con 10 unidades o menos en almacén</div>
            </div>
          </div>
          <q-list separator dense class="q-mt-xs">
            <q-item v-for="p in data.stock_critico" :key="p.producto_id">
              <q-item-section avatar style="min-width:32px">
                <q-icon name="inventory_2" size="18px" color="orange-8" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-body2">{{ p.nombre }}</q-item-label>
                <q-item-label caption>{{ p.codigo }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge :color="p.existencia <= 3 ? 'negative' : 'orange-8'" class="text-weight-bold">
                  {{ Number(p.existencia) }}
                </q-badge>
              </q-item-section>
            </q-item>
            <q-item v-if="!data.stock_critico.length">
              <q-item-section class="text-grey-6 text-body2">
                Ningún producto en nivel crítico
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>
    </div>

    </template>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const { proxy } = getCurrentInstance()
const router = useRouter()

/* ── Paleta validada (categórica, orden fijo — nunca ciclada) ──────
   Verificada con el validador de paleta sobre superficie #ffffff.
   SERIES se usa en gráficos donde solo importan los pares contiguos
   (líneas, barras). DONA es el subconjunto que además supera la
   validación con todos los pares, porque una dona se ordena por tamaño
   y cualquier porción puede terminar junto a cualquier otra. */
const SERIES = ['#1baf7a', '#eb6834', '#2a78d6', '#eda100', '#e87ba4', '#4a3aa7', '#008300', '#e34948']
const DONA = ['#1baf7a', '#eb6834', '#2a78d6', '#4a3aa7']
const ESTADO = { bueno: '#0ca30c', alerta: '#fab219', serio: '#ec835a', critico: '#d03b3b' }
const TINTA = { primaria: '#0b0b0b', secundaria: '#52514e', tenue: '#898781', grilla: '#e1e0d9', eje: '#c3c2b7' }
const FUENTE = 'system-ui, -apple-system, "Segoe UI", sans-serif'

const loading = ref(false)
const dias = ref(30)
const data = ref({
  rango: { dias: 30, desde: '', hasta: '' },
  permisos: {},
  resumen: {},
  serie_dias: [],
  serie_meses: [],
  tipo_pago: [],
  top_productos: [],
  por_tipo_producto: [],
  top_vendedores: [],
  ventas_por_hora: [],
  solicitudes_estado: [],
  vencimientos: [],
  stock_critico: [],
})

// Sin 'Ver Dashboard' la página sigue siendo accesible, pero solo muestra
// la bienvenida con el acceso a ventas — no se consulta el endpoint.
const canDashboard = computed(() => proxy.$store.hasPermission('Ver Dashboard'))

const resumen = computed(() => data.value.resumen || {})
const permisos = computed(() => data.value.permisos || {})
const hayModulos = computed(() => Object.values(permisos.value).some(Boolean))

async function cargar () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('dashboard', { params: { dias: dias.value } })
    data.value = res.data
  } catch (e) {
    proxy.$alert.error('No se pudo cargar el panel: ' + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

// Los permisos pueden llegar después del montaje (respuesta de /me),
// por eso se espera a que estén resueltos antes de pedir los datos.
let cargado = false
watch(canDashboard, (puede) => {
  if (puede && !cargado) { cargado = true; cargar() }
}, { immediate: true })

/* ── Formatos ─────────────────────────────────────────────────── */
function money (v) {
  return Number(v || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function moneyCorto (v) {
  const n = Number(v || 0)
  if (Math.abs(n) >= 1000000) return (n / 1000000).toFixed(1) + ' M'
  if (Math.abs(n) >= 1000) return (n / 1000).toFixed(1) + ' k'
  return n.toFixed(0)
}
function fechaCorta (iso) {
  if (!iso) return ''
  const [a, m, d] = iso.split('-')
  return `${d}/${m}/${a}`
}

/* El color sigue a la entidad, nunca a su posición. Cada grupo mantiene un
   registro de ranura por clave: la primera carga lo siembra en orden
   alfabético y las claves nuevas se agregan al final, de modo que cambiar
   el rango de fechas jamás repinta las categorías que ya estaban. */
const registrosColor = new Map()

function coloresEstables (grupo, claves, paleta = DONA) {
  if (!registrosColor.has(grupo)) registrosColor.set(grupo, new Map())
  const registro = registrosColor.get(grupo)

  const visibles = [...new Set(claves)]
  const entidades = visibles.filter(c => c !== 'OTROS').sort()
  for (const clave of entidades) {
    if (!registro.has(clave)) registro.set(clave, registro.size)
  }

  /* Cada clave conserva su ranura mientras esté libre; si el registro creció
     más que la paleta se toma la primera ranura disponible, para que dos
     categorías en pantalla nunca compartan color. OTROS ocupa siempre la
     última ranura: es un residuo, no una entidad. */
  const asignado = new Map()
  const usadas = new Set()
  if (visibles.includes('OTROS')) {
    asignado.set('OTROS', paleta.length - 1)
    usadas.add(paleta.length - 1)
  }
  for (const clave of entidades) {
    const preferida = registro.get(clave) % paleta.length
    let ranura = usadas.has(preferida) ? paleta.findIndex((_, i) => !usadas.has(i)) : preferida
    if (ranura < 0) ranura = preferida
    asignado.set(clave, ranura)
    usadas.add(ranura)
  }

  return claves.map(c => paleta[asignado.get(c)])
}

/* ── Chips secundarios ────────────────────────────────────────── */
const chips = computed(() => {
  const r = resumen.value
  const p = permisos.value
  const lista = []
  if (p.ventas) lista.push({ label: 'Por cobrar', value: money(r.ventas_pendientes) + ' Bs', icon: 'pending_actions', to: '/ventas', alerta: Number(r.ventas_pendientes) > 0 })
  if (p.productos) lista.push({ label: 'Productos', value: r.productos ?? 0, icon: 'inventory_2', to: '/farmacia' })
  if (p.pacientes) lista.push({ label: 'Pacientes', value: r.pacientes ?? 0, icon: 'groups', to: '/pacientes' })
  if (p.internaciones) lista.push({ label: 'Internados', value: r.internados ?? 0, icon: 'king_bed', to: '/pacientes' })
  if (p.laboratorio) lista.push({ label: 'Lab. pendientes', value: r.solicitudes_pendientes ?? 0, icon: 'biotech', to: '/solicitudes-laboratorio' })
  if (p.productos) lista.push({ label: 'Por vencer', value: r.por_vencer ?? 0, icon: 'schedule', to: '/productos-por-vencer', alerta: Number(r.por_vencer) > 0 })
  if (p.productos) lista.push({ label: 'Vencidos', value: r.vencidos ?? 0, icon: 'dangerous', to: '/productos-vencidos', alerta: Number(r.vencidos) > 0 })
  if (p.compras) lista.push({ label: 'Compras del mes', value: money(r.compras_mes) + ' Bs', icon: 'local_shipping', to: '/compras' })
  return lista
})

/* ── Base común de los gráficos ───────────────────────────────── */
const base = {
  chart: {
    fontFamily: FUENTE,
    toolbar: { show: false },
    zoom: { enabled: false },
    animations: { easing: 'easeout', speed: 350 },
  },
  grid: {
    borderColor: TINTA.grilla,
    strokeDashArray: 0,
    padding: { left: 8, right: 12, top: 0 },
    xaxis: { lines: { show: false } },
  },
  dataLabels: { enabled: false },
  legend: {
    position: 'bottom',
    horizontalAlign: 'left',
    fontSize: '12px',
    fontFamily: FUENTE,
    labels: { colors: TINTA.secundaria },
    markers: { size: 5, offsetX: -3 },
    itemMargin: { horizontal: 10, vertical: 2 },
  },
  tooltip: { style: { fontFamily: FUENTE, fontSize: '12px' } },
  states: { active: { filter: { type: 'none' } } },
}

const ejeY = {
  labels: { style: { colors: TINTA.tenue, fontSize: '11px', fontFamily: FUENTE }, formatter: v => moneyCorto(v) },
  axisBorder: { show: false },
  axisTicks: { show: false },
}
const ejeX = {
  labels: { style: { colors: TINTA.tenue, fontSize: '11px', fontFamily: FUENTE } },
  axisBorder: { color: TINTA.eje },
  axisTicks: { show: false },
  crosshairs: { stroke: { color: TINTA.eje, width: 1, dashArray: 3 } },
}

/* ── 1. Movimiento diario (área) ──────────────────────────────── */
const totalSerieVentas = computed(() =>
  data.value.serie_dias.reduce((suma, f) => suma + Number(f.ventas || 0), 0))

const serieDiaria = computed(() => {
  const series = []
  if (permisos.value.ventas) {
    series.push({ name: 'Ventas', data: data.value.serie_dias.map(f => Number(f.ventas)) })
  }
  if (permisos.value.compras) {
    series.push({ name: 'Compras', data: data.value.serie_dias.map(f => Number(f.compras)) })
  }
  return series
})

const opcionesDiario = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'area' },
  colors: [SERIES[0], SERIES[1]],
  stroke: { width: 2, curve: 'straight' },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 0, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 100] },
  },
  markers: { size: 0, strokeWidth: 2, strokeColors: '#ffffff', hover: { size: 5 } },
  legend: { ...base.legend, show: serieDiaria.value.length > 1 },
  xaxis: {
    ...ejeX,
    categories: data.value.serie_dias.map(f => fechaCorta(f.fecha).slice(0, 5)),
    tickAmount: Math.min(12, data.value.serie_dias.length),
  },
  yaxis: ejeY,
  tooltip: {
    ...base.tooltip,
    shared: true,
    intersect: false,
    x: { formatter: (_v, { dataPointIndex }) => fechaCorta(data.value.serie_dias[dataPointIndex]?.fecha) },
    y: { formatter: v => money(v) + ' Bs' },
  },
}))

/* ── 2. Evolución mensual (barras) ────────────────────────────── */
const serieMensual = computed(() => {
  const series = []
  if (permisos.value.ventas) {
    series.push({ name: 'Ventas', data: data.value.serie_meses.map(f => Number(f.ventas)) })
  }
  if (permisos.value.compras) {
    series.push({ name: 'Compras', data: data.value.serie_meses.map(f => Number(f.compras)) })
  }
  return series
})

const opcionesMensual = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[0], SERIES[1]],
  plotOptions: {
    bar: { columnWidth: '55%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  legend: { ...base.legend, show: serieMensual.value.length > 1 },
  xaxis: { ...ejeX, categories: data.value.serie_meses.map(f => f.etiqueta) },
  yaxis: ejeY,
  tooltip: { ...base.tooltip, shared: true, intersect: false, y: { formatter: v => money(v) + ' Bs' } },
}))

/* ── 3. Forma de pago (dona) ──────────────────────────────────── */
const opcionesTipoPago = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'donut' },
  labels: data.value.tipo_pago.map(f => f.tipo_pago),
  colors: coloresEstables('tipo_pago', data.value.tipo_pago.map(f => f.tipo_pago)),
  stroke: { width: 2, colors: ['#ffffff'] },
  dataLabels: {
    enabled: true,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 600, colors: ['#ffffff'] },
    dropShadow: { enabled: false },
    formatter: v => v.toFixed(0) + '%',
  },
  plotOptions: {
    pie: {
      donut: {
        size: '64%',
        labels: {
          show: true,
          name: { fontSize: '12px', color: TINTA.tenue, fontFamily: FUENTE },
          value: {
            fontSize: '18px', fontWeight: 700, color: TINTA.primaria, fontFamily: FUENTE,
            formatter: v => money(v) + ' Bs',
          },
          total: {
            show: true, label: 'Total', fontSize: '12px', color: TINTA.tenue, fontFamily: FUENTE,
            formatter: w => money(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) + ' Bs',
          },
        },
      },
    },
  },
  tooltip: { ...base.tooltip, y: { formatter: v => money(v) + ' Bs' } },
}))

/* ── 4. Top productos (barras horizontales) ───────────────────── */
const serieTopProductos = computed(() => ([
  { name: 'Vendido', data: data.value.top_productos.map(f => Number(f.total)) },
]))

const opcionesTopProductos = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[0]],
  plotOptions: {
    bar: { horizontal: true, barHeight: '58%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    offsetX: 6,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 600, colors: [TINTA.secundaria] },
    formatter: v => money(v) + ' Bs',
  },
  grid: { ...base.grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
  xaxis: {
    ...ejeX,
    categories: data.value.top_productos.map(f => f.nombre),
    labels: { ...ejeX.labels, formatter: v => moneyCorto(v) },
  },
  yaxis: {
    labels: {
      style: { colors: TINTA.secundaria, fontSize: '11px', fontFamily: FUENTE },
      maxWidth: 200,
      formatter: v => (String(v).length > 32 ? String(v).slice(0, 31) + '…' : v),
    },
  },
  legend: { show: false },
  tooltip: {
    ...base.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) => {
        const fila = data.value.top_productos[dataPointIndex]
        return `${money(v)} Bs · ${Number(fila?.cantidad || 0)} unidad(es)`
      },
    },
  },
}))

/* ── 5. Ventas por categoría (dona) ───────────────────────────── */
const opcionesTipoProducto = computed(() => ({
  ...opcionesTipoPago.value,
  labels: data.value.por_tipo_producto.map(f => f.tipo),
  colors: coloresEstables('tipo_producto', data.value.por_tipo_producto.map(f => f.tipo)),
}))

/* ── 6. Actividad por hora (barras) ───────────────────────────── */
const serieHora = computed(() => ([
  { name: 'Ventas', data: data.value.ventas_por_hora.map(f => f.cantidad) },
]))

const opcionesHora = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[2]],
  plotOptions: { bar: { columnWidth: '62%', borderRadius: 3, borderRadiusApplication: 'end' } },
  xaxis: {
    ...ejeX,
    categories: data.value.ventas_por_hora.map(f => f.hora),
    tickAmount: 8,
  },
  yaxis: {
    ...ejeY,
    labels: { ...ejeY.labels, formatter: v => Number(v).toFixed(0) },
  },
  legend: { show: false },
  tooltip: {
    ...base.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) =>
        `${v} venta(s) · ${money(data.value.ventas_por_hora[dataPointIndex]?.total)} Bs`,
    },
  },
}))

/* ── 7. Ventas por usuario (barras horizontales) ──────────────── */
const serieVendedores = computed(() => ([
  { name: 'Vendido', data: data.value.top_vendedores.map(f => Number(f.total)) },
]))

const opcionesVendedores = computed(() => ({
  ...opcionesTopProductos.value,
  colors: [SERIES[2]],
  plotOptions: {
    bar: { horizontal: true, barHeight: data.value.top_vendedores.length < 3 ? '34%' : '58%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  xaxis: {
    ...ejeX,
    categories: data.value.top_vendedores.map(f => f.nombre),
    labels: { ...ejeX.labels, formatter: v => moneyCorto(v) },
  },
  yaxis: {
    labels: {
      style: { colors: TINTA.secundaria, fontSize: '11px', fontFamily: FUENTE },
      maxWidth: 130,
      formatter: v => (String(v).length > 20 ? String(v).slice(0, 19) + '…' : v),
    },
  },
  tooltip: {
    ...base.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) => {
        const fila = data.value.top_vendedores[dataPointIndex]
        return `${money(v)} Bs · ${fila?.cantidad || 0} venta(s)`
      },
    },
  },
}))

/* ── 8. Solicitudes por estado (barras) ───────────────────────────
   Los estados son etapas de un mismo flujo, no categorías rivales: una
   sola serie con las etapas nombradas en el eje lo dice mejor que una dona. */
const serieSolicitudes = computed(() => ([
  { name: 'Solicitudes', data: data.value.solicitudes_estado.map(f => f.cantidad) },
]))

const opcionesSolicitudes = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [SERIES[2]],
  plotOptions: {
    bar: { horizontal: true, barHeight: '52%', borderRadius: 4, borderRadiusApplication: 'end' },
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'start',
    offsetX: 6,
    style: { fontSize: '11px', fontFamily: FUENTE, fontWeight: 700, colors: [TINTA.secundaria] },
  },
  grid: { ...base.grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
  xaxis: {
    ...ejeX,
    categories: data.value.solicitudes_estado.map(f => f.estado.replaceAll('_', ' ')),
    labels: { ...ejeX.labels, formatter: v => Number(v).toFixed(0) },
    /* Conteos enteros: sin esto el eje repite ticks (0,1,1,2,2,3,3). */
    tickAmount: Math.min(4, Math.max(1, ...data.value.solicitudes_estado.map(f => f.cantidad))),
  },
  yaxis: {
    labels: { style: { colors: TINTA.secundaria, fontSize: '10.5px', fontFamily: FUENTE }, maxWidth: 110 },
  },
  legend: { show: false },
  tooltip: { ...base.tooltip, y: { formatter: v => v + ' solicitud(es)' } },
}))

/* ── 9. Lotes por vencer (barras, colores de estado) ──────────── */
const serieVencimientos = computed(() => ([
  { name: 'Lotes', data: data.value.vencimientos.map(f => f.cantidad) },
]))

const opcionesVencimientos = computed(() => ({
  ...base,
  chart: { ...base.chart, type: 'bar' },
  colors: [ESTADO.critico, ESTADO.serio, ESTADO.alerta, ESTADO.bueno],
  plotOptions: {
    bar: {
      columnWidth: '48%',
      borderRadius: 4,
      borderRadiusApplication: 'end',
      distributed: true,
      dataLabels: { position: 'top' },
    },
  },
  dataLabels: {
    enabled: true,
    offsetY: -20,
    style: { fontSize: '12px', fontFamily: FUENTE, fontWeight: 700, colors: [TINTA.secundaria] },
  },
  xaxis: { ...ejeX, categories: data.value.vencimientos.map(f => f.etiqueta) },
  yaxis: {
    ...ejeY,
    labels: { ...ejeY.labels, formatter: v => Number(v).toFixed(0) },
    tickAmount: Math.min(4, Math.max(1, ...data.value.vencimientos.map(f => f.cantidad))),
  },
  legend: { show: false },
  tooltip: { ...base.tooltip, y: { formatter: v => v + ' lote(s) con existencia' } },
}))
</script>

<style scoped>
.dashboard {
  background: #f9f9f7;
}

/* ── Bienvenida sin permiso de panel ───────────────────────── */
.bienvenida__logo {
  width: 100%;
  max-width: 220px;
  height: auto;
  object-fit: contain;
}

/* ── Indicadores ───────────────────────────────────────────── */
.kpi {
  padding: 12px 14px;
  border-radius: 10px;
}
.kpi--brand {
  background: linear-gradient(135deg, #0e7a5f 0%, #12996f 100%);
  color: #fff;
}
.kpi__label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #898781;
}
.kpi--brand .kpi__label { color: rgba(255, 255, 255, .82); }
.kpi__value {
  font-size: 22px;
  font-weight: 700;
  line-height: 1.25;
  color: #0b0b0b;
}
.kpi--brand .kpi__value { color: #fff; }
.kpi__unit {
  font-size: 12px;
  font-weight: 600;
  color: #898781;
}
.kpi--brand .kpi__unit { color: rgba(255, 255, 255, .8); }
.kpi__foot {
  font-size: 11px;
  color: #52514e;
  margin-top: 2px;
}
.kpi--brand .kpi__foot { color: rgba(255, 255, 255, .85); }
.kpi__delta--up { color: #006300; font-weight: 700; }
.kpi__delta--down { color: #d03b3b; font-weight: 700; }
.kpi--brand .kpi__delta--up,
.kpi--brand .kpi__delta--down { color: #fff; }

/* ── Chips secundarios ─────────────────────────────────────── */
.mini {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 11px;
  border-radius: 10px;
  transition: border-color .15s ease, transform .15s ease;
}
.mini:hover { border-color: #0e7a5f; transform: translateY(-1px); }
.mini--alerta { background: #fff7f6; }
.mini__value {
  font-size: 14px;
  font-weight: 700;
  color: #0b0b0b;
  line-height: 1.2;
}
.mini__label {
  font-size: 10.5px;
  color: #898781;
  line-height: 1.2;
}

/* ── Paneles de gráficos ───────────────────────────────────── */
.panel {
  padding: 12px 8px 4px;
  border-radius: 10px;
  height: 100%;
}
.panel__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 0 8px 4px;
}
.panel__title {
  font-size: 13.5px;
  font-weight: 700;
  color: #0b0b0b;
}
.panel__sub {
  font-size: 11px;
  color: #898781;
  margin-top: 1px;
}
.panel__total {
  font-size: 15px;
  font-weight: 700;
  color: #0b0b0b;
  white-space: nowrap;
}
.vacio {
  padding: 48px 12px;
  text-align: center;
  font-size: 12px;
  color: #898781;
}
.dashboard__rango {
  border: 1px solid #e1e0d9;
  border-radius: 8px;
}
</style>
