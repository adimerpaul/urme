<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver ventas</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="q-mb-md">
        <div class="text-h5 text-weight-bold">Ventas</div>
        <div class="text-body2 text-grey-6">Historial de ventas y proformas de pago</div>
      </div>

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm-4">
          <q-card flat class="bg-primary text-white q-pa-md rounded-borders full-height">
            <div class="text-caption text-teal-2 text-uppercase text-weight-bold">Ventas activas</div>
            <div class="text-h5 text-weight-bold">{{ money(resumen.total_ventas) }} <span class="text-caption text-teal-2">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-md rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Anuladas</div>
            <div class="text-h5 text-weight-bold text-negative">{{ money(resumen.total_anuladas) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-4">
          <q-card flat bordered class="q-pa-md rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Total registros</div>
            <div class="text-h5 text-weight-bold">{{ resumen.cantidad }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left" no-caps
              active-color="primary" indicator-color="primary" class="q-mb-sm text-grey-7">
        <q-tab name="historial" icon="history" label="Historial" no-caps />
        <q-tab v-if="canCrear" name="nueva" icon="point_of_sale" label="Nueva venta" no-caps />
      </q-tabs>
      <q-separator class="q-mb-md" />

      <!-- ══ TAB HISTORIAL ══════════════════════════════════════════ -->
      <div v-show="tab === 'historial'">
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
                      :options="['ACTIVO', 'ANULADO']" style="width:130px" @update:model-value="onFiltroChange" />
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
              <th class="text-left">Usuario</th>
              <th class="text-center">Estado</th>
              <th class="text-left">Pago</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingVentas">
              <td colspan="9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!ventas.length">
              <td colspan="9" class="text-center text-grey-5 q-pa-md">Sin datos</td>
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
              <td>{{ row.doctor || '—' }}</td>
              <td>{{ row.user ? row.user.name : '—' }}</td>
              <td class="text-center">
                <q-badge rounded
                         :color="row.estado === 'ANULADO' ? 'red-1' : 'green-1'"
                         :text-color="row.estado === 'ANULADO' ? 'negative' : 'positive'"
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

      <!-- ══ TAB NUEVA VENTA ════════════════════════════════════════ -->
      <div v-show="tab === 'nueva'">
        <div class="row q-col-gutter-md">

          <!-- Productos -->
          <div class="col-12 col-md-7">
            <div class="row items-center q-gutter-sm q-mb-sm">
              <span class="text-subtitle2 text-weight-bold text-grey-8">Productos</span>
              <q-space />
              <q-select v-model="filtroTipo" dense outlined clearable label="Tipo" style="width:150px"
                        :options="tiposProducto" option-value="id" option-label="nombre"
                        emit-value map-options @update:model-value="onBuscarProducto" />
              <q-input v-model="buscarProducto" placeholder="Buscar producto…" dense outlined rounded clearable
                       bg-color="white" style="width:220px" @update:model-value="onBuscarProducto">
                <template v-slot:prepend><q-icon name="search" /></template>
              </q-input>
            </div>

            <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th style="width:44px"></th>
                  <th class="text-left">Código</th>
                  <th class="text-left">Producto</th>
                  <th class="text-left">Tipo</th>
                  <th class="text-right">Precio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingProductos">
                  <td colspan="5" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
                </tr>
                <tr v-else-if="!productos.length">
                  <td colspan="5" class="text-center text-grey-5 q-pa-md">Sin productos</td>
                </tr>
                <tr v-else v-for="p in productos" :key="p.id">
                  <td class="text-center">
                    <q-btn dense round unelevated size="sm" color="primary" icon="add"
                           @click="agregarProducto(p)">
                      <q-tooltip>Agregar a la venta</q-tooltip>
                    </q-btn>
                  </td>
                  <td>{{ p.codigo || '—' }}</td>
                  <td>{{ p.nombre }}</td>
                  <td>
                    <q-badge v-if="p.tipo_producto" rounded :style="{ background: p.tipo_producto.color || '#607d8b' }">
                      {{ p.tipo_producto.nombre }}
                    </q-badge>
                    <span v-else>—</span>
                  </td>
                  <td class="text-right">{{ money(p.precio) }}</td>
                </tr>
              </tbody>
            </q-markup-table>

            <div class="row items-center justify-between q-mt-xs q-px-xs">
              <div class="text-caption text-grey-6">
                Total: {{ totalProductos }} | Página {{ pageProductos }} de {{ pagesProductos }}
              </div>
              <q-pagination v-model="pageProductos" :max="pagesProductos" :max-pages="6"
                            boundary-links direction-links size="sm" @update:model-value="loadProductos" />
            </div>
          </div>

          <!-- Carrito / datos de la venta -->
          <div class="col-12 col-md-5">
            <q-card flat bordered class="q-pa-md rounded-borders">
              <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-sm">
                <q-icon name="shopping_cart" size="18px" class="q-mr-xs" />Detalle de la venta
              </div>

              <q-form @submit.prevent="registrarVenta">
                <div class="row q-col-gutter-sm q-mb-sm">
                  <div class="col-12">
                    <q-select v-model="nueva.paciente_id" label="Paciente" dense outlined clearable use-input
                              input-debounce="350" :options="opcionesPaciente"
                              option-value="id" option-label="nombre_completo" emit-value map-options
                              @filter="filtrarPacientes">
                      <template v-slot:no-option>
                        <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                      </template>
                    </q-select>
                  </div>
                  <div class="col-12" v-if="!nueva.paciente_id">
                    <q-input v-model="nueva.cliente" label="Cliente (si no es paciente)" dense outlined v-uppercase />
                  </div>
                  <div class="col-12">
                    <q-input v-model="nueva.doctor" label="Doctor" dense outlined v-uppercase />
                  </div>
                  <div class="col-6">
                    <q-input v-model="nueva.fecha_hora" label="Fecha y hora *" dense outlined type="datetime-local"
                             :rules="[v => !!v || 'Requerido']" />
                  </div>
                  <div class="col-6">
                    <q-select v-model="nueva.tipo_pago" label="Tipo de pago" dense outlined
                              :options="['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'QR']" />
                  </div>
                </div>

                <q-markup-table dense flat bordered separator="horizontal" class="full-width q-mb-sm rounded-borders tabla-compacta">
                  <thead>
                    <tr class="bg-grey-1 text-grey-7 text-uppercase">
                      <th style="width:36px"></th>
                      <th class="text-left">Producto</th>
                      <th class="text-right" style="width:86px">Cant.</th>
                      <th class="text-right" style="width:96px">Precio</th>
                      <th class="text-right" style="width:80px">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!nueva.detalles.length">
                      <td colspan="5" class="text-center text-grey-5 q-pa-md">Agregue productos desde la izquierda</td>
                    </tr>
                    <tr v-for="(linea, idx) in nueva.detalles" :key="linea.uid">
                      <td class="text-center">
                        <q-btn flat dense round icon="delete" color="negative" size="sm" @click="quitarLinea(idx)" />
                      </td>
                      <td>{{ linea.nombre }}</td>
                      <td><q-input v-model.number="linea.cantidad" dense outlined type="number" step="1" min="0"
                                   @update:model-value="recalcularLinea(linea)" /></td>
                      <td><q-input v-model.number="linea.precio" dense outlined type="number" step="0.01" min="0"
                                   @update:model-value="recalcularLinea(linea)" /></td>
                      <td class="text-right">{{ money(linea.total) }}</td>
                    </tr>
                  </tbody>
                </q-markup-table>

                <div class="row q-col-gutter-sm items-center q-mb-sm">
                  <div class="col-4">
                    <q-input :model-value="money(totalNueva)" label="Total Bs" dense outlined readonly
                             input-class="text-right text-weight-bold" />
                  </div>
                  <div class="col-4">
                    <q-input v-model.number="nueva.pago" label="Pago Bs" dense outlined type="number" step="0.01" min="0"
                             input-class="text-right" />
                  </div>
                  <div class="col-4">
                    <q-input :model-value="money(cambioNueva)" label="Cambio Bs" dense outlined readonly
                             :input-class="'text-right ' + (cambioNueva < 0 ? 'text-negative' : '')" />
                  </div>
                  <div class="col-12">
                    <q-input v-model="nueva.comentario" label="Comentario" dense outlined type="textarea" rows="1" />
                  </div>
                </div>

                <div class="row items-center justify-between">
                  <div class="text-h6">Total: <span class="text-primary text-weight-bold">{{ money(totalNueva) }} Bs</span></div>
                  <q-btn rounded unelevated color="primary" label="Registrar venta" icon-right="save" no-caps
                         type="submit" :loading="registrando" :disable="!nueva.detalles.length" />
                </div>
              </q-form>
            </q-card>
          </div>
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
            <div class="col-6"><b>Doctor:</b> {{ detalleVenta?.doctor || '—' }}</div>
            <div class="col-6"><b>Usuario:</b> {{ detalleVenta?.user?.name || '—' }}</div>
            <div class="col-6"><b>Fecha:</b> {{ formatFecha(detalleVenta?.fecha_hora) }}</div>
          </div>
          <q-markup-table dense flat bordered separator="horizontal">
            <thead>
              <tr class="bg-grey-1 text-grey-7 text-uppercase">
                <th class="text-left">Producto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in detalleVenta?.detalles || []" :key="d.id">
                <td>{{ d.nombre }}</td>
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

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirVenta } from '../../../addons/ventaPrint'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Ventas'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Ventas'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Ventas'))

const tab     = ref('historial')
const resumen = ref({ total_ventas: 0, total_anuladas: 0, cantidad: 0 })

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return v ? v.replace('T', ' ').slice(0, 16) : '—' }

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

// ── Productos (tab nueva venta) ────────────────────────────────
const productos        = ref([])
const loadingProductos = ref(false)
const buscarProducto   = ref('')
const filtroTipo       = ref(null)
const tiposProducto    = ref([])
const pageProductos    = ref(1)
const totalProductos   = ref(0)
const perProductos     = 10

const pagesProductos = computed(() => Math.max(1, Math.ceil(totalProductos.value / perProductos)))

let timerProducto = null
function onBuscarProducto () {
  clearTimeout(timerProducto)
  timerProducto = setTimeout(() => { pageProductos.value = 1; loadProductos() }, 350)
}

async function loadProductos () {
  loadingProductos.value = true
  try {
    const res = await proxy.$axios.get('productos', {
      params: {
        q: buscarProducto.value,
        tipo_producto_id: filtroTipo.value,
        page: pageProductos.value,
        per_page: perProductos,
      },
    })
    productos.value = res.data?.data || []
    totalProductos.value = res.data?.total || 0
  } catch (e) {
    proxy.$alert.error('Error al cargar productos')
  } finally {
    loadingProductos.value = false
  }
}

async function loadTiposProducto () {
  try {
    const res = await proxy.$axios.get('tipo-productos')
    tiposProducto.value = res.data || []
  } catch (e) { /* silent */ }
}

// ── Nueva venta ────────────────────────────────────────────────
const registrando = ref(false)
let lineaUid = 0

function nuevaVentaVacia () {
  return {
    paciente_id: null,
    cliente: '',
    doctor: '',
    fecha_hora: new Date().toISOString().slice(0, 16),
    tipo_pago: 'EFECTIVO',
    comentario: '',
    pago: null,
    detalles: [],
  }
}
const nueva = ref(nuevaVentaVacia())

const totalNueva  = computed(() => nueva.value.detalles.reduce((acc, l) => acc + (Number(l.total) || 0), 0))
const cambioNueva = computed(() => {
  const pago = Number(nueva.value.pago)
  if (!pago) return 0
  return Math.round((pago - totalNueva.value) * 100) / 100
})

function agregarProducto (p) {
  const existente = nueva.value.detalles.find(l => l.producto_id === p.id)
  if (existente) {
    existente.cantidad = (Number(existente.cantidad) || 0) + 1
    recalcularLinea(existente)
    return
  }
  const linea = {
    uid: ++lineaUid,
    producto_id: p.id,
    nombre: p.nombre,
    cantidad: 1,
    precio: Number(p.precio) || 0,
    total: 0,
  }
  recalcularLinea(linea)
  nueva.value.detalles.push(linea)
}

function quitarLinea (idx) {
  nueva.value.detalles.splice(idx, 1)
}

function recalcularLinea (linea) {
  linea.total = Math.round(((Number(linea.cantidad) || 0) * (Number(linea.precio) || 0)) * 100) / 100
}

async function registrarVenta () {
  if (!nueva.value.detalles.length) {
    proxy.$alert.error('Agregue al menos un producto a la venta')
    return
  }
  const pago = Number(nueva.value.pago) || totalNueva.value
  if (pago < totalNueva.value) {
    proxy.$alert.error('El pago no puede ser menor al total')
    return
  }
  registrando.value = true
  try {
    const payload = {
      paciente_id: nueva.value.paciente_id,
      cliente: nueva.value.paciente_id ? null : nueva.value.cliente,
      doctor: nueva.value.doctor,
      fecha_hora: nueva.value.fecha_hora.replace('T', ' '),
      tipo_pago: nueva.value.tipo_pago,
      comentario: nueva.value.comentario,
      pago,
      detalles: nueva.value.detalles.map(l => ({
        producto_id: l.producto_id || null,
        nombre: l.nombre,
        cantidad: l.cantidad,
        precio: l.precio,
      })),
    }
    const res = await proxy.$axios.post('ventas', payload)
    proxy.$alert.success('Venta registrada')
    nueva.value = nuevaVentaVacia()
    tab.value = 'historial'
    loadVentas()
    imprimirVenta(res.data)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al registrar la venta')
  } finally {
    registrando.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadVentas()
  loadProductos()
  loadTiposProducto()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}
</style>
