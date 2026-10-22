<template>
  <q-page class="q-pa-sm cierres-caja">
    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver los cierres de caja</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-mb-xs">
        <div>
          <div class="text-h6 text-weight-bold">Cierres de caja</div>
          <div class="text-caption text-grey-6">Un cierre por usuario y por día</div>
        </div>
        <q-space />
        <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <!-- Rangos rápidos: por defecto la semana en curso -->
      <div class="row q-col-gutter-xs q-mb-xs items-center">
        <div class="col-auto">
          <q-btn-toggle v-model="rango" dense unelevated no-caps toggle-color="primary"
                        color="grey-3" text-color="grey-8" :options="rangos"
                        @update:model-value="aplicarRango" />
        </div>
        <q-space />
        <!-- Sin 'Ver Montos Caja' solo se muestra lo declarado, nunca el
             total del sistema ni la diferencia. -->
        <div class="col-auto text-caption text-grey-7">
          <template v-if="canMontos">
            Sistema: <b>{{ money(totalSistema) }} Bs</b>
            <span class="q-mx-xs">·</span>
          </template>
          Declarado: <b class="text-primary">{{ money(totalDeclarado) }} Bs</b>
          <template v-if="canMontos">
            <span class="q-mx-xs">·</span>
            Diferencia:
            <b :class="diferencia(totalDiferencia).texto">
              <q-icon :name="diferencia(totalDiferencia).icono" size="13px" />
              {{ diferencia(totalDiferencia).monto }} Bs
              <q-tooltip>{{ diferencia(totalDiferencia).titulo }}</q-tooltip>
            </b>
          </template>
        </div>
      </div>

      <div class="row q-col-gutter-xs q-mb-xs items-center">
        <div class="col-auto">
          <q-input v-model="filtro.fecha_inicio" label="Desde" dense outlined type="date"
                   style="width:150px" @update:model-value="fechaManual" />
        </div>
        <div class="col-auto">
          <q-input v-model="filtro.fecha_fin" label="Hasta" dense outlined type="date"
                   style="width:150px" @update:model-value="fechaManual" />
        </div>
        <!-- El filtro por usuario solo aparece si el usuario puede listar usuarios. -->
        <div v-if="usuarios.length" class="col-auto">
          <q-select v-model="filtro.user_id" label="Usuario" dense outlined clearable
                    :options="usuarios" option-value="id" option-label="name"
                    emit-value map-options style="width:220px" @update:model-value="buscar" />
        </div>
        <div class="col-auto">
          <q-select v-model="porPagina" label="Por página" dense outlined
                    :options="[15, 25, 50, 100]" style="width:110px" @update:model-value="buscar" />
        </div>
      </div>

      <q-markup-table dense flat bordered separator="cell" class="full-width tabla-compacta">
        <thead>
          <tr class="bg-grey-1 text-grey-7 text-uppercase">
            <th class="text-center">Opciones</th>
            <th class="text-left">Fecha</th>
            <th class="text-left">Usuario</th>
            <th class="text-right">Ventas del día</th>
            <th v-if="canMontos" class="text-right">Sistema (Bs)</th>
            <th class="text-right">Declarado (Bs)</th>
            <th v-if="canMontos" class="text-right">Diferencia</th>
            <th class="text-left">Cerrado el</th>
            <th class="text-center">Corrección</th>
            <th class="text-left">Validación</th>
            <th class="text-left">Comentario</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="canMontos ? 11 : 9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
          </tr>
          <tr v-else-if="!cierres.length">
            <td :colspan="canMontos ? 11 : 9" class="text-center text-grey-5 q-pa-md">Sin cierres registrados</td>
          </tr>
          <tr v-else v-for="row in cierres" :key="row.id">
            <td class="text-center">
              <q-btn-dropdown label="Opciones" no-caps size="9px" dense rounded unelevated color="primary">
                <q-list dense>
                  <q-item v-if="canValidar && !row.validado" clickable v-close-popup @click="validar(row)">
                    <q-item-section avatar><q-icon name="verified" color="positive" size="18px" /></q-item-section>
                    <q-item-section>
                      <q-item-label class="text-positive">Validar cierre</q-item-label>
                      <q-item-label caption>Lo deja cerrado: ya no admite correcciones</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item v-if="canAutorizar && !row.validado && esHoy(row)" clickable v-close-popup
                          @click="abrirAutorizar(row)">
                    <q-item-section avatar><q-icon name="more_time" color="orange-9" size="18px" /></q-item-section>
                    <q-item-section>
                      <q-item-label class="text-orange-9">Autorizar más ventas</q-item-label>
                      <q-item-label caption>Da tiempo extra para vender con la caja cerrada</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="verVentas(row)">
                    <q-item-section avatar><q-icon name="receipt_long" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Ver todas las ventas</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="imprimirVoucher(row)">
                    <q-item-section avatar><q-icon name="print" color="primary" size="18px" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Imprimir voucher</q-item-label>
                      <q-item-label caption>Comprobante del cierre</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="exportarExcel(row)">
                    <q-item-section avatar><q-icon name="grid_on" color="green-8" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Exportar Excel</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="exportarPdf(row)">
                    <q-item-section avatar><q-icon name="picture_as_pdf" color="red-8" size="18px" /></q-item-section>
                    <q-item-section><q-item-label>Exportar PDF</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td>{{ formatSoloFecha(row.fecha) }}</td>
            <td>{{ row.user?.name || '—' }}</td>
            <td class="text-right">{{ row.cantidad_ventas }}</td>
            <td v-if="canMontos" class="text-right">{{ money(row.monto_sistema) }}</td>
            <td class="text-right text-weight-bold">{{ money(row.monto) }}</td>
            <td v-if="canMontos" class="text-right">
              <q-badge :color="diferencia(row.diferencia).color" class="text-weight-bold">
                <q-icon :name="diferencia(row.diferencia).icono" size="12px" class="q-mr-xs" />
                {{ diferencia(row.diferencia).monto }}
                <q-tooltip>{{ diferencia(row.diferencia).titulo }}</q-tooltip>
              </q-badge>
            </td>
            <td>{{ formatFecha(row.fecha_hora) }}</td>
            <td class="text-center">
              <q-badge v-if="row.modificado_en" color="orange-8">
                {{ formatFecha(row.modificado_en) }}
              </q-badge>
              <span v-else class="text-grey-5">—</span>
            </td>
            <td>
              <template v-if="row.validado">
                <q-badge color="positive" class="text-weight-bold">
                  <q-icon name="verified" size="12px" class="q-mr-xs" />VALIDADO
                </q-badge>
                <div class="text-grey-8">{{ row.validado_por?.name || '—' }}</div>
                <div class="text-grey-6">{{ formatFecha(row.validado_en) }}</div>
              </template>
              <q-badge v-else color="orange-1" text-color="orange-9" class="text-weight-bold">SIN VALIDAR</q-badge>
              <div v-if="row.autorizado_hasta" :class="row.autorizado ? 'text-orange-9 text-weight-bold' : 'text-grey-6'">
                <q-icon name="more_time" size="12px" />
                {{ row.autorizado ? 'Vendiendo hasta' : 'Autorizado hasta' }} {{ formatHora(row.autorizado_hasta) }}
                <q-tooltip>Autorizado por {{ row.autorizado_por?.name || '—' }}</q-tooltip>
              </div>
            </td>
            <td>{{ row.comentario || '—' }}</td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-px-xs">
        <div class="text-caption text-grey-6">
          Total: {{ total }} | Página {{ page }} de {{ paginas }}
        </div>
        <q-pagination v-model="page" :max="paginas" :max-pages="6"
                      boundary-links direction-links size="sm" @update:model-value="cargar" />
      </div>

      <!-- ── Autorizar más ventas con la caja cerrada ── -->
      <q-dialog v-model="dialogAutorizar">
        <q-card style="width:min(96vw,420px)">
          <q-card-section class="row items-center bg-orange-9 text-white q-py-sm">
            <q-icon name="more_time" size="20px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Autorizar más ventas</div>
              <div class="text-caption">{{ cierreAutorizar?.user?.name || '—' }} · {{ formatSoloFecha(cierreAutorizar?.fecha) }}</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" v-close-popup />
          </q-card-section>
          <q-card-section class="q-pa-sm">
            <div class="text-caption text-grey-7 q-mb-xs">¿Por cuánto tiempo puede seguir vendiendo?</div>
            <q-btn-toggle v-model="minutosAutorizar" dense unelevated no-caps spread toggle-color="orange-9"
                          color="grey-3" text-color="grey-8" :options="opcionesMinutos" />
            <q-banner dense rounded class="bg-blue-1 text-blue-10 q-mt-sm">
              <template v-slot:avatar><q-icon name="info" /></template>
              El total del sistema se recalcula con las nuevas ventas y el usuario podrá
              corregir su cierre una vez más para declarar el efectivo final.
            </q-banner>
          </q-card-section>
          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat dense label="Cancelar" no-caps v-close-popup />
            <q-btn dense padding="4px 14px" color="orange-9" icon="more_time" label="Autorizar" no-caps
                   :loading="autorizando" @click="autorizar" />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <!-- ── Todas las ventas del cierre ── -->
      <q-dialog v-model="dialogVentas" :maximized="$q.screen.lt.md">
        <q-card class="ventas-card">
          <q-card-section class="row items-center q-py-sm bg-primary text-white">
            <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Ventas del cierre</div>
              <div class="text-caption">
                {{ cierreSel?.user?.name || '—' }} · {{ formatSoloFecha(cierreSel?.fecha) }}
              </div>
            </div>
            <q-space />
            <q-btn flat dense no-caps color="white" icon="print" label="Voucher"
                   :loading="exportando === 'voucher'" @click="imprimirVoucher(cierreSel, true)" />
            <q-btn flat dense no-caps color="white" icon="grid_on" label="Excel" class="q-ml-xs"
                   :loading="exportando === 'excel'" @click="exportarExcel(cierreSel, true)" />
            <q-btn flat dense no-caps color="white" icon="picture_as_pdf" label="PDF" class="q-mx-xs"
                   :loading="exportando === 'pdf'" @click="exportarPdf(cierreSel, true)" />
            <q-btn icon="close" flat round dense color="white" v-close-popup />
          </q-card-section>

          <q-card-section class="q-pa-sm">
            <!-- Filtro por fecha y hora: hay turnos de media hora y turnos que
                 cruzan la medianoche, que no caben en "el día del cierre". -->
            <div class="row q-col-gutter-xs q-mb-xs items-center">
              <div class="col-auto">
                <q-input v-model="rangoVentas.desde" label="Desde (fecha y hora)" dense outlined
                         type="datetime-local" style="width:200px" />
              </div>
              <div class="col-auto">
                <q-input v-model="rangoVentas.hasta" label="Hasta (fecha y hora)" dense outlined
                         type="datetime-local" style="width:200px" />
              </div>
              <div class="col-auto">
                <q-btn dense unelevated no-caps color="primary" icon="filter_alt" label="Filtrar"
                       padding="3px 10px" :loading="loadingVentas" @click="filtrarVentas" />
              </div>
              <div class="col-auto">
                <q-btn dense flat no-caps color="primary" icon="history" label="24 h antes del cierre"
                       padding="3px 8px" @click="rango24h">
                  <q-tooltip>Para turnos de noche que cierran al día siguiente</q-tooltip>
                </q-btn>
              </div>
              <div class="col-auto">
                <q-btn dense flat no-caps color="grey-8" icon="today" label="Día del cierre"
                       padding="3px 8px" :disable="!rangoAplicado" @click="quitarRango">
                  <q-tooltip>Quitar el filtro: ventas del día del cierre</q-tooltip>
                </q-btn>
              </div>
            </div>

            <q-banner v-if="rangoAplicado && resumenVentas" dense rounded class="bg-orange-1 text-orange-10 q-mb-xs text-caption">
              <template v-slot:avatar><q-icon name="schedule" color="orange-9" /></template>
              Del <b>{{ rangoAplicado.desde }}</b> al <b>{{ rangoAplicado.hasta }}</b> ·
              Ventas ({{ resumenVentas.cantidad_ventas }}): <b>{{ money(resumenVentas.ingresos) }} Bs</b>
              <template v-if="resumenVentas.cantidad_gastos">
                · Gastos ({{ resumenVentas.cantidad_gastos }}): <b>-{{ money(resumenVentas.egresos) }} Bs</b>
              </template>
              · Neto: <b>{{ money(resumenVentas.neto) }} Bs</b>
              <template v-if="canMontos">
                · Declarado − neto:
                <b :class="diferencia(difRango).texto">
                  <q-icon :name="diferencia(difRango).icono" size="13px" />
                  {{ diferencia(difRango).monto }} Bs
                  <q-tooltip>{{ diferencia(difRango).titulo }}</q-tooltip>
                </b>
              </template>
            </q-banner>

            <div class="row q-col-gutter-xs q-mb-xs items-center text-caption text-grey-8">
              <div v-if="canMontos" class="col-auto">Sistema: <b>{{ money(cierreSel?.monto_sistema) }} Bs</b></div>
              <div class="col-auto">{{ canMontos ? '· ' : '' }}Declarado: <b>{{ money(cierreSel?.monto) }} Bs</b></div>
              <div v-if="canMontos" class="col-auto">
                · Diferencia:
                <b :class="diferencia(cierreSel?.diferencia).texto">
                  <q-icon :name="diferencia(cierreSel?.diferencia).icono" size="13px" />
                  {{ diferencia(cierreSel?.diferencia).monto }} Bs
                  <q-tooltip>{{ diferencia(cierreSel?.diferencia).titulo }}</q-tooltip>
                </b>
              </div>
              <q-space />
              <div class="col-auto">
                <q-select v-model="porPaginaVentas" dense outlined label="Por página"
                          :options="[15, 25, 50, 100]" style="width:110px"
                          @update:model-value="cargarVentas(1)" />
              </div>
            </div>

            <q-markup-table dense flat bordered separator="cell" class="full-width tabla-compacta tabla-ventas">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th class="text-left">N°</th>
                  <th class="text-left">Fecha y hora</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-left">Cliente / Paciente</th>
                  <th class="text-left">Detalle</th>
                  <th class="text-center">Estado</th>
                  <th class="text-left">Pago</th>
                  <th class="text-left">Comentario</th>
                  <th class="text-right">Total (Bs)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingVentas">
                  <td colspan="9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
                </tr>
                <tr v-else-if="!ventas.length">
                  <td colspan="9" class="text-center text-grey-5 q-pa-md">
                    {{ rangoAplicado ? 'No hay movimientos en el rango indicado' : 'Este cierre no tiene movimientos' }}
                  </td>
                </tr>
                <tr v-else v-for="v in ventas" :key="v.id">
                  <td>{{ v.id }}</td>
                  <td>{{ formatFecha(v.fecha_hora_cobro || v.fecha_hora) }}</td>
                  <td class="text-center">
                    <q-badge rounded
                             :color="esEgreso(v) ? 'deep-orange-1' : 'blue-1'"
                             :text-color="esEgreso(v) ? 'deep-orange-9' : 'blue-9'"
                             class="text-weight-bold">{{ esEgreso(v) ? 'GASTO' : 'VENTA' }}</q-badge>
                  </td>
                  <td>{{ v.paciente?.nombre_completo || v.cliente || (esEgreso(v) ? 'GASTO DE CAJA' : 'SIN CLIENTE') }}</td>
                  <!-- Cada ítem con su precio; el "*" marca el precio que cambió la vendedora -->
                  <td class="detalle">
                    <div v-for="d in (v.detalles || [])" :key="d.id" :class="{ 'precio-mod': precioModificado(d) }">
                      {{ Number(d.cantidad) }} x {{ d.nombre }} · {{ money(d.precio) }}<b v-if="precioModificado(d)">*</b>
                      <span v-if="precioModificado(d)" class="text-grey-6"> (lista {{ money(d.precio_original) }})</span>
                    </div>
                    <span v-if="!(v.detalles || []).length" class="text-grey-5">—</span>
                  </td>
                  <td class="text-center">
                    <q-badge :color="v.estado === 'ACTIVO' ? 'positive'
                      : (v.estado === 'PENDIENTE' ? 'orange-8' : 'negative')">
                      {{ v.estado }}
                    </q-badge>
                  </td>
                  <td>{{ v.tipo_pago || '—' }}</td>
                  <td class="comentario">{{ v.comentario || '' }}</td>
                  <!-- El gasto se muestra en negativo: es dinero que salió de caja -->
                  <td class="text-right text-weight-bold" :class="{ 'text-deep-orange-8': esEgreso(v) }">
                    {{ esEgreso(v) ? '-' : '' }}{{ money(v.total) }}
                  </td>
                </tr>
              </tbody>
            </q-markup-table>

            <div class="row items-center justify-between q-mt-xs q-px-xs">
              <div class="text-caption text-grey-6">
                <b class="text-negative">*</b> Precio modificado por la vendedora ·
                Total: {{ totalVentas }} | Página {{ pageVentas }} de {{ paginasVentas }}
              </div>
              <q-pagination v-model="pageVentas" :max="paginasVentas" :max-pages="6"
                            boundary-links direction-links size="sm" @update:model-value="cargarVentas()" />
            </div>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { formatBoliviaDate, formatBoliviaDateTime, formatBoliviaTime } from '../../../addons/dateTime'
