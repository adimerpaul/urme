<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">{{ isEditMode ? `Editar solicitud SAP #${editId}` : 'Nueva solicitud SAP' }}</div>
        <div class="text-caption text-grey-7">Solicitud de contratación de servicios generales</div>
      </div>
      <q-space />
      <q-btn flat icon="arrow_back" label="Volver" no-caps to="/solicitudes-sap" />
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
          <!-- Fecha y N° CITE -->
          <q-card-section class="q-pa-xs">
            <div class="row q-col-gutter-xs">
              <div class="col-12 col-sm-4">
                <q-input v-model="form.fecha" dense outlined type="date" label="Fecha *" />
              </div>
              <div class="col-12 col-sm-4">
                <q-input v-model="form.nro_cite" dense outlined label="N° CITE Solicitud" />
              </div>
              <div class="col-12 col-sm-4">
                <q-select v-model="form.estado" dense outlined emit-value map-options label="Estado" :options="estadoOptions" />
              </div>
            </div>
          </q-card-section>

          <!-- Unidad solicitante (automática) y Apertura programática -->
          <q-card-section class="q-pa-xs">
            <div class="row q-col-gutter-xs items-center">
              <div class="col-12 col-sm-6">
                <div class="unidad-display">
                  <q-icon name="business" color="primary" size="18px" class="q-mr-xs" />
                  <div>
                    <div class="text-caption text-grey-7" style="line-height:1">Unidad solicitante</div>
                    <div class="text-weight-bold text-body2">{{ unidadNombre }}</div>
                  </div>
                </div>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="form.apertura_programatica" dense outlined label="Apertura programática / SISÍN" />
              </div>
            </div>
          </q-card-section>

          <!-- Justificación -->
          <q-card-section class="q-pa-xs">
            <q-input
              v-model="form.justificacion"
              dense
              outlined
              type="textarea"
              autogrow
              label="Justificación del requerimiento (opcional)"
            >
              <template #prepend><q-icon name="assignment" /></template>
            </q-input>
          </q-card-section>

          <!-- Observaciones -->
          <q-card-section class="q-pa-xs">
            <q-input
              v-model="form.observaciones"
              dense
              outlined
              type="textarea"
              autogrow
              label="Observaciones (opcional)"
            >
              <template #prepend><q-icon name="comment" /></template>
            </q-input>
          </q-card-section>

          <q-separator />

          <!-- Toolbar tabla -->
          <div class="row items-center q-px-sm q-pt-sm">
            <div class="text-caption text-grey-7">
              {{ selectedItems.length }} producto{{ selectedItems.length === 1 ? '' : 's' }} en la solicitud
            </div>
            <q-space />
            <q-btn
              flat dense no-caps size="sm" color="negative" icon="delete_sweep" label="Borrar todos"
              :disable="selectedItems.length === 0"
              @click="clearAllItems"
            />
          </div>

          <!-- Tabla de Productos -->
          <div class="table-container q-pa-sm">
            <table class="items-table">
              <thead>
                <tr>
                  <th style="width:30%">Producto</th>
                  <th style="width:10%">Partida</th>
                  <th style="width:8%">Unidad</th>
                  <th style="width:8%">Cant.</th>
                  <th style="width:10%">P. Ref. Unit.</th>
                  <th style="width:10%">Total</th>
                  <th style="width:6%">Act.</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedItems" :key="item.key">
                  <td class="producto-cell" :title="item.descripcion">
                    <div class="producto-cell-content">
                      <q-img :src="itemImageUrl(item)" class="producto-cell-img" fit="cover" no-spinner />
                      <span class="producto-cell-nombre">{{ item.descripcion }}</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span
                      class="partida-badge"
                      :title="item.part_presup_nombre ? `${item.part_presup} — ${item.part_presup_nombre}` : item.part_presup"
                    >{{ item.part_presup || '—' }}</span>
                  </td>
                  <td><input v-model="item.unidad" type="text" class="input-inline" /></td>
                  <td><input v-model.number="item.cantidad" type="number" min="0" step="any" class="input-inline" @input="recalc(item)" /></td>
                  <td><input v-model.number="item.precio_unitario" type="number" step="0.01" class="input-inline" @input="recalc(item)" /></td>
                  <td class="text-right text-weight-bold" style="padding-right:4px;">{{ money(item.total) }}</td>
                  <td class="text-center"><q-btn flat dense round icon="delete" color="negative" size="sm" @click="removeItem(item)" /></td>
                </tr>
                <tr v-if="selectedItems.length === 0">
                  <td colspan="7" class="text-center text-grey-7 q-pa-md">Seleccione productos del panel izquierdo</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Total y Botón -->
          <q-card-section class="row items-center q-pa-sm bg-blue-1">
            <div class="text-subtitle2 text-weight-bold">
              Total: <span class="text-primary text-h6">{{ money(total) }} Bs</span>
            </div>
            <q-space />
            <q-btn
              color="primary"
              :icon="isEditMode ? 'save' : 'send'"
              :label="isEditMode ? 'Guardar cambios' : 'Registrar solicitud'"
              no-caps
              :loading="saving"
              :disable="selectedItems.length === 0 || !form.fecha"
              @click="save"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'SolicitudSapNuevaPage',
  props: {
    id: { type: [String, Number], default: null },
  },
  data () {
    return {
      saving: false,
      products: [],
      productFilter: '',
      productPagination: { page: 1, rowsPerPage: 30, rowsNumber: 0 },
      selectedItems: [],
      form: {
        fecha: moment().format('YYYY-MM-DD'),
        nro_cite: '',
        estado: 'PENDIENTE',
        apertura_programatica: '',
        justificacion: '',
        observaciones: '',
      },
      estadoOptions: [
        { label: 'Pendiente', value: 'PENDIENTE' },
        { label: 'Aprobado', value: 'APROBADO' },
        { label: 'Rechazado', value: 'RECHAZADO' },
        { label: 'Anulado', value: 'ANULADO' },
      ],
    }
  },
  computed: {
    unidadNombre () {
      return this.$store.user?.unidad?.nombre || this.$store.user?.name || 'Sin unidad asignada'
    },
    total () {
      return this.selectedItems.reduce((sum, i) => sum + Number(i.total || 0), 0)
    },
    productPages () {
      return Math.max(1, Math.ceil(this.productPagination.rowsNumber / this.productPagination.rowsPerPage))
    },
    isEditMode () {
      return !!this.editId
    },
    editId () {
      return this.id || this.$route.params.id || null
    },
  },
  async mounted () {
    await this.fetchProducts()
    if (this.editId) {
      await this.loadSolicitud(this.editId)
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
    addItem (product) {
      const partida = product.subpartida?.partida
      const item = {
        key: Date.now() + Math.random(),
        almacen_item_id: product.id,
        imagen: product.imagen,
        descripcion: product.nombre,
        unidad: product.unidad_medida || '',
        part_presup: partida?.codigo || '',
        part_presup_nombre: partida?.nombre || '',
        cantidad: 1,
        precio_unitario: Number(product.precio_unitario || 0),
        total: Number(product.precio_unitario || 0),
      }
      this.selectedItems.push(item)
    },
    recalc (item) {
      item.total = Math.round(Number(item.cantidad || 0) * Number(item.precio_unitario || 0) * 100) / 100
    },
    removeItem (item) {
      this.selectedItems = this.selectedItems.filter(i => i.key !== item.key)
    },
    clearAllItems () {
      if (this.selectedItems.length === 0) return
      this.$q.dialog({
        title: 'Borrar todos los productos',
        message: `¿Quitar los ${this.selectedItems.length} productos de la solicitud?`,
        cancel: true,
        persistent: true,
        ok: { label: 'Borrar todos', color: 'negative', noCaps: true },
      }).onOk(() => { this.selectedItems = [] })
    },
    async save () {
      if (!this.form.fecha) { this.$alert.warning('La fecha es requerida'); return }
      if (this.selectedItems.length === 0) { this.$alert.warning('Debe agregar al menos un ítem'); return }

      this.saving = true
      try {
        const payload = {
          ...this.form,
          items: this.selectedItems.map(i => ({
            almacen_item_id: i.almacen_item_id || null,
            imagen: i.imagen || null,
            descripcion: i.descripcion,
            part_presup: i.part_presup || null,
            unidad: i.unidad || null,
            cantidad: i.cantidad,
            precio_unitario: i.precio_unitario,
          })),
        }

        let res
        if (this.isEditMode) {
          res = await this.$axios.put(`solicitudes-sap/${this.editId}`, payload)
          this.$alert.success('Solicitud actualizada correctamente')
        } else {
          res = await this.$axios.post('solicitudes-sap', payload)
          this.$alert.success(`Solicitud ${res.data.nro} registrada`)
        }

        // Preguntar si imprime
        const sapId = this.isEditMode ? this.editId : res.data.id
        this.$q.dialog({
          title: 'Solicitud guardada',
          message: '¿Desea imprimir la solicitud ahora?',
          cancel: { label: 'No, volver', flat: true, noCaps: true },
          ok: { label: 'Imprimir', color: 'primary', noCaps: true, icon: 'print' },
        }).onOk(async () => {
          try {
            const pdf = await this.$axios.get(`solicitudes-sap/${sapId}/pdf`, { responseType: 'blob' })
            const blob = new Blob([pdf.data], { type: 'application/pdf' })
            const blobUrl = window.URL.createObjectURL(blob)
            window.open(blobUrl, '_blank')
            window.setTimeout(() => window.URL.revokeObjectURL(blobUrl), 60000)
          } catch { /* silencioso */ }
          this.$router.push('/solicitudes-sap')
        }).onCancel(() => {
          this.$router.push('/solicitudes-sap')
        })
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar la solicitud')
      } finally {
        this.saving = false
      }
    },
    async loadSolicitud (id) {
      try {
        const res = await this.$axios.get(`solicitudes-sap/${id}`)
        const s = res.data
        this.form.fecha                = s.fecha ? moment(s.fecha).format('YYYY-MM-DD') : moment().format('YYYY-MM-DD')
        this.form.nro_cite             = s.nro_cite || ''
        this.form.estado               = s.estado || 'PENDIENTE'
        this.form.apertura_programatica= s.apertura_programatica || ''
        this.form.justificacion        = s.justificacion || ''
        this.form.observaciones        = s.observaciones || ''
        this.selectedItems = (s.detalles || []).map(d => ({
          key: d.id,
          almacen_item_id: d.almacen_item_id || null,
          imagen: d.imagen || null,
          descripcion: d.descripcion,
          part_presup: d.part_presup || '',
          part_presup_nombre: '',
          unidad: d.unidad || '',
          cantidad: Number(d.cantidad || 0),
          precio_unitario: Number(d.precio_unitario || 0),
          total: Number(d.total || 0),
        }))
      } catch (e) {
        this.$alert.error('No se pudo cargar la solicitud')
        this.$router.push('/solicitudes-sap')
      }
    },
    itemImageUrl (row) {
      return `${this.$url}/../images/productos/${row?.imagen || 'default.png'}`
    },
    money (val) {
      return Number(val || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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
  left: 0; right: 0; bottom: 0;
  padding: 18px 6px 6px;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.15;
  color: #fff;
  text-transform: capitalize;
  text-shadow: 0 1px 2px rgba(0,0,0,.6);
  background: linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.6) 55%, rgba(0,0,0,0) 100%);
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
.product-price { font-size: 13px; font-weight: 700; color: #1976d2; }

.table-container { overflow-x: auto; background: white; border-radius: 3px; }

.items-table { width: 100%; border-collapse: collapse; font-size: 12px; }

.items-table thead { background: #f5f5f5; border-bottom: 2px solid #ddd; position: sticky; top: 0; }

.items-table th {
  padding: 6px 4px;
  text-align: left;
  font-weight: 600;
  color: #333;
  white-space: nowrap;
  font-size: 11px;
}

.items-table td { padding: 4px 2px; border-bottom: 1px solid #eee; vertical-align: middle; }
.items-table tbody tr:hover { background: #f9f9f9; }

.producto-cell { max-width: 220px; font-weight: 500; padding: 4px 6px !important; }
.producto-cell-content { display: flex; align-items: center; gap: 8px; min-width: 0; }

.producto-cell-img {
  flex: 0 0 auto;
  width: 40px; height: 40px;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
  background: #f5f5f5;
}

.producto-cell-nombre {
  flex: 1 1 auto;
  text-transform: capitalize;
  font-size: 11px;
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

.input-inline:focus { outline: none; border-color: #1976d2; background: #f0f8ff; }
.input-inline[type="text"] { text-align: left; }

.bg-blue-1 { background: #e3f2fd; border-radius: 3px; }

.unidad-display {
  display: flex;
  align-items: center;
  padding: 6px 10px;
  background: #e8f1fb;
  border: 1px solid #b3cef0;
  border-radius: 4px;
  min-height: 40px;
}

.partida-badge {
  display: inline-block;
  padding: 2px 6px;
  background: #e8f1fb;
  color: #0f5ea8;
  font-size: 11px;
  font-weight: 700;
  border-radius: 3px;
  border: 1px solid #b3cef0;
  cursor: default;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
</style>
