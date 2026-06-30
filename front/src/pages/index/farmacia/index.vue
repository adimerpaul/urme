<template>
  <q-page class="q-pa-xs">

    <!-- Sin acceso -->
    <div v-if="$store.isLogged && !$store.hasPermission('Ver Productos')"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
      <pre>{{$store.permissions}}</pre>
      <pre>{{canVer?'true':'false'}}</pre>
    </div>

    <template v-else-if="$store.isLogged">

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Productos</div>
            <div class="text-h6 text-teal text-weight-bold">{{ resumen?.productos ?? 0 }}</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Fabricantes</div>
            <div class="text-h6 text-deep-orange text-weight-bold">{{ resumen?.fabricantes ?? 0 }}</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Unidades</div>
            <div class="text-h6 text-purple text-weight-bold">{{ resumen?.unidades ?? 0 }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left"
              :active-color="tabActiveColor" :indicator-color="tabActiveColor"
              class="q-mb-xs">
        <q-tab name="productos"   icon="medication"  label="Productos" no-caps />
        <q-tab name="fabricantes" icon="factory"     label="Fabricantes" no-caps />
        <q-tab name="unidades"    icon="straighten"  label="Unidades" no-caps />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <!-- ══ TAB PRODUCTOS ════════════════════════════════════════ -->
      <div v-show="tab === 'productos'">
        <q-table
          :rows="productos || []"
          :columns="colProductos || []"
          row-key="id"
          dense flat bordered
          :pagination="paginationProd"
          @request="onRequestProd"
          :loading="loadingProd"
          :rows-per-page-options="[10, 20, 50]"
        >
          <template v-slot:top-left>
            <span class="text-subtitle2 text-grey-7">Productos farmacia</span>
          </template>
          <template v-slot:top-right>
            <q-input v-model="filterProd" label="Buscar" dense outlined clearable
                     style="width:160px" class="q-mr-xs">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps dense @click="prodNew" class="q-mr-xs" />
            <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('productos')"
                   class="q-mr-xs">
              <q-tooltip>Exportar PDF</q-tooltip>
            </q-btn>
            <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('productos')">
              <q-tooltip>Exportar Excel</q-tooltip>
            </q-btn>
          </template>

          <template v-slot:body-cell-fabricante="props">
            <q-td :props="props">{{ props.row.fabricante?.nombre || '—' }}</q-td>
          </template>

          <template v-slot:body-cell-unidad="props">
            <q-td :props="props">
              {{ props.row.unidad?.abreviatura || props.row.unidad?.nombre || '—' }}
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-pa-xs">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opc." no-caps size="xs" dense color="primary" flat>
                <q-list dense>
                  <q-item v-if="canEditar" clickable v-close-popup @click="prodEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="prodDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
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
        <q-table
          :rows="fabricantes || []"
          :columns="colFab || []"
          row-key="id"
          dense flat bordered
          :pagination="paginationFab"
          @request="onRequestFab"
          :loading="loadingFab"
          :rows-per-page-options="[10, 20, 50]"
        >
          <template v-slot:top-left>
            <span class="text-subtitle2 text-grey-7">Fabricantes</span>
          </template>
          <template v-slot:top-right>
            <q-input v-model="filterFab" label="Buscar" dense outlined clearable
                     style="width:160px" class="q-mr-xs">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps dense @click="fabNew" class="q-mr-xs" />
            <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('fabricantes')"
                   class="q-mr-xs">
              <q-tooltip>Exportar PDF</q-tooltip>
            </q-btn>
            <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('fabricantes')">
              <q-tooltip>Exportar Excel</q-tooltip>
            </q-btn>
          </template>
          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-pa-xs">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opc." no-caps size="xs" dense color="deep-orange" flat>
                <q-list dense>
                  <q-item v-if="canEditar" clickable v-close-popup @click="fabEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="fabDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
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
        <q-table
          :rows="unidades || []"
          :columns="colUnid || []"
          row-key="id"
          dense flat bordered
          :pagination="paginationUnid"
          @request="onRequestUnid"
          :loading="loadingUnid"
          :rows-per-page-options="[10, 20, 50]"
        >
          <template v-slot:top-left>
            <span class="text-subtitle2 text-grey-7">Unidades de medida</span>
          </template>
          <template v-slot:top-right>
            <q-input v-model="filterUnid" label="Buscar" dense outlined clearable
                     style="width:160px" class="q-mr-xs">
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
            <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                   no-caps dense @click="unidNew" class="q-mr-xs" />
            <q-btn color="red-7" icon="picture_as_pdf" no-caps dense @click="exportPdf('unidades')"
                   class="q-mr-xs">
              <q-tooltip>Exportar PDF</q-tooltip>
            </q-btn>
            <q-btn color="green-8" icon="table_view" no-caps dense @click="exportExcel('unidades')">
              <q-tooltip>Exportar Excel</q-tooltip>
            </q-btn>
          </template>
          <template v-slot:body-cell-actions="props">
            <q-td :props="props" class="q-pa-xs">
              <q-btn-dropdown v-if="canEditar || canEliminar"
                              label="Opc." no-caps size="xs" dense color="purple" flat>
                <q-list dense>
                  <q-item v-if="canEditar" clickable v-close-popup @click="unidEdit(props.row)">
                    <q-item-section avatar><q-icon name="edit" size="xs" /></q-item-section>
                    <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                  </q-item>
                  <q-item v-if="canEliminar" clickable v-close-popup @click="unidDelete(props.row.id)">
                    <q-item-section avatar><q-icon name="delete" color="negative" size="xs" /></q-item-section>
                    <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
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

<script>
export default {
  name: 'FarmaciaIndex',

  data () {
    return {
      tab: 'productos',
      resumen: { productos: 0, fabricantes: 0, unidades: 0 },

      // ── Productos ─────────────────────────────────────────────
      productos: [],
      loadingProd: false,
      savingProd: false,
      dialogProd: false,
      prodAction: 'Nuevo',
      filterProd: '',
      paginationProd: { page: 1, rowsPerPage: 20, rowsNumber: 0, sortBy: null, descending: false },
      prod: {},

      // ── Fabricantes ───────────────────────────────────────────
      fabricantes: [],
      allFabricantes: [],
      loadingFab: false,
      savingFab: false,
      dialogFab: false,
      fabAction: 'Nuevo',
      filterFab: '',
      paginationFab: { page: 1, rowsPerPage: 20, rowsNumber: 0 },
      fab: {},
      fabQuick: false,
      fabQNombre: '',
      fabQPais: '',

      // ── Unidades ──────────────────────────────────────────────
      unidades: [],
      allUnidades: [],
      loadingUnid: false,
      savingUnid: false,
      dialogUnid: false,
      unidAction: 'Nuevo',
      filterUnid: '',
      paginationUnid: { page: 1, rowsPerPage: 20, rowsNumber: 0 },
      unid: {},
      unidQuick: false,
      unidQNombre: '',
      unidQAbrev: '',

      _timerProd: null,
      _timerFab: null,
      _timerUnid: null,
    }
  },

  computed: {
    canVer ()      { return this.$store.hasPermission('Ver Productos') },
    canCrear ()    { return this.$store.hasPermission('Crear Productos') },
    canEditar ()   { return this.$store.hasPermission('Editar Productos') },
    canEliminar () { return this.$store.hasPermission('Eliminar Productos') },

    tabActiveColor () {
      return { productos: 'teal', fabricantes: 'deep-orange', unidades: 'purple' }[this.tab] || 'primary'
    },

    colProductos () {
      return [
        { name: 'actions',    label: '',           align: 'center', style: 'width:64px' },
        { name: 'codigo',     label: 'Código',     align: 'left',   field: 'codigo',  sortable: false },
        { name: 'nombre',     label: 'Nombre',     align: 'left',   field: 'nombre',  sortable: false },
        { name: 'marca',      label: 'Marca',      align: 'left',   field: 'marca' },
        { name: 'fabricante', label: 'Fabricante', align: 'left' },
        { name: 'unidad',     label: 'Unidad',     align: 'left' },
      ]
    },

    colFab () {
      return [
        { name: 'actions', label: '',       align: 'center', style: 'width:64px' },
        { name: 'nombre',  label: 'Nombre', align: 'left',   field: 'nombre', sortable: false },
        { name: 'pais',    label: 'País',   align: 'left',   field: 'pais' },
      ]
    },

    colUnid () {
      return [
        { name: 'actions',     label: '',            align: 'center', style: 'width:64px' },
        { name: 'nombre',      label: 'Nombre',      align: 'left',   field: 'nombre',     sortable: false },
        { name: 'abreviatura', label: 'Abreviatura', align: 'left',   field: 'abreviatura' },
      ]
    },
  },

  watch: {
    '$store.isLogged' (val) {
      if (val) this.init()
    },
    filterProd () {
      clearTimeout(this._timerProd)
      this._timerProd = setTimeout(() => {
        this.paginationProd.page = 1
        this.loadProductos()
      }, 350)
    },
    filterFab () {
      clearTimeout(this._timerFab)
      this._timerFab = setTimeout(() => {
        this.paginationFab.page = 1
        this.loadFabricantes()
      }, 350)
    },
    filterUnid () {
      clearTimeout(this._timerUnid)
      this._timerUnid = setTimeout(() => {
        this.paginationUnid.page = 1
        this.loadUnidades()
      }, 350)
    },
  },

  mounted () {
    if (this.$store.isLogged) this.init()
  },

  methods: {
    init () {
      this.loadResumen()
      this.loadProductos()
      this.loadFabricantes()
      this.loadUnidades()
      this.loadAllFabricantes()
      this.loadAllUnidades()
    },

    // ── Resumen ──────────────────────────────────────────────────
    loadResumen () {
      this.$axios.get('farmacia/resumen')
        .then(r => { if (r.data && typeof r.data === 'object') this.resumen = r.data })
        .catch(() => {})
    },

    // ── Productos ────────────────────────────────────────────────
    loadProductos () {
      this.loadingProd = true
      this.$axios.get('productos', {
        params: { page: this.paginationProd.page, per_page: this.paginationProd.rowsPerPage, q: this.filterProd, tipo: 'FARMACIA' },
      }).then(r => {
        this.productos = r.data.data || []
        this.paginationProd.rowsNumber = r.data.total || 0
      }).catch(e => this.$alert.error(e.response?.data?.message || 'Error al cargar'))
        .finally(() => { this.loadingProd = false })
    },

    onRequestProd (props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination
      this.paginationProd.page = page
      this.paginationProd.rowsPerPage = rowsPerPage
      this.paginationProd.sortBy = sortBy
      this.paginationProd.descending = descending
      this.loadProductos()
    },

    prodNew () {
      this.prod = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null, tipo: 'FARMACIA' }
      this.prodAction = 'Nuevo'
      this.dialogProd = true
    },

    prodEdit (row) {
      this.prod = { ...row, fabricante_id: row.fabricante?.id || null, unidad_id: row.unidad?.id || null }
      this.prodAction = 'Editar'
      this.dialogProd = true
    },

    async prodSave () {
      this.savingProd = true
      try {
        const payload = { ...this.prod, tipo: 'FARMACIA' }
        if (this.prod.id) {
          await this.$axios.put('productos/' + this.prod.id, payload)
          this.$alert.success('Producto actualizado')
        } else {
          await this.$axios.post('productos', payload)
          this.$alert.success('Producto creado')
        }
        this.dialogProd = false
        this.loadProductos()
        this.loadResumen()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.savingProd = false
      }
    },

    prodDelete (id) {
      this.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
        this.$axios.delete('productos/' + id)
          .then(() => { this.$alert.success('Producto eliminado'); this.loadProductos(); this.loadResumen() })
          .catch(e => this.$alert.error(e.response?.data?.message || 'Error'))
      })
    },

    // ── Fabricantes ──────────────────────────────────────────────
    loadFabricantes () {
      this.loadingFab = true
      this.$axios.get('fabricantes', {
        params: { page: this.paginationFab.page, per_page: this.paginationFab.rowsPerPage, q: this.filterFab },
      }).then(r => {
        this.fabricantes = r.data.data || []
        this.paginationFab.rowsNumber = r.data.total || 0
      }).catch(e => this.$alert.error(e.response?.data?.message || 'Error'))
        .finally(() => { this.loadingFab = false })
    },

    loadAllFabricantes () {
      this.$axios.get('fabricantes').then(r => { this.allFabricantes = r.data || [] })
    },

    onRequestFab (props) {
      const { page, rowsPerPage } = props.pagination
      this.paginationFab.page = page
      this.paginationFab.rowsPerPage = rowsPerPage
      this.loadFabricantes()
    },

    fabNew ()     { this.fab = { nombre: '', pais: '' }; this.fabAction = 'Nuevo'; this.dialogFab = true },
    fabEdit (row) { this.fab = { ...row }; this.fabAction = 'Editar'; this.dialogFab = true },

    async fabSave () {
      this.savingFab = true
      try {
        if (this.fab.id) {
          await this.$axios.put('fabricantes/' + this.fab.id, this.fab)
          this.$alert.success('Fabricante actualizado')
        } else {
          await this.$axios.post('fabricantes', this.fab)
          this.$alert.success('Fabricante creado')
        }
        this.dialogFab = false
        this.loadFabricantes()
        this.loadAllFabricantes()
        this.loadResumen()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.savingFab = false
      }
    },

    fabDelete (id) {
      this.$alert.dialog('¿Desea eliminar el fabricante?').onOk(() => {
        this.$axios.delete('fabricantes/' + id)
          .then(() => {
            this.$alert.success('Fabricante eliminado')
            this.loadFabricantes()
            this.loadAllFabricantes()
            this.loadResumen()
          })
          .catch(e => this.$alert.error(e.response?.data?.message || 'Error'))
      })
    },

    async fabQuickSave () {
      this.savingFab = true
      try {
        const res = await this.$axios.post('fabricantes', { nombre: this.fabQNombre, pais: this.fabQPais })
        this.loadAllFabricantes()
        this.prod.fabricante_id = res.data.id
        this.fabQuick = false
        this.fabQNombre = ''
        this.fabQPais = ''
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error')
      } finally {
        this.savingFab = false
      }
    },

    // ── Unidades ─────────────────────────────────────────────────
    loadUnidades () {
      this.loadingUnid = true
      this.$axios.get('unidades', {
        params: { page: this.paginationUnid.page, per_page: this.paginationUnid.rowsPerPage, q: this.filterUnid },
      }).then(r => {
        this.unidades = r.data.data || []
        this.paginationUnid.rowsNumber = r.data.total || 0
      }).catch(e => this.$alert.error(e.response?.data?.message || 'Error'))
        .finally(() => { this.loadingUnid = false })
    },

    loadAllUnidades () {
      this.$axios.get('unidades').then(r => { this.allUnidades = r.data || [] })
    },

    onRequestUnid (props) {
      const { page, rowsPerPage } = props.pagination
      this.paginationUnid.page = page
      this.paginationUnid.rowsPerPage = rowsPerPage
      this.loadUnidades()
    },

    unidNew ()     { this.unid = { nombre: '', abreviatura: '' }; this.unidAction = 'Nuevo'; this.dialogUnid = true },
    unidEdit (row) { this.unid = { ...row }; this.unidAction = 'Editar'; this.dialogUnid = true },

    async unidSave () {
      this.savingUnid = true
      try {
        if (this.unid.id) {
          await this.$axios.put('unidades/' + this.unid.id, this.unid)
          this.$alert.success('Unidad actualizada')
        } else {
          await this.$axios.post('unidades', this.unid)
          this.$alert.success('Unidad creada')
        }
        this.dialogUnid = false
        this.loadUnidades()
        this.loadAllUnidades()
        this.loadResumen()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.savingUnid = false
      }
    },

    unidDelete (id) {
      this.$alert.dialog('¿Desea eliminar la unidad?').onOk(() => {
        this.$axios.delete('unidades/' + id)
          .then(() => {
            this.$alert.success('Unidad eliminada')
            this.loadUnidades()
            this.loadAllUnidades()
            this.loadResumen()
          })
          .catch(e => this.$alert.error(e.response?.data?.message || 'Error'))
      })
    },

    async unidQuickSave () {
      this.savingUnid = true
      try {
        const res = await this.$axios.post('unidades', { nombre: this.unidQNombre, abreviatura: this.unidQAbrev })
        this.loadAllUnidades()
        this.prod.unidad_id = res.data.id
        this.unidQuick = false
        this.unidQNombre = ''
        this.unidQAbrev  = ''
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error')
      } finally {
        this.savingUnid = false
      }
    },

    // ── Exportar ─────────────────────────────────────────────────
    async exportPdf (recurso) {
      try {
        const params = recurso === 'productos'
          ? { q: this.filterProd, tipo: 'FARMACIA' }
          : recurso === 'fabricantes'
            ? { q: this.filterFab }
            : { q: this.filterUnid }
        const res = await this.$axios.get(recurso + '/export-pdf', { params, responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
        window.open(url, '_blank')
      } catch (e) {
        this.$alert.error('Error al generar PDF')
      }
    },

    async exportExcel (recurso) {
      try {
        const params = recurso === 'productos'
          ? { q: this.filterProd, tipo: 'FARMACIA' }
          : recurso === 'fabricantes'
            ? { q: this.filterFab }
            : { q: this.filterUnid }
        const res = await this.$axios.get(recurso + '/export-excel', { params, responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([res.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        }))
        const a = document.createElement('a')
        a.href = url
        a.download = recurso + '_' + new Date().toISOString().slice(0, 10) + '.xlsx'
        a.click()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$alert.error('Error al generar Excel')
      }
    },
  },
}
</script>
