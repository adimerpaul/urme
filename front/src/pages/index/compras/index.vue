<template>
  <q-page class="q-pa-xs">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver compras</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Compras Totales</div>
            <div class="text-h6 text-green-8 text-weight-bold">{{ money(resumen.total_compras) }} Bs</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Compras Anuladas</div>
            <div class="text-h6 text-red-8 text-weight-bold">{{ money(resumen.total_anuladas) }} Bs</div>
          </q-card>
        </div>
        <div class="col-4">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Total Compras</div>
            <div class="text-h6 text-blue-8 text-weight-bold">{{ resumen.cantidad }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left"
              active-color="primary" indicator-color="primary" class="q-mb-xs">
        <q-tab name="historial"  icon="history"       label="Historial" no-caps />
        <q-tab name="nueva"      icon="add_shopping_cart" label="Nueva compra" no-caps />
        <q-tab name="proveedores" icon="local_shipping" label="Proveedores" no-caps />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <!-- ══ TAB HISTORIAL ══════════════════════════════════════════ -->
      <div v-show="tab === 'historial'">
        <div class="row items-center q-col-gutter-xs q-mb-xs">
          <div class="col-auto">
            <q-input v-model="filtro.fecha_inicio" label="Fecha inicio" dense outlined type="date"
                     style="width:150px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-input v-model="filtro.fecha_fin" label="Fecha fin" dense outlined type="date"
                     style="width:150px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-select v-model="filtro.proveedor_id" label="Proveedor" dense outlined clearable
                      :options="allProveedores" option-value="id" option-label="nombre"
                      emit-value map-options style="width:170px" @update:model-value="onFiltroChange" />
          </div>
          <div class="col-auto">
            <q-select v-model="filtro.estado" label="Estado" dense outlined clearable
                      :options="['ACTIVO', 'ANULADO']" style="width:130px" @update:model-value="onFiltroChange" />
          </div>
          <q-space />
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" @click="exportExcel">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">ID</th>
              <th class="text-left">Fecha</th>
              <th class="text-left">Proveedor</th>
              <th class="text-left">Usuario</th>
              <th class="text-center">Estado</th>
              <th class="text-left">Pago</th>
              <th class="text-right">Total</th>
              <th class="text-center">Detalle</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingCompras">
              <td colspan="9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!compras.length">
              <td colspan="9" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in compras" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown v-if="canEliminar" label="Opciones" no-caps size="10px" dense color="primary">
                  <q-list>
                    <q-item :disable="row.estado === 'ANULADO'" clickable v-close-popup @click="anular(row)">
                      <q-item-section avatar><q-icon name="block" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Anular</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.id }}</td>
              <td>{{ formatFecha(row.fecha_hora) }}</td>
              <td>{{ row.proveedor ? row.proveedor.nombre : '—' }}</td>
              <td>{{ row.user ? row.user.name : '—' }}</td>
              <td class="text-center">
                <q-badge :color="row.estado === 'ANULADO' ? 'negative' : 'positive'">{{ row.estado }}</q-badge>
              </td>
              <td>{{ row.tipo_pago }}</td>
              <td class="text-right">{{ money(row.total) }}</td>
              <td class="text-center">
                <q-btn flat dense round icon="visibility" color="primary" @click="verDetalle(row)">
                  <q-tooltip>Ver detalle ({{ row.detalles_count }})</q-tooltip>
                </q-btn>
              </td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalCompras }} | Página {{ pageCompras }} de {{ pagesCompras }}
          </div>
          <q-pagination v-model="pageCompras" :max="pagesCompras" :max-pages="6"
                        boundary-links direction-links size="sm" @update:model-value="loadCompras" />
        </div>
      </div>

      <!-- ══ TAB NUEVA COMPRA ═══════════════════════════════════════ -->
      <div v-show="tab === 'nueva'">
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
            <span class="text-subtitle2 text-grey-7">Líneas de compra</span>
            <q-space />
            <q-btn color="positive" label="Agregar línea" icon="add_circle_outline" no-caps dense
                   @click="agregarLinea" />
          </div>

          <q-markup-table dense flat bordered separator="cell" class="full-width q-mb-sm">
            <thead>
              <tr class="bg-grey-2">
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
                            placeholder="Buscar producto (o dejar vacío y escribir nombre)">
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
            <div class="text-h6">Total: {{ money(totalNueva) }} Bs</div>
            <q-btn color="primary" label="Registrar compra" icon-right="save" no-caps
                   type="submit" :loading="registrando" :disable="!nueva.detalles.length" />
          </div>
        </q-form>
      </div>

      <!-- ══ TAB PROVEEDORES ════════════════════════════════════════ -->
      <div v-show="tab === 'proveedores'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Proveedores</span>
          <q-space />
          <q-input v-model="filterProv" label="Buscar" dense outlined clearable style="width:180px"
                   @update:model-value="loadProveedores">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline" no-caps dense
                 @click="provNew" />
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">NIT</th>
              <th class="text-left">Contacto</th>
              <th class="text-left">Teléfono</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingProv">
              <td colspan="6" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!proveedores.length">
              <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in proveedores" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown v-if="canEditar || canEliminar" label="Opciones" no-caps size="10px" dense color="primary">
                  <q-list>
                    <q-item v-if="canEditar" clickable v-close-popup @click="provEdit(row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="provDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.nit || '—' }}</td>
              <td>{{ row.contacto || '—' }}</td>
              <td>{{ row.telefono || '—' }}</td>
              <td class="text-center">
                <q-badge :color="row.estado === 'ACTIVO' ? 'positive' : 'grey-6'">{{ row.estado }}</q-badge>
              </td>
            </tr>
          </tbody>
        </q-markup-table>
      </div>

    </template>

    <!-- DIALOG DETALLE COMPRA -->
    <q-dialog v-model="dialogDetalle">
      <q-card style="width:min(96vw,700px)">
        <q-card-section class="row items-center bg-blue-8 text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Detalle de compra #{{ detalleCompra?.id }}</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogDetalle = false" />
        </q-card-section>
        <q-card-section style="max-height:70vh;overflow-y:auto">
          <q-markup-table dense flat bordered separator="cell">
            <thead>
              <tr class="bg-grey-2">
                <th class="text-left">Producto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
                <th class="text-left">Lote</th>
                <th class="text-left">Vencimiento</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in detalleCompra?.detalles || []" :key="d.id">
                <td>{{ d.nombre }}</td>
                <td class="text-right">{{ d.cantidad }}</td>
                <td class="text-right">{{ money(d.precio) }}</td>
                <td class="text-right">{{ money(d.total) }}</td>
                <td>{{ d.lote || '—' }}</td>
                <td>{{ d.fecha_vencimiento ? d.fecha_vencimiento.slice(0, 10) : '—' }}</td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG PROVEEDOR -->
    <q-dialog v-model="dialogProv" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-blue-8 text-white q-py-sm">
          <q-icon name="local_shipping" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ provAction }} proveedor</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogProv = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="provSave">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-input v-model="prov.nombre" label="Nombre *" dense outlined
                         :rules="[v => !!v || 'Requerido']" v-uppercase />
              </div>
              <div class="col-6"><q-input v-model="prov.nit" label="NIT" dense outlined /></div>
              <div class="col-6"><q-input v-model="prov.razon_social" label="Razón social" dense outlined v-uppercase /></div>
              <div class="col-6"><q-input v-model="prov.contacto" label="Contacto" dense outlined v-uppercase /></div>
              <div class="col-6"><q-input v-model="prov.telefono" label="Teléfono" dense outlined /></div>
              <div class="col-6"><q-input v-model="prov.email" label="Email" dense outlined /></div>
              <div class="col-6">
                <q-select v-model="prov.estado" label="Estado" dense outlined :options="['ACTIVO', 'INACTIVO']" />
              </div>
              <div class="col-12"><q-input v-model="prov.direccion" label="Dirección" dense outlined v-uppercase /></div>
              <div class="col-12"><q-input v-model="prov.observacion" label="Observación" dense outlined type="textarea" rows="2" /></div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProv = false" />
              <q-btn color="primary" :label="prov.id ? 'Guardar cambios' : 'Crear proveedor'"
                     type="submit" no-caps :loading="savingProv" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick proveedor -->
    <q-dialog v-model="provQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-blue-8 text-white q-py-sm">
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

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Compras'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Compras'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Compras'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Compras'))