import { imprimirCierreCaja } from '../../../addons/cierreCajaPrint'

const { proxy } = getCurrentInstance()

const canVer = computed(() => proxy.$store.hasPermission('Ver Cierres Caja'))
// Sin 'Ver Montos Caja' se ve solo el efectivo declarado, no el sistema ni la diferencia.
const canMontos = computed(() => proxy.$store.hasPermission('Ver Montos Caja'))
const canValidar = computed(() => proxy.$store.hasPermission('Validar Cierres Caja'))
const canAutorizar = computed(() => proxy.$store.hasPermission('Autorizar Ventas Caja Cerrada'))

const cierres = ref([])
const usuarios = ref([])
const loading = ref(false)
const page = ref(1)
const total = ref(0)
const porPagina = ref(15)
const filtro = ref({ fecha_inicio: '', fecha_fin: '', user_id: null })

// ── Rango de fechas: la semana en curso es el filtro por defecto ──
const rangos = [
  { label: 'Esta semana', value: 'semana' },
  { label: 'Semana pasada', value: 'semana_pasada' },
  { label: 'Hoy', value: 'hoy' },
  { label: 'Este mes', value: 'mes' },
  { label: 'Todo', value: 'todo' },
]
const rango = ref('semana')

function ymd (fecha) {
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return fecha.getFullYear() + '-' + mes + '-' + dia
}

