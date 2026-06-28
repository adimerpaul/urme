<template>
  <q-page class="q-pa-md bg-grey-2">

    <!-- LOADING PDF OVERLAY -->
    <q-dialog v-model="loadingPdf" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="negative" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando PDF...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Esto puede tardar unos segundos con muchos datos.</div>
      </q-card>
    </q-dialog>

    <!-- LOADING EXCEL OVERLAY -->
    <q-dialog v-model="loadingExcel" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="primary" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando Excel...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Preparando el archivo, por favor espera.</div>
      </q-card>
    </q-dialog>

    <!-- LOADING EXCEL MENSUAL OVERLAY -->
    <q-dialog v-model="loadingExcelMensual" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="teal" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando Registro Mensual...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Llenando plantilla VIH, por favor espera.</div>
      </q-card>
    </q-dialog>

    <!-- LOADING ENTs OVERLAY -->
    <q-dialog v-model="loadingEnts" persistent>
      <q-card flat class="q-pa-lg text-center" style="min-width:260px">
        <q-spinner-dots color="deep-orange" size="48px" class="q-mb-md" />
        <div class="text-subtitle1 text-weight-bold">Generando Informe ENTs...</div>
        <div class="text-caption text-grey-6 q-mt-xs">Calculando indicadores por parámetro.</div>
      </q-card>
    </q-dialog>

    <!-- ENCABEZADO -->
    <div class="row items-center q-mb-md">
      <div class="col">
        <div class="text-h5 text-weight-bold">Reporte de Solicitudes</div>
        <div class="text-caption text-grey-7">
          Todas las solicitudes en un rango de fechas — filtros por prestación, servicio, género, embarazo y cama.
        </div>
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

          <!-- Prestación / Área -->
          <div class="col-12 col-sm-6 col-md-2">
            <q-select
              v-model="filters.area_id"
              :options="areaOptions"
              dense outlined clearable
              label="Prestación (área)"
              emit-value map-options
              @update:model-value="onAreaChange"
            />
          </div>

          <!-- Servicio -->
          <div class="col-12 col-sm-6 col-md-2">
            <q-select
              v-model="filters.servicio_id"
              :options="servicioOptionsFiltrados"
              dense outlined clearable
              label="Servicio"
              emit-value map-options
              use-input
              input-debounce="200"
              @filter="filterServicios"
            />
          </div>

          <!-- Género -->
          <div class="col-6 col-sm-3 col-md-1">
            <q-select
              v-model="filters.genero"
              :options="[{ label: 'Masc.', value: 'M' }, { label: 'Fem.', value: 'F' }]"
              dense outlined clearable
              label="Género"
              emit-value map-options
            />
          </div>

          <!-- Embarazada -->
          <div class="col-6 col-sm-3 col-md-1">
            <q-select
              v-model="filters.embarazada"
              :options="[{ label: 'Sí', value: '1' }, { label: 'No', value: '0' }]"
              dense outlined clearable
              label="Embarazada"
              emit-value map-options
            />
          </div>

          <!-- Cama -->
          <div class="col-6 col-sm-3 col-md-1">
            <q-input
              v-model="filters.cama"
              dense outlined clearable
              label="Cama"
              @keyup.enter="fetchData"
            />
          </div>

          <!-- Paciente -->
          <div class="col-12 col-sm-4 col-md-2">
            <q-input
              v-model="filters.paciente"
              dense outlined clearable
              label="Paciente / CI"
              @keyup.enter="fetchData"
            >
              <template v-slot:prepend><q-icon name="person_search" /></template>
            </q-input>
          </div>

          <!-- Botones -->
          <div class="col-12 col-sm col-md-auto row q-gutter-sm items-center">
            <q-btn
              color="primary" icon="search" label="Buscar" no-caps
              :loading="loading" @click="fetchData"
            />
            <q-btn
              flat color="grey-7" icon="clear_all" label="Limpiar" no-caps
              @click="clearFilters"
            />
          </div>

        </div>
      </q-card-section>
    </q-card>

    <!-- KPIs -->
    <div class="row q-col-gutter-md q-mb-md">

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Total solicitudes</div>
          <div class="text-h4 text-weight-bold">{{ summary.total_solicitudes }}</div>
          <div class="text-caption text-grey-6">en el rango seleccionado</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Total Bs</div>
          <div class="text-h4 text-weight-bold text-primary">{{ money(summary.total_monto) }}</div>
          <div class="text-caption text-grey-6">suma de servicios facturados</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7">Embarazadas</div>
          <div class="text-h4 text-weight-bold text-purple">{{ summary.embarazadas }}</div>
          <div class="text-caption text-grey-6">pacientes con embarazo registrado</div>
        </q-card>
      </div>

      <div class="col-6 col-sm-3">
        <q-card flat bordered class="q-pa-md">
          <div class="text-caption text-grey-7 q-mb-sm">Exportar</div>
          <q-btn-dropdown
            color="primary" icon="download" label="Exportar" no-caps
            :loading="loadingExcel || loadingExcelMensual || loadingPdf || loadingEnts"
            :disable="loading || rows.length === 0"
            dropdown-icon="expand_more"
          >
            <q-list dense>
              <q-item clickable v-close-popup @click="downloadExcel" :disable="loadingExcel">
                <q-item-section avatar><q-icon name="grid_on" color="primary" /></q-item-section>
                <q-item-section>Excel — Solicitudes</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="downloadExcelMensual" :disable="loadingExcelMensual">
                <q-item-section avatar><q-icon name="table_chart" color="teal" /></q-item-section>
                <q-item-section>Excel — Registro Mensual VIH</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="openPdf" :disable="loadingPdf">
                <q-item-section avatar><q-icon name="picture_as_pdf" color="negative" /></q-item-section>
                <q-item-section>PDF — Solicitudes</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="downloadExcelEnts" :disable="loadingEnts">
                <q-item-section avatar><q-icon name="bar_chart" color="deep-orange" /></q-item-section>
                <q-item-section>Excel — Informe ENTs Química</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="openPdfEnts" :disable="loadingEnts">
                <q-item-section avatar><q-icon name="assessment" color="deep-orange" /></q-item-section>
                <q-item-section>PDF — Informe ENTs Química</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-card>
      </div>

    </div>

    <!-- TABLA -->
    <q-card flat bordered>
      <q-card-section class="row items-center q-pb-none">
        <div class="col">
          <span class="text-subtitle1 text-weight-bold">Solicitudes</span>
          <q-badge color="primary" :label="rows.length" class="q-ml-sm" />
        </div>
        <div class="col-auto">
          <q-input
            v-model="tableFilter" dense outlined
            placeholder="Buscar en tabla..."
            style="min-width: 240px"
            clearable
          >
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
        </div>
      </q-card-section>

      <q-separator class="q-mt-sm" />

      <q-card-section class="q-pa-none">
        <q-table
          :rows="rows"
          :columns="columns"
          row-key="id"
          dense flat
          :filter="tableFilter"
          :rows-per-page-options="[15, 25, 50, 100, 0]"
          :pagination="{ rowsPerPage: 25 }"
          :loading="loading"
        >
          <!-- Género -->
          <template v-slot:body-cell-paciente_genero="props">
            <q-td :props="props" class="text-center">
              <q-chip
                dense size="sm"
                :color="props.row.paciente_genero === 'M' ? 'blue-2' : 'pink-2'"
                :text-color="props.row.paciente_genero === 'M' ? 'blue-9' : 'pink-9'"
              >
                {{ props.row.paciente_genero || '—' }}
              </q-chip>
            </q-td>
          </template>

          <!-- Embarazada -->
          <template v-slot:body-cell-paciente_embarazo="props">
            <q-td :props="props" class="text-center">
              <q-icon
                v-if="props.row.paciente_embarazo"
                name="pregnant_woman" color="purple-7" size="18px"
              >
                <q-tooltip>Embarazada</q-tooltip>
              </q-icon>
              <span v-else class="text-grey-4 text-caption">—</span>
            </q-td>
          </template>

          <!-- Total Bs -->
          <template v-slot:body-cell-total_monto="props">
            <q-td :props="props" class="text-right">
              <span class="text-weight-medium text-primary">{{ money(props.row.total_monto) }}</span>
            </q-td>
          </template>

          <!-- Estado -->
          <template v-slot:body-cell-estado="props">
            <q-td :props="props">
              <q-chip dense size="sm" :color="estadoColor(props.row.estado)" text-color="white">
                {{ props.row.estado }}
              </q-chip>
            </q-td>
          </template>

          <!-- Servicios: truncado + tooltip con lista completa -->
          <template v-slot:body-cell-servicios_nombres="props">
            <q-td :props="props">
              <template v-if="props.row.servicios_nombres">
                <span class="text-caption cursor-pointer" style="white-space: nowrap;">
                  {{ truncar(props.row.servicios_nombres) }}
                  <q-badge
                    v-if="contarItems(props.row.servicios_nombres) > 1"
                    color="blue-grey-6" text-color="white"
                    :label="`${contarItems(props.row.servicios_nombres)}`"
                    class="q-ml-xs"
                  />
                  <q-tooltip max-width="320px" class="bg-grey-9 text-white text-caption">
                    <div v-for="s in props.row.servicios_nombres.split(' | ')" :key="s" class="q-py-xs">
                      • {{ s }}
                    </div>
                  </q-tooltip>
                </span>
              </template>
              <span v-else class="text-grey-4 text-caption">—</span>
            </q-td>
          </template>

          <!-- Áreas / Prestaciones -->
          <template v-slot:body-cell-areas_nombres="props">
            <q-td :props="props">
              <template v-if="props.row.areas_nombres">
                <q-chip
                  v-for="area in props.row.areas_nombres.split(' | ')"
                  :key="area"
                  dense size="sm"
                  color="teal-1" text-color="teal-9"
                  class="q-mr-xs q-mb-xs"
                >
                  {{ area }}
                </q-chip>
              </template>
              <span v-else class="text-grey-4 text-caption">—</span>
            </q-td>
          </template>

          <!-- Sin datos -->
          <template v-slot:no-data>
            <div class="full-width row flex-center q-pa-xl text-grey-6">
              <q-icon name="search_off" size="40px" class="q-mr-md" />
              <span>Sin resultados. Ajusta los filtros y presiona Buscar.</span>
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
  name: 'ReportesServiciosResumenPage',

  data () {
    return {
      loading: false,
      loadingPdf: false,
      loadingExcel: false,
      loadingExcelMensual: false,
      loadingEnts: false,
      tableFilter: '',

      filters: {
        date_from: '',
        date_to: '',
        area_id: null,
        servicio_id: null,
        genero: null,
        embarazada: null,
        cama: '',
        paciente: ''
      },

      rows: [],
      summary: {
        total_solicitudes: 0,
        total_monto: 0,
        embarazadas: 0
      },

      areas: [],
      servicios: [],
      servicioOptionsFiltrados: [],

      columns: [
        { name: 'codigo',           label: 'Código', field: 'codigo',           align: 'center', sortable: true, style: 'font-weight:600' },
        { name: 'fecha_solicitud',  label: 'Fecha',  field: 'fecha_solicitud',  align: 'center', sortable: true },
        { name: 'paciente_nombre',  label: 'Paciente', field: 'paciente_nombre', align: 'left', sortable: true },
        { name: 'paciente_ci',      label: 'CI',     field: 'paciente_ci',      align: 'center', sortable: true },
        { name: 'paciente_edad',    label: 'Edad',   field: 'paciente_edad',    align: 'center', sortable: true },
        { name: 'paciente_genero',  label: 'Gén.',   field: 'paciente_genero',  align: 'center', sortable: true },
        { name: 'paciente_embarazo', label: 'Emb.',  field: 'paciente_embarazo', align: 'center', sortable: true },
        { name: 'cama',             label: 'Cama',   field: 'cama',             align: 'center', sortable: true },
        { name: 'areas_nombres',    label: 'Prestaciones', field: 'areas_nombres', align: 'left', sortable: true },
        { name: 'servicios_nombres', label: 'Servicios', field: 'servicios_nombres', align: 'left', sortable: true },
        { name: 'total_monto',      label: 'Total Bs', field: 'total_monto',    align: 'right', sortable: true },
        { name: 'estado',           label: 'Estado', field: 'estado',           align: 'left', sortable: true }
      ]
    }
  },

  computed: {
    areaOptions () {
      return this.areas.map(a => ({ label: a.name, value: a.id }))
    },

    allServicioOptions () {
      return this.servicios
        .filter(s => !this.filters.area_id || s.area_id === this.filters.area_id)
        .map(s => ({ label: s.nombre, value: s.id }))
    }
  },

  async mounted () {
    const hoy = moment().format('YYYY-MM-DD')
    this.filters.date_from = hoy
    this.filters.date_to   = hoy

    await this.loadAreas()
    this.servicioOptionsFiltrados = this.allServicioOptions
    await this.fetchData()
  },

  methods: {
    money (n) {
      return parseFloat(n || 0).toFixed(2) + ' Bs'
    },

    contarItems (str) {
      if (!str) return 0
      return str.split(' | ').length
    },

    truncar (str, max = 28) {
      if (!str) return '—'
      const primero = str.split(' | ')[0]
      return primero.length > max ? primero.substring(0, max) + '…' : primero
    },

    estadoColor (estado) {
      const map = {
        CREADO: 'blue-grey',
        ATENDIENDO: 'orange',
        FINALIZADO: 'positive',
        RECHAZADO: 'negative'
      }
      return map[estado] || 'grey'
    },

    async loadAreas () {
      try {
        const res = await this.$axios.get('areas')
        this.areas = Array.isArray(res.data) ? res.data : (res.data?.data ?? [])
        // Extraer todos los servicios desde las áreas anidadas
        this.servicios = this.areas.flatMap(a =>
          (a.servicios || []).map(s => ({ ...s, area_id: s.area_id ?? a.id }))
        )
      } catch {}
    },

    onAreaChange () {
      this.filters.servicio_id = null
      this.servicioOptionsFiltrados = this.allServicioOptions
    },

    filterServicios (val, update) {
      const opts = this.allServicioOptions
      if (!val) {
        update(() => { this.servicioOptionsFiltrados = opts })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.servicioOptionsFiltrados = opts.filter(o => o.label.toLowerCase().includes(needle))
      })
    },

    async fetchData () {
      this.loading = true
      try {
        const params = {}
        if (this.filters.date_from)   params.date_from   = this.filters.date_from
        if (this.filters.date_to)     params.date_to     = this.filters.date_to
        if (this.filters.area_id)     params.area_id     = this.filters.area_id
        if (this.filters.servicio_id) params.servicio_id = this.filters.servicio_id
        if (this.filters.genero)      params.genero      = this.filters.genero
        if (this.filters.embarazada !== null && this.filters.embarazada !== '') {
          params.embarazada = this.filters.embarazada
        }
        if (this.filters.cama)        params.cama        = this.filters.cama
        if (this.filters.paciente)    params.paciente    = this.filters.paciente

        const res  = await this.$axios.get('reportes/servicios-resumen', { params })
        const data = res.data || {}

        this.rows    = data.rows    || []
        this.summary = data.summary || { total_solicitudes: 0, total_monto: 0, embarazadas: 0 }
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: e.response?.data?.message || 'Error cargando el reporte' })
      } finally {
        this.loading = false
      }
    },

    clearFilters () {
      this.filters = {
        date_from: moment().format('YYYY-MM-DD'),
        date_to:   moment().format('YYYY-MM-DD'),
        area_id: null,
        servicio_id: null,
        genero: null,
        embarazada: null,
        cama: '',
        paciente: ''
      }
      this.servicioOptionsFiltrados = this.allServicioOptions
    },

    buildParams () {
      const p = {}
      if (this.filters.date_from)   p.date_from   = this.filters.date_from
      if (this.filters.date_to)     p.date_to     = this.filters.date_to
      if (this.filters.area_id)     p.area_id     = this.filters.area_id
      if (this.filters.servicio_id) p.servicio_id = this.filters.servicio_id
      if (this.filters.genero)      p.genero      = this.filters.genero
      if (this.filters.embarazada !== null && this.filters.embarazada !== '') {
        p.embarazada = this.filters.embarazada
      }
      if (this.filters.cama)        p.cama        = this.filters.cama
      if (this.filters.paciente)    p.paciente    = this.filters.paciente
      return p
    },

    async downloadExcel () {
      this.loadingExcel = true
      try {
        const res = await this.$axios.get('reportes/servicios-resumen/excel', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 120000
        })
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url  = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = `solicitudes_${this.filters.date_from}_${this.filters.date_to}.xlsx`
        link.click()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: 'Error generando Excel' })
      } finally {
        this.loadingExcel = false
      }
    },

    async downloadExcelMensual () {
      this.loadingExcelMensual = true
      try {
        const res = await this.$axios.get('reportes/servicios-resumen/excel-mensual', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 120000
        })
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url  = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = `registro_vih_${this.filters.date_from}_${this.filters.date_to}.xlsx`
        link.click()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: 'Error generando Registro Mensual' })
      } finally {
        this.loadingExcelMensual = false
      }
    },

    async openPdf () {
      this.loadingPdf = true
      try {
        const res = await this.$axios.get('reportes/servicios-resumen/pdf', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 180000
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url  = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: 'Error generando PDF. Intenta con un rango de fechas más corto.' })
      } finally {
        this.loadingPdf = false
      }
    },

    async downloadExcelEnts () {
      this.loadingEnts = true
      try {
        const res = await this.$axios.get('reportes/servicios-resumen/excel-ents', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 120000
        })
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url  = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = `ents_quimica_${this.filters.date_from}_${this.filters.date_to}.xlsx`
        link.click()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: 'Error generando Excel ENTs' })
      } finally {
        this.loadingEnts = false
      }
    },

    async openPdfEnts () {
      this.loadingEnts = true
      try {
        const res = await this.$axios.get('reportes/servicios-resumen/pdf-ents', {
          params: this.buildParams(),
          responseType: 'blob',
          timeout: 120000
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url  = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
      } catch (e) {
        this.$q?.notify({ type: 'negative', message: 'Error generando PDF ENTs' })
      } finally {
        this.loadingEnts = false
      }
    }
  }
}
</script>
