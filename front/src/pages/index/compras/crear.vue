<template>
  <q-page class="q-pa-md compras-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canCrear"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para crear compras</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="row items-center q-mb-sm">
        <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/compras">
          <q-tooltip>Volver a compras</q-tooltip>
        </q-btn>
        <div>
          <div class="text-h6 text-weight-bold">Nueva compra</div>
          <div class="text-caption text-grey-6">Registro de una compra e ingreso de mercadería al inventario</div>
        </div>
        <q-space />
        <q-btn rounded outline color="grey-7" icon="local_shipping" label="Proveedores" no-caps to="/proveedores">
          <q-tooltip>Administrar proveedores</q-tooltip>
        </q-btn>
      </div>
      <q-separator class="q-mb-md" />

      <q-form @submit.prevent="registrarCompra">
        <div class="row q-col-gutter-sm q-mb-sm">
          <div class="col-12 col-sm-4">
            <q-select v-model="nueva.proveedor_id" label="Proveedor" dense outlined clearable
                      :options="allProveedores" option-value="id" option-label="nombre"
                      emit-value map-options>
              <template v-slot:after>
                <q-btn flat round dense icon="add" color="primary" @click="provQuick = true">
                  <q-tooltip>Nuevo proveedor</q-tooltip>
                </q-btn>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-sm-3">
            <q-input v-model="nueva.fecha_hora" label="Fecha y hora *" dense outlined type="datetime-local"
                     :rules="[v => !!v || 'Requerido']" />
          </div>
          <div class="col-12 col-sm-2">
            <q-input v-model="nueva.nro_factura" label="Nro. Factura" dense outlined />
          </div>
          <div class="col-12 col-sm-3">
            <q-select v-model="nueva.tipo_pago" label="Tipo de pago" dense outlined
                      :options="['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'CREDITO']" />
          </div>
          <div class="col-12">
            <q-input v-model="nueva.comentario" label="Comentario" dense outlined type="textarea" rows="1" />
          </div>
        </div>

        <div class="row items-center q-mb-xs">
          <span class="text-subtitle2 text-weight-bold text-grey-8">Líneas de compra</span>
          <q-space />
          <q-btn rounded unelevated dense no-caps color="teal-1" text-color="primary" class="text-weight-bold"
                 label="Agregar línea" icon="add" @click="agregarLinea" />
        </div>

        <q-markup-table dense flat bordered separator="horizontal" class="full-width q-mb-sm rounded-borders">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th style="width:40px"></th>
              <th class="text-left" style="min-width:220px">Producto / Nombre</th>
              <th class="text-right" style="width:100px">Cantidad</th>
              <th class="text-right" style="width:110px">Precio unit.</th>
              <th class="text-right" style="width:110px">Total</th>
              <th class="text-right" style="width:90px">Factor</th>
              <th class="text-right" style="width:110px">Precio venta</th>
              <th class="text-left" style="width:120px">Lote</th>
              <th class="text-left" style="width:140px">Vencimiento</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!nueva.detalles.length">
              <td colspan="9" class="text-center text-grey-5 q-pa-md">Sin líneas — agregue una para comenzar</td>
            </tr>
            <tr v-for="(linea, idx) in nueva.detalles" :key="linea.uid">
              <td class="text-center">
                <q-btn flat dense round icon="delete" color="negative" size="sm" @click="quitarLinea(idx)" />
              </td>
              <td>
                <q-select v-model="linea.producto_id" dense outlined use-input clearable
                          input-debounce="300" :options="opcionesProducto"
                          option-value="id" option-label="nombre" emit-value map-options
                          @filter="filtrarProductos" @update:model-value="v => onProductoSeleccionado(linea, v)"
                          placeholder="Buscar producto de farmacia (o dejar vacío y escribir nombre)">
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                </q-select>
                <q-input v-if="!linea.producto_id" v-model="linea.nombre" dense outlined class="q-mt-xs"
                         placeholder="Nombre del ítem (si no está en catálogo)" v-uppercase />
              </td>
              <td><q-input v-model.number="linea.cantidad" dense outlined type="number" step="0.0001" min="0"
                           class="text-right" @update:model-value="recalcularLinea(linea)" /></td>
              <td><q-input v-model.number="linea.precio" dense outlined type="number" step="0.01" min="0"
                           class="text-right" @update:model-value="recalcularLinea(linea)" /></td>
              <td><q-input v-model.number="linea.total" dense outlined type="number" step="0.01" min="0"
                           class="text-right" @update:model-value="recalcularDesdeTotal(linea)" /></td>
              <td><q-input v-model.number="linea.factor" dense outlined type="number" step="0.01" min="0"
                           class="text-right" @update:model-value="recalcularLinea(linea)" /></td>
              <td><q-input v-model.number="linea.precio_venta" dense outlined type="number" step="0.01" min="0"
                           class="text-right" /></td>
              <td><q-input v-model="linea.lote" dense outlined v-uppercase /></td>
              <td><q-input v-model="linea.fecha_vencimiento" dense outlined type="date" /></td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-end q-gutter-md">
          <div class="text-h6">Total: <span class="text-primary text-weight-bold">{{ money(totalNueva) }} Bs</span></div>
          <q-btn flat color="grey-7" label="Cancelar" no-caps to="/compras" />
          <q-btn rounded unelevated color="primary" label="Registrar compra" icon-right="save" no-caps
                 type="submit" :loading="registrando" :disable="!nueva.detalles.length" />
        </div>
      </q-form>

    </template>

    <!-- Quick proveedor -->
    <q-dialog v-model="provQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo proveedor rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="provQuickSave">
            <q-input v-model="provQNombre" label="Nombre *" dense outlined class="q-mb-md"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="provQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingProv" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { useRouter } from 'vue-router'
