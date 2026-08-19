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
        <q-btn v-if="canCerrarCaja" rounded outline color="teal-8" icon="lock_clock" class="q-mr-sm"
               :label="caja.cerrada ? 'Caja cerrada' : 'Cerrar caja'" no-caps @click="abrirCierre">
          <q-tooltip>
            {{ caja.cerrada ? 'Ver el cierre de caja de hoy' : 'Cerrar la caja del día' }}
          </q-tooltip>
        </q-btn>
        <q-btn v-if="canCrear" rounded unelevated color="primary" icon="point_of_sale"
               label="Nueva venta" no-caps :disable="caja.cerrada" to="/ventas/crear">
          <q-tooltip v-if="caja.cerrada">Su caja de hoy ya fue cerrada</q-tooltip>
        </q-btn>
      </div>

      <q-banner v-if="caja.cerrada" dense rounded class="bg-orange-1 text-orange-10 q-mb-xs">
        <template v-slot:avatar><q-icon name="lock" color="orange-9" /></template>
        Su caja de hoy está cerrada con <b>{{ money(caja.cierre?.monto) }} Bs</b>.
        No puede registrar ni cobrar más ventas hasta mañana.
      </q-banner>

      <!-- Tarjetas resumen — solo con 'Ver Montos Caja' -->
      <div v-if="canMontos" class="row q-col-gutter-xs q-mb-xs">
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
            <q-btn dense unelevated no-caps color="primary" icon="today" label="Hoy" @click="filtrarHoy">
              <q-tooltip>Ventas de hoy, de 00:00 a 23:59</q-tooltip>
            </q-btn>
          </div>
          <div class="col-auto">
            <q-btn dense outline no-caps color="grey-7" icon="event_repeat" label="Ver todo" @click="verTodo">
              <q-tooltip>Quitar el filtro de fecha y hora</q-tooltip>
            </q-btn>
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.fecha_inicio" label="Fecha inicio" dense outlined type="date"
                     style="width:140px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.hora_inicio" label="Desde hora" dense outlined type="time"
                     style="width:110px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.fecha_fin" label="Fecha fin" dense outlined type="date"
                     style="width:140px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.hora_fin" label="Hasta hora" dense outlined type="time"
                     style="width:110px" @update:model-value="onFiltroChange" />
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

        <div class="text-caption text-grey-6 q-mb-xs">{{ rangoFiltro }}</div>

        <!-- Ancho de columnas fijo y alto mínimo: la tabla no cambia de forma al cargar -->
        <div class="tabla-ventas-wrap">
        <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta tabla-ventas">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th class="text-left" style="width:72px"></th>
              <th class="text-left" style="width:44px">ID</th>
              <th class="text-left" style="width:118px">Fecha</th>
              <th class="text-left">Cliente / Paciente</th>
              <th class="text-left" style="width:110px">Doctor</th>
              <th class="text-left" style="width:120px">Seguro</th>
              <th class="text-left" style="width:130px">Usuario</th>
              <th class="text-center" style="width:94px">Estado</th>
              <th class="text-left" style="width:84px">Pago</th>
              <th v-if="canMontos" class="text-right" style="width:86px">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!ventas.length && !loadingVentas">
              <td :colspan="canMontos ? 10 : 9" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-for="row in ventas" :key="row.id">
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
                    <q-item v-if="row.estado === 'PENDIENTE' && !row.fecha_hora_cobro" :disable="caja.cerrada" clickable v-close-popup
                            @click="abrirCobrar(row)">
                      <q-item-section avatar><q-icon name="payments" color="positive" /></q-item-section>
                      <q-item-section>
                        <q-item-label class="text-positive">Cobrar venta</q-item-label>
                        <q-item-label v-if="caja.cerrada" caption>Caja cerrada</q-item-label>
                      </q-item-section>
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
              <!-- title: al recortarse con puntos suspensivos, el texto completo se ve al pasar el mouse -->
              <td :title="row.paciente ? row.paciente.nombre_completo : (row.cliente || '')">
                {{ row.paciente ? row.paciente.nombre_completo : (row.cliente || '—') }}
              </td>
              <td :title="row.doctor ? row.doctor.nombre : ''">{{ row.doctor ? row.doctor.nombre : '—' }}</td>
              <td :title="row.seguro ? row.seguro.nombre : 'PARTICULAR'">{{ row.seguro ? row.seguro.nombre : 'PARTICULAR' }}</td>
              <td :title="row.user ? row.user.name : ''">{{ row.user ? row.user.name : '—' }}</td>
              <td class="text-center">
                <q-badge rounded
                         :color="estadoColor(row).bg"
                         :text-color="estadoColor(row).text"
                         class="text-weight-bold">{{ estadoLabel(row) }}</q-badge>
              </td>
              <td>{{ row.tipo_pago }}</td>
              <td v-if="canMontos" class="text-right">{{ money(row.total) }}</td>
            </tr>
          </tbody>
        </q-markup-table>
          <q-inner-loading :showing="loadingVentas" color="primary" />
        </div>

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
            <div v-if="detalleVenta?.fecha_hora_cobro" class="col-6"><b>Cobrado por:</b> {{ detalleVenta?.cobrado_por?.name || '—' }}</div>
            <div v-if="detalleVenta?.fecha_hora_cobro" class="col-6"><b>Fecha de cobro:</b> {{ formatFecha(detalleVenta.fecha_hora_cobro) }}</div>
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

    <!-- ══ CIERRE DE CAJA ═════════════════════════════════════════ -->
    <q-dialog v-model="dialogCierre" persistent>
      <q-card style="width:min(96vw,520px)">
        <q-card-section class="row items-center bg-teal-8 text-white q-py-sm">
          <q-icon name="lock_clock" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">
              {{ caja.cerrada ? 'Cierre de caja de hoy' : 'Cerrar caja' }}
            </div>
            <div class="text-caption">{{ formatSoloFecha(caja.fecha) }} · {{ proxy.$store.user.name }}</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-sm">
          <!-- El total del sistema es el dinero que se debe entregar: no se
               muestra mientras se declara, solo después de cerrada la caja. -->
          <div v-if="caja.cerrada && !editandoCierre" class="row q-col-gutter-xs q-mb-sm">
            <div class="col-6">
              <q-card flat bordered class="q-pa-xs text-center">
                <div class="text-caption text-grey-6">Ventas del día (sistema)</div>
                <div class="text-subtitle1 text-weight-bold text-primary">
                  {{ money(caja.cierre?.monto_sistema) }} Bs
                </div>
              </q-card>
            </div>
            <div class="col-6">
              <q-card flat bordered class="q-pa-xs text-center">
                <div class="text-caption text-grey-6">Cantidad de ventas</div>
                <div class="text-subtitle1 text-weight-bold">
                  {{ caja.cierre?.cantidad_ventas }}
                </div>
              </q-card>
            </div>
          </div>

          <!-- Caja ya cerrada: se muestra lo guardado -->
          <template v-if="caja.cerrada && !editandoCierre">
            <q-markup-table dense flat bordered class="full-width q-mb-sm">
              <tbody>
                <tr>
                  <td class="text-grey-7">Efectivo declarado</td>
                  <td class="text-right text-weight-bold">{{ money(caja.cierre?.monto) }} Bs</td>
                </tr>
                <tr>
                  <td class="text-grey-7">Diferencia contra el sistema</td>
                  <td class="text-right">
                    <q-badge :color="Number(caja.cierre?.diferencia) === 0 ? 'positive'
                      : (Number(caja.cierre?.diferencia) > 0 ? 'blue-7' : 'negative')">
                      {{ money(caja.cierre?.diferencia) }} Bs
                    </q-badge>
                  </td>
                </tr>
                <tr>
                  <td class="text-grey-7">Cerrada el</td>
                  <td class="text-right">{{ formatFecha(caja.cierre?.fecha_hora) }}</td>
                </tr>
                <tr v-if="caja.cierre?.comentario">
                  <td class="text-grey-7">Comentario</td>
                  <td class="text-right">{{ caja.cierre.comentario }}</td>
                </tr>
                <tr v-if="caja.cierre?.modificado_en">
                  <td class="text-grey-7">Modificado el</td>
                  <td class="text-right">{{ formatFecha(caja.cierre.modificado_en) }}</td>
                </tr>
              </tbody>
            </q-markup-table>

            <q-banner dense rounded :class="caja.cierre?.puede_modificar
              ? 'bg-blue-1 text-blue-10' : 'bg-grey-3 text-grey-8'">
              <template v-slot:avatar>
                <q-icon :name="caja.cierre?.puede_modificar ? 'edit_note' : 'lock'" />
              </template>
              {{ caja.cierre?.puede_modificar
                ? 'Puede corregir este cierre una sola vez.'
                : 'Este cierre ya fue corregido una vez y no admite más cambios.' }}
            </q-banner>
          </template>

          <!-- Cerrar por primera vez, o la única corrección -->
          <q-form v-else @submit.prevent="guardarCierre">
            <q-input v-model.number="cierreForm.monto" label="Efectivo que entrega (Bs) *"
                     dense outlined type="number" step="0.01" min="0" autofocus
                     input-class="text-right"
                     hint="Cuente el efectivo y escriba el monto"
                     :rules="[v => v !== null && v !== '' || 'Requerido']" />
            <q-input v-model="cierreForm.comentario" label="Comentario" dense outlined
                     type="textarea" rows="2" v-uppercase />
            <q-banner v-if="!editandoCierre" dense rounded class="bg-orange-1 text-orange-10 q-mt-sm">
              <template v-slot:avatar><q-icon name="warning" color="orange-9" /></template>
              Al cerrar la caja ya no podrá registrar ni cobrar ventas hasta mañana.
            </q-banner>
          </q-form>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-sm">
          <q-btn flat dense label="Cerrar" no-caps v-close-popup />
          <q-btn v-if="caja.cerrada && !editandoCierre && caja.cierre?.puede_modificar"
                 dense padding="4px 14px" color="primary" icon="edit" label="Modificar" no-caps
                 @click="editarCierre" />
          <q-btn v-if="!caja.cerrada || editandoCierre"
                 dense padding="4px 14px" color="teal-8" icon="lock_clock" no-caps
                 :label="editandoCierre ? 'Guardar corrección' : 'Cerrar caja'"
                 :loading="guardandoCierre" @click="guardarCierre" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirVenta } from '../../../addons/ventaPrint'
