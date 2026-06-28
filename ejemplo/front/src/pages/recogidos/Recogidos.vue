<template>
  <q-page class="q-pa-sm">
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="q-pb-xs">
        <div class="row q-col-gutter-sm items-end">
          <div class="col-12 col-sm-auto">
            <q-input v-model="filters.from" type="date" dense outlined label="Desde" style="min-width: 140px" />
          </div>
          <div class="col-12 col-sm-auto">
            <q-input v-model="filters.to" type="date" dense outlined label="Hasta" style="min-width: 140px" />
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filters.area_id"
              :options="areaOptions"
              :option-label="areaLabel"
              option-value="id"
              emit-value
              map-options
              clearable
              dense
              outlined
              label="Área"
            />
          </div>
          <div class="col-12 col-sm">
            <q-input dense outlined v-model="filters.search" label="Buscar paciente / código / doctor" @keyup.enter="fetchRows">
              <template #append><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-auto">
            <q-btn color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="fetchRows" />
          </div>
          <div class="col-auto">
            <q-btn-dropdown color="grey-7" icon="description" label="Reportes" no-caps :loading="loadingReport" flat dense>
              <q-list dense>
                <q-item clickable v-close-popup @click="openReporte('dia')">
                  <q-item-section>Reporte por día</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openReporte('area')">
                  <q-item-section>Reporte por área</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openReporte('pendientes')">
                  <q-item-section>Pendientes</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openReporte('activos')">
                  <q-item-section>Activos</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openReporte('recogidos')">
                  <q-item-section>Recogidos</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </div>
      </q-card-section>
      <q-card-section class="q-pt-xs q-pb-sm">
        <div class="row q-gutter-xs items-center">
          <span class="text-caption text-grey-6 q-mr-xs">Filtrar por estado:</span>
          <q-chip
            dense square clickable class="q-ma-none text-caption"
            :color="filters.estado === '' ? 'blue-grey-7' : 'blue-grey-2'"
            :text-color="filters.estado === '' ? 'white' : 'blue-grey-9'"
            icon="list"
            @click="setEstado('')"
          >Todos</q-chip>
          <q-chip
            dense square clickable class="q-ma-none text-caption"
            :color="filters.estado === 'pendiente' ? 'orange-7' : 'orange-2'"
            :text-color="filters.estado === 'pendiente' ? 'white' : 'orange-10'"
            icon="hourglass_empty"
            @click="setEstado('pendiente')"
          >Pendiente</q-chip>
          <q-chip
            dense square clickable class="q-ma-none text-caption"
            :color="filters.estado === 'con_resultado' ? 'teal-7' : 'teal-2'"
            :text-color="filters.estado === 'con_resultado' ? 'white' : 'teal-10'"
            icon="science"
            @click="setEstado('con_resultado')"
          >Con resultado</q-chip>
          <q-chip
            dense square clickable class="q-ma-none text-caption"
            :color="filters.estado === 'recogido' ? 'green-7' : 'green-2'"
            :text-color="filters.estado === 'recogido' ? 'white' : 'green-10'"
            icon="check_circle"
            @click="setEstado('recogido')"
          >Recogido</q-chip>
        </div>
      </q-card-section>
    </q-card>

    <q-table
      :rows="rows"
      :columns="columns"
      row-key="id"
      dense
      flat
      bordered
      v-model:pagination="pagination"
      :loading="loading"
      @request="onRequest"
      :rows-per-page-options="[10, 20, 50]"
      title="Recojo de resultados por área"
    >
      <template #body-cell-codigo="props">
        <q-td :props="props" class="text-weight-bold text-primary">
          {{ props.row.codigo || '-' }}
        </q-td>
      </template>

      <template #body-cell-servicios="props">
        <q-td :props="props">
          <div class="row q-gutter-xs items-start q-mb-xs">
            <div
              v-for="ga in groupedAreas(props.row.servicio_solicitudes)"
              :key="`${props.row.id}-${ga.area_id}`"
              class="column items-start"
            >
              <q-chip
                :color="areaStatusColor(ga)"
                :text-color="areaTextColor(ga)"
                :icon="areaStatusIcon(ga)"
                clickable
                dense
                square
                class="q-ma-none"
                style="cursor: pointer"
                @click="openDialogByAreas(props.row, [ga])"
              >
                {{ ga.area_nombre }}
                <span class="q-ml-xs text-caption opacity-75">({{ ga.recogidos }}/{{ ga.total_servicios }})</span>
              </q-chip>
              <div v-if="ga.all_recogido && ga.recogido_en_dia" class="text-grey-6 q-mt-none" style="font-size:10px; line-height:1.3;">
                <q-icon name="schedule" size="10px" color="grey-5" />
                {{ formatRecogidoDate(ga.recogido_en_dia) }}
              </div>
            </div>
          </div>
          <q-btn
            v-if="groupedAreas(props.row.servicio_solicitudes).length > 1"
            size="xs"
            flat
            dense
            color="primary"
            icon="done_all"
            label="Recoger todas las áreas"
            no-caps
            @click="openDialogSelected(props.row)"
          />
          <div v-if="props.row.user_presentacion" class="text-caption text-grey-7 q-mt-xs" style="font-size:11px;">
            <q-icon name="person_pin" size="12px" color="teal-6" />
            <span class="text-teal-8 text-weight-medium q-ml-xs">{{ props.row.user_presentacion }}</span>
            <q-icon name="schedule" size="11px" color="grey-6" class="q-ml-sm" />
            <span class="q-ml-xs">{{ props.row.fecha_presentacion }}</span>
          </div>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 520px; max-width: 700px;">
        <q-card-section class="bg-grey-2 row items-center q-py-sm">
          <div>
            <div class="text-subtitle1 text-weight-medium">Registrar recojo</div>
            <div class="text-caption text-grey-7">
              {{ selectedSolicitud?.paciente_nombre || '-' }}
              <span v-if="selectedDialogAreas.length" class="q-ml-sm">
                · {{ selectedDialogAreas.map(x => x.area_nombre).join(', ') }}
              </span>
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section>
          <q-form @submit.prevent="saveRecogidoAreas">

            <!-- Radio Interno / Externo -->
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-12">
                <q-btn-toggle
                  v-model="tipoRecogido"
                  spread
                  no-caps
                  rounded
                  unelevated
                  :options="[
                    { label: 'Interno (paciente retira)', value: 'interno', icon: 'person' },
                    { label: 'Externo (otra persona)', value: 'externo', icon: 'group' }
                  ]"
                  :toggle-color="tipoRecogido === 'interno' ? 'teal' : 'orange'"
                  color="grey-3"
                  text-color="grey-8"
                  @update:model-value="onTipoRecogidoChange"
                />
              </div>
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.recogido_por_personal"
                  dense outlined label="Recogido por"
                  :readonly="tipoRecogido === 'interno'"
                  :bg-color="tipoRecogido === 'interno' ? 'teal-1' : ''"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.grado_parentesco"
                  :options="tipoRecogido === 'interno'
                    ? ['Interno', 'Personal', 'Personal sala']
                    : ['Padre', 'Madre', 'Hno/a', 'Abuelo/a', 'Tio/a', 'Primo/a', 'Otro', 'Personal', 'Interno', 'Personal sala']"
                  dense outlined label="Parentesco / Relación"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.telefono_recogido"
                  dense outlined label="Teléfono"
                  :readonly="tipoRecogido === 'interno'"
                  :bg-color="tipoRecogido === 'interno' ? 'teal-1' : ''"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.ci_recogido"
                  dense outlined label="C.I."
                  :readonly="tipoRecogido === 'interno'"
                  :bg-color="tipoRecogido === 'interno' ? 'teal-1' : ''"
                />
              </div>
            </div>

            <div class="text-right q-mt-md">
              <q-btn flat label="Cancelar" v-close-popup class="q-mr-sm" />
              <q-btn color="positive" label="Guardar recogido" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'RecogidosPage',
  data () {
    return {
      loading: false,
      loadingReport: false,
      saving: false,
      rows: [],
      areas: [],
      columns: [
        { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', style: 'width: 90px; font-weight:600' },
        { name: 'fecha', label: 'Fecha', field: row => row.fecha_solicitud || row.fecha_creacion || '', align: 'left', style: 'width: 100px' },
        { name: 'paciente', label: 'Paciente', field: row => row.paciente_nombre || '', align: 'left' },
        { name: 'doctor', label: 'Doctor', field: row => row.doctor?.nombre || row.doctor_nombre || '', align: 'left' },
        { name: 'servicios', label: 'Áreas', field: 'servicio_solicitudes', align: 'left' },
      ],
      pagination: {
        sortBy: 'id',
        descending: true,
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0,
      },
      filters: {
        search: '',
        from: moment().format('YYYY-MM-DD'),
        to: moment().format('YYYY-MM-DD'),
        area_id: null,
        estado: '',
      },
      dialog: false,
      tipoRecogido: 'externo',
      selectedSolicitud: null,
      selectedDialogAreas: [],
      form: {
        fue_recogido: false,
        recogido_por_personal: '',
        grado_parentesco: '',
        telefono_recogido: '',
        ci_recogido: '',
        recogido_en_dia: moment().format('YYYY-MM-DD'),
      },
    }
  },
  mounted () {
    this.fetchAreas()
    this.fetchRows()
  },
  computed: {
    isAdmin () {
      return this.$store.user?.role === 'Administrador'
    },
    areaOptions () {
      return [{ id: null, name: 'Todos' }, ...this.areas]
    },
  },
  methods: {
    areaLabel (opt) {
      if (opt?.id === null) return 'Todos'
      return opt?.title || opt?.name || `Area ${opt?.id ?? ''}`
    },
    setEstado (val) {
      this.filters.estado = val
      this.pagination.page = 1
      this.fetchRows()
    },
    fetchAreas () {
      this.$axios.get('areas')
        .then(res => { this.areas = res.data || [] })
    },
    params () {
      return {
        ...this.filters,
        area_id: this.filters.area_id || null,
        page: this.pagination.page,
        per_page: this.pagination.rowsPerPage,
      }
    },
    fetchRows () {
      this.loading = true
      this.$axios.get('recogidos', { params: this.params() })
        .then(res => {
          this.rows = res.data.rows || []
          this.pagination.rowsNumber = res.data.pagination?.rows_number || 0
          this.pagination.page = res.data.pagination?.page || 1
          this.pagination.rowsPerPage = res.data.pagination?.per_page || this.pagination.rowsPerPage
        })
        .finally(() => { this.loading = false })
    },
    onRequest (props) {
      this.pagination = props.pagination
      this.fetchRows()
    },
    async openReporte (tipo) {
      this.loadingReport = true
      try {
        const areaId = this.filters.area_id || null

        if (tipo === 'area' && !areaId) {
          this.$alert.error('Seleccione un área para el reporte por área')
          this.loadingReport = false
          return
        }

        const params = {
          tipo,
          from: this.filters.from || null,
          to: this.filters.to || null,
          date: this.filters.from || moment().format('YYYY-MM-DD'),
          search: this.filters.search || null,
          area_id: areaId,
        }
        const res = await this.$axios.get('reportes/recogidos/pdf', {
          params,
          responseType: 'blob',
        })

        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => window.URL.revokeObjectURL(url), 20000)
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'No se pudo generar el reporte')
      } finally {
        this.loadingReport = false
      }
    },
    groupedAreas (servicios = []) {
      const map = {}
      servicios.forEach(ss => {
        const areaId = ss.area_id || ss.area?.id || ss.servicio?.area?.id
        if (!areaId) return

        if (!map[areaId]) {
          map[areaId] = {
            area_id: areaId,
            area_nombre: ss.area?.title || ss.area?.name || ss.servicio?.area?.title || ss.servicio?.area?.name || `Area ${areaId}`,
            total_servicios: 0,
            recogidos: 0,
            realizados: 0,
            all_recogido: false,
            all_realizado: false,
            recogido_en_dia: null,
          }
        }

        map[areaId].total_servicios += 1
        if (ss.fue_recogido) {
          map[areaId].recogidos += 1
          if (ss.recogido_en_dia && (!map[areaId].recogido_en_dia || ss.recogido_en_dia > map[areaId].recogido_en_dia)) {
            map[areaId].recogido_en_dia = ss.recogido_en_dia
          }
        }
        if ((ss.realizado || '').toUpperCase() === 'REALIZADO') map[areaId].realizados += 1
      })

      return Object.values(map).map(x => ({
        ...x,
        all_recogido: x.total_servicios > 0 && x.total_servicios === x.recogidos,
        all_realizado: x.total_servicios > 0 && x.total_servicios === x.realizados,
      }))
    },
    areaTextColor (ga) {
      if (ga.all_recogido) return 'white'
      if (ga.all_realizado) return 'white'
      return 'grey-9'
    },
    areaStatusColor (ga) {
      if (ga.all_recogido) return 'green-7'
      if (ga.all_realizado) return 'orange-7'
      return 'grey-3'
    },
    areaStatusIcon (ga) {
      if (ga.all_recogido) return 'check_circle'
      if (ga.all_realizado) return 'science'
      return 'hourglass_empty'
    },
    formatRecogidoDate (date) {
      return moment(date).format('DD/MM/YY HH:mm')
    },
    onTipoRecogidoChange (val) {
      if (val === 'interno') {
        this.form.recogido_por_personal = this.selectedSolicitud?.paciente_nombre || ''
        this.form.grado_parentesco = 'Interno'
        this.form.telefono_recogido = this.selectedSolicitud?.paciente_telefono || ''
        this.form.ci_recogido = this.selectedSolicitud?.paciente_ci || ''
      } else {
        this.form.recogido_por_personal = ''
        this.form.grado_parentesco = ''
        this.form.telefono_recogido = ''
        this.form.ci_recogido = ''
      }
    },
    openDialogByAreas (solicitud, areasList) {
      this.selectedSolicitud = solicitud
      this.selectedDialogAreas = areasList

      const first = areasList[0]
      const firstService = (solicitud.servicio_solicitudes || []).find(x => (x.area_id || x.area?.id) === first.area_id)

      this.tipoRecogido = 'externo'

      this.form = {
        fue_recogido: true,
        recogido_por_personal: firstService?.recogido_por_personal || '',
        grado_parentesco: firstService?.grado_parentesco || '',
        telefono_recogido: firstService?.telefono_recogido || solicitud.paciente_telefono || '',
        ci_recogido: firstService?.ci_recogido || solicitud.paciente_ci || '',
        recogido_en_dia: firstService?.recogido_en_dia || moment().format('YYYY-MM-DD'),
      }

      this.dialog = true
    },
    openDialogSelected (solicitud) {
      const grouped = this.groupedAreas(solicitud.servicio_solicitudes)
      if (!grouped.length) return
      this.openDialogByAreas(solicitud, grouped)
    },
    async saveRecogidoAreas () {
      // if (!this.form.telefono_recogido) {
      //   this.$alert.error('El teléfono es obligatorio si fue recogido')
      //   return
      // }
      if (!this.selectedSolicitud?.id || !this.selectedDialogAreas.length) return
      this.saving = true

      try {
        for (const area of this.selectedDialogAreas) {
          await this.$axios.post('recogidos/recoger-area', {
            solicitude_id: this.selectedSolicitud.id,
            area_id: area.area_id,
            ...this.form,
          })
        }

        const row = this.rows.find(r => r.id === this.selectedSolicitud.id)
        if (row) {
          row.servicio_solicitudes = row.servicio_solicitudes.map(ss => {
            const hit = this.selectedDialogAreas.some(a => a.area_id === (ss.area_id || ss.area?.id || ss.servicio?.area?.id))
            if (!hit) return ss
            return {
              ...ss,
              fue_recogido: this.form.fue_recogido,
              recogido_por_personal: this.form.fue_recogido ? this.form.recogido_por_personal : null,
              grado_parentesco: this.form.fue_recogido ? this.form.grado_parentesco : null,
              telefono_recogido: this.form.fue_recogido ? this.form.telefono_recogido : null,
              ci_recogido: this.form.fue_recogido ? this.form.ci_recogido : null,
              recogido_en_dia: this.form.fue_recogido ? moment().format('YYYY-MM-DD HH:mm:ss') : null,
            }
          })
        }

        this.dialog = false
        this.$alert.success('Recogido actualizado correctamente')
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
