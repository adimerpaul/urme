<template>
  <q-page class="q-pa-sm ventas-compactas">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver ventas</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <div class="q-mb-xs">
        <div class="text-h6 text-weight-bold">Ventas</div>
        <div class="text-caption text-grey-6">Historial de ventas y proformas de pago</div>
      </div>

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-12 col-sm-3">
          <q-card flat class="bg-primary text-white q-pa-sm rounded-borders full-height">
            <div class="text-caption text-teal-2 text-uppercase text-weight-bold">Ventas activas</div>
            <div class="text-subtitle1 text-weight-bold">{{ money(resumen.total_ventas) }} <span class="text-caption text-teal-2">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Pendientes</div>
            <div class="text-subtitle1 text-weight-bold text-orange-8">{{ money(resumen.total_pendientes) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Anuladas</div>
            <div class="text-subtitle1 text-weight-bold text-negative">{{ money(resumen.total_anuladas) }} <span class="text-caption text-grey-6">Bs</span></div>
          </q-card>
        </div>
        <div class="col-12 col-sm-3">
          <q-card flat bordered class="q-pa-sm rounded-borders full-height">
            <div class="text-caption text-grey-6 text-uppercase text-weight-bold">Total registros</div>
            <div class="text-subtitle1 text-weight-bold">{{ resumen.cantidad }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left" no-caps
              active-color="primary" indicator-color="primary" class="q-mb-sm text-grey-7">
        <q-tab name="historial" icon="history" label="Historial" no-caps />
        <q-tab v-if="canCrear" name="nueva" icon="point_of_sale" label="Nueva venta" no-caps />
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
            <q-select v-model="filtro.paciente_id" label="Paciente" dense outlined clearable use-input
                      input-debounce="350" :options="opcionesPaciente"
                      option-value="id" option-label="nombre_completo" emit-value map-options
                      style="width:220px" @filter="filtrarPacientes" @update:model-value="onFiltroChange">
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
              </template>
            </q-select>
          </div>
          <div class="col-auto">
            <q-select v-model="filtro.estado" label="Estado" dense outlined clearable
                      :options="['ACTIVO', 'PENDIENTE', 'ANULADO']" style="width:140px" @update:model-value="onFiltroChange" />
          </div>
        </div>

        <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta">
          <thead>
            <tr class="bg-grey-1 text-grey-7 text-uppercase">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">ID</th>
              <th class="text-left">Fecha</th>
              <th class="text-left">Cliente / Paciente</th>
              <th class="text-left">Doctor</th>
              <th class="text-left">Seguro</th>
              <th class="text-left">Usuario</th>
              <th class="text-center">Estado</th>
              <th class="text-left">Pago</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingVentas">
              <td colspan="10" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
            </tr>
            <tr v-else-if="!ventas.length">
              <td colspan="10" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in ventas" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown label="Opciones" no-caps size="10px" dense rounded unelevated color="primary">
                  <q-list dense>
                    <q-item clickable v-close-popup @click="verDetalle(row)">
                      <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Ver detalle ({{ row.detalles_count }})</q-item-label></q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="imprimir(row)">
                      <q-item-section avatar><q-icon name="print" color="primary" /></q-item-section>
                      <q-item-section><q-item-label>Imprimir</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="row.estado === 'PENDIENTE'" clickable v-close-popup @click="abrirCobrar(row)">
                      <q-item-section avatar><q-icon name="payments" color="positive" /></q-item-section>
                      <q-item-section><q-item-label class="text-positive">Cobrar venta</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" :disable="row.estado === 'ANULADO'" clickable v-close-popup @click="anular(row)">
                      <q-item-section avatar><q-icon name="block" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Anular</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.id }}</td>
              <td>{{ formatFecha(row.fecha_hora) }}</td>
              <td>{{ row.paciente ? row.paciente.nombre_completo : (row.cliente || '—') }}</td>
              <td>{{ row.doctor ? row.doctor.nombre : '—' }}</td>
              <td>{{ row.seguro ? row.seguro.nombre : 'PARTICULAR' }}</td>
              <td>{{ row.user ? row.user.name : '—' }}</td>
              <td class="text-center">
                <q-badge rounded
                         :color="estadoColor(row.estado).bg"
                         :text-color="estadoColor(row.estado).text"
                         class="text-weight-bold">{{ row.estado }}</q-badge>
              </td>
              <td>{{ row.tipo_pago }}</td>
              <td class="text-right">{{ money(row.total) }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalVentas }} | Página {{ pageVentas }} de {{ pagesVentas }}
          </div>
          <q-pagination v-model="pageVentas" :max="pagesVentas" :max-pages="6"
                        boundary-links direction-links size="sm" @update:model-value="loadVentas" />
        </div>
      </div>

      <!-- ══ TAB NUEVA VENTA ════════════════════════════════════════ -->
      <div v-show="tab === 'nueva'">
        <div class="row q-col-gutter-md">

          <!-- Productos -->
          <div class="col-12 col-md-7">
            <div class="row items-center q-gutter-sm q-mb-sm">
              <span class="text-subtitle2 text-weight-bold text-grey-8">Productos</span>
              <q-space />
              <q-select v-model="filtroTipo" dense outlined clearable label="Tipo" style="width:150px"
                        :options="tiposProducto" option-value="id" option-label="nombre"
                        emit-value map-options @update:model-value="onBuscarProducto" />
              <q-btn dense outline no-caps color="primary" icon="refresh" label="Actualizar"
                     :loading="loadingProductos" @click="actualizarProductos">
                <q-tooltip>Actualizar productos y cantidades disponibles</q-tooltip>
              </q-btn>
              <q-input v-model="buscarProducto" placeholder="Buscar producto…" dense outlined rounded clearable
                       bg-color="white" style="width:220px" @update:model-value="onBuscarProducto">
                <template v-slot:prepend><q-icon name="search" /></template>
              </q-input>
            </div>

            <q-markup-table dense flat bordered separator="horizontal" class="full-width rounded-borders tabla-compacta">
              <thead>
                <tr class="bg-grey-1 text-grey-7 text-uppercase">
                  <th style="width:44px"></th>
                  <th class="text-left">Código</th>
                  <th class="text-left">Producto</th>
                  <th class="text-left">Tipo</th>
                  <th class="text-right">Cantidad</th>
                  <th class="text-right">Precio</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingProductos">
                  <td colspan="6" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
                </tr>
                <tr v-else-if="!productos.length">
                  <td colspan="6" class="text-center text-grey-5 q-pa-md">Sin productos</td>
                </tr>
                <tr v-else v-for="p in productos" :key="p.id">
                  <td class="text-center">
                    <q-btn dense round unelevated size="sm" color="primary" icon="add"
                           :disable="esProductoFarmacia(p) && cantidadDisponibleProducto(p) <= 0"
                           @click="agregarProducto(p)">
                      <q-tooltip>
                        {{ !esProductoFarmacia(p) || cantidadDisponibleProducto(p) > 0 ? 'Agregar a la venta' : 'Sin lote disponible' }}
                      </q-tooltip>
                    </q-btn>
                  </td>
                  <td>{{ p.codigo || '—' }}</td>
                  <td>{{ p.nombre }}</td>
                  <td>
                    <q-badge v-if="p.tipo_producto" rounded :style="{ background: p.tipo_producto.color || '#607d8b' }">
                      {{ p.tipo_producto.nombre }}
                    </q-badge>
                    <span v-else>—</span>
                  </td>
                  <td class="text-right">
                    <q-badge v-if="esProductoFarmacia(p)"
                             :color="cantidadDisponibleProducto(p) > 0 ? 'green-1' : 'red-1'"
                             :text-color="cantidadDisponibleProducto(p) > 0 ? 'green-9' : 'negative'">
                      {{ cantidadDisponibleProducto(p).toFixed(2) }}
                    </q-badge>
                    <q-badge v-else color="blue-grey-1" text-color="blue-grey-8">
                      LIBRE
                    </q-badge>
                  </td>
                  <td class="text-right">{{ money(p.precio) }}</td>
                </tr>
              </tbody>
            </q-markup-table>

            <div class="row items-center justify-between q-mt-xs q-px-xs">
              <div class="text-caption text-grey-6">
                Total: {{ totalProductos }} | Página {{ pageProductos }} de {{ pagesProductos }}
              </div>
              <q-pagination v-model="pageProductos" :max="pagesProductos" :max-pages="6"
                            boundary-links direction-links size="sm" @update:model-value="loadProductos" />
            </div>
          </div>

          <!-- Carrito / datos de la venta -->
          <div class="col-12 col-md-5">
            <q-card flat bordered class="q-pa-sm rounded-borders">
              <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-sm">
                <q-icon name="shopping_cart" size="18px" class="q-mr-xs" />Detalle de la venta
              </div>

              <q-form @submit.prevent="registrarVenta">
                <div class="row q-col-gutter-sm q-mb-sm">
                  <div class="col-12">
                    <q-select v-model="nueva.paciente_id" label="Paciente" dense outlined clearable use-input
                              input-debounce="350" :options="opcionesPaciente"
                              option-value="id" option-label="nombre_completo" emit-value map-options
                              @filter="filtrarPacientes">
                      <template v-slot:no-option>
                        <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                      </template>
                      <template v-slot:after>
                        <q-btn flat round dense icon="add" color="primary" @click="pacQuick = true">
                          <q-tooltip>Nuevo paciente</q-tooltip>
                        </q-btn>
                      </template>
                    </q-select>
                  </div>
                  <div class="col-12" v-if="!nueva.paciente_id">
                    <q-input v-model="nueva.cliente" label="Cliente (si no es paciente)" dense outlined v-uppercase />
                  </div>
                  <div class="col-12">
                    <q-select v-model="nueva.doctor_id" label="Doctor" dense outlined clearable use-input
                              input-debounce="350" :options="opcionesDoctor"
                              option-value="id" emit-value map-options
                              :option-label="d => d.nombre + (d.especialidades?.length ? ' — ' + d.especialidades.map(e => e.nombre).join(', ') : '')"
                              @filter="filtrarDoctores">
                      <template v-slot:no-option>
                        <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                      </template>
                      <template v-slot:after>
                        <q-btn flat round dense icon="add" color="primary" @click="abrirDocQuick">
                          <q-tooltip>Nuevo doctor</q-tooltip>
                        </q-btn>
                      </template>
                    </q-select>
                  </div>
                  <div class="col-12">
                    <q-select v-model="nueva.seguro_id" label="Seguro / Institución" dense outlined clearable
                              :options="allSeguros" option-value="id" option-label="nombre"
                              emit-value map-options hint="Vacío = PARTICULAR" />
                  </div>
                  <div class="col-6">
                    <q-input v-model="nueva.fecha_hora" label="Fecha y hora *" dense outlined type="datetime-local"
                             :rules="[v => !!v || 'Requerido']" />
                  </div>
                  <div class="col-6">
                    <q-select v-model="nueva.tipo_pago" label="Tipo de pago" dense outlined
                              :options="['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'QR']" />
                  </div>
                </div>

                <q-markup-table dense flat bordered separator="horizontal" class="full-width q-mb-sm rounded-borders tabla-compacta">
                  <thead>
                    <tr class="bg-grey-1 text-grey-7 text-uppercase">
                      <th style="width:36px"></th>
                      <th class="text-left">Producto</th>
                      <th class="text-right" style="width:86px">Cant.</th>
                      <th class="text-right" style="width:96px">Precio</th>
                      <th class="text-right" style="width:80px">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!nueva.detalles.length">
                      <td colspan="5" class="text-center text-grey-5 q-pa-md">Agregue productos desde la izquierda</td>
                    </tr>
                    <tr v-for="(linea, idx) in nueva.detalles" :key="linea.uid">
                      <td class="text-center">
                        <q-btn flat dense round icon="delete" color="negative" size="sm" @click="quitarLinea(idx)" />
                      </td>
                      <td>
                        <div>{{ linea.nombre }}</div>
                        <div v-if="linea.requiere_lote" class="text-caption text-grey-7">
                          Lote: <b>{{ linea.lote }}</b>
                          <span class="q-ml-xs">Vence: {{ linea.fecha_vencimiento || 'SIN FECHA' }}</span>
                        </div>
                        <div v-if="linea.requiere_lote" class="text-caption text-positive">
                          Disponible: {{ Number(linea.cantidad_disponible).toFixed(2) }}
                        </div>
                        <div v-else class="text-caption text-blue-grey-7">Sin control de lote</div>
                      </td>
                      <td><q-input v-model.number="linea.cantidad" dense outlined type="number" step="1" min="0"
                                   :max="linea.requiere_lote ? linea.cantidad_disponible : undefined"
                                   @update:model-value="recalcularLinea(linea)" /></td>
                      <td><q-input v-model.number="linea.precio" dense outlined type="number" step="0.01" min="0"
                                   @update:model-value="recalcularLinea(linea)" /></td>
                      <td class="text-right">{{ money(linea.total) }}</td>
                    </tr>
                  </tbody>
                </q-markup-table>

                <div class="row q-col-gutter-sm items-center q-mb-sm">
                  <div class="col-4">
                    <q-input :model-value="money(totalNueva)" label="Total Bs" dense outlined readonly
                             input-class="text-right text-weight-bold" />
                  </div>
                  <div class="col-4">
                    <q-input v-model.number="nueva.pago" label="Pago Bs" dense outlined type="number" step="0.01" min="0"
                             input-class="text-right" />
                  </div>
                  <div class="col-4">
                    <q-input :model-value="money(cambioNueva)" label="Cambio Bs" dense outlined readonly
                             :input-class="'text-right ' + (cambioNueva < 0 ? 'text-negative' : '')" />
                  </div>
                  <div class="col-12">
                    <q-input v-model="nueva.comentario" label="Comentario" dense outlined type="textarea" rows="1" />
                  </div>
                </div>

                <div class="row items-center justify-between q-gutter-sm">
                  <div class="text-h6">Total: <span class="text-primary text-weight-bold">{{ money(totalNueva) }} Bs</span></div>
                  <div class="q-gutter-sm">
                    <q-btn rounded outline color="orange-8" label="Vender luego" icon-right="schedule" no-caps
                           :loading="registrando" :disable="!nueva.detalles.length" @click="registrarVenta('PENDIENTE')" />
                    <q-btn rounded unelevated color="primary" label="Registrar venta" icon-right="save" no-caps
                           type="submit" :loading="registrando" :disable="!nueva.detalles.length" />
                  </div>
                </div>
              </q-form>
            </q-card>
          </div>
        </div>
      </div>

    </template>

    <!-- DIALOG SELECCIONAR LOTE -->
    <q-dialog v-model="dialogLotes">
      <q-card style="width:min(96vw,650px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="inventory_2" size="20px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle2 text-weight-bold">Seleccionar lote</div>
            <div class="text-caption">{{ productoParaLote?.nombre }}</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-sm">
          <q-list bordered separator>
            <q-item v-for="lote in lotesDisponibles" :key="lote.compra_detalle_id"
                    clickable v-ripple @click="seleccionarLote(lote)">
              <q-item-section avatar>
                <q-icon name="inventory" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">Lote {{ lote.lote }}</q-item-label>
                <q-item-label caption>
                  Compra #{{ lote.compra_id }} · Vencimiento: {{ lote.fecha_vencimiento || 'SIN FECHA' }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-chip dense color="green-1" text-color="green-9">
                  Disponible: {{ Number(lote.cantidad_disponible).toFixed(2) }}
                </q-chip>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG DETALLE VENTA -->
    <q-dialog v-model="dialogDetalle">
      <q-card style="width:min(96vw,700px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Detalle de venta #{{ detalleVenta?.id }}</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogDetalle = false" />
        </q-card-section>
        <q-card-section style="max-height:70vh;overflow-y:auto">
          <div class="row q-col-gutter-sm q-mb-sm text-body2">
            <div class="col-6"><b>Cliente:</b> {{ detalleVenta?.paciente?.nombre_completo || detalleVenta?.cliente || '—' }}</div>
            <div class="col-6"><b>Doctor:</b> {{ detalleVenta?.doctor?.nombre || '—' }}</div>
            <div class="col-6"><b>Seguro:</b> {{ detalleVenta?.seguro?.nombre || 'PARTICULAR' }}</div>
            <div class="col-6"><b>Usuario:</b> {{ detalleVenta?.user?.name || '—' }}</div>
            <div class="col-6"><b>Fecha:</b> {{ formatFecha(detalleVenta?.fecha_hora) }}</div>
            <div class="col-6"><b>Estado:</b> {{ detalleVenta?.estado || '—' }}</div>
          </div>
          <q-markup-table dense flat bordered separator="horizontal">
            <thead>
              <tr class="bg-grey-1 text-grey-7 text-uppercase">
                <th class="text-left">Producto</th>
                <th class="text-left">Lote / Vencimiento</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in detalleVenta?.detalles || []" :key="d.id">
                <td>{{ d.nombre }}</td>
                <td>{{ d.lote || '—' }} / {{ d.fecha_vencimiento || 'SIN FECHA' }}</td>
                <td class="text-right">{{ d.cantidad }}</td>
                <td class="text-right">{{ money(d.precio) }}</td>
                <td class="text-right">{{ money(d.total) }}</td>
              </tr>
            </tbody>
          </q-markup-table>
          <div class="row justify-end q-mt-sm text-body2">
            <div class="text-right">
              <div><b>Total:</b> {{ money(detalleVenta?.total) }} Bs</div>
              <div><b>Pago:</b> {{ money(detalleVenta?.pago) }} Bs</div>
              <div><b>Cambio:</b> {{ money(detalleVenta?.cambio) }} Bs</div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG COBRAR VENTA PENDIENTE -->
    <q-dialog v-model="dialogCobrar" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Cobrar venta #{{ ventaCobrar?.id }}</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="cobrarVenta">
            <div class="text-h6 q-mb-sm">Total: <span class="text-primary text-weight-bold">{{ money(ventaCobrar?.total) }} Bs</span></div>
            <q-input v-model.number="cobrarPago" label="Pago Bs *" dense outlined type="number" step="0.01" min="0"
                     class="q-mb-xs" autofocus input-class="text-right" />
            <div class="text-body2 q-mb-md">Cambio:
              <span class="text-weight-bold" :class="cobrarCambio < 0 ? 'text-negative' : 'text-positive'">
                {{ money(cobrarCambio) }} Bs
              </span>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogCobrar = false" />
              <q-btn color="primary" label="Cobrar e imprimir" icon-right="payments" type="submit" no-caps :loading="cobrando" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG PACIENTE RÁPIDO -->
    <q-dialog v-model="pacQuick" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo paciente rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="pacQuickSave">
            <q-input v-model="pacQ.nombre_completo" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model="pacQ.ci" label="CI" dense outlined v-uppercase />
              </div>
              <div class="col-6">
                <q-select v-model="pacQ.sexo" label="Sexo" dense outlined clearable
                          :options="[{label:'Masculino',value:'M'},{label:'Femenino',value:'F'}]"
                          emit-value map-options />
              </div>
              <div class="col-12">
                <q-input v-model="pacQ.telefono" label="Teléfono" dense outlined />
              </div>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="pacQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingQuick" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG DOCTOR RÁPIDO -->
    <q-dialog v-model="docQuick" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo doctor rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="docQuickSave">
            <q-input v-model="docQ.nombre" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-select v-model="docQ.especialidad_ids" label="Especialidades" dense outlined class="q-mb-md"
                      multiple use-chips :options="especialidades"
                      option-value="id" option-label="nombre" emit-value map-options />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="docQuick = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingQuick" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'
