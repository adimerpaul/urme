<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm"
         style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver pacientes</div>
    </div>

    <template v-else>
      <div class="row items-end q-gutter-sm q-mb-sm">
        <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
               no-caps @click="pacNew" />
        <q-input v-model="filter" label="Buscar" dense outlined style="width:200px"
                 @update:model-value="onFilterChange">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
        <q-select v-model="estadoInternacion" label="Estado internación" dense outlined clearable
                  style="width:180px"
                  :options="[
                    { label: 'Internado', value: 'INTERNADO' },
                    { label: 'Alta', value: 'ALTA' },
                    { label: 'No internado', value: 'NO_INTERNADO' },
                  ]"
                  emit-value map-options @update:model-value="onFilterChange" />
        <q-input v-model="altaDesde" label="Alta desde" dense outlined type="date" style="width:160px"
                 @update:model-value="onFilterChange" />
        <q-input v-model="altaHasta" label="Alta hasta" dense outlined type="date" style="width:160px"
                 @update:model-value="onFilterChange" />
        <q-btn color="primary" label="Actualizar" icon="refresh" no-caps @click="onRefresh" />
      </div>

      <q-table
        :rows="pacientes"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        v-model:pagination="pagination"
        :rows-per-page-options="[10, 20, 50, 100]"
        :loading="loading"
        @request="onRequest"
        @row-click="onRowClick"
      >
        <template v-slot:body-cell-tipo_paciente="props">
          <q-td :props="props">
            <q-badge v-if="props.row.latest_internacion?.tipo_paciente" color="indigo">
              {{ props.row.latest_internacion.tipo_paciente }}
            </q-badge>
            <span v-else>—</span>
          </q-td>
        </template>

        <template v-slot:body-cell-estado_internacion="props">
          <q-td :props="props">
            <q-badge :color="estadoColor(props.row.estado_internacion)">
              {{ estadoLabel(props.row.estado_internacion) }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-fecha_alta="props">
          <q-td :props="props">{{ props.row.latest_internacion?.fecha_alta || '—' }}</q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props" @click.stop>
            <q-btn-dropdown
              v-if="canEditar || canEliminar"
              label="Opciones" no-caps size="10px" dense color="primary"
            >
              <q-list>
                <q-item clickable v-close-popup @click="proxy.$router.push('/pacientes/' + props.row.id)">
                  <q-item-section avatar><q-icon name="visibility" /></q-item-section>
                  <q-item-section><q-item-label>Ver detalle</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEditar" clickable v-close-popup @click="pacEdit(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEliminar" clickable v-close-popup @click="pacDelete(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>

      <!-- DIALOG PACIENTE -->
      <q-dialog v-model="dialogPac" persistent>
        <q-card style="width:min(96vw,480px)">
          <q-card-section class="row items-center bg-primary text-white q-py-sm">
            <q-icon name="badge" size="20px" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ actionPac }} paciente</span>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="dialogPac = false" />
          </q-card-section>
          <q-card-section style="padding:14px 16px">
            <q-form @submit.prevent="pacSave">
              <q-input v-model="pac.nombre_completo" label="Nombre completo *" dense outlined class="q-mb-sm"
                       :rules="[v => !!v || 'Requerido']" v-uppercase />
              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-6">
                  <q-select v-model="pac.sexo" label="Sexo" dense outlined clearable
                            :options="[{label:'Masculino',value:'M'},{label:'Femenino',value:'F'}]"
                            emit-value map-options />
                </div>
                <div class="col-6">
                  <q-input v-model="pac.ci" label="CI" dense outlined v-uppercase />
                </div>
              </div>
              <q-input v-model="pac.estado" label="Estado" dense outlined class="q-mb-sm" v-uppercase />
              <q-input v-model="pac.direccion" label="Dirección" dense outlined class="q-mb-sm" v-uppercase />
              <q-input v-model="pac.telefono" label="Teléfono" dense outlined class="q-mb-md" v-uppercase />
              <div class="row justify-end q-gutter-sm">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogPac = false" />
                <q-btn color="primary" :label="pac.id ? 'Guardar' : 'Crear'"
                       type="submit" no-caps :loading="savingPac" icon-right="save" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

const canVer      = computed(() => proxy.$store.hasPermission('Ver Pacientes'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Pacientes'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Pacientes'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Pacientes'))

const pacientes = ref([])
const loading   = ref(false)
const filter    = ref('')
const estadoInternacion = ref(null)
const altaDesde = ref('')
const altaHasta = ref('')

const pagination = ref({ page: 1, rowsPerPage: 10, rowsNumber: 0 })

const columns = [
  { name: 'actions',            label: 'Acciones',          align: 'center' },
  { name: 'nombre_completo',    label: 'Nombre',            align: 'left', field: 'nombre_completo', sortable: true },
  { name: 'tipo_paciente',      label: 'Tipo paciente',     align: 'left' },
  { name: 'estado_internacion', label: 'Estado internación', align: 'left' },
  { name: 'ci',                 label: 'CI',                align: 'left', field: 'ci' },
  { name: 'sexo',               label: 'Sexo',              align: 'left', field: 'sexo' },
  { name: 'telefono',           label: 'Teléfono',          align: 'left', field: 'telefono' },
  { name: 'fecha_alta',         label: 'Fecha alta',        align: 'left' },
]

function estadoLabel (estado) {
  return { INTERNADO: 'Internado', ALTA: 'Alta', NO_INTERNADO: 'No internado' }[estado] || estado
}
function estadoColor (estado) {
  return { INTERNADO: 'orange', ALTA: 'positive', NO_INTERNADO: 'grey-6' }[estado] || 'grey-6'
}

let timer = null
function onFilterChange () {
  clearTimeout(timer)
  timer = setTimeout(() => { pagination.value.page = 1; fetchPacientes() }, 350)
}

function onRefresh () { fetchPacientes() }

function onRequest (props) {
  pagination.value.page        = props.pagination.page
  pagination.value.rowsPerPage  = props.pagination.rowsPerPage
  fetchPacientes()
}

function onRowClick (evt, row) {
  proxy.$router.push('/pacientes/' + row.id)
}

function fetchPacientes () {
  loading.value = true
  proxy.$axios.get('pacientes', {
    params: {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      q: filter.value,
      estado_internacion: estadoInternacion.value,
      alta_desde: altaDesde.value,
      alta_hasta: altaHasta.value,
    },
  }).then(res => {
    pacientes.value = res.data.data
    pagination.value.rowsNumber = res.data.total
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; fetchPacientes() }
}, { immediate: true })

// ── CRUD Paciente (alta/edición rápida) ───────────────────────
const dialogPac = ref(false)
const savingPac = ref(false)
const actionPac = ref('Nuevo')
const pac       = ref({})

function pacNew ()     { pac.value = { nombre_completo: '', sexo: null, ci: '', estado: '', direccion: '', telefono: '' }; actionPac.value = 'Nuevo';  dialogPac.value = true }
function pacEdit (row) { pac.value = { ...row }; actionPac.value = 'Editar'; dialogPac.value = true }

async function pacSave () {
  savingPac.value = true
  try {
    if (pac.value.id) {
      await proxy.$axios.put('pacientes/' + pac.value.id, pac.value)
      proxy.$alert.success('Paciente actualizado')
    } else {
      await proxy.$axios.post('pacientes', pac.value)
      proxy.$alert.success('Paciente creado')
    }
    dialogPac.value = false
    fetchPacientes()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingPac.value = false
  }
}

function pacDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el paciente?').onOk(() => {
    proxy.$axios.delete('pacientes/' + id).then(() => {
      proxy.$alert.success('Paciente eliminado')
      fetchPacientes()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}
</script>

<style scoped>
:deep(tbody tr) {
  cursor: pointer;
}
</style>
