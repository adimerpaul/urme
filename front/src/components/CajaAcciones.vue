<template>
  <!-- Acciones de caja del cajero: cerrar la caja del día y registrar un gasto.
       Se usan en el historial de ventas y en la pantalla de nueva venta. -->
  <q-btn v-if="canCerrarCaja" rounded outline color="teal-8" icon="lock_clock" class="q-mr-sm"
         :label="caja.cierre ? 'Caja cerrada' : 'Cerrar caja'" no-caps @click="abrirCierre">
    <q-tooltip>
      {{ caja.cierre ? 'Ver el cierre de caja de hoy' : 'Cerrar la caja del día' }}
    </q-tooltip>
  </q-btn>
  <q-btn v-if="canCrear && !soloFarmacia" rounded outline color="deep-orange-8" icon="shopping_bag"
         label="Nuevo gasto" no-caps class="q-mr-sm" :disable="caja.cerrada" @click="abrirGasto">
    <q-tooltip v-if="caja.cerrada">Su caja de hoy ya fue cerrada</q-tooltip>
    <q-tooltip v-else>Registrar una salida de dinero de caja: un refresco, el periódico, un taxi</q-tooltip>
  </q-btn>

  <!-- DIALOG NUEVO GASTO -->
  <q-dialog v-model="dialogGasto" persistent>
    <q-card style="width:min(96vw,420px)">
      <q-card-section class="row items-center bg-deep-orange-8 text-white q-py-sm">
        <q-icon name="shopping_bag" size="20px" class="q-mr-sm" />
        <div>
          <div class="text-subtitle1 text-weight-bold">Nuevo gasto</div>
          <div class="text-caption">Dinero que sale de caja</div>
        </div>
        <q-space />
        <q-btn icon="close" flat round dense color="white" v-close-popup />
      </q-card-section>
      <q-card-section>
        <q-form @submit.prevent="guardarGasto">
          <q-input v-model="gastoForm.descripcion" label="Descripción del gasto *" dense outlined
                   v-uppercase autofocus class="q-mb-sm"
                   hint="Ej.: COMPRA DE REFRESCO, PERIÓDICO, TAXI"
                   :rules="[v => !!(v || '').trim() || 'Requerido']" />
          <q-input v-model.number="gastoForm.monto" label="Monto Bs *" dense outlined type="number"
                   step="0.01" min="0" input-class="text-right" class="q-mb-sm"
                   :rules="[v => Number(v) > 0 || 'Indique el monto del gasto']" />
          <q-input v-model="gastoForm.comentario" label="Comentario" dense outlined
                   type="textarea" rows="2" v-uppercase />
          <div class="row justify-end q-gutter-sm q-mt-md">
            <q-btn flat color="grey-7" label="Cancelar" no-caps v-close-popup />
            <q-btn color="deep-orange-8" label="Registrar gasto" icon-right="save" type="submit"
                   no-caps :loading="guardandoGasto" />
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
            {{ caja.cierre ? 'Cierre de caja de hoy' : 'Cerrar caja' }}
          </div>
          <div class="text-caption">{{ formatSoloFecha(caja.fecha) }} · {{ proxy.$store.user.name }}</div>
        </div>
        <q-space />
        <q-btn icon="close" flat round dense color="white" v-close-popup />
      </q-card-section>

      <q-card-section class="q-pa-sm">
        <!-- El total del sistema es el dinero que se debe entregar: no se
             muestra mientras se declara, solo después de cerrada la caja. -->
        <div v-if="caja.cierre && !editandoCierre" class="row q-col-gutter-xs q-mb-sm">
          <div v-if="verComparacion" class="col-6">
            <q-card flat bordered class="q-pa-xs text-center">
              <div class="text-caption text-grey-6">Ventas del día (sistema)</div>
              <div class="text-subtitle1 text-weight-bold text-primary">
                {{ money(caja.cierre?.monto_sistema) }} Bs
              </div>
            </q-card>
          </div>
          <div :class="verComparacion ? 'col-6' : 'col-12'">
            <q-card flat bordered class="q-pa-xs text-center">
              <div class="text-caption text-grey-6">Cantidad de ventas</div>
              <div class="text-subtitle1 text-weight-bold">
                {{ caja.cierre?.cantidad_ventas }}
              </div>
            </q-card>
          </div>
        </div>

        <!-- Caja ya cerrada: se muestra lo guardado -->
        <template v-if="caja.cierre && !editandoCierre">
          <q-markup-table dense flat bordered class="full-width q-mb-sm">
            <tbody>
              <tr>
                <td class="text-grey-7">Efectivo declarado</td>
                <td class="text-right text-weight-bold">{{ money(caja.cierre?.monto) }} Bs</td>
              </tr>
              <!-- La diferencia contra el sistema es solo para quien tiene 'Ver Montos Caja'. -->
              <tr v-if="verComparacion">
                <td class="text-grey-7">Diferencia contra el sistema</td>
                <td class="text-right">
                  <q-badge :color="diferencia(caja.cierre?.diferencia).color" class="text-weight-bold">
                    <q-icon :name="diferencia(caja.cierre?.diferencia).icono" size="12px" class="q-mr-xs" />
                    {{ diferencia(caja.cierre?.diferencia).monto }} Bs
                    <q-tooltip>{{ diferencia(caja.cierre?.diferencia).titulo }}</q-tooltip>
                  </q-badge>
                </td>
              </tr>
              <tr v-if="cortesContados(caja.cierre?.detalle_efectivo).length">
                <td class="text-grey-7">Conteo</td>
                <td class="text-right text-caption">
                  {{ cortesContados(caja.cierre.detalle_efectivo).map(c => c.cantidad + '×' + c.label).join(' · ') }}
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
              <tr v-if="caja.cierre?.validado">
                <td class="text-grey-7">Validado por</td>
                <td class="text-right">
                  {{ caja.cierre.validado_por?.name || '—' }} · {{ formatFecha(caja.cierre.validado_en) }}
                </td>
              </tr>
            </tbody>
          </q-markup-table>

          <q-banner dense rounded :class="caja.cierre?.puede_modificar
            ? 'bg-blue-1 text-blue-10' : 'bg-grey-3 text-grey-8'">
            <template v-slot:avatar>
              <q-icon :name="caja.cierre?.puede_modificar ? 'edit_note' : 'lock'" />
            </template>
            {{ caja.cierre?.validado
              ? 'Este cierre ya fue validado y no admite cambios.'
              : caja.cierre?.puede_modificar
                ? 'Puede corregir este cierre una sola vez.'
                : 'Este cierre ya fue corregido una vez y no admite más cambios.' }}
          </q-banner>
        </template>

        <!-- Cerrar por primera vez, o la única corrección -->
        <q-form v-else @submit.prevent="guardarCierre">
          <q-input v-model.number="cierreForm.monto" label="Efectivo que entrega (Bs) *"
                   dense outlined type="number" step="0.01" min="0" autofocus
                   input-class="text-right text-weight-bold"
                   :rules="[v => v !== null && v !== '' || 'Requerido']" />

          <!-- Ayuda opcional: al contar por cortes se llena el monto de arriba. -->
          <div class="cortes-ayuda q-mb-sm">
            <div class="row items-center q-mb-xs">
              <span class="text-caption text-grey-7">Ayuda: contar por cortes (opcional)</span>
              <q-space />
              <span v-if="totalEfectivo > 0" class="text-caption text-teal-8 text-weight-bold">
                = {{ money(totalEfectivo) }} Bs
              </span>
              <q-btn v-if="totalEfectivo > 0" flat dense round size="xs" icon="backspace" color="grey-6"
                     class="q-ml-xs" @click="limpiarConteo">
                <q-tooltip>Limpiar conteo</q-tooltip>
              </q-btn>
            </div>
            <div v-for="grupo in gruposCortes" :key="grupo.titulo" class="cortes-fila">
              <div v-for="corte in grupo.cortes" :key="corte.valor" class="corte">
                <div class="corte-label">{{ corte.corto }}</div>
                <input v-model.number="cierreForm.detalle_efectivo[corte.valor]" type="number" min="0" step="1"
                       placeholder="0" @focus="e => e.target.select()" @input="aplicarConteo">
              </div>
            </div>
          </div>
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
        <q-btn v-if="caja.cierre && !editandoCierre && caja.cierre?.puede_modificar"
               dense padding="4px 14px" color="primary" icon="edit" label="Modificar" no-caps
               @click="editarCierre" />
        <q-btn v-if="!caja.cierre || editandoCierre"
               dense padding="4px 14px" color="teal-8" icon="lock_clock" no-caps
               :label="editandoCierre ? 'Guardar corrección' : 'Cerrar caja'"
               :loading="guardandoCierre" @click="guardarCierre" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { formatBoliviaDate, formatBoliviaDateTime } from '../addons/dateTime'
