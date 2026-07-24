<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">Laboratorios creados</div>
        <div class="text-grey-7">Solicitudes de laboratorio registradas para pacientes.</div>
      </div>
      <q-space />
      <q-btn v-if="canCrear" color="positive" icon="add" label="Crear laboratorio"
             no-caps to="/solicitudes-laboratorio/nueva" />
    </div>

    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row q-col-gutter-sm">
        <div class="col-12 col-md-5">
          <q-input v-model="filters.q" dense outlined clearable debounce="400"
                   label="Buscar código, paciente o CI" @update:model-value="recargar">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
          <q-select v-model="filters.estado" dense outlined clearable
                    :options="estados" label="Estado" @update:model-value="recargar" />
        </div>
        <div class="col-6 col-sm-4 col-md-2">
          <q-input v-model="filters.desde" dense outlined type="date" label="Desde" @update:model-value="recargar" />
        </div>
        <div class="col-6 col-sm-4 col-md-2">
          <q-input v-model="filters.hasta" dense outlined type="date" label="Hasta" @update:model-value="recargar" />
        </div>
      </q-card-section>
    </q-card>

    <q-table ref="tableRef" flat bordered row-key="id" title="Solicitudes"
             :rows="rows" :columns="columns" :loading="loading"
             v-model:pagination="pagination" :rows-per-page-options="[10, 15, 25, 50]"
             @request="onRequest">
      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-badge :color="estadoColor(props.row.estado)">{{ props.row.estado }}</q-badge>
        </q-td>
      </template>
      <template #body-cell-pruebas="props">
        <q-td :props="props">
          <div v-for="item in props.row.laboratorio_items" :key="item.id">{{ item.producto_nombre }}</div>
        </q-td>
      </template>
      <template #body-cell-total="props">
        <q-td :props="props">Bs {{ money(props.row.total) }}</q-td>
      </template>
      <template #body-cell-opciones="props">
        <q-td :props="props">
          <q-btn-dropdown dense no-caps color="primary" label="Opciones">
            <q-list dense>
              <q-item clickable v-close-popup @click="verDetalle(props.row)">
                <q-item-section avatar><q-icon name="visibility" /></q-item-section>
                <q-item-section>Ver detalle</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="imprimir(props.row)">
                <q-item-section avatar><q-icon name="print" color="indigo" /></q-item-section>
                <q-item-section>Imprimir / reimprimir</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="enviarWhatsApp(props.row)">
                <q-item-section avatar><q-icon name="chat" color="positive" /></q-item-section>
                <q-item-section>
                  <q-item-label>Enviar por WhatsApp</q-item-label>
                  <q-item-label caption>{{ props.row.paciente?.telefono || 'PACIENTE SIN TELÉFONO' }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="canEditar && props.row.estado === 'CREADO'" clickable v-close-popup
                      @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" color="primary" /></q-item-section>
                <q-item-section>Modificar</q-item-section>
              </q-item>
              <q-item v-if="canEliminar && props.row.estado === 'CREADO'" clickable v-close-popup
                      @click="eliminar(props.row)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="detalleDialog">
      <q-card style="width:min(94vw,850px);max-width:850px">
        <q-card-section class="row items-center bg-primary text-white">
          <div class="text-h6">{{ detalle?.codigo_solicitud }}</div>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section v-if="detalle">
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6"><strong>Paciente:</strong> {{ detalle.paciente?.nombre_completo }}</div>
            <div class="col-12 col-sm-6"><strong>Doctor:</strong> {{ detalle.doctor?.nombre || 'NO ASIGNADO' }}</div>
            <div class="col-12 col-sm-6"><strong>Fecha:</strong> {{ detalle.fecha_solicitud }} {{ detalle.hora_solicitud }}</div>
            <div class="col-12 col-sm-6"><strong>Estado:</strong> {{ detalle.estado }}</div>
          </div>
          <q-list bordered separator>
            <q-expansion-item v-for="item in detalle.laboratorio_items" :key="item.id"
                              icon="biotech" :label="item.producto_nombre"
                              :caption="`${item.resultados?.length || 0} datos · Bs ${money(item.precio)}`">
              <q-card>
                <q-card-section>
                  <div v-for="resultado in item.resultados" :key="resultado.id" class="q-py-xs">
                    {{ resultado.nombre }}
                    <strong class="q-ml-sm">{{ resultado.valor || '-' }} {{ resultado.unidad || '' }}</strong>
                    <span class="text-grey-7">· Ref.: {{ resultado.rango_referencia || '-' }}</span>
                  </div>
                </q-card-section>
              </q-card>
            </q-expansion-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()
const tableRef = ref(null)
const rows = ref([])
const loading = ref(false)
const detalle = ref(null)
const detalleDialog = ref(false)
const filters = ref({ q: '', estado: null, desde: '', hasta: '' })
const estados = ['CREADO', 'ATENDIENDO', 'ENVIADO_ANALITICA', 'ANALIZADO', 'FINALIZADO']
const pagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const canCrear = computed(() => proxy.$store.hasPermission('Crear Solicitudes Laboratorio'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Solicitudes Laboratorio'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Solicitudes Laboratorio'))

const columns = [
  { name: 'opciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo_solicitud', align: 'left' },
  { name: 'fecha', label: 'Fecha', field: 'fecha_solicitud', align: 'left' },
  { name: 'paciente', label: 'Paciente', field: row => row.paciente?.nombre_completo, align: 'left' },
  { name: 'doctor', label: 'Doctor', field: row => row.doctor?.nombre || 'NO ASIGNADO', align: 'left' },
  { name: 'pruebas', label: 'Laboratorios', field: 'laboratorio_items', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'total', label: 'Total', field: 'total', align: 'right' },
]

function recargar () {
  tableRef.value?.requestServerInteraction()
}
function onRequest ({ pagination: requested }) {
  cargar(requested)
}
async function cargar (requested = pagination.value) {
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio', {
      params: {
        page: requested.page,
        per_page: requested.rowsPerPage,
        ...filters.value,
      },
    })
    rows.value = data.data
    pagination.value = {
      page: data.current_page,
      rowsPerPage: data.per_page,
      rowsNumber: data.total,
    }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las solicitudes')
  } finally {
    loading.value = false
  }
}
async function verDetalle (row) {
  try {
    const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}`)
    detalle.value = data
    detalleDialog.value = true
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar el detalle')
  }
}
function editar (row) {
  proxy.$router.push({ path: '/solicitudes-laboratorio/nueva', query: { id: row.id } })
}
async function imprimir (row) {
  const ventana = window.open('', '_blank')
  try {
    const response = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}/pdf`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    if (ventana) ventana.location.href = url
    else window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    ventana?.close()
    proxy.$alert.error(error.response?.data?.message || 'No se pudo generar el PDF')
  }
}
async function enviarWhatsApp (row) {
  const telefono = normalizarTelefono(row.paciente?.telefono)
  if (!telefono) {
    proxy.$alert.error('El paciente no tiene un número de teléfono válido')
    return
  }

  const ventanaWhatsApp = window.open('', '_blank')
  try {
    const response = await proxy.$axios.get(`solicitudes-laboratorio/${row.id}/pdf`, { responseType: 'blob' })
    const archivo = new Blob([response.data], { type: 'application/pdf' })
    const urlArchivo = URL.createObjectURL(archivo)
    const enlace = document.createElement('a')
    enlace.href = urlArchivo
    enlace.download = `laboratorio_${row.codigo_solicitud}.pdf`
    enlace.click()

    const mensaje = [
      `Clínica URME - Laboratorio Clínico`,
      `Paciente: ${row.paciente?.nombre_completo || ''}`,
      `Informe: ${row.codigo_solicitud}`,
      `Adjuntamos su informe de laboratorio en formato PDF.`,
    ].join('\n')
    const whatsappUrl = `https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`
    if (ventanaWhatsApp) ventanaWhatsApp.location.href = whatsappUrl
    else window.open(whatsappUrl, '_blank')

    setTimeout(() => URL.revokeObjectURL(urlArchivo), 60000)
    proxy.$alert.success('PDF descargado y chat de WhatsApp abierto')
  } catch (error) {
    ventanaWhatsApp?.close()
    proxy.$alert.error(error.response?.data?.message || 'No se pudo preparar el envío por WhatsApp')
  }
}
function normalizarTelefono (value) {
  let numero = String(value || '').replace(/\D/g, '')
  if (!numero || numero === '0') return null
  if (numero.startsWith('00')) numero = numero.slice(2)
  if (numero.length === 8) numero = `591${numero}`
  if (numero.startsWith('0') && numero.length === 9) numero = `591${numero.slice(1)}`
  return numero.length >= 11 && numero.length <= 15 ? numero : null
}
function eliminar (row) {
  proxy.$alert.dialog(`¿Eliminar la solicitud ${row.codigo_solicitud}?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`solicitudes-laboratorio/${row.id}`)
      proxy.$alert.success('Solicitud eliminada')
      recargar()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function estadoColor (estado) {
  return { CREADO: 'grey-7', ATENDIENDO: 'orange', ENVIADO_ANALITICA: 'indigo', ANALIZADO: 'blue', FINALIZADO: 'positive' }[estado] || 'grey'
}

cargar()
</script>
