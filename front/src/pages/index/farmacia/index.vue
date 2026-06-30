<template>
  <q-page class="q-pa-xs">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Productos</div>
            <div class="text-h6 text-teal text-weight-bold">{{ resumen.productos }}</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Fabricantes</div>
            <div class="text-h6 text-deep-orange text-weight-bold">{{ resumen.fabricantes }}</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Unidades</div>
            <div class="text-h6 text-purple text-weight-bold">{{ resumen.unidades }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left"
              active-color="primary" indicator-color="primary"
              class="q-mb-xs">
        <q-tab name="productos"   icon="medication"  label="Productos" />
        <q-tab name="fabricantes" icon="factory"     label="Fabricantes" />
        <q-tab name="unidades"    icon="straighten"  label="Unidades" />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <!-- ══ TAB PRODUCTOS ══════════════════════════════════════════ -->
      <div v-show="tab === 'productos'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Productos farmacia</span>
          <q-space />
          <q-input v-model="filterProd" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterProd">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="prodNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('productos')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('productos')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Código</th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Marca</th>
              <th class="text-left">Fabricante</th>
              <th class="text-left">Unidad</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingProd">
              <td colspan="6" class="text-center q-pa-md">
                <q-spinner color="primary" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!productos.length">
              <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in productos" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown v-if="canEditar || canEliminar"
                                label="Opc." no-caps size="xs" dense color="primary" flat>
                  <q-list dense>
                    <q-item v-if="canEditar" clickable v-close-popup @click="prodEdit(row)">
                      <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="prodDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.codigo || '—' }}</td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.marca || '—' }}</td>
              <td>{{ row.fabricante ? row.fabricante.nombre : '—' }}</td>
              <td>{{ row.unidad ? (row.unidad.abreviatura || row.unidad.nombre) : '—' }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalProd }} | Página {{ pageProd }} de {{ pagesProd }}
          </div>
          <q-pagination v-model="pageProd" :max="pagesProd" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadProductos" />
        </div>
      </div>

      <!-- ══ TAB FABRICANTES ════════════════════════════════════════ -->
      <div v-show="tab === 'fabricantes'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Fabricantes</span>
          <q-space />
          <q-input v-model="filterFab" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterFab">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="fabNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('fabricantes')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('fabricantes')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">País</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingFab">
              <td colspan="3" class="text-center q-pa-md">
                <q-spinner color="deep-orange" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!fabricantes.length">
              <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in fabricantes" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown v-if="canEditar || canEliminar"
                                label="Opc." no-caps size="xs" dense color="deep-orange" flat>
                  <q-list dense>
                    <q-item v-if="canEditar" clickable v-close-popup @click="fabEdit(row)">
                      <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="fabDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.pais || '—' }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalFab }} | Página {{ pageFab }} de {{ pagesFab }}
          </div>
          <q-pagination v-model="pageFab" :max="pagesFab" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadFabricantes" />
        </div>
      </div>

      <!-- ══ TAB UNIDADES ══════════════════════════════════════════ -->
      <div v-show="tab === 'unidades'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Unidades de medida</span>
          <q-space />
          <q-input v-model="filterUnid" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterUnid">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="unidNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('unidades')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('unidades')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Abreviatura</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingUnid">
              <td colspan="3" class="text-center q-pa-md">
                <q-spinner color="purple" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!unidades.length">
              <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in unidades" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown v-if="canEditar || canEliminar"
                                label="Opc." no-caps size="xs" dense color="purple" flat>
                  <q-list dense>
                    <q-item v-if="canEditar" clickable v-close-popup @click="unidEdit(row)">
                      <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="unidDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.abreviatura || '—' }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalUnid }} | Página {{ pageUnid }} de {{ pagesUnid }}
          </div>
          <q-pagination v-model="pageUnid" :max="pagesUnid" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadUnidades" />
        </div>
      </div>

    </template>

    <!-- ═══ DIALOG PRODUCTO ══════════════════════════════════════ -->
    <q-dialog v-model="dialogProd" persistent>
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
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
                <q-select v-model="prod.fabricante_id" label="Fabricante" dense outlined
                          :options="allFabricantes" option-value="id" option-label="nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="deep-orange"
                           @click="fabQuick = true">
                      <q-tooltip>Nuevo fabricante</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.unidad_id" label="Unidad de medida" dense outlined
                          :options="allUnidades" option-value="id"
                          :option-label="u => u.abreviatura ? u.nombre + ' (' + u.abreviatura + ')' : u.nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="purple"
                           @click="unidQuick = true">
                      <q-tooltip>Nueva unidad</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProd = false" />
              <q-btn color="teal" :label="prod.id ? 'Guardar cambios' : 'Crear producto'"
                     type="submit" no-caps :loading="savingProd" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG FABRICANTE -->
    <q-dialog v-model="dialogFab" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-deep-orange text-white q-py-sm">
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
              <q-btn color="deep-orange" :label="fab.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingFab" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG UNIDAD -->
    <q-dialog v-model="dialogUnid" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-purple text-white q-py-sm">
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
              <q-btn color="purple" :label="unid.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingUnid" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick fabricante -->
    <q-dialog v-model="fabQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-deep-orange text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo fabricante rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="fabQuickSave">
            <q-input v-model="fabQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="fabQPais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="fabQuick = false" />
              <q-btn color="deep-orange" label="Crear" type="submit" no-caps :loading="savingFab" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick unidad -->
    <q-dialog v-model="unidQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-purple text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nueva unidad rápida</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="unidQuickSave">
            <q-input v-model="unidQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="unidQAbrev" label="Abreviatura" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="unidQuick = false" />
              <q-btn color="purple" label="Crear" type="submit" no-caps :loading="savingUnid" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

