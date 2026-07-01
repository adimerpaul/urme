<template>
  <q-page class="q-pa-xs">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">

      <!-- Tarjetas resumen -->
      <div class="row q-col-gutter-xs q-mb-xs">
        <div class="col-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Productos</div>
            <div class="text-h6 text-teal text-weight-bold">{{ resumen.productos }}</div>
          </q-card>
        </div>
        <div class="col-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Fabricantes</div>
            <div class="text-h6 text-deep-orange text-weight-bold">{{ resumen.fabricantes }}</div>
          </q-card>
        </div>
        <div class="col-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Unidades</div>
            <div class="text-h6 text-purple text-weight-bold">{{ resumen.unidades }}</div>
          </q-card>
        </div>
        <div class="col-3">
          <q-card flat bordered class="text-center q-pa-xs">
            <div class="text-caption text-grey-6">Tipos</div>
            <div class="text-h6 text-indigo text-weight-bold">{{ resumen.tipos }}</div>
          </q-card>
        </div>
      </div>

      <q-tabs v-model="tab" dense align="left"
              active-color="primary" indicator-color="primary"
              class="q-mb-xs">
        <q-tab name="productos"   icon="medication"  label="Productos" no-caps />
        <q-tab name="fabricantes" icon="factory"     label="Fabricantes" no-caps />
        <q-tab name="unidades"    icon="straighten"  label="Unidades" no-caps />
        <q-tab name="tipos"       icon="category"    label="Tipos de producto" no-caps />
      </q-tabs>
      <q-separator class="q-mb-xs" />

      <!-- ══ TAB PRODUCTOS ══════════════════════════════════════════ -->
      <div v-show="tab === 'productos'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Productos farmacia</span>
          <q-space />
          <q-select v-model="filterTipoProducto" label="Categoría" dense outlined clearable
                     :options="allTipoProductos" option-value="id" option-label="nombre"
                     emit-value map-options style="width:180px" @update:model-value="onFilterProd">
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
                </q-item-section>
                <q-item-section>{{ scope.opt.nombre }}</q-item-section>
              </q-item>
            </template>
          </q-select>
          <q-input v-model="filterProd" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterProd">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="prodNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('productos')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('productos')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Tipo</th>
              <th class="text-right">Precio (Bs.)</th>
              <th class="text-right">Stock</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingProd">
              <td colspan="5" class="text-center q-pa-md">
                <q-spinner color="primary" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!productos.length">
              <td colspan="5" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in productos" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown
                  v-if="canEditar || canEliminar"
                  label="Opciones"
                  no-caps
                  size="10px"
                  dense
                  color="primary"
                >
                  <q-list>
                    <q-item v-if="canEditar" clickable v-close-popup @click="prodEdit(row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="prodDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>
                <q-badge v-if="row.tipo_producto" :color="row.tipo_producto.color || 'primary'">
                  {{ row.tipo_producto.nombre }}
                </q-badge>
                <span v-else>—</span>
              </td>
              <td class="text-right">{{ row.precio ? Number(row.precio).toFixed(2) : '—' }}</td>
              <td class="text-right">
                <span :class="Number(row.stock) > 0 ? 'text-green-8 text-weight-bold' : 'text-grey-6'">
                  {{ row.stock ? Number(row.stock).toFixed(0) : '0' }}
                </span>
              </td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalProd }} | Página {{ pageProd }} de {{ pagesProd }}
          </div>
          <q-pagination v-model="pageProd" :max="pagesProd" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadProductos" />
        </div>
      </div>

      <!-- ══ TAB FABRICANTES ════════════════════════════════════════ -->
      <div v-show="tab === 'fabricantes'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Fabricantes</span>
          <q-space />
          <q-input v-model="filterFab" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterFab">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="fabNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('fabricantes')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('fabricantes')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">País</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingFab">
              <td colspan="3" class="text-center q-pa-md">
                <q-spinner color="deep-orange" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!fabricantes.length">
              <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in fabricantes" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown
                  v-if="canEditar || canEliminar"
                  label="Opciones"
                  no-caps
                  size="10px"
                  dense
                  color="primary"
                >
                  <q-list>
                    <q-item v-if="canEditar" clickable v-close-popup @click="fabEdit(row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="fabDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.pais || '—' }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalFab }} | Página {{ pageFab }} de {{ pagesFab }}
          </div>
          <q-pagination v-model="pageFab" :max="pagesFab" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadFabricantes" />
        </div>
      </div>

      <!-- ══ TAB UNIDADES ══════════════════════════════════════════ -->
      <div v-show="tab === 'unidades'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Unidades de medida</span>
          <q-space />
          <q-input v-model="filterUnid" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterUnid">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="unidNew" />
          <q-btn color="red-7" icon="picture_as_pdf" no-caps dense :loading="exportingPdf" :disable="exportingPdf"
                 @click="exportPdf('unidades')">
            <q-tooltip>Exportar PDF</q-tooltip>
          </q-btn>
          <q-btn color="green-8" icon="table_view" no-caps dense :loading="exportingExcel" :disable="exportingExcel"
                 @click="exportExcel('unidades')">
            <q-tooltip>Exportar Excel</q-tooltip>
          </q-btn>
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Abreviatura</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingUnid">
              <td colspan="3" class="text-center q-pa-md">
                <q-spinner color="purple" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!unidades.length">
              <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in unidades" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown
                  v-if="canEditar || canEliminar"
                  label="Opciones"
                  no-caps
                  size="10px"
                  dense
                  color="primary"
                >
                  <q-list>
                    <q-item v-if="canEditar" clickable v-close-popup @click="unidEdit(row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="unidDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td>{{ row.abreviatura || '—' }}</td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalUnid }} | Página {{ pageUnid }} de {{ pagesUnid }}
          </div>
          <q-pagination v-model="pageUnid" :max="pagesUnid" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadUnidades" />
        </div>
      </div>

      <!-- ══ TAB TIPOS DE PRODUCTO ═════════════════════════════════ -->
      <div v-show="tab === 'tipos'">
        <div class="row items-center q-gutter-xs q-mb-xs">
          <span class="text-subtitle2 text-grey-7">Tipos de producto (categorías)</span>
          <q-space />
          <q-input v-model="filterTipo" label="Buscar" dense outlined clearable
                   style="width:160px" @update:model-value="onFilterTipo">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>
          <q-btn v-if="canCrear" color="positive" label="Nuevo" icon="add_circle_outline"
                 no-caps dense @click="tipoNew" />
        </div>

        <q-markup-table dense flat bordered separator="cell" class="full-width">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left" style="width:64px"></th>
              <th class="text-left">Nombre</th>
              <th class="text-left">Tipo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingTipo">
              <td colspan="3" class="text-center q-pa-md">
                <q-spinner color="indigo" size="24px" />
              </td>
            </tr>
            <tr v-else-if="!tipos.length">
              <td colspan="3" class="text-center text-grey-5 q-pa-md">Sin datos</td>
            </tr>
            <tr v-else v-for="row in tipos" :key="row.id">
              <td class="q-pa-xs">
                <q-btn-dropdown
                  v-if="canEditar || canEliminar"
                  label="Opciones"
                  no-caps
                  size="10px"
                  dense
                  color="primary"
                >
                  <q-list>
                    <q-item v-if="canEditar" clickable v-close-popup @click="tipoEdit(row)">
                      <q-item-section avatar><q-icon name="edit" /></q-item-section>
                      <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                    </q-item>
                    <q-item v-if="canEliminar" clickable v-close-popup @click="tipoDelete(row.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                      <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </td>
              <td>{{ row.nombre }}</td>
              <td><q-badge :color="row.color || 'primary'">{{ row.nombre }}</q-badge></td>
            </tr>
          </tbody>
        </q-markup-table>

        <div class="row items-center justify-between q-mt-xs q-px-xs">
          <div class="text-caption text-grey-6">
            Total: {{ totalTipo }} | Página {{ pageTipo }} de {{ pagesTipo }}
          </div>
          <q-pagination v-model="pageTipo" :max="pagesTipo" :max-pages="6"
                        boundary-links direction-links size="sm"
                        @update:model-value="loadTipos" />
        </div>
      </div>

    </template>

    <!-- ═══ DIALOG PRODUCTO ══════════════════════════════════════ -->
    <q-dialog v-model="dialogProd" persistent>
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-teal text-white q-py-sm">
          <q-icon name="medication" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ prodAction }} producto</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogProd = false" />
        </q-card-section>
        <q-card-section style="max-height:75vh;overflow-y:auto;padding:14px 16px">
          <q-form @submit.prevent="prodSave">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-input v-model="prod.codigo" label="Código" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-8">
                <q-input v-model="prod.nombre" label="Nombre *" dense outlined
                         :rules="[v => !!v || 'Requerido']" v-uppercase />
              </div>
              <div class="col-12">
                <q-input v-model="prod.descripcion" label="Descripción" dense outlined
                         type="textarea" rows="2" v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.tipo_producto_id" label="Categoría (tipo de producto)" dense outlined
                          :options="allTipoProductos" option-value="id" option-label="nombre"
                          emit-value map-options clearable>
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section avatar>
                        <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
                      </q-item-section>
                      <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="indigo"
                           @click="tipoQuick = true">
                      <q-tooltip>Nuevo tipo de producto</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model.number="prod.precio" label="Precio (Bs.)" dense outlined
                         type="number" step="0.01" min="0" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="prod.marca" label="Marca" dense outlined v-uppercase />
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.fabricante_id" label="Fabricante" dense outlined
                          :options="allFabricantes" option-value="id" option-label="nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="deep-orange"
                           @click="fabQuick = true">
                      <q-tooltip>Nuevo fabricante</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="prod.unidad_id" label="Unidad de medida" dense outlined
                          :options="allUnidades" option-value="id"
                          :option-label="u => u.abreviatura ? u.nombre + ' (' + u.abreviatura + ')' : u.nombre"
                          emit-value map-options clearable>
                  <template v-slot:after>
                    <q-btn v-if="canCrear" flat round dense icon="add" color="purple"
                           @click="unidQuick = true">
                      <q-tooltip>Nueva unidad</q-tooltip>
                    </q-btn>
                  </template>
                </q-select>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogProd = false" />
              <q-btn color="teal" :label="prod.id ? 'Guardar cambios' : 'Crear producto'"
                     type="submit" no-caps :loading="savingProd" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG FABRICANTE -->
    <q-dialog v-model="dialogFab" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-deep-orange text-white q-py-sm">
          <q-icon name="factory" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ fabAction }} fabricante</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogFab = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="fabSave">
            <q-input v-model="fab.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="fab.pais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogFab = false" />
              <q-btn color="deep-orange" :label="fab.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingFab" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG UNIDAD -->
    <q-dialog v-model="dialogUnid" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-purple text-white q-py-sm">
          <q-icon name="straighten" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ unidAction }} unidad</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogUnid = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="unidSave">
            <q-input v-model="unid.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-input v-model="unid.abreviatura" label="Abreviatura (ej: mg, ml, un)"
                     dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogUnid = false" />
              <q-btn color="purple" :label="unid.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingUnid" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG TIPO DE PRODUCTO -->
    <q-dialog v-model="dialogTipo" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="row items-center bg-indigo text-white q-py-sm">
          <q-icon name="category" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ tipoAction }} tipo de producto</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogTipo = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="tipoSave">
            <q-input v-model="tipoItem.nombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase />
            <q-select v-model="tipoItem.color" label="Color" dense outlined class="q-mb-md"
                      :options="quasarColors" emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.value" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.label }}</q-item-section>
                </q-item>
              </template>
              <template v-slot:selected-item="scope">
                <q-badge :color="scope.opt.value" class="q-mr-xs" style="width:12px;height:12px" />
                {{ scope.opt.label }}
              </template>
            </q-select>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogTipo = false" />
              <q-btn color="indigo" :label="tipoItem.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingTipo" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick tipo de producto -->
    <q-dialog v-model="tipoQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-indigo text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo tipo de producto rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="tipoQuickSave">
            <q-input v-model="tipoQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-select v-model="tipoQColor" label="Color" dense outlined class="q-mb-md"
                      :options="quasarColors" emit-value map-options>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.value" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.label }}</q-item-section>
                </q-item>
              </template>
              <template v-slot:selected-item="scope">
                <q-badge :color="scope.opt.value" class="q-mr-xs" style="width:12px;height:12px" />
                {{ scope.opt.label }}
              </template>
            </q-select>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="tipoQuick = false" />
              <q-btn color="indigo" label="Crear" type="submit" no-caps :loading="savingTipo" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick fabricante -->
    <q-dialog v-model="fabQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-deep-orange text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo fabricante rápido</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="fabQuickSave">
            <q-input v-model="fabQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="fabQPais" label="País" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="fabQuick = false" />
              <q-btn color="deep-orange" label="Crear" type="submit" no-caps :loading="savingFab" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Quick unidad -->
    <q-dialog v-model="unidQuick" persistent>
      <q-card style="width:min(96vw,380px)">
        <q-card-section class="bg-purple text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nueva unidad rápida</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="unidQuickSave">
            <q-input v-model="unidQNombre" label="Nombre *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase autofocus />
            <q-input v-model="unidQAbrev" label="Abreviatura" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="unidQuick = false" />
              <q-btn color="purple" label="Crear" type="submit" no-caps :loading="savingUnid" />
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

