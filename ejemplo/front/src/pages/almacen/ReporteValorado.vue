<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row items-center q-gutter-sm">
        <div>
          <div class="text-h6 text-weight-bold">Reporte Total Valorado</div>
          <div class="text-caption text-grey-7">Método PEPS — Primeras entradas, primeras salidas</div>
        </div>
        <q-space />
        <q-btn
          color="green-8"
          icon="table_view"
          label="Excel"
          no-caps
          dense
          :loading="excelLoading"
          @click="exportExcel"
        />
        <q-btn
          color="red-8"
          icon="picture_as_pdf"
          label="PDF"
          no-caps
          dense
          :loading="pdfLoading"
          @click="exportPdf"
        />
        <q-btn dense flat round icon="refresh" :loading="loading" @click="loadData">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </q-card-section>

      <q-separator />

      <!-- Filters -->
      <q-card-section class="q-pb-sm">
        <div class="row q-col-gutter-sm items-end">
          <div class="col-12 col-sm-5 col-md-5">
            <q-select
              v-model="filters.producto_id"
              :options="productoOptions"
              dense
              outlined
              emit-value
              map-options
              label="Producto"
              use-input
              input-debounce="300"
              @filter="filterProductos"
            >
              <template #selected-item="scope">
                <div v-if="scope.opt.value === null" class="row items-center q-gutter-xs">
                  <q-icon name="select_all" color="primary" size="18px" />
                  <span class="text-weight-medium">Todos los productos</span>
                </div>
                <div v-else class="row items-center q-gutter-xs no-wrap" style="max-width:100%;">
                  <img
                    :src="imgUrl(scope.opt)"
                    style="width:22px;height:22px;object-fit:cover;border-radius:3px;"
                    @error="e => e.target.src = imgUrl(null)"
                  />
                  <span class="ellipsis">{{ scope.opt.label }}</span>
                  <q-chip dense size="xs" color="blue-1" text-color="primary" class="q-ml-xs">
                    {{ scope.opt.cantidad }} u.
                  </q-chip>
                </div>
              </template>
              <template #option="scope">
                <q-item v-bind="scope.itemProps" dense>
                  <q-item-section v-if="scope.opt.value === null" avatar>
                    <q-icon name="select_all" color="primary" />
                  </q-item-section>
                  <q-item-section v-else avatar style="min-width:38px;">
                    <img
                      :src="imgUrl(scope.opt)"
                      style="width:34px;height:34px;object-fit:cover;border-radius:4px;"
                      @error="e => e.target.src = imgUrl(null)"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ scope.opt.label }}</q-item-label>
                    <q-item-label v-if="scope.opt.value !== null" caption>
                      Stock: <strong>{{ scope.opt.cantidad }}</strong> {{ scope.opt.unidad }}
                      &nbsp;·&nbsp;
                      Valorado: <strong>Bs. {{ fmt(scope.opt.valorado) }}</strong>
                    </q-item-label>
                    <q-item-label v-else caption class="text-primary">Genera reporte de todos los productos</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
              </template>
            </q-select>
          </div>
          <div class="col-6 col-sm-3 col-md-2">
            <q-input
              v-model="filters.date_from"
              dense
              outlined
              label="Desde"
              type="date"
              clearable
            />
          </div>
          <div class="col-6 col-sm-3 col-md-2">
            <q-input
              v-model="filters.date_to"
              dense
              outlined
              label="Hasta"
              type="date"
              clearable
            />
          </div>
          <div class="col-12 col-sm-2 col-md-2">
            <q-btn
              color="primary"
              icon="search"
              label="Consultar"
              no-caps
              dense
              class="full-width"
              :loading="loading"
              @click="loadData"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- Cards -->
      <q-card-section v-if="loading" class="text-center q-py-xl">
        <q-spinner color="primary" size="48px" />
        <div class="text-grey-6 q-mt-sm">Cargando datos PEPS...</div>
      </q-card-section>

      <q-card-section v-else-if="cards.length === 0" class="text-center q-py-xl">
        <q-icon name="inventory_2" size="48px" color="grey-4" />
        <div class="text-grey-6 q-mt-sm">Sin movimientos para los filtros seleccionados</div>
      </q-card-section>

      <template v-else>
        <div v-for="card in cards" :key="card.producto_id" class="q-px-sm q-pb-md">
          <!-- Product header -->
          <div class="row items-center q-py-xs q-px-sm bg-blue-1 rounded-borders q-mt-sm" style="border-left: 4px solid #0f5ea8;">
            <div>
              <span class="text-weight-bold text-blue-9 text-subtitle2">{{ card.producto }}</span>
              <q-chip dense size="sm" color="blue-2" text-color="blue-9" class="q-ml-sm">{{ card.unidad }}</q-chip>
            </div>
            <q-space />
            <div class="row q-gutter-md text-caption">
              <div>
                <span class="text-grey-6">Entradas:</span>
                <span class="text-weight-bold text-positive q-ml-xs">{{ card.summary.total_entradas_cantidad }} u. / Bs. {{ fmt(card.summary.total_entradas_valor) }}</span>
              </div>
              <div>
                <span class="text-grey-6">Salidas:</span>
                <span class="text-weight-bold text-negative q-ml-xs">{{ card.summary.total_salidas_cantidad }} u. / Bs. {{ fmt(card.summary.total_salidas_valor) }}</span>
              </div>
              <div>
                <span class="text-grey-6">Saldo:</span>
                <span class="text-weight-bold text-primary q-ml-xs">{{ card.summary.saldo_final_cantidad }} u. / Bs. {{ fmt(card.summary.saldo_final_valor) }}</span>
              </div>
              <div>
                <span class="text-grey-6">Costo ventas:</span>
                <span class="text-weight-bold text-orange-9 q-ml-xs">Bs. {{ fmt(card.summary.costo_ventas) }}</span>
              </div>
            </div>
          </div>

          <!-- PEPS table -->
          <div class="overflow-auto q-mt-xs">
            <table class="peps-table full-width">
              <thead>
                <tr>
                  <th rowspan="2" class="fecha-col">Fecha</th>
                  <th rowspan="2" class="concepto-col">Concepto</th>
                  <th colspan="3" class="group-header entrada-header">Entradas</th>
                  <th colspan="3" class="group-header salida-header">Salidas</th>
                  <th colspan="3" class="group-header saldo-header">Saldos</th>
                </tr>
                <tr>
                  <th class="num-col entrada-header">Cant.</th>
                  <th class="num-col entrada-header">V. Unit.</th>
                  <th class="num-col entrada-header">V. Total</th>
                  <th class="num-col salida-header">Cant.</th>
                  <th class="num-col salida-header">V. Unit.</th>
                  <th class="num-col salida-header">V. Total</th>
                  <th class="num-col saldo-header">Cant.</th>
                  <th class="num-col saldo-header">V. Unit.</th>
                  <th class="num-col saldo-header">V. Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in card.rows"
                  :key="idx"
                  :class="row.tipo === 'ENTRADA' ? 'row-entrada' : 'row-salida'"
                >
                  <td class="fecha-col text-center text-caption">{{ formatDate(row.fecha) }}</td>
                  <td class="concepto-col text-caption">{{ row.concepto }}</td>

                  <!-- Entradas -->
                  <template v-if="row.tipo === 'ENTRADA'">
                    <td class="num-col text-right text-positive text-weight-medium">{{ row.cantidad }}</td>
                    <td class="num-col text-right text-positive">{{ fmt4(row.precio_unitario) }}</td>
                    <td class="num-col text-right text-positive text-weight-medium">{{ fmt(row.total) }}</td>
                    <td class="num-col text-right text-grey-4">—</td>
                    <td class="num-col text-right text-grey-4">—</td>
                    <td class="num-col text-right text-grey-4">—</td>
                  </template>
                  <template v-else>
                    <td class="num-col text-right text-grey-4">—</td>
                    <td class="num-col text-right text-grey-4">—</td>
                    <td class="num-col text-right text-grey-4">—</td>
                    <td class="num-col text-right text-negative text-weight-medium">{{ row.cantidad }}</td>
                    <td class="num-col text-right text-negative">{{ fmt4(row.precio_unitario) }}</td>
                    <td class="num-col text-right text-negative text-weight-medium">{{ fmt(row.total) }}</td>
                  </template>

                  <!-- Saldo -->
                  <td class="num-col text-right text-primary text-weight-bold">{{ row.saldo_cantidad }}</td>
                  <td class="num-col text-right text-primary">{{ fmt4(row.saldo_precio_unitario) }}</td>
                  <td class="num-col text-right text-primary text-weight-bold">{{ fmt(row.saldo_total) }}</td>
                </tr>

                <!-- Totals row -->
                <tr class="row-totals">
                  <td colspan="2" class="text-weight-bold text-caption">TOTALES</td>
                  <td class="num-col text-right text-weight-bold">{{ card.summary.total_entradas_cantidad }}</td>
                  <td class="num-col text-right text-grey-6">—</td>
                  <td class="num-col text-right text-weight-bold">{{ fmt(card.summary.total_entradas_valor) }}</td>
                  <td class="num-col text-right text-weight-bold">{{ card.summary.total_salidas_cantidad }}</td>
                  <td class="num-col text-right text-grey-6">—</td>
                  <td class="num-col text-right text-weight-bold">{{ fmt(card.summary.total_salidas_valor) }}</td>
                  <td class="num-col text-right text-weight-bold">{{ card.summary.saldo_final_cantidad }}</td>
                  <td class="num-col text-right text-grey-6">—</td>
                  <td class="num-col text-right text-weight-bold">{{ fmt(card.summary.saldo_final_valor) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Grand totals across all products -->
        <q-card-section v-if="cards.length > 1" class="q-pt-none">
          <q-separator class="q-mb-md" />
          <div class="row q-gutter-md justify-end">
            <q-card flat bordered style="min-width: 300px;">
              <q-card-section class="q-pa-sm">
                <div class="text-subtitle2 text-weight-bold text-primary q-mb-xs">Resumen General</div>
                <div class="row justify-between q-py-xs">
                  <span class="text-caption text-grey-7">Total entradas</span>
                  <span class="text-weight-bold text-positive">Bs. {{ fmt(grandSummary.total_entradas) }}</span>
                </div>
                <div class="row justify-between q-py-xs">
                  <span class="text-caption text-grey-7">Total salidas</span>
                  <span class="text-weight-bold text-negative">Bs. {{ fmt(grandSummary.total_salidas) }}</span>
                </div>
                <q-separator class="q-my-xs" />
                <div class="row justify-between q-py-xs">
                  <span class="text-caption text-weight-bold text-orange-9">Costo total de ventas</span>
                  <span class="text-weight-bold text-orange-9">Bs. {{ fmt(grandSummary.costo_ventas) }}</span>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </q-card-section>
      </template>
    </q-card>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'ReporteValorado',

  data() {
    return {
      loading: false,
      pdfLoading: false,
      excelLoading: false,
      cards: [],
      allProductos: [],
      productoOptions: [],
      filters: {
        producto_id: null,
        date_from: moment().startOf('year').format('YYYY-MM-DD'),
        date_to: moment().format('YYYY-MM-DD'),
      },
    }
  },

  computed: {
    grandSummary() {
      return {
        total_entradas: this.cards.reduce((s, c) => s + c.summary.total_entradas_valor, 0),
        total_salidas: this.cards.reduce((s, c) => s + c.summary.total_salidas_valor, 0),
        costo_ventas: this.cards.reduce((s, c) => s + c.summary.costo_ventas, 0),
      }
    },
  },

  mounted() {
    this.loadProductos()
  },

  methods: {
    async loadProductos() {
      try {
        const res = await this.$axios.get('almacen-items', { params: { rowsPerPage: 0 } })
        const items = res.data.data ?? res.data
        const mapped = (Array.isArray(items) ? items : []).map(p => ({
          label: p.nombre,
          value: p.id,
          imagen: p.imagen ?? null,
          unidad: p.unidad_medida ?? 'PZA',
          cantidad: p.cantidad ?? 0,
          precio_unitario: parseFloat(p.precio_unitario ?? 0),
          valorado: (p.cantidad ?? 0) * parseFloat(p.precio_unitario ?? 0),
        }))
        const todos = { label: 'Todos los productos', value: null, imagen: null, unidad: '', cantidad: 0, valorado: 0 }
        this.allProductos = [todos, ...mapped]
        this.productoOptions = this.allProductos
      } catch (e) {
        // silent
      }
    },

    filterProductos(val, update) {
      update(() => {
        if (!val) {
          this.productoOptions = this.allProductos
          return
        }
        const q = val.toLowerCase()
        this.productoOptions = this.allProductos.filter(p =>
          p.value === null || p.label.toLowerCase().includes(q)
        )
      })
    },

    imgUrl(opt) {
      const imagen = opt?.imagen
      return `${this.$url}/../images/productos/${imagen || 'default.png'}`
    },

    async loadData() {
      this.loading = true
      try {
        const params = {}
        if (this.filters.producto_id) params.producto_id = this.filters.producto_id
        if (this.filters.date_from) params.date_from = this.filters.date_from
        if (this.filters.date_to) params.date_to = this.filters.date_to

        const res = await this.$axios.get('reporte-valorado', { params })
        this.cards = res.data
      } catch (e) {
        this.$q.notify({ type: 'negative', message: 'Error al cargar el reporte' })
      } finally {
        this.loading = false
      }
    },

    async exportExcel() {
      this.excelLoading = true
      try {
        const params = {}
        if (this.filters.producto_id) params.producto_id = this.filters.producto_id
        if (this.filters.date_from) params.date_from = this.filters.date_from
        if (this.filters.date_to) params.date_to = this.filters.date_to

        const res = await this.$axios.get('reporte-valorado/excel', {
          params,
          responseType: 'blob',
        })
        const blob = new Blob([res.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        })
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        a.download = `reporte-valorado-peps.xlsx`
        a.click()
        URL.revokeObjectURL(a.href)
      } catch (e) {
        this.$q.notify({ type: 'negative', message: 'Error al generar el Excel' })
      } finally {
        this.excelLoading = false
      }
    },

    async exportPdf() {
      this.pdfLoading = true
      try {
        const params = {}
        if (this.filters.producto_id) params.producto_id = this.filters.producto_id
        if (this.filters.date_from) params.date_from = this.filters.date_from
        if (this.filters.date_to) params.date_to = this.filters.date_to

        const res = await this.$axios.get('reporte-valorado/pdf', {
          params,
          responseType: 'blob',
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
      } catch (e) {
        this.$q.notify({ type: 'negative', message: 'Error al generar el PDF' })
      } finally {
        this.pdfLoading = false
      }
    },

    formatDate(val) {
      return val ? moment(val).format('DD/MM/YYYY') : '—'
    },

    fmt(val) {
      return Number(val ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },

    fmt4(val) {
      return Number(val ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 4 })
    },
  },
}
</script>

<style scoped>
.peps-table {
  border-collapse: collapse;
  font-size: 12px;
  min-width: 860px;
}
.peps-table th,
.peps-table td {
  border: 1px solid #c5d9ef;
  padding: 4px 8px;
  white-space: nowrap;
}
.peps-table thead th {
  background: #1565c0;
  color: #fff;
  text-transform: uppercase;
  font-size: 11px;
}
.group-header {
  text-align: center;
  font-size: 12px !important;
  letter-spacing: 0.3px;
}
.entrada-header {
  background: #1b5e20 !important;
}
.salida-header {
  background: #b71c1c !important;
}
.saldo-header {
  background: #0d47a1 !important;
}
.fecha-col {
  width: 90px;
  min-width: 80px;
}
.concepto-col {
  min-width: 180px;
  text-align: left;
  text-transform: uppercase;
}
.num-col {
  width: 90px;
  min-width: 80px;
  text-align: right;
}
.row-entrada {
  background: #f1f8e9;
}
.row-salida {
  background: #fff3e0;
}
.row-totals td {
  background: #e3f2fd;
  border-top: 2px solid #1565c0;
}
</style>
