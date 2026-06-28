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
<!--          "data": [-->
<!--          {-->
<!--          "id": 51,-->
<!--          "paciente_id": 2,-->
<!--          "doctor_id": null,-->
<!--          "codigo_solicitud": null,-->
<!--          "tipo_atencion": "SI",-->
<!--          "tipo_otro": null,-->
<!--          "fecha_solicitud": "2025-12-30",-->
<!--          "hora_solicitud": "04:32:00",-->
<!--          "establecimiento_salud": "Hospital General",-->
<!--          "zona_establecimiento": null,-->
<!--          "diagnostico_clinico": null,-->
<!--          "diagnostico_select": null,-->
<!--          "estado": "CREADO",-->
<!--          "codigo": null,-->
<!--          "nro_registro": null,-->
<!--          "fecha_creacion": "2025-12-30 04:32:58",-->
<!--          "fecha_pre_analitica": null,-->
<!--          "fecha_envio_analitica": null,-->
<!--          "fecha_aceptacion_analitica": null,-->
<!--          "fecha_finalizacion": null,-->
<!--          "sala": null,-->
<!--          "cama": null,-->
<!--          "paciente_nombre": "Adimer Paul Chambi Ajata",-->
<!--          "paciente_ci": "7336199",-->
<!--          "paciente_telefono": "69603027",-->
<!--          "paciente_direccion": "Av. Siempre Viva 742",-->
<!--          "paciente_fecha_nac": "1989-04-02",-->
<!--          "paciente_genero": "M",-->
<!--          "paciente_edad": 36,-->
<!--          "doctor_nombre": null,-->
<!--          "doctor_especialidad": null,-->
<!--          "doctor_ci": null,-->
<!--          "doctor_telefono": null,-->
<!--          "doctor_email": null,-->
<!--          "doctor_registro": null,-->
<!--          "establecimiento_id": 1,-->
<!--          "user_id": 1,-->
<!--          "user_preanalitica_id": null,-->
<!--          "user_analitica_id": null,-->
<!--          "muestra_sangre_entera": null,-->
<!--          "muestra_coagulo": null,-->
<!--          "muestra_volumen": null,-->
<!--          "muestra_identificacion": null,-->
<!--          "muestra_equipo": null,-->
<!--          "muestra_rechazada": null,-->
<!--          "muestra_observacion": null,-->
<!--          "numero_factura": null,-->
<!--          "paciente": {-->
<!--          "id": 2,-->
<!--          "fecha_recepcion": "2025-12-15",-->
<!--          "hora_recepcion": "05:43:59",-->
<!--          "nombre_completo": "Adimer Paul Chambi Ajata",-->
<!--          "fecha_nac": "1989-04-02",-->
<!--          "genero": "M",-->
<!--          "edad": 36,-->
<!--          "ci": "7336199",-->
<!--          "telefono": "69603027",-->
<!--          "direccion": "Av. Siempre Viva 742",-->
<!--          "discapacidad": 0,-->
<!--          "discapacidad_cual": null,-->
<!--          "discapacidad_otro": null,-->
<!--          "embarazo": 0,-->
<!--          "fum": null,-->
<!--          "sem_gest": null-->
<!--          },-->
<!--          "doctor": null,-->
<!--          "servicios": [-->
<!--          {-->
<!--          "id": 1,-->
<!--          "area_id": 1,-->
<!--          "codigo": 1,-->
<!--          "nombre": "COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)",-->
<!--          "descripcion": null,-->
<!--          "metodo": "M\/SA",-->
<!--          "subarea": null,-->
<!--          "precio": "55.00",-->
<!--          "estado": "ACTIVO",-->
<!--          "pivot": {-->
<!--          "solicitude_id": 51,-->
<!--          "servicio_id": 1,-->
<!--          "precio": "55.00",-->
<!--          "area_id": 1,-->
<!--          "nombre": "COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)",-->
<!--          "created_at": "2025-12-30T08:32:58.000000Z",-->
<!--          "updated_at": "2025-12-30T08:32:58.000000Z"-->
<!--          },-->
<!--          "area": {-->
<!--          "id": 1,-->
<!--          "name": "HEMATOLOG\u00cdA (Area 2)",-->
<!--          "descripcion": "HEMATOLOG\u00cdA (Area 2)",-->
<!--          "title": "HEMATOLOG\u00cdA",-->
<!--          "estado": "ACTIVO",-->
<!--          "area_tipo_muestras": [-->
<!--          {-->
<!--          "id": 1,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "Hemograma (tapa lila)"-->
<!--          },-->
<!--          {-->
<!--          "id": 2,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "TP plasma (tapa celeste 1:9)"-->
<!--          },-->
<!--          {-->
<!--          "id": 3,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "VSG (tapa celeste 1:4)"-->
<!--          }-->
<!--          ]-->
<!--          }-->
<!--          },-->
<!--          {-->
<!--          "id": 15,-->
<!--          "area_id": 2,-->
<!--          "codigo": 15,-->
<!--          "nombre": "ALBUMINA",-->
<!--          "descripcion": null,-->
<!--          "metodo": "M\/A",-->
<!--          "subarea": "Qu\u00edmica Sangu\u00ednea",-->
<!--          "precio": "20.00",-->
<!--          "estado": "ACTIVO",-->
<!--          "pivot": {-->
<!--          "solicitude_id": 51,-->
<!--          "servicio_id": 15,-->
<!--          "precio": "20.00",-->
<!--          "area_id": 2,-->
<!--          "nombre": "ALBUMINA",-->
<!--          "created_at": "2025-12-30T08:32:58.000000Z",-->
<!--          "updated_at": "2025-12-30T08:32:58.000000Z"-->
<!--          },-->
<!--          "area": {-->
<!--          "id": 2,-->
<!--          "name": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "descripcion": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "title": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA",-->
<!--          "estado": "ACTIVO",-->
<!--          "area_tipo_muestras": [-->
<!--          {-->
<!--          "id": 4,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "Suero"-->
<!--          },-->
<!--          {-->
<!--          "id": 38,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "L\u00edquido Ascitico"-->
<!--          }-->
<!--          ]-->
<!--          }-->
<!--          }-->
<!--          ],-->
<!--          "user_preanalitica": null,-->
<!--          "user": {-->
<!--          "id": 1,-->
<!--          "name": "Admin User",-->
<!--          "username": "admin",-->
<!--          "role": "Administrador",-->
<!--          "area_id": 1,-->
<!--          "avatar": "default.png",-->
<!--          "email": "",-->
<!--          "email_verified_at": null,-->
<!--          "establecimiento_id": 1-->
<!--          }-->
<!--          },-->
<!--          {-->
<!--          "id": 44,-->
<!--          "paciente_id": 5,-->
<!--          "doctor_id": null,-->
<!--          "codigo_solicitud": null,-->
<!--          "tipo_atencion": "SI",-->
<!--          "tipo_otro": null,-->
<!--          "fecha_solicitud": "2025-12-28",-->
<!--          "hora_solicitud": "04:48:00",-->
<!--          "establecimiento_salud": "Hospital General",-->
<!--          "zona_establecimiento": null,-->
<!--          "diagnostico_clinico": null,-->
<!--          "diagnostico_select": null,-->
<!--          "estado": "ATENDIENDO",-->
<!--          "codigo": 30,-->
<!--          "nro_registro": "GQL100789",-->
<!--          "fecha_creacion": "2025-12-29 04:48:47",-->
<!--          "fecha_pre_analitica": "2025-12-29 04:48:54",-->
<!--          "fecha_envio_analitica": null,-->
<!--          "fecha_aceptacion_analitica": null,-->
<!--          "fecha_finalizacion": null,-->
<!--          "sala": null,-->
<!--          "cama": null,-->
<!--          "paciente_nombre": "Giovanna Maria Quispe Lopez",-->
<!--          "paciente_ci": "3540043",-->
<!--          "paciente_telefono": "72461667",-->
<!--          "paciente_direccion": "Final America",-->
<!--          "paciente_fecha_nac": "1989-07-10",-->
<!--          "paciente_genero": "F",-->
<!--          "paciente_edad": 36,-->
<!--          "doctor_nombre": null,-->
<!--          "doctor_especialidad": null,-->
<!--          "doctor_ci": null,-->
<!--          "doctor_telefono": null,-->
<!--          "doctor_email": null,-->
<!--          "doctor_registro": null,-->
<!--          "establecimiento_id": 1,-->
<!--          "user_id": 1,-->
<!--          "user_preanalitica_id": 1,-->
<!--          "user_analitica_id": null,-->
<!--          "muestra_sangre_entera": null,-->
<!--          "muestra_coagulo": null,-->
<!--          "muestra_volumen": null,-->
<!--          "muestra_identificacion": null,-->
<!--          "muestra_equipo": null,-->
<!--          "muestra_rechazada": null,-->
<!--          "muestra_observacion": null,-->
<!--          "numero_factura": null,-->
<!--          "paciente": {-->
<!--          "id": 5,-->
<!--          "fecha_recepcion": null,-->
<!--          "hora_recepcion": null,-->
<!--          "nombre_completo": "Giovanna Maria Quispe Lopez",-->
<!--          "fecha_nac": "1989-07-10",-->
<!--          "genero": "F",-->
<!--          "edad": 36,-->
<!--          "ci": "3540043",-->
<!--          "telefono": "69603027",-->
<!--          "direccion": "Final America",-->
<!--          "discapacidad": 0,-->
<!--          "discapacidad_cual": null,-->
<!--          "discapacidad_otro": null,-->
<!--          "embarazo": 1,-->
<!--          "fum": "2025-03-01",-->
<!--          "sem_gest": 41-->
<!--          },-->
<!--          "doctor": null,-->
<!--          "servicios": [-->
<!--          {-->
<!--          "id": 1,-->
<!--          "area_id": 1,-->
<!--          "codigo": 1,-->
<!--          "nombre": "COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)",-->
<!--          "descripcion": null,-->
<!--          "metodo": "M\/SA",-->
<!--          "subarea": null,-->
<!--          "precio": "55.00",-->
<!--          "estado": "ACTIVO",-->
<!--          "pivot": {-->
<!--          "solicitude_id": 44,-->
<!--          "servicio_id": 1,-->
<!--          "precio": "55.00",-->
<!--          "area_id": 1,-->
<!--          "nombre": "COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)",-->
<!--          "created_at": "2025-12-29T08:48:47.000000Z",-->
<!--          "updated_at": "2025-12-29T08:48:47.000000Z"-->
<!--          },-->
<!--          "area": {-->
<!--          "id": 1,-->
<!--          "name": "HEMATOLOG\u00cdA (Area 2)",-->
<!--          "descripcion": "HEMATOLOG\u00cdA (Area 2)",-->
<!--          "title": "HEMATOLOG\u00cdA",-->
<!--          "estado": "ACTIVO",-->
<!--          "area_tipo_muestras": [-->
<!--          {-->
<!--          "id": 1,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "Hemograma (tapa lila)"-->
<!--          },-->
<!--          {-->
<!--          "id": 2,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "TP plasma (tapa celeste 1:9)"-->
<!--          },-->
<!--          {-->
<!--          "id": 3,-->
<!--          "area_id": 1,-->
<!--          "tipo_muestra": "VSG (tapa celeste 1:4)"-->
<!--          }-->
<!--          ]-->
<!--          }-->
<!--          },-->
<!--          {-->
<!--          "id": 14,-->
<!--          "area_id": 2,-->
<!--          "codigo": 14,-->
<!--          "nombre": "\u00c1CIDO \u00daRICO",-->
<!--          "descripcion": null,-->
<!--          "metodo": "M\/A",-->
<!--          "subarea": "Qu\u00edmica Sangu\u00ednea",-->
<!--          "precio": "20.00",-->
<!--          "estado": "ACTIVO",-->
<!--          "pivot": {-->
<!--          "solicitude_id": 44,-->
<!--          "servicio_id": 14,-->
<!--          "precio": "20.00",-->
<!--          "area_id": 2,-->
<!--          "nombre": "\u00c1CIDO \u00daRICO",-->
<!--          "created_at": "2025-12-29T08:48:47.000000Z",-->
<!--          "updated_at": "2025-12-29T08:48:47.000000Z"-->
<!--          },-->
<!--          "area": {-->
<!--          "id": 2,-->
<!--          "name": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "descripcion": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "title": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA",-->
<!--          "estado": "ACTIVO",-->
<!--          "area_tipo_muestras": [-->
<!--          {-->
<!--          "id": 4,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "Suero"-->
<!--          },-->
<!--          {-->
<!--          "id": 38,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "L\u00edquido Ascitico"-->
<!--          }-->
<!--          ]-->
<!--          }-->
<!--          }-->
<!--          ],-->
<!--          "user_preanalitica": {-->
<!--          "id": 1,-->
<!--          "name": "Admin User",-->
<!--          "username": "admin",-->
<!--          "role": "Administrador",-->
<!--          "area_id": 1,-->
<!--          "avatar": "default.png",-->
<!--          "email": "",-->
<!--          "email_verified_at": null,-->
<!--          "establecimiento_id": 1-->
<!--          },-->
<!--          "user": {-->
<!--          "id": 1,-->
<!--          "name": "Admin User",-->
<!--          "username": "admin",-->
<!--          "role": "Administrador",-->
<!--          "area_id": 1,-->
<!--          "avatar": "default.png",-->
<!--          "email": "",-->
<!--          "email_verified_at": null,-->
<!--          "establecimiento_id": 1-->
<!--          }-->
<!--          },-->
<!--          {-->
<!--          "id": 34,-->
<!--          "paciente_id": 2,-->
<!--          "doctor_id": null,-->
<!--          "codigo_solicitud": null,-->
<!--          "tipo_atencion": "SI",-->
<!--          "tipo_otro": null,-->
<!--          "fecha_solicitud": "2025-12-22",-->
<!--          "hora_solicitud": "06:28:00",-->
<!--          "establecimiento_salud": "Hospital General",-->
<!--          "zona_establecimiento": null,-->
<!--          "diagnostico_clinico": null,-->
<!--          "diagnostico_select": null,-->
<!--          "estado": "MUESTRA RECHAZADA",-->
<!--          "codigo": 23,-->
<!--          "nro_registro": "ACA020489",-->
<!--          "fecha_creacion": "2025-12-22 06:28:14",-->
<!--          "fecha_pre_analitica": "2025-12-22 06:28:19",-->
<!--          "fecha_envio_analitica": "2025-12-22 06:28:23",-->
<!--          "fecha_aceptacion_analitica": null,-->
<!--          "fecha_finalizacion": "2025-12-22 10:08:52",-->
<!--          "sala": null,-->
<!--          "cama": null,-->
<!--          "paciente_nombre": "Adimer Paul Chambi Ajata",-->
<!--          "paciente_ci": "7336199",-->
<!--          "paciente_telefono": "69603027",-->
<!--          "paciente_direccion": "Av. Siempre Viva 742",-->
<!--          "paciente_fecha_nac": "1989-04-02",-->
<!--          "paciente_genero": "M",-->
<!--          "paciente_edad": 36,-->
<!--          "doctor_nombre": null,-->
<!--          "doctor_especialidad": null,-->
<!--          "doctor_ci": null,-->
<!--          "doctor_telefono": null,-->
<!--          "doctor_email": null,-->
<!--          "doctor_registro": null,-->
<!--          "establecimiento_id": 1,-->
<!--          "user_id": 1,-->
<!--          "user_preanalitica_id": 1,-->
<!--          "user_analitica_id": null,-->
<!--          "muestra_sangre_entera": null,-->
<!--          "muestra_coagulo": null,-->
<!--          "muestra_volumen": null,-->
<!--          "muestra_identificacion": null,-->
<!--          "muestra_equipo": null,-->
<!--          "muestra_rechazada": "Si",-->
<!--          "muestra_observacion": "jejej",-->
<!--          "numero_factura": null,-->
<!--          "paciente": {-->
<!--          "id": 2,-->
<!--          "fecha_recepcion": "2025-12-15",-->
<!--          "hora_recepcion": "05:43:59",-->
<!--          "nombre_completo": "Adimer Paul Chambi Ajata",-->
<!--          "fecha_nac": "1989-04-02",-->
<!--          "genero": "M",-->
<!--          "edad": 36,-->
<!--          "ci": "7336199",-->
<!--          "telefono": "69603027",-->
<!--          "direccion": "Av. Siempre Viva 742",-->
<!--          "discapacidad": 0,-->
<!--          "discapacidad_cual": null,-->
<!--          "discapacidad_otro": null,-->
<!--          "embarazo": 0,-->
<!--          "fum": null,-->
<!--          "sem_gest": null-->
<!--          },-->
<!--          "doctor": null,-->
<!--          "servicios": [-->
<!--          {-->
<!--          "id": 14,-->
<!--          "area_id": 2,-->
<!--          "codigo": 14,-->
<!--          "nombre": "\u00c1CIDO \u00daRICO",-->
<!--          "descripcion": null,-->
<!--          "metodo": "M\/A",-->
<!--          "subarea": "Qu\u00edmica Sangu\u00ednea",-->
<!--          "precio": "20.00",-->
<!--          "estado": "ACTIVO",-->
<!--          "pivot": {-->
<!--          "solicitude_id": 34,-->
<!--          "servicio_id": 14,-->
<!--          "precio": "20.00",-->
<!--          "area_id": 2,-->
<!--          "nombre": "\u00c1CIDO \u00daRICO",-->
<!--          "created_at": "2025-12-22T10:28:14.000000Z",-->
<!--          "updated_at": "2025-12-22T10:28:14.000000Z"-->
<!--          },-->
<!--          "area": {-->
<!--          "id": 2,-->
<!--          "name": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "descripcion": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA (Area 3)",-->
<!--          "title": "QU\u00cdMICA SANGU\u00cdNEA Y SEROLOG\u00cdA",-->
<!--          "estado": "ACTIVO",-->
<!--          "area_tipo_muestras": [-->
<!--          {-->
<!--          "id": 4,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "Suero"-->
<!--          },-->
<!--          {-->
<!--          "id": 38,-->
<!--          "area_id": 2,-->
<!--          "tipo_muestra": "L\u00edquido Ascitico"-->
<!--          }-->
<!--          ]-->
<!--          }-->
<!--          }-->
<!--          ],-->
<!--          "user_preanalitica": {-->
<!--          "id": 1,-->
<!--          "name": "Admin User",-->
<!--          "username": "admin",-->
<!--          "role": "Administrador",-->
<!--          "area_id": 1,-->
<!--          "avatar": "default.png",-->
<!--          "email": "",-->
<!--          "email_verified_at": null,-->
<!--          "establecimiento_id": 1-->
<!--          },-->
<!--          "user": {-->
<!--          "id": 1,-->
<!--          "name": "Admin User",-->
<!--          "username": "admin",-->
<!--          "role": "Administrador",-->
<!--          "area_id": 1,-->
<!--          "avatar": "default.png",-->
<!--          "email": "",-->
<!--          "email_verified_at": null,-->
<!--          "establecimiento_id": 1-->
<!--          }-->
<!--          }-->
<!--          ],-->
<!--          mostrar en texto cantidad CREADO ATENDIDO MUESTRA RECHAZADO-->
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
          <q-btn
            color="red"
            icon="add"
            label="Mostrar Rechazadas"
            no-caps
            @click="showRejected"
          />
          <q-btn
            color="primary"
            icon="refresh"
            label="Actualizar"
            no-caps
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
        :rows-per-page-options="[10, 20, 50]"
        @request="onRequest"
        @rowClick="openDialogSolicitud"
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
              :color="props.row.estado === 'CREADO' ? 'blue-6' : (props.row.estado === 'MUESTRA RECHAZADA'?'red-6':'grey-6')"
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
<!--            @click.stop="onGenerarCodigo(props.row)"-->
            <q-btn
              v-if="props.row.estado === 'CREADO'"
              dense
              no-caps
              outline
              color="deep-purple-6"
              icon="confirmation_number"
              :label="props.row.codigo ? 'Ver código' : 'Generar código'"
              :loading="loadingRowId === props.row.id"
            />
            <span v-else class="text-red text-bold">
              Sin Enviar Muestra
            </span>
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
                    :options="[10, 20, 50]"
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
                      {{ moment().format('YYYY-MM-DD HH:mm:ss') || '-' }}
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
                            moment().format('YYYY-MM-DD HH:mm:ss')
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
                <div class="q-ml-md q-mt-xs">
                  <template v-for="(atm, index) in areas_tipo_muestras" :key="atm.id">
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

