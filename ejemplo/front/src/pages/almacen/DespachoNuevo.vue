<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Nuevo despacho</div>
        <div class="text-caption text-grey-7">Genera la entrega de materiales a partir de un pedido existente</div>
      </div>
      <q-space />
      <q-btn flat no-caps color="grey-8" icon="arrow_back" label="Volver a despachos" to="/despachos" />
    </div>

    <!-- Lookup pedido -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-sm-4">
          <q-input
            v-model="pedidoIdInput"
            outlined
            dense
            label="N° de pedido"
            type="number"
            min="1"
            :disable="loadingLookup"
            @keyup.enter="lookupPedido"
          >
            <template #prepend><q-icon name="receipt_long" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn
            unelevated
            no-caps
            color="primary"
            icon="search"
            label="Buscar pedido"
            :loading="loadingLookup"
            @click="lookupPedido"
          />
        </div>
        <q-space />
        <div v-if="pedido" class="col-auto">
          <q-btn flat no-caps color="grey-8" icon="close" label="Limpiar" @click="resetForm" />
        </div>
      </q-card-section>

      <q-banner v-if="errorLookup" class="bg-red-1 text-red-9 q-ma-sm">
        <template #avatar><q-icon name="error" color="red-7" /></template>
        {{ errorLookup }}
      </q-banner>
    </q-card>

    <!-- Datos pedido + items -->
    <div v-if="pedido" class="row q-col-gutter-sm">
      <div class="col-12 col-md-4">
        <q-card flat bordered>
          <q-card-section class="bg-blue-1">
            <div class="text-caption text-blue-9 text-weight-bold">PEDIDO</div>
            <div class="text-h6 text-weight-bold text-blue-10">#{{ pedido.id }}</div>
            <div class="text-caption text-grey-8">{{ formatDateTime(pedido.fecha_hora) }}</div>
          </q-card-section>
          <q-separator />
          <q-list dense>
            <q-item>
              <q-item-section avatar><q-icon name="person" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Solicitante</q-item-label>
                <q-item-label class="text-weight-medium">{{ pedido.nombre_usuario || '-' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="apartment" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Unidad</q-item-label>
                <q-item-label class="text-weight-medium">{{ pedido.unidad ? pedido.unidad.nombre : '-' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="pedido.comentario">
              <q-item-section avatar><q-icon name="comment" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Comentario</q-item-label>
                <q-item-label>{{ pedido.comentario }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="payments" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Total pedido</q-item-label>
                <q-item-label class="text-weight-bold text-primary">{{ money(pedido.total) }} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <q-card flat bordered class="q-mt-sm">
          <q-card-section>
            <div class="text-subtitle2 text-weight-bold q-mb-sm">
              <q-icon name="local_shipping" class="q-mr-xs" />
              Datos del despacho
            </div>
            <q-input
              v-model="form.fecha_entrega"
              outlined
              dense
              type="datetime-local"
              label="Fecha de entrega"
              class="q-mb-sm"
            >
              <template #prepend><q-icon name="event" /></template>
            </q-input>
            <q-input
              v-model="form.personal_recepcion"
              outlined
              dense
              readonly
              label="Personal de recepcion"
              class="q-mb-sm"
            >
              <template #prepend><q-icon name="how_to_reg" /></template>
            </q-input>
            <q-input
              v-model="form.observaciones"
              outlined
              dense
              type="textarea"
              autogrow
              label="Observaciones"
              maxlength="1000"
            />
          </q-card-section>
        </q-card>
      </div>

      <!-- Items a despachar -->
      <div class="col-12 col-md-8">
        <q-card flat bordered>
          <q-card-section class="row items-center q-pb-xs">
            <q-icon name="inventory_2" size="22px" color="primary" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Items a despachar</div>
              <div class="text-caption text-grey-7">
                Ajusta las cantidades según el stock disponible
              </div>
            </div>
            <q-space />
            <q-chip dense color="primary" text-color="white" :label="`${items.length} items`" />
          </q-card-section>

          <q-separator />

          <div class="items-table">
            <div class="items-header">
              <div class="col-item">#</div>
              <div class="col-img"></div>
              <div class="col-desc">Descripción</div>
              <div class="col-num">Pedido</div>
              <div class="col-num">Stock</div>
              <div class="col-qty">Cantidad</div>
              <div class="col-num">Precio</div>
              <div class="col-num">Subtotal</div>
            </div>

            <div v-for="(it, idx) in items" :key="idx" class="items-row">
              <div class="col-item">{{ idx + 1 }}</div>
              <div class="col-img">
                <q-img :src="itemImageUrl(it)" width="40px" height="40px" fit="cover" class="prod-img" />
              </div>
              <div class="col-desc">
                <div class="text-weight-medium">{{ it.nombre }}</div>
                <div class="text-caption text-grey-7">{{ it.unidad || '-' }}</div>
              </div>
              <div class="col-num">
                <q-badge color="grey-3" text-color="grey-9">{{ it.cantidad_pedida }}</q-badge>
              </div>
              <div class="col-num">
                <q-badge :color="stockColor(it)" class="text-weight-bold">{{ it.stock_disponible }}</q-badge>
              </div>
              <div class="col-qty">
                <q-input
                  v-model.number="it.cantidad"
                  dense
                  outlined
                  type="number"
                  :min="0"
                  step="any"
                  :max="it.stock_disponible"
                  :error="hasItemError(it)"
                  no-error-icon
                />
              </div>
              <div class="col-num">{{ money(it.precio_unitario) }}</div>
              <div class="col-num text-weight-bold text-primary">
                {{ money(lineSubtotal(it)) }}
              </div>
            </div>
          </div>

          <q-separator />

          <q-card-section class="row items-center summary-bar">
            <div class="text-caption text-grey-7">
              <strong>{{ totalItems }}</strong> unidades a despachar
            </div>
            <q-space />
            <div class="text-subtitle2">
              Total a despachar:
              <span class="text-weight-bold text-primary q-ml-sm text-h6">{{ money(totalDespacho) }} Bs</span>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions class="q-pa-sm">
            <q-btn flat no-caps color="grey-8" label="Cancelar" :disable="saving" @click="resetForm" />
            <q-space />
            <q-btn
              unelevated
              no-caps
              color="green"
              icon="local_shipping"
              label="Despachar"
              :loading="saving"
              :disable="!canSave"
              @click="confirmAndSave"
            />
          </q-card-actions>
        </q-card>
      </div>
    </div>

    <q-card v-else flat bordered class="text-center q-pa-lg">
      <q-icon name="search" size="60px" color="grey-5" class="q-mb-sm" />
      <div class="text-subtitle1 text-grey-7">Ingresa el N° de pedido para iniciar el despacho</div>
    </q-card>

    <!-- Print prompt -->
    <q-dialog v-model="showPrintPrompt" persistent>
      <q-card style="width: 380px;">
        <q-card-section class="text-center">
          <q-icon name="check_circle" color="green" size="60px" />
          <div class="text-h6 text-weight-bold q-mt-sm">Despacho creado</div>
          <div class="text-body2 text-grey-7">
            N° {{ createdDespacho?.nro }}
          </div>
        </q-card-section>
        <q-card-actions align="center" class="q-pb-md">
          <q-btn flat no-caps color="grey-8" label="No, gracias" @click="afterPrintFlow(false)" />
          <q-btn unelevated no-caps color="primary" icon="print" label="Imprimir" @click="afterPrintFlow(true)" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'DespachoNuevo',
  data () {
    return {
      pedidoIdInput: '',
      loadingLookup: false,
      errorLookup: '',
      pedido: null,
      items: [],
      saving: false,
      showPrintPrompt: false,
      createdDespacho: null,
      form: {
        fecha_entrega: moment().format('YYYY-MM-DDTHH:mm'),
        personal_recepcion: '',
        observaciones: '',
      },
    }
  },
  computed: {
    totalDespacho () {
      return this.items.reduce((sum, it) => sum + this.lineSubtotal(it), 0)
    },
    totalItems () {
      return this.items.reduce((sum, it) => sum + (Number(it.cantidad) || 0), 0)
    },
    canSave () {
      if (!this.pedido) return false
      if (this.items.every(it => (Number(it.cantidad) || 0) === 0)) return false
      return !this.items.some(it => this.hasItemError(it))
    },
  },
  mounted () {
    if (this.$route.query.pedido_id) {
      this.pedidoIdInput = this.$route.query.pedido_id
      this.lookupPedido()
    }
  },
  methods: {
    money (val) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        .format(Number(val || 0))
    },
    formatDateTime (val) {
      if (!val) return '-'
      return moment(val).format('DD/MM/YYYY HH:mm')
    },
    itemImageUrl (it) {
      const imagen = it?.imagen || 'default.png'
      return `${this.$url}/../images/productos/${imagen}`
    },
    lineSubtotal (it) {
      return (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0)
    },
    stockColor (it) {
      if (it.stock_disponible <= 0) return 'red-7'
      if (it.stock_disponible < it.cantidad_pedida) return 'orange-7'
      return 'green-7'
    },
    hasItemError (it) {
      const c = Number(it.cantidad) || 0
      return c < 0 || c > it.stock_disponible
    },
    async lookupPedido () {
      this.errorLookup = ''
      const id = parseInt(this.pedidoIdInput)
      if (!id) {
        this.errorLookup = 'Ingresa un ID de pedido válido'
        return
      }
      this.loadingLookup = true
      try {
        const res = await this.$axios.get(`despachos/pedido-lookup/${id}`)
        this.pedido = res.data.pedido
        this.items = res.data.detalles.map(d => ({ ...d }))
        this.form.personal_recepcion = this.pedido.nombre_usuario || ''
      } catch (err) {
        this.errorLookup = err?.response?.data?.message || 'No se pudo recuperar el pedido'
        this.pedido = null
        this.items = []
      } finally {
        this.loadingLookup = false
      }
    },
    resetForm () {
      this.pedido = null
      this.items = []
      this.pedidoIdInput = ''
      this.errorLookup = ''
      this.form.fecha_entrega = moment().format('YYYY-MM-DDTHH:mm')
      this.form.personal_recepcion = ''
      this.form.observaciones = ''
    },
    confirmAndSave () {
      this.$q.dialog({
        title: 'Confirmar despacho',
        message: `¿Confirmas el despacho de ${this.totalItems} unidades por ${this.money(this.totalDespacho)} Bs? Esta acción reducirá el inventario.`,
        cancel: true,
        persistent: true,
      }).onOk(this.save)
    },
    async save () {
      this.saving = true
      try {
        const payload = {
          pedido_id: this.pedido.id,
          fecha_entrega: this.form.fecha_entrega,
          personal_recepcion: this.form.personal_recepcion,
          observaciones: this.form.observaciones || null,
          items: this.items
            .filter(it => Number(it.cantidad) > 0)
            .map(it => ({
              almacen_item_id: it.producto_id,
              descripcion: it.nombre,
              unidad: it.unidad,
              cantidad: Number(it.cantidad),
              precio_unitario: Number(it.precio_unitario || 0),
            })),
        }
        const res = await this.$axios.post('despachos', payload)
        this.createdDespacho = res.data
        this.showPrintPrompt = true
        this.$q.notify({ color: 'positive', message: 'Despacho creado', position: 'top' })
      } catch (err) {
        this.$q.notify({
          color: 'negative',
          message: err?.response?.data?.message || 'Error al crear el despacho',
          position: 'top',
        })
      } finally {
        this.saving = false
      }
    },
    async afterPrintFlow (doPrint) {
      this.showPrintPrompt = false
      if (doPrint && this.createdDespacho) {
        try {
          const res = await this.$axios.get(`despachos/${this.createdDespacho.id}/pdf`, { responseType: 'blob' })
          const blob = new Blob([res.data], { type: 'application/pdf' })
          const url = window.URL.createObjectURL(blob)
          window.open(url, '_blank')
          window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
        } catch (e) {
          this.$q.notify({ color: 'negative', message: 'No se pudo generar el PDF', position: 'top' })
        }
      }
      this.$router.push('/despachos')
    },
  },
}
</script>

<style scoped>
.items-table {
  background: #fff;
}

.items-header,
.items-row {
  display: grid;
  grid-template-columns: 36px 50px 1fr 70px 70px 100px 80px 90px;
  gap: 6px;
  align-items: center;
  padding: 6px 10px;
}

.items-header {
  background: #f7f9fc;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  border-bottom: 1px solid #e5eaf2;
}

.items-row {
  border-bottom: 1px solid #f0f2f5;
  font-size: 13px;
}

.items-row:last-child {
  border-bottom: none;
}

.col-num {
  text-align: right;
}

.col-item {
  text-align: center;
  font-weight: 600;
  color: #6b7280;
}

.prod-img {
  border-radius: 4px;
  border: 1px solid #e5eaf2;
}

.summary-bar {
  background: #f7f9fc;
}
</style>