import { formatBoliviaDate, formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Ventas'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Ventas'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Ventas'))
// Montos acumulados de caja (tarjetas de totales): permiso aparte.
const canMontos   = computed(() => proxy.$store.hasPermission('Ver Montos Caja'))

const canCerrarCaja = computed(() => proxy.$store.hasPermission('Cerrar Caja'))

const resumen = ref({ total_ventas: 0, total_pendientes: 0, total_anuladas: 0, cantidad: 0 })

// ── Cierre de caja del día ─────────────────────────────────────
// ver_montos: el backend solo manda el total del sistema a quien puede verlo.
const caja = ref({ fecha: '', cerrada: false, ver_montos: false, cierre: null, total_sistema: null, cantidad_ventas: null })
const dialogCierre = ref(false)
const editandoCierre = ref(false)
const guardandoCierre = ref(false)
const cierreForm = ref({ monto: null, comentario: '' })

async function cargarCaja () {
  if (!canCerrarCaja.value) return
  try {
    const { data } = await proxy.$axios.get('cierres-caja/estado')
    caja.value = data
  } catch {
    // Sin estado de caja la pantalla sigue siendo usable: el backend igual bloquea.
  }
}

function abrirCierre () {
  editandoCierre.value = false
  // Arranca en cero: el cajero cuenta el efectivo y escribe lo que entrega.
  cierreForm.value = { monto: 0, comentario: '' }
  dialogCierre.value = true
  cargarCaja()
}

function editarCierre () {
  editandoCierre.value = true
  cierreForm.value = {
    monto: Number(caja.value.cierre?.monto || 0),
    comentario: caja.value.cierre?.comentario || '',
  }
}

async function guardarCierre () {
  if (cierreForm.value.monto === null || cierreForm.value.monto === '') {
    proxy.$alert.error('Indique el efectivo contado en caja')
    return
  }
  guardandoCierre.value = true
  try {
    if (editandoCierre.value) {
      const { data } = await proxy.$axios.put('cierres-caja/' + caja.value.cierre.id, cierreForm.value)
      proxy.$alert.success(data.message || 'Cierre modificado')
    } else {
      const { data } = await proxy.$axios.post('cierres-caja', cierreForm.value)
      // Si ya existía, el backend devuelve el mismo cierre con el mismo monto.
      proxy.$alert.success(data.message || 'Caja cerrada')
    }
    editandoCierre.value = false
    await cargarCaja()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo guardar el cierre')
  } finally {
    guardandoCierre.value = false
  }
}

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return formatBoliviaDateTime(v) }
function formatSoloFecha (v) { return formatBoliviaDate(v, '—') }