// ── Colores Quasar disponibles ──────────────────────────────────
const quasarColors = [
  'primary', 'secondary', 'accent', 'positive', 'negative', 'info', 'warning', 'dark',
  'red', 'pink', 'purple', 'deep-purple', 'indigo', 'blue', 'light-blue', 'cyan', 'teal',
  'green', 'light-green', 'lime', 'yellow', 'amber', 'orange', 'deep-orange', 'brown',
  'grey', 'blue-grey',
].map(c => ({ label: c, value: c }))

// ── Permisos ───────────────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Productos'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Productos'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Productos'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Productos'))

// ── Estado general ─────────────────────────────────────────────
const tab     = ref('productos')
const resumen = ref({ productos: 0, fabricantes: 0, unidades: 0, tipos: 0 })
const exportingPdf   = ref(false)
const exportingExcel = ref(false)

// ── Productos ──────────────────────────────────────────────────
const productos   = ref([])
const loadingProd = ref(false)
const savingProd  = ref(false)
const dialogProd  = ref(false)
const prodAction  = ref('Nuevo')
const filterProd  = ref('')
const filterTipoProducto = ref(null)
const pageProd    = ref(1)
const totalProd   = ref(0)
const perProd     = 15
const prod        = ref({})
let timerProd     = null

const pagesProd = computed(() => Math.max(1, Math.ceil(totalProd.value / perProd)))

