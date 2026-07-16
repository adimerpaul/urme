<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm"
         style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver doctores</div>
    </div>

    <template v-else>
      <div class="row items-end q-gutter-sm q-mb-sm">
        <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
               no-caps @click="docNew" />
        <q-input v-model="filter" label="Buscar" dense outlined style="width:200px"
                 @update:model-value="onFilterChange">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
        <q-select v-model="estado" label="Estado" dense outlined clearable style="width:140px"
                  :options="['ACTIVO', 'INACTIVO']" @update:model-value="onFilterChange" />
        <q-btn color="primary" label="Actualizar" icon="refresh" no-caps @click="fetchDoctores" />
      </div>

      <q-table
        :rows="doctores"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        v-model:pagination="pagination"
        :rows-per-page-options="[10, 20, 50, 100]"
        :loading="loading"
        @request="onRequest"
      >
        <template v-slot:body-cell-especialidades="props">
          <q-td :props="props">
            <template v-if="props.row.especialidades?.length">
              <q-badge v-for="esp in props.row.especialidades" :key="esp.id"
                       color="teal-1" text-color="primary" rounded class="q-mr-xs text-weight-bold">
                {{ esp.nombre }}
              </q-badge>
            </template>
            <span v-else>—</span>
          </q-td>
        </template>

        <template v-slot:body-cell-estado="props">
          <q-td :props="props">
            <q-badge rounded
                     :color="props.row.estado === 'ACTIVO' ? 'green-1' : 'grey-3'"
                     :text-color="props.row.estado === 'ACTIVO' ? 'positive' : 'grey-7'"
                     class="text-weight-bold">{{ props.row.estado }}</q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props" @click.stop>
            <q-btn-dropdown
              v-if="canEditar || canEliminar"
              label="Opciones" no-caps size="10px" dense color="primary"
            >
              <q-list>
                <q-item v-if="canEditar" clickable v-close-popup @click="docEdit(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEliminar" clickable v-close-popup @click="docDelete(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>

      <!-- DIALOG DOCTOR -->
      <q-dialog v-model="dialogDoc" persistent>
        <q-card style="width:min(96vw,520px)">
          <q-card-section class="row items-center bg-primary text-white q-py-sm">
            <q-icon name="medical_information" size="20px" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ actionDoc }} doctor</span>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="dialogDoc = false" />
          </q-card-section>
          <q-card-section style="padding:14px 16px">
            <q-form @submit.prevent="docSave">
              <q-input v-model="doc.nombre" label="Nombre completo *" dense outlined class="q-mb-sm"
                       :rules="[v => !!v || 'Requerido']" v-uppercase />
              <q-select v-model="doc.especialidad_ids" label="Especialidades" dense outlined class="q-mb-sm"
                        multiple use-chips :options="especialidades"
                        option-value="id" option-label="nombre" emit-value map-options>
                <template v-slot:after>
                  <q-btn flat round dense icon="add" color="primary" @click="espQuick = true">
                    <q-tooltip>Nueva especialidad</q-tooltip>
                  </q-btn>
                </template>
              </q-select>
              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-6">
                  <q-input v-model="doc.ci" label="CI" dense outlined v-uppercase />
                </div>
                <div class="col-6">
                  <q-input v-model="doc.registro" label="Matrícula / Registro" dense outlined v-uppercase />
                </div>
                <div class="col-6">
                  <q-input v-model="doc.telefono" label="Teléfono" dense outlined />
                </div>
                <div class="col-6">
                  <q-input v-model="doc.email" label="Email" dense outlined />
                </div>
                <div class="col-6">
                  <q-select v-model="doc.estado" label="Estado" dense outlined :options="['ACTIVO', 'INACTIVO']" />
                </div>
              </div>
              <div class="row justify-end q-gutter-sm">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogDoc = false" />
                <q-btn color="primary" :label="doc.id ? 'Guardar' : 'Crear'"
                       type="submit" no-caps :loading="savingDoc" icon-right="save" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- Quick especialidad -->
      <q-dialog v-model="espQuick" persistent>
        <q-card style="width:min(96vw,380px)">
          <q-card-section class="bg-primary text-white q-py-sm">
            <span class="text-subtitle2 text-weight-bold">Nueva especialidad</span>
          </q-card-section>
          <q-card-section>
            <q-form @submit.prevent="espQuickSave">
              <q-input v-model="espNombre" label="Nombre *" dense outlined class="q-mb-md"
                       :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
              <div class="row justify-end q-gutter-sm">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="espQuick = false" />
                <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingEsp" />
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