<!--                  <pre>{{areas_tipo_muestras}}</pre>-->
<!--                  [-->
<!--                  {-->
<!--                  "id": 3,-->
<!--                  "name": "UROANÁLISIS (Area 4)",-->
<!--                  "area_tipo_muestras": [-->
<!--                  {-->
<!--                  "id": 5,-->
<!--                  "area_id": 3,-->
<!--                  "tipo_muestra": "Orinas",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 6,-->
<!--                  "area_id": 3,-->
<!--                  "tipo_muestra": "Heces",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 7,-->
<!--                  "area_id": 3,-->
<!--                  "tipo_muestra": "Sangre capilar",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 8,-->
<!--                  "area_id": 3,-->
<!--                  "tipo_muestra": "Cutánea",-->
<!--                  "selected": false-->
<!--                  }-->
<!--                  ]-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 4,-->
<!--                  "name": "MICROBIOLOGÍA (Area 5)",-->
<!--                  "area_tipo_muestras": [-->
<!--                  {-->
<!--                  "id": 9,-->
<!--                  "area_id": 4,-->
<!--                  "tipo_muestra": "Orina",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 10,-->
<!--                  "area_id": 4,-->
<!--                  "tipo_muestra": "Heces",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 11,-->
<!--                  "area_id": 4,-->
<!--                  "tipo_muestra": "Líquidos",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 12,-->
<!--                  "area_id": 4,-->
<!--                  "tipo_muestra": "Secreciones",-->
<!--                  "selected": false-->
<!--                  },-->
<!--                  {-->
<!--                  "id": 13,-->
<!--                  "area_id": 4,-->
<!--                  "tipo_muestra": "Otros",-->
<!--                  "selected": false-->
<!--                  }-->
<!--                  ]-->
<!--                  }-->
<!--                  ]-->

                </div>
