<template>
  <q-page class="q-pa-sm">
    <div class="row items-center q-mb-md">
      <div><div class="text-h6 text-weight-bold">Kardex de reactivos</div><div class="text-caption text-grey-7">Consumo mensual calculado a partir de pruebas de laboratorio</div></div>
    </div>
    <q-form class="row q-col-gutter-sm items-start q-mb-md" @submit="consultar">
      <div class="col-12 col-sm-5">
        <q-select v-model="reactivo" :options="opciones" option-label="nombre" label="Reactivo" outlined dense
                  use-input clearable input-debounce="300" @filter="filtrarReactivos" :rules="[v => !!v || 'Seleccione un reactivo']" />
      </div>
      <div class="col-12 col-sm-3"><q-input v-model="mes" type="month" outlined dense stack-label label="Mes" :rules="[v => /^\d{4}-\d{2}$/.test(v || '') || 'Seleccione un mes']" /></div>
      <div class="col-auto"><q-btn label="Consultar" icon="search" type="submit" color="primary" no-caps :loading="loading" /></div>
      <div class="col-auto"><q-btn label="Imprimir PDF" icon="print" color="teal" no-caps :disable="!datos || cambiado" :loading="printing" @click="imprimir" /></div>
    </q-form>
    <q-banner v-if="cambiado && datos" dense class="bg-amber-1 q-mb-sm">Pulsa Consultar para actualizar el reporte con los filtros seleccionados.</q-banner>
    <template v-if="datos">
      <q-banner dense class="bg-blue-1 text-blue-10 q-mb-sm">{{ datos.nota }}</q-banner>
      <div class="row items-center q-gutter-sm q-mb-sm">
        <b>{{ datos.reactivo.nombre }} · {{ datos.mes }}</b>
        <q-chip>{{ datos.cantidad_pruebas }} pruebas</q-chip>
        <q-chip color="teal-1" text-color="teal-10">Salida calculada: {{ numero(datos.total_salidas) }} {{ datos.reactivo.unidad }}</q-chip>
      </div>
      <q-table flat bordered dense :rows="datos.movimientos" :columns="columns" row-key="clave" :pagination="{ rowsPerPage: 31 }" :rows-per-page-options="[31, 50, 0]" no-data-label="Sin pruebas realizadas para este reactivo en el mes seleccionado">
        <template #body-cell-observaciones="props"><q-td :props="props" style="white-space:normal;min-width:200px;max-width:400px">{{ props.value }}</q-td></template>
      </q-table>
    </template>
    <q-banner v-else class="bg-grey-2">Selecciona un reactivo y un mes para consultar su kardex. Por defecto se muestra el mes pasado.</q-banner>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()
const anterior = new Date()
anterior.setDate(1)
anterior.setMonth(anterior.getMonth() - 1)
const mes = ref(`${anterior.getFullYear()}-${String(anterior.getMonth() + 1).padStart(2, '0')}`)
const reactivo = ref(null)
const opciones = ref([])
const datos = ref(null)
const loading = ref(false)
const printing = ref(false)
const cambiado = computed(() => datos.value && (datos.value.mes !== mes.value || datos.value.reactivo.id !== reactivo.value?.id))
const numero = value => value == null ? 'Sin registro' : Number(value).toLocaleString('es-BO', { maximumFractionDigits: 4 })
const columns = [
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
  { name: 'ingreso', label: 'Ingreso', field: row => numero(row.ingreso), align: 'right' },
  { name: 'marca', label: 'Marca', field: row => row.marca || '-', align: 'left' },
  { name: 'lote', label: 'Lote', field: row => row.lote || '-', align: 'left' },
  { name: 'vencimiento', label: 'Vencimiento', field: row => row.vencimiento || '-', align: 'left' },
  { name: 'salida', label: 'Salida calculada', field: row => numero(row.salida), align: 'right' },
  { name: 'saldo', label: 'Saldo', field: row => numero(row.saldo), align: 'right' },
  { name: 'responsable', label: 'Registrado por', field: row => row.responsable || 'Sin registro', align: 'left' },
  { name: 'observaciones', label: 'Pruebas realizadas', field: 'observaciones', align: 'left' },
]

async function filtrarReactivos (q, update, abort) {
  try {
    const { data } = await proxy.$axios.get('reactivos', { params: { q, per_page: 100 } })
    update(() => { opciones.value = data.data })
  } catch (error) {
    abort()
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los reactivos')
  }
}

async function consultar () {
  if (!reactivo.value || !mes.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('reactivos-kardex', { params: { reactivo_id: reactivo.value.id, mes: mes.value } })
    data.movimientos = data.movimientos.map((fila, index) => ({ ...fila, clave: index }))
    datos.value = data
  } catch (error) {
    datos.value = null
    proxy.$alert.error(error.response?.data?.message || 'No se pudo consultar el kardex')
  } finally { loading.value = false }
}

async function imprimir () {
  if (!datos.value || cambiado.value) return
  printing.value = true
  try {
    const { data } = await proxy.$axios.get('reactivos-kardex/pdf', {
      params: { reactivo_id: datos.value.reactivo.id, mes: datos.value.mes }, responseType: 'blob',
    })
    const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = `kardex_reactivo_${datos.value.reactivo.id}_${datos.value.mes}.pdf`
    enlace.click()
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    proxy.$alert.error('No se pudo generar el PDF')
  } finally { printing.value = false }
}
</script>
