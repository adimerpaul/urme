<template>
  <q-page class="q-pa-md bg-grey-2">
    <template v-if="isAlmacenRole">
      <div class="row items-center q-mb-md">
        <div class="col-12 col-md-6">
          <div class="row items-center q-gutter-sm">
            <div class="text-h5 text-weight-bold">Dashboard de Almacén</div>
            <q-chip
              dense
              :color="almacenCanVerTodos ? 'primary' : 'orange'"
              text-color="white"
              :icon="almacenCanVerTodos ? 'groups' : 'person'"
              :label="almacenCanVerTodos ? 'General — todos los pedidos' : 'Personal — solo mis pedidos'"
            />
          </div>
          <div class="text-caption text-grey-7">
            Seguimiento de pedidos, despachos, solicitudes e inventario
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="row items-center q-gutter-sm justify-end">
            <div class="col-12 col-sm-4">
              <q-input v-model="filters.date_from" type="date" dense outlined label="Desde" />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="filters.date_to" type="date" dense outlined label="Hasta" />
            </div>
            <div class="col-auto">
              <q-btn
                color="primary"
                icon="refresh"
                label="Actualizar"
                no-caps
                :loading="loading"
                @click="fetchDashboard"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-pa-md">
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Pedidos</div>
                <div class="text-h5 text-weight-bold">{{ almacenResumen.pedidos || 0 }}</div>
              </div>
              <q-icon name="shopping_bag" size="36px" class="text-primary" />
            </div>
            <div class="text-caption text-grey-6 q-mt-sm">
              Pendientes: {{ almacenResumen.pedidos_pendientes || 0 }}
            </div>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-pa-md">
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Despachos</div>
                <div class="text-h5 text-weight-bold">{{ almacenResumen.despachos || 0 }}</div>
              </div>
              <q-icon name="local_shipping" size="36px" class="text-positive" />
            </div>
            <div class="text-caption text-grey-6 q-mt-sm">
              Anulados: {{ almacenResumen.despachos_anulados || 0 }}
            </div>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-pa-md">
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Solicitudes</div>
                <div class="text-h5 text-weight-bold">{{ almacenResumen.solicitudes || 0 }}</div>
              </div>
              <q-icon name="receipt_long" size="36px" class="text-indigo" />
            </div>
            <div class="text-caption text-grey-6 q-mt-sm">Solicitudes registradas en el rango.</div>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="q-pa-md">
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-caption text-grey-7">Inventario</div>
                <div class="text-h5 text-weight-bold">{{ almacenResumen.inventario_items || 0 }}</div>
              </div>
              <q-icon name="inventory_2" size="36px" class="text-teal" />
            </div>
            <div class="text-caption text-grey-6 q-mt-sm">
              Sin existencia: {{ almacenResumen.items_sin_existencia || 0 }}
            </div>
          </q-card>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-8">
          <q-card flat bordered class="q-pa-md">
            <div class="text-subtitle1 text-weight-bold q-mb-sm">Movimiento diario</div>
            <apexchart
              type="area"
              height="320"
              :options="chartAlmacenMovimiento.options"
              :series="chartAlmacenMovimiento.series"
            />
          </q-card>
        </div>
