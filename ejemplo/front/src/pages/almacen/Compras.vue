<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- Encabezado -->
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Compras</div>
        <div class="text-caption text-grey-7">Listado de entradas registradas en almacén</div>
      </div>
      <q-space />
      <q-btn unelevated color="primary" icon="add_circle" label="Compra nueva" no-caps to="/almacen/compras/nueva" />
    </div>

    <!-- Cards de resumen -->
    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-green">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="green-6" text-color="white" icon="shopping_cart" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Compras totales</div>
              <div class="text-h6 text-weight-bold text-green-9">{{ money(summary.total_compras) }} Bs</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-red">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="red-6" text-color="white" icon="block" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Compras anuladas</div>
              <div class="text-h6 text-weight-bold text-red-9">{{ money(summary.total_anuladas) }} Bs</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered class="summary-card summary-blue">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="blue-7" text-color="white" icon="receipt_long" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Cantidad de compras</div>
              <div class="text-h6 text-weight-bold text-blue-9">{{ summary.cantidad }}</div>
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
          <q-input v-model="filters.q" dense outlined clearable label="Buscar proveedor o factura">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn unelevated color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="fetchRows" />
        </div>
        <div class="col-12">
          <div class="row items-center q-gutter-xs">
            <span class="text-caption text-grey-7 q-mr-xs">Acceso rápido:</span>
            <q-btn
              v-for="atajo in atajosSemana"
              :key="atajo.label"
              dense
              no-caps
              unelevated
              size="sm"
              :color="atajo.active ? 'primary' : 'grey-3'"
              :text-color="atajo.active ? 'white' : 'grey-9'"
              :label="atajo.label"
              @click="atajo.fn()"
            />
          </div>
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
        <template #body-cell-fecha_hora="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ formatDate(props.row.fecha_hora) }}</div>
            <div class="text-caption text-grey-7">{{ formatTime(props.row.fecha_hora) }}</div>
          </q-td>
        </template>

        <template #body-cell-proveedor="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar size="32px" color="primary" text-color="white" icon="local_shipping" class="q-mr-sm" />
              <div>
                <div class="text-weight-medium text-capitalize">{{ props.row.proveedor?.nombre || props.row.nombre || 'Sin proveedor' }}</div>
                <div v-if="props.row.nro_factura" class="text-caption text-grey-7">Factura: {{ props.row.nro_factura }}</div>
                <div v-if="props.row.comentario" class="text-caption text-grey-7" :title="props.row.comentario">
                  {{ props.row.comentario }}
                </div>
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-productos="props">
          <q-td :props="props">
            <div class="row q-gutter-xs">
              <q-chip
                v-for="det in (props.row.detalles || []).slice(0, 3)"
                :key="det.id"
                dense
                square
                color="blue-grey-1"
                text-color="blue-grey-9"
                class="text-capitalize"
                style="font-size: 11px; max-width: 160px"
              >
                <span class="ellipsis">{{ det.nombre }}</span>
                <q-tooltip>{{ det.nombre }} × {{ det.cantidad }}</q-tooltip>
              </q-chip>
              <q-chip
                v-if="(props.row.detalles || []).length > 3"
                dense
                square
                color="primary"
                text-color="white"
                style="font-size: 11px"
              >
                +{{ (props.row.detalles || []).length - 3 }} más
              </q-chip>
              <span v-if="!(props.row.detalles || []).length" class="text-grey-6 text-caption">-</span>
            </div>
          </q-td>
        </template>

        <template #body-cell-motivo_registro="props">
          <q-td :props="props">
            <q-chip dense square color="grey-3" text-color="grey-9" class="text-capitalize">
              {{ (props.row.motivo_registro || '').toLowerCase() }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-tipo_pago="props">
          <q-td :props="props">
            <q-chip v-if="props.row.tipo_pago" dense square outline color="primary" :icon="pagoIcon(props.row.tipo_pago)">
              {{ pagoLabel(props.row.tipo_pago) }}
            </q-chip>
            <span v-else class="text-grey-6">-</span>
          </q-td>
        </template>

        <template #body-cell-total="props">
          <q-td :props="props" class="text-right">
            <span class="text-weight-bold text-primary">{{ money(props.row.total) }} Bs</span>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge
              :color="props.row.estado === 'ACTIVO' ? 'green' : 'red'"
              class="q-pa-xs text-weight-bold"
            >
              <q-icon :name="props.row.estado === 'ACTIVO' ? 'check_circle' : 'cancel'" size="14px" class="q-mr-xs" />
              {{ props.row.estado }}
            </q-badge>
          </q-td>
        </template>

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
                  <q-item-section>Imprimir</q-item-section>
                </q-item>
                <q-item
                  clickable
                  v-close-popup
                  :disable="props.row.estado === 'ANULADO' || isBlocked(props.row)"
                  @click="editar(props.row)"
                >
                  <q-item-section avatar><q-icon name="edit" color="amber-9" /></q-item-section>
                  <q-item-section>
                    <div>Modificar</div>
                    <div v-if="isBlocked(props.row)" class="text-caption text-grey-6">{{ blockReason(props.row) }}</div>
                  </q-item-section>
                </q-item>
                <q-separator />
                <q-item
                  clickable
                  v-close-popup
                  :disable="props.row.estado === 'ANULADO' || isBlocked(props.row)"
                  @click="anular(props.row)"
                >
                  <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                  <q-item-section :class="{ 'text-negative': !isBlocked(props.row) }">
                    <div>Anular</div>
                    <div v-if="isBlocked(props.row)" class="text-caption text-grey-6">{{ blockReason(props.row) }}</div>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Diálogo detalle -->
    <q-dialog v-model="detailDialog">
      <q-card class="detail-dialog">
        <!-- Header -->
        <div class="detail-header">
          <div class="row items-center no-wrap">
            <q-icon name="receipt_long" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">Detalle de compra #{{ selected?.id }}</div>
              <div class="text-caption text-grey-3">
                {{ formatDateTime(selected?.fecha_hora) }}
              </div>
            </div>
            <q-space />
            <q-badge
              v-if="selected"
              :color="selected.estado === 'ACTIVO' ? 'green' : 'red'"
              class="q-pa-sm text-weight-bold q-mr-sm"
            >
              {{ selected.estado }}
            </q-badge>
            <q-btn flat round dense icon="close" color="white" @click="detailDialog = false" />
          </div>
        </div>

        <q-card-section v-if="selected" class="q-pa-md">
          <!-- Metadatos -->
          <div class="meta-grid">
            <div class="meta-item">
              <q-icon name="local_shipping" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Proveedor</div>
                <div class="meta-value">{{ selected.proveedor?.nombre || selected.nombre || 'Sin proveedor' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="assignment" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Motivo</div>
                <div class="meta-value">{{ (selected.motivo_registro || '-').toLowerCase() }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="payments" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Tipo de pago</div>
                <div class="meta-value">{{ pagoLabel(selected.tipo_pago) }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="description" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">N° factura</div>
                <div class="meta-value">{{ selected.nro_factura || '-' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="person" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Registrado por</div>
                <div class="meta-value">{{ selected.user?.name || '-' }}</div>
              </div>
            </div>
            <div class="meta-item">
              <q-icon name="swap_horiz" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Tipo</div>
                <div class="meta-value">{{ (selected.tipo_registro || '-').toLowerCase() }}</div>
              </div>
            </div>
            <div v-if="selected.comentario" class="meta-item">
              <q-icon name="comment" size="20px" class="meta-icon" />
              <div class="meta-content">
                <div class="meta-label">Comentario</div>
                <div class="meta-value">{{ selected.comentario }}</div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <!-- Productos -->
        <q-card-section class="q-pa-md">
          <div class="row items-center q-mb-sm">
            <q-icon name="inventory_2" size="18px" color="primary" class="q-mr-xs" />
            <div class="text-subtitle2 text-weight-bold">Productos</div>
            <q-space />
            <q-chip dense color="primary" text-color="white" :label="`${(selected?.detalles || []).length} items`" />
          </div>
          <div class="detail-items">
            <div
              v-for="det in selected?.detalles || []"
              :key="det.id"
              class="detail-item"
            >
              <q-img
                :src="itemImageUrl(det)"
                class="detail-item-img"
                fit="cover"
                no-spinner
              />
              <div class="detail-item-info">
                <div class="detail-item-name">{{ det.nombre || det.producto?.nombre || '-' }}</div>
                <div class="detail-item-meta">
                  <span>{{ det.cantidad }} × {{ money(det.precio) }} Bs</span>
                  <span v-if="det.lote">· Lote: {{ det.lote }}</span>
                  <span v-if="det.fecha_vencimiento">· Vence: {{ formatDate(det.fecha_vencimiento) }}</span>
                </div>
              </div>
              <div class="detail-item-total">{{ money(det.total) }} Bs</div>
            </div>
            <div v-if="(selected?.detalles || []).length === 0" class="text-center text-grey-7 q-pa-md">
              Sin productos
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <!-- Resumen -->
        <q-card-section class="q-pa-md detail-summary">
          <div class="summary-row">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">{{ money(selected?.total) }} Bs</span>
          </div>
          <div class="summary-row">
            <span class="summary-label">Items</span>
            <span class="summary-value">{{ (selected?.detalles || []).length }}</span>
          </div>
          <q-separator class="q-my-sm" />
          <div class="summary-row total-row">
            <span class="total-label">Total</span>
            <span class="total-value">{{ money(selected?.total) }} Bs</span>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions class="q-pa-md">
          <q-space />
          <q-btn flat no-caps color="grey-8" label="Cerrar" @click="detailDialog = false" />
          <q-btn
            v-if="selected"
            unelevated
            no-caps
            color="teal"
            icon="print"
            label="Imprimir"
            :loading="printingId === selected?.id"
            @click="imprimir(selected)"
          />
          <q-btn
            v-if="selected && selected.estado !== 'ANULADO'"
            unelevated
            no-caps
            color="primary"
            icon="edit"
            label="Modificar"
            :disable="isBlocked(selected)"
            @click="editar(selected)"
          />
          <div v-if="selected && isBlocked(selected)" class="text-caption text-negative q-ml-sm self-center">
            <q-icon name="info" /> {{ blockReason(selected) }}
          </div>
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'ComprasPage',
  data () {
    return {
      loading: false,
      printingId: null,
      rows: [],
      summary: { total_compras: 0, total_anuladas: 0, cantidad: 0 },
      selected: null,
      detailDialog: false,
      filters: {
        date_from: moment().startOf('isoWeek').format('YYYY-MM-DD'),
        date_to: moment().endOf('isoWeek').format('YYYY-MM-DD'),
        estado: null,
        q: '',
      },
      pagination: { page: 1, rowsPerPage: 15, rowsNumber: 0 },
      estadoOptions: [
        { label: 'Activo', value: 'ACTIVO' },
        { label: 'Anulado', value: 'ANULADO' },
      ],
      pagoOptions: [
        { label: 'Efectivo', value: 'EFECTIVO', icon: 'payments' },
        { label: 'Crédito', value: 'CREDITO', icon: 'credit_card' },
        { label: 'Transferencia', value: 'TRANSFERENCIA', icon: 'swap_horiz' },
        { label: 'QR', value: 'QR', icon: 'qr_code_2' },
      ],
      columns: [
        { name: 'actions', label: 'Acciones', field: 'id', align: 'left', style: 'width: 130px' },
        { name: 'id', label: '#', field: 'id', align: 'left', style: 'width: 60px' },
        { name: 'fecha_hora', label: 'Fecha', field: 'fecha_hora', align: 'left' },
        { name: 'proveedor', label: 'Proveedor', field: row => row.proveedor?.nombre || row.nombre || '-', align: 'left' },
        { name: 'productos', label: 'Productos', field: 'detalles', align: 'left' },
        { name: 'motivo_registro', label: 'Motivo', field: 'motivo_registro', align: 'left' },
        { name: 'total', label: 'Total', field: 'total', align: 'right' },
        { name: 'tipo_pago', label: 'Pago', field: 'tipo_pago', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
      ],
    }
  },
  computed: {
    atajosSemana () {
      const from = this.filters.date_from
      const to = this.filters.date_to
      const estaSemanaDe = moment().startOf('isoWeek').format('YYYY-MM-DD')
      const estaSemanaA = moment().endOf('isoWeek').format('YYYY-MM-DD')
      const pasadaDe = moment().subtract(1, 'weeks').startOf('isoWeek').format('YYYY-MM-DD')
      const pasadaA = moment().subtract(1, 'weeks').endOf('isoWeek').format('YYYY-MM-DD')
      const mesDe = moment().startOf('month').format('YYYY-MM-DD')
      const mesA = moment().endOf('month').format('YYYY-MM-DD')
      return [
        {
          label: 'Esta semana',
          active: from === estaSemanaDe && to === estaSemanaA,
          fn: () => this.setRango(estaSemanaDe, estaSemanaA),
        },
        {
          label: 'Semana pasada',
          active: from === pasadaDe && to === pasadaA,
          fn: () => this.setRango(pasadaDe, pasadaA),
        },
        {
          label: 'Este mes',
          active: from === mesDe && to === mesA,
          fn: () => this.setRango(mesDe, mesA),
        },
      ]
    },
  },
  mounted () {
    this.fetchRows()
  },
  methods: {
    setRango (from, to) {
      this.filters.date_from = from
      this.filters.date_to = to
      this.fetchRows()
    },
    async fetchRows () {
      this.loading = true
      try {
        const res = await this.$axios.get('compras', {
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
      const res = await this.$axios.get(`compras/${row.id}`)
      this.selected = res.data
      this.detailDialog = true
    },
    editar (row) {
      this.detailDialog = false
      this.$router.push(`/almacen/compras/${row.id}/editar`)
    },
    async imprimir (row) {
      if (!row?.id) return
      this.printingId = row.id
      try {
        const res = await this.$axios.get(`compras/${row.id}/pdf`, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
        window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el PDF')
      } finally {
        this.printingId = null
      }
    },
    anular (row) {
      if (this.isBlocked(row)) {
        this.$alert.warning(this.blockReason(row))
        return
      }
      this.$q.dialog({
        title: 'Anular compra',
        message: `¿Anular la compra #${row.id}? Esta acción marca la compra y sus detalles como anulados.`,
        cancel: true,
        persistent: true,
        ok: { label: 'Anular', color: 'negative', noCaps: true },
      }).onOk(async () => {
        try {
          await this.$axios.delete(`compras/${row.id}`)
          this.$alert.success('Compra anulada')
          await this.fetchRows()
        } catch (e) {
          this.$alert.error(e.response?.data?.message || 'No se pudo anular la compra')
        }
      })
    },
    hasVentas (row) {
      if (!row) return false
      if (row.vendido_total !== undefined && row.vendido_total !== null) {
        return Number(row.vendido_total) > 0
      }
      if (Array.isArray(row.detalles)) {
        return row.detalles.some(d => Number(d?.cantidad_venta || 0) > 0)
      }
      return false
    },
    hasDespachos (row) {
      if (!row) return false
      return Number(row.despachado_count || 0) > 0
    },
    isTooOld (row) {
      if (!row?.created_at) return false
      return moment(row.created_at).isBefore(moment().subtract(1, 'month'))
    },
    isBlocked (row) {
      return this.isTooOld(row) || this.hasVentas(row) || this.hasDespachos(row)
    },
    blockReason (row) {
      if (this.isTooOld(row)) return 'Bloqueado: más de 1 mes de antigüedad'
      if (this.hasDespachos(row)) return 'Bloqueado: ya fue despachado'
      if (this.hasVentas(row)) return 'Bloqueado: ya hay ventas'
      return ''
    },
    money (value) {
      return Number(value || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    formatDate (datetime) {
      if (!datetime) return '-'
      return moment(datetime).format('DD/MM/YYYY')
    },
    formatTime (datetime) {
      if (!datetime) return ''
      return moment(datetime).format('HH:mm')
    },
    formatDateTime (datetime) {
      if (!datetime) return '-'
      return moment(datetime).format('DD/MM/YYYY HH:mm')
    },
    pagoLabel (value) {
      if (!value) return 'Ninguno'
      const opt = this.pagoOptions.find(p => p.value === value)
      return opt ? opt.label : value
    },
    pagoIcon (value) {
      const opt = this.pagoOptions.find(p => p.value === value)
      return opt ? opt.icon : 'payments'
    },
    itemImageUrl (det) {
      const imagen = det?.producto?.imagen || det?.imagen || 'default.png'
      return `${this.$url}/../images/productos/${imagen}`
    },
  },
}
</script>

<style scoped>
.summary-card {
  border-radius: 10px;
}

.summary-green { border-left: 4px solid #43a047; }
.summary-red { border-left: 4px solid #c90022; }
.summary-blue { border-left: 4px solid #1976d2; }

/* Diálogo de detalle */
.detail-dialog {
  width: 760px;
  max-width: 94vw;
  max-height: 92vh;
  border-radius: 10px;
  overflow-y: auto;
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

.meta-icon {
  color: #1976d2;
  margin-top: 2px;
}

.meta-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

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
  text-transform: capitalize;
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

.detail-item:last-child {
  border-bottom: none;
}

.detail-item-img {
  flex: 0 0 auto;
  width: 56px;
  height: 56px;
  border-radius: 6px;
  border: 1px solid #e5eaf2;
  background: #f5f5f5;
}

.detail-item-info {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

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
}

.detail-item-total {
  font-size: 14px;
  font-weight: 700;
  color: #1976d2;
  white-space: nowrap;
}

.detail-summary {
  background: #f7f9fc;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.summary-label {
  font-size: 13px;
  color: #4b5563;
}

.summary-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.total-row {
  padding: 6px 12px;
  background: #e3f2fd;
  border-radius: 8px;
}

.total-label {
  font-size: 15px;
  font-weight: 700;
  color: #0d47a1;
}

.total-value {
  font-size: 22px;
  font-weight: 800;
  color: #1976d2;
}
</style>
