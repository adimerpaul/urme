<template>
  <q-page class="q-pa-md">
    <div v-if="proxy.$store.isLogged && !canVer" class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-col-gutter-sm q-mb-md">
        <div class="col">
          <div class="text-h5 text-weight-bold">Laboratorio</div>
          <div class="text-body2 text-grey-6">Productos del tipo LABORATORIOS y configuración de resultados</div>
        </div>
        <q-badge color="primary" class="q-pa-sm">{{ pagination.rowsNumber }} productos</q-badge>
      </div>

      <q-card flat bordered>
        <q-card-section class="row items-center q-col-gutter-sm q-pa-sm">
          <div class="col">
            <q-input v-model="search" dense outlined clearable debounce="400"
                     placeholder="Buscar por nombre o código" @update:model-value="buscar">
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </div>
          <q-btn flat round color="primary" icon="refresh" :loading="loading" @click="cargar">
            <q-tooltip>Actualizar</q-tooltip>
          </q-btn>
        </q-card-section>
        <q-separator />

        <q-table v-model:pagination="pagination" flat dense row-key="id"
                 :rows="productos" :columns="columns" :loading="loading"
                 :rows-per-page-options="[10, 20, 50]"
                 no-data-label="No existen productos del tipo LABORATORIOS"
                 @request="onRequest">
          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn-dropdown
                dense
                unelevated
                color="primary"
                label="Opciones"
                no-caps
                size="sm"
              >
                <q-list dense style="min-width: 180px">
                  <q-item
                    v-if="canEditar"
                    clickable
                    v-close-popup
                    @click="editarProducto(props.row)"
                  >
                    <q-item-section avatar>
                      <q-icon name="edit" color="primary" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Modificar</q-item-label>
                    </q-item-section>
                  </q-item>

                  <q-item
                    clickable
                    v-close-popup
                    @click="administrar(props.row)"
                  >
                    <q-item-section avatar>
                      <q-icon name="settings" color="teal" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label>Administrar</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
          <template #body-cell-codigo="props">
            <q-td :props="props"><q-badge outline color="primary">{{ props.row.codigo || 'S/C' }}</q-badge></q-td>
          </template>
          <template #body-cell-nombre="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.nombre }}</div>
              <div v-if="props.row.descripcion" class="text-caption text-grey-6">{{ props.row.descripcion }}</div>
            </q-td>
          </template>
          <template #body-cell-tipo="props">
            <q-td :props="props">
              <q-badge :color="props.row.tipo_producto?.color || 'primary'">
                {{ props.row.tipo_producto?.nombre || 'LABORATORIOS' }}
              </q-badge>
            </q-td>
          </template>
          <template #body-cell-precio="props">
            <q-td :props="props" class="text-weight-bold">Bs {{ money(props.row.precio) }}</q-td>
          </template>
        </q-table>
      </q-card>
    </template>

    <q-dialog v-model="productoDialog" persistent>
      <q-card style="width:min(94vw,620px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="edit" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Modificar producto de laboratorio</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarProducto">
          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-input v-model="productoForm.codigo" v-uppercase dense outlined label="Código" />
            </div>
            <div class="col-12 col-sm-8">
              <q-input v-model="productoForm.nombre" v-uppercase dense outlined label="Nombre *" :rules="[required]" />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model.number="productoForm.precio" dense outlined type="number" min="0" step="0.01"
                       label="Precio (Bs) *" :rules="[required]" />
            </div>
            <div class="col-12">
              <q-input v-model="productoForm.descripcion" v-uppercase dense outlined type="textarea" autogrow label="Descripción" />
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" color="primary" icon="save" label="Guardar" no-caps :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="adminDialog" persistent maximized>
      <q-card>
        <q-bar class="bg-teal text-white">
          <q-icon name="biotech" />
          <div class="text-weight-bold">Administrar: {{ productoAdmin.nombre }}</div>
          <q-space /><q-btn flat dense icon="close" v-close-popup />
        </q-bar>

        <q-card-section class="q-pa-md">
          <q-banner rounded class="bg-blue-1 text-blue-10 q-mb-md">
            <template #avatar><q-icon name="info" color="primary" /></template>
            Los datos y fórmulas configurados aquí serán la base para registrar resultados cuando este laboratorio sea solicitado a un paciente.
          </q-banner>

          <q-card flat bordered class="q-mb-md">
            <q-card-section class="q-pa-sm">
              <div class="row items-center q-mb-sm">
                <div>
                  <div class="row items-center q-gutter-sm">
                    <q-icon name="fact_check" color="primary" size="22px" />
                    <div class="text-subtitle1 text-weight-bold">Datos y rangos de referencia</div>
                    <q-badge color="primary">{{ datos.length }}</q-badge>
                  </div>
                  <div class="text-caption text-grey-7">Nombre, variable técnica, unidad y rangos de referencia.</div>
                </div>
                <q-space />
                <q-btn v-if="canEditar" color="positive" icon="add" label="Nuevo dato" no-caps @click="nuevoDato" />
              </div>
              <q-table flat bordered dense row-key="id" :rows="datos" :columns="datoColumns"
                       :loading="adminLoading" :rows-per-page-options="[0]">
                <template #body-cell-nombre_variable="props">
                  <q-td :props="props">
                    <q-badge outline color="primary" class="text-mono">
                      {{ props.row.nombre_variable }}
                    </q-badge>
                  </q-td>
                </template>
                <template #body-cell-visible="props">
                  <q-td :props="props"><q-badge :color="props.row.visible ? 'positive' : 'grey'">{{ props.row.visible ? 'SÍ' : 'NO' }}</q-badge></q-td>
                </template>
                <template #body-cell-acciones="props">
                  <q-td :props="props">
                    <q-btn v-if="canEditar" flat round dense color="primary" icon="edit" @click="editarDato(props.row)" />
                    <q-btn v-if="canEditar" flat round dense color="negative" icon="delete" @click="eliminarDato(props.row)" />
                  </q-td>
                </template>
              </q-table>
            </q-card-section>
          </q-card>

          <q-card flat bordered>
            <q-card-section class="q-pa-sm">
              <div class="row items-center q-mb-sm">
                <div>
                  <div class="row items-center q-gutter-sm">
                    <q-icon name="functions" color="deep-purple" size="22px" />
                    <div class="text-subtitle1 text-weight-bold">Fórmulas derivadas</div>
                    <q-badge color="deep-purple">{{ formulas.length }}</q-badge>
                  </div>
                  <div class="text-caption text-grey-7">Use las variables de los datos para calcular resultados derivados.</div>
                </div>
                <q-space />
                <q-btn v-if="canEditar" color="deep-purple" icon="add" label="Nueva fórmula" no-caps
                       :disable="!datos.length" @click="nuevaFormula" />
              </div>
              <q-table flat bordered dense row-key="id" :rows="formulas" :columns="formulaColumns"
                       :loading="adminLoading" :rows-per-page-options="[0]">
                <template #body-cell-variable="props">
                  <q-td :props="props"><code>{{ props.row.nombre_variable }}</code></q-td>
                </template>
                <template #body-cell-formula="props">
                  <q-td :props="props"><code class="text-deep-purple">{{ props.row.formula }}</code></q-td>
                </template>
                <template #body-cell-visible="props">
                  <q-td :props="props"><q-badge :color="props.row.visible ? 'positive' : 'grey'">{{ props.row.visible ? 'SÍ' : 'NO' }}</q-badge></q-td>
                </template>
                <template #body-cell-acciones="props">
                  <q-td :props="props">
                    <q-btn v-if="canEditar" flat round dense color="primary" icon="edit" @click="editarFormula(props.row)" />
                    <q-btn v-if="canEditar" flat round dense color="negative" icon="delete" @click="eliminarFormula(props.row)" />
                  </q-td>
                </template>
              </q-table>
            </q-card-section>
          </q-card>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="datoDialog" persistent>
      <q-card style="width:min(94vw,650px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="fact_check" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ datoForm.id ? 'Modificar' : 'Nuevo' }} dato</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarDato">
          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-sm-7">
              <q-input v-model="datoForm.nombre" v-uppercase dense outlined label="Nombre *"
                       :rules="[required]" @update:model-value="actualizarVariableDato" />
            </div>
            <div class="col-12 col-sm-5">
              <q-input v-model="datoForm.nombre_variable" dense outlined label="Variable *"
                       hint="Ej.: glucosa, colesterol_total" :rules="[required, validVariable]" />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="datoForm.unidad" dense outlined label="Unidad" hint="mg/dL, %, U/L..." />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model.number="datoForm.orden" dense outlined type="number" min="0" label="Orden" />
            </div>
            <div class="col-12 col-sm-4"><q-toggle v-model="datoForm.visible" label="Visible" /></div>
            <div class="col-12">
              <q-input v-model="datoForm.rango_referencia" v-uppercase dense outlined type="textarea" autogrow
                       label="Rangos de referencia" hint="Ej.: HOMBRES: 13–17 | MUJERES: 12–15" />
            </div>
            <div class="col-12">
              <q-banner dense rounded class="bg-blue-1 text-blue-10">
                La variable identifica este dato dentro de las fórmulas. Ejemplo:
                <code>hematocrito</code>.
              </q-banner>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" color="primary" icon="save" label="Guardar" no-caps :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="formulaDialog" persistent>
      <q-card style="width:min(94vw,720px)">
        <q-card-section class="row items-center bg-deep-purple text-white q-py-sm">
          <q-icon name="functions" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ formulaForm.id ? 'Modificar' : 'Nueva' }} fórmula</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="guardarFormula">
          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-sm-7">
              <q-input v-model="formulaForm.nombre" v-uppercase dense outlined label="Nombre del resultado"
                       @update:model-value="actualizarVariableFormula" />
            </div>
            <div class="col-12 col-sm-5">
              <q-input v-model="formulaForm.nombre_variable" dense outlined label="Variable resultado *"
                       :rules="[required, validVariable]" />
            </div>
            <div class="col-12">
              <q-input v-model="formulaForm.formula" dense outlined label="Fórmula *"
                       hint="Ej.: colesterol_total - hdl_colesterol" :rules="[required]" />
              <div class="q-mt-xs">
                <span class="text-caption text-grey-7 q-mr-sm">Variables disponibles:</span>
                <q-chip v-for="variable in variablesDisponibles" :key="variable" dense clickable
                        color="deep-purple-1" text-color="deep-purple" @click="insertarVariable(variable)">
                  {{ variable }}
                </q-chip>
              </div>
              <div v-if="formulaError" class="text-negative text-caption">{{ formulaError }}</div>
            </div>
            <div class="col-12">
              <q-banner dense rounded class="bg-deep-purple-1 text-deep-purple-10">
                Ejemplos:
                <code>(hematocrito * 10) / globulos_rojos</code> o
                <code>(neutrofilos * globulos_blancos) / 100</code>.
                Haga clic en las variables disponibles para agregarlas.
              </q-banner>
            </div>
            <div class="col-12 col-sm-4"><q-input v-model="formulaForm.unidad" dense outlined label="Unidad del resultado" /></div>
            <div class="col-12 col-sm-4"><q-input v-model.number="formulaForm.orden" dense outlined type="number" min="0" label="Orden" /></div>
            <div class="col-12 col-sm-4"><q-toggle v-model="formulaForm.visible" label="Visible" /></div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" no-caps v-close-popup />
            <q-btn type="submit" color="deep-purple" icon="save" label="Guardar" no-caps
                   :disable="!!formulaError" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const productos = ref([])
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const productoDialog = ref(false)
const productoForm = ref({})
const adminDialog = ref(false)
const adminLoading = ref(false)
const productoAdmin = ref({})
const datos = ref([])
const formulas = ref([])
const datoDialog = ref(false)
const datoForm = ref({})
const formulaDialog = ref(false)
const formulaForm = ref({})
const formulaError = ref('')
const pagination = ref({ page: 1, rowsPerPage: 20, rowsNumber: 0 })