<!--                <pre>{{areas_tipo_muestras}}</pre>-->
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
<!--        btn muestar no tomada en colcoa rojo-->
        <q-separator />

        <!-- ACCIONES -->
        <q-card-actions class="bg-grey-1 row items-center">
          <q-btn
            class="q-ma-md"
          color="red"
          icon="warning"
          no-caps
          @click="marcarMuestraNoTomada"
          >
            Muestra <br>No Tomada
          </q-btn>
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
            icon="send"
            :loading="savingPre"
            no-caps
            @click="guardarPreAnalitica"
          >
            {{ etiquetaGuardarPreanalitica() }}
          </q-btn>
        </q-card-actions>

      </q-card>
    </q-dialog>
<!--    dialogRechazado-->
    <q-dialog
      v-model="dialogRechazado"
      persistent
      transition-show="jump-down"
      transition-hide="jump-up"
    >
      <q-card class="q-pa-md" style="max-width: 95vh;width: 650px">
        <div class="text-h6 text-center bg-grey-8 text-white">
          SOLICITUDES CON MUESTRAS RECHAZADAS
        </div>
        <q-card-section class="q-pa-xs">
          <q-markup-table
            :rows="rows.filter(r => r.estado === 'MUESTRA RECHAZADA')"
            :columns="columns"
            row-key="id"
            dense
            flat
            bordered
          >
            <thead>
              <tr>
  <!--              <th>Acciones</th>-->
                <th>Fecha Solicitud</th>
                <th>Estado</th>
                <th>Paciente</th>
                <th>Código</th>
                <th>Médico Solicitante</th>
