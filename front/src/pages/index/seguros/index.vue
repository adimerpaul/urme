<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm"
         style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver seguros</div>
    </div>

    <template v-else>
      <q-table
        :rows="seguros"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        :rows-per-page-options="[0]"
        title="Seguros"
        :filter="filter"
        :loading="loading"
      >
        <template v-slot:top-right>
          <q-btn v-if="canCrear"
                 color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps @click="seguroNew" class="q-mr-sm" />
          <q-btn color="primary" label="Actualizar" icon="refresh"
                 no-caps @click="segurosGet" class="q-mr-sm" />
          <q-input v-model="filter" label="Buscar" dense outlined style="width:180px">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown
              v-if="canVer || canEditar || canEliminar"
              label="Opciones" no-caps size="10px" dense color="primary"
            >
              <q-list>
                <q-item v-if="canVer" clickable v-close-popup @click="verDetalle(props.row)">
                  <q-item-section avatar><q-icon name="groups" color="primary" /></q-item-section>
                  <q-item-section><q-item-label>Pacientes e internaciones</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEditar" clickable v-close-popup @click="seguroEdit(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEliminar" clickable v-close-popup @click="seguroDelete(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>

      <q-dialog v-model="dialog" persistent>
        <q-card style="width:min(96vw,420px)">
          <q-card-section class="row items-center bg-primary text-white q-py-sm">
            <q-icon name="verified_user" size="20px" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ action }} seguro</span>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="dialog = false" />
          </q-card-section>
          <q-card-section style="padding:14px 16px">
            <q-form @submit.prevent="seguroSave">
              <q-input v-model="seguro.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                       :rules="[v => !!v || 'Requerido']" v-uppercase />
              <q-input v-model="seguro.nit" label="NIT" dense outlined class="q-mb-md" v-uppercase />
              <div class="row justify-end q-gutter-sm">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialog = false" />
                <q-btn color="primary" :label="seguro.id ? 'Guardar' : 'Crear'"
                       type="submit" no-caps :loading="saving" icon-right="save" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <q-dialog v-model="dialogDetalle" maximized>
        <q-card>
          <q-card-section class="row items-center bg-primary text-white q-py-sm">
            <q-icon name="verified_user" size="22px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">{{ detalle.seguro?.nombre }}</div>
              <div class="text-caption">Pacientes e internaciones del seguro</div>
            </div>
            <q-space />
            <q-btn flat dense no-caps icon="print" label="Imprimir" color="white" @click="imprimirDetalle" />
            <q-btn icon="close" flat round dense color="white" @click="dialogDetalle = false" />
          </q-card-section>

          <q-card-section class="q-pa-md">
            <div class="row q-col-gutter-sm q-mb-md">
              <div v-for="item in resumenCards" :key="item.label" class="col-6 col-md-3">
                <q-card flat bordered class="q-pa-md">
                  <div class="text-caption text-grey-7">{{ item.label }}</div>
                  <div class="text-h6 text-weight-bold text-primary">{{ item.value }}</div>
                </q-card>
              </div>
            </div>

            <q-inner-loading :showing="loadingDetalle" color="primary" />
            <q-tabs v-model="tabDetalle" dense align="left" no-caps active-color="primary" indicator-color="primary">
              <q-tab name="pacientes" icon="groups" :label="'Pacientes (' + (detalle.resumen?.cantidad_pacientes || 0) + ')'" />
              <q-tab name="internaciones" icon="local_hospital" :label="'Internaciones (' + (detalle.resumen?.cantidad_internaciones || 0) + ')'" />
            </q-tabs>
            <q-separator />
            <q-tab-panels v-model="tabDetalle" animated>
              <q-tab-panel name="pacientes" class="q-pa-none q-pt-sm">
                <q-table dense flat bordered row-key="id" :rows="detalle.pacientes || []"
                         :columns="pacienteColumns" :rows-per-page-options="[10, 20, 50]"
                         no-data-label="No hay pacientes afiliados actualmente" />
              </q-tab-panel>
              <q-tab-panel name="internaciones" class="q-pa-none q-pt-sm">
                <q-table dense flat bordered row-key="id" :rows="detalle.internaciones || []"
                         :columns="internacionColumns" :rows-per-page-options="[10, 20, 50]"
                         no-data-label="No hay internaciones registradas con este seguro">
                  <template v-slot:body-cell-estado="props">
                    <q-td :props="props">
                      <q-badge :color="props.row.fecha_alta ? 'positive' : 'orange'">
                        {{ props.row.fecha_alta ? 'ALTA' : 'INTERNADO' }}
                      </q-badge>
                    </q-td>
                  </template>
                </q-table>
              </q-tab-panel>
            </q-tab-panels>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirSeguro } from '../../../addons/seguroPrint'