/** Lunes de la semana a la que pertenece la fecha (semana de lunes a domingo). */
function lunesDe (fecha) {
  const base = new Date(fecha)
  base.setDate(base.getDate() - ((base.getDay() + 6) % 7))
  return base
}

function sumarDias (fecha, dias) {
  const base = new Date(fecha)
  base.setDate(base.getDate() + dias)
  return base
}

function aplicarRango (valor) {
  const hoy = new Date()
  const opcion = valor || rango.value

  if (opcion === 'hoy') {
    filtro.value.fecha_inicio = ymd(hoy)
    filtro.value.fecha_fin = ymd(hoy)
  } else if (opcion === 'semana') {
    const lunes = lunesDe(hoy)
    filtro.value.fecha_inicio = ymd(lunes)
    filtro.value.fecha_fin = ymd(sumarDias(lunes, 6))
  } else if (opcion === 'semana_pasada') {
    const lunes = sumarDias(lunesDe(hoy), -7)
    filtro.value.fecha_inicio = ymd(lunes)
    filtro.value.fecha_fin = ymd(sumarDias(lunes, 6))
  } else if (opcion === 'mes') {
    filtro.value.fecha_inicio = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 1))
    filtro.value.fecha_fin = ymd(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0))
  } else {
    filtro.value.fecha_inicio = ''
    filtro.value.fecha_fin = ''
  }

  buscar()
}