<!--                <th>Prestaciones</th>-->
<!--                <th>Establecimiento</th>-->
<!--                <th>Tipo atención</th>-->
<!--                <th># Prestaciones</th>-->
<!--                <th>Responsable Preanalítica</th>-->
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows.filter(r => r.estado === 'MUESTRA RECHAZADA')" :key="row.id">
  <!--              <td>-->
  <!--                <q-btn-->
  <!--                  dense-->
  <!--                  no-caps-->
  <!--                  outline-->
  <!--                  color="deep-purple-6"-->
  <!--                  icon="confirmation_number"-->
  <!--                  :label="row.codigo ? 'Ver código' : 'Generar código'"

  <!--                  @click.stop="onGenerarCodigo(row)"-->
  <!--                />-->
  <!--              </td>-->
                <td>{{ row.fecha_creacion ? moment(row.fecha_creacion).format('DD/MM/YYYY HH:mm') : '' }}</td>
                <td>
                  <q-chip
                    dense
                    :color="row.estado === 'CREADO' ? 'blue-6' : (row.estado === 'MUESTRA RECHAZADA'?'red-6':'grey-6')"
                    text-color="white"
                    icon="pending"
                  >
                    {{ row.estado }}
                  </q-chip>
                </td>
                <td>
                  <div class="text-weight-medium">
                    {{ row.paciente_nombre || row.paciente?.nombre_completo }}
                  </div>
                  <div class="text-caption text-grey-7">
                    CI: {{ row.paciente_ci || row.paciente?.ci || '-' }}
                  </div>
                </td>
                <td>
                  <div v-if="row.codigo">
                    <span class="text-bold">
                      {{ row.codigo }} -
                      {{ row.nro_registro }}
                    </span>
                  </div>
                  <div v-else class="text-negative text-caption">
                    Sin código
                  </div>
                </td>
                <td>
                  <!-- mostrar array de solicitudes rechazadas en formato json bonita -->
                  <div>
                    <div class="text-weight-medium">
                      {{row.solicitud_rechazadas.length}} motivos de rechazo <br>
                      <small class="text-grey-7">
