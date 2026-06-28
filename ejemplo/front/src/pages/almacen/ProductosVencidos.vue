<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Productos vencidos</div>
        <div class="text-caption text-grey-7">Lotes vencidos con existencia (no utilizados)</div>
      </div>
      <q-space />
      <q-btn dense flat round icon="refresh" :loading="loading" @click="fetchRows">
        <q-tooltip>Actualizar</q-tooltip>
      </q-btn>
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-sm-4">
        <q-card flat bordered>
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="44px" color="red-7" text-color="white" icon="warning" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Existencia total (vencidos)</div>
              <div class="text-h6 text-weight-bold">{{ summary.existencia_total || 0 }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-8">
        <q-card flat bordered>
          <q-card-section class="row q-col-gutter-sm items-center q-pa-sm">
            <div class="col-12 col-sm-3">
              <q-input v-model="filters.hasta" dense outlined type="date" label="Vencidos hasta" />
            </div>
            <div class="col-12 col-sm">
              <q-input v-model="filters.q" dense outlined clearable label="Buscar (producto, lote, factura, usuario)">
                <template #prepend><q-icon name="search" /></template>
              </q-input>
            </div>
            <div class="col-auto">
              <q-btn unelevated color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="onSearch" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered>
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
        <template #top>
          <div class="row items-center q-col-gutter-sm full-width q-pa-sm">
            <div class="col">
              <div class="text-caption text-grey-7">
                Vencidos hasta: {{ serverFilters.hasta || '-' }}
              </div>
            </div>
          </div>
        </template>

        <template #body-cell-fecha_vencimiento="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <div class="text-weight-medium">{{ formatDate(props.row.fecha_vencimiento) }}</div>
              <q-space />
              <q-chip dense square :color="expiredChipColor(daysToExpire(props.row.fecha_vencimiento))" text-color="white">
                {{ expiredChipLabel(daysToExpire(props.row.fecha_vencimiento)) }}
              </q-chip>
            </div>
          </q-td>
        </template>

        <template #body-cell-producto="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar rounded size="32px" class="q-mr-sm">
                <q-img :src="itemImageUrl(props.row)" ratio="1" fit="cover" />
              </q-avatar>
              <div>
                <div class="text-weight-medium text-capitalize">{{ props.row.producto?.nombre || props.row.nombre || '-' }}</div>
                <div class="text-caption text-grey-7">
                  Lote: {{ props.row.lote || '-' }}
                  <span v-if="props.row.compra?.nro_factura"> | Factura: {{ props.row.compra.nro_factura }}</span>
                </div>
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-existencia="props">
          <q-td :props="props" class="text-right">
            <q-chip dense square color="red-1" text-color="red-10">
              {{ props.row.existencia }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-compra="props">
          <q-td :props="props">
            <div class="text-weight-medium">#{{ props.row.compra?.id || '-' }}</div>
            <div class="text-caption text-grey-7">
              {{ formatDateTime(props.row.compra?.fecha_hora) }}
            </div>
          </q-td>
        </template>

        <template #body-cell-usuario="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.compra?.user?.name || '-' }}</div>
            <div class="text-caption text-grey-7">{{ props.row.compra?.proveedor?.nombre || 'Sin proveedor' }}</div>
          </q-td>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'ProductosVencidosPage',
  data () {
    const today = new Date()
    const yyyy = today.getFullYear()
    const mm = String(today.getMonth() + 1).padStart(2, '0')
    const dd = String(today.getDate()).padStart(2, '0')
    return {
      loading: false,
      rows: [],
      summary: {
        existencia_total: 0
      },
      serverFilters: {
        hasta: null
      },
      filters: {
        hasta: `${yyyy}-${mm}-${dd}`,
        q: ''
      },
      pagination: {
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0
      },
      columns: [
        { name: 'fecha_vencimiento', label: 'Vence', field: 'fecha_vencimiento', align: 'left', sortable: true },
        { name: 'producto', label: 'Producto / Lote', field: row => row.producto?.nombre || row.nombre, align: 'left' },
        { name: 'existencia', label: 'Existencia', field: 'existencia', align: 'right', sortable: true },
        { name: 'cantidad', label: 'Entrada', field: 'cantidad', align: 'right', sortable: true },
        { name: 'cantidad_venta', label: 'Salida', field: 'cantidad_venta', align: 'right', sortable: true },
        { name: 'cantidad_venta', label: 'Salida', field: 'cantidad_venta', align: 'right', sortable: true },
        { name: 'compra', label: 'Compra', field: row => row.compra?.id, align: 'left' },
        { name: 'usuario', label: 'Usuario / Proveedor', field: row => row.compra?.user?.name, align: 'left' }
      ]
    }
  },
  mounted () {
    this.fetchRows()
  },
  methods: {
    onSearch () {
      this.pagination.page = 1
      this.fetchRows()
    },
    async onRequest (props) {
      this.pagination = props.pagination
      await this.fetchRows()
    },
    formatDate (value) {
      if (!value) return '-'
      return new Date(value).toLocaleDateString()
    },
    formatDateTime (value) {
      if (!value) return '-'
      return this.$filters.dateDmYHis(value)
    },
    itemImageUrl (row) {
      const imagen = row?.producto?.imagen || 'default.png'
      return `${this.$url}/../images/productos/${imagen}`
    },
    daysToExpire (fechaVencimiento) {
      if (!fechaVencimiento) return null
      const today = new Date()
      const startToday = new Date(today.getFullYear(), today.getMonth(), today.getDate())
      const venc = new Date(`${fechaVencimiento}T00:00:00`)
      const diffMs = venc.getTime() - startToday.getTime()
      return Math.round(diffMs / (1000 * 60 * 60 * 24))
    },
    expiredChipColor (days) {
      if (days === null) return 'grey-7'
      if (days >= 0) return 'grey-7'
      const daysAgo = Math.abs(days)
      if (daysAgo <= 7) return 'red-7'
      if (daysAgo <= 30) return 'red-8'
      return 'red-10'
    },
    expiredChipLabel (days) {
      if (days === null) return '-'
      if (days >= 0) return `${days}d`
      return `Vencido (${Math.abs(days)}d)`
    },
    async fetchRows () {
      this.loading = true
      try {
        const params = {
          hasta: this.filters.hasta || undefined,
          q: this.filters.q || undefined,
          rowsPerPage: this.pagination.rowsPerPage,
          page: this.pagination.page
        }
        const res = await this.$axios.get('almacen/productos-vencidos', { params })
        this.rows = res.data.data || []
        this.pagination.rowsNumber = res.data.total || 0
        this.pagination.page = res.data.current_page || this.pagination.page
        this.summary = res.data.summary || { existencia_total: 0 }
        this.serverFilters = res.data.filters || { hasta: null }
      } catch (e) {
        console.log(e)
        this.$alert.error('No se pudo cargar productos vencidos')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

