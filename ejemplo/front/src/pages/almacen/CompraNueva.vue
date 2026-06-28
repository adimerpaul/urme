<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">{{ isEditMode ? `Editar compra #${editId}` : 'Compras nuevas' }}</div>
        <div class="text-caption text-grey-7">{{ isEditMode ? 'Modificación de una entrada de almacén' : 'Registro de entradas de almacén' }}</div>
      </div>
      <q-space />
      <q-btn
        v-if="isEditMode"
        flat
        icon="print"
        label="Imprimir"
        no-caps
        color="teal"
        :loading="printing"
        @click="imprimir"
      />
      <q-btn
        color="primary"
        :icon="isEditMode ? 'save' : 'add_circle'"
        :label="isEditMode ? 'Guardar cambios' : 'Registrar compra'"
        no-caps
        :loading="saving"
        :disable="selectedItems.length === 0"
        @click="confirmSave"
      />
      <q-btn flat icon="arrow_back" label="Volver" no-caps to="/almacen/compras" />
    </div>

    <div class="row q-col-gutter-sm">
      <!-- Carrusel de Productos -->
      <div class="col-12 col-md-5">
        <q-card flat bordered>
          <q-card-section class="q-pa-sm">
            <q-input v-model="productFilter" dense outlined clearable debounce="350" label="Buscar producto" @update:model-value="fetchProducts">
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </q-card-section>
          <q-separator />
          <q-card-section class="product-grid q-pa-sm">
            <div v-for="item in products" :key="item.id" class="product-card" @click="addItem(item)">
              <div class="product-image-wrapper">
                <q-img :src="itemImageUrl(item)" class="product-image" fit="cover" />
                <div class="product-name-overlay" :title="item.nombre">{{ item.nombre }}</div>
              </div>
              <div class="product-info">
                <div class="product-qty">{{ item.unidad_medida || '-' }}</div>
                <div class="product-price">{{ money(item.precio_unitario) }} Bs</div>
              </div>
            </div>
          </q-card-section>
          <q-card-section class="row justify-center q-pa-xs">
            <q-pagination v-model="productPagination.page" :max="productPages" max-pages="6" size="sm" @update:model-value="fetchProducts" />
          </q-card-section>
        </q-card>
      </div>

  <!-- Formulario y Tabla -->
      <div class="col-12 col-md-7">
        <q-card flat bordered>
          <!-- Header: Motivo, Fecha, Pago -->
          <q-card-section class="q-pa-xs">
            <div class="row q-col-gutter-xs">
              <div class="col-12 col-sm-4">
                <q-select v-model="form.motivo_registro" dense outlined emit-value map-options label="Motivo" :options="motivoOptions" />
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.fecha_hora" dense outlined type="datetime-local" label="Fecha y hora" />
              </div>
              <div class="col-12 col-sm-4">
                <q-select v-model="form.tipo_pago" dense outlined emit-value map-options label="Pago" :options="pagoOptions" />
              </div>
            </div>
          </q-card-section>

          <!-- Proveedor y Factura -->
          <q-card-section class="q-pa-xs">
            <div class="row q-col-gutter-xs items-end">
              <div class="col-12 col-sm-8">
                <q-select
                  v-model="form.proveedor_id"
                  dense outlined
                  emit-value
                  map-options
                  use-input
                  clearable
                  label="Proveedor"
                  :options="proveedorOptions"
                  @update:model-value="onProveedorChange"
                >
                  <template #append>
                    <q-btn flat dense icon="add" color="primary" size="sm" @click="showProveedorDialog = true" title="Agregar proveedor" />
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.nro_factura" dense outlined label="Nro factura" />
              </div>
            </div>
          </q-card-section>

          <!-- Campos documento -->
          <q-card-section class="q-pa-xs">
            <div class="row q-col-gutter-xs">
              <div class="col-12 col-sm-4">
                <q-input v-model="form.numero" dense outlined clearable label="Número (impresión)" />
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.categoria_programatica" dense outlined label="Categoría programática" />
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.orden_de_compra" dense outlined label="Orden de compra" />
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.codigo_interno" dense outlined label="Código interno" />
              </div>
              <div class="col-12">
                <q-input v-model="form.hoja_de_ruta" dense outlined type="textarea" autogrow label="Hoja de ruta (opcional)" />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.unidad_id"
                  dense outlined
                  emit-value
                  map-options
                  clearable
                  use-input
                  input-debounce="0"
                  label="Unidad (opcional)"
                  :options="unidadOptionsFiltradas"
                  @filter="filtrarUnidades"
                />
              </div>
            </div>
          </q-card-section>

          <!-- Retención -->
          <q-card-section class="q-pa-xs">
            <div class="row items-center q-col-gutter-xs">
              <div class="col-auto">
                <q-checkbox v-model="form.retencion_activa" label="Retención" dense />
              </div>
              <div v-if="form.retencion_activa" class="col-3">
                <q-input
                  v-model.number="form.retencion_porcentaje"
                  dense outlined type="number" min="0" max="100" step="0.01"
                  label="%" suffix="%"
                />
              </div>
            </div>
          </q-card-section>

          <!-- Comentario -->
          <q-card-section class="q-pa-xs">
            <q-input
              v-model="form.comentario"
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
              {{ selectedItems.length }} producto{{ selectedItems.length === 1 ? '' : 's' }} en la compra
            </div>
            <q-space />
            <q-btn
              flat
              dense
              no-caps
              size="sm"
              color="negative"
              icon="delete_sweep"
              label="Borrar todos"
              :disable="selectedItems.length === 0"
              @click="clearAllItems"
            />
          </div>

          <!-- Tabla de Productos -->
          <div class="table-container q-pa-sm">
            <table class="items-table">
              <thead>
                <tr>
                  <th style="width: 32%">Producto</th>
                  <th style="width: 8%">Cant.</th>
                  <th style="width: 8%">P. Unit.</th>
                  <th style="width: 10%">Total</th>
                  <th style="width: 10%">Lote</th>
                  <th style="width: 12%">Vencimiento</th>
                  <th style="width: 6%">Días</th>
                  <th style="width: 6%">Act.</th>
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
                  <td><input v-model.number="item.cantidad" type="number" min="0" step="any" class="input-inline" @keyup="recalculate(item)" /></td>
                  <td><input v-model.number="item.precio" type="number" step="0.01" class="input-inline" @keyup="recalculate(item)" /></td>
                  <td><input v-model.number="item.total" type="number" step="0.01" class="input-inline" @keyup="recalculatePrice(item)" /></td>
                  <td><input v-model="item.lote" type="text" class="input-inline" /></td>
                  <td><input v-model="item.fecha_vencimiento" type="date" class="input-inline" @change="updateDaysLeft(item)" /></td>
                  <td class="dias-cell" :class="getDaysClass(item.dias_restantes)">{{ item.dias_restantes ?? '-' }}</td>
                  <td class="text-center"><q-btn flat dense round icon="delete" color="negative" size="sm" @click="removeItem(item)" /></td>
                </tr>
                <tr v-if="selectedItems.length === 0">
                  <td colspan="8" class="text-center text-grey-7">0 de 0 productos</td>
                </tr>
                <tr v-else>
                  <td colspan="8" class="text-center text-grey-7">0 de {{ selectedItems.length }} productos</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Total y Botón -->
          <q-card-section class="row items-center q-pa-sm bg-blue-1">
            <div>
              <div class="text-subtitle2 text-weight-bold">Total: <span class="text-primary text-h6">{{ money(total) }} Bs</span></div>
              <div v-if="form.retencion_activa && form.retencion_porcentaje > 0" class="text-caption text-grey-8">
                Retención {{ form.retencion_porcentaje }}%: <span class="text-negative">-{{ money(retencionMonto) }} Bs</span>
                &nbsp;|&nbsp; Neto: <span class="text-weight-bold">{{ money(totalNeto) }} Bs</span>
              </div>
            </div>
            <q-space />
            <q-btn color="primary" :icon="isEditMode ? 'save' : 'add_circle'" :label="isEditMode ? 'Guardar cambios' : 'Registrar compra'" no-caps :loading="saving" :disable="selectedItems.length === 0" @click="confirmSave" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Diálogo: Agregar Proveedor -->
    <q-dialog v-model="showProveedorDialog" @hide="newProveedor = {}">
      <q-card style="width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Agregar nuevo proveedor</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="showProveedorDialog = false" />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input v-model="newProveedor.nombre" outlined label="Nombre *" />
          <q-input v-model="newProveedor.carnet" outlined label="Carnet / NIT" />
          <q-input v-model="newProveedor.razon_social" outlined label="Razón social" />
          <q-input v-model="newProveedor.telefono" outlined label="Teléfono" />
          <q-input v-model="newProveedor.email" outlined label="Email" type="email" />
        </q-card-section>

        <q-separator />

        <q-card-section class="row q-gutter-sm">
          <q-space />
          <q-btn label="Cancelar" flat no-caps color="grey-8" @click="showProveedorDialog = false" />
          <q-btn label="Crear proveedor" no-caps unelevated color="primary" :loading="savingProveedor" @click="saveProveedor" />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Confirmación -->
    <q-dialog v-model="showConfirmDialog" @hide="confirmData = null">
      <q-card class="confirm-dialog">
        <!-- Encabezado -->
        <div class="confirm-header">
          <div class="row items-center no-wrap">
            <q-icon name="receipt_long" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">{{ isEditMode ? 'Confirmar cambios de la compra' : 'Confirmar registro de compra' }}</div>
              <div class="text-caption text-grey-3">Revisá los datos antes de guardar</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="showConfirmDialog = false" />
          </div>
        </div>

        <div class="confirm-content">
          <!-- Metadatos -->
          <q-card-section class="q-pa-md">
            <div class="meta-grid">
              <div class="meta-item">
                <q-icon name="event" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Fecha y hora</div>
                  <div class="meta-value">{{ formatDateTime(confirmData?.fecha_hora) }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="local_shipping" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Proveedor</div>
                  <div class="meta-value">{{ confirmData?.proveedor_nombre || 'Sin proveedor' }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="assignment" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Motivo</div>
                  <div class="meta-value">{{ confirmData?.motivo_registro || '-' }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="payments" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Tipo de pago</div>
                  <div class="meta-value">{{ pagoLabel(confirmData?.tipo_pago) }}</div>
                </div>
              </div>
              <div v-if="confirmData?.unidad_nombre" class="meta-item">
                <q-icon name="business" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Unidad</div>
                  <div class="meta-value">{{ confirmData.unidad_nombre }}</div>
                </div>
              </div>
              <div v-if="confirmData?.categoria_programatica" class="meta-item">
                <q-icon name="category" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Cat. programática</div>
                  <div class="meta-value">{{ confirmData.categoria_programatica }}</div>
                </div>
              </div>
              <div v-if="confirmData?.orden_de_compra" class="meta-item">
                <q-icon name="description" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Orden de compra</div>
                  <div class="meta-value">{{ confirmData.orden_de_compra }}</div>
                </div>
              </div>
              <div v-if="confirmData?.codigo_interno" class="meta-item">
                <q-icon name="tag" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Código interno</div>
                  <div class="meta-value">{{ confirmData.codigo_interno }}</div>
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
              <q-chip dense color="primary" text-color="white" :label="`${selectedItems.length} ${selectedItems.length === 1 ? 'item' : 'items'}`" />
            </div>
            <div class="confirm-items">
              <div v-for="(item, idx) in selectedItems" :key="idx" class="confirm-item">
                <q-img
                  :src="itemImageUrl(item)"
                  class="confirm-item-img"
                  fit="cover"
                  no-spinner
                />
                <div class="confirm-item-info">
                  <div class="confirm-item-name">{{ item.nombre }}</div>
                  <div class="confirm-item-meta">
                    {{ item.cantidad }} × {{ money(item.precio) }} Bs
                  </div>
                </div>
                <div class="confirm-item-total">{{ money(item.total) }} Bs</div>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <!-- Resumen -->
          <q-card-section class="q-pa-md confirm-summary">
            <div class="summary-row">
              <span class="summary-label">Subtotal</span>
              <span class="summary-value">{{ money(total) }} Bs</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Items</span>
              <span class="summary-value">{{ selectedItems.length }}</span>
            </div>
            <template v-if="form.retencion_activa && form.retencion_porcentaje > 0">
              <div class="summary-row">
                <span class="summary-label">Retención {{ form.retencion_porcentaje }}%</span>
                <span class="summary-value text-negative">-{{ money(retencionMonto) }} Bs</span>
              </div>
            </template>
            <q-separator class="q-my-sm" />
            <div class="summary-row total-row">
              <span class="total-label">{{ form.retencion_activa && form.retencion_porcentaje > 0 ? 'Neto a pagar' : 'Total a registrar' }}</span>
              <span class="total-value">{{ money(form.retencion_activa && form.retencion_porcentaje > 0 ? totalNeto : total) }} Bs</span>
            </div>
            <div v-if="confirmData?.comentario" class="summary-comment">
              <div class="summary-comment-label">Comentario</div>
              <div class="summary-comment-value">{{ confirmData.comentario }}</div>
            </div>
          </q-card-section>
        </div>

        <q-separator />

        <!-- Acciones -->
        <q-card-actions class="q-pa-md confirm-actions">
          <q-space />
          <q-btn label="Cancelar" flat no-caps color="grey-8" @click="showConfirmDialog = false" />
          <q-btn :label="isEditMode ? 'Guardar cambios' : 'Confirmar registro'" no-caps unelevated color="primary" icon="check_circle" :loading="saving" @click="save" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'CompraNuevaPage',
  props: {
    id: { type: [String, Number], default: null },
  },
  data () {
    return {
      loadingProducts: false,
      loadingCompra: false,
      saving: false,
      printing: false,
      savingProveedor: false,
      products: [],
      proveedores: [],
      proveedorOptions: [],
      unidades: [],
      unidadOptionsFiltradas: [],
      selectedItems: [],
      productFilter: '',
      productPagination: { page: 1, rowsPerPage: 30, rowsNumber: 0 },
      showProveedorDialog: false,
      showConfirmDialog: false,
      newProveedor: {},
      confirmData: null,
      form: {
        numero: '',
        proveedor_id: null,
        unidad_id: null,
        fecha_hora: moment().format('YYYY-MM-DDTHH:mm'),
        tipo_registro: 'ENTRADA',
        motivo_registro: 'COMPRA',
        tipo_pago: '',
        nro_factura: '',
        carnet: '',
        nombre: '',
        comentario: '',
        categoria_programatica: '',
        orden_de_compra: '',
        codigo_interno: '',
        hoja_de_ruta: '',
        retencion_activa: false,
        retencion_porcentaje: 7,
      },
      entradaMotivos: ['COMPRA', 'DONACION', 'TRANSFERENCIA', 'JUSTO','AJUSTE POSITIVO', 'AJUSTE NEGATIVO', 'OTRO'],
      pagoOptions: [
        { label: 'Ninguno', value: '' },
        { label: 'Efectivo', value: 'EFECTIVO' },
        { label: 'Crédito', value: 'CREDITO' },
        { label: 'Transferencia', value: 'TRANSFERENCIA' },
        { label: 'QR', value: 'QR' },
      ],
    }
  },
  computed: {
    total () {
      return this.selectedItems.reduce((sum, item) => sum + Number(item.total || 0), 0)
    },
    retencionMonto () {
      if (!this.form.retencion_activa || !this.form.retencion_porcentaje) return 0
      return Math.round(this.total * this.form.retencion_porcentaje / 100 * 100) / 100
    },
    totalNeto () {
      return Math.round((this.total - this.retencionMonto) * 100) / 100
    },
    productPages () {
      return Math.max(1, Math.ceil(this.productPagination.rowsNumber / this.productPagination.rowsPerPage))
    },
    motivoOptions () {
      return this.entradaMotivos.map(value => ({ label: value, value }))
    },
    isEditMode () {
      return !!this.editId
    },
    editId () {
      return this.id || this.$route.params.id || null
    },
    unidadOptions () {
      return this.unidades.map(u => ({ label: u.nombre, value: u.id }))
    },
  },
  async mounted () {
    await Promise.all([this.fetchProducts(), this.fetchProveedores(), this.fetchUnidades()])
    if (this.editId) {
      await this.loadCompra(this.editId)
    }
  },
  methods: {
    async fetchProducts () {
      const res = await this.$axios.get('almacen-items', {
        params: {
          page: this.productPagination.page,
          rowsPerPage: this.productPagination.rowsPerPage,
          q: this.productFilter,
        },
      })
      this.products = res.data.data || []
      this.productPagination.rowsNumber = res.data.total || 0
    },
    async fetchProveedores () {
      const res = await this.$axios.get('proveedores')
      this.proveedores = res.data || []
      this.updateProveedorOptions()
    },
    async fetchUnidades () {
      const res = await this.$axios.get('unidades')
      this.unidades = res.data?.data || res.data || []
      this.unidadOptionsFiltradas = this.unidadOptions
    },
    filtrarUnidades (val, update) {
      update(() => {
        if (!val) {
          this.unidadOptionsFiltradas = this.unidadOptions
        } else {
          const needle = val.toLowerCase()
          this.unidadOptionsFiltradas = this.unidadOptions.filter(o => o.label.toLowerCase().includes(needle))
        }
      })
    },
    updateProveedorOptions () {
      this.proveedorOptions = this.proveedores.map(p => ({ label: p.nombre, value: p.id }))
    },
    onProveedorChange () {
      const proveedor = this.proveedores.find(p => p.id === this.form.proveedor_id)
      if (proveedor) {
        this.form.carnet = proveedor?.carnet || proveedor?.nit || ''
        this.form.nombre = proveedor?.nombre || ''
      }
    },
    async saveProveedor () {
      if (!this.newProveedor.nombre?.trim()) {
        this.$alert.warning('El nombre es requerido')
        return
      }
      this.savingProveedor = true
      try {
        const res = await this.$axios.post('proveedores', this.newProveedor)
        this.proveedores.push(res.data)
        this.updateProveedorOptions()
        this.form.proveedor_id = res.data.id
        this.onProveedorChange()
        this.$alert.success('Proveedor creado')
        this.showProveedorDialog = false
        this.newProveedor = {}
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al crear proveedor')
      } finally {
        this.savingProveedor = false
      }
    },
    addItem (product) {
      // const current = this.selectedItems.find(item => item.producto_id === product.id)
      // if (current) {
      //   current.cantidad += 1
      //   this.recalculate(current)
      //   return
      // }
      const item = {
        producto_id: product.id,
        imagen: product.imagen,
        nombre: product.nombre,
        unidad_medida: product.unidad_medida,
        cantidad: 1,
        precio: Number(product.precio_unitario || 0),
        total: Number(product.precio_unitario || 0),
        factor: 1.25,
        precio_venta: Number(product.precio_unitario || 0) * 1.25,
        lote: '',
        fecha_vencimiento: '',
        dias_restantes: null,
      }
      this.selectedItems.push(item)
    },
    round2 (value) {
      return Math.round(Number(value || 0) * 100) / 100
    },
    recalculate (item) {
      item.total = this.round2(Number(item.cantidad || 0) * Number(item.precio || 0))
      item.precio_venta = this.round2(Number(item.precio || 0) * Number(item.factor || 1))
    },
    recalculatePrice (item) {
      const cantidad = Number(item.cantidad || 1)
      if (cantidad > 0) {
        item.precio = this.round2(Number(item.total || 0) / cantidad)
      }
    },
    updateDaysLeft (item) {
      if (!item.fecha_vencimiento) {
        item.dias_restantes = null
        return
      }
      const today = moment().startOf('day')
      const vencimiento = moment(item.fecha_vencimiento).startOf('day')
      item.dias_restantes = vencimiento.diff(today, 'days')
    },
    getDaysClass (dias) {
      if (dias === null) return ''
      if (dias < 0) return 'dias-vencido'
      if (dias === 0) return 'dias-hoy'
      if (dias <= 7) return 'dias-proximo'
      return 'dias-ok'
    },
    removeItem (item) {
      this.selectedItems = this.selectedItems.filter(row => row.producto_id !== item.producto_id)
    },
    clearAllItems () {
      if (this.selectedItems.length === 0) return
      this.$q.dialog({
        title: 'Borrar todos los productos',
        message: `¿Quitar los ${this.selectedItems.length} productos de la compra?`,
        cancel: true,
        persistent: true,
        ok: { label: 'Borrar todos', color: 'negative', noCaps: true },
      }).onOk(() => {
        this.selectedItems = []
      })
    },
    confirmSave () {
      const proveedor = this.proveedores.find(p => p.id === this.form.proveedor_id)
      const unidad = this.unidades.find(u => u.id === this.form.unidad_id)
      this.confirmData = {
        fecha_hora: this.form.fecha_hora,
        proveedor_nombre: proveedor?.nombre,
        unidad_nombre: unidad?.nombre || null,
        motivo_registro: this.form.motivo_registro,
        tipo_pago: this.form.tipo_pago,
        comentario: this.form.comentario || '',
        categoria_programatica: this.form.categoria_programatica || '',
        orden_de_compra: this.form.orden_de_compra || '',
        codigo_interno: this.form.codigo_interno || '',
      }
      this.showConfirmDialog = true
    },
    async save () {
      this.saving = true
      try {
        const payload = {
          ...this.form,
          fecha_hora: this.form.fecha_hora ? this.form.fecha_hora.replace('T', ' ') : null,
          retencion_porcentaje: this.form.retencion_activa ? (this.form.retencion_porcentaje || 0) : 0,
          items: this.selectedItems,
        }
        if (this.isEditMode) {
          await this.$axios.put(`compras/${this.editId}`, payload)
          this.$alert.success('Compra actualizada correctamente')
        } else {
          await this.$axios.post('compras', payload)
          this.$alert.success('Compra registrada correctamente')
        }
        this.$router.push('/almacen/compras')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
        this.showConfirmDialog = false
      }
    },
    async loadCompra (id) {
      this.loadingCompra = true
      try {
        const res = await this.$axios.get(`compras/${id}`)
        const compra = res.data

        // Bloquear edición si la compra tiene más de 1 mes
        if (compra.created_at && moment(compra.created_at).isBefore(moment().subtract(1, 'month'))) {
          this.$alert.error('No se puede modificar esta compra: tiene más de un mes de antigüedad.')
          this.$router.push('/almacen/compras')
          return
        }

        this.form.numero = compra.numero || ''
        this.form.proveedor_id = compra.proveedor_id || null
        this.form.unidad_id = compra.unidad_id || null
        this.form.fecha_hora = compra.fecha_hora ? moment(compra.fecha_hora).format('YYYY-MM-DDTHH:mm') : moment().format('YYYY-MM-DDTHH:mm')
        this.form.tipo_registro = compra.tipo_registro || 'ENTRADA'
        this.form.motivo_registro = compra.motivo_registro || 'COMPRA'
        this.form.tipo_pago = compra.tipo_pago || ''
        this.form.nro_factura = compra.nro_factura || ''
        this.form.carnet = compra.carnet || ''
        this.form.nombre = compra.nombre || ''
        this.form.comentario = compra.comentario || ''
        this.form.categoria_programatica = compra.categoria_programatica || ''
        this.form.orden_de_compra = compra.orden_de_compra || ''
        this.form.codigo_interno = compra.codigo_interno || ''
        this.form.hoja_de_ruta = compra.hoja_de_ruta || ''
        this.form.retencion_porcentaje = parseFloat(compra.retencion_porcentaje) || 7
        this.form.retencion_activa = parseFloat(compra.retencion_porcentaje) > 0
        this.onProveedorChange()
        this.selectedItems = (compra.detalles || []).map(d => ({
          producto_id: d.producto_id,
          imagen: d.producto?.imagen,
          nombre: d.nombre || d.producto?.nombre,
          unidad_medida: d.producto?.unidad_medida,
          cantidad: Number(d.cantidad || 0),
          precio: Number(d.precio || 0),
          total: Number(d.total || 0),
          factor: Number(d.factor || 1.25),
          precio_venta: Number(d.precio_venta || 0),
          lote: d.lote || '',
          fecha_vencimiento: d.fecha_vencimiento ? moment(d.fecha_vencimiento).format('YYYY-MM-DD') : '',
          dias_restantes: null,
        }))
        this.selectedItems.forEach(item => this.updateDaysLeft(item))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar la compra')
        this.$router.push('/almacen/compras')
      } finally {
        this.loadingCompra = false
      }
    },
    async imprimir () {
      this.printing = true
      try {
        const res = await this.$axios.get(`compras/${this.editId}/pdf`, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        window.open(URL.createObjectURL(blob), '_blank')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el PDF')
      } finally {
        this.printing = false
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
    pagoLabel (value) {
      if (!value) return 'Ninguno'
      const opt = this.pagoOptions.find(p => p.value === value)
      return opt ? opt.label : value
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

.product-price {
  font-size: 13px;
  font-weight: 700;
  color: #1976d2;
}

/* Tabla de productos */
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
  padding: 4px 2px;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
}

.items-table tbody tr:hover {
  background: #f9f9f9;
}

.producto-cell {
  max-width: 220px;
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

/* Columna Días */
.dias-cell {
  text-align: center;
  font-weight: 600;
  font-size: 11px;
  padding: 2px 4px;
  border-radius: 2px;
}

.dias-ok {
  color: #4caf50;
}

.dias-proximo {
  color: #ff9800;
  background: #fff3e0;
}

.dias-hoy {
  color: #f44336;
  background: #ffebee;
}

.dias-vencido {
  color: #c62828;
  background: #ffcdd2;
}

/* Inputs normales en tabla */
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

.input-inline[type="text"] {
  text-align: left;
}

.bg-blue-1 {
  background: #e3f2fd;
  border-radius: 3px;
}

/* Diálogo de confirmación */
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