const tab     = ref('historial')
const resumen = ref({ total_compras: 0, total_anuladas: 0, cantidad: 0 })
const allProveedores = ref([])

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return v ? v.replace('T', ' ').slice(0, 16) : '—' }

// ── Historial ──────────────────────────────────────────────────
const compras        = ref([])
const loadingCompras = ref(false)
const pageCompras    = ref(1)
const totalCompras   = ref(0)
const perCompras     = 15
const exportingExcel = ref(false)
const filtro = ref({ fecha_inicio: '', fecha_fin: '', proveedor_id: null, estado: null })

const pagesCompras = computed(() => Math.max(1, Math.ceil(totalCompras.value / perCompras)))

let timerFiltro = null
function onFiltroChange () {
  clearTimeout(timerFiltro)
  timerFiltro = setTimeout(() => { pageCompras.value = 1; loadCompras() }, 350)
}

async function loadCompras () {
  loadingCompras.value = true
  try {
    const res = await proxy.$axios.get('compras', {
      params: {
        page: pageCompras.value,
        per_page: perCompras,
        fecha_inicio: filtro.value.fecha_inicio,
        fecha_fin: filtro.value.fecha_fin,
        proveedor_id: filtro.value.proveedor_id,
        estado: filtro.value.estado,
      },
    })
    const data = res.data || {}
    resumen.value = data.resumen || { total_compras: 0, total_anuladas: 0, cantidad: 0 }
    compras.value = data.compras?.data || []
    totalCompras.value = data.compras?.total || 0
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar compras')
  } finally {
    loadingCompras.value = false
  }
}