<!--                        {{ row.solicitud_rechazadas.map(r => r.motivo).join('; ') }}-->
<!--                        mostrar medico ara motivo-->
                        {{ row.solicitud_rechazadas.map(r => r.motivo + ' (por: ' + (r.user?.name || 'Desconocido') + ')').join('; ') }}
                        <br>
                        {{ row.solicitud_rechazadas.map(r => 'Área: ' + (r.area?.title || 'Desconocida')).join('; ') }}
                      </small>
                    </div>
                  </div>
<!--                  <pre>{{row.solicitud_rechazadas}}</pre>-->
<!--                  [-->
<!--                  {-->
<!--                  "id": 1,-->
<!--                  "solicitude_id": 75,-->
<!--                  "motivo": "aas",-->
<!--                  "fecha_hora": "2026-01-14 05:41:26",-->
<!--                  "area_id": 1,-->
<!--                  "user_id": 1,-->
<!--                  "user": {-->
<!--                  "id": 1,-->
<!--                  "name": "Admin User",-->
<!--                  "username": "admin",-->
<!--                  "role": "Administrador",-->
<!--                  "area_id": 1,-->
<!--                  "avatar": "default.png",-->
<!--                  "email": "",-->
<!--                  "email_verified_at": null,-->
<!--                  "establecimiento_id": 1-->
<!--                  },-->
<!--                  "area": {-->
<!--                  "id": 1,-->
<!--                  "name": "HEMATOLOGÍA (Area 2)",-->
<!--                  "descripcion": "HEMATOLOGÍA (Area 2)",-->
<!--                  "title": "HEMATOLOGÍA",-->
<!--                  "estado": "ACTIVO"-->
<!--                  }-->
<!--                  }-->
<!--                  ]-->
                  <!--                  {{ row.doctor_nombre || row.doctor?.name || '' }}-->
                </td>
