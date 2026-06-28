<template>
  <q-page class="q-pa-md bg-grey-2">
    <!-- HEADER -->
    <div class="row items-center q-mb-md">
      <div class="col-12 col-md-6">
        <div class="text-h5 text-weight-bold">Reporte de Consentimientos</div>
        <div class="text-caption text-grey-7">
          Totales por usuario y evolución por tiempo (acepta / rechaza).
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="row items-center q-col-gutter-sm justify-end">
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.date_from" type="date" dense outlined label="Desde" />
          </div>
          <div class="col-12 col-sm-3">
            <q-input v-model="filters.date_to" type="date" dense outlined label="Hasta" />
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filters.group_by"
              :options="groupOptions"
              dense outlined
              label="Agrupar"
              emit-value
              map-options
            />
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filters.user_id"
              :options="usersOptions"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              clearable
              dense outlined
              label="Usuario"
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
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Total consentimientos</div>
              <div class="text-h5 text-weight-bold">{{ resumen.total || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="fact_check" size="36px" class="text-primary" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Total en el rango y filtros seleccionados.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Aceptados</div>
              <div class="text-h5 text-weight-bold">{{ resumen.acepta || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="check_circle" size="36px" class="text-positive" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            % Acepta: {{ resumen.pct_acepta || 0 }}%
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Rechazados</div>
              <div class="text-h5 text-weight-bold">{{ resumen.rechaza || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="cancel" size="36px" class="text-negative" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            % Rechaza: {{ resumen.pct_rechaza || 0 }}%
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Usuarios con registros</div>
              <div class="text-h5 text-weight-bold">{{ porUsuario.length || 0 }}</div>
            </div>
            <div class="col-auto">
              <q-icon name="groups" size="36px" class="text-indigo" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Cantidad de usuarios que registraron consentimientos.
          </div>
        </q-card>
      </div>
    </div>

    <!-- CHARTS -->
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Donut acepta/rechaza -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Distribución (Acepta vs Rechaza)
          </div>
          <apexchart
            type="donut"
            height="300"
            :options="chartDonut.options"
            :series="chartDonut.series"
          />
        </q-card>
      </div>

      <!-- Histograma por tiempo -->
      <div class="col-12 col-md-8">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Evolución por {{ labelGroup }}
          </div>
          <apexchart
            type="bar"
            height="300"
            :options="chartHist.options"
            :series="chartHist.series"
          />
        </q-card>
      </div>
    </div>

    <!-- TABLA POR USUARIO -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">Resumen por usuario</div>
        <div class="text-caption text-grey-7">
          Totales, aceptados, rechazados y % aceptación.
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <q-table
          :rows="porUsuario"
          :columns="columnsUsuarios"
          row-key="user_id"
          dense
          flat
          :rows-per-page-options="[10, 20, 0]"
        >
          <template #body-cell-pct_acepta="props">
            <q-td :props="props" class="text-right">
              <q-chip dense color="positive" text-color="white">
                {{ props.row.pct_acepta }}%
              </q-chip>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- ÚLTIMOS REGISTROS -->
    <q-card flat bordered>
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold">Últimos consentimientos</div>
        <div class="text-caption text-grey-7">Últimos 20 en el rango filtrado.</div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <q-table
          :rows="ultimos"
          :columns="columnsUltimos"
          row-key="id"
          dense
          flat
          :rows-per-page-options="[10, 20, 0]"
        >
          <template #body-cell-tipo="props">
            <q-td :props="props">
              <q-chip
                dense
                :color="props.row.tipo === 'ACEPTA' ? 'positive' : (props.row.tipo === 'RECHAZA' ? 'negative' : 'grey-7')"
                text-color="white"
              >
                {{ props.row.tipo || 'SIN TIPO' }}
              </q-chip>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'ConsentimientoReporte',
  data () {
    return {
      loading: false,

      filters: {
        date_from: '',
        date_to: '',
        user_id: null,
        group_by: 'day'
      },

      groupOptions: [
        { label: 'Día', value: 'day' },
        { label: 'Semana', value: 'week' },
        { label: 'Mes', value: 'month' }
      ],

      usersOptions: [],

      resumen: {},
      porUsuario: [],
      serieTiempo: [],
      ultimos: [],

      columnsUsuarios: [
        { name: 'user_name', label: 'Usuario', field: 'user_name', align: 'left' },
        { name: 'total', label: 'Total', field: 'total', align: 'right' },
        { name: 'acepta', label: 'Acepta', field: 'acepta', align: 'right' },
        { name: 'rechaza', label: 'Rechaza', field: 'rechaza', align: 'right' },
        { name: 'pct_acepta', label: '% Acepta', field: 'pct_acepta', align: 'right' }
      ],

      columnsUltimos: [
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'fecha_consentimiento', label: 'Fecha', field: 'fecha_consentimiento', align: 'left' },
        { name: 'tipo', label: 'Tipo', field: 'tipo', align: 'left' },
        { name: 'paciente', label: 'Paciente', field: 'paciente', align: 'left' },
        { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
        { name: 'user', label: 'Usuario', field: 'user', align: 'left' }
      ],

      chartDonut: {
        series: [0, 0],
        options: {
          labels: ['ACEPTA', 'RECHAZA'],
          legend: { position: 'bottom' },
          dataLabels: { enabled: true }
        }
      },

      chartHist: {
        series: [
          { name: 'Total', data: [] },
          { name: 'ACEPTA', data: [] },
          { name: 'RECHAZA', data: [] }
        ],
        options: {
          chart: { stacked: false, toolbar: { show: false } },
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
          dataLabels: { enabled: false },
          legend: { position: 'top' }
        }
      }
    }
  },
  computed: {
    labelGroup () {
      const v = this.filters.group_by
      if (v === 'week') return 'semana'
      if (v === 'month') return 'mes'
      return 'día'
    }
  },
  mounted () {
    // Por defecto: últimos 30 días
    this.filters.date_to = moment().format('YYYY-MM-DD')
    this.filters.date_from = moment().subtract(30, 'days').format('YYYY-MM-DD')

    this.loadUsers()
    this.fetchReporte()
  },
  methods: {
    async loadUsers () {
      try {
        const res = await this.$axios.get('users')
        // users: [{id,name,...}]
        this.usersOptions = (res.data || []).map(u => ({ id: u.id, name: u.name }))
      } catch (e) {
        // opcional
      }
    },

    async fetchReporte () {
      this.loading = true
      try {
        const res = await this.$axios.get('reportes/consentimientos', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to,
            user_id: this.filters.user_id,
            group_by: this.filters.group_by
          }
        })

        const data = res.data || {}
        this.resumen = data.resumen || {}
        this.porUsuario = data.por_usuario || []
        this.serieTiempo = data.serie_tiempo || []
        this.ultimos = data.ultimos || []

        // DONUT
        this.chartDonut.series = [
          Number(this.resumen.acepta || 0),
          Number(this.resumen.rechaza || 0)
        ]

        // HISTOGRAMA
        const cats = this.serieTiempo.map(e => e.periodo)
        this.chartHist.options = {
          ...this.chartHist.options,
          xaxis: { categories: cats }
        }
        this.chartHist.series = [
          { name: 'Total', data: this.serieTiempo.map(e => Number(e.total || 0)) },
          { name: 'ACEPTA', data: this.serieTiempo.map(e => Number(e.acepta || 0)) },
          { name: 'RECHAZA', data: this.serieTiempo.map(e => Number(e.rechaza || 0)) }
        ]
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'Error cargando reporte')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
