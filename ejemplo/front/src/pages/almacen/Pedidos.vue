<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Pedidos</div>
        <div class="text-caption text-grey-7">Listado de pedidos registrados en almacén</div>
      </div>
      <q-space />
      <q-btn
        v-if="canCreate"
        unelevated
        color="primary"
        icon="add_circle"
        label="Pedido nuevo"
        no-caps
        to="/pedidos/nuevo"
      />
    </div>

    <div class="row q-col-gutter-sm q-mb-sm">
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-blue">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="blue-7" text-color="white" icon="pending_actions" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Pendientes</div>
              <div class="text-h6 text-weight-bold text-blue-9">{{ summaryData.total_pendientes }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-green">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="green-6" text-color="white" icon="task_alt" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Aceptados</div>
              <div class="text-h6 text-weight-bold text-green-9">{{ summaryData.total_aceptados }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-red">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="red-6" text-color="white" icon="cancel" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Rechazados</div>
              <div class="text-h6 text-weight-bold text-red-9">{{ summaryData.total_rechazados }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-3">
        <q-card flat bordered class="summary-card summary-slate">
          <q-card-section class="row items-center no-wrap">
            <q-avatar size="48px" color="grey-8" text-color="white" icon="shopping_bag" />
            <div class="q-ml-md">
              <div class="text-caption text-grey-8">Cantidad de pedidos</div>
              <div class="text-h6 text-weight-bold text-grey-10">{{ summaryData.cantidad }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row q-col-gutter-sm items-center q-pa-sm">
        <div class="col-12 col-sm-3">
          <q-input
            v-model="filters.date_from"
            dense
            outlined
            type="date"
            label="Fecha inicio"
          >
            <template #prepend><q-icon name="event" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-3">
          <q-input
            v-model="filters.date_to"
            dense
            outlined
            type="date"
            label="Fecha fin"
          >
            <template #prepend><q-icon name="event" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-2">
          <q-select
            v-model="filters.estado"
            :options="estadoFilterOptions"
            dense
            outlined
            clearable
            emit-value
            map-options
            label="Estado"
          />
        </div>
        <div class="col-12 col-sm">
          <q-input
            v-model="filters.q"
            dense
            outlined
            clearable
            debounce="350"
            label="Buscar usuario o ID"
            @update:model-value="applyFilters"
          >
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn
            unelevated
            color="primary"
            icon="search"
            label="Buscar"
            no-caps
            :loading="loading"
            @click="applyFilters"
          />
        </div>
        <div class="col-auto">
          <q-btn
            flat
            color="grey-8"
            icon="refresh"
            label="Limpiar"
            no-caps
            @click="clearFilters"
          />
        </div>
      </q-card-section>

      <q-separator />

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
        <template #body-cell-id="props">
          <q-td :props="props">
            <q-badge color="grey-3" text-color="grey-9" class="text-weight-medium">
              #{{ props.row.id }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-nombre_usuario="props">
          <q-td :props="props">
            <div class="row items-center no-wrap">
              <q-avatar size="32px" color="primary" text-color="white" icon="person" class="q-mr-sm" />
              <div class="column">
                <div class="text-weight-medium">{{ props.row.nombre_usuario || '-' }}</div>
                <div class="text-caption text-grey-7">
                  {{ props.row.detalles_count || 0 }} items · {{ money(props.row.total) }} Bs
                </div>
                <div v-if="detallesPreview(props.row)" class="text-caption text-grey-7 preview-text">
                  {{ detallesPreview(props.row) }}
                </div>
                <div v-if="props.row.comentario" class="text-caption text-grey-7 comment-text" :title="props.row.comentario">
                  {{ props.row.comentario }}
                </div>
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-unidad="props">
          <q-td :props="props">
            <div v-if="props.row.unidad" class="row items-center no-wrap">
              <q-icon name="apartment" size="14px" color="teal-8" class="q-mr-xs" />
              <div class="text-caption text-weight-medium" style="max-width: 200px; white-space: normal; line-height: 1.2">{{ props.row.unidad.nombre }}</div>
            </div>
            <span v-else class="text-grey-5">-</span>
          </q-td>
        </template>

        <template #body-cell-fecha_hora="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ formatDate(props.row.fecha_hora) }}</div>
            <div class="text-caption text-grey-7">{{ formatTime(props.row.fecha_hora) }}</div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge :color="estadoColor(props.row.estado)" class="q-pa-xs text-weight-bold">
              {{ props.row.estado }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-modificado="props">
          <q-td :props="props">
            <q-chip
              dense
              square
              :color="props.row.modificado ? 'amber-2' : 'grey-3'"
              :text-color="props.row.modificado ? 'amber-10' : 'grey-8'"
            >
              {{ props.row.modificado ? 'Sí' : 'No' }}
            </q-chip>
            <div
              v-if="props.row.modificacion_detalle"
              class="text-caption text-grey-7 q-mt-xs modificacion-text"
              :title="props.row.modificacion_detalle"
            >
              {{ props.row.modificacion_detalle }}
            </div>
          </q-td>
        </template>

        <template #body-cell-total="props">
          <q-td :props="props" class="text-right">
            <span class="text-weight-bold text-primary">{{ money(props.row.total) }} Bs</span>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn-dropdown
              unelevated
              color="primary"
              text-color="white"
              no-caps
              size="sm"
              label="Opciones"
              icon="settings"
            >
              <q-list dense>
                <q-item clickable v-close-popup @click="viewPedido(props.row)">
                  <q-item-section avatar><q-icon name="visibility" color="primary" /></q-item-section>
                  <q-item-section>Ver detalle</q-item-section>
                </q-item>
                <q-item v-if="canEdit" clickable v-close-popup @click="openCambiarUnidad(props.row)">
                  <q-item-section avatar><q-icon name="apartment" color="teal-7" /></q-item-section>
                  <q-item-section>Cambiar unidad</q-item-section>
                </q-item>
                <q-item
                  v-if="canPrint"
                  clickable
                  v-close-popup
                  :disable="printingId === props.row.id"
                  @click="printPedido(props.row.id)"
                >
                  <q-item-section avatar>
                    <q-spinner v-if="printingId === props.row.id" color="teal" size="20px" />
                    <q-icon v-else name="print" color="teal" />
                  </q-item-section>
                  <q-item-section>Imprimir</q-item-section>
                </q-item>
<!--                <q-item-->
<!--                  v-if="canPrint"-->
<!--                  clickable-->
<!--                  v-close-popup-->
<!--                  :disable="whatsappId === props.row.id"-->
<!--                  @click="sendPedidoWhatsApp(props.row.id)"-->
<!--                >-->
<!--                  <q-item-section avatar>-->
<!--                    <q-spinner v-if="whatsappId === props.row.id" color="green" size="20px" />-->
<!--                    <q-icon v-else name="send" color="green" />-->
<!--                  </q-item-section>-->
<!--                  <q-item-section>Enviar por WhatsApp</q-item-section>-->
<!--                </q-item>-->
                <q-separator v-if="canDelete" />
                <q-item
                  v-if="canDelete"
                  clickable
                  v-close-popup
                  :disable="props.row.estado !== 'PENDIENTE'"
                  @click="deletePedido(props.row.id)"
                >
                  <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                  <q-item-section class="text-negative">Anular</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="showDetailDialog">
      <q-card class="detail-dialog">
        <div class="detail-header">
          <div class="row items-center no-wrap">
            <q-icon name="receipt_long" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">Detalle de pedido #{{ selectedPedido?.id }}</div>
              <div class="text-caption text-grey-3">{{ formatDateTime(selectedPedido?.fecha_hora) }}</div>
            </div>
            <q-space />
            <q-badge
              v-if="selectedPedido"
              :color="estadoColor(selectedPedido.estado)"
              class="q-pa-sm text-weight-bold q-mr-sm"
            >
              {{ selectedPedido.estado }}
            </q-badge>
            <q-btn flat round dense icon="close" color="white" @click="showDetailDialog = false" />
          </div>
        </div>

        <div class="detail-content">
          <q-card-section v-if="selectedPedido" class="q-pa-sm">
            <div class="meta-grid">
              <div class="meta-item">
                <q-icon name="badge" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">ID</div>
                  <div class="meta-value">#{{ selectedPedido.id }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="person" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Usuario</div>
                  <div class="row items-center no-wrap">
                    <div class="meta-value">{{ selectedPedido.nombre_usuario || selectedPedido.user?.name || '-' }}</div>
                    <q-btn
                      v-if="selectedPedido.nombre_usuario"
                      flat
                      round
                      dense
                      icon="history"
                      color="blue-7"
                      size="sm"
                      class="q-ml-xs"
                      @click="openHistorial(selectedPedido)"
                    >
                      <q-tooltip>Ver historial del usuario</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="event" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Fecha y hora</div>
                  <div class="meta-value">{{ formatDateTime(selectedPedido.fecha_hora) }}</div>
                </div>
              </div>
              <div class="meta-item" :class="selectedPedido.modificado ? 'meta-item--modified' : ''">
                <q-icon name="edit_note" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Modificado</div>
                  <div class="meta-value">{{ selectedPedido.modificado ? 'Sí' : 'No' }}</div>
                  <div v-if="selectedPedido.modificacion_detalle" class="q-mt-xs">
                    <div
                      v-for="(c, i) in parseModificacionDetalle(selectedPedido.modificacion_detalle)"
                      :key="i"
                      class="mod-row"
                    >
                      <q-icon
                        :name="c.tipo === 'quito' ? 'remove_circle' : c.tipo === 'agrego' ? 'add_circle' : 'swap_horiz'"
                        :color="c.tipo === 'quito' ? 'red-7' : c.tipo === 'agrego' ? 'green-7' : 'amber-8'"
                        size="13px"
                        class="q-mr-xs"
                      />
                      <span :class="c.tipo === 'quito' ? 'text-red-9' : c.tipo === 'agrego' ? 'text-green-9' : 'text-amber-9'">
                        {{ c.texto }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="selectedPedido.unidad" class="meta-item">
                <q-icon name="apartment" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Unidad</div>
                  <div class="meta-value">{{ selectedPedido.unidad.nombre }}</div>
                </div>
              </div>
              <div class="meta-item">
                <q-icon name="payments" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Total</div>
                  <div class="meta-value">{{ money(selectedPedido.total) }} Bs</div>
                </div>
              </div>
              <div v-if="selectedPedido.comentario" class="meta-item">
                <q-icon name="comment" size="20px" class="meta-icon" />
                <div class="meta-content">
                  <div class="meta-label">Comentario</div>
                  <div class="meta-value">{{ selectedPedido.comentario }}</div>
                </div>
              </div>
            </div>

          </q-card-section>

          <q-separator />

          <q-card-section v-if="selectedPedido" class="q-pa-sm">

            <div class="row items-center q-mb-sm">
              <q-icon name="inventory_2" size="18px" color="primary" class="q-mr-xs" />
              <div class="text-subtitle2 text-weight-bold">Productos</div>
              <q-space />
              <q-chip dense color="primary" text-color="white" :label="`${detailItems.length} items`" />
            </div>

            <div v-if="editingItems" class="row q-col-gutter-sm items-end q-mb-sm">
              <div class="col-12 col-sm">
                <q-select
                  v-model="productToAdd"
                  dense
                  outlined
                  clearable
                  use-input
                  fill-input
                  hide-selected
                  input-debounce="300"
                  label="Agregar producto"
                  :options="productOptions"
                  option-label="label"
                  :loading="loadingProductOptions"
                  @filter="filterAddProducts"
                  popup-content-style="max-height: 320px"
                >
                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar style="min-width:44px">
                        <q-img
                          :src="productoImgUrl(scope.opt.imagen)"
                          width="40px" height="40px"
                          fit="cover"
                          style="border-radius:6px; border:1px solid #e5eaf2"
                        />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label class="text-weight-medium">{{ scope.opt.nombre }}</q-item-label>
                        <q-item-label caption>
                          {{ scope.opt.unidad_medida || '-' }}
                          <span v-if="scope.opt.subpartida" class="q-ml-xs text-grey-6">
                            · {{ scope.opt.subpartida.codigo }} {{ scope.opt.subpartida.nombre }}
                          </span>
                        </q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-chip
                          dense size="sm"
                          :color="scope.opt.cantidad > 0 ? 'teal' : 'grey-5'"
                          text-color="white"
                        >
                          {{ scope.opt.cantidad }} disp.
                        </q-chip>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-6 col-sm-2">
                <q-input
                  v-model.number="productAddQty"
                  dense
                  outlined
                  type="number"
                  min="1"
                  label="Cantidad"
                />
              </div>
              <div class="col-6 col-sm-auto">
                <q-btn
                  unelevated
                  no-caps
                  color="primary"
                  icon="add"
                  label="Agregar"
                  @click="addProductToDraft"
                />
              </div>
            </div>

            <div class="detail-items">
              <div
                v-for="det in detailItems"
                :key="det.id"
                class="detail-item"
              >
                <q-img
                  :src="itemImageUrl(det)"
                  class="detail-item-img"
                  fit="cover"
                  no-spinner
                />
                <div class="detail-item-info">
                  <div class="detail-item-name">
                    {{ det.producto?.nombre || det.nombre || '-' }}
                    <q-chip
                      v-if="det.producto?.cantidad != null && canVerStock"
                      dense
                      size="sm"
                      :color="det.producto.cantidad > 0 ? 'teal-1' : 'red-1'"
                      :text-color="det.producto.cantidad > 0 ? 'teal-9' : 'red-9'"
                      class="q-ml-xs"
                      style="font-size:10px; padding:0 4px"
                    >
                      Stock: {{ det.producto.cantidad }}
                    </q-chip>
                  </div>
                  <div class="detail-item-meta">
                    <template v-if="editingItems">
                      <q-input
                        v-model.number="det.cantidad"
                        dense
                        outlined
                        type="number"
                        min="1"
                        class="qty-input"
                      />
                      <span>x {{ money(det.precio_unitario) }} Bs</span>
                    </template>
                    <span v-else>{{ det.cantidad }} x {{ money(det.precio_unitario) }} Bs</span>
                  </div>
                </div>
                <div class="detail-item-actions">
                  <div class="detail-item-total">{{ money(lineSubtotal(det)) }} Bs</div>
                  <q-btn
                    v-if="editingItems"
                    flat
                    dense
                    round
                    color="negative"
                    icon="delete"
                    size="sm"
                    @click="removeDraftItem(det)"
                  />
                </div>
              </div>
              <div v-if="detailItems.length === 0" class="text-center text-grey-7 q-pa-md">
                Sin productos
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section v-if="selectedPedido" class="q-pa-sm detail-summary">
            <div class="summary-row">
              <span class="summary-label">Subtotal</span>
              <span class="summary-value">{{ money(detailTotal) }} Bs</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Items</span>
              <span class="summary-value">{{ detailItems.length }}</span>
            </div>
            <q-separator class="q-my-sm" />
            <div class="summary-row total-row">
              <span class="total-label">Total</span>
              <span class="total-value">{{ money(detailTotal) }} Bs</span>
            </div>
          </q-card-section>
        </div>

        <q-separator />

        <q-card-actions class="q-pa-sm detail-actions">
          <q-space />
          <q-btn flat no-caps color="grey-8" label="Cerrar" @click="showDetailDialog = false" />

          <q-btn
            v-if="selectedPedido && canEdit && !editingItems"
            unelevated
            no-caps
            color="green"
            icon="task_alt"
            label="Aceptar"
            :disable="selectedPedido.estado !== 'PENDIENTE'"
            :loading="savingEstado === 'ACEPTADO'"
            @click="setEstadoFromDetail('ACEPTADO')"
          />
          <q-btn
            v-if="selectedPedido && canEdit && !editingItems"
            unelevated
            no-caps
            color="red"
            icon="cancel"
            label="Rechazar"
            :disable="selectedPedido.estado !== 'PENDIENTE'"
            :loading="savingEstado === 'RECHAZADO'"
            @click="setEstadoFromDetail('RECHAZADO')"
          />

          <q-btn
            v-if="selectedPedido && canEdit && !editingItems"
            unelevated
            no-caps
            color="primary"
            icon="edit"
            label="Modificar"
            :disable="selectedPedido.estado !== 'PENDIENTE'"
            @click="startEditItems"
          />
          <q-btn
            v-if="selectedPedido && canEdit && editingItems"
            flat
            no-caps
            color="grey-8"
            icon="close"
            label="Cancelar"
            :disable="savingDetail"
            @click="cancelEditItems"
          />
          <q-btn
            v-if="selectedPedido && canEdit && editingItems"
            unelevated
            no-caps
            color="primary"
            icon="save"
            label="Guardar"
            :loading="savingDetail"
            @click="saveItemChanges"
          />

          <q-btn
            v-if="selectedPedido && canDelete && !editingItems"
            unelevated
            no-caps
            color="negative"
            icon="cancel"
            label="Anular"
            :disable="selectedPedido.estado !== 'PENDIENTE'"
            @click="deletePedido(selectedPedido.id)"
          />

          <q-btn
            v-if="selectedPedido && canPrint"
            unelevated
            no-caps
            color="teal"
            icon="print"
            label="Imprimir"
            :disable="editingItems"
            :loading="printingId === selectedPedido.id"
            @click="printPedido(selectedPedido.id)"
          />
<!--          <q-btn-->
<!--            v-if="selectedPedido && canPrint"-->
<!--            unelevated-->
<!--            no-caps-->
<!--            color="green"-->
<!--            icon="send"-->
<!--            label="Enviar WhatsApp"-->
<!--            :disable="editingItems"-->
<!--            :loading="whatsappId === selectedPedido.id"-->
<!--            @click="sendPedidoWhatsApp(selectedPedido.id)"-->
<!--          />-->
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog cambiar unidad -->
    <q-dialog v-model="showUnidadDialog" persistent>
      <q-card style="min-width:400px; max-width:94vw">
        <q-card-section class="row items-center no-wrap bg-teal-8 text-white">
          <q-icon name="apartment" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Cambiar unidad</div>
            <div class="text-caption text-teal-2">Pedido #{{ unidadDialogPedido?.id }} — {{ unidadDialogPedido?.nombre_usuario }}</div>
          </div>
          <q-space />
          <q-btn flat round dense icon="close" color="white" @click="showUnidadDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-md">
          <q-select
            v-model="selectedUnidadId"
            :options="unidades"
            option-value="id"
            option-label="nombre"
            emit-value
            map-options
            outlined
            dense
            label="Seleccionar unidad"
            use-input
            input-debounce="0"
            :filter-method="(val, update) => update(() => {})"
          >
            <template #prepend><q-icon name="apartment" /></template>
          </q-select>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat no-caps color="grey-8" label="Cancelar" @click="showUnidadDialog = false" />
          <q-btn
            unelevated no-caps color="teal-8" icon="save" label="Guardar"
            :loading="savingUnidad"
            :disable="!selectedUnidadId"
            @click="saveUnidad"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showHistorialDialog">
      <q-card class="historial-dialog">
        <div class="historial-header">
          <div class="row items-center no-wrap">
            <q-icon name="history" size="24px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Historial de pedidos</div>
              <div class="text-caption text-grey-3">{{ historialUser }}</div>
            </div>
            <q-space />
            <q-chip dense color="white" text-color="blue-9" :label="`${historialPagination.rowsNumber} pedidos`" />
            <q-btn flat round dense icon="close" color="white" class="q-ml-xs" @click="showHistorialDialog = false" />
          </div>
        </div>

        <div class="historial-content">
          <div v-if="loadingHistorial" class="text-center q-pa-lg">
            <q-spinner size="40px" color="primary" />
          </div>

          <div v-else-if="historialRows.length === 0" class="text-center text-grey-6 q-pa-lg">
            <q-icon name="inbox" size="40px" class="q-mb-sm" />
            <div>Sin pedidos registrados</div>
          </div>

          <div v-else class="historial-list">
            <div v-for="pedido in historialRows" :key="pedido.id" class="historial-item">
              <div class="row items-center no-wrap q-mb-xs">
                <q-badge color="grey-3" text-color="grey-9" class="q-mr-sm text-weight-medium">#{{ pedido.id }}</q-badge>
                <div class="text-caption text-grey-8">{{ formatDate(pedido.fecha_hora) }} · {{ formatTime(pedido.fecha_hora) }}</div>
                <q-space />
                <q-badge :color="estadoColor(pedido.estado)" class="q-pa-xs text-weight-bold q-mr-sm">{{ pedido.estado }}</q-badge>
                <span class="text-weight-bold text-primary text-body2">{{ money(pedido.total) }} Bs</span>
              </div>
              <div class="historial-products">
                <div
                  v-for="det in (pedido.detalles || []).slice(0, 7)"
                  :key="det.id"
                  class="historial-product-chip"
                >
                  <q-img
                    :src="itemImageUrl(det)"
                    width="38px"
                    height="38px"
                    fit="cover"
                    class="historial-product-img"
                  >
                    <q-tooltip>{{ det.producto?.nombre || '-' }} × {{ det.cantidad }}</q-tooltip>
                  </q-img>
                  <div class="historial-product-name">{{ det.producto?.nombre || '-' }}</div>
                </div>
                <div v-if="(pedido.detalles || []).length > 7" class="historial-product-more">
                  +{{ pedido.detalles.length - 7 }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <q-separator />

        <q-card-actions class="q-pa-sm row items-center">
          <q-pagination
            v-if="historialPagination.rowsNumber > historialPagination.rowsPerPage"
            v-model="historialPagination.page"
            :max="Math.ceil(historialPagination.rowsNumber / historialPagination.rowsPerPage)"
            size="sm"
            @update:model-value="fetchHistorial"
          />
          <q-space />
          <q-btn flat no-caps color="grey-8" label="Cerrar" @click="showHistorialDialog = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import moment from 'moment'

const { proxy } = getCurrentInstance()
const $q = useQuasar()

const showDetailDialog = ref(false)
const selectedPedido = ref(null)
const printingId = ref(null)
const whatsappId = ref(null)
const editingItems = ref(false)
const itemsDraft = ref([])
const productOptions = ref([])
const loadingProductOptions = ref(false)
const productToAdd = ref(null)
const productAddQty = ref(1)
const savingDetail = ref(false)
const savingEstado = ref(null)

const showUnidadDialog = ref(false)
const unidadDialogPedido = ref(null)
const selectedUnidadId = ref(null)
const unidades = ref([])
const savingUnidad = ref(false)

const showHistorialDialog = ref(false)
const historialUser = ref('')
const historialRows = ref([])
const loadingHistorial = ref(false)
const historialPagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })

const loading = ref(false)
const rows = ref([])
const summary = ref({
  total_pendientes: 0,
  total_aceptados: 0,
  total_rechazados: 0,
  cantidad: 0,
})

const pagination = ref({
  sortBy: 'id',
  descending: true,
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
})

const filters = ref({
  date_from: moment().format('YYYY-MM-DD'),
  date_to: moment().format('YYYY-MM-DD'),
  estado: null,
  q: ''
})

const estadoEditOptions = [
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Aceptado', value: 'ACEPTADO' },
  { label: 'Rechazado', value: 'RECHAZADO' }
]

const estadoFilterOptions = [
  ...estadoEditOptions,
  { label: 'Anulado', value: 'ANULADO' }
]

const columns = [
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'left', style: 'width: 130px' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
  { name: 'id', label: 'ID', field: 'id', align: 'left', style: 'width: 80px' },
  { name: 'fecha_hora', label: 'Fecha', field: 'fecha_hora', align: 'left' },
  { name: 'nombre_usuario', label: 'Usuario', field: 'nombre_usuario', align: 'left' },
  { name: 'unidad', label: 'Unidad', field: 'unidad', align: 'left' },
  { name: 'modificado', label: 'Modificado', field: 'modificado', align: 'left' },
  { name: 'total', label: 'Total', field: 'total', align: 'right' }
]


const userPermissions = computed(() => proxy.$store.permissions || [])
const currentUser = computed(() => proxy.$store.user || {})
const isAdminUser = computed(() => currentUser.value?.role === 'Administrador')

const canSeeAll = computed(() => isAdminUser.value || userPermissions.value.includes('Ver todos los pedidos'))
const canVerStock = computed(() => isAdminUser.value || userPermissions.value.includes('Módulo inventario'))
const canCreate = computed(() => isAdminUser.value || userPermissions.value.includes('Crear Pedidos'))
const canEdit = computed(() => isAdminUser.value || userPermissions.value.includes('Editar Pedidos'))
const canDelete = computed(() => isAdminUser.value || userPermissions.value.includes('Anular Pedidos'))
const canPrint = computed(() => isAdminUser.value || userPermissions.value.includes('Imprimir Pedidos'))

const summaryData = computed(() => ({
  total_pendientes: summary.value.total_pendientes ?? rows.value.filter(p => p.estado === 'PENDIENTE').length,
  total_aceptados: summary.value.total_aceptados ?? rows.value.filter(p => p.estado === 'ACEPTADO').length,
  total_rechazados: summary.value.total_rechazados ?? rows.value.filter(p => p.estado === 'RECHAZADO').length,
  cantidad: summary.value.cantidad ?? rows.value.length
}))

const detailItems = computed(() => {
  return editingItems.value ? itemsDraft.value : (selectedPedido.value?.detalles || [])
})

function lineSubtotal (det) {
  const cantidad = Number(det?.cantidad || 0)
  const precio = Number(det?.precio_unitario || 0)
  return Number.isFinite(cantidad) && Number.isFinite(precio) ? cantidad * precio : 0
}

const detailTotal = computed(() => {
  return detailItems.value.reduce((sum, det) => sum + lineSubtotal(det), 0)
})

function detallesPreview (pedido) {
  const detalles = pedido?.detalles || []
  if (!Array.isArray(detalles) || detalles.length === 0) return ''

  const parts = detalles.slice(0, 3).map((d) => {
    const nombre = d?.producto?.nombre || d?.nombre || '-'
    const cantidad = Number(d?.cantidad || 0)
    return `${nombre} x${cantidad}`
  })

  return detalles.length > 3 ? `${parts.join(', ')}...` : parts.join(', ')
}

onMounted(async () => {
  await fetchRows()
})

async function fetchRows () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('pedidos', {
      params: {
        page: pagination.value.page,
        rowsPerPage: pagination.value.rowsPerPage,
        ...filters.value,
      },
    })

    rows.value = res.data.data || []
    pagination.value.rowsNumber = res.data.total || 0
    summary.value = res.data.summary || summary.value
  } finally {
    loading.value = false
  }
}

async function applyFilters () {
  pagination.value.page = 1
  await fetchRows()
}

function clearFilters () {
  filters.value = {
    date_from: moment().format('YYYY-MM-DD'),
    date_to: moment().format('YYYY-MM-DD'),
    estado: null,
    q: ''
  }
  applyFilters()
}

async function onRequest (props) {
  pagination.value = props.pagination
  await fetchRows()
}

async function viewPedido (pedido) {
  const res = await proxy.$axios.get(`pedidos/${pedido.id}`)
  selectedPedido.value = res.data
  editingItems.value = false
  itemsDraft.value = []
  resetDraftProductForm()
  showDetailDialog.value = true
}

function startEditItems () {
  if (!selectedPedido.value) return
  const detalles = selectedPedido.value.detalles || []
  itemsDraft.value = detalles.map((d) => ({
    id: d.id,
    producto_id: d.producto_id ?? d.producto?.id,
    producto: d.producto,
    cantidad: Number(d.cantidad || 0),
    precio_unitario: Number(d.precio_unitario || 0),
  }))
  editingItems.value = true
  resetDraftProductForm()
  loadProductOptions().catch(() => {})
}

function cancelEditItems () {
  editingItems.value = false
  itemsDraft.value = []
  resetDraftProductForm()
}

function hasItemChanges () {
  const original = selectedPedido.value?.detalles || []
  if (original.length !== itemsDraft.value.length) return true

  const originalById = new Map(original.map((d) => [d.id, d]))
  for (const draft of itemsDraft.value) {
    const orig = originalById.get(draft.id)
    if (!orig) return true
    if (Number(orig.cantidad || 0) !== Number(draft.cantidad || 0)) return true
    if (Number(orig.precio_unitario || 0) !== Number(draft.precio_unitario || 0)) return true
  }

  return false
}

async function saveItemChanges () {
  if (!selectedPedido.value?.id) return
  if (!hasItemChanges()) {
    cancelEditItems()
    $q.notify({ color: 'info', message: 'Sin cambios', position: 'top' })
    return
  }

  const items = itemsDraft.value.map((d) => ({
    producto_id: d.producto_id,
    cantidad: Number(d.cantidad || 0),
    precio_unitario: Number(d.precio_unitario || 0),
  }))

  if (items.length === 0) {
    $q.notify({ color: 'negative', message: 'Debe haber al menos un producto en el pedido', position: 'top' })
    return
  }

  const missing = items.find((i) => !i.producto_id || i.cantidad < 1)
  if (missing) {
    $q.notify({ color: 'negative', message: 'Revisa las cantidades (mínimo 1) y productos', position: 'top' })
    return
  }

  savingDetail.value = true
  try {
    const res = await proxy.$axios.put(`pedidos/${selectedPedido.value.id}`, { items })
    selectedPedido.value = res.data
    editingItems.value = false
    itemsDraft.value = []
    await applyFilters()
    $q.notify({ color: 'positive', message: 'Pedido modificado', position: 'top' })
  } finally {
    savingDetail.value = false
  }
}

function resetDraftProductForm () {
  productToAdd.value = null
  productAddQty.value = 1
}

async function loadProductOptions (q = '') {
  loadingProductOptions.value = true
  try {
    const res = await proxy.$axios.get('almacen-items', {
      params: {
        q,
        page: 1,
        rowsPerPage: 30,
      },
    })
    const rows = res?.data?.data || []
    productOptions.value = rows.map((item) => ({
      label: `${item.nombre} (${item.unidad_medida || '-'})`,
      value: item.id,
      nombre: item.nombre,
      precio_unitario: Number(item.precio_unitario || 0),
      unidad_medida: item.unidad_medida,
      imagen: item.imagen,
      cantidad: Number(item.cantidad ?? 0),
      subpartida: item.subpartida ?? null,
    }))
  } finally {
    loadingProductOptions.value = false
  }
}

function filterAddProducts (val, update) {
  loadProductOptions(val)
    .then(() => update(() => {}))
    .catch(() => update(() => {}))
}

function addProductToDraft () {
  const selected = productToAdd.value
  if (!selected?.value) {
    $q.notify({ color: 'negative', message: 'Selecciona un producto', position: 'top' })
    return
  }

  const cantidad = Math.max(1, Number(productAddQty.value || 1))
  const existing = itemsDraft.value.find((d) => Number(d.producto_id) === Number(selected.value))
  if (existing) {
    existing.cantidad = Number(existing.cantidad || 0) + cantidad
  } else {
    itemsDraft.value.unshift({
      id: `new-${selected.value}-${Date.now()}`,
      producto_id: selected.value,
      producto: {
        nombre: selected.nombre,
        unidad_medida: selected.unidad_medida,
        imagen: selected.imagen,
      },
      cantidad,
      precio_unitario: Number(selected.precio_unitario || 0),
    })
  }

  resetDraftProductForm()
}

function removeDraftItem (item) {
  const itemId = item?.id
  const itemProductoId = item?.producto_id
  itemsDraft.value = itemsDraft.value.filter((d) => d.id !== itemId && d.producto_id !== itemProductoId)
}

async function setEstadoFromDetail (estado) {
  if (!selectedPedido.value?.id) return
  savingEstado.value = estado
  try {
    const res = await proxy.$axios.put(`pedidos/${selectedPedido.value.id}`, { estado })
    selectedPedido.value = res.data
    await applyFilters()
    $q.notify({ color: 'positive', message: `Pedido ${estado.toLowerCase()}`, position: 'top' })
  } finally {
    savingEstado.value = null
  }
}

function deletePedido (id) {
  $q.dialog({
    title: 'Confirmar',
    message: '¿Deseas anular este pedido?',
    cancel: true
  }).onOk(async () => {
    const res = await proxy.$axios.delete(`pedidos/${id}`)
    if (selectedPedido.value?.id === id) {
      selectedPedido.value = res.data
    }
    await applyFilters()
    $q.notify({
      color: 'positive',
      message: 'Pedido anulado',
      position: 'top'
    })
  })
}

async function printPedido (id) {
  printingId.value = id
  try {
    const res = await proxy.$axios.get(`pedidos/${id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
    window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: 'No se pudo generar la impresión',
      position: 'top'
    })
  } finally {
    printingId.value = null
  }
}

async function sendPedidoWhatsApp (id) {
  whatsappId.value = id
  try {
    const res = await proxy.$axios.get(`pedidos/${id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const disposition = (res && res.headers && res.headers['content-disposition']) || ''
    const match = disposition.match(/filename="?([^"]+)"?/)
    const fileName = (match && match[1]) || `pedido_${id}.pdf`

    const file = new File([blob], fileName, { type: 'application/pdf' })
    if (navigator.canShare && navigator.share && navigator.canShare({ files: [file] })) {
      await navigator.share({
        files: [file],
        title: fileName
      })
      return
    }

    const link = document.createElement('a')
    const fileUrl = window.URL.createObjectURL(blob)
    link.href = fileUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(fileUrl)

    $q.notify({
      color: 'warning',
      message: 'Tu navegador no permite compartir PDF directo. Se descargó el archivo para enviarlo por WhatsApp.',
      position: 'top'
    })
  } catch (error) {
    $q.notify({
      color: 'negative',
      message: error?.response?.data?.message || 'No se pudo preparar el PDF para WhatsApp',
      position: 'top'
    })
  } finally {
    whatsappId.value = null
  }
}

async function openCambiarUnidad (row) {
  unidadDialogPedido.value = row
  selectedUnidadId.value = row.unidad?.id ?? null
  if (unidades.value.length === 0) {
    const res = await proxy.$axios.get('unidades')
    unidades.value = res.data
  }
  showUnidadDialog.value = true
}

async function saveUnidad () {
  if (!selectedUnidadId.value || !unidadDialogPedido.value) return
  savingUnidad.value = true
  try {
    await proxy.$axios.patch(`pedidos/${unidadDialogPedido.value.id}/unidad`, { unidad_id: selectedUnidadId.value })
    $q.notify({ color: 'positive', message: 'Unidad actualizada', position: 'top' })
    showUnidadDialog.value = false
    if (selectedPedido.value?.id === unidadDialogPedido.value.id) {
      const res = await proxy.$axios.get(`pedidos/${unidadDialogPedido.value.id}`)
      selectedPedido.value = res.data
    }
    await applyFilters()
  } catch (e) {
    $q.notify({ color: 'negative', message: e?.response?.data?.message || 'Error al actualizar unidad', position: 'top' })
  } finally {
    savingUnidad.value = false
  }
}

function estadoColor (estado) {
  if (estado === 'ACEPTADO') return 'green'
  if (estado === 'RECHAZADO') return 'red'
  if (estado === 'ANULADO') return 'grey-7'
  return 'amber-8'
}

function money (value) {
  return new Intl.NumberFormat('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(value || 0))
}

function formatDate (value) {
  if (!value) return '-'
  return moment(value).format('DD/MM/YYYY')
}

function formatTime (value) {
  if (!value) return ''
  return moment(value).format('HH:mm')
}

function formatDateTime (value) {
  if (!value) return '-'
  return moment(value).format('DD/MM/YYYY HH:mm')
}

function itemImageUrl (det) {
  const imagen = det?.producto?.imagen || det?.imagen || 'default.png'
  return `${proxy.$url}/../images/productos/${imagen}`
}

function productoImgUrl (imagen) {
  return `${proxy.$url}/../images/productos/${imagen || 'default.png'}`
}

async function openHistorial (row) {
  historialUser.value = row.nombre_usuario || ''
  historialPagination.value.page = 1
  historialRows.value = []
  showHistorialDialog.value = true
  await fetchHistorial()
}

function parseModificacionDetalle (texto) {
  if (!texto) return []
  return texto.split('; ').map(cambio => {
    if (cambio.startsWith('Se quitó ')) return { tipo: 'quito', texto: cambio }
    if (cambio.startsWith('Se agregó ')) return { tipo: 'agrego', texto: cambio }
    return { tipo: 'cambio', texto: cambio }
  })
}

async function fetchHistorial () {
  loadingHistorial.value = true
  try {
    const res = await proxy.$axios.get('pedidos', {
      params: {
        q: historialUser.value,
        page: historialPagination.value.page,
        rowsPerPage: historialPagination.value.rowsPerPage,
      },
    })
    historialRows.value = res.data.data || []
    historialPagination.value.rowsNumber = res.data.total || 0
  } finally {
    loadingHistorial.value = false
  }
}

</script>

<style scoped>
.summary-card {
  border-radius: 10px;
}

.summary-blue { border-left: 4px solid #1976d2; }
.summary-green { border-left: 4px solid #43a047; }
.summary-red { border-left: 4px solid #c90022; }
.summary-slate { border-left: 4px solid #455a64; }

.detail-dialog {
  width: 760px;
  max-width: 94vw;
  max-height: 92vh;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.detail-content {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}

.detail-actions {
  flex: 0 0 auto;
  background: #fff;
  position: sticky;
  bottom: 0;
  z-index: 2;
}

.detail-header {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: #fff;
  padding: 16px 18px;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

.detail-items {
  border: 1px solid #e5eaf2;
  border-radius: 8px;
  background: #fff;
  padding: 4px;
  max-height: 320px;
  overflow-y: auto;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 10px;
  border-bottom: 1px solid #f0f2f5;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-item-img {
  flex: 0 0 auto;
  width: 56px;
  height: 56px;
  border-radius: 6px;
  border: 1px solid #e5eaf2;
  background: #f5f5f5;
}

.detail-item-info {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.detail-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  text-transform: capitalize;
  line-height: 1.2;
  word-break: break-word;
}

.detail-item-meta {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.qty-input {
  width: 84px;
}

.qty-input :deep(.q-field__control) {
  height: 32px;
}

.preview-text,
.comment-text {
  max-width: 520px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modificacion-text {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #b45309;
}

.meta-item--modified {
  border-color: #fbbf24;
  background: #fffbeb;
}

.mod-row {
  display: flex;
  align-items: center;
  font-size: 11px;
  font-weight: 500;
  line-height: 1.6;
}

.detail-item-total {
  font-size: 14px;
  font-weight: 700;
  color: #1976d2;
  white-space: nowrap;
}

.detail-item-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.detail-summary {
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

.historial-dialog {
  width: 680px;
  max-width: 96vw;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 88vh;
}

.historial-header {
  background: linear-gradient(135deg, #0277bd 0%, #01579b 100%);
  color: #fff;
  padding: 14px 18px;
  flex: 0 0 auto;
}

.historial-content {
  flex: 1 1 auto;
  overflow-y: auto;
  min-height: 0;
}

.historial-list {
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.historial-item {
  background: #fff;
  border: 1px solid #e5eaf2;
  border-radius: 8px;
  padding: 10px 12px;
}

.historial-products {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 6px;
}

.historial-product-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  width: 56px;
}

.historial-product-img {
  border-radius: 6px;
  border: 1px solid #e5eaf2;
  background: #f5f5f5;
}

.historial-product-name {
  font-size: 10px;
  color: #4b5563;
  text-align: center;
  line-height: 1.2;
  max-width: 56px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.historial-product-more {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 6px;
  background: #e3f2fd;
  color: #1976d2;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid #bbdefb;
  align-self: flex-start;
}
</style>
