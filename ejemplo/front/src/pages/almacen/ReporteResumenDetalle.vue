<template>
  <q-page class="q-pa-md">
    <!-- Header -->
    <div class="row items-center q-mb-md q-col-gutter-sm">
      <div class="col">
        <div class="text-h6 text-weight-bold text-primary">Reporte Resumen y Detalle</div>
        <div class="text-caption text-grey-6">Almacenes y Farmacia — Bienes de Consumo</div>
      </div>
      <div class="col-auto row q-col-gutter-xs items-center">
        <div class="col-auto">
          <q-input v-model="desde" dense outlined type="date" label="Desde" style="min-width:150px"/>
        </div>
        <div class="col-auto">
          <q-input v-model="hasta" dense outlined type="date" label="Hasta" style="min-width:150px"/>
        </div>
        <div class="col-auto">
          <q-btn color="primary" icon="refresh" label="Cargar" @click="cargar" no-caps :loading="loading" dense/>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <q-tabs v-model="tab" dense align="left" class="q-mb-sm" active-color="primary" indicator-color="primary">
      <q-tab name="resumen" icon="bar_chart" label="Resumen (DGCF-R1.05)"/>
      <q-tab name="detalle" icon="table_chart" label="Detalle (DGCF-R1.06)"/>
    </q-tabs>

    <q-tab-panels v-model="tab" animated>
      <!-- ─── RESUMEN ─────────────────────────────────────────────────── -->
      <q-tab-panel name="resumen" class="q-pa-none">
        <div class="row items-center q-mb-sm q-col-gutter-xs">
          <div class="col">
            <span class="text-caption text-grey-7">{{ meta.periodo }}</span>
            <span class="text-caption text-grey-7 q-ml-md">
              — {{ resumenSeleccionados }} de {{ resumenRows.length }} marcados para imprimir
            </span>
          </div>
          <div class="col-auto row q-col-gutter-xs">
            <div class="col-auto">
              <q-btn outline color="green-8" icon="table_view" :label="`Excel (${resumenSeleccionados})`" size="sm" no-caps
                     @click="exportar('resumen','excel')" :loading="exportingR" :disable="!resumenSeleccionados"/>
            </div>
            <div class="col-auto">
              <q-btn outline color="red-8" icon="picture_as_pdf" :label="`PDF (${resumenSeleccionados})`" size="sm" no-caps
                     @click="exportar('resumen','pdf')" :loading="exportingR" :disable="!resumenSeleccionados"/>
            </div>
          </div>
        </div>

        <q-table
          :rows="resumenRows"
          :columns="resumenCols"
          dense flat bordered
          :rows-per-page-options="[0]"
          :loading="loading"
          row-key="nro"
        >
          <template v-slot:header-cell-check="props">
            <q-th :props="props">
              <q-checkbox dense :model-value="todosResumenMarcados" @update:model-value="marcarTodoResumen"/>
            </q-th>
          </template>
          <template v-slot:body-cell-check="props">
            <q-td :props="props" class="text-center">
              <q-checkbox dense :model-value="!subpartidasExcluidas.has(props.row.subpartida_id)"
                          @update:model-value="alternarSubpartida(props.row)"/>
            </q-td>
          </template>
          <template v-slot:body-cell-acciones="props">
            <q-td :props="props" class="text-center">
              <q-btn icon="visibility" round dense flat size="sm" color="primary"
                     @click="verProductos(props.row)">
                <q-tooltip>Ver productos</q-tooltip>
              </q-btn>
            </q-td>
          </template>
          <template v-slot:bottom-row>
            <q-tr class="bg-blue-8 text-white text-weight-bold">
              <q-td></q-td>
              <q-td></q-td>
              <q-td colspan="3" class="text-center">TOTAL</q-td>
              <q-td class="text-right">{{ fmt(resumenTotal?.cant_ini) }}</q-td>
              <q-td class="text-right">{{ fmtBs(resumenTotal?.saldo_ini) }}</q-td>
              <q-td class="text-right">{{ fmt(resumenTotal?.cant_final) }}</q-td>
              <q-td class="text-right">{{ fmtBs(resumenTotal?.saldo_final) }}</q-td>
            </q-tr>
          </template>
        </q-table>
      </q-tab-panel>

      <!-- ─── DETALLE ─────────────────────────────────────────────────── -->
      <q-tab-panel name="detalle" class="q-pa-none">
        <div class="row items-center q-mb-sm q-col-gutter-xs">
          <div class="col">
            <span class="text-caption text-grey-7">{{ meta.periodo }}</span>
            <span class="text-caption text-grey-7 q-ml-md">
              — {{ detalleSeleccionados }} de {{ detallePagination.rowsNumber }} marcados para imprimir
            </span>
          </div>
          <div class="col-auto row q-col-gutter-xs">
            <div class="col-auto">
              <q-input v-model="filtroDetalle" dense outlined clearable placeholder="Buscar..." style="min-width:200px"
                       @update:model-value="onFiltroDetalleChange" @clear="onFiltroDetalleChange('')">
                <template v-slot:append><q-icon name="search"/></template>
              </q-input>
            </div>
            <div class="col-auto">
              <q-btn outline color="green-8" icon="table_view" :label="`Excel (${detalleSeleccionados})`" size="sm" no-caps
                     @click="exportar('detalle','excel')" :loading="exportingD" :disable="!detalleSeleccionados"/>
            </div>
            <div class="col-auto">
              <q-btn outline color="red-8" icon="picture_as_pdf" :label="`PDF (${detalleSeleccionados})`" size="sm" no-caps
                     @click="exportar('detalle','pdf')" :loading="exportingD" :disable="!detalleSeleccionados"/>
            </div>
          </div>
        </div>

        <q-table
          :rows="detalleRows"
          :columns="detalleCols"
          dense flat bordered
          row-key="id"
          v-model:pagination="detallePagination"
          :rows-per-page-options="[50, 100, 200, 500, 1000]"
          :loading="loading || loadingDetalle"
          @request="onRequestDetalle"
        >
          <template v-slot:header-cell-check="props">
            <q-th :props="props">
              <q-checkbox dense :model-value="todosDetalleMarcados" @update:model-value="marcarTodoDetalle"/>
            </q-th>
          </template>
          <template v-slot:body-cell-check="props">
            <q-td :props="props" class="text-center">
              <q-checkbox dense :model-value="!detalleExcluidos.has(props.row.id)"
                          @update:model-value="alternarDetalle(props.row)"/>
            </q-td>
          </template>
          <template v-slot:body-cell-acciones="props">
            <q-td :props="props" class="text-center">
              <q-btn icon="visibility" round dense flat size="sm" color="primary"
                     @click="verSalidas(props.row)">
                <q-tooltip>Ver salidas</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-tab-panel>
    </q-tab-panels>

    <!-- ─── Dialog: Productos de la subpartida ─────────────────────────── -->
    <q-dialog v-model="productosDialog" full-width>
      <q-card>
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <div class="text-subtitle1 text-weight-bold">Productos — {{ productosTitulo }}</div>
          <q-space/>
          <q-btn icon="close" flat round dense v-close-popup/>
        </q-card-section>
        <q-card-section class="q-pa-none" style="max-height:70vh; overflow:auto;">
          <q-table
            :rows="productosRows"
            :columns="productosCols"
            dense flat bordered
            :rows-per-page-options="[0]"
            :loading="loadingProductos"
            row-key="nro"
          >
            <template v-slot:body-cell-acciones="props">
              <q-td :props="props" class="text-center">
                <q-btn icon="visibility" round dense flat size="sm" color="primary"
                       @click="verSalidas(props.row)">
                  <q-tooltip>Ver salidas</q-tooltip>
                </q-btn>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- ─── Dialog: Salidas de un producto (control/verificación) ──────── -->
    <q-dialog v-model="movimientosDialog">
      <q-card style="min-width:700px; max-width:95vw;">
        <q-card-section class="row items-center bg-deep-orange-8 text-white q-py-sm">
          <div class="text-subtitle1 text-weight-bold">Salidas — {{ movimientosTitulo }}</div>
          <q-space/>
          <q-btn icon="close" flat round dense v-close-popup/>
        </q-card-section>
        <q-card-section class="q-pa-none" style="max-height:70vh; overflow:auto;">
          <q-table
            :rows="movimientosRows"
            :columns="movimientosCols"
            dense flat bordered
            :rows-per-page-options="[0]"
            :loading="loadingMovimientos"
            row-key="nro"
            no-data-label="Sin salidas registradas en el período"
          />
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'ReporteResumenDetallePage',
  data() {
    const year = new Date().getFullYear()
    return {
      tab: 'resumen',
      loading: false,
      loadingDetalle: false,
      exportingR: false,
      exportingD: false,
      filtroDetalle: '',
      filtroDetalleTimer: null,
      desde: `${year}-01-01`,
      hasta: `${year}-12-31`,
      meta: { periodo: '' },

      resumenRows: [],
      resumenTotal: null,
      subpartidasExcluidas: new Set(),

      detalleRows: [],
      detalleExcluidos: new Set(),
      detallePagination: { page: 1, rowsPerPage: 50, rowsNumber: 0 },

      resumenCols: [
        { name: 'check',       label: '',         field: 'check',       align: 'center', style: 'width:34px' },
        { name: 'acciones',    label: '',         field: 'acciones',    align: 'center', style: 'width:40px' },
        { name: 'nro',         label: 'Nº',       field: 'nro',         align: 'center', sortable: true, style: 'width:50px' },
        { name: 'detalle',     label: 'DETALLE',   field: 'detalle',     align: 'left',   sortable: true,
          style: 'max-width:180px; white-space:normal; word-break:break-word; line-height:1.1;' },
        { name: 'subpartida',  label: 'Subpartida', field: 'subpartida', align: 'center', sortable: true, style: 'width:90px' },
        { name: 'cant_ini',    label: 'Cant. Inicial',  field: 'cant_ini',    align: 'right', sortable: true,
          format: v => this.fmt(v) },
        { name: 'saldo_ini',   label: 'Saldo Inicial (Bs)', field: 'saldo_ini',   align: 'right', sortable: true,
          format: v => this.fmtBs(v) },
        { name: 'cant_final',  label: 'Cant. Final',   field: 'cant_final',  align: 'right', sortable: true,
          format: v => this.fmt(v) },
        { name: 'saldo_final', label: 'Saldo Final (Bs)', field: 'saldo_final', align: 'right', sortable: true,
          format: v => this.fmtBs(v) },
      ],

      detalleCols: [
        { name: 'check',            label: '',               field: 'check',            align: 'center', style: 'width:34px' },
        { name: 'acciones',         label: '',               field: 'acciones',         align: 'center', style: 'width:40px' },
        { name: 'nro',              label: 'Nº',             field: 'nro',              align: 'center', style: 'width:40px' },
        { name: 'descripcion',      label: 'Descripción',    field: 'descripcion',      align: 'left',
          style: 'max-width:180px; white-space:normal; word-break:break-word; line-height:1.1;' },
        { name: 'unidad',           label: 'Unidad',         field: 'unidad',           align: 'center', style: 'width:70px' },
        { name: 'precio_unitario',  label: 'P. Unit.',       field: 'precio_unitario',  align: 'right',  format: v => this.fmtBs(v), style: 'width:90px' },
        { name: 'cant_saldo_ini',   label: 'Cant. S. Ini.',  field: 'cant_saldo_ini',   align: 'right',  format: v => this.fmt(v),   style: 'width:80px' },
        { name: 'cant_entradas',    label: 'Cant. Ent.',     field: 'cant_entradas',    align: 'right',  format: v => this.fmt(v),   style: 'width:75px' },
        { name: 'cant_salidas',     label: 'Cant. Sal.',     field: 'cant_salidas',     align: 'right',  format: v => this.fmt(v),   style: 'width:75px' },
        { name: 'cant_saldo_final', label: 'Cant. S. Final', field: 'cant_saldo_final', align: 'right',  format: v => this.fmt(v),   style: 'width:85px' },
        { name: 'val_saldo_ini',    label: 'Val. S. Ini.',   field: 'val_saldo_ini',    align: 'right',  format: v => this.fmtBs(v), style: 'width:95px' },
        { name: 'val_entradas',     label: 'Val. Ent.',      field: 'val_entradas',     align: 'right',  format: v => this.fmtBs(v), style: 'width:90px' },
        { name: 'val_salidas',      label: 'Val. Sal.',      field: 'val_salidas',      align: 'right',  format: v => this.fmtBs(v), style: 'width:90px' },
        { name: 'val_saldo_final',  label: 'Val. S. Final',  field: 'val_saldo_final',  align: 'right',  format: v => this.fmtBs(v), style: 'width:95px' },
      ],

      movimientosCols: [
        { name: 'nro',             label: 'Nº',        field: 'nro',             align: 'center', style: 'width:40px' },
        { name: 'tipo',            label: 'Tipo',       field: 'tipo',            align: 'center' },
        { name: 'fecha',           label: 'Fecha',      field: 'fecha',           align: 'center',
          format: v => this.fmtFecha(v) },
        { name: 'documento',       label: 'Documento',  field: 'documento',       align: 'center' },
        { name: 'destino',         label: 'Destino / Unidad', field: 'destino',   align: 'left' },
        { name: 'lote',            label: 'Lote',       field: 'lote',            align: 'center' },
        { name: 'cantidad',        label: 'Cantidad',   field: 'cantidad',        align: 'right',
          format: v => this.fmt(v) },
        { name: 'precio_unitario', label: 'P. Unit.',   field: 'precio_unitario', align: 'right',
          format: v => this.fmtBs(v) },
        { name: 'total',           label: 'Total (Bs)', field: 'total',           align: 'right',
          format: v => this.fmtBs(v) },
      ],

      productosDialog: false,
      productosTitulo: '',
      productosRows: [],
      loadingProductos: false,

      movimientosDialog: false,
      movimientosTitulo: '',
      movimientosRows: [],
      loadingMovimientos: false,
    }
  },
  computed: {
    productosCols() {
      return this.detalleCols.filter(c => c.name !== 'check')
    },
    todosResumenMarcados() {
      return this.resumenRows.length > 0 && this.subpartidasExcluidas.size === 0
    },
    resumenSeleccionados() {
      return Math.max(this.resumenRows.length - this.subpartidasExcluidas.size, 0)
    },
    todosDetalleMarcados() {
      return this.detalleRows.length > 0 && this.detalleRows.every(r => !this.detalleExcluidos.has(r.id))
    },
    detalleSeleccionados() {
      return Math.max(this.detallePagination.rowsNumber - this.detalleExcluidos.size, 0)
    },
  },
  mounted() {
    this.cargar()
  },
  methods: {
    async cargar() {
      this.loading = true
      this.subpartidasExcluidas = new Set()
      this.detalleExcluidos = new Set()
      this.detallePagination.page = 1
      try {
        const res = await this.$axios.get('reporte-resumen-detalle', {
          params: {
            desde: this.desde, hasta: this.hasta,
            page: 1, per_page: this.detallePagination.rowsPerPage,
          },
        })
        this.resumenRows  = res.data.resumen.rows  || []
        this.resumenTotal = res.data.resumen.total || null
        this.meta         = res.data.meta          || { periodo: '' }
        this.detalleRows  = res.data.detalle.rows  || []
        this.detallePagination.rowsNumber = res.data.detalle.total || 0
        this.detallePagination.page = res.data.detalle.page || 1
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cargar el reporte')
      } finally {
        this.loading = false
      }
    },

    onFiltroDetalleChange(val) {
      this.filtroDetalle = val
      clearTimeout(this.filtroDetalleTimer)
      this.filtroDetalleTimer = setTimeout(() => {
        this.onRequestDetalle({ pagination: { ...this.detallePagination, page: 1 } })
      }, 350)
    },

    async onRequestDetalle({ pagination }) {
      this.loadingDetalle = true
      try {
        const { page, rowsPerPage } = pagination
        const res = await this.$axios.get('reporte-resumen-detalle/detalle', {
          params: {
            desde: this.desde, hasta: this.hasta,
            page, per_page: rowsPerPage, q: this.filtroDetalle,
          },
        })
        this.detalleRows = res.data.detalle.rows || []
        this.detallePagination = {
          ...this.detallePagination,
          page,
          rowsPerPage,
          rowsNumber: res.data.detalle.total || 0,
        }
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cargar el detalle')
      } finally {
        this.loadingDetalle = false
      }
    },

    marcarTodoResumen(val) {
      this.subpartidasExcluidas = val ? new Set() : new Set(this.resumenRows.map(r => r.subpartida_id))
    },
    alternarSubpartida(row) {
      const id = row.subpartida_id
      if (this.subpartidasExcluidas.has(id)) this.subpartidasExcluidas.delete(id)
      else this.subpartidasExcluidas.add(id)
      this.subpartidasExcluidas = new Set(this.subpartidasExcluidas)
    },

    marcarTodoDetalle(val) {
      const ids = this.detalleRows.map(r => r.id)
      if (val) ids.forEach(id => this.detalleExcluidos.delete(id))
      else ids.forEach(id => this.detalleExcluidos.add(id))
      this.detalleExcluidos = new Set(this.detalleExcluidos)
    },
    alternarDetalle(row) {
      const id = row.id
      if (this.detalleExcluidos.has(id)) this.detalleExcluidos.delete(id)
      else this.detalleExcluidos.add(id)
      this.detalleExcluidos = new Set(this.detalleExcluidos)
    },

    async exportar(tipo, formato) {
      const isResumen = tipo === 'resumen'
      if (isResumen) this.exportingR = true
      else           this.exportingD = true

      try {
        const url = `reporte-resumen-detalle/${tipo}/${formato}`
        const ext = formato === 'excel' ? 'xlsx' : 'pdf'
        const code = tipo === 'resumen' ? 'DGCF-R1.05' : 'DGCF-R1.06'

        const body = { desde: this.desde, hasta: this.hasta }
        if (isResumen) body.exclude_subpartida_ids = [...this.subpartidasExcluidas]
        else           body.exclude_ids = [...this.detalleExcluidos]

        const res = await this.$axios.post(url, body, { responseType: 'blob' })
        const blob = new Blob([res.data], {
          type: formato === 'excel'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf',
        })
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        a.download = `${code}-${tipo}.${ext}`
        a.click()
        URL.revokeObjectURL(a.href)
      } catch (e) {
        this.$alert.error('Error al exportar')
      } finally {
        if (isResumen) this.exportingR = false
        else           this.exportingD = false
      }
    },

    fmt(v) {
      if (v === null || v === undefined) return '—'
      return Number(v).toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
    },
    fmtBs(v) {
      if (v === null || v === undefined) return '—'
      return Number(v).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    fmtFecha(v) {
      if (!v) return '—'
      const d = new Date(v)
      if (isNaN(d.getTime())) return v
      return d.toLocaleDateString('es-BO') + ' ' + d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
    },

    async verProductos(row) {
      this.productosTitulo = `${row.subpartida} ${row.detalle}`
      this.productosDialog = true
      this.productosRows = []
      this.loadingProductos = true
      try {
        const res = await this.$axios.get(`reporte-resumen-detalle/productos/${row.subpartida_id}`, {
          params: { desde: this.desde, hasta: this.hasta },
        })
        this.productosRows = res.data.rows || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cargar los productos')
      } finally {
        this.loadingProductos = false
      }
    },

    async verSalidas(row) {
      this.movimientosTitulo = row.descripcion
      this.movimientosDialog = true
      this.movimientosRows = []
      this.loadingMovimientos = true
      try {
        const res = await this.$axios.get(`reporte-resumen-detalle/movimientos/${row.id}`, {
          params: { desde: this.desde, hasta: this.hasta },
        })
        this.movimientosRows = res.data.rows || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cargar las salidas')
      } finally {
        this.loadingMovimientos = false
      }
    },
  },
}
</script>