function estadoLabel (venta) {
  return venta?.estado === 'PENDIENTE' && venta?.fecha_hora_cobro ? 'COBRADO' : venta?.estado
}

function estadoColor (venta) {
  if (venta?.estado === 'ANULADO') return { bg: 'red-1', text: 'negative' }
  if (venta?.estado === 'PENDIENTE' && !venta?.fecha_hora_cobro) return { bg: 'orange-1', text: 'orange-9' }
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
function hoyBolivia () { return formatBoliviaDate(new Date()) }

const filtro = ref({
  fecha_inicio: hoyBolivia(),
  fecha_fin: hoyBolivia(),
  hora_inicio: '00:00',
  hora_fin: '23:59',
  paciente_id: null,
  estado: null,
})

const rangoFiltro = computed(() => {
  const f = filtro.value
  if (!f.fecha_inicio && !f.fecha_fin) return 'Mostrando todas las ventas'
  const desde = f.fecha_inicio ? `${f.fecha_inicio} ${f.hora_inicio || '00:00'}` : 'la primera venta'
  const hasta = f.fecha_fin ? `${f.fecha_fin} ${f.hora_fin || '23:59'}` : 'la última venta'
  return `Mostrando ventas desde ${desde} hasta ${hasta}`
})

function filtrarHoy () {
  filtro.value.fecha_inicio = hoyBolivia()
  filtro.value.fecha_fin = hoyBolivia()
  filtro.value.hora_inicio = '00:00'
  filtro.value.hora_fin = '23:59'
  pageVentas.value = 1
  loadVentas()
}

function verTodo () {
  filtro.value.fecha_inicio = ''
  filtro.value.fecha_fin = ''
  filtro.value.hora_inicio = ''
  filtro.value.hora_fin = ''
  pageVentas.value = 1
  loadVentas()
}

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
        hora_inicio: filtro.value.hora_inicio,
        hora_fin: filtro.value.hora_fin,
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
  cargarCaja()
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

/* La tabla mantiene su forma mientras carga: alto reservado para el spinner
   y anchos de columna fijos (no se recalculan con el contenido de las filas). */
/* Alto de una página completa (15 filas + cabecera): así tampoco cambia
   al pasar de una página llena a una con pocas filas. */
.tabla-ventas-wrap {
  position: relative;
  min-height: 476px;
}

.tabla-ventas :deep(table) {
  table-layout: fixed;
  width: 100%;
  /* Suma de las columnas fijas + un mínimo para Cliente/Paciente:
     en pantallas angostas se desplaza en horizontal en vez de aplastarse. */
  min-width: 1010px;
}

.tabla-ventas :deep(td) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
