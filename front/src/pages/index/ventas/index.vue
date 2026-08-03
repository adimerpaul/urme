<template>
  <q-page class="q-pa-sm ventas-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver ventas</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="row items-center q-mb-xs">
        <div>
          <div class="text-h6 text-weight-bold">Ventas</div>
          <div class="text-caption text-grey-6">Historial de ventas y proformas de pago</div>
        </div>
        <q-space />
        <q-btn v-if="canCrear" rounded unelevated color="primary" icon="point_of_sale"
               label="Nueva venta" no-caps to="/ventas/crear" />
      </div>

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-12 col-sm-3">
          <q-card flat class="bg-primary text-white q-pa-sm rounded-borders full-height">
            <div class="text-caption text-teal-2 text-uppercase text-weight-bold">Ventas activas</div>
            <div class="text-subtitle1 text-weight-bold">{{ money(resumen.total_ventas) }} <span class="text-caption text-teal-2">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Pendientes</div>
            <div class="text-subtitle1 text-weight-bold text-orange-8">{{ money(resumen.total_pendientes) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Anuladas</div>
            <div class="text-subtitle1 text-weight-bold text-negative">{{ money(resumen.total_anuladas) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Total registros</div>
            <div class="text-subtitle1 text-weight-bold">{{ resumen.cantidad }}</div>
          </q-card>
        </div>
      </div>

      <q-separator class="q-mb-xs" />

      <!-- ══ HISTORIAL ══════════════════════════════════════════════ -->
      <div>
        <div class="row items-center q-col-gutter-xs q-mb-xs">
          <div class="col-auto">
            <q-input v-model="filtro.fecha_inicio" label="Fecha inicio" dense outlined type="date"
                     style="width:150px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.fecha_fin" label="Fecha fin" dense outlined type="date"
                     style="width:150px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-select v-model="filtro.paciente_id" label="Paciente" dense outlined clearable use-input
                      input-debounce="350" :options="opcionesPaciente"
                      option-value="id" option-label="nombre_completo" emit-value map-options
                      style="width:220px" @filter="filtrarPacientes" @update:model-value="onFiltroChange">
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
              </template>
            </q-select>
          </div>
          <div class="col-auto">
            <q-select v-model="filtro.estado" label="Estado" dense outlined clearable
                      :options="['ACTIVO', 'PENDIENTE', 'ANULADO']" style="width:140px" @update:model-value="onFiltroChange" />
          </div>
        </div>

        <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">ID</th>
              <th class="text-left">Fecha</th>
              <th class="text-left">Cliente / Paciente</th>
              <th class="text-left">Doctor</th>
              <th class="text-left">Seguro</th>
              <th class="text-left">Usuario</th>
              <th class="text-center">Estado</th>
              <th class="text-left">Pago</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingVentas">
              <td colspan="10" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!ventas.length">
              <td colspan="10" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in ventas" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown label="Opciones" no-caps size="10px" dense rounded unelevated color="primary">
                  <q-list dense>
                    <q-item clickable v-close-popup @click="verDetalle(row)">
                      <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Ver detalle ({{ row.detalles_count }})</q-item-label></q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="imprimir(row)">
                      <q-item-section avatar><q-icon name="print" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Imprimir</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="row.estado === 'PENDIENTE'" clickable v-close-popup @click="abrirCobrar(row)">
                      <q-item-section avatar><q-icon name="payments" color="positive" /></q-item-section>
                      <q-item-section><q-item-label class="text-positive">Cobrar venta</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" :disable="row.estado === 'ANULADO'" clickable v-close-popup @click="anular(row)">
                      <q-item-section avatar><q-icon name="block" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Anular</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.id }}</td>
              <td>{{ formatFecha(row.fecha_hora) }}</td>
              <td>{{ row.paciente ? row.paciente.nombre_completo : (row.cliente || '—') }}</td>
              <td>{{ row.doctor ? row.doctor.nombre : '—' }}</td>
              <td>{{ row.seguro ? row.seguro.nombre : 'PARTICULAR' }}</td>
              <td>{{ row.user ? row.user.name : '—' }}</td>
              <td class="text-center">
                <q-badge rounded
                         :color="estadoColor(row.estado).bg"
                         :text-color="estadoColor(row.estado).text"
                         class="text-weight-bold">{{ row.estado }}</q-badge>
              </td>
              <td>{{ row.tipo_pago }}</td>
              <td class="text-right">{{ money(row.total) }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalVentas }} | Página {{ pageVentas }} de {{ pagesVentas }}
          </div>
          <q-pagination v-model="pageVentas" :max="pagesVentas" :max-pages="6"
                        boundary-links direction-links size="sm" @update:model-value="loadVentas" />
        </div>
      </div>

    </template>

    <!-- DIALOG DETALLE VENTA -->
    <q-dialog v-model="dialogDetalle">
      <q-card style="width:min(96vw,700px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Detalle de venta #{{ detalleVenta?.id }}</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogDetalle = false" />
        </q-card-section>
        <q-card-section style="max-height:70vh;overflow-y:auto">
          <div class="row q-col-gutter-sm q-mb-sm text-body2">
            <div class="col-6"><b>Cliente:</b> {{ detalleVenta?.paciente?.nombre_completo || detalleVenta?.cliente || '—' }}</div>
            <div class="col-6"><b>Doctor:</b> {{ detalleVenta?.doctor?.nombre || '—' }}</div>
            <div class="col-6"><b>Seguro:</b> {{ detalleVenta?.seguro?.nombre || 'PARTICULAR' }}</div>
            <div class="col-6"><b>Usuario:</b> {{ detalleVenta?.user?.name || '—' }}</div>
            <div class="col-6"><b>Fecha:</b> {{ formatFecha(detalleVenta?.fecha_hora) }}</div>
            <div class="col-6"><b>Estado:</b> {{ detalleVenta?.estado || '—' }}</div>
          </div>
          <q-markup-table dense flat bordered separator="horizontal">
            <thead>
              <tr class="bg-grey-1 text-grey-7 text-uppercase">
                <th class="text-left">Producto</th>
                <th class="text-left">Lote / Vencimiento</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in detalleVenta?.detalles || []" :key="d.id">
                <td>{{ d.nombre }}</td>
                <td>{{ d.lote || '—' }} / {{ d.fecha_vencimiento || 'SIN FECHA' }}</td>
                <td class="text-right">{{ d.cantidad }}</td>
                <td class="text-right">{{ money(d.precio) }}</td>
                <td class="text-right">{{ money(d.total) }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <div class="row justify-end q-mt-sm text-body2">
            <div class="text-right">
              <div><b>Total:</b> {{ money(detalleVenta?.total) }} Bs</div>
              <div><b>Pago:</b> {{ money(detalleVenta?.pago) }} Bs</div>
              <div><b>Cambio:</b> {{ money(detalleVenta?.cambio) }} Bs</div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG COBRAR VENTA PENDIENTE -->
    <q-dialog v-model="dialogCobrar" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Cobrar venta #{{ ventaCobrar?.id }}</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="cobrarVenta">
            <div class="text-h6 q-mb-sm">Total: <span class="text-primary text-weight-bold">{{ money(ventaCobrar?.total) }} Bs</span></div>
            <q-input v-model.number="cobrarPago" label="Pago Bs *" dense outlined type="number" step="0.01" min="0"
                     class="q-mb-xs" autofocus input-class="text-right" />
            <div class="text-body2 q-mb-md">Cambio:
              <span class="text-weight-bold" :class="cobrarCambio < 0 ? 'text-negative' : 'text-positive'">
                {{ money(cobrarCambio) }} Bs
              </span>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogCobrar = false" />
              <q-btn color="primary" label="Cobrar e imprimir" icon-right="payments" type="submit" no-caps :loading="cobrando" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirVenta } from '../../../addons/ventaPrint'
import { formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Ventas'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Ventas'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Ventas'))

const resumen = ref({ total_ventas: 0, total_pendientes: 0, total_anuladas: 0, cantidad: 0 })

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return formatBoliviaDateTime(v) }

function estadoColor (estado) {
  if (estado === 'ANULADO') return { bg: 'red-1', text: 'negative' }
  if (estado === 'PENDIENTE') return { bg: 'orange-1', text: 'orange-9' }
  return { bg: 'green-1', text: 'positive' }
}

// ── Pacientes (select con filtro) ──────────────────────────────
const opcionesPaciente = ref([])
async function filtrarPacientes (val, update) {
  try {
    const res = await proxy.$axios.get('pacientes', { params: { q: val, per_page: 20 } })
    update(() => { opcionesPaciente.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesPaciente.value = [] })
  }
}

// ── Historial ──────────────────────────────────────────────────
const ventas        = ref([])
const loadingVentas = ref(false)
const pageVentas    = ref(1)
const totalVentas   = ref(0)
const perVentas     = 15
const filtro = ref({ fecha_inicio: '', fecha_fin: '', paciente_id: null, estado: null })

const pagesVentas = computed(() => Math.max(1, Math.ceil(totalVentas.value / perVentas)))

let timerFiltro = null
function onFiltroChange () {
  clearTimeout(timerFiltro)
  timerFiltro = setTimeout(() => { pageVentas.value = 1; loadVentas() }, 350)
}

async function loadVentas () {
  loadingVentas.value = true
  try {
    const res = await proxy.$axios.get('ventas', {
      params: {
        page: pageVentas.value,
        per_page: perVentas,
        fecha_inicio: filtro.value.fecha_inicio,
        fecha_fin: filtro.value.fecha_fin,
        paciente_id: filtro.value.paciente_id,
        estado: filtro.value.estado,
      },
    })
    const data = res.data || {}
    resumen.value = data.resumen || { total_ventas: 0, total_anuladas: 0, cantidad: 0 }
    ventas.value = data.ventas?.data || []
    totalVentas.value = data.ventas?.total || 0
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar ventas')
  } finally {
    loadingVentas.value = false
  }
}

const dialogDetalle = ref(false)
const detalleVenta  = ref(null)
async function verDetalle (row) {
  try {
    const res = await proxy.$axios.get('ventas/' + row.id)
    detalleVenta.value = res.data
    dialogDetalle.value = true
  } catch (e) {
    proxy.$alert.error('Error al cargar el detalle')
  }
}

async function imprimir (row) {
  try {
    const res = await proxy.$axios.get('ventas/' + row.id)
    imprimirVenta(res.data)
  } catch (e) {
    proxy.$alert.error('Error al imprimir')
  }
}

function anular (row) {
  proxy.$alert.dialog('¿Desea anular la venta #' + row.id + '?').onOk(() => {
    proxy.$axios.delete('ventas/' + row.id)
      .then(() => { proxy.$alert.success('Venta anulada'); loadVentas() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al anular'))
  })
}

// ── Cobrar venta pendiente ─────────────────────────────────────
const dialogCobrar = ref(false)
const ventaCobrar  = ref(null)
const cobrarPago   = ref(0)
const cobrando     = ref(false)

const cobrarCambio = computed(() => {
  const pago = Number(cobrarPago.value) || 0
  return Math.round((pago - Number(ventaCobrar.value?.total || 0)) * 100) / 100
})

function abrirCobrar (row) {
  ventaCobrar.value = row
  cobrarPago.value = Number(row.total)
  dialogCobrar.value = true
}

async function cobrarVenta () {
  if (Number(cobrarPago.value) < Number(ventaCobrar.value?.total || 0)) {
    proxy.$alert.error('El pago no puede ser menor al total')
    return
  }
  cobrando.value = true
  try {
    const res = await proxy.$axios.put('ventas/' + ventaCobrar.value.id + '/completar', { pago: cobrarPago.value })
    proxy.$alert.success('Venta cobrada')
    dialogCobrar.value = false
    loadVentas()
    imprimirVenta(res.data)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cobrar')
  } finally {
    cobrando.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadVentas()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.ventas-compactas :deep(.q-field--dense .q-field__native),
.ventas-compactas :deep(.q-field--dense .q-field__input),
.ventas-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.ventas-compactas :deep(.q-field--dense .q-field__append),
.ventas-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.ventas-compactas :deep(.q-textarea.q-field--dense .q-field__control) {
  min-height: 38px;
}

.ventas-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}
</style>
