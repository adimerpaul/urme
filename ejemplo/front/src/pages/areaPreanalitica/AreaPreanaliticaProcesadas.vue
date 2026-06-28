<template>
  <q-page class="q-pa-sm">
    <!-- HEADER / FILTROS -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-xs">
        <div class="col-12 col-sm-3">
          <div class="text-h6">Área Preanalítica</div>
          <div class="text-caption text-grey-7">
            Solicitudes pendientes de procesamiento
          </div>
        </div>
        <div class="col-12 col-sm-2">
          <q-input v-model="from" dense outlined label="Desde" type="date" />
        </div>
        <div class="col-12 col-sm-2">
          <q-input v-model="to" dense outlined label="Hasta" type="date" />
        </div>

        <div class="col-12 col-sm-3">
          <q-input
            v-model="filter"
            dense
            outlined
            debounce="400"
            clearable
            label="Buscar por código, paciente o CI"
          >
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>

        <div class="col-12 col-sm-4 text-right">
          <div>
            <div class="text-subtitle2">
              Total Solicitudes:
              <span class="text-bold">{{ pagination.rowsNumber }}</span>
            </div>
            <div class="text-caption text-grey-7">
              Creadas:
              <span class="text-bold text-blue-6">{{ rows.filter(r => r.estado === 'CREADO').length }}</span>
              |
              Atendidas:
              <span class="text-bold text-grey-6">{{ rows.filter(r => r.estado === 'ATENDIENDO').length }}</span>
              |
              Muestra Rechazada:
              <span class="text-bold text-red-6">{{ rows.filter(r => r.estado === 'MUESTRA RECHAZADA').length }}</span>
            </div>
          </div>
<!--          <q-btn-->
<!--            color="red"-->
<!--            icon="add"-->
<!--            label="Mostrar Rechazadas"-->
<!--            no-caps-->
<!--            @click="showRejected"-->
<!--          />-->
          <q-btn
            dense
            color="primary"
            icon="print"
            label="Imprimir"
            no-caps
            @click="imprimirSolicitud"
          />
          <q-btn
            color="primary"
            icon="refresh"
            label="Actualizar"
            no-caps
            dense
            :loading="loading"
            @click="reloadTable"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- TABLA -->
    <q-card flat bordered>
      <q-table
        ref="tablePreanalitica"
        :rows="rows"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        :loading="loading"
        v-model:pagination="pagination"
        :rows-per-page-options="[50, 100, 150,200,300]"
        @request="onRequest"
      >
        <template #top>
          <div class="row items-center full-width q-pa-xs">
            <div class="col">
              <div class="text-subtitle1">Solicitudes</div>
              <div class="text-caption text-grey-7">
                Mostrando solicitudes con estado <b>CREADO</b>
              </div>
            </div>
          </div>
        </template>

        <!-- COLUMNA PACIENTE BONITA -->
        <template #body-cell-paciente="props">
          <q-td :props="props">
            <div class="text-weight-medium">
              {{ props.row.paciente_nombre || props.row.paciente?.nombre_completo }}
            </div>
            <div class="text-caption text-grey-7">
              CI: {{ props.row.paciente_ci || props.row.paciente?.ci || '-' }}
            </div>
          </q-td>
        </template>

        <!-- COLUMNA ESTABLECIMIENTO -->
        <template #body-cell-establecimiento="props">
          <q-td :props="props">
            <div>{{ props.row.establecimiento_salud || '-' }}</div>
          </q-td>
        </template>

        <!-- COLUMNA TIPO ATENCIÓN -->
        <template #body-cell-tipo_atencion="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="props.row.tipo_atencion === 'SI' ? 'green-6' : 'orange-6'"
              text-color="white"
            >
              {{ props.row.tipo_atencion === 'SI' ? 'SUS' : props.row.tipo_otro || 'EXT' }}
            </q-chip>
          </q-td>
        </template>

        <!-- COLUMNA ESTADO -->
        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="(props.row.estado === 'ANALIZADO' || props.row.estado === 'ENVIADO_ANALITICA') ? 'green-6' : (props.row.estado === 'MUESTRA NO TOMADA'?'red-6':'grey-6')"
              text-color="white"
              icon="pending"
            >
              {{ props.row.estado }}
            </q-chip>
          </q-td>
        </template>

        <!-- COLUMNA CÓDIGO -->
        <template #body-cell-codigo="props">
          <q-td :props="props">
            <span v-if="props.row.codigo" class="text-bold text-primary">{{ props.row.codigo }}</span>
            <span v-else class="text-negative text-caption">Sin código</span>
          </q-td>
        </template>

        <!-- COLUMNA SERVICIOS -->
        <template #body-cell-servicios_count="props">
          <q-td :props="props" class="text-center">
            <q-badge color="primary" :label="props.row.servicios?.length || 0" />
          </q-td>
        </template>
        <!--        servicios-->
        <template #body-cell-servicios="props">
          <q-td :props="props">
            <!--            lista de servicio en ul margin 0-->
            <ul class="q-pa-none q-ma-none">
              <li v-for="servicio in props.row.servicios" :key="servicio.id">
                {{ textCapitalize(servicio.nombre) }}
              </li>
            </ul>
          </q-td>
        </template>
        <!-- ACCIONES -->
        <template #body-cell-actions="props">
          <q-td :props="props" class="text-right">
            <q-btn-dropdown
              dense
              color="primary"
              no-caps
              label="Opciones"
              size="sm"
              @click.stop
            >
              <q-list dense style="min-width: 230px">
                <q-item clickable v-close-popup @click.stop="openDialogSolicitud(null, props.row, null)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section>Editar preanalítica</q-item-section>
                </q-item>

                <q-item clickable v-close-popup @click.stop="openDialogTestEmbarazo(props.row)">
                  <q-item-section avatar><q-icon name="science" /></q-item-section>
                  <q-item-section>Llenar test de embarazo</q-item-section>
                </q-item>

                <q-item
                  clickable
                  v-close-popup
                  @click.stop="printTestEmbarazo(props.row)"
                >
                  <q-item-section avatar><q-icon name="print" /></q-item-section>
                  <q-item-section>Imprimir test de embarazo</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <!-- FOOTER CON PAGINACIÓN BONITA -->
        <template #bottom="scope">
          <div class="row items-center justify-between full-width q-px-sm q-py-xs">
            <!-- Info -->
            <div class="col-12 col-sm-4 text-caption q-mb-xs q-mb-sm-none">
              Mostrando
              <b>{{ firstRowIndex(scope.pagination) }} - {{ lastRowIndex(scope.pagination) }}</b>
              de
              <b>{{ scope.pagination.rowsNumber }}</b> solicitudes
            </div>

            <!-- Controles -->
            <div class="col-12 col-sm-8">
              <div class="row items-center justify-end q-gutter-sm">
                <div class="col-auto">
                  <q-select
                    v-model="pagination.rowsPerPage"
                    :options="[50, 100, 150,200,300]"
                    dense
                    outlined
                    options-dense
                    style="width: 90px"
                    label="Filas"
                    @update:model-value="onChangeRowsPerPage"
                  />
                </div>
                <div class="col-auto">
                  <q-pagination
                    v-model="pagination.page"
                    :max="pagesNumber"
                    max-pages="7"
                    boundary-links
                    direction-links
                    icon-first="first_page"
                    icon-last="last_page"
                    icon-prev="chevron_left"
                    icon-next="chevron_right"
                    size="sm"
                    @update:model-value="onChangePage"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </q-table>