/** Tocar las fechas a mano rompe el rango rápido: pasa a ser un rango libre. */
function fechaManual () {
  rango.value = null
  buscar()
}

const paginas = computed(() => Math.max(1, Math.ceil(total.value / porPagina.value)))
const totalDeclarado = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto || 0), 0))
const totalSistema = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto_sistema || 0), 0))
const totalDiferencia = computed(() => Math.round((totalDeclarado.value - totalSistema.value) * 100) / 100)

function money (v) { return Number(v || 0).toFixed(2) }

/**
 * La diferencia se lee por el signo: sobrante en verde con +, faltante en rojo
 * con −, y caja cuadrada en gris. El monto va en valor absoluto porque el
 * signo ya lo dice el ícono.
 */
function diferencia (valor) {
  const n = Math.round((Number(valor) || 0) * 100) / 100
  if (n > 0) return { icono: 'add', color: 'positive', texto: 'text-positive', titulo: 'Sobrante: se declaró de más', monto: money(n) }
  if (n < 0) return { icono: 'remove', color: 'negative', texto: 'text-negative', titulo: 'Faltante: se declaró de menos', monto: money(-n) }
  return { icono: 'check', color: 'grey-6', texto: 'text-grey-7', titulo: 'Sin diferencia: la caja cuadra', monto: money(0) }
}