// ── Estado general ─────────────────────────────────────────────
const tab     = ref('productos')
const resumen = ref({ productos: 0, fabricantes: 0, unidades: 0 })

// ── Productos ──────────────────────────────────────────────────
const productos   = ref([])
const loadingProd = ref(false)
const savingProd  = ref(false)
const dialogProd  = ref(false)
const prodAction  = ref('Nuevo')
const filterProd  = ref('')
const pageProd    = ref(1)
const totalProd   = ref(0)
const perProd     = 20
const prod        = ref({})
let timerProd     = null

const pagesProd = computed(() => Math.max(1, Math.ceil(totalProd.value / perProd)))

// ── Fabricantes ────────────────────────────────────────────────
const fabricantes    = ref([])
const allFabricantes = ref([])
const loadingFab     = ref(false)
const savingFab      = ref(false)
const dialogFab      = ref(false)
const fabAction      = ref('Nuevo')
const filterFab      = ref('')
const pageFab        = ref(1)
const totalFab       = ref(0)
const perFab         = 20
const fab            = ref({})
const fabQuick       = ref(false)
const fabQNombre     = ref('')
const fabQPais       = ref('')
let timerFab         = null

const pagesFab = computed(() => Math.max(1, Math.ceil(totalFab.value / perFab)))

// ── Unidades ───────────────────────────────────────────────────
const unidades    = ref([])
const allUnidades = ref([])
const loadingUnid = ref(false)
const savingUnid  = ref(false)
const dialogUnid  = ref(false)
const unidAction  = ref('Nuevo')
const filterUnid  = ref('')
const pageUnid    = ref(1)
const totalUnid   = ref(0)
const perUnid     = 20
const unid        = ref({})
const unidQuick   = ref(false)
const unidQNombre = ref('')
const unidQAbrev  = ref('')
let timerUnid     = null

const pagesUnid = computed(() => Math.max(1, Math.ceil(totalUnid.value / perUnid)))

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadResumen()
  loadProductos()
  loadFabricantes()
  loadUnidades()
  loadAllFabricantes()
  loadAllUnidades()
}

onMounted(() => { if (proxy.$store.isLogged) init() })
watch(() => proxy.$store.isLogged, (val) => { if (val) init() })

// ── Resumen ────────────────────────────────────────────────────
function loadResumen () {
  proxy.$axios.get('farmacia/resumen')
    .then(r => { if (r.data && typeof r.data === 'object') resumen.value = r.data })
    .catch(() => {})
}

// ── Productos ──────────────────────────────────────────────────
function loadProductos () {
  loadingProd.value = true
  proxy.$axios.get('productos', {
    params: { page: pageProd.value, per_page: perProd, q: filterProd.value, tipo: 'FARMACIA' },
  }).then(r => {
    productos.value = r.data.data || []
    totalProd.value = r.data.total || 0
  }).catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al cargar'))
    .finally(() => { loadingProd.value = false })
}