import { imprimirVenta } from '../../../addons/ventaPrint'
import { formatBoliviaDateTime, nowBoliviaDateTimeInput } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Ventas'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Ventas'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Ventas'))

const tab     = ref('historial')
const resumen = ref({ total_ventas: 0, total_pendientes: 0, total_anuladas: 0, cantidad: 0 })

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return formatBoliviaDateTime(v) }

function estadoColor (estado) {
  if (estado === 'ANULADO') return { bg: 'red-1', text: 'negative' }
  if (estado === 'PENDIENTE') return { bg: 'orange-1', text: 'orange-9' }
  return { bg: 'green-1', text: 'positive' }
}

// ── Pacientes (select con filtro) ──────────────────────────────
const opcionesPaciente = ref([])
async function filtrarPacientes (val, update) {
  try {
    const res = await proxy.$axios.get('pacientes', { params: { q: val, per_page: 20 } })
    update(() => { opcionesPaciente.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesPaciente.value = [] })
  }
}

// ── Doctores (select con filtro) ───────────────────────────────
const opcionesDoctor = ref([])
async function filtrarDoctores (val, update) {
  try {
    const res = await proxy.$axios.get('doctores', { params: { q: val, estado: 'ACTIVO', per_page: 20 } })
    update(() => { opcionesDoctor.value = res.data?.data || [] })
  } catch (e) {
    update(() => { opcionesDoctor.value = [] })
  }
}