function formatFecha (v) { return formatBoliviaDateTime(v) }
function formatSoloFecha (v) { return formatBoliviaDate(v, '—') }
function formatHora (v) { return formatBoliviaTime(v, '—') }

function buscar () {
  page.value = 1
  cargar()
}

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('cierres-caja', {
      params: {
        fecha_inicio: filtro.value.fecha_inicio || undefined,
        fecha_fin: filtro.value.fecha_fin || undefined,
        user_id: filtro.value.user_id || undefined,
        page: page.value,
        per_page: porPagina.value,
      },
    })
    cierres.value = data.data || []
    total.value = data.total || 0
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los cierres')
  } finally {
    loading.value = false
  }
}

async function cargarUsuarios () {
  try {
    const { data } = await proxy.$axios.get('users')
    usuarios.value = data.data || data || []
  } catch {
    // El filtro por usuario es opcional: sin la lista la pantalla sigue funcionando.
  }
}

// ── Validación ────────────────────────────────────────────────────
function validar (row) {
  proxy.$alert.confirm(
    '¿Validar el cierre de ' + (row.user?.name || '—') + ' del ' + formatSoloFecha(row.fecha) +
    '? Una vez validado queda cerrado y ya no se puede corregir.',
  ).onOk(async () => {
    try {
      const { data } = await proxy.$axios.put('cierres-caja/' + row.id + '/validar')
      proxy.$alert.success(data.message || 'Cierre validado')
      cargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo validar el cierre')
    }
  })
}

