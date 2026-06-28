<template>
  <q-page class="q-pa-sm">
    <!-- HEADER / FILTROS -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-xs">
        <div class="col-12 col-sm-2">
          <div class="text-subtitle2">{{$store.user.area?.name}}</div>
          <!--          <div class="text-caption text-grey-7">-->
          <!--            Solicitudes recibidas de Preanalítica (estado ENVIADO_ANALITICA)-->
          <!--          </div>-->
        </div>

        <div class="col-12 col-sm-2">
          <q-input v-model="from" type="date" dense outlined label="Desde">
            <template #prepend><q-icon name="event" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-2">
          <q-input v-model="to" type="date" dense outlined label="Hasta">
            <template #prepend><q-icon name="event" /></template>
          </q-input>
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

        <div class="col-12 col-sm-2 text-right row q-gutter-xs justify-end">
          <q-btn
            color="primary"
            icon="search"
            label="Buscar"
            no-caps
            :loading="loading"
            @click="analiticaGet()"
          />
        </div>
        <div class="col-12 col-sm-2 text-right row q-gutter-xs justify-end">
          <q-btn
            color="teal"
            icon="print"
            label="Imprimir result"
            no-caps
            @click="abrirDialogPresentacion"
          />
        </div>
        <div class="col-12">
          <q-markup-table dense wrap-cells flat bordered>
            <thead>
            <tr class="bg-primary text-white" >
              <th>Opciones</th>
              <th>Código</th>
              <th>Paciente</th>
              <th>CI</th>
              <th>Establecimiento</th>
              <th>Estado</th>
              <th>Fecha Solicitud</th>
              <th>
                Servicios
                <div class="row q-gutter-xs q-mt-xs" style="font-weight:normal;font-size:11px;opacity:0.85">
                  <span><q-icon name="check_circle" color="green-3" size="12px" /> Realizado</span>
                  <span><q-icon name="cancel" color="orange-3" size="12px" /> Pendiente</span>
                  <span><q-icon name="inventory_2" color="light-blue-3" size="12px" /> Entregado</span>
                </div>
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="solicitud in solicitudesPaginadas" :key="solicitud.id" style="cursor: pointer;" >
              <td>
              <q-btn-dropdown dense color="primary" no-caps label="Opciones" size="10px">
                  <q-list>
                    <!-- HEMATOLOGÍA -->
                    <q-item clickable @click="selectHematologia(solicitud)" v-close-popup dense v-if="$store.user.role === 'Administrador' || hasPermission('HEMATOLOGÍA')">
                      <q-item-section avatar><q-icon name="bloodtype" /></q-item-section>
                      <q-item-section>Hematología</q-item-section>
                    </q-item>

                    <!--                    enviar whatsapp dcotor y paciente-->
                    <q-item clickable @click="enviarWhatsApp(solicitud,'HematologiaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.hematologia?.code && ($store.user.role === 'Administrador' || hasPermission('HEMATOLOGÍA'))">
                      <q-item-section avatar>
                        <q-icon name="fa-brands fa-whatsapp" />
                      </q-item-section>
                      <q-item-section>
                        WhatsApp Doctor({{solicitud.doctor_telefono}})
                        <!--                        <pre>{{solicitud.doctor_telefono}}</pre>-->
                      </q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'HematologiaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.hematologia?.code && ($store.user.role === 'Administrador' || hasPermission('HEMATOLOGÍA'))">
                      <q-item-section avatar>
                        <q-icon name="fa-brands fa-whatsapp" />
                      </q-item-section>
                      <q-item-section>
                        WhatsApp Paciente({{solicitud.paciente_telefono}})
                        <!--                        <pre>{{solicitud.paciente_telefono}}</pre>-->
                      </q-item-section>
                    </q-item>

                    <q-separator spaced v-if="$store.user.role === 'Administrador' || hasPermission('HEMATOLOGÍA')" />