// ── Seguros ────────────────────────────────────────────────────
const allSeguros = ref([])
async function loadSeguros () {
  try {
    const res = await proxy.$axios.get('seguros')
    allSeguros.value = res.data || []
  } catch (e) { /* silent */ }
}

// ── Especialidades (para doctor rápido) ────────────────────────
const especialidades = ref([])
async function loadEspecialidades () {
  try {
    const res = await proxy.$axios.get('especialidades')
    especialidades.value = res.data || []
  } catch (e) { /* silent */ }
}

// ── Historial ──────────────────────────────────────────────────
const ventas        = ref([])
const loadingVentas = ref(false)
const pageVentas    = ref(1)
const totalVentas   = ref(0)
const perVentas     = 15
const filtro = ref({ fecha_inicio: '', fecha_fin: '', paciente_id: null, estado: null })

const pagesVentas = computed(() => Math.max(1, Math.ceil(totalVentas.value / perVentas)))

let timerFiltro = null
function onFiltroChange () {
  clearTimeout(timerFiltro)
  timerFiltro = setTimeout(() => { pageVentas.value = 1; loadVentas() }, 350)
}

async function loadVentas () {
  loadingVentas.value = true
  try {
    const res = await proxy.$axios.get('ventas', {
      params: {
        page: pageVentas.value,
        per_page: perVentas,
        fecha_inicio: filtro.value.fecha_inicio,
        fecha_fin: filtro.value.fecha_fin,
        paciente_id: filtro.value.paciente_id,
        estado: filtro.value.estado,
      },
    })
    const data = res.data || {}
    resumen.value = data.resumen || { total_ventas: 0, total_anuladas: 0, cantidad: 0 }
    ventas.value = data.ventas?.data || []
    totalVentas.value = data.ventas?.total || 0
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar ventas')
  } finally {
    loadingVentas.value = false
  }
}