// ── Autorizar más ventas (solo el cierre de hoy) ──────────────────
const dialogAutorizar = ref(false)
const cierreAutorizar = ref(null)
const minutosAutorizar = ref(30)
const autorizando = ref(false)
const opcionesMinutos = [
  { label: '15 min', value: 15 },
  { label: '30 min', value: 30 },
  { label: '1 hora', value: 60 },
  { label: '2 horas', value: 120 },
  { label: 'Resto del día', value: 720 },
]

function esHoy (row) { return String(row.fecha || '').slice(0, 10) === ymd(new Date()) }

function abrirAutorizar (row) {
  cierreAutorizar.value = row
  minutosAutorizar.value = 30
  dialogAutorizar.value = true
}

async function autorizar () {
  autorizando.value = true
  try {
    const { data } = await proxy.$axios.put('cierres-caja/' + cierreAutorizar.value.id + '/autorizar', {
      minutos: minutosAutorizar.value,
    })
    proxy.$alert.success(data.message || 'Ventas autorizadas')
    dialogAutorizar.value = false
    cargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo autorizar')
  } finally {
    autorizando.value = false
  }
}

// ── Ventas de un cierre ───────────────────────────────────────────
const dialogVentas = ref(false)
const cierreSel = ref(null)
const ventas = ref([])

