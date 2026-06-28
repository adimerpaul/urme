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
          </div>
          <div class="col-auto row q-col-gutter-xs">
            <div class="col-auto">
              <q-btn outline color="green-8" icon="table_view" label="Excel" size="sm" no-caps
                     @click="exportar('resumen','excel')" :loading="exportingR"/>
            </div>
            <div class="col-auto">
              <q-btn outline color="red-8" icon="picture_as_pdf" label="PDF" size="sm" no-caps
                     @click="exportar('resumen','pdf')" :loading="exportingR"/>
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
          <template v-slot:bottom-row>
            <q-tr class="bg-blue-8 text-white text-weight-bold">
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
          </div>
          <div class="col-auto row q-col-gutter-xs">
            <div class="col-auto">
              <q-input v-model="filtroDetalle" dense outlined clearable placeholder="Buscar..." style="min-width:200px">
                <template v-slot:append><q-icon name="search"/></template>
              </q-input>
            </div>
            <div class="col-auto">
              <q-btn outline color="green-8" icon="table_view" label="Excel" size="sm" no-caps
                     @click="exportar('detalle','excel')" :loading="exportingD"/>
            </div>
            <div class="col-auto">
              <q-btn outline color="red-8" icon="picture_as_pdf" label="PDF" size="sm" no-caps
                     @click="exportar('detalle','pdf')" :loading="exportingD"/>
            </div>
          </div>
        </div>

        <q-table
          :rows="detalleFiltered"
          :columns="detalleCols"
          dense flat bordered
          :rows-per-page-options="[0]"
          :loading="loading"
          row-key="nro"
        />
      </q-tab-panel>
    </q-tab-panels>
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
      exportingR: false,
      exportingD: false,
      filtroDetalle: '',
      desde: `${year}-01-01`,
      hasta: `${year}-12-31`,
      resumenRows: [],
      resumenTotal: null,
      meta: { periodo: '' },
      detalleRows: [],

      resumenCols: [
        { name: 'nro',         label: 'Nº',       field: 'nro',         align: 'center', sortable: true, style: 'width:50px' },
        { name: 'detalle',     label: 'DETALLE',   field: 'detalle',     align: 'left',   sortable: true },
        { name: 'partida',     label: 'Partida',   field: 'partida',     align: 'center', sortable: true, style: 'width:90px' },
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
        { name: 'nro',              label: 'Nº',             field: 'nro',              align: 'center', style: 'width:40px' },
        { name: 'descripcion',      label: 'Descripción',    field: 'descripcion',      align: 'left',   sortable: true },
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
    }
  },
  mounted() {
    this.cargar()
  },
  computed: {
    detalleFiltered() {
      const q = (this.filtroDetalle || '').toLowerCase()
      if (!q) return this.detalleRows
      return this.detalleRows.filter(r =>
        r.descripcion.toLowerCase().includes(q) || r.unidad.toLowerCase().includes(q)
      )
    },
  },
  methods: {
    async cargar() {
      this.loading = true
      try {
        const res = await this.$axios.get('reporte-resumen-detalle', {
          params: { desde: this.desde, hasta: this.hasta },
        })
        this.resumenRows  = res.data.resumen.rows  || []
        this.resumenTotal = res.data.resumen.total || null
        this.meta         = res.data.meta          || { periodo: '' }
        this.detalleRows  = res.data.detalle.rows  || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cargar el reporte')
      } finally {
        this.loading = false
      }
    },

    async exportar(tipo, formato) {
      const isResumen = tipo === 'resumen'
      if (isResumen) this.exportingR = true
      else           this.exportingD = true

      try {
        const url = `reporte-resumen-detalle/${tipo}/${formato}`
        const ext = formato === 'excel' ? 'xlsx' : 'pdf'
        const code = tipo === 'resumen' ? 'DGCF-R1.05' : 'DGCF-R1.06'

        const res = await this.$axios.get(url, {
          responseType: 'blob',
          params: { desde: this.desde, hasta: this.hasta },
        })
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
  },
}
</script>
