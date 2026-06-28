<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold text-deep-orange-8">
          <q-icon name="warning" class="q-mr-xs" />Pedido de Emergencia
        </div>
        <div class="text-caption text-grey-7">Registrar pedido de almacén en nombre de otro usuario</div>
      </div>
      <q-space />
      <q-btn flat icon="arrow_back" label="Volver" to="/pedidos" no-caps />
    </div>

    <!-- Selector de usuario -->
    <q-card flat bordered class="q-mb-sm" style="border-left: 4px solid #f57c00;">
      <q-card-section class="q-pa-sm">
        <div class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="selectedUser"
              :options="userOptions"
              option-value="id"
              option-label="label"
              emit-value
              map-options
              dense
              outlined
              clearable
              use-input
              input-debounce="0"
              label="Usuario del pedido *"
              :loading="loadingUsers"
              @filter="filterUsers"
            >
              <template #prepend><q-icon name="person" color="deep-orange" /></template>
              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon name="person" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.username }} · {{ scope.opt.role }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey">Sin resultados</q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div v-if="selectedUserData" class="col-12 col-md-6">
            <q-banner dense rounded class="bg-orange-1 text-orange-10" style="border: 1px solid #ffcc80;">
              <template #avatar><q-icon name="info" color="orange-7" /></template>
              El pedido se registrará a nombre de
              <strong>{{ selectedUserData.name }}</strong>
              <span v-if="selectedUserData.unidad"> · {{ selectedUserData.unidad.nombre }}</span>
            </q-banner>
          </div>
        </div>
      </q-card-section>
    </q-card>

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
              :class="{ 'product-card--disabled': !selectedUser }"
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

          <!-- Toolbar de la tabla -->
          <div class="row items-center q-px-sm q-pt-sm">
            <div class="text-caption text-grey-7">
              {{ selectedItems.length }} Producto{{ selectedItems.length === 1 ? '' : 's' }} en el pedido
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
                      class="input-inline"
                      @change="recalculate(item)"
                    />
                  </td>
                  <td class="text-right">{{ money(item.precio_unitario) }}</td>
                  <td class="text-right text-weight-bold">{{ money(item.subtotal) }}</td>
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
          <q-card-section class="row items-center q-pa-sm bg-orange-1">
            <div class="text-subtitle2 text-weight-bold">
              TOTAL:
              <span class="text-deep-orange text-h6">{{ money(total) }} Bs</span>
            </div>
            <q-space />
            <q-btn
              color="deep-orange"
              icon="warning"
              label="Confirmar pedido de emergencia"
              :loading="saving"
              no-caps
              :disable="selectedItems.length === 0 || !selectedUser"
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
            <q-icon name="warning" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">CONFIRMAR PEDIDO DE EMERGENCIA</div>
              <div class="text-caption text-orange-3">
                PEDIDO A NOMBRE DE: {{ selectedUserData?.name?.toUpperCase() }}
              </div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="showConfirmDialog = false" />
          </div>
        </div>

        <div class="confirm-content">
          <q-card-section class="q-pa-md">
            <div class="row items-center q-mb-sm">
              <q-icon name="inventory_2" size="18px" color="deep-orange" class="q-mr-xs" />
              <div class="text-subtitle2 text-weight-bold">PRODUCTOS</div>
              <q-space />
              <q-chip dense color="deep-orange" text-color="white" :label="`${selectedItems.length} ITEMS`" />
            </div>

            <div class="confirm-items">
              <div v-for="(item, idx) in selectedItems" :key="idx" class="confirm-item">
                <q-img :src="itemImageUrl(item)" class="confirm-item-img" fit="cover" no-spinner />
                <div class="confirm-item-info">
                  <div class="confirm-item-name">{{ item.nombre }}</div>
                  <div class="confirm-item-meta">{{ item.cantidad }} × {{ money(item.precio_unitario) }} Bs</div>
                </div>
                <div class="confirm-item-total">{{ money(item.subtotal) }} Bs</div>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-md confirm-summary">
            <div class="summary-row">
              <span class="summary-label">USUARIO</span>
              <span class="summary-value">{{ selectedUserData?.name }}</span>
            </div>
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
          <q-btn label="Registrar emergencia" unelevated color="deep-orange" icon="warning" :loading="saving" @click="save" no-caps />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'PedidosEmergenciaPage',
  data () {
    return {
      loadingProducts: false,
      loadingUsers: false,
      saving: false,
      products: [],
      allUsers: [],
      userOptions: [],
      selectedUser: null,
      selectedItems: [],
      comentario: '',
      productFilter: '',
      productPagination: { page: 1, rowsPerPage: 30, rowsNumber: 0 },
      showConfirmDialog: false,
      confirmData: null,
    }
  },
  computed: {
    selectedUserData () {
      if (!this.selectedUser) return null
      return this.allUsers.find(u => u.id === this.selectedUser) || null
    },
    canVerStock () {
      const perms = this.$store?.permissions || []
      const role  = this.$store?.user?.role || ''
      return role === 'Administrador' || perms.includes('Ver todos los pedidos')
    },
    total () {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.subtotal || 0), 0)
    },
    productPages () {
      return Math.max(1, Math.ceil(this.productPagination.rowsNumber / this.productPagination.rowsPerPage))
    },
  },
  async mounted () {
    await Promise.all([this.fetchUsers(), this.fetchProducts()])
  },
  methods: {
    async fetchUsers () {
      this.loadingUsers = true
      try {
        const res = await this.$axios.get('users')
        this.allUsers = res.data || []
        this.userOptions = this.buildUserOptions(this.allUsers)
      } finally {
        this.loadingUsers = false
      }
    },
    buildUserOptions (users) {
      return users.map(u => ({
        id: u.id,
        label: `${u.name} (${u.username || u.role || ''})`,
        name: u.name,
        username: u.username,
        role: u.role,
        unidad: u.unidad,
      }))
    },
    filterUsers (val, update) {
      if (!val) {
        update(() => { this.userOptions = this.buildUserOptions(this.allUsers) })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.userOptions = this.buildUserOptions(
          this.allUsers.filter(u =>
            (u.name || '').toLowerCase().includes(needle) ||
            (u.username || '').toLowerCase().includes(needle)
          )
        )
      })
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
      if (!this.selectedUser) {
        this.$q.notify({ color: 'warning', message: 'SELECCIONA UN USUARIO PRIMERO', position: 'top' })
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
      this.$alert.success(`${product.nombre}`, 'Producto agregado')
      this.selectedItems.unshift({
        producto_id: productoId,
        imagen: product.imagen,
        nombre: product.nombre,
        unidad_medida: product.unidad_medida,
        cantidad: 1,
        precio_unitario: precioUnitario,
        subtotal: precioUnitario,
      })
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
      if (!this.selectedUser) {
        this.$q.notify({ color: 'negative', message: 'SELECCIONA UN USUARIO', position: 'top' })
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
          user_id: this.selectedUser,
          comentario: this.comentario || null,
          items: this.selectedItems.map(item => ({
            producto_id: item.producto_id,
            cantidad: item.cantidad,
            precio_unitario: item.precio_unitario,
            subtotal: item.subtotal,
          })),
        })
        this.$q.notify({ color: 'positive', message: 'PEDIDO DE EMERGENCIA CREADO CORRECTAMENTE', position: 'top' })
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
  max-height: calc(100vh - 280px);
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
  background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 55%, rgba(0,0,0,0) 100%);
  word-break: break-word;
  white-space: normal;
}

