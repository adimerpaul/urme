<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Pedidos nuevo</div>
        <div class="text-caption text-grey-7">Registro pedido almacen</div>
      </div>
      <q-space />
      <q-btn flat icon="arrow_back" label="Volver" to="/pedidos" no-caps />
    </div>

    <q-banner
      v-if="!loadingVentana && !pedidosPermitidos"
      rounded
      class="bg-orange-1 text-orange-10 q-mb-sm"
      style="border: 1px solid #ffcc80;"
    >
      <template #avatar>
        <q-icon name="block" color="orange-7" size="26px" />
      </template>
      <div class="text-weight-bold">Pedidos deshabilitados</div>
      <div class="text-body2">{{ ventanaPedidoMensaje }}</div>
    </q-banner>

    <q-banner
      v-if="!loadingLimite && limiteMensual.bloqueado"
      rounded
      class="bg-red-1 text-red-10 q-mb-sm"
      style="border: 1px solid #ef9a9a;"
    >
      <template #avatar>
        <q-icon name="shopping_cart_checkout" color="red-7" size="26px" />
      </template>
      <div class="text-weight-bold">Límite mensual alcanzado</div>
      <div class="text-body2">
        Ya realizaste {{ limiteMensual.pedidos_mes }} de {{ limiteMensual.max_pedidos }} pedido(s) permitido(s) este mes.
        Contacta al administrador para que amplíe tu límite.
      </div>
    </q-banner>

    <q-banner
      v-if="!loadingLimite && !limiteMensual.bloqueado && pedidosPermitidos"
      rounded
      class="bg-blue-1 text-blue-10 q-mb-sm"
      style="border: 1px solid #90caf9;"
    >
      <template #avatar>
        <q-icon name="info" color="blue-6" size="22px" />
      </template>
      <div class="text-body2">
        Este mes has realizado <strong>{{ limiteMensual.pedidos_mes }}</strong> de <strong>{{ limiteMensual.max_pedidos }}</strong> pedido(s) permitido(s).
      </div>
    </q-banner>

    <div class="row q-col-gutter-sm">
      <!-- Carrusel de Productos -->
      <div class="col-12 col-md-5">
        <q-card flat bordered>
          <q-card-section class="q-pa-sm">
            <q-input
              v-model="productFilter"
              dense
              outlined
              clearable
              debounce="350"
              label="Busca producto"
              @update:model-value="fetchProducts"
            >
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </q-card-section>
          <q-separator />
          <q-card-section class="product-grid q-pa-sm">
            <div
              v-for="item in products"
              :key="item.id"
              class="product-card"
              :class="{ 'product-card--disabled': !puedeCrearPedido }"
              @click="addItem(item)"
            >
              <div class="product-image-wrapper">
                <q-img :src="itemImageUrl(item)" class="product-image" fit="cover" />
                <div class="product-name-overlay" :title="item.nombre">{{ item.nombre }}</div>
              </div>
              <div class="product-info">
                <div class="product-qty">{{ item.unidad_medida || '-' }}</div>
                <div v-if="canVerStock" class="product-stock">
                  <q-icon name="inventory_2" size="10px" />
                  {{ item.cantidad }} disponibles
                </div>
                <div class="product-price">{{ money(item.precio_unitario) }} Bs</div>
              </div>
            </div>
          </q-card-section>
          <q-card-section class="row justify-center q-pa-xs">
            <q-pagination
              v-model="productPagination.page"
              :max="productPages"
              max-pages="6"
              size="sm"
              @update:model-value="fetchProducts"
            />
          </q-card-section>
        </q-card>
      </div>

      <!-- Formulario y Tabla -->
      <div class="col-12 col-md-7">
        <q-card flat bordered>
          <q-card-section class="q-pa-sm">
            <q-input
              v-model="comentario"
              dense
              outlined
              type="textarea"
              autogrow
              label="Comentario (opcional)"
            >
              <template #prepend><q-icon name="comment" /></template>
            </q-input>
          </q-card-section>

          <q-separator />

          <!-- Header: Datos -->