<!--                <td>{{ row.servicios ? row.servicios.map(s => s.nombre).join(', ') : '' }}</td>-->
<!--                <td>{{ row.establecimiento_salud || '-' }}</td>-->
<!--                <td>-->
<!--                  <q-chip-->
<!--                    dense-->
<!--                    :color="row.tipo_atencion === 'SI' ? 'green-6' : 'orange-6'"-->
<!--                    text-color="white"-->
<!--                  >-->
<!--                    {{ row.tipo_atencion === 'SI' ? 'SUS' : row.tipo_otro || 'EXT' }}-->
<!--                  </q-chip>-->
<!--                </td>-->
<!--                <td class="text-center">-->
<!--                  <q-badge color="primary" :label="row.servicios ? row.servicios.length : 0" />-->
<!--                </td>-->
<!--                <td>{{ row.user_preanalitica ? row.user_preanalitica.name : 'No asignado' }}</td>-->
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>
        <q-card-actions align="center" class="bg-grey-1 q-mt-md">
          <q-btn
            flat
            label="Cerrar"
            color="primary"
            icon="close"
            v-close-popup
            no-caps
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import moment from 'moment'
import consentimientos from "pages/consentimientos/Consentimientos.vue";

export default {
  name: 'AreaPreanaliticaPage',
  data () {
    return {
      dialogConsentimiento: false,
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
        { name: 'actions', label: 'Acciones', align: 'right' },
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
    if (!this.$store.socketAnalitica) {
      this.$store.socketAnalitica = true
      this.$socket.on('silSolicitud', msg => {
        this.$alert.info('Nueva solicitud de analítica recibido.')
        this.reloadTable()
      })
    }
  },
  watch: {
    from () { this.resetToFirstPageAndReload() },
    to ()   { this.resetToFirstPageAndReload() },
    filter () { this.resetToFirstPageAndReload() }
  },
  methods: {
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
      this.$axios.get('solicitudes-area-preanalitica', {
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
