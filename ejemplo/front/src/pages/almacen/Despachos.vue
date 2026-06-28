<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Despachos</div>
        <div class="text-caption text-grey-7">Entregas de materiales realizadas desde almacén</div>
      </div>
      <q-space />
      <q-btn
        v-if="canCreate"
        unelevated
        color="primary"
        icon="add_circle"
        label="Nuevo despacho"
        no-caps
        to="/despachos/nuevo"
      />
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-blue">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="blue-7" text-color="white" icon="payments" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Total despachado</div>
              <div class="text-h6 text-weight-bold text-blue-9">{{ money(summary.total_bs) }} Bs</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-green">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="green-6" text-color="white" icon="task_alt" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Despachados</div>
              <div class="text-h6 text-weight-bold text-green-9">{{ summary.despachados }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-red">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="red-6" text-color="white" icon="cancel" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Anulados</div>
              <div class="text-h6 text-weight-bold text-red-9">{{ summary.anulados }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-slate">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="grey-8" text-color="white" icon="local_shipping" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Cantidad</div>
              <div class="text-h6 text-weight-bold text-grey-10">{{ summary.cantidad }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="q-mb-sm">
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
          <q-select
            v-model="filters.estado"
            :options="estadoOptions"
            dense outlined clearable emit-value map-options
            label="Estado"
          />
        </div>
        <div class="col-12 col-sm">
          <q-input
            v-model="filters.q"
            dense outlined clearable debounce="350"
            label="Buscar nro, solicitante o servicio"
            @update:model-value="applyFilters"
          >
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn unelevated color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="applyFilters" />
        </div>
        <div class="col-auto">
          <q-btn flat color="grey-8" icon="refresh" label="Limpiar" no-caps @click="clearFilters" />
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
        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn-dropdown
              unelevated color="primary" text-color="white"
              no-caps size="sm" label="Opciones" icon="settings"
            >
              <q-list dense>
                <q-item clickable v-close-popup @click="viewDespacho(props.row)">
                  <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                  <q-item-section>Ver detalle</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openCambiarUnidad(props.row)">
                  <q-item-section avatar><q-icon name="apartment" color="teal-7" /></q-item-section>
                  <q-item-section>Cambiar unidad</q-item-section>
                </q-item>
                <q-item
                  v-if="canPrint"
                  clickable v-close-popup
                  :disable="printingId === props.row.id"
                  @click="printDespacho(props.row.id)"
                >
                  <q-item-section avatar>
                    <q-spinner v-if="printingId === props.row.id" color="teal" size="20px" />
                    <q-icon v-else name="print" color="teal" />
                  </q-item-section>
                  <q-item-section>Imprimir</q-item-section>
                </q-item>
                <q-separator v-if="canDelete && props.row.estado !== 'ANULADO'" />
                <q-item
                  v-if="canDelete && props.row.estado !== 'ANULADO'"
                  clickable v-close-popup
                  @click="anularDespacho(props.row)"
                >
                  <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                  <q-item-section class="text-negative">Anular</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <template #body-cell-nro="props">
          <q-td :props="props">
            <q-badge color="blue-1" text-color="blue-10" class="text-weight-medium">
              {{ props.row.nro || `#${props.row.id}` }}
            </q-badge>
            <div v-if="props.row.pedido_id" class="text-caption text-grey-7 q-mt-xs">
              Pedido #{{ props.row.pedido_id }}
            </div>
          </q-td>
        </template>

        <template #body-cell-fecha_entrega="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ formatDate(props.row.fecha_entrega) }}</div>
            <div class="text-caption text-grey-7">{{ formatTime(props.row.fecha_entrega) }}</div>
          </q-td>
        </template>

        <template #body-cell-solicitante="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar size="30px" color="primary" text-color="white" icon="person" class="q-mr-sm" />
              <div class="text-weight-medium">{{ props.row.solicitante || '-' }}</div>
            </div>
          </q-td>
        </template>

        <template #body-cell-servicio="props">
          <q-td :props="props">
            <div v-if="props.row.unidad || props.row.servicio" class="row items-center no-wrap">
              <q-icon name="apartment" size="14px" color="teal-8" class="q-mr-xs" />
              <div class="text-caption text-weight-medium" style="max-width: 200px; white-space: normal; line-height: 1.2">
                {{ props.row.unidad ? props.row.unidad.nombre : props.row.servicio }}
              </div>
            </div>
            <span v-else class="text-grey-5">-</span>
          </q-td>
        </template>

        <template #body-cell-recepcion="props">
          <q-td :props="props">
            <div>{{ props.row.personal_recepcion || '-' }}</div>
          </q-td>
        </template>

        <template #body-cell-detalles_count="props">
          <q-td :props="props" class="text-center">
            <q-chip dense color="grey-3" text-color="grey-9" :label="`${props.row.detalles_count || 0} items`" />
          </q-td>
        </template>

        <template #body-cell-total="props">
          <q-td :props="props" class="text-right">
            <span class="text-weight-bold text-primary">{{ money(props.row.total) }} Bs</span>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge :color="estadoColor(props.row.estado)" class="q-pa-xs text-weight-bold">
              {{ props.row.estado }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Dialog cambiar unidad -->
    <q-dialog v-model="showUnidadDialog" persistent>
      <q-card style="min-width:400px; max-width:94vw">
        <q-card-section class="row items-center no-wrap bg-teal-8 text-white">
          <q-icon name="apartment" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Cambiar unidad</div>
            <div class="text-caption text-teal-2">Despacho {{ unidadDialogRow?.nro || `#${unidadDialogRow?.id}` }}</div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" color="white" @click="showUnidadDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-md">
          <q-select
            v-model="selectedUnidadId"
            :options="unidades"
            option-value="id"
            option-label="nombre"
            emit-value
            map-options
            outlined
            dense
            label="Seleccionar unidad"
          >
            <template #prepend><q-icon name="apartment" /></template>
          </q-select>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat no-caps color="grey-8" label="Cancelar" @click="showUnidadDialog = false" />
          <q-btn
            unelevated no-caps color="teal-8" icon="save" label="Guardar"
            :loading="savingUnidad"
            :disable="!selectedUnidadId"
            @click="saveUnidad"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Detail dialog -->
    <q-dialog v-model="showDetail">
      <q-card class="detail-dialog">
        <div class="detail-header">
          <div class="row items-center no-wrap">
            <q-icon name="local_shipping" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">Despacho {{ selected?.nro }}</div>
              <div class="text-caption text-grey-3">{{ formatDateTime(selected?.fecha_entrega) }}</div>
            </div>
            <q-space />
            <q-badge v-if="selected" :color="estadoColor(selected.estado)" class="q-pa-sm text-weight-bold q-mr-sm">
              {{ selected.estado }}
            </q-badge>
            <q-btn flat round dense icon="close" color="white" @click="showDetail = false" />
          </div>
        </div>

        <div class="detail-content">
          <q-card-section v-if="selected">
            <div class="meta-grid">
              <div class="meta-item">
                <q-icon name="badge" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">N° Despacho</div>
                  <div class="meta-value">{{ selected.nro || '-' }}</div>
                </div>
              </div>
              <div class="meta-item" v-if="selected.pedido_id">
                <q-icon name="receipt_long" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Pedido</div>
                  <div class="meta-value">#{{ selected.pedido_id }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="person" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Solicitante</div>
                  <div class="meta-value">{{ selected.solicitante || '-' }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="apartment" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Unidad</div>
                  <div class="meta-value">{{ selected.unidad ? selected.unidad.nombre : (selected.servicio || '-') }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="event" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Fecha de entrega</div>
                  <div class="meta-value">{{ formatDateTime(selected.fecha_entrega) }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="how_to_reg" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Personal de recepción</div>
                  <div class="meta-value">{{ selected.personal_recepcion || '-' }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="local_shipping" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Responsable de despacho</div>
                  <div class="meta-value">{{ selected.user?.name || '-' }}</div>
                </div>
              </div>
              <div class="meta-item" v-if="selected.observaciones">
                <q-icon name="comment" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Observaciones</div>
                  <div class="meta-value">{{ selected.observaciones }}</div>
                </div>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section v-if="selected">
            <div class="row items-center q-mb-sm">
              <q-icon name="inventory_2" color="primary" class="q-mr-xs" />
              <div class="text-subtitle2 text-weight-bold">Items despachados</div>
              <q-space />
              <q-chip dense color="primary" text-color="white" :label="`${selected.detalles?.length || 0} items`" />
            </div>

            <div class="detail-items">
              <div class="d-row d-header">
                <span>#</span>
                <span>Descripción</span>
                <span>Unidad</span>
                <span class="text-right">Cant.</span>
                <span class="text-right">Precio</span>
                <span class="text-right">Total</span>
              </div>
              <div v-for="d in (selected.detalles || [])" :key="d.id" class="d-row">
                <span>{{ d.item }}</span>
                <span>{{ d.descripcion }}</span>
                <span>{{ d.unidad || '-' }}</span>
                <span class="text-right">{{ d.cantidad }}</span>
                <span class="text-right">{{ money(d.precio_unitario) }}</span>
                <span class="text-right text-weight-bold text-primary">{{ money(d.total) }}</span>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section v-if="selected" class="detail-summary">
            <div class="summary-row total-row">
              <span class="total-label">Total</span>
              <span class="total-value">{{ money(selected.total) }} Bs</span>
            </div>
          </q-card-section>
        </div>

        <q-separator />

        <q-card-actions class="q-pa-sm detail-actions">
          <q-space />
          <q-btn flat no-caps color="grey-8" label="Cerrar" @click="showDetail = false" />
          <q-btn
            v-if="canPrint && selected"
            unelevated no-caps color="teal" icon="print" label="Imprimir"
            :loading="printingId === selected.id"
            @click="printDespacho(selected.id)"
          />
          <q-btn
            v-if="canDelete && selected && selected.estado !== 'ANULADO'"
            unelevated no-caps color="negative" icon="cancel" label="Anular"
            @click="anularDespacho(selected)"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'Despachos',
  data () {
    return {
      loading: false,
      rows: [],
      summary: { total_bs: 0, cantidad: 0, despachados: 0, anulados: 0 },
      pagination: { sortBy: 'id', descending: true, page: 1, rowsPerPage: 15, rowsNumber: 0 },
      filters: {
        date_from: moment().startOf('month').format('YYYY-MM-DD'),
        date_to: moment().format('YYYY-MM-DD'),
        estado: null,
        q: '',
      },
      estadoOptions: [
        { label: 'Despachado', value: 'DESPACHADO' },
        { label: 'Anulado', value: 'ANULADO' },
      ],
      columns: [
        { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'left', style: 'width: 130px' },
        { name: 'nro', label: 'N° Despacho', field: 'nro', align: 'left' },
        { name: 'fecha_entrega', label: 'Fecha entrega', field: 'fecha_entrega', align: 'left' },
        { name: 'solicitante', label: 'Solicitante', field: 'solicitante', align: 'left' },
        { name: 'servicio', label: 'Unidad', field: 'servicio', align: 'left' },
        { name: 'recepcion', label: 'Recepción', field: 'personal_recepcion', align: 'left' },
        { name: 'detalles_count', label: 'Items', field: 'detalles_count', align: 'center' },
        { name: 'total', label: 'Total', field: 'total', align: 'right' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
      ],
      printingId: null,
      showDetail: false,
      selected: null,
      showUnidadDialog: false,
      unidadDialogRow: null,
      selectedUnidadId: null,
      unidades: [],
      savingUnidad: false,
    }
  },
  computed: {
    isAdmin () { return this.$store.user?.role === 'Administrador' },
    perms () { return this.$store.permissions || [] },
    canCreate () { return this.isAdmin || this.perms.includes('Crear Despachos') },
    canDelete () { return this.isAdmin || this.perms.includes('Anular Despachos') },
    canPrint () { return this.isAdmin || this.perms.includes('Imprimir Despachos') },
  },
  mounted () { this.fetchRows() },
  methods: {
    async fetchRows () {
      this.loading = true
      try {
        const res = await this.$axios.get('despachos', {
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
    async applyFilters () {
      this.pagination.page = 1
      await this.fetchRows()
    },
    clearFilters () {
      this.filters = {
        date_from: moment().startOf('month').format('YYYY-MM-DD'),
        date_to: moment().format('YYYY-MM-DD'),
        estado: null,
        q: '',
      }
      this.applyFilters()
    },
    async onRequest (props) {
      this.pagination = props.pagination
      await this.fetchRows()
    },
    async viewDespacho (row) {
      const res = await this.$axios.get(`despachos/${row.id}`)
      this.selected = res.data
      this.showDetail = true
    },
    async printDespacho (id) {
      this.printingId = id
      try {
        const res = await this.$axios.get(`despachos/${id}/pdf`, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
        window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$q.notify({ color: 'negative', message: 'No se pudo imprimir el despacho', position: 'top' })
      } finally {
        this.printingId = null
      }
    },
    anularDespacho (row) {
      this.$q.dialog({
        title: 'Anular despacho',
        message: `¿Anular el despacho ${row.nro || `#${row.id}`}? Las cantidades volverán al inventario.`,
        cancel: true,
      }).onOk(async () => {
        await this.$axios.post(`despachos/${row.id}/anular`)
        this.$q.notify({ color: 'positive', message: 'Despacho anulado', position: 'top' })
        if (this.selected?.id === row.id) {
          const res = await this.$axios.get(`despachos/${row.id}`)
          this.selected = res.data
        }
        await this.fetchRows()
      })
    },
    async openCambiarUnidad (row) {
      this.unidadDialogRow = row
      this.selectedUnidadId = row.unidad?.id ?? null
      if (this.unidades.length === 0) {
        const res = await this.$axios.get('unidades')
        this.unidades = res.data
      }
      this.showUnidadDialog = true
    },
    async saveUnidad () {
      if (!this.selectedUnidadId || !this.unidadDialogRow) return
      this.savingUnidad = true
      try {
        await this.$axios.patch(`despachos/${this.unidadDialogRow.id}/unidad`, { unidad_id: this.selectedUnidadId })
        this.$q.notify({ color: 'positive', message: 'Unidad actualizada', position: 'top' })
        this.showUnidadDialog = false
        if (this.selected?.id === this.unidadDialogRow.id) {
          const res = await this.$axios.get(`despachos/${this.unidadDialogRow.id}`)
          this.selected = res.data
        }
        await this.fetchRows()
      } catch (e) {
        this.$q.notify({ color: 'negative', message: e?.response?.data?.message || 'Error al actualizar unidad', position: 'top' })
      } finally {
        this.savingUnidad = false
      }
    },
    estadoColor (estado) {
      if (estado === 'DESPACHADO') return 'green'
      if (estado === 'ANULADO') return 'red'
      return 'grey-7'
    },
    money (val) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        .format(Number(val || 0))
    },
    formatDate (val) {
      if (!val) return '-'
      return moment(val).format('DD/MM/YYYY')
    },
    formatTime (val) {
      if (!val) return ''
      return moment(val).format('HH:mm')
    },
    formatDateTime (val) {
      if (!val) return '-'
      return moment(val).format('DD/MM/YYYY HH:mm')
    },
  },
}
</script>

<style scoped>
.summary-card { border-radius: 10px; }
.summary-blue { border-left: 4px solid #1976d2; }
.summary-green { border-left: 4px solid #43a047; }
.summary-red { border-left: 4px solid #c90022; }
.summary-slate { border-left: 4px solid #455a64; }

.detail-dialog {
  width: 800px; max-width: 96vw; max-height: 92vh;
  border-radius: 10px; overflow: hidden;
  display: flex; flex-direction: column;
}
.detail-content { flex: 1 1 auto; overflow-y: auto; min-height: 0; }
.detail-actions { background: #fff; }
.detail-header {
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  color: #fff; padding: 16px 18px;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}
.meta-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 8px 10px; background: #f7f9fc;
  border: 1px solid #e5eaf2; border-radius: 8px;
}
.meta-icon { color: #2e7d32; }
.meta-label {
  font-size: 11px; color: #6b7280; text-transform: uppercase;
  letter-spacing: 0.4px; font-weight: 600;
}
.meta-value { font-size: 13px; font-weight: 600; color: #1f2937; word-break: break-word; }

.detail-items {
  border: 1px solid #e5eaf2; border-radius: 8px; overflow: hidden;
}
.d-row {
  display: grid;
  grid-template-columns: 30px 2fr 80px 60px 80px 90px;
  gap: 6px; padding: 8px 10px; font-size: 13px;
  border-bottom: 1px solid #f0f2f5;
}
.d-row:last-child { border-bottom: none; }
.d-header {
  background: #f7f9fc; font-size: 11px; font-weight: 700;
  color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px;
}

.detail-summary { background: #f7f9fc; }
.summary-row { display: flex; justify-content: space-between; padding: 4px 0; }
.total-row { padding: 8px 12px; background: #e8f5e9; border-radius: 8px; }
.total-label { font-size: 15px; font-weight: 700; color: #1b5e20; }
.total-value { font-size: 22px; font-weight: 800; color: #2e7d32; }
</style>