// Un movimiento de caja puede ser una venta (INGRESO) o un gasto (EGRESO).
function esEgreso (v) { return v?.tipo_movimiento === 'EGRESO' }

/** La vendedora cobró un precio distinto al de lista. */
function precioModificado (d) {
  return d?.precio_original != null && Number(d.precio).toFixed(2) !== Number(d.precio_original).toFixed(2)
}
const loadingVentas = ref(false)
const pageVentas = ref(1)
const totalVentas = ref(0)
const porPaginaVentas = ref(15)
const paginasVentas = computed(() => Math.max(1, Math.ceil(totalVentas.value / porPaginaVentas.value)))

// Rango de fecha y hora para ver las ventas; sin aplicar, se ve el día del cierre.
const rangoVentas = ref({ desde: '', hasta: '' })
const rangoAplicado = ref(null)
const resumenVentas = ref(null)
const difRango = computed(() =>
  Math.round((Number(cierreSel.value?.monto || 0) - Number(resumenVentas.value?.neto || 0)) * 100) / 100)

/** "2026-10-06T22:30" (datetime-local) a partir de una fecha en hora de Bolivia. */
function inputFechaHora (valor) {
  const texto = formatBoliviaDateTime(valor, '')
  return texto ? texto.replace(' ', 'T') : ''
}

/** Los campos arrancan con el día del cierre, de 00:00 hasta la hora en que cerró. */
function rangoDelDia (cierre) {
  const dia = String(cierre?.fecha || '').slice(0, 10)
  return { desde: dia + 'T00:00', hasta: inputFechaHora(cierre?.fecha_hora) || dia + 'T23:59' }
}

function verVentas (row) {
  cierreSel.value = row
  rangoVentas.value = rangoDelDia(row)
  rangoAplicado.value = null
  resumenVentas.value = null
  dialogVentas.value = true
  cargarVentas(1)
}

function filtrarVentas () {
  const { desde, hasta } = rangoVentas.value
  if (!desde || !hasta) {
    proxy.$alert.error('Indique la fecha y hora de inicio y de fin')
    return
  }
  if (hasta < desde) {
    proxy.$alert.error('La fecha y hora "hasta" debe ser posterior a "desde"')
    return
  }
  rangoAplicado.value = { desde: desde.replace('T', ' '), hasta: hasta.replace('T', ' ') }
  cargarVentas(1)
}

/** Turno de noche: las 24 horas anteriores a la hora en que se cerró la caja. */
function rango24h () {
  const hasta = inputFechaHora(cierreSel.value?.fecha_hora)
  if (!hasta) return
  const cierre = new Date(cierreSel.value.fecha_hora)
  rangoVentas.value = { desde: inputFechaHora(new Date(cierre.getTime() - 24 * 3600 * 1000)), hasta }
  filtrarVentas()
}