<!--          <q-card-section class="q-pa-xs">-->
<!--            <div class="row q-col-gutter-xs">-->
<!--              <div class="col-12 col-sm-6">-->
<!--                <q-input :model-value="currentUsername" dense outlined label="USUARIO" readonly disable />-->
<!--              </div>-->
<!--              <div class="col-12 col-sm-6">-->
<!--                <q-input model-value="AUTOMATICO" dense outlined label="FECHA Y HORA" readonly disable />-->
<!--              </div>-->
<!--            </div>-->
<!--          </q-card-section>-->

<!--          <q-separator />-->

          <!-- Toolbar de la tabla -->
          <div class="row items-center q-px-sm q-pt-sm">
            <div class="text-caption text-grey-7">
              {{ selectedItems.length }} Porducto{{ selectedItems.length === 1 ? '' : 's' }} en el pedido
            </div>
            <q-space />
            <q-btn
              flat
              dense
              size="sm"
              color="negative"
              icon="delete_sweep"
              label="Borrar todos"
              no-caps
              :disable="selectedItems.length === 0"
              @click="clearAllItems"
            />
          </div>

          <!-- Tabla de Productos -->
          <div class="table-container q-pa-sm">
            <table class="items-table">
              <thead>
                <tr>
                  <th style="width: 44%">PRODUCTO</th>
                  <th style="width: 10%">CANT.</th>
                  <th style="width: 14%">P. UNIT.</th>
                  <th style="width: 14%">SUBTOTAL</th>
                  <th style="width: 6%">ACT.</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedItems" :key="item.producto_id">
                  <td class="producto-cell" :title="item.nombre">
                    <div class="producto-cell-content">
                      <q-img
                        :src="itemImageUrl(item)"
                        class="producto-cell-img"
                        fit="cover"
                        no-spinner
                      />
                      <span class="producto-cell-nombre">{{ item.nombre }}</span>
                    </div>
                  </td>
                  <td>
                    <input
                      v-model.number="item.cantidad"
                      type="number"
                      min="0"
                      step="any"
                      class="input-inline"
                      @change="recalculate(item)"
                    />
                  </td>
                  <td class="text-right">
                    {{ money(item.precio_unitario) }}
                  </td>
                  <td class="text-right text-weight-bold">
                    {{ money(item.subtotal) }}
                  </td>
                  <td class="text-center">
                    <q-btn flat dense round icon="delete" color="negative" size="sm" @click="removeItem(item)" />
                  </td>
                </tr>
                <tr v-if="selectedItems.length === 0">
                  <td colspan="5" class="text-center text-grey-7">0 DE 0 PRODUCTOS</td>
                </tr>
                <tr v-else>
                  <td colspan="5" class="text-center text-grey-7">0 DE {{ selectedItems.length }} PRODUCTOS</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Total y Botón -->
          <q-card-section class="row items-center q-pa-sm bg-blue-1">
            <div class="text-subtitle2 text-weight-bold">
              TOTAL:
              <span class="text-primary text-h6">{{ money(total) }} Bs</span>
            </div>
            <q-space />
            <q-btn
              color="primary"
              icon="check_circle"
              label="Confimar registro"
              :loading="saving"
              no-caps
              :disable="selectedItems.length === 0 || !puedeCrearPedido"
              @click="confirmSave"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Diálogo: Confirmación -->
    <q-dialog v-model="showConfirmDialog" @hide="confirmData = null">
      <q-card class="confirm-dialog">
        <div class="confirm-header">
          <div class="row items-center no-wrap">
            <q-icon name="shopping_bag" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">CONFIRMAR REGISTRO DE PEDIDO</div>
              <div class="text-caption text-grey-3">REVISA LOS DATOS ANTES DE GUARDAR</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="showConfirmDialog = false" />
          </div>
        </div>

        <div class="confirm-content">
          <q-card-section class="q-pa-md">
            <div class="row items-center q-mb-sm">
              <q-icon name="inventory_2" size="18px" color="primary" class="q-mr-xs" />
              <div class="text-subtitle2 text-weight-bold">PRODUCTOS</div>
              <q-space />
              <q-chip dense color="primary" text-color="white" :label="`${selectedItems.length} ITEMS`" />
            </div>

            <div class="confirm-items">
              <div v-for="(item, idx) in selectedItems" :key="idx" class="confirm-item">
                <q-img :src="itemImageUrl(item)" class="confirm-item-img" fit="cover" no-spinner />
                <div class="confirm-item-info">
                  <div class="confirm-item-name">{{ item.nombre }}</div>
                  <div class="confirm-item-meta">
                    {{ item.cantidad }} × {{ money(item.precio_unitario) }} Bs
                  </div>
                </div>
                <div class="confirm-item-total">{{ money(item.subtotal) }} Bs</div>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-md confirm-summary">
            <div class="summary-row">
              <span class="summary-label">ITEMS</span>
              <span class="summary-value">{{ selectedItems.length }}</span>
            </div>
            <div class="summary-row total-row">
              <span class="total-label">TOTAL A REGISTRAR</span>
              <span class="total-value">{{ money(total) }} Bs</span>
            </div>
            <div v-if="confirmData?.comentario" class="summary-comment">
              <div class="summary-comment-label">COMENTARIO</div>
              <div class="summary-comment-value">{{ confirmData.comentario }}</div>
            </div>
          </q-card-section>
        </div>

        <q-separator />

        <q-card-actions class="q-pa-md confirm-actions">
          <q-space />
          <q-btn label="Cancelar" flat color="grey-8" @click="showConfirmDialog = false" no-caps />
          <q-btn label="Guardar" unelevated color="primary" icon="check_circle" :loading="saving" @click="save" no-caps />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'PedidosNuevaPage',
  data () {
    return {
      loadingProducts: false,
      loadingVentana: false,
      loadingLimite: false,
      saving: false,
      limiteMensual: { max_pedidos: 1, pedidos_mes: 0, bloqueado: false },
      products: [],
      selectedItems: [],
      comentario: '',
      productFilter: '',
      productPagination: { page: 1, rowsPerPage: 30, rowsNumber: 0 },
      showConfirmDialog: false,
      confirmData: null,
      ventanaPedido: {
        fecha_inicio_pedido_almacen: null,
        fecha_fin_pedido_almacen: null,
        pedidos_habilitados: false,
      },
      form: {},
    }
  },
  computed: {
    currentUsername () {
      return this.$store?.user?.username || this.$store?.user?.name || '-'
    },
    canVerStock () {
      const perms = this.$store?.permissions || []
      const role  = this.$store?.user?.role || ''
      return role === 'Administrador' || perms.includes('Módulo inventario')
    },
    total () {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.subtotal || 0), 0)
    },
    productPages () {
      return Math.max(1, Math.ceil(this.productPagination.rowsNumber / this.productPagination.rowsPerPage))
    },
    fechasVentanaConfiguradas () {
      return !!this.ventanaPedido.fecha_inicio_pedido_almacen && !!this.ventanaPedido.fecha_fin_pedido_almacen
    },
    pedidosPermitidos () {
      return this.fechasVentanaConfiguradas && !!this.ventanaPedido.pedidos_habilitados
    },
    puedeCrearPedido () {
      return this.pedidosPermitidos && !this.limiteMensual.bloqueado
    },
    ventanaPedidoMensaje () {
      if (!this.fechasVentanaConfiguradas) {
        return 'No se puede crear un pedido porque falta configurar la fecha de inicio y la fecha de finalización en Herramientas de Almacén.'
      }

      return `La ventana configurada es del ${this.formatDate(this.ventanaPedido.fecha_inicio_pedido_almacen)} al ${this.formatDate(this.ventanaPedido.fecha_fin_pedido_almacen)}.`
    },
  },
  async mounted () {
    await Promise.all([this.fetchVentanaPedido(), this.fetchLimiteMensual()])
    await this.fetchProducts()
  },
  methods: {
    async fetchVentanaPedido () {
      this.loadingVentana = true
      try {
        const res = await this.$axios.get('herramientas-almacen')
        this.ventanaPedido = res.data || this.ventanaPedido
      } finally {
        this.loadingVentana = false
      }
    },
    async fetchLimiteMensual () {
      this.loadingLimite = true
      try {
        const res = await this.$axios.get('pedidos/limite-mensual')
        this.limiteMensual = res.data
      } finally {
        this.loadingLimite = false
      }
    },
    async fetchProducts () {
      this.loadingProducts = true
      try {
        const res = await this.$axios.get('almacen-items', {
          params: {
            page: this.productPagination.page,
            rowsPerPage: this.productPagination.rowsPerPage,
            q: this.productFilter,
            solo_mis_subpartidas: true,
            existente: 1,
          },
        })
        this.products = res.data.data || []
        this.productPagination.rowsNumber = res.data.total || 0
      } finally {
        this.loadingProducts = false
      }
    },
    addItem (product) {
      if (!this.pedidosPermitidos) {
        this.$q.notify({ color: 'negative', message: this.ventanaPedidoMensaje, position: 'top' })
        return
      }
      if (this.limiteMensual.bloqueado) {
        this.$q.notify({ color: 'negative', message: `Límite de ${this.limiteMensual.max_pedidos} pedido(s) mensual alcanzado.`, position: 'top' })
        return
      }
      const productoId = product.id
      const existing = this.selectedItems.find(i => i.producto_id === productoId)
      if (existing) {
        existing.cantidad = Number(existing.cantidad || 0) + 1
        this.recalculate(existing)
        return
      }

      const precioUnitario = Number(product.precio_unitario || 0)
      // mesnaje de agregado
      this.$alert.success(`${product.nombre}`, 'Producto agregado',)
      const item = {
        producto_id: productoId,
        imagen: product.imagen,
        nombre: product.nombre,
        unidad_medida: product.unidad_medida,
        cantidad: 1,
        precio_unitario: precioUnitario,
        subtotal: precioUnitario,
      }
      this.selectedItems.unshift(item)
    },
    recalculate (item) {
      const cantidad = Math.max(0, Number(item.cantidad || 0))
      item.cantidad = cantidad
      item.subtotal = cantidad * Number(item.precio_unitario || 0)
    },
    removeItem (item) {
      this.selectedItems = this.selectedItems.filter(i => i.producto_id !== item.producto_id)
    },
    clearAllItems () {
      this.selectedItems = []
    },
    confirmSave () {
      if (!this.pedidosPermitidos) {
        this.$q.notify({ color: 'negative', message: this.ventanaPedidoMensaje, position: 'top' })
        return
      }
      if (this.limiteMensual.bloqueado) {
        this.$q.notify({ color: 'negative', message: `Límite de ${this.limiteMensual.max_pedidos} pedido(s) mensual alcanzado.`, position: 'top' })
        return
      }
      if (!this.selectedItems.length) {
        this.$q.notify({ color: 'negative', message: 'AGREGA AL MENOS UN PRODUCTO', position: 'top' })
        return
      }
      this.confirmData = { comentario: this.comentario || '' }
      this.showConfirmDialog = true
    },
    async save () {
      if (!this.confirmData) return
      this.saving = true
      try {
        await this.$axios.post('pedidos', {
          comentario: this.comentario || null,
          items: this.selectedItems.map(item => ({
            producto_id: item.producto_id,
            cantidad: item.cantidad,
            precio_unitario: item.precio_unitario,
            subtotal: item.subtotal,
          })),
        })
        this.$q.notify({ color: 'positive', message: 'PEDIDO CREADO CORRECTAMENTE', position: 'top' })
        this.$router.push('/pedidos')
      } catch (e) {
        this.$q.notify({ color: 'negative', message: e.response?.data?.message || 'NO SE PUDO GUARDAR', position: 'top' })
      } finally {
        this.saving = false
        this.showConfirmDialog = false
      }
    },
    itemImageUrl (row) {
      return `${this.$url}/../images/productos/${row?.imagen || 'default.png'}`
    },
    money (value) {
      return Number(value || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    formatDateTime (datetime) {
      if (!datetime) return '-'
      return moment(datetime).format('DD/MM/YYYY HH:mm')
    },
    formatDate (date) {
      if (!date) return '-'
      return moment(date).format('DD/MM/YYYY')
    },
  },
}
</script>

<style scoped>
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 8px;
  max-height: calc(100vh - 230px);
  overflow: auto;
}

