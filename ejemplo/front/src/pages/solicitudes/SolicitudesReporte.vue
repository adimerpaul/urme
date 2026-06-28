<!-- src/pages/SolicitudesReporte.vue -->
<template>
  <q-page class="q-pa-md bg-grey-2">
    <!-- HEADER -->
    <div class="row items-center q-mb-md">
      <div class="col-12 col-md-6">
        <div class="text-h5 text-weight-bold">Reporte de Solicitudes (Usuarios + Prestaciones)</div>
        <div class="text-caption text-grey-7">
          Filtra por usuario/prestaciones y visualiza conteos, top y evolución.
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="row items-center q-col-gutter-sm justify-end">
          <div class="col-12 col-sm-4">
            <q-input v-model="filters.date_from" type="date" dense outlined label="Desde" />
          </div>
          <div class="col-12 col-sm-4">
            <q-input v-model="filters.date_to" type="date" dense outlined label="Hasta" />
          </div>
          <div class="col-12 col-sm-4">
            <q-select
              v-model="filters.group"
              :options="groupOptions"
              dense outlined
              label="Agrupar"
              emit-value
              map-options
            />
          </div>

          <div class="col-12 col-sm-6">
            <q-select
              v-model="filters.user_id"
              :options="usersOptions"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              dense outlined
              clearable
              label="Usuario"
            />
          </div>

          <div class="col-12 col-sm-6">
            <q-select
              v-model="filters.servicio_id"
              :options="prestacionesOptions"
              option-label="nombre"
              option-value="id"
              emit-value
              map-options
              dense outlined
              multiple
              use-chips
              clearable
              label="Prestaciones"
            />
          </div>

          <div class="col-auto">
            <q-btn
              color="primary"
              icon="refresh"
              label="Actualizar"
              no-caps
              :loading="loading"
              @click="fetchReporte"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Solicitudes</div>
              <div class="text-h5 text-weight-bold">{{ resumen.total_solicitudes || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="receipt_long" size="36px" class="text-primary" />
            </div>
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Prestaciones registradas</div>
              <div class="text-h5 text-weight-bold">{{ resumen.total_prestaciones || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="science" size="36px" class="text-secondary" />
            </div>
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Prestaciones / solicitud</div>
              <div class="text-h5 text-weight-bold">{{ resumen.promedio_prestaciones || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="bar_chart" size="36px" class="text-teal" />
            </div>
          </div>
        </q-card>
      </div>
    </div>

    <!-- CHARTS -->
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Donut: prestaciones por usuario -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">Prestaciones por usuario (Top)</div>
          <apexchart
            type="donut"
            height="300"
            :options="chartDonutUsuarios.options"
            :series="chartDonutUsuarios.series"
          />
        </q-card>
      </div>

      <!-- Histograma: top prestaciones -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">Top prestaciones (registradas)</div>
          <apexchart
            type="bar"
            height="300"
            :options="chartTopPrestaciones.options"
            :series="chartTopPrestaciones.series"
          />
        </q-card>
      </div>

      <!-- Serie: tiempo -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">Evolución (Solicitudes vs Prestaciones)</div>
          <apexchart
            type="bar"
            height="300"
            :options="chartSerie.options"
            :series="chartSerie.series"
          />
        </q-card>
      </div>
    </div>

    <!-- TABLAS -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Por usuario</div>
            <div class="text-caption text-grey-7">Solicitudes y prestaciones registradas por usuario.</div>
          </q-card-section>
          <q-separator />
          <q-card-section class="q-pa-none">
            <q-table
              :rows="porUsuario"
              :columns="columnsUsuario"
              row-key="user_id"
              dense
              flat
              :rows-per-page-options="[10, 20, 0]"
            />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold">Top prestaciones</div>
            <div class="text-caption text-grey-7">Cuántas veces se registró cada prestación.</div>
          </q-card-section>
          <q-separator />
          <q-card-section class="q-pa-none">
            <q-table
              :rows="topPrestaciones"
              :columns="columnsPrestacion"
              row-key="prestacion_id"
              dense
              flat
              :rows-per-page-options="[10, 20, 0]"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered>
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">Últimas solicitudes</div>
        <div class="text-caption text-grey-7">Últimas 20 solicitudes (con conteo de prestaciones).</div>
      </q-card-section>
      <q-separator />
      <q-card-section class="q-pa-none">
        <q-table
          :rows="ultimas"
          :columns="columnsUltimas"
          row-key="id"
          dense
          flat
          :rows-per-page-options="[10, 20, 0]"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'SolicitudesReporte',
  data () {
    return {
      loading: false,
      filters: {
        date_from: '',
        date_to: '',
        user_id: null,
        servicio_id: [], // 👈 se mantiene porque el backend filtra por servicio_id
        group: 'day'
      },
      groupOptions: [
        { label: 'Día', value: 'day' },
        { label: 'Semana', value: 'week' },
        { label: 'Mes', value: 'month' }
      ],

      resumen: {},
      porUsuario: [],
      topPrestaciones: [],
      serie: [],
      ultimas: [],

      usersOptions: [],
      prestacionesOptions: [],

      columnsUsuario: [
        { name: 'user_name', label: 'Usuario', field: 'user_name', align: 'left' },
        { name: 'username', label: 'Username', field: 'username', align: 'left' },
        { name: 'solicitudes', label: 'Solicitudes', field: 'solicitudes', align: 'right' },
        { name: 'prestaciones', label: 'Prestaciones', field: 'prestaciones', align: 'right' }
      ],
      columnsPrestacion: [
        { name: 'prestacion_nombre', label: 'Prestación', field: 'prestacion_nombre', align: 'left' },
        { name: 'solicitudes', label: 'Solicitudes', field: 'solicitudes', align: 'right' },
        { name: 'total', label: 'Registradas', field: 'total', align: 'right' }
      ],
      columnsUltimas: [
        { name: 'nro_registro', label: 'Nro Registro', field: 'nro_registro', align: 'left' },
        { name: 'codigo_solicitud', label: 'Código', field: 'codigo_solicitud', align: 'left' },
        { name: 'paciente_nombre', label: 'Paciente', field: 'paciente_nombre', align: 'left' },
        { name: 'doctor_nombre', label: 'Doctor', field: 'doctor_nombre', align: 'left' },
        { name: 'user_name', label: 'Usuario', field: 'user_name', align: 'left' },
        { name: 'cant_prestaciones', label: 'N° prestaciones', field: 'cant_prestaciones', align: 'right' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
        { name: 'fecha_creacion', label: 'Fecha', field: 'fecha_creacion', align: 'left' }
      ],

      // CHARTS
      chartDonutUsuarios: {
        series: [],
        options: {
          labels: [],
          legend: { position: 'bottom' },
          dataLabels: { enabled: true }
        }
      },
      chartTopPrestaciones: {
        series: [{ name: 'Registradas', data: [] }],
        options: {
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
          dataLabels: { enabled: false }
        }
      },
      chartSerie: {
        series: [
          { name: 'Solicitudes', data: [] },
          { name: 'Prestaciones', data: [] }
        ],
        options: {
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
          dataLabels: { enabled: false },
          legend: { position: 'top' }
        }
      }
    }
  },
  mounted () {
    const today = moment().format('YYYY-MM-DD')
    const from = moment().subtract(30, 'days').format('YYYY-MM-DD')
    this.filters.date_from = from
    this.filters.date_to = today

    this.loadUsers()
    this.loadPrestaciones()
    this.fetchReporte()
  },
  methods: {
    async loadUsers () {
      try {
        const res = await this.$axios.get('users')
        this.usersOptions = res.data || []
      } catch (e) {
        this.usersOptions = []
      }
    },
    async loadPrestaciones () {
      try {
        // 👇 el endpoint sigue siendo servicios (BD), solo cambiamos el nombre en UI
        const res = await this.$axios.get('servicios')
        this.prestacionesOptions = res.data || []
      } catch (e) {
        this.prestacionesOptions = []
      }
    },

    async fetchReporte () {
      this.loading = true
      try {
        const res = await this.$axios.get('reportes/solicitudes-servicios', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to,
            user_id: this.filters.user_id,
            servicio_id: this.filters.servicio_id && this.filters.servicio_id.length
              ? this.filters.servicio_id
              : null,
            group: this.filters.group
          }
        })

        const data = res.data || {}
        this.resumen = data.resumen || {}
        this.porUsuario = data.por_usuario || []
        this.topPrestaciones = data.top_prestaciones || []
        this.serie = data.serie || []
        this.ultimas = data.ultimas || []

        // ---- donut usuarios (Top 8 por prestaciones)
        const topU = [...this.porUsuario]
          .sort((a, b) => (b.prestaciones || 0) - (a.prestaciones || 0))
          .slice(0, 8)

        this.chartDonutUsuarios.series = topU.map(x => x.prestaciones || 0)
        this.chartDonutUsuarios.options = {
          ...this.chartDonutUsuarios.options,
          labels: topU.map(x => x.user_name || 'SIN NOMBRE')
        }

        // ---- top prestaciones (bar horizontal)
        this.chartTopPrestaciones.series = [
          { name: 'Registradas', data: this.topPrestaciones.map(x => x.total || 0) }
        ]
        this.chartTopPrestaciones.options = {
          ...this.chartTopPrestaciones.options,
          xaxis: { categories: this.topPrestaciones.map(x => x.prestacion_nombre || 'SIN NOMBRE') }
        }

        // ---- serie tiempo (bar)
        this.chartSerie.series = [
          { name: 'Solicitudes', data: this.serie.map(x => x.solicitudes || 0) },
          { name: 'Prestaciones', data: this.serie.map(x => x.prestaciones || 0) }
        ]
        this.chartSerie.options = {
          ...this.chartSerie.options,
          xaxis: { categories: this.serie.map(x => x.label) }
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'Error cargando reporte')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