const dialogDetalle = ref(false)
const detalleCompra = ref(null)
async function verDetalle (row) {
  try {
    const res = await proxy.$axios.get('compras/' + row.id)
    detalleCompra.value = res.data
    dialogDetalle.value = true
  } catch (e) {
    proxy.$alert.error('Error al cargar el detalle')
  }
}

function anular (row) {
  proxy.$alert.dialog('¿Desea anular la compra #' + row.id + '? Esto revertirá el stock generado.').onOk(() => {
    proxy.$axios.delete('compras/' + row.id)
      .then(() => { proxy.$alert.success('Compra anulada'); loadCompras() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al anular'))
  })
}

async function exportExcel () {
  exportingExcel.value = true
  try {
    const res = await proxy.$axios.get('compras/export-excel', {
      params: { ...filtro.value },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = 'compras_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error('Error al generar Excel')
  } finally {
    exportingExcel.value = false
  }
}

// ── Nueva compra ───────────────────────────────────────────────
const registrando = ref(false)
const opcionesProducto = ref([])
let lineaUid = 0

function nuevaCompraVacia () {
  return {
    proveedor_id: null,
    fecha_hora: new Date().toISOString().slice(0, 16),
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
    const res = await proxy.$axios.get('productos', { params: { q: val, per_page: 20 } })
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
    tab.value = 'historial'
    loadCompras()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al registrar la compra')
  } finally {
    registrando.value = false
  }
}

// ── Proveedores ────────────────────────────────────────────────
const proveedores   = ref([])
const loadingProv   = ref(false)
const savingProv    = ref(false)
const dialogProv    = ref(false)
const provAction    = ref('Nuevo')
const filterProv    = ref('')
const prov          = ref({})
const provQuick     = ref(false)
const provQNombre   = ref('')

async function loadProveedores () {
  loadingProv.value = true
  try {
    const res = await proxy.$axios.get('proveedores', { params: { q: filterProv.value, per_page: 100 } })
    proveedores.value = res.data?.data || []
  } catch (e) {
    proxy.$alert.error('Error al cargar proveedores')
  } finally {
    loadingProv.value = false
  }
}

async function loadAllProveedores () {
  try {
    const res = await proxy.$axios.get('proveedores')
    allProveedores.value = res.data || []
  } catch (e) { /* silent */ }
}

function provNew ()     { prov.value = { nombre: '', estado: 'ACTIVO' }; provAction.value = 'Nuevo'; dialogProv.value = true }
function provEdit (row) { prov.value = { ...row }; provAction.value = 'Editar'; dialogProv.value = true }

async function provSave () {
  savingProv.value = true
  try {
    if (prov.value.id) {
      await proxy.$axios.put('proveedores/' + prov.value.id, prov.value)
      proxy.$alert.success('Proveedor actualizado')
    } else {
      await proxy.$axios.post('proveedores', prov.value)
      proxy.$alert.success('Proveedor creado')
    }
    dialogProv.value = false
    loadProveedores()
    loadAllProveedores()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProv.value = false
  }
}

function provDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el proveedor?').onOk(() => {
    proxy.$axios.delete('proveedores/' + id)
      .then(() => { proxy.$alert.success('Proveedor eliminado'); loadProveedores(); loadAllProveedores() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
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
  loadCompras()
  loadProveedores()
  loadAllProveedores()
  agregarLinea()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>