.product-card {
  position: relative;
  min-height: 170px;
  border: 1px solid #d8e0e8;
  border-radius: 6px;
  cursor: pointer;
  overflow: hidden;
  background: #fff;
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.product-card--disabled {
  cursor: not-allowed;
  opacity: 0.58;
}

.product-card--disabled:hover {
  transform: none;
  box-shadow: none;
}

.product-image-wrapper {
  position: relative;
  height: 120px;
  background: #f5f5f5;
  overflow: hidden;
}

.product-image {
  height: 120px;
  width: 100%;
  background: #f5f5f5;
}

.product-name-overlay {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 18px 6px 6px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.15;
  color: #fff;
  text-transform: capitalize;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.85) 0%,
    rgba(0, 0, 0, 0.6) 55%,
    rgba(0, 0, 0, 0) 100%
  );
  word-break: break-word;
  white-space: normal;
}

.product-info {
  padding: 6px 8px;
  display: flex;
  flex-direction: column;
  gap: 3px;
  justify-content: space-between;
}

.product-qty {
  font-size: 10px;
  color: #999;
  text-transform: capitalize;
}

.product-stock {
  font-size: 10px;
  color: #2e7d32;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 2px;
}

.product-price {
  font-size: 13px;
  font-weight: 700;
  color: #1976d2;
}