const dialogDetalle = ref(false)
const detalleVenta  = ref(null)
async function verDetalle (row) {
  try {
    const res = await proxy.$axios.get('ventas/' + row.id)
    detalleVenta.value = res.data
    dialogDetalle.value = true
  } catch (e) {
    proxy.$alert.error('Error al cargar el detalle')
  }
}

async function imprimir (row) {
  try {
    const res = await proxy.$axios.get('ventas/' + row.id)
    imprimirVenta(res.data)
  } catch (e) {
    proxy.$alert.error('Error al imprimir')
  }
}

function anular (row) {
  proxy.$alert.dialog('¿Desea anular la venta #' + row.id + '?').onOk(() => {
    proxy.$axios.delete('ventas/' + row.id)
      .then(() => { proxy.$alert.success('Venta anulada'); loadVentas() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error al anular'))
  })
}

// ── Productos (tab nueva venta) ────────────────────────────────
const productos        = ref([])
const loadingProductos = ref(false)
const buscarProducto   = ref('')
const filtroTipo       = ref(null)
const tiposProducto    = ref([])
const pageProductos    = ref(1)
const totalProductos   = ref(0)
const perProductos     = 10

const pagesProductos = computed(() => Math.max(1, Math.ceil(totalProductos.value / perProductos)))

function cantidadDisponibleProducto (producto) {
  return Math.max(
    0,
    Number(producto.cantidad_con_lote || 0) - Number(producto.cantidad_vendida_lote || 0),
  )
}

function esProductoFarmacia (producto) {
  return String(producto.tipo_producto?.nombre || '').toUpperCase() === 'FARMACIA'
}

let timerProducto = null
function onBuscarProducto () {
  clearTimeout(timerProducto)
  timerProducto = setTimeout(() => { pageProductos.value = 1; loadProductos() }, 350)
}

async function loadProductos () {
  loadingProductos.value = true
  try {
    const res = await proxy.$axios.get('productos', {
      params: {
        q: buscarProducto.value,
        tipo_producto_id: filtroTipo.value,
        page: pageProductos.value,
        per_page: perProductos,
      },
    })
    productos.value = res.data?.data || []
    totalProductos.value = res.data?.total || 0
  } catch (e) {
    proxy.$alert.error('Error al cargar productos')
  } finally {
    loadingProductos.value = false
  }
}

async function loadTiposProducto () {
  try {
    const res = await proxy.$axios.get('tipo-productos')
    tiposProducto.value = res.data || []
  } catch (e) { /* silent */ }
}

async function actualizarProductos () {
  pageProductos.value = 1
  await Promise.all([loadProductos(), loadTiposProducto()])
}

// ── Nueva venta ────────────────────────────────────────────────
const registrando = ref(false)
const dialogLotes = ref(false)
const productoParaLote = ref(null)
const lotesDisponibles = ref([])
let lineaUid = 0

function nuevaVentaVacia () {
  return {
    paciente_id: null,
    doctor_id: null,
    seguro_id: null,
    cliente: '',
    fecha_hora: nowBoliviaDateTimeInput(),
    tipo_pago: 'EFECTIVO',
    comentario: '',
    pago: null,
    detalles: [],
  }
}
const nueva = ref(nuevaVentaVacia())

const totalNueva  = computed(() => nueva.value.detalles.reduce((acc, l) => acc + (Number(l.total) || 0), 0))
const cambioNueva = computed(() => {
  const pago = Number(nueva.value.pago)
  if (!pago) return 0
  return Math.round((pago - totalNueva.value) * 100) / 100
})

async function agregarProducto (p) {
  if (!esProductoFarmacia(p)) {
    agregarProductoSinLote(p)
    return
  }
  try {
    const res = await proxy.$axios.get('productos/' + p.id + '/lotes-disponibles')
    const lotes = res.data || []
    if (!lotes.length) {
      proxy.$alert.error('No se puede vender ' + p.nombre + ': no tiene un lote con stock disponible')
      return
    }
    if (lotes.length === 1) {
      agregarProductoConLote(p, lotes[0])
      return
    }
    productoParaLote.value = p
    lotesDisponibles.value = lotes
    dialogLotes.value = true
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al consultar los lotes disponibles')
  }
}

function agregarProductoSinLote (p) {
  const existente = nueva.value.detalles.find(l => (
    l.producto_id === p.id && !l.requiere_lote
  ))
  if (existente) {
    existente.cantidad = (Number(existente.cantidad) || 0) + 1
    recalcularLinea(existente)
    return
  }
  const linea = {
    uid: ++lineaUid,
    producto_id: p.id,
    nombre: p.nombre,
    requiere_lote: false,
    cantidad: 1,
    precio: Number(p.precio) || 0,
    total: 0,
  }
  recalcularLinea(linea)
  nueva.value.detalles.push(linea)
}

function seleccionarLote (lote) {
  agregarProductoConLote(productoParaLote.value, lote)
  dialogLotes.value = false
}

function agregarProductoConLote (p, lote) {
  const existente = nueva.value.detalles.find(l => (
    l.producto_id === p.id && l.compra_detalle_id === lote.compra_detalle_id
  ))
  if (existente) {
    if (Number(existente.cantidad) >= Number(existente.cantidad_disponible)) {
      proxy.$alert.error('No hay más unidades disponibles en el lote ' + lote.lote)
      return
    }
    existente.cantidad = (Number(existente.cantidad) || 0) + 1
    recalcularLinea(existente)
    return
  }
  const linea = {
    uid: ++lineaUid,
    producto_id: p.id,
    requiere_lote: true,
    compra_detalle_id: lote.compra_detalle_id,
    nombre: p.nombre,
    lote: lote.lote,
    fecha_vencimiento: lote.fecha_vencimiento,
    cantidad_disponible: Number(lote.cantidad_disponible),
    cantidad: 1,
    precio: Number(p.precio) || 0,
    total: 0,
  }
  recalcularLinea(linea)
  nueva.value.detalles.push(linea)
}

function quitarLinea (idx) {
  nueva.value.detalles.splice(idx, 1)
}

function recalcularLinea (linea) {
  if (linea.requiere_lote && Number(linea.cantidad) > Number(linea.cantidad_disponible)) {
    linea.cantidad = Number(linea.cantidad_disponible)
    proxy.$alert.error('La cantidad supera el stock disponible del lote ' + linea.lote)
  }
  linea.total = Math.round(((Number(linea.cantidad) || 0) * (Number(linea.precio) || 0)) * 100) / 100
}

async function registrarVenta (estado = 'ACTIVO') {
  if (!nueva.value.detalles.length) {
    proxy.$alert.error('Agregue al menos un producto a la venta')
    return
  }
  const lineaSinLote = nueva.value.detalles.find(l => (
    l.requiere_lote && (!l.compra_detalle_id || !l.lote)
  ))
  if (lineaSinLote) {
    proxy.$alert.error('Seleccione un lote para ' + lineaSinLote.nombre)
    return
  }
  const lineaSinStock = nueva.value.detalles.find(l => (
    Number(l.cantidad) <= 0
    || (l.requiere_lote && Number(l.cantidad) > Number(l.cantidad_disponible))
  ))
  if (lineaSinStock) {
    proxy.$alert.error(
      lineaSinStock.requiere_lote
        ? 'Cantidad no disponible para el lote ' + lineaSinStock.lote
        : 'La cantidad de ' + lineaSinStock.nombre + ' debe ser mayor a cero',
    )
    return
  }
  const pago = Number(nueva.value.pago) || totalNueva.value
  if (estado !== 'PENDIENTE' && pago < totalNueva.value) {
    proxy.$alert.error('El pago no puede ser menor al total')
    return
  }
  registrando.value = true
  try {
    const payload = {
      paciente_id: nueva.value.paciente_id,
      doctor_id: nueva.value.doctor_id,
      seguro_id: nueva.value.seguro_id,
      cliente: nueva.value.paciente_id ? null : nueva.value.cliente,
      fecha_hora: nueva.value.fecha_hora.replace('T', ' '),
      tipo_pago: nueva.value.tipo_pago,
      comentario: nueva.value.comentario,
      pago,
      estado,
      detalles: nueva.value.detalles.map(l => ({
        producto_id: l.producto_id || null,
        compra_detalle_id: l.compra_detalle_id,
        nombre: l.nombre,
        lote: l.lote,
        cantidad: l.cantidad,
        precio: l.precio,
      })),
    }
    const res = await proxy.$axios.post('ventas', payload)
    proxy.$alert.success(estado === 'PENDIENTE' ? 'Venta guardada como pendiente' : 'Venta registrada')
    nueva.value = nuevaVentaVacia()
    tab.value = 'historial'
    loadVentas()
    if (estado !== 'PENDIENTE') {
      imprimirVenta(res.data)
    }
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al registrar la venta')
  } finally {
    registrando.value = false
  }
}

// ── Cobrar venta pendiente ─────────────────────────────────────
const dialogCobrar = ref(false)
const ventaCobrar  = ref(null)
const cobrarPago   = ref(0)
const cobrando     = ref(false)

const cobrarCambio = computed(() => {
  const pago = Number(cobrarPago.value) || 0
  return Math.round((pago - Number(ventaCobrar.value?.total || 0)) * 100) / 100
})

function abrirCobrar (row) {
  ventaCobrar.value = row
  cobrarPago.value = Number(row.total)
  dialogCobrar.value = true
}

async function cobrarVenta () {
  if (Number(cobrarPago.value) < Number(ventaCobrar.value?.total || 0)) {
    proxy.$alert.error('El pago no puede ser menor al total')
    return
  }
  cobrando.value = true
  try {
    const res = await proxy.$axios.put('ventas/' + ventaCobrar.value.id + '/completar', { pago: cobrarPago.value })
    proxy.$alert.success('Venta cobrada')
    dialogCobrar.value = false
    loadVentas()
    imprimirVenta(res.data)
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cobrar')
  } finally {
    cobrando.value = false
  }
}

// ── Paciente / Doctor rápido ───────────────────────────────────
const savingQuick = ref(false)
const pacQuick = ref(false)
const pacQ = ref({ nombre_completo: '', ci: '', sexo: null, telefono: '' })
const docQuick = ref(false)
const docQ = ref({ nombre: '', especialidad_ids: [] })

async function pacQuickSave () {
  savingQuick.value = true
  try {
    const res = await proxy.$axios.post('pacientes', pacQ.value)
    opcionesPaciente.value = [res.data, ...opcionesPaciente.value]
    nueva.value.paciente_id = res.data.id
    pacQuick.value = false
    pacQ.value = { nombre_completo: '', ci: '', sexo: null, telefono: '' }
    proxy.$alert.success('Paciente creado')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al crear paciente')
  } finally {
    savingQuick.value = false
  }
}

function abrirDocQuick () {
  if (!especialidades.value.length) loadEspecialidades()
  docQuick.value = true
}

async function docQuickSave () {
  savingQuick.value = true
  try {
    const res = await proxy.$axios.post('doctores', docQ.value)
    opcionesDoctor.value = [res.data, ...opcionesDoctor.value]
    nueva.value.doctor_id = res.data.id
    docQuick.value = false
    docQ.value = { nombre: '', especialidad_ids: [] }
    proxy.$alert.success('Doctor creado')
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al crear doctor')
  } finally {
    savingQuick.value = false
  }
}

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadVentas()
  loadProductos()
  loadTiposProducto()
  loadSeguros()
  loadEspecialidades()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })
</script>

<style scoped>
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__control),
.ventas-compactas :deep(.q-field--dense:not(.q-textarea) .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}

.ventas-compactas :deep(.q-field--dense .q-field__native),
.ventas-compactas :deep(.q-field--dense .q-field__input),
.ventas-compactas :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}

.ventas-compactas :deep(.q-field--dense .q-field__append),
.ventas-compactas :deep(.q-field--dense .q-field__prepend) {
  height: 30px;
}

.ventas-compactas :deep(.q-textarea.q-field--dense .q-field__control) {
  min-height: 38px;
}

.ventas-compactas :deep(.q-field__bottom) {
  min-height: 14px;
  padding-top: 2px;
  font-size: 10px;
}

.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}
</style>
