<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else>
      <q-tabs v-model="tab" dense align="left" active-color="primary" indicator-color="primary"
              class="q-mb-md text-grey-7">
        <q-tab name="productos"   label="Productos"   icon="medication" />
        <q-tab name="fabricantes" label="Fabricantes" icon="factory" />
        <q-tab name="unidades"    label="Unidades"    icon="straighten" />
      </q-tabs>
      <q-separator class="q-mb-md" />

      <!-- ══ TAB PRODUCTOS ════════════════════════════════════════ -->
      <div v-show="tab === 'productos'">
        <q-table :rows="productos" :columns="colProductos" row-key="id"
                 dense flat bordered :rows-per-page-options="[0]"
                 title="Productos" :filter="filterProd" :loading="loadingProd">
          <template v-slot:top-right>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps @click="prodNew" class="q-mr-sm" />
            <q-btn color="primary" label="Actualizar" icon="refresh"
                   no-caps @click="loadProductos" class="q-mr-sm" />
            <q-input v-model="filterProd" label="Buscar" dense outlined style="width:180px">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
          </template>

          <template v-slot:body-cell-tipo="props">
            <q-td :props="props">
              <q-badge color="teal" outline>{{ props.row.tipo }}</q-badge>
            </q-td>
          </template>

          <template v-slot:body-cell-fabricante="props">
            <q-td :props="props">{{ props.row.fabricante?.nombre || '—' }}</q-td>
          </template>

          <template v-slot:body-cell-unidad="props">
            <q-td :props="props">{{ props.row.unidad?.abreviatura || props.row.unidad?.nombre || '—' }}</q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opciones" no-caps size="10px" dense color="primary">
                <q-list>
                  <q-item v-if="canEditar" clickable v-close-popup @click="prodEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="prodDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </div>

      <!-- ══ TAB FABRICANTES ══════════════════════════════════════ -->
      <div v-show="tab === 'fabricantes'">
        <q-table :rows="fabricantes" :columns="colFab" row-key="id"
                 dense flat bordered :rows-per-page-options="[0]"
                 title="Fabricantes" :filter="filterFab" :loading="loadingFab">
          <template v-slot:top-right>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps @click="fabNew" class="q-mr-sm" />
            <q-btn color="primary" label="Actualizar" icon="refresh"
                   no-caps @click="loadFabricantes" class="q-mr-sm" />
            <q-input v-model="filterFab" label="Buscar" dense outlined style="width:180px">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
          </template>
          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opciones" no-caps size="10px" dense color="primary">
                <q-list>
                  <q-item v-if="canEditar" clickable v-close-popup @click="fabEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="fabDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </div>

      <!-- ══ TAB UNIDADES ═════════════════════════════════════════ -->
      <div v-show="tab === 'unidades'">
        <q-table :rows="unidades" :columns="colUnid" row-key="id"
                 dense flat bordered :rows-per-page-options="[0]"
                 title="Unidades de Medida" :filter="filterUnid" :loading="loadingUnid">
          <template v-slot:top-right>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps @click="unidNew" class="q-mr-sm" />
            <q-btn color="primary" label="Actualizar" icon="refresh"
                   no-caps @click="loadUnidades" class="q-mr-sm" />
            <q-input v-model="filterUnid" label="Buscar" dense outlined style="width:180px">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
          </template>
          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opciones" no-caps size="10px" dense color="primary">
                <q-list>
                  <q-item v-if="canEditar" clickable v-close-popup @click="unidEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="unidDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════
         DIALOG PRODUCTO
    ════════════════════════════════════════════════════════════ -->
    <q-dialog v-model="dialogProd" persistent>
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-primary text-white q-pb-sm">
          <q-icon name="medication" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ prodAction }} producto</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogProd = false" />
        </q-card-section>
        <q-card-section style="max-height:75vh;overflow-y:auto;padding:14px 16px">
          <q-form @submit.prevent="prodSave">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-input v-model="prod.codigo" label="Código" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-8">
                <q-input v-model="prod.nombre" label="Nombre *" dense outlined
                         :rules="[v => !!v || 'Requerido']" v-uppercase />
              </div>
              <div class="col-12">
                <q-input v-model="prod.descripcion" label="Descripción" dense outlined
                         type="textarea" rows="2" v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="prod.marca" label="Marca" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.tipo" label="Tipo *" dense outlined
                          :options="tiposOpts" emit-value map-options
                          :rules="[v => !!v || 'Requerido']" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.fabricante_id" label="Fabricante" dense outlined
                          :options="fabricantes" option-value="id" option-label="nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="primary"
                           @click="fabQuick = true">
                      <q-tooltip>Nuevo fabricante</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.unidad_id" label="Unidad de medida" dense outlined
                          :options="unidades" option-value="id"
                          :option-label="u => u.abreviatura ? u.nombre + ' (' + u.abreviatura + ')' : u.nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="primary"
                           @click="unidQuick = true">
                      <q-tooltip>Nueva unidad</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProd = false" />
              <q-btn color="primary" :label="prod.id ? 'Guardar cambios' : 'Crear producto'"
                     type="submit" no-caps :loading="savingProd" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG FABRICANTE -->
    <q-dialog v-model="dialogFab" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-primary text-white q-pb-sm">
          <q-icon name="factory" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ fabAction }} fabricante</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogFab = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="fabSave">
            <q-input v-model="fab.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="fab.pais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogFab = false" />
              <q-btn color="primary" :label="fab.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingFab" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG UNIDAD -->
    <q-dialog v-model="dialogUnid" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-primary text-white q-pb-sm">
          <q-icon name="straighten" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ unidAction }} unidad</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogUnid = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="unidSave">
            <q-input v-model="unid.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="unid.abreviatura" label="Abreviatura (ej: mg, ml, un)"
                     dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogUnid = false" />
              <q-btn color="primary" :label="unid.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingUnid" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- quick-create desde dialog producto -->
    <q-dialog v-model="fabQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo fabricante rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="fabQuickSave">
            <q-input v-model="fabQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="fabQPais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="fabQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingFab" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="unidQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nueva unidad rápida</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="unidQuickSave">
            <q-input v-model="unidQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="unidQAbrev" label="Abreviatura" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="unidQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingUnid" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