// ── Fabricantes ────────────────────────────────────────────────
const fabricantes    = ref([])
const allFabricantes = ref([])
const loadingFab     = ref(false)
const savingFab      = ref(false)
const dialogFab      = ref(false)
const fabAction      = ref('Nuevo')
const filterFab      = ref('')
const pageFab        = ref(1)
const totalFab       = ref(0)
const perFab         = 15
const fab            = ref({})
const fabQuick       = ref(false)
const fabQNombre     = ref('')
const fabQPais       = ref('')
let timerFab         = null

const pagesFab = computed(() => Math.max(1, Math.ceil(totalFab.value / perFab)))

// ── Unidades ───────────────────────────────────────────────────
const unidades    = ref([])
const allUnidades = ref([])
const loadingUnid = ref(false)
const savingUnid  = ref(false)
const dialogUnid  = ref(false)
const unidAction  = ref('Nuevo')
const filterUnid  = ref('')
const pageUnid    = ref(1)
const totalUnid   = ref(0)
const perUnid     = 15
const unid        = ref({})
const unidQuick   = ref(false)
const unidQNombre = ref('')
const unidQAbrev  = ref('')
let timerUnid     = null

const pagesUnid = computed(() => Math.max(1, Math.ceil(totalUnid.value / perUnid)))

