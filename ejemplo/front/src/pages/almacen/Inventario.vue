<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="inventory-toolbar">
        <div>
          <div class="text-h6 text-weight-bold">Inventario</div>
          <div class="text-caption text-grey-7">Items del clasificador presupuestario</div>
        </div>
        <q-space />
        <q-btn-dropdown
          color="primary"
          icon="bar_chart"
          label="Reportes"
          no-caps
          dense
          :loading="!!reportLoading"
          :disable="!!reportLoading"
        >
          <q-list dense style="min-width: 210px">
            <q-item clickable v-close-popup :disable="!!reportLoading" @click="printReport(false)">
              <q-item-section avatar>
                <q-spinner v-if="reportLoading === 'all'" color="primary" size="20px" />
                <q-icon v-else name="picture_as_pdf" color="negative" />
              </q-item-section>
              <q-item-section>{{ reportLoading === 'all' ? 'Generando...' : 'PDF — Todo' }}</q-item-section>
            </q-item>
            <q-item clickable v-close-popup :disable="!!reportLoading" @click="printReport(true)">
              <q-item-section avatar>
                <q-spinner v-if="reportLoading === 'existing'" color="primary" size="20px" />
                <q-icon v-else name="picture_as_pdf" color="orange" />
              </q-item-section>
              <q-item-section>{{ reportLoading === 'existing' ? 'Generando...' : 'PDF — Existente' }}</q-item-section>
            </q-item>
            <q-separator />
            <q-item clickable v-close-popup :disable="!!reportLoading" @click="downloadExcel(false)">
              <q-item-section avatar>
                <q-spinner v-if="reportLoading === 'excel-all'" color="positive" size="20px" />
                <q-icon v-else name="grid_on" color="positive" />
              </q-item-section>
              <q-item-section>{{ reportLoading === 'excel-all' ? 'Generando...' : 'Excel — Todo' }}</q-item-section>
            </q-item>
            <q-item clickable v-close-popup :disable="!!reportLoading" @click="downloadExcel(true)">
              <q-item-section avatar>
                <q-spinner v-if="reportLoading === 'excel-existing'" color="teal" size="20px" />
                <q-icon v-else name="grid_on" color="teal" />
              </q-item-section>
              <q-item-section>{{ reportLoading === 'excel-existing' ? 'Generando...' : 'Excel — Existente' }}</q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
        <q-btn
          color="deep-orange" icon="link_off" no-caps dense
          @click="openSinVincular"
        >
          <span>Sin vincular</span>
          <q-badge v-if="sinVincularCount > 0" color="white" text-color="deep-orange"
                   floating :label="sinVincularCount" style="font-size:10px" />
        </q-btn>
        <q-btn color="indigo" icon="account_tree" label="Catalogo" no-caps dense @click="openCatalogManager" />
        <q-btn color="positive" icon="add_circle_outline" label="Nuevo item" no-caps dense @click="openItemDialog()" />
        <q-btn dense flat round icon="refresh" :loading="loading" @click="reloadAll">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="filters.grupo_id"
              :options="grupoOptions"
              dense
              outlined
              clearable
              emit-value
              map-options
              label="Grupo"
              @update:model-value="onGrupoChange"
            />
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="filters.partida_id"
              :options="partidaOptions"
              dense
              outlined
              clearable
              emit-value
              map-options
              label="Partida"
              @update:model-value="onPartidaChange"
            />
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <q-select
              v-model="filters.subpartida_id"
              :options="subpartidaOptions"
              dense
              outlined
              clearable
              emit-value
              map-options
              label="Subpartida"
              @update:model-value="resetAndFetchItems"
            />
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <q-input v-model="filters.q" dense outlined clearable label="Buscar" debounce="450" @update:model-value="resetAndFetchItems">
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pa-sm q-pt-none">
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-sm-6 col-md-3">
            <div class="summary-card">
              <div class="text-caption text-grey-7">Grupo</div>
              <div class="summary-title">{{ selectedGrupoLabel }}</div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="summary-card">
              <div class="text-caption text-grey-7">Partida</div>
              <div class="summary-title">{{ selectedPartidaLabel }}</div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="summary-card">
              <div class="text-caption text-grey-7">Subpartida</div>
              <div class="summary-title">{{ selectedSubpartidaLabel }}</div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="summary-card summary-card--strong">
              <div class="row items-center no-wrap">
                <div>
                  <div class="text-caption text-grey-7">Items / Existencia</div>
                  <div class="summary-title">{{ summary.items }} items</div>
                </div>
                <q-space />
                <q-badge color="primary" class="text-bold">
                  {{ quantity(summary.cantidad) }}
                </q-badge>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-table
        v-model:pagination="pagination"
        dense
        flat
        bordered
        row-key="id"
        class="inventory-table"
        :rows="items"
        :columns="columns"
        :loading="loading"
        :rows-per-page-options="[10, 15, 25, 50]"
        binary-state-sort
        @request="onTableRequest"
      >
        <template #body-cell-imagen="props">
          <q-td :props="props">
            <div
              class="table-img-drop"
              :class="{ 'table-img-drop--over': draggingDirectId === props.row.id }"
              @dragover.prevent="draggingDirectId = props.row.id"
              @dragleave.prevent="draggingDirectId = null"
              @drop.prevent="onTableImageDrop($event, props.row)"
            >
              <q-avatar rounded size="42px" class="item-image">
                <q-spinner-dots v-if="uploadingImageId === props.row.id" color="primary" size="24px" />
                <q-img v-else :src="itemImageUrl(props.row)" ratio="1" fit="cover" />
              </q-avatar>
              <div class="table-img-drop__hint">
                <q-icon name="upload" size="14px" color="white" />
              </div>
            </div>
          </q-td>
        </template>

        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown dense color="primary" label="Opciones" no-caps size="sm">
              <q-list dense style="min-width: 150px">
                <q-item clickable v-close-popup @click="openItemDialog(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section>Editar</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="openHistoryDialog(props.row)">
                  <q-item-section avatar><q-icon name="history" color="primary" /></q-item-section>
                  <q-item-section>Historial</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="deleteItem(props.row)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section class="text-negative">Eliminar</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <template #body-cell-clasificador="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              {{ props.row.subpartida?.codigo }} - {{ props.row.subpartida?.nombre }}
            </div>
            <div class="text-caption text-grey-7 ellipsis">
              {{ props.row.subpartida?.partida?.codigo }} - {{ props.row.subpartida?.partida?.nombre }}
            </div>
            <div class="text-caption text-grey-6 ellipsis">
              {{ props.row.subpartida?.partida?.grupo?.codigo }} - {{ props.row.subpartida?.partida?.grupo?.nombre }}
            </div>
          </q-td>
        </template>

        <template #body-cell-nombre="props">
          <q-td :props="props">
            <div class="inventory-name">{{ props.row.nombre }}</div>
          </q-td>
        </template>

        <template #body-cell-precio_unitario="props">
          <q-td :props="props">
            <q-badge color="blue-1" text-color="primary">{{ money(props.row.precio_unitario) }}</q-badge>
          </q-td>
        </template>

        <template #body-cell-cantidad="props">
          <q-td :props="props">
            <q-badge
              :color="Number(props.row.cantidad || 0) > 0 ? 'green-1' : 'grey-3'"
              :text-color="Number(props.row.cantidad || 0) > 0 ? 'green-9' : 'grey-7'"
              class="text-bold"
            >
              {{ quantity(props.row.cantidad) }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="catalogManagerDialog" persistent maximized>
      <q-card>
        <q-card-section class="row items-center q-py-sm">
          <div>
            <div class="text-subtitle1 text-weight-bold">Catalogo presupuestario</div>
            <div class="text-caption text-grey-7">Grupos, partidas y subpartidas</div>
          </div>
          <q-space />
          <q-btn dense flat round icon="close" @click="catalogManagerDialog = false" />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-pa-sm">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-3">
              <q-btn-toggle
                v-model="catalogTab"
                spread
                dense
                unelevated
                toggle-color="primary"
                :options="[
                  { label: 'Grupos', value: 'grupos' },
                  { label: 'Partidas', value: 'partidas' },
                  { label: 'Subpartidas', value: 'subpartidas' },
                ]"
              />
            </div>
            <div class="col-12 col-md">
              <q-input v-model="catalogFilter" dense outlined clearable label="Buscar catalogo">
                <template #prepend><q-icon name="search" /></template>
              </q-input>
            </div>
            <div class="col-12 col-md-auto">
              <q-btn color="positive" icon="add" label="Nuevo" no-caps dense @click="openCatalogForm()" />
            </div>
          </div>
        </q-card-section>

        <q-table
          dense
          flat
          bordered
          row-key="id"
          :rows="filteredCatalogRows"
          :columns="catalogColumns"
          :rows-per-page-options="[15, 25, 50, 0]"
        >
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown dense color="primary" label="Opciones" no-caps size="sm">
                <q-list dense style="min-width: 150px">
                  <q-item clickable v-close-popup @click="openCatalogForm(props.row)">
                    <q-item-section avatar><q-icon name="edit" /></q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="deleteCatalog(props.row)">
                    <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                    <q-item-section class="text-negative">Eliminar</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
          <template #body-cell-padre="props">
            <q-td :props="props">{{ parentLabel(props.row) }}</q-td>
          </template>
        </q-table>
      </q-card>
    </q-dialog>

    <q-dialog v-model="catalogFormDialog" persistent>
      <q-card style="width: min(92vw, 460px)">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ catalogForm.id ? 'Editar' : 'Nuevo' }} {{ catalogSingular }}
          </div>
          <q-space />
          <q-btn dense flat round icon="close" @click="catalogFormDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saveCatalog">
            <q-select
              v-if="catalogTab === 'partidas'"
              v-model="catalogForm.grupo_id"
              :options="grupoOptions"
              dense
              outlined
              emit-value
              map-options
              label="Grupo"
              :rules="[val => !!val || 'Campo requerido']"
            />
            <q-select
              v-if="catalogTab === 'subpartidas'"
              v-model="catalogForm.partida_id"
              :options="allPartidaOptions"
              dense
              outlined
              emit-value
              map-options
              label="Partida"
              :rules="[val => !!val || 'Campo requerido']"
            />
            <q-input v-model.number="catalogForm.num" dense outlined type="number" label="Num" :rules="[val => val !== null && val !== '' || 'Campo requerido']" />
            <q-input v-model="catalogForm.codigo" dense outlined label="Codigo" :rules="[val => !!val || 'Campo requerido']" />
            <q-input v-model="catalogForm.nombre" dense outlined label="Nombre" :rules="[val => !!val || 'Campo requerido']" />
            <div class="text-right q-mt-sm">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="catalogFormDialog = false" />
              <q-btn color="primary" label="Guardar" no-caps type="submit" :loading="savingCatalog" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="itemDialog" persistent>
      <q-card style="width: min(92vw, 560px)">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ itemForm.id ? 'Editar item' : 'Nuevo item' }}
          </div>
          <q-space />
          <q-btn dense flat round icon="close" @click="itemDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit="saveItem">
            <q-select
              v-model="itemGrupoId"
              :options="itemGrupoOptions"
              dense
              outlined
              clearable
              emit-value
              map-options
              use-input
              input-debounce="0"
              label="Grupo"
              @filter="filterGrupoOptions"
              @update:model-value="onItemGrupoChange"
            />
            <q-select
              v-model="itemPartidaId"
              :options="itemPartidaOptions"
              dense
              outlined
              clearable
              emit-value
              map-options
              use-input
              input-debounce="0"
              label="Partida"
              @filter="filterItemPartidaOptions"
              @update:model-value="onItemPartidaChange"
            />
            <q-select
              v-model="itemForm.subpartida_id"
              :options="itemSubpartidaOptions"
              dense
              outlined
              emit-value
              map-options
              use-input
              input-debounce="0"
              label="Subpartida"
              @filter="filterItemSubpartidaOptions"
              :rules="[val => !!val || 'Campo requerido']"
            />
            <q-input v-model="itemForm.nombre" dense outlined label="Nombre" :rules="[val => !!val || 'Campo requerido']" />
            <q-input v-model="itemForm.unidad_medida" dense outlined label="Unidad de medida" />
            <q-input v-model.number="itemForm.precio_unitario" dense outlined type="number" step="0.01" label="Precio unitario" />
            <div class="row q-col-gutter-sm items-center">
              <div class="col-auto">
                <q-avatar rounded size="74px" class="item-image-preview">
                  <q-img :src="itemPreviewUrl" ratio="1" fit="cover" />
                </q-avatar>
              </div>
              <div class="col">
                <q-file
                  v-model="itemImageFile"
                  dense
                  outlined
                  clearable
                  accept="image/*"
                  label="Imagen producto"
                  @update:model-value="onItemImageChange"
                >
                  <template #prepend>
                    <q-icon name="image" />
                  </template>
                </q-file>
              </div>
            </div>
            <div
              class="item-image-dropzone q-mt-sm"
              :class="{ 'item-image-dropzone--over': draggingItemImage }"
              ref="itemImageDropzone"
              tabindex="0"
              @click="focusItemImageDropzone"
              @dragover.prevent="onItemImageDragOver"
              @dragleave.prevent="onItemImageDragLeave"
              @drop.prevent="onItemImageDrop"
              @paste="onItemImagePaste"
            >
              <input
                ref="itemImageInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onItemImageInputChange"
              >
              <div class="column items-center justify-center full-height">
                <q-icon name="add_photo_alternate" size="34px" color="grey-7" />
                <div class="text-caption text-grey-7 q-mt-xs">
                  Cambiar foto: arrastra o pega (Ctrl+V)
                </div>
                <q-btn
                  class="q-mt-sm"
                  dense
                  outline
                  no-caps
                  color="primary"
                  icon="upload"
                  label="Seleccionar"
                  @click.stop="pickItemImageFile"
                />
              </div>
            </div>
            <div class="text-right q-mt-sm">
              <q-btn flat color="negative" label="Cancelar" no-caps @click="itemDialog = false" />
              <q-btn color="primary" label="Guardar" no-caps type="submit" :loading="savingItem" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="historyDialog">
      <q-card class="history-dialog">
        <q-card-section class="history-header">
          <div class="row items-center no-wrap">
            <q-icon name="history" size="28px" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Historial</div>
              <div class="text-caption text-grey-3">{{ historyItem?.nombre }}</div>
            </div>
            <q-space />
            <q-btn dense flat round icon="close" color="white" @click="historyDialog = false" />
          </div>
        </q-card-section>

        <q-tabs
          v-model="historyTab"
          dense
          active-color="primary"
          indicator-color="primary"
          align="justify"
        >
          <q-tab name="despachos" icon="local_shipping" label="Despachos" />
          <q-tab name="pedidos" icon="shopping_bag" label="Pedidos" />
          <q-tab name="solicitudesSap" icon="description" label="Sol. SAP" />
          <q-tab name="compras" icon="shopping_cart" label="Compras" />
        </q-tabs>
        <q-separator />

        <q-tab-panels v-model="historyTab" animated class="history-panels">
          <q-tab-panel name="despachos" class="q-pa-none">
            <div v-if="historyLoading.despachos" class="history-empty">
              <q-spinner color="primary" size="32px" />
              <div class="q-mt-sm">Cargando despachos...</div>
            </div>
            <div v-else-if="historyDespachos.length === 0" class="history-empty text-grey-7">
              <q-icon name="local_shipping" size="34px" />
              <div class="q-mt-sm">Sin despachos registrados para este producto</div>
            </div>
            <q-list v-else separator>
              <q-item v-for="row in historyDespachos" :key="row.id" class="history-item">
                <q-item-section>
                  <div class="row items-center q-col-gutter-sm">
                    <div class="col">
                      <div class="text-weight-bold">{{ row.nro || `#${row.id}` }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatDateTime(row.fecha_entrega) }} · {{ row.solicitante || '-' }}
                      </div>
                      <div v-if="row.servicio" class="text-caption text-grey-7">{{ row.servicio }}</div>
                    </div>
                    <div class="col-auto text-right">
                      <q-badge :color="row.estado === 'ANULADO' ? 'red' : 'green'" class="q-mb-xs">
                        {{ row.estado }}
                      </q-badge>
                      <div class="text-weight-bold text-primary">
                        {{ quantity(productDespachoDetalle(row)?.cantidad) }} {{ productDespachoDetalle(row)?.unidad || historyItem?.unidad_medida || '' }}
                      </div>
                      <div class="text-caption text-grey-7">{{ money(productDespachoDetalle(row)?.total) }} Bs</div>
                    </div>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="pedidos" class="q-pa-none">
            <div v-if="historyLoading.pedidos" class="history-empty">
              <q-spinner color="primary" size="32px" />
              <div class="q-mt-sm">Cargando pedidos...</div>
            </div>
            <div v-else-if="historyPedidos.length === 0" class="history-empty text-grey-7">
              <q-icon name="shopping_bag" size="34px" />
              <div class="q-mt-sm">Sin pedidos registrados para este producto</div>
            </div>
            <q-list v-else separator>
              <q-item v-for="row in historyPedidos" :key="row.id" class="history-item">
                <q-item-section>
                  <div class="row items-center q-col-gutter-sm">
                    <div class="col">
                      <div class="text-weight-bold">Pedido #{{ row.id }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatDateTime(row.fecha_hora) }} · {{ row.nombre_usuario || row.user?.name || '-' }}
                      </div>
                      <div v-if="row.comentario" class="text-caption text-grey-7">{{ row.comentario }}</div>
                    </div>
                    <div class="col-auto text-right">
                      <q-badge :color="pedidoEstadoColor(row.estado)" class="q-mb-xs">
                        {{ row.estado }}
                      </q-badge>
                      <div class="text-weight-bold text-primary">
                        {{ quantity(productPedidoDetalle(row)?.cantidad) }} {{ historyItem?.unidad_medida || '' }}
                      </div>
                      <div class="text-caption text-grey-7">{{ money(productPedidoDetalle(row)?.subtotal) }} Bs</div>
                    </div>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>
          <q-tab-panel name="solicitudesSap" class="q-pa-none">
            <div v-if="historyLoading.solicitudesSap" class="history-empty">
              <q-spinner color="primary" size="32px" />
              <div class="q-mt-sm">Cargando solicitudes SAP...</div>
            </div>
            <div v-else-if="historySolicitudesSap.length === 0" class="history-empty text-grey-7">
              <q-icon name="description" size="34px" />
              <div class="q-mt-sm">Sin solicitudes SAP para este producto</div>
            </div>
            <q-list v-else separator>
              <q-item v-for="row in historySolicitudesSap" :key="row.id" class="history-item">
                <q-item-section>
                  <div class="row items-center q-col-gutter-sm">
                    <div class="col">
                      <div class="text-weight-bold">{{ row.nro || `#${row.id}` }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatDateTime(row.fecha) }} · {{ row.unidad_solicitante || '-' }}
                      </div>
                      <div v-if="row.nro_cite" class="text-caption text-grey-7">CITE: {{ row.nro_cite }}</div>
                    </div>
                    <div class="col-auto text-right">
                      <q-badge :color="sapEstadoColor(row.estado)" class="q-mb-xs">{{ row.estado }}</q-badge>
                      <div class="text-weight-bold text-primary">
                        {{ quantity(productSapDetalle(row)?.cantidad) }} {{ productSapDetalle(row)?.unidad || historyItem?.unidad_medida || '' }}
                      </div>
                      <div class="text-caption text-grey-7">{{ money(productSapDetalle(row)?.total) }} Bs</div>
                    </div>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>

          <q-tab-panel name="compras" class="q-pa-none">
            <div v-if="historyLoading.compras" class="history-empty">
              <q-spinner color="primary" size="32px" />
              <div class="q-mt-sm">Cargando compras...</div>
            </div>
            <div v-else-if="historyCompras.length === 0" class="history-empty text-grey-7">
              <q-icon name="shopping_cart" size="34px" />
              <div class="q-mt-sm">Sin compras registradas para este producto</div>
            </div>
            <q-list v-else separator>
              <q-item v-for="row in historyCompras" :key="row.id" class="history-item">
                <q-item-section>
                  <div class="row items-center q-col-gutter-sm">
                    <div class="col">
                      <div class="text-weight-bold">Compra #{{ row.id }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatDateTime(row.fecha_hora) }} · {{ row.proveedor?.nombre || row.nombre || 'Sin proveedor' }}
                      </div>
                      <div v-if="row.nro_factura" class="text-caption text-grey-7">Factura: {{ row.nro_factura }}</div>
                    </div>
                    <div class="col-auto text-right">
                      <q-badge :color="row.estado === 'ACTIVO' ? 'green' : 'red'" class="q-mb-xs">{{ row.estado }}</q-badge>
                      <div class="text-weight-bold text-primary">
                        {{ quantity(productCompraDetalle(row)?.cantidad) }} {{ historyItem?.unidad_medida || '' }}
                        <span
                          v-if="Number(productCompraDetalle(row)?.cantidad_venta) > 0"
                          class="text-grey-7 text-weight-regular"
                          style="font-size: 12px"
                        >({{ quantity(productCompraDetalle(row)?.cantidad_venta) }} vendido)</span>
                      </div>
                      <div class="text-caption text-grey-7">
                        {{ money(productCompraDetalle(row)?.total) }} Bs
                        <span
                          v-if="Number(productCompraDetalle(row)?.cantidad_venta) > 0"
                          class="q-ml-xs"
                        >· Restante: <strong>{{ quantity(Number(productCompraDetalle(row)?.cantidad || 0) - Number(productCompraDetalle(row)?.cantidad_venta || 0)) }}</strong></span>
                      </div>
                    </div>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
    </q-dialog>

    <!-- ═══════════════════════════════════════════════════════
         DIÁLOGO SIN VINCULAR
         ═══════════════════════════════════════════════════════ -->
    <q-dialog v-model="sinVincularDialog" maximized>
      <q-card>
        <!-- Cabecera -->
        <q-card-section class="row items-center bg-deep-orange text-white q-pa-sm">
          <q-icon name="link_off" size="22px" class="q-mr-sm" />
          <div class="text-subtitle1 text-weight-bold">
            Productos sin vincular
            <q-badge color="white" text-color="deep-orange" class="q-ml-sm" :label="sinVincularRows.length" />
          </div>
          <q-space />
          <q-input v-model="sinVincularSearch" dense outlined dark placeholder="Buscar..." clearable
                   style="min-width:220px" class="q-mr-sm">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn flat round dense icon="close" color="white" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pa-none">
          <q-table
            :rows="sinVincularFiltered"
            :columns="sinVincularCols"
            row-key="id"
            dense flat
            :loading="sinVincularLoading"
            :rows-per-page-options="[25, 50, 0]"
            :pagination="{ rowsPerPage: 25 }"
          >
            <!-- Precio unitario -->
            <template v-slot:body-cell-precio_unitario="props">
              <q-td :props="props" class="text-right">{{ props.row.precio_unitario }}</q-td>
            </template>

            <!-- Sugerencias + acción vincular -->
            <template v-slot:body-cell-accion="props">
              <q-td :props="props" style="min-width:340px; max-width:420px">
                <div v-if="props.row._modo === 'nuevo'" class="row q-col-gutter-xs items-end">
                  <div class="col-12">
                    <q-select v-model="props.row._grupoId" :options="grupoOptions" dense outlined
                              emit-value map-options label="Grupo" clearable
                              @update:model-value="onSVGrupoChange(props.row)" />
                  </div>
                  <div class="col-12">
                    <q-select v-model="props.row._partidaId"
                              :options="props.row._partidaOpts || []"
                              dense outlined emit-value map-options label="Partida" clearable
                              @update:model-value="onSVPartidaChange(props.row)" />
                  </div>
                  <div class="col-12">
                    <q-select v-model="props.row._subpartidaId"
                              :options="props.row._subpartidaOpts || []"
                              dense outlined emit-value map-options label="Subpartida" clearable />
                  </div>
                  <div class="col-auto">
                    <q-btn dense no-caps color="positive" icon="check" label="Crear y vincular"
                           :loading="props.row._saving"
                           :disable="!props.row._subpartidaId"
                           @click="vincularNuevo(props.row)" />
                  </div>
                  <div class="col-auto">
                    <q-btn dense flat no-caps color="grey" icon="close" @click="props.row._modo = null" />
                  </div>
                </div>

                <div v-else-if="props.row._modo === 'buscar'" class="row q-col-gutter-xs items-center">
                  <div class="col">
                    <q-select
                      v-model="props.row._itemSeleccionado"
                      :options="props.row._busquedaOpts || []"
                      dense outlined clearable use-input
                      option-label="nombre" option-value="id"
                      label="Buscar ítem..."
                      input-debounce="300"
                      @filter="(val, update) => buscarItemOpts(val, update, props.row)"
                    >
                      <template v-slot:option="scope">
                        <q-item v-bind="scope.itemProps">
                          <q-item-section>
                            <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                            <q-item-label caption>{{ scope.opt.grupo_nombre }} › {{ scope.opt.subpartida_nombre }}</q-item-label>
                          </q-item-section>
                        </q-item>
                      </template>
                    </q-select>
                  </div>
                  <div class="col-auto">
                    <q-btn dense no-caps color="primary" icon="link" label="Vincular"
                           :loading="props.row._saving"
                           :disable="!props.row._itemSeleccionado"
                           @click="vincularExistente(props.row)" />
                  </div>
                  <div class="col-auto">
                    <q-btn dense flat no-caps color="grey" icon="close" @click="props.row._modo = null" />
                  </div>
                </div>

                <div v-else>
                  <!-- Sugerencias rápidas -->
                  <div v-if="props.row.sugerencias && props.row.sugerencias.length" class="q-mb-xs">
                    <q-chip
                      v-for="s in props.row.sugerencias" :key="s.id"
                      dense clickable color="blue-1" text-color="blue-9" size="sm"
                      icon="link" class="q-mr-xs q-mb-xs"
                      @click="vincularDirecto(props.row, s)"
                    >
                      <q-tooltip>{{ s.grupo_nombre }} › {{ s.subpartida_nombre }}</q-tooltip>
                      {{ s.nombre.length > 28 ? s.nombre.substring(0, 28) + '…' : s.nombre }}
                      <span style="font-size:9px" class="q-ml-xs text-grey-6">{{ s.unidad_medida }}</span>
                    </q-chip>
                  </div>
                  <!-- Botones de acción -->
                  <div class="row q-gutter-xs">
                    <q-btn dense no-caps outline color="primary" icon="search" label="Buscar ítem"
                           size="sm" @click="props.row._modo = 'buscar'; props.row._busquedaOpts = props.row.sugerencias" />
                    <q-btn dense no-caps outline color="positive" icon="add" label="Crear nuevo"
                           size="sm" @click="props.row._modo = 'nuevo'" />
                  </div>
                </div>
              </q-td>
            </template>

            <template v-slot:no-data>
              <div class="full-width row flex-center q-pa-xl text-positive">
                <q-icon name="check_circle" size="40px" class="q-mr-md" />
                <span class="text-subtitle1">¡Todos los productos están vinculados!</span>
              </div>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'InventarioPage',
  data () {
    return {
      loading: false,
      reportLoading: null,
      savingCatalog: false,
      savingItem: false,
      sinVincularDialog: false,
      sinVincularLoading: false,
      sinVincularRows: [],
      sinVincularSearch: '',
      sinVincularCount: 0,
      sinVincularCols: [
        { name: 'n',               label: '#',       field: 'n',               align: 'center', sortable: true, style: 'width:48px' },
        { name: 'nombre',          label: 'Nombre CSV',   field: 'nombre',     align: 'left',   sortable: true },
        { name: 'unidad_medida',   label: 'Unidad',  field: 'unidad_medida',   align: 'center', sortable: true, style: 'width:80px' },
        { name: 'precio_unitario', label: 'P.U.',    field: 'precio_unitario', align: 'right',  sortable: true, style: 'width:90px' },
        { name: 'saldo_final',     label: 'Stock',   field: 'saldo_final',     align: 'center', sortable: true, style: 'width:70px' },
        { name: 'accion',          label: 'Vincular a ítem del inventario', field: 'accion',  align: 'left' },
      ],
      items: [],
      grupos: [],
      grupoOptionsFiltered: [],
      itemPartidaOptionsFiltered: [],
      itemSubpartidaOptionsFiltered: [],
      itemGrupoId: null,
      itemPartidaId: null,
      itemImageFile: null,
      itemPreviewUrl: '',
      draggingItemImage: false,
      draggingDirectId: null,
      uploadingImageId: null,
      summary: {
        items: 0,
        cantidad: 0,
      },
      catalogManagerDialog: false,
      catalogFormDialog: false,
      itemDialog: false,
      historyDialog: false,
      historyTab: 'despachos',
      historyItem: null,
      historyDespachos: [],
      historyPedidos: [],
      historySolicitudesSap: [],
      historyCompras: [],
      historyLoading: {
        despachos: false,
        pedidos: false,
        solicitudesSap: false,
        compras: false,
      },
      catalogTab: 'grupos',
      catalogFilter: '',
      catalogForm: {},
      itemForm: {},
      filters: {
        grupo_id: null,
        partida_id: null,
        subpartida_id: null,
        q: '',
      },
      pagination: {
        sortBy: 'nombre',
        descending: false,
        page: 1,
        rowsPerPage: 15,
        rowsNumber: 0,
      },
      columns: [
        { name: 'actions', label: 'Acciones', field: 'id', align: 'left' },
        { name: 'imagen', label: 'Imagen', field: 'imagen', align: 'left' },
        { name: 'nombre', label: 'Item', field: 'nombre', align: 'left', sortable: true },
        { name: 'unidad_medida', label: 'Unidad', field: 'unidad_medida', align: 'left', sortable: true },
        { name: 'cantidad', label: 'Cantidad', field: 'cantidad', align: 'right', sortable: true },
        { name: 'precio_unitario', label: 'P.U.', field: 'precio_unitario', align: 'right', sortable: true },
        { name: 'clasificador', label: 'Clasificador', field: row => row.subpartida?.codigo || '', align: 'left' },
      ],
    }
  },
  computed: {
    sinVincularFiltered () {
      const q = (this.sinVincularSearch || '').toLowerCase()
      if (!q) return this.sinVincularRows
      return this.sinVincularRows.filter(r => r.nombre.toLowerCase().includes(q) || (r.unidad_medida || '').toLowerCase().includes(q))
    },
    grupoOptions () {
      return this.grupos.map(g => ({ label: `${g.codigo} - ${g.nombre}`, value: g.id }))
    },
    allPartidas () {
      return this.grupos.flatMap(g => (g.partidas || []).map(p => ({ ...p, grupo: g })))
    },
    allSubpartidas () {
      return this.allPartidas.flatMap(p => (p.subpartidas || []).map(s => ({ ...s, partida: p })))
    },
    partidaOptions () {
      return this.allPartidas
        .filter(p => !this.filters.grupo_id || p.grupo_id === this.filters.grupo_id)
        .map(p => ({ label: `${p.codigo} - ${p.nombre}`, value: p.id }))
    },
    allPartidaOptions () {
      return this.allPartidas.map(p => ({ label: `${p.codigo} - ${p.nombre}`, value: p.id }))
    },
    subpartidaOptions () {
      return this.allSubpartidas
        .filter(s => !this.filters.partida_id || s.partida_id === this.filters.partida_id)
        .map(s => ({ label: `${s.codigo} - ${s.nombre}`, value: s.id }))
    },
    allSubpartidaOptions () {
      return this.allSubpartidas.map(s => ({ label: `${s.codigo} - ${s.nombre}`, value: s.id }))
    },
    itemGrupoOptions () {
      return this.grupoOptionsFiltered.length ? this.grupoOptionsFiltered : this.grupoOptions
    },
    itemPartidaBaseOptions () {
      return this.allPartidas
        .filter(p => !this.itemGrupoId || p.grupo_id === this.itemGrupoId)
        .map(p => ({ label: `${p.codigo} - ${p.nombre}`, value: p.id }))
    },
    itemPartidaOptions () {
      return this.itemPartidaOptionsFiltered.length ? this.itemPartidaOptionsFiltered : this.itemPartidaBaseOptions
    },
    itemSubpartidaBaseOptions () {
      return this.allSubpartidas
        .filter(s => !this.itemPartidaId || s.partida_id === this.itemPartidaId)
        .map(s => ({ label: `${s.codigo} - ${s.nombre}`, value: s.id }))
    },
    itemSubpartidaOptions () {
      return this.itemSubpartidaOptionsFiltered.length ? this.itemSubpartidaOptionsFiltered : this.itemSubpartidaBaseOptions
    },
    selectedGrupoLabel () {
      const grupo = this.grupos.find(g => g.id === this.filters.grupo_id)
      return grupo ? `${grupo.codigo} - ${grupo.nombre}` : 'Todos'
    },
    selectedPartidaLabel () {
      const partida = this.allPartidas.find(p => p.id === this.filters.partida_id)
      return partida ? `${partida.codigo} - ${partida.nombre}` : 'Todas'
    },
    selectedSubpartidaLabel () {
      const subpartida = this.allSubpartidas.find(s => s.id === this.filters.subpartida_id)
      return subpartida ? `${subpartida.codigo} - ${subpartida.nombre}` : 'Todas'
    },
    catalogRows () {
      if (this.catalogTab === 'grupos') return this.grupos
      if (this.catalogTab === 'partidas') return this.allPartidas
      return this.allSubpartidas
    },
    filteredCatalogRows () {
      const q = (this.catalogFilter || '').toLowerCase().trim()
      if (!q) return this.catalogRows
      return this.catalogRows.filter(row => {
        return [row.codigo, row.nombre, this.parentLabel(row)]
          .filter(Boolean)
          .some(value => String(value).toLowerCase().includes(q))
      })
    },
    catalogColumns () {
      const columns = [
        { name: 'actions', label: 'Acciones', field: 'id', align: 'left' },
        { name: 'codigo', label: 'Codigo', field: 'codigo', align: 'left', sortable: true },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left', sortable: true },
      ]
      if (this.catalogTab !== 'grupos') columns.push({ name: 'padre', label: 'Padre', field: 'id', align: 'left' })
      return columns
    },
    catalogSingular () {
      if (this.catalogTab === 'grupos') return 'grupo'
      if (this.catalogTab === 'partidas') return 'partida'
      return 'subpartida'
    },
    catalogEndpoint () {
      if (this.catalogTab === 'grupos') return 'grupos'
      if (this.catalogTab === 'partidas') return 'partidas'
      return 'subpartidas'
    },
  },
  mounted () {
    this.reloadAll()
    this.fetchSinVincularCount()
  },
  methods: {
    async reloadAll () {
      await this.fetchCatalog()
      await this.fetchItems()
    },

    // ── Sin vincular ───────────────────────────────────────────────
    async fetchSinVincularCount () {
      try {
        const res = await this.$axios.get('compras-mayo/sin-vincular/count')
        this.sinVincularCount = res.data.count || 0
      } catch {}
    },

    async openSinVincular () {
      this.sinVincularDialog = true
      this.sinVincularRows = []
      this.sinVincularLoading = true
      try {
        const res = await this.$axios.get('compras-mayo/sin-vincular')
        this.sinVincularRows = (res.data.data || []).map(r => ({
          ...r,
          _modo: null,
          _saving: false,
          _grupoId: null,
          _partidaId: null,
          _subpartidaId: null,
          _partidaOpts: [],
          _subpartidaOpts: [],
          _itemSeleccionado: null,
          _busquedaOpts: r.sugerencias || [],
        }))
      } catch (e) {
        this.$alert.error('Error cargando productos sin vincular')
      } finally {
        this.sinVincularLoading = false
      }
    },

    onSVGrupoChange (row) {
      row._partidaId = null
      row._subpartidaId = null
      row._subpartidaOpts = []
      const grupo = this.grupos.find(g => g.id === row._grupoId)
      row._partidaOpts = (grupo?.partidas || []).map(p => ({ label: `${p.codigo} - ${p.nombre}`, value: p.id, _subpartidas: p.subpartidas }))
    },

    onSVPartidaChange (row) {
      row._subpartidaId = null
      const partida = row._partidaOpts.find(p => p.value === row._partidaId)
      row._subpartidaOpts = (partida?._subpartidas || []).map(s => ({ label: `${s.codigo} - ${s.nombre}`, value: s.id }))
    },

    async buscarItemOpts (val, update, row) {
      try {
        const res = await this.$axios.get('compras-mayo/buscar-item', { params: { q: val } })
        update(() => { row._busquedaOpts = res.data || [] })
      } catch {
        update(() => {})
      }
    },

    async vincularDirecto (row, sugerencia) {
      if (row._saving) return
      row._saving = true
      try {
        await this.$axios.post(`compras-mayo/${row.id}/vincular`, { almacen_item_id: sugerencia.id })
        this._quitarVinculado(row)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al vincular')
      } finally {
        row._saving = false
      }
    },

    async vincularExistente (row) {
      if (!row._itemSeleccionado || row._saving) return
      row._saving = true
      try {
        await this.$axios.post(`compras-mayo/${row.id}/vincular`, {
          almacen_item_id: row._itemSeleccionado.id,
        })
        this._quitarVinculado(row)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al vincular')
      } finally {
        row._saving = false
      }
    },

    async vincularNuevo (row) {
      if (!row._subpartidaId || row._saving) return
      row._saving = true
      try {
        await this.$axios.post(`compras-mayo/${row.id}/vincular`, {
          subpartida_id:   row._subpartidaId,
          nombre:          row.nombre,
          unidad_medida:   row.unidad_medida,
          precio_unitario: row.precio_unitario,
        })
        this._quitarVinculado(row)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al crear y vincular')
      } finally {
        row._saving = false
      }
    },

    _quitarVinculado (row) {
      const idx = this.sinVincularRows.findIndex(r => r.id === row.id)
      if (idx !== -1) this.sinVincularRows.splice(idx, 1)
      this.sinVincularCount = Math.max(0, this.sinVincularCount - 1)
      this.$alert?.success?.('Vinculado correctamente')
      this.fetchItems() // refresca el inventario
    },
    // ── Fin sin vincular ───────────────────────────────────────────
    async fetchCatalog () {
      const res = await this.$axios.get('grupos', { params: { with_partidas: 1 } })
      this.grupos = res.data || []
    },
    async fetchItems () {
      this.loading = true
      try {
        const res = await this.$axios.get('almacen-items', {
          params: {
            page: this.pagination.page,
            rowsPerPage: this.pagination.rowsPerPage,
            sortBy: this.pagination.sortBy,
            descending: this.pagination.descending,
            ...this.filters,
          },
        })
        this.items = res.data.data || []
        this.pagination.rowsNumber = res.data.total || 0
        this.summary = res.data.summary || { items: this.pagination.rowsNumber, cantidad: 0 }
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar inventario')
      } finally {
        this.loading = false
      }
    },
    onTableRequest (props) {
      this.pagination = props.pagination
      this.fetchItems()
    },
    resetAndFetchItems () {
      this.pagination.page = 1
      this.fetchItems()
    },
    onGrupoChange () {
      this.filters.partida_id = null
      this.filters.subpartida_id = null
      this.resetAndFetchItems()
    },
    onPartidaChange () {
      this.filters.subpartida_id = null
      this.resetAndFetchItems()
    },
    openCatalogManager () {
      this.catalogManagerDialog = true
    },
    openCatalogForm (row = null) {
      this.catalogForm = row
        ? { ...row }
        : { num: null, codigo: '', nombre: '', grupo_id: this.filters.grupo_id, partida_id: this.filters.partida_id }
      this.catalogFormDialog = true
    },
    async saveCatalog () {
      this.savingCatalog = true
      try {
        const payload = {
          num: this.catalogForm.num,
          codigo: this.catalogForm.codigo,
          nombre: this.catalogForm.nombre,
        }
        if (this.catalogTab === 'partidas') payload.grupo_id = this.catalogForm.grupo_id
        if (this.catalogTab === 'subpartidas') payload.partida_id = this.catalogForm.partida_id
        if (this.catalogForm.id) {
          await this.$axios.put(`${this.catalogEndpoint}/${this.catalogForm.id}`, payload)
        } else {
          await this.$axios.post(this.catalogEndpoint, payload)
        }
        this.catalogFormDialog = false
        this.$alert.success('Catalogo actualizado')
        await this.reloadAll()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.savingCatalog = false
      }
    },
    deleteCatalog (row) {
      this.$alert.dialog(`Desea eliminar ${row.codigo} - ${row.nombre}?`).onOk(async () => {
        try {
          await this.$axios.delete(`${this.catalogEndpoint}/${row.id}`)
          this.$alert.success('Registro eliminado')
          await this.reloadAll()
        } catch (e) {
          this.$alert.error(e.response?.data?.message || 'No se pudo eliminar')
        }
      })
    },
    openItemDialog (row = null) {
      const subpartida = row?.subpartida_id
        ? this.allSubpartidas.find(s => s.id === row.subpartida_id)
        : this.allSubpartidas.find(s => s.id === this.filters.subpartida_id)
      const partida = subpartida ? this.allPartidas.find(p => p.id === subpartida.partida_id) : null
      this.itemGrupoId = partida?.grupo_id || this.filters.grupo_id || null
      this.itemPartidaId = subpartida?.partida_id || this.filters.partida_id || null
      this.itemPartidaOptionsFiltered = []
      this.itemSubpartidaOptionsFiltered = []
      this.itemImageFile = null
      this.itemPreviewUrl = this.itemImageUrl(row || {})
      this.itemForm = row
        ? { ...row }
        : {
            subpartida_id: this.filters.subpartida_id,
            nombre: '',
            unidad_medida: '',
            precio_unitario: 0,
          }
      this.itemDialog = true
    },
    async saveItem () {
      this.savingItem = true
      try {
        const payload = this.itemPayload()
        if (this.itemForm.id) {
          if (payload instanceof FormData) {
            await this.$axios.post(`almacen-items/${this.itemForm.id}`, payload)
          } else {
            await this.$axios.put(`almacen-items/${this.itemForm.id}`, payload)
          }
        } else {
          await this.$axios.post('almacen-items', payload)
        }
        this.itemDialog = false
        this.$alert.success('Item guardado')
        await this.fetchItems()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.savingItem = false
      }
    },
    itemPayload () {
      if (!this.itemImageFile) {
        return {
          subpartida_id: this.itemForm.subpartida_id,
          nombre: this.itemForm.nombre,
          unidad_medida: this.itemForm.unidad_medida,
          precio_unitario: this.itemForm.precio_unitario,
        }
      }

      const form = new FormData()
      form.append('subpartida_id', this.itemForm.subpartida_id || '')
      form.append('nombre', this.itemForm.nombre || '')
      form.append('unidad_medida', this.itemForm.unidad_medida || '')
      form.append('precio_unitario', this.itemForm.precio_unitario || 0)
      form.append('imagen', this.itemImageFile)
      if (this.itemForm.id) form.append('_method', 'PUT')
      return form
    },
    onItemImageChange (file) {
      if (this.itemPreviewUrl && this.itemPreviewUrl.startsWith('blob:')) {
        URL.revokeObjectURL(this.itemPreviewUrl)
      }
      this.itemPreviewUrl = file ? URL.createObjectURL(file) : this.itemImageUrl(this.itemForm)
    },
    pickItemImageFile () {
      this.$refs.itemImageInput?.click()
    },
    focusItemImageDropzone () {
      this.$refs.itemImageDropzone?.focus?.()
    },
    onItemImageInputChange (e) {
      const file = e?.target?.files?.[0]
      if (!file) return
      this.itemImageFile = file
      this.onItemImageChange(file)
      e.target.value = ''
    },
    onItemImageDragOver () {
      this.draggingItemImage = true
    },
    onItemImageDragLeave () {
      this.draggingItemImage = false
    },
    onItemImageDrop (e) {
      this.draggingItemImage = false
      const file = e?.dataTransfer?.files?.[0]
      if (!file) return
      if (!String(file.type || '').startsWith('image/')) {
        this.$alert?.error && this.$alert.error('Solo se permiten imágenes')
        return
      }
      this.itemImageFile = file
      this.onItemImageChange(file)
    },
    async onItemImagePaste (e) {
      try {
        const items = Array.from(e?.clipboardData?.items || [])
        const imageItem = items.find(it => String(it.type || '').startsWith('image/'))
        if (imageItem) {
          const file = imageItem.getAsFile()
          if (!file) return
          this.itemImageFile = file
          this.onItemImageChange(file)
          return
        }

        const text = (e?.clipboardData?.getData('text/plain') || '').trim()
        if (/^https?:\/\//i.test(text)) {
          const res = await fetch(text)
          const blob = await res.blob()
          if (!String(blob.type || '').startsWith('image/')) {
            this.$alert?.error && this.$alert.error('El enlace pegado no es una imagen')
            return
          }
          const ext = (blob.type || 'image/png').split('/')[1] || 'png'
          const file = new File([blob], `imagen.${ext}`, { type: blob.type || 'image/png' })
          this.itemImageFile = file
          this.onItemImageChange(file)
          return
        }

        this.$alert?.error && this.$alert.error('Pega una imagen (copiada) o un enlace directo a imagen')
      } catch (err) {
        console.log(err)
        this.$alert?.error && this.$alert.error('No se pudo pegar la imagen (puede ser por permisos del navegador)')
      }
    },
    onTableImageDrop (e, row) {
      this.draggingDirectId = null
      const file = e?.dataTransfer?.files?.[0]
      if (!file) return
      if (!String(file.type || '').startsWith('image/')) {
        this.$alert.error('Solo se permiten imágenes')
        return
      }
      this.uploadDirectImage(row, file)
    },
    async uploadDirectImage (row, file) {
      this.uploadingImageId = row.id
      try {
        const form = new FormData()
        form.append('imagen', file)
        const res = await this.$axios.post(`almacen-items/${row.id}/imagen`, form)
        const idx = this.items.findIndex(i => i.id === row.id)
        if (idx !== -1) this.items.splice(idx, 1, { ...this.items[idx], imagen: res.data.imagen, _ts: Date.now() })
        this.$alert.success('Imagen actualizada')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo actualizar la imagen')
      } finally {
        this.uploadingImageId = null
      }
    },
    itemImageUrl (row) {
      const ts = row?._ts ? `?t=${row._ts}` : ''
      return `${this.$url}/../images/productos/${row?.imagen || 'default.png'}${ts}`
    },
    deleteItem (row) {
      this.$alert.dialog(`Desea eliminar ${row.nombre}?`).onOk(async () => {
        try {
          await this.$axios.delete(`almacen-items/${row.id}`)
          this.$alert.success('Item eliminado')
          await this.fetchItems()
        } catch (e) {
          this.$alert.error(e.response?.data?.message || 'No se pudo eliminar')
        }
      })
    },
    async openHistoryDialog (row) {
      this.historyItem = row
      this.historyTab = 'despachos'
      this.historyDespachos = []
      this.historyPedidos = []
      this.historySolicitudesSap = []
      this.historyCompras = []
      this.historyDialog = true
      await Promise.all([
        this.fetchHistoryDespachos(row.id),
        this.fetchHistoryPedidos(row.id),
        this.fetchHistorySolicitudesSap(row.id),
        this.fetchHistoryCompras(row.id),
      ])
    },
    async fetchHistoryDespachos (productoId) {
      this.historyLoading.despachos = true
      try {
        const res = await this.$axios.get('despachos', {
          params: {
            producto_id: productoId,
            rowsPerPage: 50,
          },
        })
        this.historyDespachos = (res.data.data || []).filter(r => r.estado !== 'ANULADO')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar historial de despachos')
      } finally {
        this.historyLoading.despachos = false
      }
    },
    async fetchHistoryPedidos (productoId) {
      this.historyLoading.pedidos = true
      try {
        const res = await this.$axios.get('pedidos', {
          params: {
            producto_id: productoId,
            rowsPerPage: 50,
          },
        })
        this.historyPedidos = (res.data.data || []).filter(r => r.estado !== 'ANULADO')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar historial de pedidos')
      } finally {
        this.historyLoading.pedidos = false
      }
    },
    async fetchHistorySolicitudesSap (productoId) {
      this.historyLoading.solicitudesSap = true
      try {
        const res = await this.$axios.get('solicitudes-sap', {
          params: { producto_id: productoId, rowsPerPage: 50 },
        })
        this.historySolicitudesSap = (res.data.data || []).filter(r => r.estado !== 'ANULADO')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar solicitudes SAP')
      } finally {
        this.historyLoading.solicitudesSap = false
      }
    },
    async fetchHistoryCompras (productoId) {
      this.historyLoading.compras = true
      try {
        const res = await this.$axios.get('compras', {
          params: { producto_id: productoId, rowsPerPage: 50 },
        })
        this.historyCompras = (res.data.data || []).filter(r => r.estado !== 'ANULADO')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar historial de compras')
      } finally {
        this.historyLoading.compras = false
      }
    },
    productDespachoDetalle (row) {
      return (row?.detalles || [])[0] || null
    },
    productPedidoDetalle (row) {
      return (row?.detalles || [])[0] || null
    },
    productSapDetalle (row) {
      return (row?.detalles || [])[0] || null
    },
    productCompraDetalle (row) {
      return (row?.detalles || [])[0] || null
    },
    pedidoEstadoColor (estado) {
      if (estado === 'ACEPTADO') return 'green'
      if (estado === 'RECHAZADO' || estado === 'ANULADO') return 'red'
      return 'orange'
    },
    sapEstadoColor (estado) {
      if (estado === 'APROBADO') return 'green'
      if (estado === 'ANULADO') return 'red'
      return 'orange'
    },
    async printReport (existente) {
      this.reportLoading = existente ? 'existing' : 'all'
      try {
        const res = await this.$axios.get('almacen-items/reporte/pdf', {
          params: { existente: existente ? 1 : 0, ...this.filters },
          responseType: 'blob',
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank')
        window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el reporte')
      } finally {
        this.reportLoading = null
      }
    },

    async downloadExcel (existente) {
      this.reportLoading = existente ? 'excel-existing' : 'excel-all'
      try {
        const res = await this.$axios.get('almacen-items/reporte/excel', {
          params: { existente: existente ? 1 : 0, ...this.filters },
          responseType: 'blob',
          timeout: 120000,
        })
        const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        const url  = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href     = url
        link.download = (existente ? 'inventario_existente' : 'inventario_completo') + '_' + new Date().toISOString().slice(0,10) + '.xlsx'
        link.click()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el Excel')
      } finally {
        this.reportLoading = null
      }
    },
    onItemGrupoChange () {
      this.itemPartidaId = null
      this.itemForm.subpartida_id = null
      this.itemPartidaOptionsFiltered = []
      this.itemSubpartidaOptionsFiltered = []
    },
    onItemPartidaChange () {
      this.itemForm.subpartida_id = null
      this.itemSubpartidaOptionsFiltered = []
    },
    filterGrupoOptions (value, update) {
      update(() => {
        this.grupoOptionsFiltered = this.filterOptions(this.grupoOptions, value)
      })
    },
    filterItemPartidaOptions (value, update) {
      update(() => {
        this.itemPartidaOptionsFiltered = this.filterOptions(this.itemPartidaBaseOptions, value)
      })
    },
    filterItemSubpartidaOptions (value, update) {
      update(() => {
        this.itemSubpartidaOptionsFiltered = this.filterOptions(this.itemSubpartidaBaseOptions, value)
      })
    },
    filterOptions (options, value) {
      const text = (value || '').toLowerCase()
      if (!text) return options
      return options.filter(option => option.label.toLowerCase().includes(text))
    },
    parentLabel (row) {
      if (this.catalogTab === 'partidas') return row.grupo ? `${row.grupo.codigo} - ${row.grupo.nombre}` : ''
      if (this.catalogTab === 'subpartidas') return row.partida ? `${row.partida.codigo} - ${row.partida.nombre}` : ''
      return ''
    },
    money (value) {
      const number = Number(value || 0)
      return number.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    quantity (value) {
      const number = Number(value || 0)
      return number.toLocaleString('es-BO', { maximumFractionDigits: 2 })
    },
    formatDateTime (value) {
      if (!value) return '-'
      return new Date(value).toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
  },
}
</script>

<style scoped>
.inventory-toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  flex-wrap: wrap;
}

.inventory-table {
  min-height: 560px;
}

.inventory-name {
  max-width: 520px;
  white-space: normal;
  line-height: 1.2;
}

.item-image,
.item-image-preview {
  border: 1px solid #d9e2ec;
  background: #f5f8fb;
}

.history-dialog {
  width: 820px;
  max-width: 96vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  border-radius: 8px;
  overflow: hidden;
}

.history-header {
  background: #1565c0;
  color: #fff;
  padding: 14px 16px;
}

.history-panels {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
}

.history-empty {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  text-align: center;
}

.history-item {
  padding: 10px 14px;
}

.table-img-drop {
  position: relative;
  display: inline-flex;
  border-radius: 6px;
  cursor: grab;
}

.table-img-drop__hint {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.35);
  border-radius: 6px;
  opacity: 0;
  transition: opacity 0.15s;
  pointer-events: none;
}

.table-img-drop:hover .table-img-drop__hint {
  opacity: 1;
}

.table-img-drop--over {
  outline: 2px dashed #1976d2;
  border-radius: 6px;
}

.table-img-drop--over .table-img-drop__hint {
  opacity: 1;
  background: rgba(25, 118, 210, 0.45);
}

.item-image-dropzone {
  height: 92px;
  border: 2px dashed rgba(0, 0, 0, 0.18);
  border-radius: 10px;
  background: white;
  cursor: pointer;
  outline: none;
}

.item-image-dropzone--over {
  border-color: #1976d2;
  background: #e3f2fd;
}
</style>
