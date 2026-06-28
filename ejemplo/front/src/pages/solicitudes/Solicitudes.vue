<template>
  <q-page class="q-pa-sm">
    <q-card flat bordered>
      <q-card-section class="row items-center q-col-gutter-xs">
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.from" type="date" dense outlined label="Desde" />
        </div>
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.to" type="date" dense outlined label="Hasta" />
        </div>
        <div class="col-12 col-sm-3">
          <q-select
            v-model="filters.estado"
            :options="['', 'CREADO', 'ATENDIENDO', 'FINALIZADO']"
            dense outlined
            clearable
            label="Estado"
          />
        </div>
      </q-card-section>

      <q-card-section class="row items-center q-col-gutter-xs">
        <div class="col-12 col-sm-4">
          <q-input dense outlined v-model="search" clearable label="Buscar por código, paciente o CI">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-6 text-right q-gutter-xs">
          <q-btn
            color="red"
            icon="block"
            label="Muestras Rechazadas"
            no-caps
            :loading="loading"
            @click="getMuestrasRechazadas()"
          />
          <q-btn
            color="primary"
            icon="search"
            label="Filtrar"
            no-caps
            :loading="loading"
            @click="getSolicitudes"
          />
          <q-btn-dropdown
            color="teal"
            icon="download"
            label="Exportar"
            no-caps
            :loading="exportLoading"
          >
            <q-list dense>
              <q-item clickable v-close-popup @click="exportarPdf">
                <q-item-section avatar>
                  <q-icon name="picture_as_pdf" color="red-7" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Imprimir PDF</q-item-label>
                  <q-item-label caption>Descarga reporte en PDF</q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="exportarExcel">
                <q-item-section avatar>
                  <q-icon name="table_chart" color="green-7" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Exportar Excel</q-item-label>
                  <q-item-label caption>Descarga archivo .xlsx</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
          <q-btn
            color="positive"
            icon="add_circle_outline"
            label="Nueva"
            no-caps
            :loading="loading"
            @click="nuevo"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-table
      class="q-mt-sm"
      :rows="rows"
      :columns="columns"
      row-key="id"
      dense flat bordered
      :rows-per-page-options="[10, 25, 50, 100, 200]"
      v-model:pagination="pagination"
      :loading="loading"
      title="Solicitudes"
      @request="onRequest"
    >
      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown color="primary" label="Opciones" dense size="10px" no-caps>
            <q-list>
              <q-item clickable v-close-popup @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="eliminar(props.row.id)">
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="abrirConsentimiento(props.row)">
                <q-item-section avatar><q-icon name="description" /></q-item-section>
                <q-item-section>Consentimiento</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="imprimirConsentimiento(props.row)">
                <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
                <q-item-section>Imprimir consentimiento</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-chip :color="colorEstado(props.row.estado)" text-color="white" dense>
            {{ props.row.estado }}
          </q-chip>
        </q-td>
      </template>

      <template #body-cell-consentimiento="props">
        <q-td :props="props">
          <q-chip
            :color="props.row.consentimiento ? 'positive' : 'grey-6'"
            text-color="white"
            dense
          >
            {{ props.row.consentimiento ? (props.row.consentimiento.tipo || 'REGISTRADO') : 'PENDIENTE' }}
          </q-chip>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="muestraRechazadasDialog" persistent max-width="600px">
      <q-card>
        <q-card-section class="row items-center bg-grey-7 text-white">
          <div class="text-h6">Muestras Rechazadas</div>
          <q-space/>
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-xs">
          <q-markup-table dense wrap-cells bordered flat>
            <thead>
              <tr>
                <th>Id</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th>Fecha Solicitud</th>
                <th>Estado</th>
                <th>Observacion</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="muestra in muestrasRechazadas" :key="muestra.id">
                <td>{{ muestra.id }}</td>
                <td>{{ muestra.paciente_nombre }}</td>
                <td>{{ muestra.doctor_nombre }}</td>
                <td>{{ muestra.fecha_solicitud }}</td>
                <td>{{ muestra.estado }}</td>
                <td>{{ muestra.muestra_observacion }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="consentimientoDialog" persistent>
      <q-card style="width: min(920px, 96vw); max-width: 96vw;">
        <q-card-section class="row items-center">
          <div class="text-h6">Consentimiento en Admision</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <div class="row q-col-gutter-sm q-mb-sm">
            <div class="col-12 col-sm-6"><b>Paciente:</b> {{ consentimiento.nombre_completo || '-' }}</div>
            <div class="col-12 col-sm-3"><b>CI:</b> {{ consentimiento.ci || '-' }}</div>
            <div class="col-12 col-sm-3"><b>Solicitud:</b> #{{ selectedSolicitud?.id || '-' }}</div>
          </div>

          <q-form @submit.prevent="guardarConsentimiento">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-3">
                <q-input v-model="consentimiento.fecha_recepcion" type="date" dense outlined label="Fecha recepcion" />
              </div>
              <div class="col-12 col-sm-3">
                <q-input v-model="consentimiento.hora_recepcion" type="text" dense outlined label="Hora recepcion" maxlength="10" />
              </div>
              <div class="col-12 col-sm-3">
                <q-input v-model="consentimiento.fecha_solicitud" type="date" dense outlined label="Fecha solicitud medico" />
              </div>
              <div class="col-12 col-sm-3">
                <q-input v-model="consentimiento.fecha_consentimiento" type="date" dense outlined label="Fecha consentimiento" />
              </div>

              <div class="col-12 col-sm-4">
                <q-toggle
                  v-model="consentimiento.medicamento"
                  :true-value="1"
                  :false-value="0"
                  label="Medicamento"
                />
              </div>
              <div class="col-12 col-sm-8" v-if="consentimiento.medicamento">
                <q-input v-model="consentimiento.tratamiento" dense outlined label="Tratamiento" />
              </div>

              <div class="col-12 col-sm-4">
                <q-select
                  v-model="consentimiento.condicion"
                  :options="['BASAL', 'AYUNO PROL', 'POST PRANDIAL']"
                  dense outlined
                  label="Condicion"
                  clearable
                />
              </div>
              <div class="col-12 col-sm-4" v-if="consentimiento.condicion === 'ETAPA_GESTACION'">
                <q-input v-model="consentimiento.etapa_gestacion" dense outlined label="Etapa gestacion" />
              </div>
              <div class="col-12 col-sm-4">
                <q-select
                  v-model="consentimiento.tipo"
                  :options="['ACEPTA', 'RECHAZA']"
                  dense outlined
                  label="Tipo consentimiento"
                  clearable
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input v-model="consentimiento.declarante_nombre" dense outlined label="Yo declarante" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="consentimiento.declarante_condicion"
                  :options="['Padre', 'Madre', 'Abuelo/a', 'Hijo/a', 'Propio', 'Otros']"
                  dense outlined
                  label="En condicion de"
                  clearable
                />
              </div>
              <div class="col-12" v-if="consentimiento.declarante_condicion === 'Otros'">
                <q-input v-model="consentimiento.declarante_condicion_otro" dense outlined label="Otra condicion" />
              </div>

              <div class="col-12"><q-separator class="q-my-sm" /></div>
              <div class="col-12"><div class="text-subtitle2 q-mb-xs">Tipo de muestra</div></div>

              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_liquidos" :true-value="1" :false-value="0" dense label="Liquidos" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_esputo" :true-value="1" :false-value="0" dense label="Esputo" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_secreciones" :true-value="1" :false-value="0" dense label="Secreciones" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_orina" :true-value="1" :false-value="0" dense label="Orina" />
              </div>
<!--              <div class="col-12 col-sm-6 col-md-3">-->
<!--                <q-input v-model="consentimiento.hr_recoleccion_orina" type="text" dense outlined label="HR recoleccion" maxlength="10" />-->
<!--              </div>-->
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_heces" :true-value="1" :false-value="0" dense label="HR recoleccion heces" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model="consentimiento.hr_recoleccion_heces" type="text" dense outlined label="HR recoleccion" maxlength="10" />
              </div>

              <div class="col-12">
                <q-input v-model="consentimiento.observaciones" type="textarea" autogrow dense outlined label="Observaciones" />
              </div>
            </div>

            <div class="text-right q-mt-md">
              <q-btn flat label="Cerrar" v-close-popup :disable="consentimientoLoading" />
              <q-btn color="primary" label="Guardar" class="q-ml-sm" type="submit" :loading="consentimientoLoading" />
              <q-btn
                color="negative"
                icon="picture_as_pdf"
                label="Imprimir / Reimprimir"
                class="q-ml-sm"
                :disable="!selectedSolicitud?.id"
                @click="imprimirConsentimiento(selectedSolicitud)"
              />
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
  name: 'SolicitudesPage',
  data () {
    return {
      rows: [],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', style: 'font-weight:600' },
        { name: 'fecha_solicitud', label: 'Fecha', field: row => row.fecha_solicitud, format: v => v || '', align: 'left' },
        { name: 'paciente', label: 'Paciente', field: row => row?.paciente_nombre || '', align: 'left' },
        { name: 'doctor', label: 'Doctor', field: row => row.doctor?.nombre || row.doctor_nombre || '', align: 'left' },
        { name: 'tipo_atencion', label: 'Tipo atención', field: 'tipo_atencion', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
        { name: 'consentimiento', label: 'Consentimiento', field: row => row?.consentimiento?.tipo || '', align: 'left' }
      ],
      search: '',
      loading: false,
      pagination: {
        page: 1,
        rowsPerPage: 25,
        rowsNumber: 0
      },
      filters: {
        from: moment().startOf('month').format('YYYY-MM-DD'),
        to: moment().endOf('month').format('YYYY-MM-DD'),
        tipo_atencion: '',
        estado: '',
      },
      exportLoading: false,
      muestrasRechazadas: [],
      muestraRechazadasDialog: false,
      consentimientoDialog: false,
      consentimientoLoading: false,
      selectedSolicitud: null,
      consentimiento: {
        id: null,
        solicitude_id: null,
        paciente_id: null,
        nombre_completo: '',
        ci: '',
        fecha_recepcion: '',
        hora_recepcion: '',
        fecha_solicitud: '',
        fecha_consentimiento: '',
        medicamento: 0,
        tratamiento: '',
        condicion: '',
        etapa_gestacion: '',
        tipo: '',
        declarante_nombre: '',
        declarante_condicion: '',
        declarante_condicion_otro: '',
        m_orina: 0,
        hr_recoleccion_orina: '',
        m_liquidos: 0,
        m_esputo: 0,
        m_secreciones: 0,
        m_heces: 0,
        hr_recoleccion_heces: '',
        observaciones: ''
      }
    }
  },
  mounted () {
    this.getSolicitudes()
  },
  methods: {
    colorEstado (estado) {
      if (estado === 'MUESTRA RECHAZADA') return 'red'
      if (estado === 'ANALIZADO') return 'green'
      if (estado === 'ATENDIENDO') return 'blue'
      return 'grey'
    },
    getMuestrasRechazadas () {
      this.loading = true
      this.muestraRechazadasDialog = true
      this.muestrasRechazadas = []
      this.$axios.get('solicitudesMuestrasRechazadas')
        .then(res => {
          this.muestrasRechazadas = res.data
        })
        .finally(() => { this.loading = false })
    },
    getSolicitudes (resetPage = true) {
      if (resetPage) this.pagination.page = 1
      this.loading = true
      this.$axios
        .get('solicitudes', {
          params: {
            ...this.filters,
            search: this.search || undefined,
            page: this.pagination.page,
            per_page: this.pagination.rowsPerPage
          }
        })
        .then(res => {
          this.rows = res.data.data
          this.pagination.rowsNumber = res.data.total
          this.pagination.page = res.data.current_page
        })
        .finally(() => { this.loading = false })
    },
    onRequest (props) {
      const { page, rowsPerPage } = props.pagination
      this.pagination.page = page
      this.pagination.rowsPerPage = rowsPerPage
      this.getSolicitudes(false)
    },
    consentimientoBase () {
      return {
        id: null,
        solicitude_id: this.selectedSolicitud?.id || null,
        paciente_id: this.selectedSolicitud?.paciente_id || null,
        nombre_completo: this.selectedSolicitud?.paciente_nombre || '',
        ci: this.selectedSolicitud?.paciente_ci || '',
        fecha_recepcion: moment().format('YYYY-MM-DD'),
        hora_recepcion: moment().format('HH:mm'),
        fecha_solicitud: this.selectedSolicitud?.fecha_solicitud || '',
        fecha_consentimiento: moment().format('YYYY-MM-DD'),
        medicamento: 0,
        tratamiento: '',
        condicion: '',
        etapa_gestacion: '',
        tipo: '',
        declarante_nombre: '',
        declarante_condicion: '',
        declarante_condicion_otro: '',
        m_orina: 0,
        hr_recoleccion_orina: '',
        m_liquidos: 0,
        m_esputo: 0,
        m_secreciones: 0,
        m_heces: 0,
        hr_recoleccion_heces: '',
        observaciones: ''
      }
    },
    abrirConsentimiento (row) {
      this.selectedSolicitud = row
      this.consentimientoDialog = true
      this.consentimientoLoading = true
      this.consentimiento = this.consentimientoBase()
      this.$axios
        .get(`solicitudes/${row.id}/consentimiento`)
        .then(res => {
          this.consentimiento = { ...this.consentimientoBase(), ...res.data }
          if (!this.consentimiento.declarante_nombre) {
            this.consentimiento.declarante_nombre = this.consentimiento.nombre_completo
            this.consentimiento.declarante_condicion = 'Propio'
          }
        })
        .finally(() => { this.consentimientoLoading = false })
    },
    guardarConsentimiento () {
      if (!this.selectedSolicitud?.id) return
      this.consentimientoLoading = true
      this.$axios
        .post(`solicitudes/${this.selectedSolicitud.id}/consentimiento`, this.consentimiento)
        .then(res => {
          this.consentimiento = { ...this.consentimiento, ...res.data }
          const idx = this.rows.findIndex(r => r.id === this.selectedSolicitud.id)
          if (idx >= 0) this.rows[idx].consentimiento = res.data
          this.selectedSolicitud = { ...this.selectedSolicitud, consentimiento: res.data }
          if (this.$alert?.success) this.$alert.success('Consentimiento guardado')
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          if (this.$alert?.error) this.$alert.error('Error al guardar consentimiento: ' + msg)
        })
        .finally(() => { this.consentimientoLoading = false })
    },
    imprimirConsentimiento (row) {
      if (!row?.id) return
      const isSelectedRow = row?.id === this.selectedSolicitud?.id
      const hasConsentimiento = isSelectedRow ? !!this.consentimiento?.id : !!row?.consentimiento
      if (!hasConsentimiento) {
        if (this.$alert?.error) this.$alert.error('Primero debe registrar el consentimiento')
        return
      }
      const url = `${this.$axios.defaults.baseURL}/solicitudes/${row.id}/consentimiento/print`
      window.open(url, '_blank')
    },
    exportParams () {
      return {
        from: this.filters.from || '',
        to: this.filters.to || '',
        ...(this.filters.estado ? { estado: this.filters.estado } : {}),
        ...(this.filters.tipo_atencion ? { tipo_atencion: this.filters.tipo_atencion } : {}),
        ...(this.filters.codigo ? { codigo: this.filters.codigo } : {})
      }
    },
    async exportarExcel () {
      this.exportLoading = true
      try {
        const res = await this.$axios.get('solicitudes/export/excel', {
          params: this.exportParams(),
          responseType: 'blob'
        })
        const blob = new Blob([res.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        })
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        a.download = `solicitudes_${this.filters.from || 'inicio'}_${this.filters.to || 'fin'}.xlsx`
        a.click()
        URL.revokeObjectURL(a.href)
      } catch {
        this.$q.notify({ type: 'negative', message: 'Error al generar el Excel' })
      } finally {
        this.exportLoading = false
      }
    },
    async exportarPdf () {
      this.exportLoading = true
      try {
        const res = await this.$axios.get('solicitudes/export/pdf', {
          params: this.exportParams(),
          responseType: 'blob'
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 10000)
      } catch {
        this.$q.notify({ type: 'negative', message: 'Error al generar el PDF' })
      } finally {
        this.exportLoading = false
      }
    },
    nuevo () {
      this.$router.push({ name: 'solicitudes-new' })
    },
    editar (row) {
      this.$router.push({ name: 'solicitudes-edit', params: { id: row.id } })
    },
    eliminar (id) {
      if (this.$alert && this.$alert.dialog) {
        this.$alert.dialog('¿Eliminar solicitud?').onOk(() => {
          this.$axios.delete(`solicitudes/${id}`).then(() => {
            this.$alert.success('Eliminado')
            this.getSolicitudes()
          })
        })
      } else {
        if (confirm('¿Eliminar solicitud?')) {
          this.$axios.delete(`solicitudes/${id}`).then(() => {
            this.getSolicitudes()
          })
        }
      }
    }
  }
}
</script>