// ── Tipos de producto ────────────────────────────────────────────
const tipos           = ref([])
const allTipoProductos = ref([])
const loadingTipo     = ref(false)
const savingTipo      = ref(false)
const dialogTipo      = ref(false)
const tipoAction      = ref('Nuevo')
const filterTipo      = ref('')
const pageTipo        = ref(1)
const totalTipo       = ref(0)
const perTipo         = 15
const tipoItem        = ref({})
const tipoQuick       = ref(false)
const tipoQNombre     = ref('')
const tipoQColor      = ref('primary')
let timerTipo         = null

const pagesTipo = computed(() => Math.max(1, Math.ceil(totalTipo.value / perTipo)))

// ── Init ───────────────────────────────────────────────────────
function init () {
  loadFarmaciaData()
}

watch(() => proxy.$store.isLogged, (val) => { if (val) init() }, { immediate: true })

async function loadFarmaciaData () {
  loadingProd.value = true
  loadingFab.value = true
  loadingUnid.value = true
  loadingTipo.value = true

  try {
    const res = await proxy.$axios.get('farmacia/datos', {
      params: {
        page_prod: pageProd.value,
        page_fab: pageFab.value,
        page_unid: pageUnid.value,
        page_tipo: pageTipo.value,
        per_page: perProd,
        q_prod: filterProd.value,
        q_fab: filterFab.value,
        q_unid: filterUnid.value,
        q_tipo: filterTipo.value,
        tipo_producto_id: filterTipoProducto.value,
      },
    })

    const data = res.data || {}
    resumen.value = data.resumen || { productos: 0, fabricantes: 0, unidades: 0, tipos: 0 }

    productos.value = data.productos?.data || []
    totalProd.value = data.productos?.total || 0

    fabricantes.value = data.fabricantes?.data || []
    totalFab.value = data.fabricantes?.total || 0

    unidades.value = data.unidades?.data || []
    totalUnid.value = data.unidades?.total || 0

    tipos.value = data.tipos?.data || []
    totalTipo.value = data.tipos?.total || 0

    allFabricantes.value = data.allFabricantes || []
    allUnidades.value = data.allUnidades || []
    allTipoProductos.value = data.allTipoProductos || []
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al cargar')
  } finally {
    loadingProd.value = false
    loadingFab.value = false
    loadingUnid.value = false
    loadingTipo.value = false
  }
}