const canVer      = computed(() => proxy.$store.hasPermission('Ver Doctores'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Doctores'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Doctores'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Doctores'))

const doctores = ref([])
const loading  = ref(false)
const filter   = ref('')
const estado   = ref(null)
const especialidades = ref([])

const pagination = ref({ page: 1, rowsPerPage: 10, rowsNumber: 0 })

const columns = [
  { name: 'actions',        label: 'Acciones',       align: 'center' },
  { name: 'nombre',         label: 'Nombre',         align: 'left', field: 'nombre', sortable: true },
  { name: 'especialidades', label: 'Especialidades', align: 'left' },
  { name: 'ci',             label: 'CI',             align: 'left', field: 'ci' },
  { name: 'registro',       label: 'Matrícula',      align: 'left', field: 'registro' },
  { name: 'telefono',       label: 'Teléfono',       align: 'left', field: 'telefono' },
  { name: 'estado',         label: 'Estado',         align: 'center' },
]

let timer = null
function onFilterChange () {
  clearTimeout(timer)
  timer = setTimeout(() => { pagination.value.page = 1; fetchDoctores() }, 350)
}

function onRequest (props) {
  pagination.value.page        = props.pagination.page
  pagination.value.rowsPerPage = props.pagination.rowsPerPage
  fetchDoctores()
}

function fetchDoctores () {
  loading.value = true
  proxy.$axios.get('doctores', {
    params: {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      q: filter.value,
      estado: estado.value,
    },
  }).then(res => {
    doctores.value = res.data.data
    pagination.value.rowsNumber = res.data.total
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

function fetchEspecialidades () {
  proxy.$axios.get('especialidades').then(res => {
    especialidades.value = res.data || []
  }).catch(() => { /* silent */ })
}

let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; fetchDoctores(); fetchEspecialidades() }
}, { immediate: true })

// ── CRUD Doctor ────────────────────────────────────────────────
const dialogDoc = ref(false)
const savingDoc = ref(false)
const actionDoc = ref('Nuevo')
const doc       = ref({})

function docNew () {
  doc.value = { nombre: '', especialidad_ids: [], ci: '', registro: '', telefono: '', email: '', estado: 'ACTIVO' }
  actionDoc.value = 'Nuevo'
  dialogDoc.value = true
}

function docEdit (row) {
  doc.value = { ...row, especialidad_ids: (row.especialidades || []).map(e => e.id) }
  actionDoc.value = 'Editar'
  dialogDoc.value = true
}

async function docSave () {
  savingDoc.value = true
  try {
    if (doc.value.id) {
      await proxy.$axios.put('doctores/' + doc.value.id, doc.value)
      proxy.$alert.success('Doctor actualizado')
    } else {
      await proxy.$axios.post('doctores', doc.value)
      proxy.$alert.success('Doctor creado')
    }
    dialogDoc.value = false
    fetchDoctores()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingDoc.value = false
  }
}

function docDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el doctor?').onOk(() => {
    proxy.$axios.delete('doctores/' + id).then(() => {
      proxy.$alert.success('Doctor eliminado')
      fetchDoctores()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}

// ── Quick especialidad ─────────────────────────────────────────
const espQuick  = ref(false)
const espNombre = ref('')
const savingEsp = ref(false)

async function espQuickSave () {
  savingEsp.value = true
  try {
    const res = await proxy.$axios.post('especialidades', { nombre: espNombre.value })
    fetchEspecialidades()
    if (!doc.value.especialidad_ids.includes(res.data.id)) {
      doc.value.especialidad_ids.push(res.data.id)
    }
    espQuick.value = false
    espNombre.value = ''
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error')
  } finally {
    savingEsp.value = false
  }
}
</script>