<!--                    &lt;!&ndash; QUÍMICA SANGUÍNEA &ndash;&gt;-->
<!--                    <q-item clickable @click="$router.push({ name: 'analitica-quimica-sanguinia', params: { id: solicitud.id } })" v-close-popup dense>-->
<!--                      <q-item-section avatar><q-icon name="science" /></q-item-section>-->
<!--                      <q-item-section>Química Sanguínea</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="printQuimica(solicitud)" v-close-popup dense v-if="solicitud.quimica_sanguinea?.code">-->
<!--                      <q-item-section avatar><q-icon name="print" /></q-item-section>-->
<!--                      <q-item-section>Imprimir Química Sanguínea</q-item-section>-->
<!--                    </q-item>-->
<!--&lt;!&ndash;                    imprimi curva de tolerancia&ndash;&gt;-->
<!--                    <q-item clickable @click="printQuimicaTolerancia(solicitud)" v-close-popup dense v-if="solicitud.quimica_sanguinea?.code">-->
<!--                      <q-item-section avatar><q-icon name="show_chart" /></q-item-section>-->
<!--                      <q-item-section>Curva de Tolerancia</q-item-section>-->
<!--                    </q-item>-->
<!--&lt;!&ndash;                    imprecion de cito quimico&ndash;&gt;-->
<!--                    <q-item clickable @click="printCitoQuimico(solicitud)" v-close-popup dense v-if="solicitud.quimica_sanguinea?.code">-->
<!--                      <q-item-section avatar><q-icon name="biotech" /></q-item-section>-->
<!--                      <q-item-section>Cito Químico</q-item-section>-->
<!--                    </q-item>-->


                    <!-- QUÍMICA SANGUÍNEA -->
                    <q-item clickable @click="$router.push({ name: 'analitica-quimica-sanguinia', params: { id: solicitud.id } })" v-close-popup dense v-if="$store.user.role === 'Administrador' || hasPermission('QUÍMICA SANGUÍNEA Y SEROLOGÍA')">
                      <q-item-section avatar><q-icon name="science" /></q-item-section>
                      <q-item-section>Química Sanguínea</q-item-section>
                    </q-item>
<!--                    <q-item>-->
<!--                      <pre>{{$store.user}}</pre>-->
<!--                    </q-item>-->

                    <!-- WhatsApp Química -->
                    <q-item clickable @click="enviarWhatsApp(solicitud,'QuimicaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.quimica_sanguinea?.code && ($store.user.role === 'Administrador' || hasPermission('QUÍMICA SANGUÍNEA Y SEROLOGÍA'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'QuimicaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.quimica_sanguinea?.code && ($store.user.role === 'Administrador' || hasPermission('QUÍMICA SANGUÍNEA Y SEROLOGÍA'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                    </q-item>

                    <q-separator spaced v-if="$store.user.role === 'Administrador' || hasPermission('QUÍMICA SANGUÍNEA Y SEROLOGÍA')" />

                    <!-- UROANÁLISIS -->
                    <q-item clickable @click="$router.push({ name: 'analitica-uroanalisis', params: { id: solicitud.id } })" v-close-popup dense v-if="$store.user.role === 'Administrador' || hasPermission('UROANÁLISIS')">
                      <q-item-section avatar><q-icon name="water_drop" /></q-item-section>
                      <q-item-section>
                        Uroanálisis
                        <!--                        <pre>{{solicitud.uroanalisis.code}}</pre>-->
                      </q-item-section>
                    </q-item>


                    <!-- WhatsApp Uroanálisis -->
                    <q-item clickable @click="enviarWhatsApp(solicitud,'UroanalisisDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.uroanalisis?.code && ($store.user.role === 'Administrador' || hasPermission('UROANÁLISIS'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'UroanalisisPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.uroanalisis?.code && ($store.user.role === 'Administrador' || hasPermission('UROANÁLISIS'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                    </q-item>

                    <q-separator spaced v-if="$store.user.role === 'Administrador' || hasPermission('UROANÁLISIS')" />

                    <!-- PARASITOLOGÍA -->
                    <q-item clickable @click="$router.push({ name: 'analitica-parasitologia', params: { id: solicitud.id } })" v-close-popup dense v-if="$store.user.role === 'Administrador' || hasPermission('UROANÁLISIS')">
                      <q-item-section avatar><q-icon name="bug_report" /></q-item-section>
                      <q-item-section>Parasitología</q-item-section>
                    </q-item>


                    <!-- WhatsApp Parasitología -->
                    <q-item clickable @click="enviarWhatsApp(solicitud,'ParasitologiaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.parasitologia?.code && ($store.user.role === 'Administrador' || hasPermission('UROANÁLISIS'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'ParasitologiaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.parasitologia?.code && ($store.user.role === 'Administrador' || hasPermission('UROANÁLISIS'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                    </q-item>

                    <q-separator spaced  v-if="$store.user.role === 'Administrador' || hasPermission('UROANÁLISIS')" />

                    <!-- PAPILOMA HUMANO -->