function onFilterProd () {
  clearTimeout(timerProd)
  timerProd = setTimeout(() => { pageProd.value = 1; loadProductos() }, 350)
}

function prodNew () {
  prod.value = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null }
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
    const payload = { ...prod.value, tipo: 'FARMACIA' }
    if (prod.value.id) {
      await proxy.$axios.put('productos/' + prod.value.id, payload)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos', payload)
      proxy.$alert.success('Producto creado')
    }
    dialogProd.value = false
    loadProductos()
    loadResumen()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProd.value = false
  }
}

function prodDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
    proxy.$axios.delete('productos/' + id)
      .then(() => { proxy.$alert.success('Producto eliminado'); loadProductos(); loadResumen() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

// ── Fabricantes ────────────────────────────────────────────────
function loadFabricantes () {
  loadingFab.value = true
  proxy.$axios.get('fabricantes', {
    params: { page: pageFab.value, per_page: perFab, q: filterFab.value },
  }).then(r => {
    fabricantes.value = r.data.data || []
    totalFab.value = r.data.total || 0
  }).catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
    .finally(() => { loadingFab.value = false })
}

function loadAllFabricantes () {
  proxy.$axios.get('fabricantes').then(r => { allFabricantes.value = r.data || [] })
}

function onFilterFab () {
  clearTimeout(timerFab)
  timerFab = setTimeout(() => { pageFab.value = 1; loadFabricantes() }, 350)
}

function fabNew ()     { fab.value = { nombre: '', pais: '' }; fabAction.value = 'Nuevo'; dialogFab.value = true }
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
    loadFabricantes(); loadAllFabricantes(); loadResumen()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingFab.value = false
  }
}

function fabDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el fabricante?').onOk(() => {
    proxy.$axios.delete('fabricantes/' + id)
      .then(() => { proxy.$alert.success('Fabricante eliminado'); loadFabricantes(); loadAllFabricantes(); loadResumen() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function fabQuickSave () {
  savingFab.value = true
  try {
    const res = await proxy.$axios.post('fabricantes', { nombre: fabQNombre.value, pais: fabQPais.value })
    loadAllFabricantes()
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

// ── Unidades ───────────────────────────────────────────────────
function loadUnidades () {
  loadingUnid.value = true
  proxy.$axios.get('unidades', {
    params: { page: pageUnid.value, per_page: perUnid, q: filterUnid.value },
  }).then(r => {
    unidades.value = r.data.data || []
    totalUnid.value = r.data.total || 0
  }).catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
    .finally(() => { loadingUnid.value = false })
}

function loadAllUnidades () {
  proxy.$axios.get('unidades').then(r => { allUnidades.value = r.data || [] })
}

function onFilterUnid () {
  clearTimeout(timerUnid)
  timerUnid = setTimeout(() => { pageUnid.value = 1; loadUnidades() }, 350)
}

function unidNew ()     { unid.value = { nombre: '', abreviatura: '' }; unidAction.value = 'Nuevo'; dialogUnid.value = true }
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
    loadUnidades(); loadAllUnidades(); loadResumen()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingUnid.value = false
  }
}

function unidDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la unidad?').onOk(() => {
    proxy.$axios.delete('unidades/' + id)
      .then(() => { proxy.$alert.success('Unidad eliminada'); loadUnidades(); loadAllUnidades(); loadResumen() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function unidQuickSave () {
  savingUnid.value = true
  try {
    const res = await proxy.$axios.post('unidades', { nombre: unidQNombre.value, abreviatura: unidQAbrev.value })
    loadAllUnidades()
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

// ── Exportar ───────────────────────────────────────────────────
async function exportPdf (recurso) {
  try {
    const params = recurso === 'productos'
      ? { q: filterProd.value, tipo: 'FARMACIA' }
      : recurso === 'fabricantes' ? { q: filterFab.value } : { q: filterUnid.value }
    const res = await proxy.$axios.get(recurso + '/export-pdf', { params, responseType: 'blob' })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (e) {
    proxy.$alert.error('Error al generar PDF')
  }
}

async function exportExcel (recurso) {
  try {
    const params = recurso === 'productos'
      ? { q: filterProd.value, tipo: 'FARMACIA' }
      : recurso === 'fabricantes' ? { q: filterFab.value } : { q: filterUnid.value }
    const res = await proxy.$axios.get(recurso + '/export-excel', { params, responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = recurso + '_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error('Error al generar Excel')
  }
}
</script>