import { nowBoliviaDateTimeInput } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()
const router = useRouter()

// ── Permisos ───────────────────────────────────────────────────
const canCrear = computed(() => proxy.$store.hasPermission('Crear Compras'))

const allProveedores = ref([])

function money (v) { return Number(v || 0).toFixed(2) }

// ── Nueva compra ───────────────────────────────────────────────
const registrando = ref(false)
const opcionesProducto = ref([])
let lineaUid = 0

function nuevaCompraVacia () {
  return {
    proveedor_id: null,
    fecha_hora: nowBoliviaDateTimeInput(),
    nro_factura: '',
    tipo_pago: 'EFECTIVO',
    comentario: '',
    detalles: [],
  }
}
const nueva = ref(nuevaCompraVacia())

const totalNueva = computed(() => nueva.value.detalles.reduce((acc, l) => acc + (Number(l.total) || 0), 0))

function agregarLinea () {
  nueva.value.detalles.push({
    uid: ++lineaUid,
    producto_id: null,
    nombre: '',
    cantidad: 1,
    precio: 0,
    total: 0,
    factor: 1.3,
    precio_venta: null,
    lote: '',
    fecha_vencimiento: '',
  })
}

function quitarLinea (idx) {
  nueva.value.detalles.splice(idx, 1)
}

function actualizarPrecioVenta (linea) {
  if (linea.factor) {
    linea.precio_venta = Math.round(((Number(linea.precio) || 0) * Number(linea.factor)) * 100) / 100
  }
}

function recalcularLinea (linea) {
  linea.total = Math.round(((Number(linea.cantidad) || 0) * (Number(linea.precio) || 0)) * 100) / 100
  actualizarPrecioVenta(linea)
}

function recalcularDesdeTotal (linea) {
  const cantidad = Number(linea.cantidad) || 0
  linea.precio = cantidad > 0 ? Math.round((Number(linea.total) / cantidad) * 100) / 100 : 0
  actualizarPrecioVenta(linea)
}

async function filtrarProductos (val, update) {
  try {
    const res = await proxy.$axios.get('productos', { params: { q: val, tipo: 'FARMACIA', per_page: 20 } })
    update(() => { opcionesProducto.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesProducto.value = [] })
  }
}

function onProductoSeleccionado (linea, productoId) {
  const producto = opcionesProducto.value.find(p => p.id === productoId)
  if (producto) {
    linea.nombre = producto.nombre
    if (!linea.precio && producto.precio) linea.precio = Number(producto.precio)
    recalcularLinea(linea)
  }
}

async function registrarCompra () {
  if (!nueva.value.detalles.length) {
    proxy.$alert.error('Agregue al menos una línea de compra')
    return
  }
  registrando.value = true
  try {
    const payload = {
      proveedor_id: nueva.value.proveedor_id,
      fecha_hora: nueva.value.fecha_hora.replace('T', ' '),
      nro_factura: nueva.value.nro_factura,
      tipo_pago: nueva.value.tipo_pago,
      comentario: nueva.value.comentario,
      detalles: nueva.value.detalles.map(l => ({
        producto_id: l.producto_id || null,
        nombre: l.nombre,
        cantidad: l.cantidad,
        precio: l.precio,
        factor: l.factor || null,
        precio_venta: l.precio_venta || null,
        lote: l.lote || null,
        fecha_vencimiento: l.fecha_vencimiento || null,
      })),
    }
    await proxy.$axios.post('compras', payload)
    proxy.$alert.success('Compra registrada')
    nueva.value = nuevaCompraVacia()
    router.push('/compras')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al registrar la compra')
  } finally {
    registrando.value = false
  }
}

// ── Proveedores (solo lectura + alta rápida) ───────────────────
const savingProv  = ref(false)
const provQuick   = ref(false)
const provQNombre = ref('')

async function loadAllProveedores () {
  try {
    const res = await proxy.$axios.get('proveedores')
    allProveedores.value = res.data || []
  } catch (e) { /* silent */ }
}

async function provQuickSave () {
  savingProv.value = true
  try {
    const res = await proxy.$axios.post('proveedores', { nombre: provQNombre.value, estado: 'ACTIVO' })
    await loadAllProveedores()
    nueva.value.proveedor_id = res.data.id
    provQuick.value = false
    provQNombre.value = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingProv.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadAllProveedores()
  agregarLinea()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.compras-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.compras-compactas :deep(.q-field--dense .q-field__native),
.compras-compactas :deep(.q-field--dense .q-field__input),
.compras-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.compras-compactas :deep(.q-field--dense .q-field__append),
.compras-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.compras-compactas :deep(.q-textarea.q-field--dense .q-field__control) {
  min-height: 38px;
}

.compras-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}
</style>