<!--                    <q-item clickable @click="$router.push({ name: 'analitica-papiloma-humano', params: { id: solicitud.id } })" v-close-popup dense>-->
<!--                      <q-item-section avatar><q-icon name="health_and_safety" /></q-item-section>-->
<!--                      <q-item-section>-->
<!--                        Papiloma Humano-->
<!--                        &lt;!&ndash;                        <pre>{{solicitud}}</pre>&ndash;&gt;-->
<!--                      </q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="printPapilomaHumano(solicitud)" v-close-popup dense v-if="solicitud.papiloma_humano?.code">-->
<!--                      <q-item-section avatar><q-icon name="print" /></q-item-section>-->
<!--                      <q-item-section>Imprimir Papiloma Humano</q-item-section>-->
<!--                    </q-item>-->
<!--                    &lt;!&ndash; WhatsApp Papiloma Humano &ndash;&gt;-->
<!--                    <q-item clickable @click="enviarWhatsApp(solicitud,'PapilomaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.papiloma_humano?.code">-->
<!--                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>-->
<!--                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="enviarWhatsApp(solicitud,'PapilomaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.papiloma_humano?.code">-->
<!--                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>-->
<!--                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-separator spaced />-->
<!--                    &lt;!&ndash; PANEL RESPIRATORIO &ndash;&gt;-->
<!--                    <q-item clickable @click="$router.push({ name: 'analitica-panel-respiratorio', params: { id: solicitud.id } })" v-close-popup dense>-->
<!--                      <q-item-section avatar><q-icon name="air" /></q-item-section>-->
<!--                      <q-item-section>Panel Respiratorio</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="printPanelRespiratorio(solicitud)" v-close-popup dense v-if="solicitud.panel_respiratorio?.code">-->
<!--                      <q-item-section avatar><q-icon name="print" /></q-item-section>-->
<!--                      <q-item-section>Imprimir Panel Respiratorio</q-item-section>-->
<!--                    </q-item>-->
<!--                    &lt;!&ndash; WhatsApp Panel Respiratorio &ndash;&gt;-->
<!--                    <q-item clickable @click="enviarWhatsApp(solicitud,'PanelRespiratorioDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.panel_respiratorio?.code">-->
<!--                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>-->
<!--                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="enviarWhatsApp(solicitud,'PanelRespiratorioPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.panel_respiratorio?.code">-->
<!--                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>-->
<!--                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-separator spaced />-->
<!--                    &lt;!&ndash; PANEL SEXUAL &ndash;&gt;-->
<!--                    <q-item clickable @click="$router.push({ name: 'analitica-panel-sexual', params: { id: solicitud.id } })" v-close-popup dense>-->
<!--                      <q-item-section avatar><q-icon name="favorite" /></q-item-section>-->
<!--                      <q-item-section>Panel Sexual</q-item-section>-->
<!--                    </q-item>-->
<!--                    <q-item clickable @click="printPanelSexual(solicitud)" v-close-popup dense v-if="solicitud.panel_sexual?.code">-->
<!--                      <q-item-section avatar><q-icon name="print" /></q-item-section>-->
<!--                      <q-item-section>Imprimir Panel Sexual</q-item-section>-->
<!--                    </q-item>-->

<!--                    en biologia molecular debe ir los 3 papilomas en su menu-->
                    <!-- INMUNOLOGÍA -->
<!--                    <q-item>-->
<!--                      <pre>{{$store.user}}</pre>-->
<!--                    </q-item>-->
                    <q-item clickable @click="$router.push({ name: 'analitica-inmunologia', params: { id: solicitud.id } })" v-close-popup dense v-if="$store.user.role === 'Administrador' || hasPermission('INMUNOLOGÍA')">
                      <q-item-section avatar><q-icon name="shield" /></q-item-section>
                      <q-item-section>
                        Inmunología
                        <!--                        <pre>{{solicitud}}</pre>-->
                      </q-item-section>
                    </q-item>

                    <!-- Imprimir + WhatsApp Inmunología Analítica (requiere código UUID) -->
                    <q-item clickable @click="printInmunologiaAnalitica(solicitud)" v-close-popup dense v-if="solicitud.inmunologia_analitica_codigo && ($store.user.role === 'Administrador' || hasPermission('INMUNOLOGÍA'))">
                      <q-item-section avatar><q-icon name="print" color="deep-purple" /></q-item-section>
                      <q-item-section>Imprimir Inmunología</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'InmunologiaAnaliticaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.inmunologia_analitica_codigo && ($store.user.role === 'Administrador' || hasPermission('INMUNOLOGÍA'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Doctor ({{solicitud.doctor_telefono}})</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'InmunologiaAnaliticaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.inmunologia_analitica_codigo && ($store.user.role === 'Administrador' || hasPermission('INMUNOLOGÍA'))">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Paciente ({{solicitud.paciente_telefono}})</q-item-section>
                    </q-item>