const columns = [
  { name: 'acciones', label: 'Opciones', field: 'id', align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'nombre', label: 'Producto', field: 'nombre', align: 'left', sortable: true },
  { name: 'tipo', label: 'Tipo', field: row => row.tipo_producto?.nombre, align: 'center' },
  { name: 'precio', label: 'Precio', field: 'precio', align: 'right', sortable: true },
]
const datoColumns = [
  { name: 'acciones', label: 'Opciones', field: 'id', align: 'center' },
  { name: 'orden', label: 'Orden', field: 'orden', align: 'right' },
  { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
  { name: 'nombre_variable', label: 'Variable', field: 'nombre_variable', align: 'left' },
  { name: 'unidad', label: 'Unidad', field: row => row.unidad || '—', align: 'left' },
  { name: 'rango_referencia', label: 'Rangos de referencia', field: row => row.rango_referencia || '—', align: 'left' },
  { name: 'visible', label: 'Visible', field: 'visible', align: 'center' },
]
const formulaColumns = [
  { name: 'acciones', label: 'Opciones', field: 'id', align: 'center' },
  { name: 'orden', label: 'Orden', field: 'orden', align: 'right' },
  { name: 'nombre', label: 'Nombre', field: row => row.nombre || '—', align: 'left' },
  { name: 'variable', label: 'Variable resultado', field: 'nombre_variable', align: 'left' },
  { name: 'formula', label: 'Fórmula', field: 'formula', align: 'left' },
  { name: 'unidad', label: 'Unidad', field: row => row.unidad || '—', align: 'left' },
  { name: 'visible', label: 'Visible', field: 'visible', align: 'center' },
]

const canVer = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canEditar = computed(() => proxy.$store.hasPermission('Editar Productos'))
const variablesDisponibles = computed(() => [
  ...datos.value.map(item => item.nombre_variable),
  ...formulas.value
    .filter(item => item.id !== formulaForm.value.id)
    .map(item => item.nombre_variable),
])

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) cargar()
}, { immediate: true })