<!--      <pre>-->
<!--        {{ rows }}-->
<!--      </pre>-->
    </q-card>
    <!--    dialogConsentimiento-->
    <q-dialog
      v-model="dialogConsentimiento"
      transition-show="jump-down"
      transition-hide="jump-up"
    >
      <q-card class="q-pa-none" style="max-width: 900px;width: 600px">
        <!-- HEADER -->
        <q-card-section class="bg-indigo-8 text-white">
          <div class="row items-center no-wrap">
            <div class="col">
              <div class="text-subtitle1 flex items-center q-gutter-sm">
                <q-icon name="inventory_2" />
                <span>Detalle de Solicitud</span>
              </div>
              <div class="text-caption q-mt-xs">
                Código muestra:
                <span class="text-bold">
              {{ consentimiento.codigo || 'Sin código' }}
              {{ consentimiento.nro_registro ? (' - ' + consentimiento.nro_registro) : '' }}
            </span>
              </div>
            </div>

            <div class="col-auto column items-end q-gutter-xs">
              <!-- Estado -->
              <q-chip
                dense
                square
                :color="consentimiento.estado === 'CREADO' ? 'blue-5' : 'grey-6'"
                text-color="white"
                icon="pending"
              >
                {{ consentimiento.estado || 'SIN ESTADO' }}
              </q-chip>

              <!-- Tipo de atención -->
              <q-chip
                dense
                square
                :color="consentimiento.tipo_atencion === 'SI' ? 'green-5' : 'orange-5'"
                text-color="white"
                icon="health_and_safety"
              >
                {{ consentimiento.tipo_atencion === 'SI'
                ? 'SUS SI'
                : (consentimiento.tipo_otro || 'SUS NO') }}
              </q-chip>
            </div>

            <div class="col-auto">
              <q-btn
                dense
                flat
                round
                icon="close"
                v-close-popup
              />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <!-- CONTENIDO SCROLLEABLE -->
        <q-card-section class="q-pa-none">
          <div>
            <div class="q-pa-md q-gutter-md">
              <div>
                <div class="text-subtitle2 text-primary q-mb-xs">
                  Datos de la solicitud
<!--                  q input cambiar numero de muestra y codigo-->
<!--                  <pre>{{consentimiento.codigo}}</pre>-->
                  <div class="row">
                    <div class="col-12 col-md-3">
                      <q-input
                        v-model="consentimiento.codigo"
                        debounce="2000"
                        @update:model-value="onCodigoChange"
                        label="Número de muestra"
                        dense
                        outlined
                        class="q-mt-sm"/>
                    </div>
                    <div class="col-12 col-md-3">
                      <q-input
                        v-model="consentimiento.nro_registro"
                        label="Número de registro"
                        @update:model-value="onCodigoChange"
                        dense
                        outlined
                        class="q-mt-sm"/>
                    </div>
                  </div>