function quitarRango () {
  rangoVentas.value = rangoDelDia(cierreSel.value)
  rangoAplicado.value = null
  cargarVentas(1)
}

/** Parámetros del rango aplicado, para la lista y las exportaciones del diálogo. */
function paramsRango (conRango) {
  return conRango && rangoAplicado.value ? { ...rangoAplicado.value } : {}
}

async function cargarVentas (pagina) {
  if (!cierreSel.value) return
  if (pagina) pageVentas.value = pagina
  loadingVentas.value = true
  try {
    const { data } = await proxy.$axios.get('cierres-caja/' + cierreSel.value.id + '/ventas', {
      params: { page: pageVentas.value, per_page: porPaginaVentas.value, ...paramsRango(true) },
    })
    cierreSel.value = data.cierre || cierreSel.value
    ventas.value = data.ventas?.data || []
    totalVentas.value = data.ventas?.total || 0
    resumenVentas.value = data.resumen || null
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las ventas del cierre')
  } finally {
    loadingVentas.value = false
  }
}

// ── Exportaciones ─────────────────────────────────────────────────
const exportando = ref(null)

/**
 * Voucher del cierre en media carta. Se piden las ventas del cierre (hasta las
 * 100 que admite el endpoint) para imprimir el detalle y el corte por forma de
 * pago; si el cierre tiene más, el voucher sale con el resumen y avisa que el
 * detalle completo está en el PDF.
 */
async function imprimirVoucher (cierre, conRango = false) {
  if (!cierre) return
  exportando.value = 'voucher'
  try {
    const { data } = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas', {
      params: { page: 1, per_page: 100, ...paramsRango(conRango) },
    })
    imprimirCierreCaja(data.cierre || cierre, data.ventas?.data || [], {
      totalVentas: data.ventas?.total ?? null,
      verMontos: canMontos.value,
      rango: data.rango || null,
      resumen: data.resumen || null,
    })
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo imprimir el voucher del cierre')
  } finally {
    exportando.value = null
  }
}

function nombreArchivo (cierre, extension) {
  return 'cierre_caja_' + String(cierre.fecha || '').slice(0, 10) + '_' + cierre.id + '.' + extension
}

async function exportarExcel (cierre, conRango = false) {
  if (!cierre) return
  exportando.value = 'excel'
  try {
    const res = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas/export-excel', {
      params: paramsRango(conRango),
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = nombreArchivo(cierre, 'xlsx')
    a.click()
    window.URL.revokeObjectURL(url)
  } catch {
    proxy.$alert.error('Error al generar el Excel')
  } finally {
    exportando.value = null
  }
}

async function exportarPdf (cierre, conRango = false) {
  if (!cierre) return
  exportando.value = 'pdf'
  try {
    const res = await proxy.$axios.get('cierres-caja/' + cierre.id + '/ventas/export-pdf', {
      params: paramsRango(conRango),
      responseType: 'blob',
    })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch {
    proxy.$alert.error('Error al generar el PDF')
  } finally {
    exportando.value = null
  }
}

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) {
    aplicarRango('semana')
    cargarUsuarios()
  }
}, { immediate: true })
</script>

<style scoped>
.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}

.ventas-card {
  width: 98vw;
  max-width: 1500px;
}

/* Diálogo de ventas: letra más chica y filas apretadas para ver el detalle */
.tabla-ventas :deep(th),
.tabla-ventas :deep(td) {
  font-size: 10px;
  padding: 1px 4px;
  line-height: 1.25;
  height: auto;
  vertical-align: top;
}
.tabla-ventas .detalle { color: #555; }
.tabla-ventas .precio-mod { color: #c62828; font-weight: 600; }
.tabla-ventas .comentario { color: #8a5a00; font-style: italic; max-width: 260px; white-space: normal; }

.cierres-caja :deep(.q-field--dense .q-field__control),
.cierres-caja :deep(.q-field--dense .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.cierres-caja :deep(.q-field--dense .q-field__native),
.cierres-caja :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
</style>