.product-info {
  padding: 6px 8px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.product-qty { font-size: 10px; color: #999; text-transform: capitalize; }

.product-stock {
  font-size: 10px;
  color: #2e7d32;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 2px;
}

.product-price { font-size: 13px; font-weight: 700; color: #e65100; }

.table-container { overflow-x: auto; background: white; border-radius: 3px; }

.items-table { width: 100%; border-collapse: collapse; font-size: 12px; }

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

.items-table tbody tr:hover { background: #f9f9f9; }

.producto-cell { max-width: 260px; font-weight: 500; padding: 4px 6px !important; }

.producto-cell-content { display: flex; align-items: center; gap: 8px; min-width: 0; }

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
  border-color: #e65100;
  background: #fff8f5;
}

.bg-orange-1 { background: #fff3e0; border-radius: 3px; }

.confirm-dialog {
  width: 680px;
  max-width: 92vw;
  max-height: 92vh;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.confirm-content { flex: 1 1 auto; min-height: 0; overflow-y: auto; }

.confirm-actions {
  flex: 0 0 auto;
  background: #fff;
  position: sticky;
  bottom: 0;
  z-index: 2;
}

.confirm-header {
  background: linear-gradient(135deg, #e65100 0%, #bf360c 100%);
  color: #fff;
  padding: 16px 18px;
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

.confirm-item:last-child { border-bottom: none; }

.confirm-item-img {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  border-radius: 6px;
  border: 1px solid #e5eaf2;
  background: #f5f5f5;
}

.confirm-item-info { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; }

.confirm-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  text-transform: capitalize;
  line-height: 1.2;
  word-break: break-word;
}

.confirm-item-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }

.confirm-item-total { font-size: 14px; font-weight: 700; color: #e65100; white-space: nowrap; }

.confirm-summary { background: #f7f9fc; }

.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; }

.summary-label { font-size: 13px; color: #4b5563; }
.summary-value { font-size: 14px; font-weight: 600; color: #1f2937; }

.total-row { padding: 6px 12px; background: #fff3e0; border-radius: 8px; }

.total-label { font-size: 15px; font-weight: 700; color: #bf360c; }
.total-value { font-size: 22px; font-weight: 800; color: #e65100; }

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

.summary-comment-value { margin-top: 2px; font-size: 14px; color: #1f2937; word-break: break-word; }
</style>