// ── Permisos ─────────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

// ── Tab activo ───────────────────────────────────────────────────
const tab = ref('productos')

// ── Tipos disponibles ────────────────────────────────────────────
const tiposOpts = [
  { label: 'FARMACIA',      value: 'FARMACIA' },
  { label: 'LABORATORIO',   value: 'LABORATORIO' },
  { label: 'INSUMOS',       value: 'INSUMOS' },
  { label: 'EQUIPAMIENTO',  value: 'EQUIPAMIENTO' },
]

// ════════════════════════════════════════════════════════════════
// PRODUCTOS
// ════════════════════════════════════════════════════════════════
const productos   = ref([])
const prod        = ref({})
const loadingProd = ref(false)
const savingProd  = ref(false)
const dialogProd  = ref(false)
const prodAction  = ref('Nuevo')
const filterProd  = ref('')

const colProductos = [
  { name: 'actions',     label: 'Acciones',    align: 'center' },
  { name: 'codigo',      label: 'Código',      align: 'left', field: 'codigo',  sortable: true },
  { name: 'nombre',      label: 'Nombre',      align: 'left', field: 'nombre',  sortable: true },
  { name: 'marca',       label: 'Marca',       align: 'left', field: 'marca' },
  { name: 'fabricante',  label: 'Fabricante',  align: 'left' },
  { name: 'unidad',      label: 'Unidad',      align: 'left' },
  { name: 'tipo',        label: 'Tipo',        align: 'left', field: 'tipo' },
]

function loadProductos () {
  loadingProd.value = true
  proxy.$axios.get('productos', { params: { per_page: 500 } })
    .then(r => { productos.value = r.data.data })
    .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al cargar'))
    .finally(() => { loadingProd.value = false })
}

function prodNew () {
  prod.value = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null, tipo: 'FARMACIA' }
  prodAction.value = 'Nuevo'
  dialogProd.value = true
}

function prodEdit (row) {
  prod.value = { ...row, fabricante_id: row.fabricante?.id || null, unidad_id: row.unidad?.id || null }
  prodAction.value = 'Editar'
  dialogProd.value = true
}

async function prodSave () {
  savingProd.value = true
  try {
    if (prod.value.id) {
      await proxy.$axios.put('productos/' + prod.value.id, prod.value)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos', prod.value)
      proxy.$alert.success('Producto creado')
    }
    dialogProd.value = false
    loadProductos()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProd.value = false
  }
}

function prodDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
    proxy.$axios.delete('productos/' + id)
      .then(() => { proxy.$alert.success('Producto eliminado'); loadProductos() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

// ════════════════════════════════════════════════════════════════
// FABRICANTES
// ════════════════════════════════════════════════════════════════
const fabricantes   = ref([])
const fab           = ref({})
const loadingFab    = ref(false)
const savingFab     = ref(false)
const dialogFab     = ref(false)
const fabAction     = ref('Nuevo')
const filterFab     = ref('')
const fabQuick      = ref(false)
const fabQNombre    = ref('')
const fabQPais      = ref('')

const colFab = [
  { name: 'actions', label: 'Acciones', align: 'center' },
  { name: 'nombre',  label: 'Nombre',   align: 'left', field: 'nombre',  sortable: true },
  { name: 'pais',    label: 'País',     align: 'left', field: 'pais' },
]

function loadFabricantes () {
  loadingFab.value = true
  proxy.$axios.get('fabricantes')
    .then(r => { fabricantes.value = r.data })
    .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
    .finally(() => { loadingFab.value = false })
}

function fabNew () { fab.value = { nombre: '', pais: '' }; fabAction.value = 'Nuevo'; dialogFab.value = true }
function fabEdit (row) { fab.value = { ...row }; fabAction.value = 'Editar'; dialogFab.value = true }

async function fabSave () {
  savingFab.value = true
  try {
    if (fab.value.id) {
      await proxy.$axios.put('fabricantes/' + fab.value.id, fab.value)
      proxy.$alert.success('Fabricante actualizado')
    } else {
      await proxy.$axios.post('fabricantes', fab.value)
      proxy.$alert.success('Fabricante creado')
    }
    dialogFab.value = false
    loadFabricantes()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingFab.value = false
  }
}

function fabDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el fabricante?').onOk(() => {
    proxy.$axios.delete('fabricantes/' + id)
      .then(() => { proxy.$alert.success('Fabricante eliminado'); loadFabricantes() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function fabQuickSave () {
  savingFab.value = true
  try {
    const res = await proxy.$axios.post('fabricantes', { nombre: fabQNombre.value, pais: fabQPais.value })
    await loadFabricantes()
    prod.value.fabricante_id = res.data.id
    fabQuick.value = false
    fabQNombre.value = ''
    fabQPais.value = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingFab.value = false
  }
}

// ════════════════════════════════════════════════════════════════
// UNIDADES
// ════════════════════════════════════════════════════════════════
const unidades    = ref([])
const unid        = ref({})
const loadingUnid = ref(false)
const savingUnid  = ref(false)
const dialogUnid  = ref(false)
const unidAction  = ref('Nuevo')
const filterUnid  = ref('')
const unidQuick   = ref(false)
const unidQNombre = ref('')
const unidQAbrev  = ref('')

const colUnid = [
  { name: 'actions',      label: 'Acciones',     align: 'center' },
  { name: 'nombre',       label: 'Nombre',        align: 'left', field: 'nombre',      sortable: true },
  { name: 'abreviatura',  label: 'Abreviatura',   align: 'left', field: 'abreviatura' },
]

function loadUnidades () {
  loadingUnid.value = true
  proxy.$axios.get('unidades')
    .then(r => { unidades.value = r.data })
    .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
    .finally(() => { loadingUnid.value = false })
}

function unidNew () { unid.value = { nombre: '', abreviatura: '' }; unidAction.value = 'Nuevo'; dialogUnid.value = true }
function unidEdit (row) { unid.value = { ...row }; unidAction.value = 'Editar'; dialogUnid.value = true }

async function unidSave () {
  savingUnid.value = true
  try {
    if (unid.value.id) {
      await proxy.$axios.put('unidades/' + unid.value.id, unid.value)
      proxy.$alert.success('Unidad actualizada')
    } else {
      await proxy.$axios.post('unidades', unid.value)
      proxy.$alert.success('Unidad creada')
    }
    dialogUnid.value = false
    loadUnidades()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingUnid.value = false
  }
}

function unidDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la unidad?').onOk(() => {
    proxy.$axios.delete('unidades/' + id)
      .then(() => { proxy.$alert.success('Unidad eliminada'); loadUnidades() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function unidQuickSave () {
  savingUnid.value = true
  try {
    const res = await proxy.$axios.post('unidades', { nombre: unidQNombre.value, abreviatura: unidQAbrev.value })
    await loadUnidades()
    prod.value.unidad_id = res.data.id
    unidQuick.value = false
    unidQNombre.value = ''
    unidQAbrev.value  = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingUnid.value = false
  }
}

// ── Carga inicial ─────────────────────────────────────────────
let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) {
    fetched = true
    loadProductos()
    loadFabricantes()
    loadUnidades()
  }
}, { immediate: true })
</script>