watch(() => formulaForm.value.formula, validarFormula)

function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
function validVariable (value) {
  return /^[a-z][a-z0-9_]*$/.test(value || '') || 'Use minúsculas, números y guion bajo'
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function toVariable (value) {
  return (value || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
}
function buscar () {
  pagination.value.page = 1
  cargar()
}
function onRequest ({ pagination: requested }) {
  pagination.value = requested
  cargar()
}

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('productos', {
      params: { tipo: 'LABORATORIOS', q: search.value || undefined, page: pagination.value.page, per_page: pagination.value.rowsPerPage },
    })
    productos.value = data.data || []
    pagination.value = { ...pagination.value, page: data.current_page || 1, rowsPerPage: data.per_page || 20, rowsNumber: data.total || 0 }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los productos')
  } finally {
    loading.value = false
  }
}

function editarProducto (producto) {
  productoForm.value = {
    ...producto,
    precio: Number(producto.precio),
    fabricante_id: producto.fabricante?.id || null,
    unidad_id: producto.unidad?.id || null,
    tipo_producto_id: producto.tipo_producto?.id || null,
  }
  productoDialog.value = true
}
async function guardarProducto () {
  saving.value = true
  try {
    await proxy.$axios.put('productos/' + productoForm.value.id, productoForm.value)
    productoDialog.value = false
    proxy.$alert.success('Producto actualizado')
    await cargar()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo actualizar')
  } finally {
    saving.value = false
  }
}