const { proxy } = getCurrentInstance()

const canVer      = computed(() => proxy.$store.hasPermission('Ver Seguros'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Seguros'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Seguros'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Seguros'))

const seguros = ref([])
const seguro  = ref({})
const dialog  = ref(false)
const loading = ref(false)
const saving  = ref(false)
const action  = ref('')
const filter  = ref('')
const dialogDetalle = ref(false)
const loadingDetalle = ref(false)
const tabDetalle = ref('pacientes')
const detalle = ref({ seguro: {}, pacientes: [], internaciones: [], resumen: {} })

const columns = [
  { name: 'actions', label: 'Acciones', align: 'center' },
  { name: 'nombre',  label: 'Nombre',   align: 'left', field: 'nombre', sortable: true },
  { name: 'nit',     label: 'NIT',      align: 'left', field: 'nit',    sortable: true },
]

const pacienteColumns = [
  { name: 'nombre', label: 'Paciente', align: 'left', field: 'nombre_completo', sortable: true },
  { name: 'ci', label: 'CI', align: 'left', field: row => row.ci || '—', sortable: true },
  { name: 'telefono', label: 'Teléfono', align: 'left', field: row => row.telefono || '—' },
]

function totalInternacion (row) {
  return (row.items || []).reduce((sum, item) => sum + Number(item.total || 0), 0)
}

const internacionColumns = [
  { name: 'paciente', label: 'Paciente', align: 'left', field: row => row.paciente?.nombre_completo || '—', sortable: true },
  { name: 'ci', label: 'CI', align: 'left', field: row => row.paciente?.ci || '—' },
  { name: 'fecha_ingreso', label: 'Ingreso', align: 'left', field: row => row.fecha_ingreso || '—', sortable: true },
  { name: 'fecha_alta', label: 'Alta', align: 'left', field: row => row.fecha_alta || '—' },
  { name: 'sala', label: 'Sala', align: 'left', field: row => row.sala || '—' },
  { name: 'estado', label: 'Estado', align: 'center' },
  { name: 'total', label: 'Total Bs', align: 'right', field: row => totalInternacion(row).toFixed(2), sortable: true },
]

const resumenCards = computed(() => [
  { label: 'Pacientes afiliados', value: detalle.value.resumen?.cantidad_pacientes || 0 },
  { label: 'Pacientes internados', value: detalle.value.resumen?.pacientes_internados || 0 },
  { label: 'Internaciones', value: detalle.value.resumen?.cantidad_internaciones || 0 },
  { label: 'Total cargos', value: `${Number(detalle.value.resumen?.total || 0).toFixed(2)} Bs` },
])

let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; segurosGet() }
}, { immediate: true })

function segurosGet () {
  loading.value = true
  proxy.$axios.get('seguros').then(res => {
    seguros.value = res.data
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

function seguroNew ()      { seguro.value = { nombre: '', nit: '' }; action.value = 'Nuevo';  dialog.value = true }
function seguroEdit (row)  { seguro.value = { ...row };              action.value = 'Editar'; dialog.value = true }

async function seguroSave () {
  saving.value = true
  try {
    if (seguro.value.id) {
      await proxy.$axios.put('seguros/' + seguro.value.id, seguro.value)
      proxy.$alert.success('Seguro actualizado')
    } else {
      await proxy.$axios.post('seguros', seguro.value)
      proxy.$alert.success('Seguro creado')
    }
    dialog.value = false
    segurosGet()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    saving.value = false
  }
}

function seguroDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el seguro?').onOk(() => {
    loading.value = true
    proxy.$axios.delete('seguros/' + id).then(() => {
      proxy.$alert.success('Seguro eliminado')
      segurosGet()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    }).finally(() => { loading.value = false })
  })
}

async function verDetalle (row) {
  dialogDetalle.value = true
  loadingDetalle.value = true
  tabDetalle.value = 'pacientes'
  try {
    const { data } = await proxy.$axios.get(`seguros/${row.id}/detalle`)
    detalle.value = data
  } catch (err) {
    dialogDetalle.value = false
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar el detalle del seguro')
  } finally {
    loadingDetalle.value = false
  }
}

function imprimirDetalle () {
  imprimirSeguro(detalle.value)
}
</script>
