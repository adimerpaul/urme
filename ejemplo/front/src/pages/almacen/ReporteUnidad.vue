<template>
  <q-page class="q-pa-md bg-grey-2">

    <!-- LOADING PDF -->
    <q-dialog v-model="loadingPdf" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="negative" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando PDF...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Esto puede tardar unos segundos.</div>
      </q-card>
    </q-dialog>

    <!-- LOADING EXCEL -->
    <q-dialog v-model="loadingExcel" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="positive" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando Excel...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Preparando el archivo...</div>
      </q-card>
    </q-dialog>

    <!-- ENCABEZADO -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Reporte por Unidad</div>
        <div class="text-caption text-grey-7">Material despachado agrupado por producto, filtrado por unidad solicitante y rango de fechas.</div>
      </div>
    </div>

    <!-- FILTROS -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="q-pb-sm">
        <div class="text-overline text-grey-6 q-mb-xs">FILTROS</div>
        <div class="row q-col-gutter-sm items-end">

          <div class="col-6 col-sm-3 col-md-2">
            <q-input v-model="filters.date_from" type="date" dense outlined label="Desde" />
          </div>

          <div class="col-6 col-sm-3 col-md-2">
            <q-input v-model="filters.date_to" type="date" dense outlined label="Hasta" />
          </div>

          <div class="col-12 col-sm-6 col-md-4">
            <q-select
              v-model="filters.solicitante"
              :options="unidadOptions"
              dense outlined clearable
              label="Unidad solicitante"
              emit-value map-options
              use-input input-debounce="200"
              @filter="filterUnidades"
            >
              <template v-slot:prepend><q-icon name="business" /></template>
            </q-select>
          </div>

          <!-- Persona -->
          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="filters.personal_recepcion"
              :options="personaOptionsFiltradas"
              dense outlined clearable
              label="Persona que retiró"
              emit-value map-options
              use-input input-debounce="200"
              @filter="filterPersonas"
            >
              <template v-slot:prepend><q-icon name="person" /></template>
            </q-select>
          </div>

          <div class="col-12 col-sm col-md-auto row q-gutter-sm items-center">
            <q-btn color="positive" icon="search" label="Buscar" no-caps :loading="loading" @click="fetchData" />
            <q-btn flat color="grey-7" icon="clear_all" label="Limpiar" no-caps @click="clearFilters" />
          </div>

        </div>
      </q-card-section>
    </q-card>

    <!-- KPIs -->
    <div class="row q-col-gutter-md q-mb-md">

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Productos distintos</div>
          <div class="text-h4 text-weight-bold text-green-9">{{ summary.total_items }}</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Cantidad total</div>
          <div class="text-h4 text-weight-bold text-green-8">{{ summary.total_cantidad }}</div>
          <div class="text-caption text-grey-6">unidades despachadas</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Total Bs</div>
          <div class="text-h4 text-weight-bold text-positive">{{ money(summary.total_monto) }}</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7 q-mb-sm">Exportar</div>
          <div class="row q-gutter-sm">
            <q-btn
              outline color="positive" icon="grid_on" label="Excel" no-caps size="sm"
              :loading="loadingExcel" :disable="loading || loadingPdf || rows.length === 0"
              @click="downloadExcel"
            />
            <q-btn
              outline color="negative" icon="picture_as_pdf" label="PDF" no-caps size="sm"
              :loading="loadingPdf" :disable="loading || loadingExcel || rows.length === 0"
              @click="openPdf"
            />
          </div>
        </q-card>
      </div>

    </div>

    <!-- TABLA -->
    <q-card flat bordered>
      <q-card-section class="row items-center q-pb-none">
        <div class="col">
          <span class="text-subtitle1 text-weight-bold">Material despachado</span>
          <q-badge color="positive" :label="rows.length" class="q-ml-sm" />
        </div>
        <div class="col-auto">
          <q-input v-model="tableFilter" dense outlined placeholder="Buscar producto..." style="min-width:220px" clearable>
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
        </div>
      </q-card-section>

      <q-separator class="q-mt-sm" />

      <q-card-section class="q-pa-none">
        <q-table
          :rows="rows"
          :columns="columns"
          row-key="almacen_item_id"
          dense flat
          :filter="tableFilter"
          :rows-per-page-options="[15, 25, 50, 100, 0]"
          :pagination="{ rowsPerPage: 25 }"
          :loading="loading"
        >

          <!-- Imagen -->
          <template v-slot:body-cell-imagen="props">
            <q-td :props="props" style="width:56px;">
              <q-avatar square size="44px">
                <img
                  :src="imgUrl(props.row.imagen)"
                  style="object-fit:contain;"
                  @error="onImgError"
                />
              </q-avatar>
            </q-td>
          </template>

          <!-- Nombre -->
          <template v-slot:body-cell-item_nombre="props">
            <q-td :props="props">
              <span class="text-weight-medium">{{ props.row.item_nombre }}</span>
            </q-td>
          </template>

          <!-- Cantidad -->
          <template v-slot:body-cell-cantidad_total="props">
            <q-td :props="props" class="text-right">
              <q-chip dense color="green-1" text-color="green-9" size="sm">
                {{ props.row.cantidad_total }}
              </q-chip>
            </q-td>
          </template>

          <!-- Total Bs -->
          <template v-slot:body-cell-total_monto="props">
            <q-td :props="props" class="text-right">
              <span class="text-weight-medium text-positive">{{ money(props.row.total_monto) }}</span>
            </q-td>
          </template>

          <!-- Precio promedio -->
          <template v-slot:body-cell-precio_promedio="props">
            <q-td :props="props" class="text-right">
              {{ money(props.row.precio_promedio) }}
            </q-td>
          </template>

          <!-- Personas -->
          <template v-slot:body-cell-personas_recepcion="props">
            <q-td :props="props">
              <template v-if="props.row.personas_recepcion">
                <q-chip
                  v-for="p in props.row.personas_recepcion.split(', ')"
                  :key="p"
                  dense size="sm"
                  color="blue-1" text-color="blue-9"
                  icon="person"
                  class="q-mr-xs q-mb-xs"
                >
                  {{ p }}
                </q-chip>
              </template>
              <span v-else class="text-grey-4 text-caption">—</span>
            </q-td>
          </template>

          <template v-slot:no-data>
            <div class="full-width row flex-center q-pa-xl text-grey-6">
              <q-icon name="inventory_2" size="40px" class="q-mr-md" />
              <span>Sin resultados. Selecciona una unidad y un rango de fechas.</span>
            </div>
          </template>

        </q-table>
      </q-card-section>
    </q-card>

  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'ReporteUnidadPage',

  data () {
    return {
      loading: false,
      loadingPdf: false,
      loadingExcel: false,
      tableFilter: '',

      filters: {
        date_from: '',
        date_to: '',
        solicitante: null,
        personal_recepcion: null
      },

      rows: [],
      summary: { total_items: 0, total_cantidad: 0, total_monto: 0 },

      unidades: [],
      unidadOptionsFiltradas: [],
      personas: [],
      personaOptionsFiltradas: [],

      columns: [
        { name: 'imagen',         label: '',               field: 'imagen',         align: 'center' },
        { name: 'item_nombre',    label: 'Producto',       field: 'item_nombre',    align: 'left',  sortable: true },
        { name: 'unidad_medida',  label: 'Unidad',         field: 'unidad_medida',  align: 'center', sortable: true },
        { name: 'cantidad_total', label: 'Cantidad',       field: 'cantidad_total', align: 'right', sortable: true },
        { name: 'precio_promedio', label: 'P. Unit. Bs',  field: 'precio_promedio', align: 'right', sortable: true },
        { name: 'total_monto',       label: 'Total Bs',            field: 'total_monto',       align: 'right', sortable: true },
        { name: 'personas_recepcion', label: 'Personas que retiraron', field: 'personas_recepcion', align: 'left',  sortable: true }
      ]
    }
  },

  computed: {
    unidadOptions () {
      return this.unidades.map(u => ({ label: u, value: u }))
    }
  },

  async mounted () {
    const hoy = moment().format('YYYY-MM-DD')
    this.filters.date_from = moment().startOf('month').format('YYYY-MM-DD')
    this.filters.date_to   = hoy

    await Promise.all([this.loadUnidades(), this.loadPersonas()])
  },

  methods: {
    money (n) {
      return parseFloat(n || 0).toFixed(2) + ' Bs'
    },

    imgUrl (imagen) {
      if (!imagen) return `${this.$url}/../images/productos/default.png`
      return `${this.$url}/../images/productos/${imagen}`
    },

    onImgError (e) {
      e.target.src = `${this.$url}/../images/productos/default.png`
    },

    async loadUnidades () {
      try {
        const res = await this.$axios.get('reportes/almacen-unidad/unidades')
        this.unidades = res.data || []
        this.unidadOptionsFiltradas = this.unidadOptions
      } catch {}
    },

    async loadPersonas () {
      try {
        const res = await this.$axios.get('reportes/almacen-unidad/personas')
        this.personas = res.data || []
        this.personaOptionsFiltradas = this.personas.map(p => ({ label: p, value: p }))
      } catch {}
    },

    filterPersonas (val, update) {
      const opts = this.personas.map(p => ({ label: p, value: p }))
      if (!val) { update(() => { this.personaOptionsFiltradas = opts }); return }
      const needle = val.toLowerCase()
      update(() => { this.personaOptionsFiltradas = opts.filter(o => o.label.toLowerCase().includes(needle)) })
    },

    filterUnidades (val, update) {
      const opts = this.unidadOptions
      if (!val) { update(() => { this.unidadOptionsFiltradas = opts }); return }
      const needle = val.toLowerCase()
      update(() => { this.unidadOptionsFiltradas = opts.filter(o => o.label.toLowerCase().includes(needle)) })
    },

    async fetchData () {
      this.loading = true
      try {
        const params = {}
        if (this.filters.date_from)  params.date_from   = this.filters.date_from
        if (this.filters.date_to)    params.date_to     = this.filters.date_to
        if (this.filters.solicitante)        params.solicitante        = this.filters.solicitante
        if (this.filters.personal_recepcion) params.personal_recepcion = this.filters.personal_recepcion

        const res  = await this.$axios.get('reportes/almacen-unidad', { params })
        this.rows    = res.data?.rows    || []
        this.summary = res.data?.summary || { total_items: 0, total_cantidad: 0, total_monto: 0 }
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: e.response?.data?.message || 'Error cargando el reporte' })
      } finally {
        this.loading = false
      }
    },

    clearFilters () {
      this.filters = {
        date_from:   moment().startOf('month').format('YYYY-MM-DD'),
        date_to:     moment().format('YYYY-MM-DD'),
        solicitante: null,
        personal_recepcion: null
      }
    },

    buildParams () {
      const p = {}
      if (this.filters.date_from)   p.date_from   = this.filters.date_from
      if (this.filters.date_to)     p.date_to     = this.filters.date_to
      if (this.filters.solicitante)        p.solicitante        = this.filters.solicitante
      if (this.filters.personal_recepcion) p.personal_recepcion = this.filters.personal_recepcion
      return p
    },

    async downloadExcel () {
      this.loadingExcel = true
      try {
        const res = await this.$axios.get('reportes/almacen-unidad/excel', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 120000
        })
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url  = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = `reporte_unidad_${this.filters.date_from}_${this.filters.date_to}.xlsx`
        link.click()
        window.URL.revokeObjectURL(url)
      } catch {
        this.$q?.notify({ type: 'negative', message: 'Error generando Excel' })
      } finally {
        this.loadingExcel = false
      }
    },

    async openPdf () {
      this.loadingPdf = true
      try {
        const res = await this.$axios.get('reportes/almacen-unidad/pdf', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 180000
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        window.open(window.URL.createObjectURL(blob), '_blank')
      } catch {
        this.$q?.notify({ type: 'negative', message: 'Error generando PDF' })
      } finally {
        this.loadingPdf = false
      }
    }
  }
}
</script>