import { imprimirVenta } from '../addons/ventaPrint'

const { proxy } = getCurrentInstance()

const props = defineProps({
  soloFarmacia: { type: Boolean, default: false },
})

// caja: el estado de la caja del día, para que la página bloquee sus ventas.
// gasto: se registró un gasto, la página puede recargar su listado.
const emit = defineEmits(['caja', 'gasto'])

const canCrear      = computed(() => proxy.$store.hasPermission('Crear Ventas'))
const canMontos     = computed(() => proxy.$store.hasPermission('Ver Montos Caja'))
const canCerrarCaja = computed(() => proxy.$store.hasPermission('Cerrar Caja'))

// ── Cierre de caja del día ─────────────────────────────────────
// ver_montos: el backend solo manda el total del sistema a quien puede verlo.
const caja = ref({ fecha: '', cerrada: false, ver_montos: false, cierre: null, total_sistema: null, cantidad_ventas: null })
const dialogCierre = ref(false)
const editandoCierre = ref(false)
const guardandoCierre = ref(false)
const cierreForm = ref({ monto: null, detalle_efectivo: conteoVacio(), comentario: '' })

// La comparación contra el sistema no se muestra tras el primer cierre (así la
// corrección sigue siendo a ciegas): aparece recién cuando el cierre ya no
// admite cambios, es decir, después de la corrección o de la validación.
const verComparacion = computed(() => canMontos.value && caja.value.cierre && !caja.value.cierre.puede_modificar)