<!--                  <pre>{{consentimiento.nro_registro}}</pre>-->
                </div>
                <q-separator spaced />

                <div class="row q-col-gutter-md">
                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Fecha de solicitud</div>
                    <div class="text-body2">
                      {{ consentimiento.fecha_creacion || '-' }}
                    </div>

                    <div class="text-caption text-grey-7 q-mt-sm">
                      Fecha recepción preanalítica
                    </div>
                    <div class="text-body2">
                      {{ consentimiento.fecha_envio_analitica || '-' }}
                    </div>

                    <div class="text-caption text-grey-7 q-mt-sm">
                      Tiempo de atención
                    </div>
                    <div>
                      <q-chip
                        dense
                        color="blue-6"
                        text-color="white"
                        icon="access_time"
                      >
                        {{
                          tiempoAtencion(
                            consentimiento.fecha_creacion,
                            consentimiento.fecha_envio_analitica
                          ) || 'No registrado'
                        }}
                      </q-chip>
                    </div>
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Responsable de entrega</div>
                    <div class="text-body2">
                      {{ consentimiento.user_preanalitica?.name || 'No asignado' }}
                    </div>

                    <div class="text-caption text-grey-7 q-mt-sm">Establecimiento</div>
                    <div class="text-body2">
                      {{ consentimiento.establecimiento_salud || '-' }}
                    </div>
                    <!--                    sala y cama-->
                    <div class="text-caption text-grey-7 q-mt-sm">Sala / Cama</div>
                    <div class="text-body2">
                      <q-chip
                        v-if="consentimiento.sala || consentimiento.cama"
                        dense
                        color="grey-5"
                        text-color="black"
                        icon="hotel"
                      >
                        {{
                          (consentimiento.sala ? consentimiento.sala + ' / ' : '') +
                          (consentimiento.cama ? consentimiento.cama : '')
                        }}
                      </q-chip>
                      <span v-else class="text-grey-7">-</span>
                      <div class="text-caption text-grey-7 q-mt-sm">Tipo de paciente</div>
                      <div class="text-body2">
                        {{ consentimiento.cama === '' || consentimiento.cama === null ? 'Ambulatorio' : 'Internado' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- BLOQUE: PACIENTE -->
              <div>
                <div class="text-subtitle2 text-primary q-mb-xs">
                  Datos del paciente
                </div>
                <q-separator spaced />

                <div class="row q-col-gutter-md">
                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Paciente</div>
                    <div class="text-body1 text-weight-medium">
                      {{
                        consentimiento.paciente_nombre
                        || consentimiento.paciente?.nombre_completo
                        || '-'
                      }}
                    </div>

                    <div class="text-caption text-grey-7">Edad</div>
                    <div class="text-body2">
                      {{ consentimiento.edad || consentimiento.paciente?.edad || '-' }} años
                    </div>
                    <div class="text-caption text-grey-7">Sexo</div>
                    <div class="text-body2">
                      {{ consentimiento.paciente?.genero || '-' }}
                    </div>
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Código</div>
                    <div class="text-body2">
                      {{ consentimiento.codigo || 'Sin código' }}
                    </div>

                    <div class="text-caption text-grey-7">Nro. de registro</div>
                    <div class="text-body2">
                      {{ consentimiento.nro_registro || 'Sin registro' }}
                    </div>
                    <div class="text-caption text-grey-7">Codigo Muestra</div>
                    <div class="text-body2">
                      <!--                      {{ consentimiento.codigo + '-' + consentimiento.nro_registro?.slice(0,3) || 'Sin código muestra' }} mejora para que no haya nunn ni undefinifd-->
                      {{ consentimiento.codigo
                      ? (consentimiento.nro_registro
                        ? consentimiento.codigo + '-' + consentimiento.nro_registro.slice(0,3)
                        : consentimiento.codigo + '-000')
                      : 'Sin código muestra'
                      }}
                    </div>
                  </div>
                  <div class="col-12">
                  </div>
                </div>
              </div>

              <!-- BLOQUE: DOCTOR SOLICITANTE -->
              <div>
                <div class="text-subtitle2 text-primary q-mb-xs">
                  Médico solicitante
                </div>
                <q-separator spaced />
                <div class="row q-col-gutter-md">
                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Médico</div>
                    <div class="text-body1 text-weight-medium">
                      {{
                        consentimiento.doctor_nombre
                        || consentimiento.doctor?.name
                        || '-'
                      }}
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="text-caption text-grey-7">Especialidad</div>
                    <div class="text-body2">
                      {{ consentimiento.doctor?.especialidad || '-' }}
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="text-caption text-grey-7 q-mt-sm">Diagnóstico clínico</div>
                    <div class="text-body1">
                      {{ consentimiento.diagnostico_select || '-' }}
                    </div>
                    <div class="text-body2">
                      {{ consentimiento.diagnostico_clinico || '-' }}
                    </div>
                    <!--                    <pre>{{ consentimiento.diagnostico_select}}</pre>-->
                  </div>
                </div>
              </div>

              <!-- BLOQUE: SERVICIOS -->
              <div>
                <div class="text-subtitle2 text-primary q-mb-xs">
                  Servicios solicitados
                </div>
                <q-separator spaced />

                <div v-if="consentimiento.servicios && consentimiento.servicios.length">
                  <q-list bordered separator dense>
                    <q-item
                      v-for="servicio in consentimiento.servicios"
                      :key="servicio.id"
                      class="q-py-xs"
                    >
                      <q-item-section avatar>
                        <q-icon name="biotech" />
                      </q-item-section>

                      <q-item-section>
                        <div class="text-body2">
                          {{ textCapitalize(servicio.nombre) }}
                        </div>
                        <div class="text-caption text-grey-7">
                          {{ textCapitalize(servicio.area?.name) }}
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </div>
                <div v-else class="text-caption text-grey-7">
                  No hay servicios registrados.
                </div>
              </div>
              <div>
<!--                <pre>{{consentimiento.pre_analitica_muestras}}</pre>-->
<!--                [-->
<!--                {-->
<!--                "id": 869,-->
<!--                "area_tipo_muestra_id": 4,-->
<!--                "solicitude_id": 120,-->
<!--                "estado": "Pendiente",-->
<!--                "nombre": "Suero",-->
<!--                "selected": 0,-->
<!--                "area_tipo_muestra": {-->
<!--                "id": 4,-->
<!--                "area_id": 2,-->
<!--                "tipo_muestra": "Suero",-->
<!--                "area": {-->
<!--                "id": 2,-->
<!--                "name": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "descripcion": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "title": "QUÍMICA SANGUÍNEA Y SEROLOGÍA",-->
<!--                "estado": "ACTIVO"-->
<!--                }-->
<!--                }-->
<!--                },-->
<!--                {-->
<!--                "id": 870,-->
<!--                "area_tipo_muestra_id": 38,-->
<!--                "solicitude_id": 120,-->
<!--                "estado": "Pendiente",-->
<!--                "nombre": "Líquido",-->
<!--                "selected": 0,-->
<!--                "area_tipo_muestra": {-->
<!--                "id": 38,-->
<!--                "area_id": 2,-->
<!--                "tipo_muestra": "Líquido",-->
<!--                "area": {-->
<!--                "id": 2,-->
<!--                "name": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "descripcion": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "title": "QUÍMICA SANGUÍNEA Y SEROLOGÍA",-->
<!--                "estado": "ACTIVO"-->
<!--                }-->
<!--                }-->
<!--                },-->
<!--                {-->
<!--                "id": 871,-->
<!--                "area_tipo_muestra_id": 44,-->
<!--                "solicitude_id": 120,-->
<!--                "estado": "Pendiente",-->
<!--                "nombre": "Orina",-->
<!--                "selected": 0,-->
<!--                "area_tipo_muestra": {-->
<!--                "id": 44,-->
<!--                "area_id": 2,-->
<!--                "tipo_muestra": "Orina",-->
<!--                "area": {-->
<!--                "id": 2,-->
<!--                "name": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "descripcion": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "title": "QUÍMICA SANGUÍNEA Y SEROLOGÍA",-->
<!--                "estado": "ACTIVO"-->
<!--                }-->
<!--                }-->
<!--                },-->
<!--                {-->
<!--                "id": 872,-->
<!--                "area_tipo_muestra_id": 45,-->
<!--                "solicitude_id": 120,-->
<!--                "estado": "Pendiente",-->
<!--                "nombre": "Líquidos biológicos",-->
<!--                "selected": 1,-->
<!--                "area_tipo_muestra": {-->
<!--                "id": 45,-->
<!--                "area_id": 2,-->
<!--                "tipo_muestra": "Líquidos biológicos",-->
<!--                "area": {-->
<!--                "id": 2,-->
<!--                "name": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "descripcion": "QUÍMICA SANGUÍNEA Y SEROLOGÍA (Area 3)",-->
<!--                "title": "QUÍMICA SANGUÍNEA Y SEROLOGÍA",-->
<!--                "estado": "ACTIVO"-->
<!--                }-->
<!--                }-->
<!--                }-->
<!--                ]-->

                <div class="q-ml-md q-mt-xs">
                  <template v-for="atm in areas_tipo_muestras" :key="atm.id">
                    <div class="text-subtitle2 text-primary q-mb-xs">
                      {{ atm.name }}
                    </div>
                    <q-separator spaced />
                    <div class="q-mb-md">
                      <q-checkbox
                        v-for="tipo_muestra in atm.area_tipo_muestras"
                        :key="tipo_muestra.id"
                        v-model="tipo_muestra.selected"
                        :label="tipo_muestra.tipo_muestra"
                        :true-value="true"
                        :false-value="false"
                      />
                    </div>
                  </template>
                </div>
              </div>

              <div>
                <div class="text-subtitle2 text-primary q-mb-xs">
                  Comentarios
                </div>
                <q-separator spaced />

                <q-input
                  v-model="comentarioNuevo"
                  type="textarea"
                  autogrow
                  dense
                  outlined
                  label="Agregar comentario"
                />

                <div class="text-right q-mt-sm">
                  <q-btn
                    color="primary"
                    icon="add_comment"
                    label="Agregar comentario"
                    no-caps
                    :loading="savingComentario"
                    @click="guardarComentarioPreanalitica"
                  />
                </div>

                <q-markup-table
                  flat
                  bordered
                  dense
                  class="q-mt-md"
                  v-if="consentimiento.pre_analitica_comentarios && consentimiento.pre_analitica_comentarios.length"
                >
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Usuario</th>
                      <th>Comentario</th>
                      <th class="text-right">Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="comentario in consentimiento.pre_analitica_comentarios"
                      :key="comentario.id"
                    >
                      <td>{{ formatoComentarioFecha(comentario.created_at) }}</td>
                      <td>{{ comentario.user?.name || 'Desconocido' }}</td>
                      <td style="white-space: pre-wrap;">{{ comentario.comentario }}</td>
                      <td class="text-right">
                        <q-btn
                          v-if="puedeEliminarComentario(comentario)"
                          flat
                          dense
                          round
                          color="negative"
                          icon="delete"
                          @click="eliminarComentarioPreanalitica(comentario)"
                        />
                      </td>
                    </tr>
                  </tbody>
                </q-markup-table>

                <div v-else class="text-caption text-grey-7 q-mt-md">
                  No hay comentarios registrados.
                </div>
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <q-card-actions class="bg-grey-1 row items-center">
          <q-space />
          <q-btn
            flat
            label="Cerrar"
            color="primary"
            icon="close"
            v-close-popup
            no-caps
          />
          <q-btn
            unelevated
            color="primary"
            icon="save"
            :loading="savingPre"
            no-caps
            :label="etiquetaGuardarPreanalitica()"
            @click="guardarPreAnalitica"
          />
        </q-card-actions>

      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogTestEmbarazo" persistent>
      <q-card style="width: 520px; max-width: 95vw;">
        <q-card-section class="bg-primary text-white">
          <div class="text-subtitle1">Test de embarazo</div>
          <div class="text-caption">
            {{ testEmbarazoSolicitud?.paciente_nombre || testEmbarazoSolicitud?.paciente?.nombre_completo || '-' }}
            <span v-if="testEmbarazoSolicitud?.codigo"> | {{ testEmbarazoSolicitud.codigo }}</span>
          </div>
        </q-card-section>

        <q-card-section class="q-gutter-sm">
          <q-select
            v-model="testEmbarazoForm.test_embarazo"
            :options="['Positivo', 'Negativo']"
            label="Resultado"
            dense
            outlined
            options-dense
            :disable="loadingTestEmbarazo || savingTestEmbarazo || printingTestEmbarazo"
          />
          <div class="text-caption text-grey-7" v-if="testEmbarazoForm.quimica_code">
            Código Química: <b>{{ testEmbarazoForm.quimica_code }}</b>
          </div>
          <div class="text-caption text-primary" v-if="loadingTestEmbarazo">
            Cargando datos del test...
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="grey-8" v-close-popup :disable="savingTestEmbarazo || printingTestEmbarazo" />
          <q-btn
            flat
            label="Imprimir"
            icon="print"
            color="primary"
            :disable="!testEmbarazoForm.test_embarazo"
            :loading="printingTestEmbarazo"
            @click="printTestEmbarazo(testEmbarazoSolicitud)"
          />
          <q-btn
            color="primary"
            icon="save"
            label="Guardar"
            :loading="savingTestEmbarazo"
            @click="saveTestEmbarazo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <!--    dialogRechazado-->
<!--    <q-dialog-->
<!--      v-model="dialogRechazado"-->
<!--      persistent-->
<!--      transition-show="jump-down"-->
<!--      transition-hide="jump-up"-->
<!--    >-->
<!--      <q-card class="q-pa-md" style="max-width: 95vh;width: 650px">-->
<!--        <div class="text-h6 text-center bg-grey-8 text-white">-->
<!--          SOLICITUDES CON MUESTRAS RECHAZADAS-->
<!--        </div>-->
<!--        <q-card-section class="q-pa-xs">-->
<!--          <q-markup-table-->
<!--            :rows="rows.filter(r => r.estado === 'MUESTRA RECHAZADA')"-->
<!--            :columns="columns"-->
<!--            row-key="id"-->
<!--            dense-->
<!--            flat-->
<!--            bordered-->
<!--          >-->
<!--            <thead>-->
<!--            <tr>-->
<!--              &lt;!&ndash;              <th>Acciones</th>&ndash;&gt;-->
<!--              <th>Fecha Solicitud</th>-->
<!--              <th>Estado</th>-->
<!--              <th>Paciente</th>-->
<!--              <th>Código</th>-->
<!--              <th>Médico Solicitante</th>-->
<!--              &lt;!&ndash;                <th>Prestaciones</th>&ndash;&gt;-->
<!--              &lt;!&ndash;                <th>Establecimiento</th>&ndash;&gt;-->
<!--              &lt;!&ndash;                <th>Tipo atención</th>&ndash;&gt;-->
<!--              &lt;!&ndash;                <th># Prestaciones</th>&ndash;&gt;-->
<!--              &lt;!&ndash;                <th>Responsable Preanalítica</th>&ndash;&gt;-->
<!--            </tr>-->
<!--            </thead>-->
<!--            <tbody>-->
<!--            <tr v-for="row in rows.filter(r => r.estado === 'MUESTRA RECHAZADA')" :key="row.id">-->
<!--              &lt;!&ndash;              <td>&ndash;&gt;-->
<!--              &lt;!&ndash;                <q-btn&ndash;&gt;-->
<!--              &lt;!&ndash;                  dense&ndash;&gt;-->
<!--              &lt;!&ndash;                  no-caps&ndash;&gt;-->
<!--              &lt;!&ndash;                  outline&ndash;&gt;-->
<!--              &lt;!&ndash;                  color="deep-purple-6"&ndash;&gt;-->
<!--              &lt;!&ndash;                  icon="confirmation_number"&ndash;&gt;-->
<!--              &lt;!&ndash;                  :label="row.codigo ? 'Ver código' : 'Generar código'"-->

<!--              &lt;!&ndash;                  @click.stop="onGenerarCodigo(row)"&ndash;&gt;-->
<!--              &lt;!&ndash;                />&ndash;&gt;-->
<!--              &lt;!&ndash;              </td>&ndash;&gt;-->
<!--              <td>{{ row.fecha_creacion ? moment(row.fecha_creacion).format('DD/MM/YYYY HH:mm') : '' }}</td>-->
<!--              <td>-->
<!--                <q-chip-->
<!--                  dense-->
<!--                  :color="row.estado === 'CREADO' ? 'blue-6' : (row.estado === 'MUESTRA RECHAZADA'?'red-6':'grey-6')"-->
<!--                  text-color="white"-->
<!--                  icon="pending"-->
<!--                >-->
<!--                  {{ row.estado }}-->
<!--                </q-chip>-->
<!--              </td>-->
<!--              <td>-->
<!--                <div class="text-weight-medium">-->
<!--                  {{ row.paciente_nombre || row.paciente?.nombre_completo }}-->
<!--                </div>-->
<!--                <div class="text-caption text-grey-7">-->
<!--                  CI: {{ row.paciente_ci || row.paciente?.ci || '-' }}-->
<!--                </div>-->
<!--              </td>-->
<!--              <td>-->
<!--                <div v-if="row.codigo">-->
<!--                    <span class="text-bold">-->
<!--                      {{ row.codigo }} - -->
<!--                      {{ row.nro_registro }}-->
<!--                    </span>-->
<!--                </div>-->
<!--                <div v-else class="text-negative text-caption">-->
<!--                  Sin código-->
<!--                </div>-->
<!--              </td>-->
<!--              <td>-->
<!--                &lt;!&ndash; mostrar array de solicitudes rechazadas en formato json bonita &ndash;&gt;-->
<!--                <div>-->
<!--                  <div class="text-weight-medium">-->
<!--                    {{row.solicitud_rechazadas.length}} motivos de rechazo <br>-->
<!--                    <small class="text-grey-7">-->
<!--                      &lt;!&ndash;                        {{ row.solicitud_rechazadas.map(r => r.motivo).join('; ') }}&ndash;&gt;-->
<!--                      &lt;!&ndash;                        mostrar medico ara motivo&ndash;&gt;-->
<!--                      {{ row.solicitud_rechazadas.map(r => r.motivo + ' (por: ' + (r.user?.name || 'Desconocido') + ')').join('; ') }}-->
<!--                      <br>-->
<!--                      {{ row.solicitud_rechazadas.map(r => 'Área: ' + (r.area?.title || 'Desconocida')).join('; ') }}-->
<!--                    </small>-->
<!--                  </div>-->
<!--                </div>-->
<!--                &lt;!&ndash;                  <pre>{{row.solicitud_rechazadas}}</pre>&ndash;&gt;-->
<!--                &lt;!&ndash;                  [&ndash;&gt;-->
<!--                &lt;!&ndash;                  {&ndash;&gt;-->
<!--                &lt;!&ndash;                  "id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "solicitude_id": 75,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "motivo": "aas",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "fecha_hora": "2026-01-14 05:41:26",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "area_id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "user_id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "user": {&ndash;&gt;-->
<!--                &lt;!&ndash;                  "id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "name": "Admin User",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "username": "admin",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "role": "Administrador",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "area_id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "avatar": "default.png",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "email": "",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "email_verified_at": null,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "establecimiento_id": 1&ndash;&gt;-->
<!--                &lt;!&ndash;                  },&ndash;&gt;-->
<!--                &lt;!&ndash;                  "area": {&ndash;&gt;-->
<!--                &lt;!&ndash;                  "id": 1,&ndash;&gt;-->
<!--                &lt;!&ndash;                  "name": "HEMATOLOGÍA (Area 2)",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "descripcion": "HEMATOLOGÍA (Area 2)",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "title": "HEMATOLOGÍA",&ndash;&gt;-->
<!--                &lt;!&ndash;                  "estado": "ACTIVO"&ndash;&gt;-->
<!--                &lt;!&ndash;                  }&ndash;&gt;-->
<!--                &lt;!&ndash;                  }&ndash;&gt;-->
<!--                &lt;!&ndash;                  ]&ndash;&gt;-->
<!--                &lt;!&ndash;                  {{ row.doctor_nombre || row.doctor?.name || '' }}&ndash;&gt;-->
<!--              </td>-->
<!--              &lt;!&ndash;                <td>{{ row.servicios ? row.servicios.map(s => s.nombre).join(', ') : '' }}</td>&ndash;&gt;-->
<!--              &lt;!&ndash;                <td>{{ row.establecimiento_salud || '-' }}</td>&ndash;&gt;-->
<!--              &lt;!&ndash;                <td>&ndash;&gt;-->
<!--              &lt;!&ndash;                  <q-chip&ndash;&gt;-->
<!--              &lt;!&ndash;                    dense&ndash;&gt;-->
<!--              &lt;!&ndash;                    :color="row.tipo_atencion === 'SI' ? 'green-6' : 'orange-6'"&ndash;&gt;-->
<!--              &lt;!&ndash;                    text-color="white"&ndash;&gt;-->
<!--              &lt;!&ndash;                  >&ndash;&gt;-->
<!--              &lt;!&ndash;                    {{ row.tipo_atencion === 'SI' ? 'SUS' : row.tipo_otro || 'EXT' }}&ndash;&gt;-->
<!--              &lt;!&ndash;                  </q-chip>&ndash;&gt;-->
<!--              &lt;!&ndash;                </td>&ndash;&gt;-->
<!--              &lt;!&ndash;                <td class="text-center">&ndash;&gt;-->
<!--              &lt;!&ndash;                  <q-badge color="primary" :label="row.servicios ? row.servicios.length : 0" />&ndash;&gt;-->
<!--              &lt;!&ndash;                </td>&ndash;&gt;-->
<!--              &lt;!&ndash;                <td>{{ row.user_preanalitica ? row.user_preanalitica.name : 'No asignado' }}</td>&ndash;&gt;-->
<!--            </tr>-->
<!--            </tbody>-->
<!--          </q-markup-table>-->
<!--        </q-card-section>-->
<!--        <q-card-actions align="center" class="bg-grey-1 q-mt-md">-->
<!--          <q-btn-->
<!--            flat-->
<!--            label="Cerrar"-->
<!--            color="primary"-->
<!--            icon="close"-->
<!--            v-close-popup-->
<!--            no-caps-->
<!--          />-->
<!--        </q-card-actions>-->
<!--      </q-card>-->
<!--    </q-dialog>-->

  </q-page>
</template>

<script>
import moment from 'moment'
import consentimientos from "pages/consentimientos/Consentimientos.vue";
import { printSolicitudPreanalitica } from 'src/utils/printSolicitudPreanalitica'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'


export default {
  name: 'AreaPreanaliticaPage',
  data () {
    return {
      dialogConsentimiento: false,
      dialogTestEmbarazo: false,
      rows: [],
      from: moment().startOf('month').format('YYYY-MM-DD'),
      to: moment().endOf('month').format('YYYY-MM-DD'),
      loading: false,
      moment: moment,
      filter: '',
      loadingRowId: null,
      savingComentario: false,
      comentarioNuevo: '',
      dialogRechazado: false,
      savingPre: false,          // 👈 nuevo
      selectedMuestras: [],      // 👈 nuevo: ids de area_tipo_muestras
      consentimiento: null,
      testEmbarazoSolicitud: null,
      savingTestEmbarazo: false,
      loadingTestEmbarazo: false,
      printingTestEmbarazo: false,
      testEmbarazoForm: {
        solicitude_id: null,
        test_embarazo: null,
        quimica_code: null
      },
      areas_tipo_muestras: [],
      areas_tipo_muestrasAll: [],
      pagination: {
        page: 1,
        rowsPerPage: 10,
        rowsNumber: 0,
        sortBy: 'id',
        descending: true
      },
      columns: [
        { name: 'actions', label: 'Opciones', field: 'id', align: 'left' },
        {
          name: 'codigo',
          label: 'Código',
          field: 'codigo',
          align: 'left',
          style: 'font-weight:600'
        },
        {
          name: 'fecha_creacion',
          label: 'Fecha Solicitud',
          field: row => row.fecha_creacion,
          format: val => (val ? moment(val).format('DD/MM/YYYY HH:mm') : ''),
          sortable: true,
          align: 'left'
        },
        {
          name: 'fecha_envio_analitica',
          label: 'Fecha Envio Analítica',
          field: row => row.fecha_envio_analitica,
          format: val => (val ? moment(val).format('DD/MM/YYYY HH:mm') : ''),
          sortable: true,
          align: 'left'
        },
        {
          name: 'user_preanalitica',
          label: 'Responsable Preanalítica',
          field: row => row.user_preanalitica ? row.user_preanalitica.name : 'No asignado',
          sortable: true,
          align: 'left'
        },
        {
          name: 'estado',
          label: 'Estado',
          field: 'estado',
          align: 'left'
        },
        {
          name: 'paciente',
          label: 'Paciente',
          field: row => row.paciente_nombre || (row.paciente && row.paciente.nombre_completo) || '',
          align: 'left'
        },
        {
          name: 'doctor',
          label: 'Médico Solicitante',
          field: row => row.doctor_nombre || (row.doctor && row.doctor.name) || '',
          align: 'left'
        },
        {
          name: 'servicios',
          label: 'Prestaciones',
          field: row => row.servicios ? row.servicios.map(s => s.nombre).join(', ') : '',
          align: 'left'
        },
        {
          name: 'establecimiento',
          label: 'Establecimiento',
          field: row => row.establecimiento_salud,
          align: 'left'
        },
        {
          name: 'tipo_atencion',
          label: 'Tipo atención',
          field: 'tipo_atencion',
          align: 'left'
        },
        {
          name: 'servicios_count',
          label: '# Prestaciones',
          field: row => (row.servicios ? row.servicios.length : 0),
          align: 'center'
        },
        //   reponsable user_preanalitica_id
        {
          name: 'responsable',
          label: 'Responsable Preanalítica',
          field: row => row.user_preanalitica ? row.user_preanalitica.name : 'No asignado',
          align: 'left'
        }
      ]
    }
  },
  computed: {
    consentimientos() {
      return consentimientos
    },
    pagesNumber () {
      const { rowsPerPage, rowsNumber } = this.pagination
      if (!rowsPerPage || rowsPerPage <= 0) return 1
      return Math.max(1, Math.ceil(rowsNumber / rowsPerPage))
    }
  },
  mounted () {
    this.reloadTable()
    this.areasTipoMuestrasGet()
  },
  watch: {
    from () { this.resetToFirstPageAndReload() },
    to ()   { this.resetToFirstPageAndReload() },
    filter () { this.resetToFirstPageAndReload() }
  },
  methods: {
    hasTestEmbarazo (row) {
      return !!(row?._test_embarazo || row?.quimica_sanguinea?.test_embarazo)
    },
    async openDialogTestEmbarazo (row) {
      if (!row?.id) return

      this.dialogTestEmbarazo = true
      this.loadingTestEmbarazo = true
      this.$q.loading.show({ message: 'Cargando test de embarazo...' })

      this.testEmbarazoSolicitud = row
      this.testEmbarazoForm = {
        solicitude_id: row.id,
        test_embarazo: row._test_embarazo || null,
        quimica_code: row._quimica_code || null
      }

      try {
        const { data } = await this.$axios.get(`solicitudes/${row.id}/test-embarazo`)
        this.testEmbarazoSolicitud = data?.solicitud || row
        this.testEmbarazoForm = {
          solicitude_id: row.id,
          test_embarazo: data?.test_embarazo || null,
          quimica_code: data?.quimica_code || null
        }
        row._test_embarazo = data?.test_embarazo || null
        row._quimica_code = data?.quimica_code || null
      } catch (err) {
        const msg = err.response?.data?.message || err.message
        this.$alert?.error ? this.$alert.error('Error al cargar test de embarazo: ' + msg) : null
      } finally {
        this.loadingTestEmbarazo = false
        this.$q.loading.hide()
      }
    },
    async saveTestEmbarazo () {
      if (!this.testEmbarazoForm.solicitude_id) return
      if (!this.testEmbarazoForm.test_embarazo) {
        this.$alert?.error ? this.$alert.error('Seleccione un resultado para el test de embarazo.') : null
        return
      }

      this.savingTestEmbarazo = true
      this.$q.loading.show({ message: 'Guardando test de embarazo...' })
      try {
        const { data } = await this.$axios.post(
          `solicitudes/${this.testEmbarazoForm.solicitude_id}/test-embarazo`,
          { test_embarazo: this.testEmbarazoForm.test_embarazo }
        )
        this.testEmbarazoForm.quimica_code = data?.quimica_code || null

        const idx = this.rows.findIndex(r => r.id === this.testEmbarazoForm.solicitude_id)
        if (idx !== -1) {
          this.rows[idx]._test_embarazo = this.testEmbarazoForm.test_embarazo
          this.rows[idx]._quimica_code = this.testEmbarazoForm.quimica_code
        }

        this.$alert?.success ? this.$alert.success('Test de embarazo guardado correctamente.') : null
      } catch (err) {
        const msg = err.response?.data?.message || err.message
        this.$alert?.error ? this.$alert.error('Error al guardar test de embarazo: ' + msg) : null
      } finally {
        this.savingTestEmbarazo = false
        this.$q.loading.hide()
      }
    },
    async printTestEmbarazo (row) {
      const id = row?.id || this.testEmbarazoForm.solicitude_id
      if (!id) return

      this.printingTestEmbarazo = true
      this.$q.loading.show({ message: 'Preparando impresión...' })

      try {
        let quimicaCode = row?._quimica_code || this.testEmbarazoForm.quimica_code || null

        if (!this.hasTestEmbarazo(row || { _test_embarazo: this.testEmbarazoForm.test_embarazo })) {
          const { data } = await this.$axios.get(`solicitudes/${id}/test-embarazo`)
          if (!data?.test_embarazo) {
            this.$alert?.error ? this.$alert.error('Debe llenar el test de embarazo antes de imprimir.') : null
            return
          }
          if (row) {
            row._test_embarazo = data.test_embarazo
            row._quimica_code = data.quimica_code || null
          }
          quimicaCode = data?.quimica_code || quimicaCode
        }

        if (!quimicaCode) {
          const { data } = await this.$axios.get(`solicitudes/${id}/test-embarazo`)
          quimicaCode = data?.quimica_code || null
          if (!quimicaCode) {
            this.$alert?.error ? this.$alert.error('No se encontró código de Química para imprimir.') : null
            return
          }
        }
        const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${quimicaCode}/pdf`
        window.open(url, '_blank')
      } catch (err) {
        const msg = err.response?.data?.message || err.message
        this.$alert?.error ? this.$alert.error('Error al imprimir test de embarazo: ' + msg) : null
      } finally {
        this.printingTestEmbarazo = false
        this.$q.loading.hide()
      }
    },
    onCodigoChange() {
      // actualizar codigo y nro_registro en backend
      if (!this.consentimiento) return
      this.$axios.post(`solicitudes/${this.consentimiento.id}/actualizar-codigo`, {
        codigo: this.consentimiento.codigo,
        nro_registro: this.consentimiento.nro_registro
      })
        .then(res => {
          this.$alert.success('Código actualizado correctamente.')
          this.reloadTable()
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          if (this.$alert && this.$alert.error) {
            this.$alert.error('Error al actualizar código: ' + msg)
          }
        })
    },
    imprimirSolicitud () {
      const base = this.$axios.defaults.baseURL
      const qs = new URLSearchParams({
        from: this.from || '',
        to: this.to || '',
        filter: this.filter || ''
      }).toString()

      // abre en otra pestaña (stream del PDF)
      window.open(`${base}/solicitudes-area-preanalitica/pdf?${qs}`, '_blank')
    },
    areasTipoMuestrasGet(){
      this.$axios.get('areas-tipo-muestras')
        .then(res => {
          this.areas_tipo_muestrasAll = res.data || []
        })
        .catch(err => {
          console.error(err)
          this.$alert && this.$alert.error
            ? this.$alert.error('Error al cargar tipos de muestras')
            : null
        })
    },
    marcarMuestraNoTomada() {
      if (!this.consentimiento) return
      // $q. dalog motivo_rechazo
      this.$q.dialog({
        title: 'Motivo de rechazo',
        message: 'Por favor, ingrese el motivo por el cual la muestra no fue tomada:',
        prompt: {
          model: '',
          type: 'text',
          isValid: val => val.length > 0
        },
        cancel: true,
        persistent: true
      }).onOk(motivo => {
        // enviar motivo al backend
        this.$axios.post(`solicitudes/${this.consentimiento.id}/marcar-muestra-no-tomada`, {
          motivo_rechazo: motivo
        })
          .then(res => {
            this.$alert.success('La muestra ha sido marcada como no tomada.')
            this.dialogConsentimiento = false
            this.reloadTable()
          })
          .catch(err => {
            console.error(err)
            const msg = err.response?.data?.message || err.message
            if (this.$alert && this.$alert.error) {
              this.$alert.error('Error al marcar muestra como no tomada: ' + msg)
            }
          })
      })
    },
    guardarPreAnalitica () {
      if (!this.consentimiento) return

      if (!this.consentimiento.codigo) {
        this.$alert.error('La solicitud debe tener un código antes de enviarla a Distribución.')
        return
      }

      // debe selecionar al menos un tipo de muestra
      const muestrasSeleccionadas = []
      this.areas_tipo_muestras.forEach(area => {
        area.area_tipo_muestras.forEach(tm => {
          if (tm.selected) {
            muestrasSeleccionadas.push(tm.id)
          }
        })
      })
      if (muestrasSeleccionadas.length === 0) {
        this.$alert.error('Debe seleccionar al menos un tipo de muestra para continuar.')
        return
      }

      this.savingPre = true
      this.$axios.post(`solicitudes/${this.consentimiento.id}/pre-analitica`, {
        area_tipo_muestras: this.areas_tipo_muestras
      })
        .then(res => {
          this.$alert.success('Muestras guardadas y solicitud enviada a Distribución')
          this.dialogConsentimiento = false
          this.reloadTable()
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          if (this.$alert && this.$alert.error) {
            this.$alert.error('Error al guardar muestras: ' + msg)
          }
        })
        .finally(() => {
          this.savingPre = false
        })
    },
    guardarComentarioPreanalitica () {
      if (!this.consentimiento?.id) return
      if (!String(this.comentarioNuevo || '').trim()) {
        this.$alert?.error?.('Escriba un comentario antes de guardar.')
        return
      }

      this.savingComentario = true
      this.$axios.post(`solicitudes/${this.consentimiento.id}/pre-analitica-comentarios`, {
        comentario: this.comentarioNuevo
      })
        .then(res => {
          if (!Array.isArray(this.consentimiento.pre_analitica_comentarios)) {
            this.consentimiento.pre_analitica_comentarios = []
          }
          this.consentimiento.pre_analitica_comentarios.unshift(res.data)
          this.comentarioNuevo = ''
          this.$alert?.success?.('Comentario agregado')
        })
        .catch(err => {
          const msg = err.response?.data?.message || err.message
          this.$alert?.error?.('Error al guardar comentario: ' + msg)
        })
        .finally(() => {
          this.savingComentario = false
        })
    },
    eliminarComentarioPreanalitica (comentario) {
      if (!this.consentimiento?.id || !comentario?.id) return

      this.$axios.delete(`solicitudes/${this.consentimiento.id}/pre-analitica-comentarios/${comentario.id}`)
        .then(() => {
          this.consentimiento.pre_analitica_comentarios =
            (this.consentimiento.pre_analitica_comentarios || []).filter(item => item.id !== comentario.id)
          this.$alert?.success?.('Comentario eliminado')
        })
        .catch(err => {
          const msg = err.response?.data?.message || err.message
          this.$alert?.error?.('Error al eliminar comentario: ' + msg)
        })
    },
    puedeEliminarComentario (comentario) {
      return Number(comentario?.user_id) === Number(this.$store.user?.id)
    },
    formatoComentarioFecha (fecha) {
      return fecha ? moment(fecha).format('DD/MM/YYYY HH:mm') : '-'
    },
    etiquetaGuardarPreanalitica () {
      return ['CREADO', 'ATENDIENDO'].includes(this.consentimiento?.estado)
        ? 'Guardar y Enviar a Distribución'
        : 'Guardar cambios'
    },
    textCapitalize(text) {
      if (!text) return ''
      return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase()
    },
    tiempoAtencion(fechaSolicitud, fechaAtencion) {
      if (!fechaSolicitud || !fechaAtencion) return null;

      const inicio = moment(fechaSolicitud);
      const fin = moment(fechaAtencion);

      const duracion = moment.duration(fin.diff(inicio));

      const dias = duracion.days();
      const horas = duracion.hours();
      const minutos = duracion.minutes();
      const segundos = duracion.seconds();

      let resultado = '';
      if (dias > 0) resultado += `${dias} d `;
      if (horas > 0) resultado += `${horas} h `;
      if (minutos > 0) resultado += `${minutos} m`;
      if (dias === 0 && horas === 0 && minutos === 0) {
        resultado += `${segundos} s`;
      }

      return resultado.trim();
    },
    showRejected() {
      this.dialogRechazado = true
    },
    openDialogSolicitud (action, row, index) {
      this.consentimiento = row
      if (!Array.isArray(this.consentimiento.pre_analitica_comentarios)) {
        this.consentimiento.pre_analitica_comentarios = []
      }
      this.comentarioNuevo = ''
      // cargar seleccionadas desde backend (relación area_tipo_muestras de la solicitud)
      // this.selectedMuestras = (row.area_tipo_muestras || []).map(m => m.id)
      // console.log(row)
      this.dialogConsentimiento = true
      const guardadas = row.pre_analitica_muestras || []
      const guardadasIds = new Set(guardadas.map(item => item.area_tipo_muestra_id))
      const linkedIds = new Set()
      const areas = []

      ;(row.servicios || []).forEach(servicio => {
        if (servicio.area && !areas.some(a => a.id === servicio.area.id)) {
          areas.push(servicio.area)
        }

        ;(servicio.tipos_muestra || []).forEach(tipo => {
          linkedIds.add(tipo.id)
        })
      })

      this.areas_tipo_muestras = areas.map(area => ({
        id: area.id,
        name: area.name,
        area_tipo_muestras: this.areas_tipo_muestrasAll
          .filter(tm => tm.area_id === area.id)
          .map(tm => ({
            ...tm,
            selected: guardadas.length > 0 ? guardadasIds.has(tm.id) : linkedIds.has(tm.id)
          }))
      }))
    },
    // Utils info footer
    firstRowIndex (pag) {
      if (pag.rowsNumber === 0) return 0
      return (pag.page - 1) * pag.rowsPerPage + 1
    },
    lastRowIndex (pag) {
      if (pag.rowsNumber === 0) return 0
      const last = pag.page * pag.rowsPerPage
      return last > pag.rowsNumber ? pag.rowsNumber : last
    },

    clearFilter () {
      this.filter = ''
      this.reloadTable()
    },

    resetToFirstPageAndReload () {
      if (this.pagination.page !== 1) {
        this.pagination.page = 1
      }
      this.reloadTable()
    },

    reloadTable () {
      if (this.$refs.tablePreanalitica) {
        this.$refs.tablePreanalitica.requestServerInteraction()
      } else {
        this.fetchFromServer(this.pagination, this.filter)
      }
    },

    onRequest (props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination
      this.pagination.page = page
      this.pagination.rowsPerPage = rowsPerPage
      this.pagination.sortBy = sortBy
      this.pagination.descending = descending

      this.fetchFromServer(this.pagination, this.filter)
    },

    fetchFromServer (pagination, filter) {
      this.loading = true
      const isNumeric = /^\d+$/.test((filter || '').trim())
      this.$axios.get('solicitudes-area-preanalitica-estado', {
        params: {
          from: this.from,
          to: this.to,
          codigo: isNumeric ? (filter || '') : '',
          page: pagination.page,
          per_page: pagination.rowsPerPage,
          filter: isNumeric ? '' : (filter || '')
        }
      })
        .then(res => {
          this.rows = res.data.data || []
          this.pagination.rowsNumber = res.data.total || 0
          this.pagination.page = res.data.current_page || pagination.page
        })
        .catch(err => {
          console.error(err)
          this.$alert && this.$alert.error
            ? this.$alert.error('Error al cargar solicitudes')
            : null
        })
        .finally(() => {
          this.loading = false
        })
    },

    onChangePage (page) {
      this.pagination.page = page
      this.reloadTable()
    },

    onChangeRowsPerPage (val) {
      this.pagination.rowsPerPage = val
      this.pagination.page = 1
      this.reloadTable()
    },

    onGenerarCodigo (row) {
      this.$q.dialog({
        title: row.codigo ? 'Generar nuevo código' : 'Generar código',
        message: row.codigo
          ? '¿Está seguro de generar un nuevo código para esta solicitud? El código anterior dejará de ser válido.'
          : '¿Está seguro de generar un código para esta solicitud?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.generarCodigo(row)
      })
    },
    generarCodigo(row) {
      this.loadingRowId = row.id
      this.$axios.post(`solicitudes/${row.id}/generar-codigo`)
        .then(res => {
          this.reloadTable()
          this.$alert && this.$alert.success
            ? this.$alert.success('Código generado correctamente')
            : null
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          this.$alert && this.$alert.error
            ? this.$alert.error('Error al generar código: ' + msg)
            : null
        })
        .finally(() => {
          this.loadingRowId = null
        })
    }
  }
}
</script>