.table-container {
  overflow-x: auto;
  background: white;
  border-radius: 3px;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.items-table thead {
  background: #f5f5f5;
  border-bottom: 2px solid #ddd;
  position: sticky;
  top: 0;
}

.items-table th {
  padding: 6px 4px;
  text-align: left;
  font-weight: 600;
  color: #333;
  white-space: nowrap;
  font-size: 11px;
}

.items-table td {
  padding: 6px 4px;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
}

.items-table tbody tr:hover {
  background: #f9f9f9;
}

.producto-cell {
  max-width: 260px;
  font-weight: 500;
  padding: 4px 6px !important;
}

.producto-cell-content {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.producto-cell-img {
  flex: 0 0 auto;
  width: 60px;
  height: 60px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: #f5f5f5;
}

.producto-cell-nombre {
  flex: 1 1 auto;
  text-transform: capitalize;
  font-size: 12px;
  line-height: 1.2;
  white-space: normal;
  word-break: break-word;
}

.input-inline {
  width: 100%;
  padding: 2px 3px;
  font-size: 11px;
  border: 1px solid #ddd;
  border-radius: 2px;
  text-align: center;
  font-family: inherit;
}

.input-inline:focus {
  outline: none;
  border-color: #1976d2;
  background: #f0f8ff;
}

.bg-blue-1 {
  background: #e3f2fd;
  border-radius: 3px;
}

.confirm-dialog {
  width: 680px;
  max-width: 92vw;
  max-height: 92vh;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.confirm-content {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}

.confirm-actions {
  flex: 0 0 auto;
  background: #fff;
  position: sticky;
  bottom: 0;
  z-index: 2;
}

.confirm-header {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: #fff;
  padding: 16px 18px;
}

.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
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

.confirm-items {
  border: 1px solid #e5eaf2;
  border-radius: 8px;
  background: #fff;
  padding: 4px;
  max-height: 260px;
  overflow-y: auto;
}

.confirm-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 10px;
  border-bottom: 1px solid #f0f2f5;
}

.confirm-item:last-child {
  border-bottom: none;
}

.confirm-item-img {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  border-radius: 6px;
  border: 1px solid #e5eaf2;
  background: #f5f5f5;
}

.confirm-item-info {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.confirm-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  text-transform: capitalize;
  line-height: 1.2;
  word-break: break-word;
}

.confirm-item-meta {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.confirm-item-total {
  font-size: 14px;
  font-weight: 700;
  color: #1976d2;
  white-space: nowrap;
}

.confirm-summary {
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

.summary-comment {
  margin-top: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  background: #eef2f7;
  border: 1px solid #dbe4ef;
}

.summary-comment-label {
  font-size: 11px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  font-weight: 600;
}

.summary-comment-value {
  margin-top: 2px;
  font-size: 14px;
  color: #1f2937;
  word-break: break-word;
}
</style>