<!--        <div class="col-12 col-md-4">-->
<!--          <q-card flat bordered class="q-pa-md">-->
<!--            <div class="text-subtitle1 text-weight-bold q-mb-sm">Estados de solicitudes</div>-->
<!--            <apexchart-->
<!--              type="donut"-->
<!--              height="320"-->
<!--              :options="chartAlmacenSolicitudes.options"-->
<!--              :series="chartAlmacenSolicitudes.series"-->
<!--            />-->
<!--          </q-card>-->
<!--        </div>-->
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-lg-4">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Últimos pedidos</div>
              <div class="text-caption text-grey-7">Pedidos recientes del rango seleccionado.</div>
            </q-card-section>
            <q-table
              dense
              flat
              :rows="almacenUltimosPedidos"
              :columns="columnsPedidos"
              row-key="id"
              :rows-per-page-options="[8]"
              hide-bottom
            >
              <template #body-cell-estado="props">
                <q-td :props="props">
                  <q-badge :color="pedidoEstadoColor(props.row.estado)">{{ props.row.estado }}</q-badge>
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

        <div class="col-12 col-lg-4">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Últimos despachos</div>
              <div class="text-caption text-grey-7">Entregas recientes desde almacén.</div>
            </q-card-section>
            <q-table
              dense
              flat
              :rows="almacenUltimosDespachos"
              :columns="columnsDespachos"
              row-key="id"
              :rows-per-page-options="[8]"
              hide-bottom
            >
              <template #body-cell-estado="props">
                <q-td :props="props">
                  <q-badge :color="props.row.estado === 'ANULADO' ? 'red' : 'green'">{{ props.row.estado }}</q-badge>
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

        <div class="col-12 col-lg-4">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Inventario crítico</div>
              <div class="text-caption text-grey-7">Items con menor existencia.</div>
            </q-card-section>
            <q-table
              dense
              flat
              :rows="almacenInventarioCritico"
              :columns="columnsInventario"
              row-key="id"
              :rows-per-page-options="[10]"
              hide-bottom
            />
          </q-card>
        </div>
      </div>
    </template>
    <template v-else>
    <!-- HEADER -->
    <div class="row items-center q-mb-md">

      <div class="col-12 col-md-6">

        <div class="text-h5 text-weight-bold">Resumen de atención de los pacientes</div>
        <div class="text-caption text-grey-7">
          Visión por áreas, servicios y preanalítica
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="row items-center q-gutter-sm justify-end">
          <div class="col-12 col-sm-4">
            <q-input
              v-model="filters.date_from"
              type="date"
              dense outlined
              label="Desde"
            />
          </div>
          <div class="col-12 col-sm-4">
            <q-input
              v-model="filters.date_to"
              type="date"
              dense outlined
              label="Hasta"
            />
          </div>
          <div class="col-auto">
            <q-btn
              color="primary"
              icon="refresh"
              label="Actualizar"
              no-caps
              :loading="loading"
              @click="fetchDashboard"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- KPIs ENFOCADOS EN ÁREAS / SERVICIOS -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Solicitudes</div>
              <div class="text-h5 text-weight-bold">
                {{ resumen.total_solicitudes || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="receipt_long" size="36px" class="text-primary" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Total de solicitudes en el rango seleccionado.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Servicios solicitados</div>
              <div class="text-h5 text-weight-bold">
                {{ resumen.total_servicios || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="science" size="36px" class="text-secondary" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Cantidad total de servicios (exámenes) asociados a las solicitudes.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Servicios / solicitud</div>
              <div class="text-h5 text-weight-bold">
                {{ resumen.promedio_servicios || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="bar_chart" size="36px" class="text-teal" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Promedio de servicios por cada solicitud de laboratorio.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Muestras preanalíticas</div>
              <div class="text-h5 text-weight-bold">
                {{ resumen.total_muestras_preanaliticas || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="inventory_2" size="36px" class="text-positive" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Muestras registradas en la etapa preanalítica.
          </div>
        </q-card>
      </div>
    </div>

    <!-- KPIs FILA 2: PACIENTES, FINALIZADAS, PENDIENTES -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Pacientes atendidos</div>
              <div class="text-h5 text-weight-bold">
                {{ resumen.total_pacientes || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="people" size="36px" class="text-indigo" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Pacientes únicos con solicitudes en el período.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Finalizadas</div>
              <div class="text-h5 text-weight-bold text-positive">
                {{ resumen.finalizadas || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="check_circle" size="36px" class="text-positive" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Solicitudes con resultados entregados.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Resultados pendientes</div>
              <div class="text-h5 text-weight-bold text-orange">
                {{ (resumen.total_solicitudes - resumen.finalizadas) || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="pending_actions" size="36px" class="text-orange" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Solicitudes aún sin finalizar en el período.
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-3">
        <q-card flat bordered class="q-pa-md">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-caption text-grey-7">Áreas atendidas</div>
              <div class="text-h5 text-weight-bold text-teal">
                {{ porArea.length || 0 }}
              </div>
            </div>
            <div class="col-auto">
              <q-icon name="category" size="36px" class="text-teal" />
            </div>
          </div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Áreas de laboratorio con servicios en el período.
          </div>
        </q-card>
      </div>
    </div>

    <!-- GRÁFICOS FILA 1: ÁREAS Y SERVICIOS -->
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Solicitudes por área (donut) -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Solicitudes por área
          </div>
          <apexchart
            type="donut"
            height="280"
            :options="chartAreas.options"
            :series="chartAreas.series"
          />
        </q-card>
      </div>

      <!-- Solicitudes vs servicios por área (bar) -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Solicitudes y servicios por área
          </div>
          <apexchart
            type="bar"
            height="280"
            :options="chartServiciosArea.options"
            :series="chartServiciosArea.series"
          />
        </q-card>
      </div>

      <!-- Top servicios más solicitados -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Top servicios más solicitados
          </div>
          <apexchart
            type="bar"
            height="280"
            :options="chartTopServicios.options"
            :series="chartTopServicios.series"
          />
        </q-card>
      </div>
    </div>

    <!-- GRÁFICOS FILA 2: TIEMPO + PREANALÍTICA -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-8">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Evolución diaria de solicitudes
          </div>
          <apexchart
            type="area"
            height="320"
            :options="chartSerieFechas.options"
            :series="chartSerieFechas.series"
          />
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="q-pa-md">
          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Top tipos de muestra (preanalítica)
          </div>
          <apexchart
            type="bar"
            height="320"
            :options="chartMuestrasTipo.options"
            :series="chartMuestrasTipo.series"
          />
        </q-card>
      </div>
    </div>

    <!-- TABLA DE SOLICITUDES DEL DÍA (PAGINADA) -->
    <div class="row q-col-gutter-sm">
      <!-- TABLA -->
      <div :class="showFiltros ? 'col-12 col-md-9' : 'col-12'">
      <q-card flat bordered>
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">Solicitudes del período</div>
            <div class="text-caption text-grey-7">
              Todas las solicitudes en el rango filtrado — paginadas.
            </div>
          </div>
          <div class="col-auto">
            <q-input
              v-model="solicitudesSearch"
              dense outlined
              placeholder="Buscar paciente, código..."
              debounce="400"
              style="min-width:220px"
              @update:model-value="fetchSolicitudesList(1)"
            >
              <template v-slot:append><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-auto">
            <q-btn
              outline color="green-8" icon="table_view" no-caps
              label="Excel" :loading="excelLoading"
              @click="exportExcel"
            />
          </div>
          <div class="col-auto">
            <q-btn
              :color="showFiltros ? 'primary' : 'grey-7'"
              :outline="!showFiltros"
              icon="filter_list"
              no-caps
              dense
              :label="selectedAreaIds.length ? `Filtros (${selectedAreaIds.length})` : 'Filtros'"
              @click="showFiltros = !showFiltros"
            />
          </div>
        </q-card-section>

        <q-separator v-if="selectedAreaIds.length" />
        <q-card-section v-if="selectedAreaIds.length" class="q-pa-xs q-px-sm row q-gutter-xs items-center">
          <span class="text-caption text-grey-7">Áreas activas:</span>
          <q-chip
            v-for="id in selectedAreaIds"
            :key="id"
            dense removable color="primary" text-color="white" size="sm"
            @remove="toggleArea(id)"
          >
            {{ areaLabel(id) }}
          </q-chip>
          <q-btn flat dense no-caps size="sm" label="Limpiar" color="negative" @click="clearFiltros" />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-none">
          <q-table
          :rows="ultimas"
          :columns="columnsUltimas"
          row-key="id"
          dense flat
          :loading="loadingSolicitudes"
          v-model:pagination="solicitudesPagination"
          :rows-per-page-options="[10, 20, 50, 100]"
          @request="onSolicitudesRequest"
        >
          <template v-slot:body-cell-paciente_info="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.paciente_nombre }}</div>
              <div class="text-caption text-grey-7">
                <span v-if="props.row.paciente_ci" class="q-mr-sm">CI: {{ props.row.paciente_ci }}</span>
                <span v-if="props.row.paciente_codigo" class="text-primary q-mr-sm">Cód: {{ props.row.paciente_codigo }}</span>
                <span v-if="props.row.paciente_edad">{{ props.row.paciente_edad }} años</span>
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-areas_pruebas="props">
            <q-td :props="props">
              <div class="q-gutter-xs" style="max-width:260px">
                <q-chip
                  v-for="(area, i) in splitItems(props.row.areas)"
                  :key="'a'+i"
                  dense color="teal-1" text-color="teal-9" size="sm"
                >{{ area }}</q-chip>
              </div>
              <div v-if="props.row.pruebas" class="q-gutter-xs q-mt-xs" style="max-width:260px">
                <q-chip
                  v-for="(p, i) in splitItems(props.row.pruebas).slice(0, 3)"
                  :key="'p'+i"
                  dense color="grey-2" text-color="grey-8" size="xs"
                >{{ p }}</q-chip>
                <q-chip
                  v-if="splitItems(props.row.pruebas).length > 3"
                  dense color="blue-grey-2" text-color="blue-grey-7" size="xs"
                >
                  +{{ splitItems(props.row.pruebas).length - 3 }} más
                  <q-tooltip>{{ splitItems(props.row.pruebas).slice(3).join(', ') }}</q-tooltip>
                </q-chip>
              </div>
              <div v-if="props.row.cant_realizados > 0" class="text-caption text-positive q-mt-xs">
                {{ props.row.cant_realizados }}/{{ props.row.cant_servicios }} realizados
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-establecimiento_info="props">
            <q-td :props="props">
              <div v-if="props.row.establecimiento_nombre" class="text-caption text-weight-medium">{{ props.row.establecimiento_nombre }}</div>
              <div v-if="props.row.unidad_solicitante" class="text-caption text-grey-7">{{ props.row.unidad_solicitante }}</div>
              <div v-if="props.row.sala || props.row.cama" class="text-caption text-grey-6">
                <span v-if="props.row.sala">Sala: {{ props.row.sala }}</span>
                <span v-if="props.row.cama" class="q-ml-xs">Cama: {{ props.row.cama }}</span>
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-estado="props">
            <q-td :props="props">
              <q-chip
                dense
                :color="colorEstado(props.row.estado)"
                text-color="white"
              >
                {{ props.row.estado || 'SIN ESTADO' }}
              </q-chip>
            </q-td>
          </template>

          <template v-slot:body-cell-reportes="props">
            <q-td :props="props" class="text-center">
              <q-btn
                v-if="props.row.nro_registro"
                dense flat round
                icon="description"
                color="primary"
                size="sm"
                @click="abrirReporte(props.row.nro_registro)"
              >
                <q-tooltip>Ver reporte PDF</q-tooltip>
              </q-btn>
            </q-td>
          </template>
          </q-table>
        </q-card-section>
      </q-card>
      </div>

      <!-- PANEL DE FILTROS (derecha) -->
      <div v-if="showFiltros" class="col-12 col-md-3">
        <q-card flat bordered style="position:sticky;top:60px">
          <q-card-section class="row items-center q-pa-sm">
            <div class="text-subtitle2">Filtros</div>
            <q-space />
            <q-btn flat dense round icon="close" size="sm" @click="showFiltros = false" />
          </q-card-section>
          <q-separator />

          <!-- FILTRO POR ÁREA -->
          <q-card-section class="q-pa-sm">
            <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">
              <q-icon name="category" size="14px" class="q-mr-xs" />Áreas / Servicios
            </div>
            <q-list dense>
              <q-item
                v-for="area in areasDisponibles"
                :key="area.area_id"
                tag="label" clickable dense class="q-px-xs rounded-borders"
                :class="selectedAreaIds.includes(area.area_id) ? 'bg-primary-1' : ''"
              >
                <q-item-section avatar style="min-width:30px">
                  <q-checkbox
                    v-model="selectedAreaIds"
                    :val="area.area_id"
                    dense
                    color="primary"
                    @update:model-value="fetchSolicitudesList(1)"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption">{{ area.area_nombre }}</q-item-label>
                  <q-item-label caption class="text-grey-6">{{ area.solicitudes }} solicitudes</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="!areasDisponibles.length" dense>
                <q-item-section class="text-caption text-grey-6">
                  Cargue el dashboard primero.
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <!-- FILTRO POR ESTADO DE RESULTADOS -->
          <q-card-section class="q-pa-sm">
            <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">
              <q-icon name="science" size="14px" class="q-mr-xs" />Avance de resultados
            </div>
            <q-list dense>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroResultado" val="" dense color="grey" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section><q-item-label class="text-caption">Todos</q-item-label></q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroResultado" val="con_resultados" dense color="positive" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-positive">Con resultados</q-item-label>
                  <q-item-label caption class="text-grey-6">Al menos 1 servicio realizado</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroResultado" val="sin_resultados" dense color="orange" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-orange">Sin resultados</q-item-label>
                  <q-item-label caption class="text-grey-6">Ningún servicio realizado</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroResultado" val="completos" dense color="primary" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-primary">Completos</q-item-label>
                  <q-item-label caption class="text-grey-6">Todos los servicios realizados</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <!-- FILTRO POR VALOR (alto / bajo) -->
          <q-card-section class="q-pa-sm">
            <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">
              <q-icon name="show_chart" size="14px" class="q-mr-xs" />Valores vs. rangos
            </div>
            <q-list dense>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroValor" val="" dense color="grey" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section><q-item-label class="text-caption">Todos</q-item-label></q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroValor" val="alto" dense color="red" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-red">
                    <q-icon name="arrow_upward" size="12px" />  Resultados altos
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">Algún valor &gt; rango máximo</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroValor" val="bajo" dense color="blue" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-blue">
                    <q-icon name="arrow_downward" size="12px" />  Resultados bajos
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">Algún valor &lt; rango mínimo</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroValor" val="fuera_rango" dense color="deep-orange" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-deep-orange">
                    <q-icon name="warning" size="12px" />  Fuera de rango
                  </q-item-label>
                  <q-item-label caption class="text-grey-6">Alto o bajo (cualquiera)</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <!-- FILTRO POR ESTADO DE RECOJO -->
          <q-card-section class="q-pa-sm">
            <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">
              <q-icon name="inventory" size="14px" class="q-mr-xs" />Estado de recojo
            </div>
            <q-list dense>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroRecogido" val="" dense color="grey" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section><q-item-label class="text-caption">Todos</q-item-label></q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroRecogido" val="pendiente" dense color="orange" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-orange">Pendiente</q-item-label>
                  <q-item-label caption class="text-grey-6">Sin resultados aún</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroRecogido" val="con_resultado" dense color="blue" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-blue">Con resultado</q-item-label>
                  <q-item-label caption class="text-grey-6">Listo, no recogido</q-item-label>
                </q-item-section>
              </q-item>
              <q-item tag="label" clickable dense class="q-px-xs">
                <q-item-section avatar style="min-width:30px">
                  <q-radio v-model="filtroRecogido" val="recogido" dense color="positive" @update:model-value="fetchSolicitudesList(1)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-caption text-positive">Recogido</q-item-label>
                  <q-item-label caption class="text-grey-6">Resultado ya entregado</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />
          <q-card-section class="q-pa-sm">
            <q-btn flat dense no-caps color="negative" icon="clear_all" label="Limpiar filtros" class="full-width" @click="clearFiltros" />
          </q-card-section>
        </q-card>
      </div>
    </div>
    </template>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'SolicitudesDashboardPage',
  data () {
    return {
      loading: false,
      loadingSolicitudes: false,
      excelLoading: false,
      filters: {
        date_from: '',
        date_to: ''
      },
      solicitudesSearch: '',
      solicitudesPagination: {
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0
      },
      showFiltros: false,
      selectedAreaIds: [],
      filtroResultado: '',
      filtroValor: '',
      filtroRecogido: '',
      resumen: {},
      porArea: [],
      ultimas: [],
      almacenCanVerTodos: false,
      almacenResumen: {},
      almacenUltimosPedidos: [],
      almacenUltimosDespachos: [],
      almacenInventarioCritico: [],
      columnsUltimas: [
        { name: 'codigo_solicitud', label: 'Cód. Solicitud', field: 'codigo', align: 'left' },
        { name: 'paciente_info', label: 'Paciente (Cód. / Edad)', field: 'paciente_nombre', align: 'left' },
        { name: 'doctor_nombre', label: 'Doctor', field: 'doctor_nombre', align: 'left' },
        { name: 'areas_pruebas', label: 'Áreas / Pruebas', field: row => row.areas || '', align: 'left' },
        { name: 'cant_servicios', label: 'N° Serv.', field: row => row.cant_servicios, align: 'right' },
        { name: 'tipo_atencion', label: 'Tipo Prestación', field: 'tipo_atencion', align: 'left' },
        { name: 'establecimiento_info', label: 'Establecimiento / Sala', field: row => row.establecimiento_nombre || '', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' },
        { name: 'fecha_solicitud', label: 'Fecha', field: row => row.fecha_creacion ? row.fecha_creacion.substring(0, 10) : '', align: 'left' },
        { name: 'hora_solicitud', label: 'Hora', field: 'hora_solicitud', align: 'left' },
        { name: 'reportes', label: 'Reportes', field: 'id', align: 'center' }
      ],
      columnsPedidos: [
        { name: 'id', label: 'Pedido', field: row => `#${row.id}`, align: 'left' },
        { name: 'fecha_hora', label: 'Fecha', field: row => this.formatDateTime(row.fecha_hora), align: 'left' },
        { name: 'nombre_usuario', label: 'Usuario', field: row => row.nombre_usuario || '-', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' }
      ],
      columnsDespachos: [
        { name: 'nro', label: 'Despacho', field: row => row.nro || `#${row.id}`, align: 'left' },
        { name: 'fecha_entrega', label: 'Fecha', field: row => this.formatDateTime(row.fecha_entrega), align: 'left' },
        { name: 'solicitante', label: 'Solicitante', field: row => row.solicitante || '-', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'left' }
      ],
      columnsInventario: [
        { name: 'nombre', label: 'Item', field: 'nombre', align: 'left' },
        { name: 'unidad_medida', label: 'Unidad', field: row => row.unidad_medida || '-', align: 'left' },
        { name: 'cantidad', label: 'Existencia', field: row => this.quantity(row.cantidad), align: 'right' }
      ],
      // CHARTS
      chartAreas: {
        series: [],
        options: {
          labels: [],
          legend: { position: 'bottom' },
          dataLabels: { enabled: true }
        }
      },
      chartServiciosArea: {
        series: [],
        options: {
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: false, columnWidth: '50%' } },
          dataLabels: { enabled: false },
          legend: { position: 'top' }
        }
      },
      chartTopServicios: {
        series: [{ name: 'Solicitudes', data: [] }],
        options: {
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: true, barHeight: '70%' } },
          dataLabels: { enabled: false }
        }
      },
      chartSerieFechas: {
        series: [{ name: 'Solicitudes', data: [] }],
        options: {
          xaxis: { categories: [] },
          dataLabels: { enabled: false },
          stroke: { curve: 'smooth' },
          tooltip: { x: { format: 'yyyy-MM-dd' } }
        }
      },
      chartMuestrasTipo: {
        series: [{ name: 'Muestras', data: [] }],
        options: {
          xaxis: { categories: [] },
          plotOptions: { bar: { horizontal: true } },
          dataLabels: { enabled: false }
        }
      },
      chartAlmacenMovimiento: {
        series: [
          { name: 'Pedidos', data: [] },
          { name: 'Despachos', data: [] }
        ],
        options: {
          xaxis: { categories: [] },
          dataLabels: { enabled: false },
          stroke: { curve: 'smooth' },
          legend: { position: 'top' }
        }
      },
      chartAlmacenSolicitudes: {
        series: [],
        options: {
          labels: [],
          legend: { position: 'bottom' },
          dataLabels: { enabled: true }
        }
      }
    }
  },
  computed: {
    isAlmacenRole () {
      const user = this.$store?.user?.role ? this.$store.user : JSON.parse(localStorage.getItem('user') || '{}')
      return ['Almacén', 'Almacen','Usuario'].includes(user?.role)
    },
    areasDisponibles () {
      return this.porArea.filter(a => a.area_id)
    }
  },
  watch: {
    isAlmacenRole () {
      if (this.filters.date_from && this.filters.date_to) {
        this.fetchDashboard()
      }
    }
  },
  mounted () {
    // Por defecto últimos 30 días
    const today = moment().format('YYYY-MM-DD')
    const from = moment().subtract(30, 'days').format('YYYY-MM-DD')
    this.filters.date_from = from
    this.filters.date_to = today
    this.fetchDashboard()
  },
  methods: {
    async fetchDashboard () {
      this.loading = true
      try {
        if (this.isAlmacenRole) {
          await this.fetchAlmacenDashboard()
          return
        }

        const res = await this.$axios.get('reportes/solicitudes-dashboard', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to
          }
        })

        const data = res.data || {}
        this.resumen = data.resumen || {}

        // --- Áreas: donut (solo solicitudes por área)
        const porArea = data.por_area || []
        this.porArea = porArea
        this.chartAreas.series = porArea.map(e => e.solicitudes)
        this.chartAreas.options = {
          ...this.chartAreas.options,
          labels: porArea.map(e => e.area_nombre || 'SIN ÁREA')
        }

        // --- Áreas: bar (solicitudes vs servicios)
        this.chartServiciosArea.series = [
          {
            name: 'Solicitudes',
            data: porArea.map(e => e.solicitudes)
          },
          {
            name: 'Servicios',
            data: porArea.map(e => e.servicios)
          }
        ]
        this.chartServiciosArea.options = {
          ...this.chartServiciosArea.options,
          xaxis: { categories: porArea.map(e => e.area_nombre || 'SIN ÁREA') }
        }

        // --- Top servicios más solicitados
        const topServ = data.top_servicios || []
        this.chartTopServicios.series = [
          {
            name: 'Solicitudes',
            data: topServ.map(e => e.total)
          }
        ]
        this.chartTopServicios.options = {
          ...this.chartTopServicios.options,
          xaxis: { categories: topServ.map(e => e.servicio_nombre || 'SIN NOMBRE') }
        }

        // --- Serie fechas (area)
        const serie = data.serie_fechas || []
        this.chartSerieFechas.series = [
          { name: 'Solicitudes', data: serie.map(e => e.total) }
        ]
        this.chartSerieFechas.options = {
          ...this.chartSerieFechas.options,
          xaxis: { categories: serie.map(e => e.fecha) }
        }

        // --- Top tipos de muestra (preanalítica)
        const tipos = data.por_tipo_muestra || []
        this.chartMuestrasTipo.series = [
          { name: 'Muestras', data: tipos.map(e => e.total) }
        ]
        this.chartMuestrasTipo.options = {
          ...this.chartMuestrasTipo.options,
          xaxis: {
            categories: tipos.map(e =>
              `${e.tipo_muestra} (${e.area_nombre || 'SIN ÁREA'})`
            )
          }
        }
        // Cargar tabla paginada en paralelo
        this.fetchSolicitudesList(1)
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'Error cargando dashboard')
      } finally {
        this.loading = false
      }
    },

    async fetchSolicitudesList (page) {
      this.loadingSolicitudes = true
      try {
        const perPage = this.solicitudesPagination.rowsPerPage || 20
        const res = await this.$axios.get('reportes/solicitudes-dashboard/list', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to,
            search: this.solicitudesSearch || undefined,
            area_ids: this.selectedAreaIds.length ? this.selectedAreaIds.join(',') : undefined,
            filtro_resultado: this.filtroResultado || undefined,
            filtro_valor: this.filtroValor || undefined,
            filtro_recogido: this.filtroRecogido || undefined,
            page,
            per_page: perPage
          }
        })
        const p = res.data
        this.ultimas = p.data || []
        this.solicitudesPagination = {
          ...this.solicitudesPagination,
          page: p.current_page,
          rowsPerPage: p.per_page,
          rowsNumber: p.total
        }
      } catch (e) {
        this.$alert?.error('Error cargando solicitudes')
      } finally {
        this.loadingSolicitudes = false
      }
    },

    onSolicitudesRequest (props) {
      this.solicitudesPagination.rowsPerPage = props.pagination.rowsPerPage
      this.fetchSolicitudesList(props.pagination.page)
    },

    async exportExcel () {
      this.excelLoading = true
      try {
        const res = await this.$axios.get('reportes/solicitudes-dashboard/excel', {
          params: {
            date_from: this.filters.date_from,
            date_to: this.filters.date_to,
            search: this.solicitudesSearch || undefined,
            area_ids: this.selectedAreaIds.length ? this.selectedAreaIds.join(',') : undefined,
            filtro_resultado: this.filtroResultado || undefined,
            filtro_valor: this.filtroValor || undefined,
            filtro_recogido: this.filtroRecogido || undefined
          },
          responseType: 'blob'
        })
        const blob = new Blob([res.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        })
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        const from = this.filters.date_from || 'inicio'
        const to   = this.filters.date_to   || 'fin'
        a.download = `solicitudes_${from}_${to}.xlsx`
        a.click()
        URL.revokeObjectURL(a.href)
      } catch (e) {
        this.$alert?.error('Error al generar el Excel')
      } finally {
        this.excelLoading = false
      }
    },

    async fetchAlmacenDashboard () {
      const res = await this.$axios.get('reportes/almacen-dashboard', {
        params: {
          date_from: this.filters.date_from,
          date_to: this.filters.date_to
        }
      })

      const data = res.data || {}
      this.almacenCanVerTodos = !!data.can_ver_todos
      this.almacenResumen = data.resumen || {}
      this.almacenUltimosPedidos = data.ultimos_pedidos || []
      this.almacenUltimosDespachos = data.ultimos_despachos || []
      this.almacenInventarioCritico = data.inventario_critico || []

      const fechas = [...new Set([
        ...(data.serie_pedidos || []).map(row => row.fecha),
        ...(data.serie_despachos || []).map(row => row.fecha)
      ])].sort()
      const pedidosMap = new Map((data.serie_pedidos || []).map(row => [row.fecha, Number(row.total || 0)]))
      const despachosMap = new Map((data.serie_despachos || []).map(row => [row.fecha, Number(row.total || 0)]))

      this.chartAlmacenMovimiento.series = [
        { name: 'Pedidos', data: fechas.map(fecha => pedidosMap.get(fecha) || 0) },
        { name: 'Despachos', data: fechas.map(fecha => despachosMap.get(fecha) || 0) }
      ]
      this.chartAlmacenMovimiento.options = {
        ...this.chartAlmacenMovimiento.options,
        xaxis: { categories: fechas }
      }

      const solicitudesEstado = data.solicitudes_por_estado || {}
      this.chartAlmacenSolicitudes.series = Object.values(solicitudesEstado).map(value => Number(value || 0))
      this.chartAlmacenSolicitudes.options = {
        ...this.chartAlmacenSolicitudes.options,
        labels: Object.keys(solicitudesEstado)
      }
    },
    pedidoEstadoColor (estado) {
      if (estado === 'ACEPTADO') return 'green'
      if (estado === 'RECHAZADO' || estado === 'ANULADO') return 'red'
      return 'orange'
    },
    formatDateTime (value) {
      if (!value) return '-'
      return moment(value).format('DD/MM/YYYY HH:mm')
    },
    quantity (value) {
      return Number(value || 0).toLocaleString('es-BO', { maximumFractionDigits: 2 })
    },
    colorEstado (estado) {
      switch (estado) {
        case 'CREADO': return 'grey-7'
        case 'ATENDIENDO': return 'orange'
        case 'ENVIADO_ANALITICA': return 'indigo'
        case 'ANALITICA_ATENDIENDO': return 'blue'
        case 'FINALIZADO': return 'positive'
        default: return 'blue-grey'
      }
    },
    splitItems (str) {
      if (!str) return []
      return str.split(/[,\n]/).map(s => s.trim()).filter(Boolean)
    },
    toggleArea (id) {
      const idx = this.selectedAreaIds.indexOf(id)
      if (idx >= 0) this.selectedAreaIds.splice(idx, 1)
      else this.selectedAreaIds.push(id)
      this.fetchSolicitudesList(1)
    },
    areaLabel (id) {
      const a = this.porArea.find(x => x.area_id === id)
      return a ? a.area_nombre : id
    },
    clearFiltros () {
      this.selectedAreaIds = []
      this.filtroResultado = ''
      this.filtroValor = ''
      this.filtroRecogido = ''
      this.fetchSolicitudesList(1)
    },
    abrirReporte (nroRegistro) {
      const apiBase = this.$axios.defaults.baseURL || ''
      const backBase = apiBase.replace(/\/api\/?$/, '')
      const url = `${backBase}/public/reportes/${nroRegistro}`
      window.open(url, '_blank')
    }
  }

}
</script>