function loadProductos () { return loadFarmaciaData() }
function loadFabricantes () { return loadFarmaciaData() }
function loadUnidades () { return loadFarmaciaData() }
function loadTipos () { return loadFarmaciaData() }

function onFilterProd () {
  clearTimeout(timerProd)
  timerProd = setTimeout(() => { pageProd.value = 1; loadProductos() }, 350)
}

function prodNew () {
  prod.value = { codigo: '', nombre: '', descripcion: '', marca: '', fabricante_id: null, unidad_id: null, tipo_producto_id: null, precio: 0 }
  prodAction.value = 'Nuevo'
  dialogProd.value = true
}

function prodEdit (row) {
  prod.value = { ...row, fabricante_id: row.fabricante?.id || null, unidad_id: row.unidad?.id || null, tipo_producto_id: row.tipo_producto?.id || null }
  prodAction.value = 'Editar'
  dialogProd.value = true
}

async function prodSave () {
  savingProd.value = true
  try {
    const payload = { ...prod.value }
    if (prod.value.id) {
      await proxy.$axios.put('productos/' + prod.value.id, payload)
      proxy.$alert.success('Producto actualizado')
    } else {
      await proxy.$axios.post('productos', payload)
      proxy.$alert.success('Producto creado')
    }
    dialogProd.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingProd.value = false
  }
}

function prodDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el producto?').onOk(() => {
    proxy.$axios.delete('productos/' + id)
      .then(() => { proxy.$alert.success('Producto eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

function onFilterFab () {
  clearTimeout(timerFab)
  timerFab = setTimeout(() => { pageFab.value = 1; loadFabricantes() }, 350)
}

function fabNew ()     { fab.value = { nombre: '', pais: '' }; fabAction.value = 'Nuevo'; dialogFab.value = true }
function fabEdit (row) { fab.value = { ...row }; fabAction.value = 'Editar'; dialogFab.value = true }

async function fabSave () {
  savingFab.value = true
  try {
    if (fab.value.id) {
      await proxy.$axios.put('fabricantes/' + fab.value.id, fab.value)
      proxy.$alert.success('Fabricante actualizado')
    } else {
      await proxy.$axios.post('fabricantes', fab.value)
      proxy.$alert.success('Fabricante creado')
    }
    dialogFab.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingFab.value = false
  }
}

function fabDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el fabricante?').onOk(() => {
    proxy.$axios.delete('fabricantes/' + id)
      .then(() => { proxy.$alert.success('Fabricante eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function fabQuickSave () {
  savingFab.value = true
  try {
    const res = await proxy.$axios.post('fabricantes', { nombre: fabQNombre.value, pais: fabQPais.value })
    loadFarmaciaData()
    prod.value.fabricante_id = res.data.id
    fabQuick.value = false
    fabQNombre.value = ''
    fabQPais.value = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingFab.value = false
  }
}

function onFilterUnid () {
  clearTimeout(timerUnid)
  timerUnid = setTimeout(() => { pageUnid.value = 1; loadUnidades() }, 350)
}

function unidNew ()     { unid.value = { nombre: '', abreviatura: '' }; unidAction.value = 'Nuevo'; dialogUnid.value = true }
function unidEdit (row) { unid.value = { ...row }; unidAction.value = 'Editar'; dialogUnid.value = true }

async function unidSave () {
  savingUnid.value = true
  try {
    if (unid.value.id) {
      await proxy.$axios.put('unidades/' + unid.value.id, unid.value)
      proxy.$alert.success('Unidad actualizada')
    } else {
      await proxy.$axios.post('unidades', unid.value)
      proxy.$alert.success('Unidad creada')
    }
    dialogUnid.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingUnid.value = false
  }
}

function unidDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la unidad?').onOk(() => {
    proxy.$axios.delete('unidades/' + id)
      .then(() => { proxy.$alert.success('Unidad eliminada'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function unidQuickSave () {
  savingUnid.value = true
  try {
    const res = await proxy.$axios.post('unidades', { nombre: unidQNombre.value, abreviatura: unidQAbrev.value })
    loadFarmaciaData()
    prod.value.unidad_id = res.data.id
    unidQuick.value = false
    unidQNombre.value = ''
    unidQAbrev.value  = ''
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingUnid.value = false
  }
}

function onFilterTipo () {
  clearTimeout(timerTipo)
  timerTipo = setTimeout(() => { pageTipo.value = 1; loadTipos() }, 350)
}

function tipoNew ()     { tipoItem.value = { nombre: '', color: 'primary' }; tipoAction.value = 'Nuevo'; dialogTipo.value = true }
function tipoEdit (row) { tipoItem.value = { ...row }; tipoAction.value = 'Editar'; dialogTipo.value = true }

async function tipoSave () {
  savingTipo.value = true
  try {
    if (tipoItem.value.id) {
      await proxy.$axios.put('tipo-productos/' + tipoItem.value.id, tipoItem.value)
      proxy.$alert.success('Tipo de producto actualizado')
    } else {
      await proxy.$axios.post('tipo-productos', tipoItem.value)
      proxy.$alert.success('Tipo de producto creado')
    }
    dialogTipo.value = false
    loadFarmaciaData()
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    savingTipo.value = false
  }
}

function tipoDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el tipo de producto?').onOk(() => {
    proxy.$axios.delete('tipo-productos/' + id)
      .then(() => { proxy.$alert.success('Tipo de producto eliminado'); loadFarmaciaData() })
      .catch(e => proxy.$alert.error(e.response?.data?.message || 'Error'))
  })
}

async function tipoQuickSave () {
  savingTipo.value = true
  try {
    const res = await proxy.$axios.post('tipo-productos', { nombre: tipoQNombre.value, color: tipoQColor.value })
    loadFarmaciaData()
    prod.value.tipo_producto_id = res.data.id
    tipoQuick.value = false
    tipoQNombre.value = ''
    tipoQColor.value = 'primary'
  } catch (e) {
    proxy.$alert.error(e.response?.data?.message || 'Error')
  } finally {
    savingTipo.value = false
  }
}

// ── Exportar ───────────────────────────────────────────────────
function exportParams (recurso) {
  if (recurso === 'productos') {
    return { tipo_producto_id: filterTipoProducto.value, q: filterProd.value }
  }
  if (recurso === 'fabricantes') {
    return { q: filterFab.value }
  }
  if (recurso === 'unidades') {
    return { q: filterUnid.value }
  }
  return {}
}

async function exportPdf (recurso) {
  exportingPdf.value = true
  try {
    const params = exportParams(recurso)
    const res = await proxy.$axios.get(recurso + '/export-pdf', { params, responseType: 'blob' })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (e) {
    proxy.$alert.error('Error al generar PDF')
  } finally {
    exportingPdf.value = false
  }
}

async function exportExcel (recurso) {
  exportingExcel.value = true
  try {
    const params = exportParams(recurso)
    const res = await proxy.$axios.get(recurso + '/export-excel', { params, responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }))
    const a = document.createElement('a')
    a.href = url
    a.download = recurso + '_' + new Date().toISOString().slice(0, 10) + '.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    proxy.$alert.error('Error al generar Excel')
  } finally {
    exportingExcel.value = false
  }
}
</script>