async function administrar (producto) {
  productoAdmin.value = producto
  adminDialog.value = true
  await cargarConfiguracion()
}
async function cargarConfiguracion () {
  adminLoading.value = true
  try {
    const { data } = await proxy.$axios.get(`productos/${productoAdmin.value.id}/laboratorio-configuracion`)
    datos.value = data.laboratorio_datos || []
    formulas.value = data.laboratorio_formulas || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar la configuración')
  } finally {
    adminLoading.value = false
  }
}

function nuevoDato () {
  datoForm.value = { nombre: '', nombre_variable: '', unidad: '', rango_referencia: '', orden: (datos.value.length + 1) * 10, visible: true }
  datoDialog.value = true
}
function editarDato (dato) {
  datoForm.value = { ...dato }
  datoDialog.value = true
}
function actualizarVariableDato () {
  if (!datoForm.value.id) datoForm.value.nombre_variable = toVariable(datoForm.value.nombre)
}
async function guardarDato () {
  saving.value = true
  try {
    if (datoForm.value.id) {
      await proxy.$axios.put(`producto-laboratorio-datos/${datoForm.value.id}`, datoForm.value)
    } else {
      await proxy.$axios.post(`productos/${productoAdmin.value.id}/laboratorio-datos`, datoForm.value)
    }
    datoDialog.value = false
    proxy.$alert.success('Dato guardado')
    await cargarConfiguracion()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}
function eliminarDato (dato) {
  proxy.$alert.dialog(`¿Eliminar el dato "${dato.nombre}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`producto-laboratorio-datos/${dato.id}`)
      proxy.$alert.success('Dato eliminado')
      await cargarConfiguracion()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}

function nuevaFormula () {
  formulaForm.value = { nombre: '', nombre_variable: '', formula: '', unidad: '', orden: (formulas.value.length + 1) * 10, visible: true }
  formulaError.value = ''
  formulaDialog.value = true
}
function editarFormula (formula) {
  formulaForm.value = { ...formula }
  formulaError.value = ''
  formulaDialog.value = true
}
function actualizarVariableFormula () {
  if (!formulaForm.value.id) formulaForm.value.nombre_variable = toVariable(formulaForm.value.nombre)
}
function insertarVariable (variable) {
  formulaForm.value.formula = `${formulaForm.value.formula || ''} ${variable}`.trim()
}
function validarFormula () {
  const expression = (formulaForm.value.formula || '').trim()
  formulaError.value = ''
  if (!expression) return
  const tokens = expression.replace(/[()]/g, ' ').split(/[\s+\-*/]+/)
    .filter(token => token && !/^\d+(\.\d+)?$/.test(token))
  const unknown = [...new Set(tokens.filter(token => !variablesDisponibles.value.includes(token)))]
  if (unknown.length) formulaError.value = `Variables desconocidas: ${unknown.join(', ')}`
}
async function guardarFormula () {
  validarFormula()
  if (formulaError.value) return
  saving.value = true
  try {
    if (formulaForm.value.id) {
      await proxy.$axios.put(`producto-laboratorio-formulas/${formulaForm.value.id}`, formulaForm.value)
    } else {
      await proxy.$axios.post(`productos/${productoAdmin.value.id}/laboratorio-formulas`, formulaForm.value)
    }
    formulaDialog.value = false
    proxy.$alert.success('Fórmula guardada')
    await cargarConfiguracion()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo guardar')
  } finally {
    saving.value = false
  }
}
function eliminarFormula (formula) {
  proxy.$alert.dialog(`¿Eliminar la fórmula "${formula.nombre || formula.nombre_variable}"?`).onOk(async () => {
    try {
      await proxy.$axios.delete(`producto-laboratorio-formulas/${formula.id}`)
      proxy.$alert.success('Fórmula eliminada')
      await cargarConfiguracion()
    } catch (error) {
      proxy.$alert.error(error.response?.data?.message || 'No se pudo eliminar')
    }
  })
}
function firstValidationError (error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}
</script>
