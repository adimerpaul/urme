<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- Encabezado -->
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Solicitudes SAP</div>
        <div class="text-caption text-grey-7">Solicitudes de contratación de servicios generales</div>
      </div>
      <q-space />
      <q-btn unelevated color="primary" icon="add_circle" label="Nueva solicitud" no-caps to="/solicitudes-sap/nueva" />
    </div>

    <!-- Cards de resumen -->
    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-blue">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="blue-7" text-color="white" icon="description" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Total en solicitudes</div>
              <div class="text-h6 text-weight-bold text-blue-9">{{ money(summary.total_bs) }} Bs</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-orange">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="orange-7" text-color="white" icon="pending" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Pendientes</div>
              <div class="text-h6 text-weight-bold text-orange-9">{{ summary.pendiente }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-green">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="green-6" text-color="white" icon="check_circle" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Aprobadas</div>
              <div class="text-h6 text-weight-bold text-green-9">{{ summary.aprobado }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Filtros + tabla -->
    <q-card flat bordered>
      <q-card-section class="row q-col-gutter-sm items-center q-pa-sm">
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.date_from" dense outlined type="date" label="Fecha inicio">
            <template #prepend><q-icon name="event" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.date_to" dense outlined type="date" label="Fecha fin">
            <template #prepend><q-icon name="event" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-2">
          <q-select v-model="filters.estado" dense outlined clearable emit-value map-options label="Estado" :options="estadoOptions" />
        </div>
        <div class="col-12 col-sm">
          <q-input v-model="filters.q" dense outlined clearable label="Buscar N° / Unidad">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn unelevated color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="fetchRows" />
        </div>
      </q-card-section>

      <q-separator />

      <q-table
        v-model:pagination="pagination"
        flat
        row-key="id"
        :rows="rows"
        :columns="columns"
        :loading="loading"
        :rows-per-page-options="[10, 15, 25, 50]"
        @request="onRequest"
      >
        <!-- Acciones (izquierda) -->
        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown
              unelevated
              color="primary"
              text-color="white"
              no-caps
              size="sm"
              label="Opciones"
              icon="settings"
            >
              <q-list dense>
                <q-item clickable v-close-popup @click="openDetail(props.row)">
                  <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                  <q-item-section>Ver detalle</q-item-section>
                </q-item>
                <q-item clickable v-close-popup :disable="printingId === props.row.id" @click="imprimir(props.row)">
                  <q-item-section avatar>
                    <q-spinner v-if="printingId === props.row.id" color="teal" size="20px" />
                    <q-icon v-else name="print" color="teal" />
                  </q-item-section>
                  <q-item-section>Imprimir PDF</q-item-section>
                </q-item>
                <q-item clickable v-close-popup :disable="props.row.estado === 'ANULADO'" @click="editar(props.row)">
                  <q-item-section avatar><q-icon name="edit" color="amber-9" /></q-item-section>
                  <q-item-section>Editar</q-item-section>
                </q-item>
                <q-separator />
                <q-item clickable v-close-popup :disable="props.row.estado === 'ANULADO'" @click="eliminar(props.row)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section class="text-negative">Eliminar</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <!-- N° -->
        <template #body-cell-nro="props">
          <q-td :props="props">
            <span class="text-weight-bold text-primary">{{ props.row.nro }}</span>
          </q-td>
        </template>

        <!-- Fecha -->
        <template #body-cell-fecha="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ formatDate(props.row.fecha) }}</div>
            <div v-if="props.row.user" class="text-caption text-grey-7">{{ props.row.user.name }}</div>
          </q-td>
        </template>

        <!-- Unidad solicitante -->
        <template #body-cell-unidad_solicitante="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar size="32px" color="blue-7" text-color="white" icon="business" class="q-mr-sm" />
              <div>
                <div class="text-weight-medium text-capitalize">{{ props.row.unidad_solicitante || 'Sin unidad' }}</div>
                <div v-if="props.row.apertura_programatica" class="text-caption text-grey-7">
                  Aper.: {{ props.row.apertura_programatica }}
                </div>
              </div>
            </div>
          </q-td>
        </template>

        <!-- Total -->
        <template #body-cell-total="props">
          <q-td :props="props" class="text-right">
            <span class="text-weight-bold text-primary">{{ money(props.row.total) }} Bs</span>
          </q-td>
        </template>

        <!-- Estado -->
        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge
              :color="estadoColor(props.row.estado)"
              class="q-pa-xs text-weight-bold"
            >
              <q-icon :name="estadoIcon(props.row.estado)" size="14px" class="q-mr-xs" />
              {{ props.row.estado }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Diálogo detalle -->
    <q-dialog v-model="detailDialog">
      <q-card class="detail-dialog">
        <div class="detail-header">
          <div class="row items-center no-wrap">
            <q-icon name="description" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">{{ selected?.nro }}</div>
              <div class="text-caption text-grey-3">{{ formatDate(selected?.fecha) }}</div>
            </div>
            <q-space />
            <q-badge
              v-if="selected"
              :color="estadoColor(selected.estado)"
              class="q-pa-sm text-weight-bold q-mr-sm"
            >
              {{ selected.estado }}
            </q-badge>
            <q-btn flat round dense icon="close" color="white" @click="detailDialog = false" />
          </div>
        </div>

        <q-card-section v-if="selected" class="q-pa-md">
          <div class="meta-grid">
            <div class="meta-item">
              <q-icon name="business" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Unidad solicitante</div>
                <div class="meta-value">{{ selected.unidad_solicitante || '-' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="account_tree" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Apertura programática</div>
                <div class="meta-value">{{ selected.apertura_programatica || '-' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="tag" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">N° CITE solicitud</div>
                <div class="meta-value">{{ selected.nro_cite || '-' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="person" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Registrado por</div>
                <div class="meta-value">{{ selected.user?.name || '-' }}</div>
              </div>
            </div>
            <div v-if="selected.justificacion" class="meta-item" style="grid-column: 1 / -1">
              <q-icon name="assignment" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Justificación</div>
                <div class="meta-value">{{ selected.justificacion }}</div>
              </div>
            </div>
            <div v-if="selected.observaciones" class="meta-item" style="grid-column: 1 / -1">
              <q-icon name="comment" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Observaciones</div>
                <div class="meta-value">{{ selected.observaciones }}</div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-md">
          <div class="row items-center q-mb-sm">
            <q-icon name="inventory_2" size="18px" color="primary" class="q-mr-xs" />
            <div class="text-subtitle2 text-weight-bold">Ítems solicitados</div>
            <q-space />
            <q-chip dense color="primary" text-color="white" :label="`${(selected?.detalles || []).length} ítems`" />
          </div>
          <div class="detail-items">
            <div v-for="det in selected?.detalles || []" :key="det.id" class="detail-item">
              <q-avatar size="40px" color="blue-1" text-color="blue-9" icon="inventory_2" class="q-mr-sm" />
              <div class="detail-item-info">
                <div class="detail-item-name">{{ det.descripcion }}</div>
                <div class="detail-item-meta">
                  <span v-if="det.part_presup" class="part-badge">{{ det.part_presup }}</span>
                  <span>{{ det.cantidad }} × {{ money(det.precio_unitario) }} Bs</span>
                  <span v-if="det.unidad">· {{ det.unidad }}</span>
                </div>
              </div>
              <div class="detail-item-total">{{ money(det.total) }} Bs</div>
            </div>
            <div v-if="(selected?.detalles || []).length === 0" class="text-center text-grey-7 q-pa-md">
              Sin ítems
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-md detail-summary">
          <div class="summary-row">
            <span class="summary-label">Ítems</span>
            <span class="summary-value">{{ (selected?.detalles || []).length }}</span>
          </div>
          <q-separator class="q-my-sm" />
          <div class="summary-row total-row">
            <span class="total-label">Total solicitud</span>
            <span class="total-value">{{ money(selected?.total) }} Bs</span>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions class="q-pa-md">
          <q-space />
          <q-btn flat no-caps color="grey-8" label="Cerrar" @click="detailDialog = false" />
          <q-btn
            v-if="selected"
            unelevated no-caps color="teal" icon="print" label="Imprimir PDF"
            :loading="printingId === selected?.id"
            @click="imprimir(selected)"
          />
          <q-btn
            v-if="selected && selected.estado !== 'ANULADO'"
            unelevated no-caps color="primary" icon="edit" label="Editar"
            @click="editar(selected)"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'SolicitudesSapPage',
  data () {
    return {
      loading: false,
      printingId: null,
      rows: [],
      summary: { total_bs: 0, cantidad: 0, pendiente: 0, aprobado: 0 },
      selected: null,
      detailDialog: false,
      filters: {
        date_from: moment().startOf('month').format('YYYY-MM-DD'),
        date_to: moment().format('YYYY-MM-DD'),
        estado: null,
        q: '',
      },
      pagination: { page: 1, rowsPerPage: 15, rowsNumber: 0 },
      estadoOptions: [
        { label: 'Pendiente', value: 'PENDIENTE' },
        { label: 'Aprobado', value: 'APROBADO' },
        { label: 'Rechazado', value: 'RECHAZADO' },
        { label: 'Anulado', value: 'ANULADO' },
      ],
      columns: [
        { name: 'actions', label: 'Acciones', field: 'id', align: 'left', style: 'width: 130px' },
        { name: 'nro', label: 'N°', field: 'nro', align: 'left' },
        { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
        { name: 'unidad_solicitante', label: 'Unidad solicitante', field: 'unidad_solicitante', align: 'left' },
        { name: 'detalles_count', label: 'Ítems', field: 'detalles_count', align: 'center' },
        { name: 'total', label: 'Total', field: 'total', align: 'right' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
      ],
    }
  },
  mounted () {
    this.fetchRows()
  },
  methods: {
    async fetchRows () {
      this.loading = true
      try {
        const res = await this.$axios.get('solicitudes-sap', {
          params: {
            page: this.pagination.page,
            rowsPerPage: this.pagination.rowsPerPage,
            ...this.filters,
          },
        })
        this.rows = res.data.data || []
        this.pagination.rowsNumber = res.data.total || 0
        this.summary = res.data.summary || this.summary
      } finally {
        this.loading = false
      }
    },
    onRequest (props) {
      this.pagination = props.pagination
      this.fetchRows()
    },
    async openDetail (row) {
      const res = await this.$axios.get(`solicitudes-sap/${row.id}`)
      this.selected = res.data
      this.detailDialog = true
    },
    editar (row) {
      this.detailDialog = false
      this.$router.push(`/solicitudes-sap/${row.id}/editar`)
    },
    async imprimir (row) {
      if (!row?.id) return
      this.printingId = row.id
      try {
        const res = await this.$axios.get(`solicitudes-sap/${row.id}/pdf`, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
        window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$alert.error('No se pudo generar el PDF')
      } finally {
        this.printingId = null
      }
    },
    eliminar (row) {
      this.$q.dialog({
        title: 'Eliminar solicitud',
        message: `¿Eliminar la solicitud ${row.nro}? Esta acción no se puede deshacer.`,
        cancel: true,
        persistent: true,
        ok: { label: 'Eliminar', color: 'negative', noCaps: true },
      }).onOk(async () => {
        try {
          await this.$axios.delete(`solicitudes-sap/${row.id}`)
          this.$alert.success('Solicitud eliminada')
          await this.fetchRows()
        } catch (e) {
          this.$alert.error(e.response?.data?.message || 'No se pudo eliminar')
        }
      })
    },
    money (value) {
      return Number(value || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    formatDate (val) {
      return val ? moment(val).format('DD/MM/YYYY') : '-'
    },
    estadoColor (estado) {
      const map = { PENDIENTE: 'orange', APROBADO: 'green', RECHAZADO: 'red', ANULADO: 'grey' }
      return map[estado] || 'grey'
    },
    estadoIcon (estado) {
      const map = { PENDIENTE: 'schedule', APROBADO: 'check_circle', RECHAZADO: 'cancel', ANULADO: 'block' }
      return map[estado] || 'help'
    },
  },
}
</script>

<style scoped>
.summary-card { border-radius: 10px; }
.summary-blue   { border-left: 4px solid #1976d2; }
.summary-orange { border-left: 4px solid #f57c00; }
.summary-green  { border-left: 4px solid #43a047; }

.detail-dialog {
  width: 760px;
  max-width: 94vw;
  border-radius: 10px;
  overflow: hidden;
}

.detail-header {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: #fff;
  padding: 16px 18px;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.meta-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  background: #f7f9fc;
  border: 1px solid #e5eaf2;
  border-radius: 8px;
}

.meta-icon { color: #1976d2; margin-top: 2px; }

.meta-content { display: flex; flex-direction: column; min-width: 0; }

.meta-label {
  font-size: 11px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  font-weight: 600;
}

.meta-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  word-break: break-word;
}

.detail-items {
  border: 1px solid #e5eaf2;
  border-radius: 8px;
  background: #fff;
  padding: 4px;
  max-height: 320px;
  overflow-y: auto;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 10px;
  border-bottom: 1px solid #f0f2f5;
}

.detail-item:last-child { border-bottom: none; }

.detail-item-info { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; }

.detail-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  text-transform: capitalize;
  line-height: 1.2;
  word-break: break-word;
}

.detail-item-meta {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}

.part-badge {
  display: inline-block;
  padding: 1px 5px;
  background: #e8f1fb;
  color: #0f5ea8;
  font-size: 11px;
  font-weight: 700;
  border-radius: 3px;
  border: 1px solid #b3cef0;
}

.detail-item-total { font-size: 14px; font-weight: 700; color: #1976d2; white-space: nowrap; }

.detail-summary { background: #f7f9fc; }

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.summary-label { font-size: 13px; color: #4b5563; }
.summary-value { font-size: 14px; font-weight: 600; color: #1f2937; }

.total-row { padding: 6px 12px; background: #e3f2fd; border-radius: 8px; }
.total-label { font-size: 15px; font-weight: 700; color: #0d47a1; }
.total-value { font-size: 22px; font-weight: 800; color: #1976d2; }
</style>