// Cortes de billetes y monedas en Bolivia; las claves coinciden con el backend.
const gruposCortes = [
  {
    titulo: 'BILLETES',
    cortes: ['200', '100', '50', '20', '10'].map(v => ({ valor: v, label: v + ' Bs', corto: v })),
  },
  {
    titulo: 'MONEDAS',
    cortes: [
      { valor: '5', label: '5 Bs', corto: '5' },
      { valor: '2', label: '2 Bs', corto: '2' },
      { valor: '1', label: '1 Bs', corto: '1' },
      { valor: '0.5', label: '50 ctvs', corto: '50¢' },
      { valor: '0.2', label: '20 ctvs', corto: '20¢' },
      { valor: '0.1', label: '10 ctvs', corto: '10¢' },
    ],
  },
]
const todosLosCortes = gruposCortes.flatMap(g => g.cortes)

function conteoVacio () {
  return Object.fromEntries(['200', '100', '50', '20', '10', '5', '2', '1', '0.5', '0.2', '0.1'].map(v => [v, '']))
}

// En centavos para no arrastrar errores de punto flotante (0.1 + 0.2).
function centavosCorte (valor, cantidad) {
  return (Number(cantidad) || 0) * Math.round(Number(valor) * 100)
}
const totalEfectivo = computed(() =>
  todosLosCortes.reduce((s, c) => s + centavosCorte(c.valor, cierreForm.value.detalle_efectivo[c.valor]), 0) / 100)