<!--                    <q-item>-->
<!--                      <pre>{{$store.user}}</pre>-->
<!--                    </q-item>-->

                    <q-item dense class="cursor-pointer" v-if="($store.user.role === 'Administrador' || hasPermission('BIOLOGÍA MOLECULAR'))">
                      <q-item-section avatar>
                        <q-icon name="print" />
                      </q-item-section>

                      <q-item-section>
                        Biologia molecular
                      </q-item-section>

                      <q-item-section side>
                        <q-icon name="chevron_right" />
                      </q-item-section>

                      <q-menu anchor="top end" self="top start">
                        <q-list dense style="min-width: 220px">

                          <q-item clickable v-close-popup dense @click="$router.push({ name: 'analitica-papiloma-humano', params: { id: solicitud.id } })" >
                            <q-item-section  avatar><q-icon name="health_and_safety" /></q-item-section>
                            <q-item-section>Papiloma Humano</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup dense @click="printPapilomaHumano(solicitud)" v-if="solicitud.papiloma_humano?.code">
                            <q-item-section avatar><q-icon name="print" /></q-item-section>
                            <q-item-section>Imprimir Papiloma Humano</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PapilomaDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.papiloma_humano?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PapilomaPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.papiloma_humano?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                          </q-item>
                          <q-separator spaced />
                          <q-item clickable v-close-popup dense @click="$router.push({ name: 'analitica-panel-respiratorio', params: { id: solicitud.id } })" >
                            <q-item-section  avatar><q-icon name="air" /></q-item-section>
                            <q-item-section>Panel Respiratorio</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup dense @click="printPanelRespiratorio(solicitud)" v-if="solicitud.panel_respiratorio?.code">
                            <q-item-section avatar><q-icon name="print" /></q-item-section>
                            <q-item-section>Imprimir Panel Respiratorio</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PanelRespiratorioDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.panel_respiratorio?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PanelRespiratorioPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.panel_respiratorio?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                          </q-item>
                          <q-separator spaced />
                          <q-item clickable v-close-popup dense @click="$router.push({ name: 'analitica-panel-sexual', params: { id: solicitud.id } })" >
                            <q-item-section  avatar><q-icon name="favorite" /></q-item-section>
                            <q-item-section>Panel Sexual</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup dense @click="printPanelSexual(solicitud)" v-if="solicitud.panel_sexual?.code">
                            <q-item-section avatar><q-icon name="print" /></q-item-section>
                            <q-item-section>Imprimir Panel Sexual</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PanelSexualDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.panel_sexual?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                          </q-item>
                          <q-item clickable @click="enviarWhatsApp(solicitud,'PanelSexualPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.panel_sexual?.code">
                            <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                            <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-item>
<!--                    <q-item dense v-if="solicitud.quimica_sanguinea?.code" class="cursor-pointer">-->

                    <!-- WhatsApp Panel Sexual -->
                    <q-item clickable @click="enviarWhatsApp(solicitud,'PanelSexualDoctor')" v-close-popup dense v-if="solicitud.doctor_telefono && solicitud.panel_sexual?.code">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Doctor({{solicitud.doctor_telefono}})</q-item-section>
                    </q-item>
                    <q-item clickable @click="enviarWhatsApp(solicitud,'PanelSexualPaciente')" v-close-popup dense v-if="solicitud.paciente_telefono && solicitud.panel_sexual?.code">
                      <q-item-section avatar><q-icon name="fa-brands fa-whatsapp" /></q-item-section>
                      <q-item-section>WhatsApp Paciente({{solicitud.paciente_telefono}})</q-item-section>
                    </q-item>

                    <q-separator spaced v-if="$store.user.role === 'Administrador'" />

                  </q-list>
                </q-btn-dropdown>
              </td>
              <td class="text-bold text-primary">{{ solicitud.codigo }}</td>
              <td>{{ solicitud.paciente_nombre }}</td>
              <td>{{ solicitud.paciente_ci }}</td>
              <td>{{ solicitud.establecimiento_salud }}</td>
              <td>
<!--                {{ // solicitud.estado }}-->
                <q-chip v-if="solicitud.estado === 'MUESTRA NO TOMADA'" color="red" text-color="white" dense>
                  Muestra no tomada
                </q-chip>
                <div>{{solicitud.motivo_rechazo}}</div>
              </td>
              <td>{{ solicitud.fecha_envio_analitica }}</td>
<!--              <td>-->
<!--                <q-chip v-if="solicitud.estado === 'ANALIZADO'" color="green" text-color="white" dense>-->
<!--                  Finalizado-->
<!--                </q-chip>-->
<!--                <q-chip v-else-if="solicitud.estado === 'ENVIADO_ANALITICA'" color="red" text-color="white" dense>-->
<!--                  Recibido-->
<!--                </q-chip>-->
<!--&lt;!&ndash;                MUESTRA RECHAZADA&ndash;&gt;-->
<!--                <q-chip v-else-if="solicitud.estado === 'MUESTRA RECHAZADA'" color="orange" text-color="white" dense>-->
<!--                  Muestra Rechazada-->
<!--                </q-chip>-->
<!--              </td>-->
              <td >
                <div style="border: 1px solid rgba(34,103,182,0.62); border-radius: 4px; padding: 2px;">
                  <ul style="padding-left: 1em; margin: 0;list-style: none">
                    <li v-for="servicio in solicitud.servicios" :key="servicio.id" style="text-decoration: none">
                      <!--                    icono verde de epntinte pque;o-->
                      <!--                    <q-icon name="check_circle" color="green" size="12px" class="q-mr-xs" />-->
                      <!--                    rojo-->
                      <!--                    <q-icon name="cancel" color="orange" size="12px" class="q-mr-xs"/>-->
                      <!--                    <pre>{{servicio.pivot.realizado}}</pre>-->
                      <q-icon name="cancel" color="orange" size="12px" v-if="servicio.pivot.realizado === 'PENDIENTE'">
                        <q-tooltip>Pendiente</q-tooltip>
                      </q-icon>
                      <q-icon name="check_circle" color="green" size="12px" v-else>
                        <q-tooltip>Realizado</q-tooltip>
                      </q-icon>
                      <q-icon
                        name="inventory_2"
                        size="12px"
                        class="q-ml-xs"
                        :color="servicio.pivot.fue_recogido ? 'light-blue-6' : 'grey-4'"
                      >
                        <q-tooltip>{{ servicio.pivot.fue_recogido ? 'Resultado entregado' : 'No entregado aún' }}</q-tooltip>
                      </q-icon>
                      {{ $filters.textCapitalize(servicio.nombre) }} - {{ $filters.textCapitalize(servicio.precio) }} -
                      <span class="text-grey" style="font-size: 0.8em;">
                        {{ $filters.textCapitalize(servicio.area.name) }}
                      </span>
                      <span v-if="servicio.pivot.realizado_por" class="text-teal-8 text-weight-medium q-ml-xs" style="font-size:0.8em;">
                        · {{ servicio.pivot.realizado_por }}
                      </span>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
            </tbody>
          </q-markup-table>

          <!-- Paginación -->
          <div class="row items-center justify-between q-px-sm q-py-xs q-mt-xs">
            <div class="col-auto text-caption text-grey-7">
              Mostrando <b>{{ paginacionInfo.desde }}-{{ paginacionInfo.hasta }}</b> de <b>{{ totalSolicitudes }}</b> solicitudes
            </div>
            <div class="col-auto row items-center q-gutter-sm">
              <q-select
                v-model="rowsPerPage"
                :options="[10, 25, 50, 100]"
                dense outlined options-dense
                style="width:85px"
                label="Filas"
                @update:model-value="onChangeRowsPerPage"
              />
              <q-pagination
                v-model="page"
                :max="pagesNumber"
                max-pages="7"
                boundary-links direction-links
                icon-first="first_page" icon-last="last_page"
                icon-prev="chevron_left" icon-next="chevron_right"
                size="sm"
                color="primary"
                @update:model-value="onChangePage"
              />
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

  <!-- DIÁLOGO IMPRIMIR RESULTADOS -->
  <q-dialog v-model="dialogPresentacion" maximized>
    <q-card>
      <q-card-section class="row items-center bg-teal text-white q-pa-sm">
        <q-icon name="print" size="24px" class="q-mr-sm" />
        <div class="text-subtitle1 text-weight-bold">Registro de entrega — {{ from }} / {{ to }}</div>
        <q-space />
        <q-chip color="white" text-color="teal" dense icon="checklist">{{ seleccionadosSolicitudes.length }} solicitudes</q-chip>
        <q-btn flat round dense icon="close" color="white" v-close-popup class="q-ml-sm" />
      </q-card-section>

      <q-card-section class="q-pa-sm">
        <div class="row q-gutter-sm items-center q-mb-sm">
          <q-btn color="teal" icon="picture_as_pdf" label="Registrar y abrir PDF" no-caps :loading="loadingPresentacion" :disable="!seleccionadosSolicitudes.length" @click="registrarYAbrirPdf" />
          <q-btn outline color="grey-7" icon="refresh" label="Recargar" no-caps @click="cargarParaPresentacion" />
        </div>

        <div v-if="loadingPresentacion" class="text-center q-pa-lg">
          <q-spinner size="40px" color="teal" />
        </div>

        <div v-else-if="!gruposPresentacion.length" class="text-center text-grey q-pa-lg">
          No hay prestaciones realizadas para esta fecha.
        </div>

        <div v-else>
          <div v-for="grupo in gruposPresentacion" :key="grupo.area_id" class="q-mb-md">
            <div class="text-subtitle2 text-weight-bold bg-teal-1 q-pa-xs q-mb-xs rounded-borders row items-center" style="border-left:4px solid #009688">
              <q-checkbox dense :model-value="todosDelGrupo(grupo)" @update:model-value="toggleGrupo(grupo, $event)" color="teal" class="q-mr-xs" />
              {{ grupo.area_nombre }}
            </div>
            <q-markup-table dense flat bordered>
              <thead>
                <tr class="bg-grey-2">
                  <th style="width:36px"></th>
                  <th class="text-left">ID</th>
                  <th class="text-left">Nro Registro</th>
                  <th class="text-left">Paciente</th>
                  <th class="text-left">Prestación</th>
                  <th class="text-left">Aceptado por</th>
                  <th class="text-left">Fecha y hora</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="sol in grupo.solicitudes" :key="sol.solicitud_id">
                  <tr v-for="(srv, i) in sol.servicios" :key="srv.id">
                    <td><q-checkbox v-if="i === 0" dense v-model="seleccionados" :val="sol.solicitud_id" color="teal" /></td>
                    <td v-if="i === 0" :rowspan="sol.servicios.length" class="text-weight-bold text-primary">{{ sol.analisis_id ?? sol.solicitud_id }}</td>
                    <td>{{ sol.nro_registro }}</td>
                    <td>{{ sol.paciente_nombre }}</td>
                    <td>{{ srv.nombre }}</td>
                    <td v-if="i === 0" :rowspan="sol.servicios.length">
                      <span v-if="sol.user_presentacion" class="text-positive text-weight-medium">{{ sol.user_presentacion }}</span>
                      <span v-else class="text-grey-5 text-caption">—</span>
                    </td>
                    <td v-if="i === 0" :rowspan="sol.servicios.length">
                      <span v-if="sol.fecha_presentacion" class="text-positive text-caption">{{ sol.fecha_presentacion }}</span>
                      <span v-else class="text-grey-5 text-caption">—</span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </q-markup-table>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>

  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'AreaAnaliticaListPage',
  data () {
    return {
      from: moment().startOf('month').format('YYYY-MM-DD'),
      to: moment().endOf('month').format('YYYY-MM-DD'),
      fecha: moment().format('YYYY-MM-DD'),
      solicitudes: [],
      totalSolicitudes: 0,
      loading: false,
      filter: '',
      page: 1,
      rowsPerPage: 25,
      dialogPresentacion: false,
      loadingPresentacion: false,
      gruposPresentacion: [],
      seleccionados: [],
    }
  },
  computed: {
    seleccionadosSolicitudes () {
      return [...new Set(this.seleccionados)]
    },
    pagesNumber () {
      return Math.max(1, Math.ceil(this.totalSolicitudes / this.rowsPerPage))
    },
    solicitudesPaginadas () {
      return this.solicitudes
    },
    paginacionInfo () {
      const total = this.totalSolicitudes
      if (!total) return { desde: 0, hasta: 0 }
      const desde = (this.page - 1) * this.rowsPerPage + 1
      const hasta = Math.min(this.page * this.rowsPerPage, total)
      return { desde, hasta }
    }
  },
  mounted () {
    this.analiticaGet()
    // if (!this.$store.socketAnalitica) {
    //   this.$store.socketAnalitica = true
    //   this.$socket.on('silSolicitud', msg => {
    //     this.$alert.info('Nueva solicitud de analítica recibido.')
    //     this.analiticaGet()
    //   })
    // }
  },
  methods: {
    hasPermission(perm) {
        return this.$store.permissions.includes(perm)
    },
    printQuimica (solicitud) {
      const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf`
      window.open(url, '_blank')
    },
    printQuimicaTolerancia(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf-tolerancia`
      window.open(url, '_blank')
    },

    printCitoQuimico(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf-cito-quimico`
      window.open(url, '_blank')
    },

    enviarWhatsApp(solicitud, tipo) {
      let mensajeWhatssApp = ''
      let telefono = ''
      let linkPdf = ''

      // ===== Links por tipo (mismo estilo que Hematología) =====
      if (tipo === 'HematologiaDoctor' || tipo === 'HematologiaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/hematologia/solicitud/${solicitud.hematologia?.code}/pdf`
      } else if (tipo === 'QuimicaDoctor' || tipo === 'QuimicaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf`
      } else if (tipo === 'UroanalisisDoctor' || tipo === 'UroanalisisPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/uroanalisis/solicitud/${solicitud.uroanalisis?.code}/pdf`
      } else if (tipo === 'ParasitologiaDoctor' || tipo === 'ParasitologiaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/parasitologia/solicitud/${solicitud.parasitologia?.code}/pdf`
      } else if (tipo === 'PapilomaDoctor' || tipo === 'PapilomaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/papiloma-humano/solicitud/${solicitud.papiloma_humano?.code}/pdf`
      } else if (tipo === 'PanelRespiratorioDoctor' || tipo === 'PanelRespiratorioPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/panel-respiratorio/solicitud/${solicitud.panel_respiratorio?.code}/pdf`
      } else if (tipo === 'PanelSexualDoctor' || tipo === 'PanelSexualPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/panel-sexual/solicitud/${solicitud.panel_sexual?.code}/pdf`
      } else if (tipo === 'InmunologiaDoctor' || tipo === 'InmunologiaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/inmunologia/solicitud/${solicitud.inmunologia?.code}/pdf`
      } else if (tipo === 'InmunologiaAnaliticaDoctor' || tipo === 'InmunologiaAnaliticaPaciente') {
        linkPdf = `${this.$axios.defaults.baseURL}/inmunologia-analitica/resultado/${solicitud.inmunologia_analitica_codigo}/pdf`
      }

      // ===== Mensajes Doctor/Paciente =====
      if (tipo === 'HematologiaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Hematología para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'HematologiaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Hematología ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'QuimicaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Química Sanguínea para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'QuimicaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Química Sanguínea ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'UroanalisisDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Uroanálisis para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'UroanalisisPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Uroanálisis ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'ParasitologiaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Parasitología para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'ParasitologiaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Parasitología ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'PapilomaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Papiloma Humano para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'PapilomaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Papiloma Humano ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'PanelRespiratorioDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Panel Respiratorio para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'PanelRespiratorioPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Panel Respiratorio ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'PanelSexualDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Panel Sexual para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'PanelSexualPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Panel Sexual ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'InmunologiaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Inmunología para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'InmunologiaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Inmunología ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      } else if (tipo === 'InmunologiaAnaliticaDoctor') {
        mensajeWhatssApp = `Estimado Dr. ${solicitud.doctor_nombre}, le informamos que los resultados de Inmunología para el paciente ${solicitud.paciente_nombre} (CI: ${solicitud.paciente_ci}) ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.doctor_telefono
      } else if (tipo === 'InmunologiaAnaliticaPaciente') {
        mensajeWhatssApp = `Estimado/a ${solicitud.paciente_nombre}, le informamos que sus resultados de Inmunología ya están disponibles. Puede acceder a los resultados en el siguiente enlace: ${linkPdf}`
        telefono = solicitud.paciente_telefono
      }

      const urlWhatsApp = `https://api.whatsapp.com/send?phone=${telefono}&text=${encodeURIComponent(mensajeWhatssApp)}`
      window.open(urlWhatsApp, '_blank')
    },

    printHematologia(solicitud) {
      // $query = Solicitude::with([
      //   'paciente', 'doctor', 'servicios.area.rangos', 'resultados',
      //   'hematologia',
      //   'quimicaSanguinea',
      //   'uroanalisis',
      //   'parasitologia',
      //   'papilomaHumano',
      //   'panelRespiratorio',
      //   'panelSexual',
      //   'cultivoAntibiograma',
      // ])
      const url = `${this.$axios.defaults.baseURL}/hematologia/solicitud/${solicitud.hematologia?.code}/pdf`
      window.open(url, '_blank')
    },
    printUroanalisis (solicitud) {
      const url = `${this.$axios.defaults.baseURL}/uroanalisis/solicitud/${solicitud.uroanalisis?.code}/pdf`
      window.open(url, '_blank')
    },
    printParasitologia(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/parasitologia/solicitud/${solicitud.parasitologia?.code}/pdf`
      window.open(url, '_blank')
    },
    printPapilomaHumano(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/papiloma-humano/solicitud/${solicitud.papiloma_humano?.code}/pdf`
      window.open(url, '_blank')
    },
    printPanelRespiratorio(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/panel-respiratorio/solicitud/${solicitud.panel_respiratorio?.code}/pdf`
      window.open(url, '_blank')
    },
    printPanelSexual(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/panel-sexual/solicitud/${solicitud.panel_sexual?.code}/pdf`
      window.open(url, '_blank')
    },
    printCultivoAntibiograma(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/cultivo-antibiograma/solicitud/${solicitud.cultivoAntibiograma?.code}/pdf`
      window.open(url, '_blank')
    },
    printInmunologia(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/inmunologia/solicitud/${solicitud.id}/pdf-all?area_id=5`
      window.open(url, '_blank')
    },
    printInmunologiaAnalitica(solicitud) {
      const url = `${this.$axios.defaults.baseURL}/inmunologia-analitica/resultado/${solicitud.inmunologia_analitica_codigo}/pdf`
      window.open(url, '_blank')
    },
    selectHematologia(solicitud) {
      this.$router.push({ name: 'analitica-hematologia', params: { id: solicitud.id } })
    },
    selectRow(solicitud) {
      this.$router.push({ name: 'analitica-detalle', params: { id: solicitud.id } })
    },
    clearFilter () {
      this.filter = ''
      this.analiticaGet()
    },
    onChangePage (page) {
      this.page = page
      this.analiticaGet(false)
    },
    onChangeRowsPerPage (val) {
      this.rowsPerPage = val
      this.page = 1
      this.analiticaGet(false)
    },
    async analiticaGet (resetPage = true) {
      this.loading = true
      try {
        if (resetPage) {
          this.page = 1
        }
        const isNumeric = /^\d+$/.test((this.filter || '').trim())
        const params = {
          filter: isNumeric ? '' : this.filter,
          from: this.from,
          to: this.to,
          codigo: isNumeric ? this.filter : '',
          page: this.page,
          per_page: this.rowsPerPage,
        }
        const response = await this.$axios.get('/solicitudesAnalitica', { params })
        this.solicitudes = response.data.data || []
        this.totalSolicitudes = response.data.total || 0
        this.page = response.data.current_page || this.page
      } catch (error) {
        this.$alert.error('Error al cargar las solicitudes de analítica.')
      } finally {
        this.loading = false
      }
    },

    abrirDialogPresentacion () {
      this.dialogPresentacion = true
      this.seleccionados = []
      this.cargarParaPresentacion()
    },

    async cargarParaPresentacion () {
      this.loadingPresentacion = true
      try {
        const res = await this.$axios.get('analitica/para-presentacion', { params: { fecha: this.from } })
        this.gruposPresentacion = res.data
        this.seleccionados = []
        this.gruposPresentacion.forEach(g => {
          g.solicitudes.forEach(sol => {
            if (!this.seleccionados.includes(sol.solicitud_id)) {
              this.seleccionados.push(sol.solicitud_id)
            }
          })
        })
      } catch {
        this.$alert.error('Error al cargar datos de presentación.')
      } finally {
        this.loadingPresentacion = false
      }
    },

    todosDelGrupo (grupo) {
      return grupo.solicitudes.every(sol => this.seleccionados.includes(sol.solicitud_id))
    },

    toggleGrupo (grupo, val) {
      grupo.solicitudes.forEach(sol => {
        if (val && !this.seleccionados.includes(sol.solicitud_id)) {
          this.seleccionados.push(sol.solicitud_id)
        }
        if (!val) {
          this.seleccionados = this.seleccionados.filter(id => id !== sol.solicitud_id)
        }
      })
    },

    async registrarYAbrirPdf () {
      this.loadingPresentacion = true
      try {
        await this.$axios.post('analitica/registrar-presentacion', {
          solicitude_ids: this.seleccionadosSolicitudes
        })
        const base = this.$axios.defaults.baseURL.replace(/\/api\/?$/, '')
        window.open(`${base}/api/analitica/pdf-presentacion?fecha=${this.from}`, '_blank')
      } catch {
        this.$alert.error('Error al registrar la presentación.')
      } finally {
        this.loadingPresentacion = false
      }
    },
  }
}
</script>