// Solo los cortes con cantidad, para mostrar el cierre guardado.
function cortesContados (detalle) {
  return todosLosCortes
    .map(c => ({ ...c, cantidad: Number(detalle?.[c.valor] || 0) }))
    .filter(c => c.cantidad > 0)
}

// El conteo es solo una ayuda: al escribir una cantidad se llena el monto,
// que el cajero puede seguir corrigiendo a mano.
function aplicarConteo () {
  cierreForm.value.monto = totalEfectivo.value
}
function limpiarConteo () {
  cierreForm.value.detalle_efectivo = conteoVacio()
}

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
  cierreForm.value = { monto: null, detalle_efectivo: conteoVacio(), comentario: '' }
  dialogCierre.value = true
  cargarCaja()
}

function editarCierre () {
  editandoCierre.value = true
  // Parte del conteo guardado (los cierres anteriores a los cortes arrancan en cero).
  cierreForm.value = {
    monto: Number(caja.value.cierre?.monto || 0),
    detalle_efectivo: { ...conteoVacio(), ...(caja.value.cierre?.detalle_efectivo || {}) },
    comentario: caja.value.cierre?.comentario || '',
  }
}

async function guardarCierre () {
  if (cierreForm.value.monto === null || cierreForm.value.monto === '') {
    proxy.$alert.error('Indique el efectivo que entrega')
    return
  }
  const invalido = todosLosCortes.find(c => {
    const v = cierreForm.value.detalle_efectivo[c.valor]
    return v !== '' && v !== null && (!Number.isInteger(Number(v)) || Number(v) < 0)
  })
  if (invalido) {
    proxy.$alert.error('Cantidad inválida en el corte de ' + invalido.label)
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

// ── Nuevo gasto (salida de dinero de caja) ─────────────────────
const dialogGasto = ref(false)
const guardandoGasto = ref(false)
const gastoForm = ref({ descripcion: '', monto: null, comentario: '' })

function abrirGasto () {
  gastoForm.value = { descripcion: '', monto: null, comentario: '' }
  dialogGasto.value = true
}

async function guardarGasto () {
  if (!gastoForm.value.descripcion.trim()) {
    proxy.$alert.error('Indique en qué se gastó')
    return
  }
  if (!(Number(gastoForm.value.monto) > 0)) {
    proxy.$alert.error('Indique el monto del gasto')
    return
  }
  guardandoGasto.value = true
  try {
    const { data } = await proxy.$axios.post('ventas/gasto', gastoForm.value)
    proxy.$alert.success('Gasto registrado')
    dialogGasto.value = false
    emit('gasto')
    imprimirVenta(data)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'No se pudo registrar el gasto')
  } finally {
    guardandoGasto.value = false
  }
}

function money (v) { return Number(v || 0).toFixed(2) }

/**
 * Misma lectura que en Cierres de caja: sobrante en verde con +, faltante en
 * rojo con −, caja cuadrada en gris. El monto va en valor absoluto porque el
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


watch(caja, val => emit('caja', val), { deep: true })
watch(() => proxy.$store.isLogged, (val) => { if (val) cargarCaja() }, { immediate: true })

defineExpose({ cargarCaja })
</script>

<style scoped>
/* ── Ayuda de conteo por cortes (cierre de caja) ─────────────── */
.cortes-ayuda {
  border: 1px dashed #cfd8dc;
  border-radius: 6px;
  padding: 4px 6px 6px;
}
.cortes-fila {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 4px;
}
.cortes-fila + .cortes-fila { margin-top: 4px; }
.corte { text-align: center; }
.corte-label {
  font-size: 10px;
  font-weight: 600;
  color: #546e7a;
  line-height: 14px;
}
.corte input {
  width: 100%;
  height: 24px;
  padding: 0 3px;
  border: 1px solid #cfd8dc;
  border-radius: 4px;
  font-size: 12px;
  text-align: center;
  outline: none;
  -moz-appearance: textfield;
}
.corte input:focus { border-color: #00796b; }
.corte input::-webkit-outer-spin-button,
.corte input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>
