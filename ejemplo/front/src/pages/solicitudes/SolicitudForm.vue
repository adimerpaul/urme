<template>
  <q-card style="max-width: 980px; margin: 0 auto;" flat bordered>
      <q-card-section class="row items-center q-pa-sm">
        <div class="text-subtitle1">
          Nueva solicitud
<!--          si hay fecah de creacion nesecito la mostrar-->
          <template v-if="solicitud.id">
            <q-chip size="sm" color="grey-4" text-color="black" class="q-ml-sm">
              Creación: {{ solicitud.fecha_creacion }} {{ solicitud.hora_creacion }}
            </q-chip>
          </template>
        </div>
        <q-space />
        <q-btn icon="arrow_back" flat round dense @click="$router.push({ path: '/solicitudes' })" />
        <q-btn color="primary" label="Guardar" @click="$refs.form.submit()" :loading="loading" />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <q-form @submit="guardar" ref="form">
          <div class="row q-col-gutter-xs q-mb-sm">
            <div class="col-12 col-sm-4">
              <q-input
                v-model="solicitud.codigo"
                label="Código"
                dense
                outlined
                clearable
                @update:model-value="onCodigoManualChange"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                v-model="solicitud.nro_registro"
                label="Nro. registro"
                dense
                outlined
                clearable
                @update:model-value="onNroRegistroManualChange"
              />
            </div>
          </div>

          <!-- Paciente -->
          <div class="row items-center q-mb-xs">
            <q-icon name="person" size="18px" class="q-mr-xs" />
            <div class="text-subtitle2">
              Datos del paciente
<!--              <q-btn flat dense icon="child_care" color="primary" class="q-ml-sm"-->
<!--                     @click="rnnnGet('RN')"-->
<!--                     label="RN" />-->
              <q-btn flat dense icon="face" color="primary" class="q-ml-xs"
                     @click="rnnnGet('NN')"
                     label="NN" />
            </div>
          </div>
          <div class="row q-col-gutter-xs">
            <div class="col-6 col-sm-3">
              <q-input v-model="solicitud.paciente_ci" label="CI" dense outlined clearable
                       @update:model-value="onChangeCi" debounce="600" >
                <template v-slot:append>
                  <q-spinner
                    color="primary"
                    v-if="loading"
                  />
                </template>
              </q-input>
<!--              <pre>{{solicitud}}</pre>-->
            </div>
            <div class="col-12 col-sm-6">
              <div class="relative-position">
                <q-input
                  v-model="solicitud.paciente_nombre"
                  label="Nombre / CI / Teléfono"
                  dense outlined clearable
                  :loading="loadingPacientes"
                  @update:model-value="buscarPacientesInput"
                  @clear="limpiarPaciente"
                  @keydown="blockSearchSpecialChars"
                  @paste.prevent="onPastePaciente"
                >
                  <template #append>
                    <q-icon name="search" color="grey-5" v-if="!loadingPacientes" />
                  </template>
                </q-input>
                <q-menu
                  no-focus
                  no-refocus
                  fit
                  :model-value="pacientesOptions.length > 0"
                  @update:model-value="val => { if (!val) pacientesOptions = [] }"
                  style="max-height: 260px; overflow-y: auto"
                >
                  <q-list dense>
                    <q-item
                      v-for="p in pacientesOptions"
                      :key="p.id"
                      clickable
                      v-close-popup
                      @click="onSelectPaciente(p)"
                    >
                      <q-item-section>
                        <q-item-label>{{ p.nombre_completo }}</q-item-label>
                        <q-item-label caption>CI: {{ p.ci }} • Tel: {{ p.telefono || 'S/N' }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </div>
            </div>
            <div class="col-6 col-sm-3">
              <q-input
                v-model="solicitud.paciente_telefono"
                label="Celular"
                dense
                outlined
                clearable
                @update:model-value="val => setUpperSolicitudField('paciente_telefono', val)"
              />
            </div>

<!--            <div class="col-12 col-md-6">-->
<!--              <q-input v-model="solicitud.paciente_direccion" label="Dirección" dense outlined clearable/>-->
<!--            </div>-->
            <div class="col-12 col-md-6">
              <div class="text-caption text-black">FUM (Fecha última menstruación)</div>
              <q-chip size="12px" color="primary" text-color="white">FUM: {{ solicitud.paciente_fum || 'N/A' }}</q-chip>
<!--              <pre>{{solicitud.paciente.fum}}</pre>-->
            </div>

            <div class="col-6 col-sm-4">
              <q-input v-model="solicitud.paciente_fecha_nac" type="date" label="F. nacimiento"
                       dense outlined @update:model-value="onCalculateEdad" />
            </div>

            <div class="col-6 col-sm-4">
              <div class="text-caption text-black">Género</div>
              <q-radio v-model="solicitud.paciente_genero" val="F" label="F" dense />
              <q-radio v-model="solicitud.paciente_genero" val="M" label="M" dense />
              <q-radio v-model="solicitud.paciente_genero" val="OTRO" label="Otro" dense />
            </div>

            <div class="col-12 col-sm-2">
              <q-input v-model.number="solicitud.paciente_edad" type="number" label="Edad" dense outlined />
            </div>
            <div class="col-12 col-sm-2">
              <span class="text-bold">Edad adm: </span> <br>
              {{edadadmY}}
            </div>

            <div class="col-12 col-sm-4">
              <q-toggle
                v-model="solicitud.paciente_discapacidad"
                :true-value="1"
                :false-value="0"
                label="Discapacidad"
                dense
              />
            </div>
            <div class="col-12 col-sm-4" v-if="solicitud.paciente_discapacidad">
              <q-input
                v-model="solicitud.paciente_discapacidad_cual"
                label="Discapacidad (cual)"
                dense
                outlined
                clearable
                @update:model-value="val => setUpperSolicitudField('paciente_discapacidad_cual', val)"
              />
            </div>
            <div class="col-12 col-sm-4" v-if="solicitud.paciente_discapacidad">
              <q-input
                v-model="solicitud.paciente_discapacidad_otro"
                label="Discapacidad (otro)"
                dense
                outlined
                clearable
                @update:model-value="val => setUpperSolicitudField('paciente_discapacidad_otro', val)"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-toggle
                v-model="solicitud.paciente_embarazo"
                :true-value="1"
                :false-value="0"
                label="Embarazo"
                dense
              />
            </div>
            <div class="col-12 col-sm-4" v-if="solicitud.paciente_embarazo">
              <q-input
                v-model="solicitud.paciente_fum"
                type="date"
                label="FUM"
                dense
                @update:model-value="
                solicitud.paciente_sem_gest = solicitud.paciente_fum
                  ? Math.floor(moment().diff(moment(solicitud.paciente_fum, 'YYYY-MM-DD'), 'weeks', true))
                  : null"
                outlined
              />
            </div>
            <div class="col-12 col-sm-4" v-if="solicitud.paciente_embarazo">
              <q-input
                v-model.number="solicitud.paciente_sem_gest"
                type="number"
                label="Semanas gestación"
                dense
                outlined
              />
            </div>
          </div>

          <q-separator class="q-my-sm" />

          <!-- Doctor -->
          <div class="row items-center q-mb-xs">
            <q-icon name="person" size="18px" class="q-mr-xs" />
            <div class="text-subtitle2">Datos del médico solicitante</div>
          </div>

          <div class="row q-col-gutter-xs">
            <div class="col-12">
              <q-select
                v-model="solicitud.doctor_id"
                :options="doctoresOptions"
                use-input
                option-value="id"
                :option-label="doctor =>
                  doctor.nombre + ' (' + doctor.especialidad + ')' +
                  (doctor.telefono ? ' - ' + doctor.telefono : '') + ' ' +
                  (doctor.establecimiento?.nombre || '')
                "
                @filter="filterDoctores"
                emit-value map-options
                dense outlined clearable
                label="Doctor (opcional)"
                @update:model-value="onSelectDoctor"
              >
<!--                <template agregra boton para agregar nuevo doctor>-->
                <template #after>
                  <q-btn flat dense icon="person_add" color="positive" @click="dialogDoctorNew=true" />
                </template>
              </q-select>
            </div>
          </div>

          <q-separator class="q-my-sm" />

          <!-- Datos solicitud -->
          <div class="row items-center q-mb-xs">
            <q-icon name="assignment" size="18px" class="q-mr-xs" />
            <div class="text-subtitle2">Datos de la solicitud</div>
          </div>

          <div class="row q-col-gutter-xs items-center">
            <div class="col-6 col-sm-3">
              <q-toggle v-model="solicitud.tipo_atencion" true-value="SI" false-value="NO" dense
                        @update:model-value="onTipoAtencionChange">
                {{ solicitud.tipo_atencion === 'SI' ? 'SUS' : 'EXT' }}
              </q-toggle>
            </div>

            <template v-if="solicitud.tipo_atencion === 'NO'">
              <div class="col-12 col-md-4">
                <q-select
                  v-model="solicitud.tipo_paciente_externo"
                  :options="[
                    'PACIENTES SOAT',
                    'PROG. CHAGAS EPIDEMIOLOGIA',
                    'PROG. CHAGAS BANCO DE SANGRE',
                    'PROG. EPID. LEISHMANIA',
                    'TRABAJO SOCIAL'
                  ]"
                  dense outlined clearable
                  label="Programa / Tipo paciente externo"
                  @update:model-value="val => { if (!val) solicitud.autorizado_por = '' }"
                />
              </div>
              <div class="col-12 col-md-4" v-if="solicitud.tipo_paciente_externo">
                <q-input
                  v-model="solicitud.autorizado_por"
                  label="Autorizado por"
                  dense outlined clearable
                  @update:model-value="val => solicitud.autorizado_por = (val || '').toUpperCase()"
                />
              </div>
              <div class="col-6 col-md-3" >
<!--                <q-input  v-model="solicitud.tipo_otro"-->
<!--                          label="Especificar tipo de atención" dense outlined />-->
                <q-select
                  v-model="solicitud.establecimiento_salud"
                  :options="establecimientos"
                  option-label="nombre"
                  use-input
                  @filter="(val, update) => {
                  update(() => {
                    const text = (val || '').toLowerCase().trim()
                    if (!text) { this.establecimientos = this.establecimientosAll; return }
                    this.establecimientos = this.establecimientosAll
                      .filter(e => {
                        const nombre = String(e.nombre || '').toLowerCase()
                        const tipo = String(e.tipo || '').toLowerCase()
                        const nivel = String(e.nivel || '').toLowerCase()
                        return nombre.includes(text) || tipo.includes(text) || nivel.includes(text)
                      })
                      .slice(0, 50)
                  })
                }"
                  option-value="nombre"
                  emit-value map-options
                  label="Establecimiento de salud"
                  dense outlined clearable
                  @update:model-value="onEstablecimientoChange"
                >
                  <template #after>
                    <q-btn flat dense icon="add_business" color="positive"
                      @click="openDialogEstablecimiento('PRIVADO')" />
                  </template>
                  <template #option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section>
                        <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                        <q-item-label caption>{{ scope.opt.tipo }} • {{ scope.opt.nivel }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-6 col-md-3" >
                <q-input v-model="solicitud.numero_factura"
                         label="Número de factura" dense outlined />
              </div>
            </template>
            <div class="col-6 col-md-6" v-else>
              <q-select
                v-model="solicitud.establecimiento_salud"
                :options="establecimientos"
                option-label="nombre"
                use-input
                @filter="(val, update) => {
                  update(() => {
                    const text = (val || '').toLowerCase().trim()
                    if (!text) { this.establecimientos = this.establecimientosAll; return }
                    this.establecimientos = this.establecimientosAll
                      .filter(e => {
                        const nombre = String(e.nombre || '').toLowerCase()
                        const tipo = String(e.tipo || '').toLowerCase()
                        const nivel = String(e.nivel || '').toLowerCase()
                        return nombre.includes(text) || tipo.includes(text) || nivel.includes(text)
                      })
                      .slice(0, 50)
                  })
                }"
                option-value="nombre"
                emit-value map-options
                label="Establecimiento de salud"
                dense outlined clearable
                @update:model-value="onEstablecimientoChange"
              >
                <template #after>
                  <q-btn flat dense icon="add_business" color="positive"
                    @click="openDialogEstablecimiento('PUBLICO')" />
                </template>
                <template #option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                      <q-item-label caption>{{ scope.opt.tipo }} • {{ scope.opt.nivel }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
<!--              <pre>{{establecimientosPublicos}}</pre>-->
            </div>

<!--            <div class="col-12 col-md-6 q-mt-xs">-->
<!--              <div class="row no-wrap items-start q-gutter-x-xs">-->
<!--                <div class="col">-->
<!--                  <q-select-->
<!--                    v-model="solicitud.diagnostico_select"-->
<!--                    :options="diagnosticos"-->
<!--                    option-label="cie10"-->
<!--                    option-value="cie10"-->
<!--                    dense-->
<!--                    outlined-->
<!--                    clearable-->
<!--                    label="Buscar diagnóstico clínico"-->
<!--                    use-input-->
<!--                    emit-value-->
<!--                    map-options-->
<!--                    input-debounce="500"-->
<!--                    @filter="onFilterDiagnosticos"-->
<!--                    no-options-label="Escriba al menos 2 caracteres para buscar"-->
<!--                  >-->
<!--                    <template #option="scope">-->
<!--                      <q-item v-bind="scope.itemProps">-->
<!--                        <q-item-section>-->
<!--                          <q-item-label>{{ scope.opt.cie10 }}</q-item-label>-->
<!--                          <q-item-label caption>-->
<!--                            Especialidad: {{ scope.opt.especialidad }}-->
<!--                          </q-item-label>-->
<!--                        </q-item-section>-->
<!--                      </q-item>-->
<!--                    </template>-->
<!--                  </q-select>-->
<!--                </div>-->
<!--                &lt;!&ndash; Botón gestionar diagnósticos — solo Administrador &ndash;&gt;-->
<!--                <q-btn-->
<!--                  v-if="isAdmin"-->
<!--                  flat dense round-->
<!--                  icon="settings"-->
<!--                  color="primary"-->
<!--                  style="margin-top:2px"-->
<!--                  @click="abrirGestionDiagnosticos"-->
<!--                >-->
<!--                  <q-tooltip>Gestionar diagnósticos</q-tooltip>-->
<!--                </q-btn>-->
<!--              </div>-->
<!--            </div>-->

            <!-- ══════════ DIÁLOGO GESTIÓN DE DIAGNÓSTICOS (solo admin) ══════════ -->
            <q-dialog v-model="dialogDiagnosticos" maximized transition-show="slide-up" transition-hide="slide-down">
              <q-card class="column no-wrap" style="height:100vh">

                <q-bar class="bg-primary text-white" style="height:48px">
                  <q-icon name="medical_services" />
                  <span class="text-weight-bold q-ml-sm">Gestión de Diagnósticos Clínicos (CIE-10)</span>
                  <q-space />
                  <q-btn flat dense icon="close" v-close-popup />
                </q-bar>

                <div class="col row no-wrap" style="overflow:hidden">

                  <!-- Panel izquierdo: formulario -->
                  <div class="col-12 col-md-4 q-pa-md column" style="border-right:1px solid #e0e0e0; max-width:380px">
                    <div class="text-subtitle2 text-weight-bold q-mb-md">
                      <q-icon :name="diagEditando ? 'edit' : 'add_circle'" color="primary" class="q-mr-xs" />
                      {{ diagEditando ? 'Editar diagnóstico' : 'Nuevo diagnóstico' }}
                    </div>

                    <q-form @submit.prevent="guardarDiagnostico" class="column q-gutter-sm">
                      <q-input
                        v-model="diagForm.cie10"
                        label="CIE-10 / Descripción *"
                        dense outlined
                        :rules="[v => !!v || 'Requerido']"
                        hint="Ej: J06.9 Infección aguda de las vías respiratorias"
                      />
                      <q-input
                        v-model="diagForm.especialidad"
                        label="Especialidad *"
                        dense outlined
                        :rules="[v => !!v || 'Requerido']"
                        hint="Ej: Medicina Interna, Pediatría"
                      />
                      <q-input
                        v-model="diagForm.servicio"
                        label="Servicio *"
                        dense outlined
                        :rules="[v => !!v || 'Requerido']"
                        hint="Ej: Laboratorio, Emergencias"
                      />
                      <div class="row q-gutter-sm q-mt-sm">
                        <q-btn
                          v-if="diagEditando"
                          flat no-caps label="Cancelar edición"
                          @click="nuevoDiagnostico"
                          class="col"
                        />
                        <q-btn
                          type="submit"
                          color="primary"
                          no-caps
                          :label="diagEditando ? 'Actualizar' : 'Guardar'"
                          :loading="loadingDiag"
                          class="col"
                        />
                      </div>
                    </q-form>
                  </div>

                  <!-- Panel derecho: tabla -->
                  <div class="col column" style="overflow:hidden">
                    <div class="q-pa-md row items-center q-gutter-sm">
                      <q-input
                        v-model="diagSearch"
                        dense outlined clearable
                        placeholder="Buscar CIE-10, especialidad o servicio..."
                        style="min-width:260px"
                        debounce="300"
                        @update:model-value="fetchDiagnosticosAdmin(1)"
                      >
                        <template v-slot:append><q-icon name="search" /></template>
                      </q-input>
                      <q-space />
                      <div class="text-caption text-grey-7">
                        {{ diagPagination.rowsNumber }} registros
                      </div>
                    </div>

                    <div class="col" style="overflow:auto">
                      <q-table
                        :rows="diagRows"
                        :columns="columnsDiag"
                        row-key="id"
                        dense flat
                        :loading="loadingDiagTable"
                        v-model:pagination="diagPagination"
                        :rows-per-page-options="[15, 25, 50]"
                        @request="onDiagRequest"
                        class="full-height"
                      >
                        <template v-slot:body-cell-acciones="props">
                          <q-td :props="props">
                            <q-btn flat dense round icon="edit" color="primary" size="sm"
                              @click="editarDiagnostico(props.row)">
                              <q-tooltip>Editar</q-tooltip>
                            </q-btn>
                            <q-btn flat dense round icon="delete" color="negative" size="sm"
                              @click="eliminarDiagnostico(props.row)">
                              <q-tooltip>Eliminar</q-tooltip>
                            </q-btn>
                          </q-td>
                        </template>

                        <template #bottom="scope">
                          <div class="row items-center justify-between q-pa-xs full-width">
                            <div class="col text-caption">
                              {{ (scope.pagination.page - 1) * scope.pagination.rowsPerPage + 1 }}
                              – {{ Math.min(scope.pagination.page * scope.pagination.rowsPerPage, diagPagination.rowsNumber) }}
                              de {{ diagPagination.rowsNumber }}
                            </div>
                            <div class="col-auto row items-center q-gutter-xs">
                              <q-select
                                v-model="scope.pagination.rowsPerPage"
                                :options="[15,25,50]" dense outlined options-dense
                                style="width:70px"
                                @update:model-value="val => { diagPagination.rowsPerPage = val; fetchDiagnosticosAdmin(1) }"
                              />
                              <q-pagination
                                v-model="scope.pagination.page"
                                :max="Math.ceil(diagPagination.rowsNumber / diagPagination.rowsPerPage) || 1"
                                max-pages="6" boundary-links direction-links size="sm"
                                @update:model-value="val => fetchDiagnosticosAdmin(val)"
                              />
                            </div>
                          </div>
                        </template>
                      </q-table>
                    </div>
                  </div>
                </div>
              </q-card>
            </q-dialog>
            <div class="col-12 col-md-6 q-mt-xs">
              <q-input v-model="solicitud.diagnostico_clinico" type="textarea"
                       label="Diagnóstico clínico otros" dense outlined autogrow />
            </div>

            <div class="col-6">
              <q-input v-model="solicitud.fecha_solicitud" type="date" label="Fecha de solicitud medico"
                       dense outlined />
            </div>

            <div class="col-6">
              <q-select
                v-model="solicitud.unidad_solicitante_id"
                :options="unidadesSolicitantes"
                :option-label="(opt) => opt && opt.abreviatura ? opt.abreviatura + ' — ' + opt.nombre : (opt && opt.nombre || '')"
                option-value="id"
                use-input
                emit-value
                map-options
                label="Unidad solicitante"
                dense
                outlined
                clearable
                @filter="filterUnidadesSolicitantes"
                @update:model-value="onSelectUnidadSolicitante"
              >
                <template v-if="isAdmin" #after>
                  <q-btn
                    flat dense no-caps
                    color="primary"
                    icon="settings"
                    label="Gestionar"
                    @click="abrirDialogUnidadSolicitante"
                  >
                    <q-tooltip>Solo administradores</q-tooltip>
                  </q-btn>
                </template>
              </q-select>
            </div>

            <div class="col-2">
              <q-input v-model="solicitud.sala" label="Sala" dense outlined />
            </div>
            <div class="col-2">
              <q-input v-model="solicitud.cama" label="Cama" dense outlined />
            </div>
            <!--            diagnostico_select-->
            <!--            <div class="col-8">-->
            <!--            </div>-->
          </div>

          <q-separator class="q-my-sm" />

          <!-- Servicios -->
          <div class="row items-center q-mb-xs">
            <q-icon name="biotech" size="18px" class="q-mr-xs" />
            <div class="text-subtitle2">Servicios solicitados</div>
            <q-space />
            <q-badge color="primary" outline>{{ totalServiciosSeleccionados }} seleccionados</q-badge>
          </div>

          <q-card flat bordered class="q-mb-xs">
<!--            <q-card-section class="row q-col-gutter-xs">-->
<!--              <div class="col-12 col-sm-6">-->
<!--                <q-input v-model="serviciosFilter" dense outlined label="Buscar servicio (nombre / código / subárea)" clearable>-->
<!--                  <template #append><q-icon name="search" /></template>-->
<!--                </q-input>-->
<!--              </div>-->
<!--              <div class="col-12 col-sm-6">-->
<!--                <q-select v-model="serviciosAreaId" :options="areas" option-label="name" option-value="id"-->
<!--                          dense outlined clearable label="Filtrar por área" emit-value map-options>-->
<!--                  <template #prepend><q-icon name="science" /></template>-->
<!--                </q-select>-->
<!--                &lt;!&ndash;                <pre>{{serviciosAreaId}}</pre>&ndash;&gt;-->
<!--              </div>-->
<!--            </q-card-section>-->

            <q-card-section class=" text-grey-7">
              <div v-if="solicitud.tipo_atencion === 'SI' && establecimientoServiciosBase">
                Mostrando servicios base de: <b>{{ establecimientoServiciosBase.nombre }}</b>
              </div>
              <div v-else-if="solicitud.tipo_atencion === 'SI'">
                No se encontró el establecimiento base de servicios.
              </div>
              <div v-else>
                Mostrando todos los servicios disponibles (atención particular / especificar).
              </div>
              <div v-if="selectedServicios.length === 0" class="q-mt-sm">
                No ha seleccionado ningún servicio aún.
              </div>
              <div v-else>
                Servicios seleccionados:
                <ul>
                  <li v-for="(s, index) in selectedServicios" :key="index">
                    {{ s.area }} - {{ s.servicio }} (Bs. {{ s.precio }})
                  </li>
                </ul>
<!--                mostrar cantidada de sericio selecionados y total-->
                <div class="text-bold">
                  Total de servicios seleccionados: <b>{{ selectedServicios.length }}</b>
                  Monto total: <b>Bs. {{ selectedServicios.reduce((sum, s) => sum + parseFloat(s.precio), 0) }}</b>
                </div>
              </div>
            </q-card-section>
          </q-card>
<!--          <div class="row"  mostrar solo al crar no al editar-->
          <div class="row q-ma-md" v-if="!solicitud.id" style="border: 4px solid #3151d9; padding: 10px; border-radius: 4px;">
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.bilirrubinas_totales" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([17])"
                          class="text-subtitle2"
              >
                Bilirrubinas totales y fracciones
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.inmunoglobulinas" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([144, 145, 146])"
                          class="text-subtitle2"
              >
                Inmunoglobulinas IgG, IgM, IgA
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.coproparasitologico_simple" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([70])"
                          class="text-subtitle2"
              >
                Coproparasitológico simple
              </q-checkbox>
            </div>
<!--            moco fecal-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.moco_fecal" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([75])"
                          class="text-subtitle2"
              >
                Moco fecal
              </q-checkbox>
            </div>
<!--            Coproparasitológico seriado-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.coproparasitologico_seriado" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([71])"
                          class="text-subtitle2"
              >
                Coproparasitológico seriado
              </q-checkbox>
            </div>
<!--            Nitrógeno ureico sérico y urea-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.nitrogeno_ureico" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([39, 51])"
                          class="text-subtitle2"
              >
                Nitrógeno ureico sérico y urea
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.creatinina_orina" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([77])"
                          class="text-subtitle2"
              >
                Sangre Oculta en Heces
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.proteinac" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([54])"
                          class="text-subtitle2"
              >
                Proteína C Reactiva (PCR)
              </q-checkbox>
            </div>
<!--            ☐ Creatinina sérica-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.creatinina_serica" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([25])"
                          class="text-subtitle2"
              >
                Creatinina sérica
              </q-checkbox>
            </div>
<!--            ☐ Proteinuria de 24 horas-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.proteinuria_24h" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([45])"
                          class="text-subtitle2"
              >
                Proteinuria de 24 horas
              </q-checkbox>
            </div>
<!--            ☐ Cultivo p/ gérmenes comunes y antibiograma-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.cultivo_germenes" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([79])"
                          class="text-subtitle2"
              >
                Cultivo p/ gérmenes comunes y antibiograma
              </q-checkbox>
            </div>
<!--            ☐ Prueba rápida para sífilis-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.prueba_sifilis" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([59])"
                          class="text-subtitle2"
              >
                Prueba rápida para sífilis
              </q-checkbox>
            </div>
<!--            ☐ Examen general de orina-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.examen_orina" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([65])"
                          class="text-subtitle2"
              >
                Examen general de orina
              </q-checkbox>
            </div>
<!--            ☐ Tiempo de coagulación y tiempo de sangría-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.tiempo_coagulacion" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([10,12,13,1])"
                          class="text-subtitle2"
              >
                Coagulograma
              </q-checkbox>
            </div>
<!--            ☐ Electrolitos (sodio, potasio, cloro)-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.electrolitos" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([26])"
                          class="text-subtitle2"
              >
                Electrolitos (sodio, potasio, cloro)
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.tiempo_protrombina" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([12])"
                          class="text-subtitle2"
              >
                Tiempo de protrombina/APTT
              </q-checkbox>
            </div>
<!--            ☐ Factor reumatoideo-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.factor_reumatoideo" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([53])"
                          class="text-subtitle2"
              >
                Factor reumatoideo
              </q-checkbox>
            </div>
<!--            ☐ Transaminasas TGO – TGP-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.transaminasas" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([49,48])"
                          class="text-subtitle2"
              >
                Transaminasas TGO – TGP
              </q-checkbox>
            </div>
<!--            ☐ Fosfatasa alcalina y ácida-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.fosfatasa" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([27])"
                          class="text-subtitle2"
              >
                Fosfatasa alcalina y ácida
              </q-checkbox>
            </div>
<!--            ☐ Test de embarazo en sangre (HCG)-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.test_embarazo" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([63])"
                          class="text-subtitle2"
              >
                Test de embarazo en sangre (HCG)
              </q-checkbox>
            </div>
<!--            ☐ Frotis tinción Gram-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.frotis_gram" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([82])"
                          class="text-subtitle2"
              >
                Frotis tinción Gram
              </q-checkbox>
            </div>
<!--            ☐ Reactantes de fase aguda (VES, Fibrinógeno, PCR)-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.reactantes_fase_aguda" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([2, 3, 54])"
                          class="text-subtitle2"
              >
                Reactantes de fase aguda (VES, Fibrinógeno, PCR)
              </q-checkbox>
            </div>
<!--            ☐ Grupo sanguíneo y factor Rh-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.grupo_sanguineo" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([5])"
                          class="text-subtitle2"
              >
                Grupo sanguíneo y factor Rh
              </q-checkbox>
            </div>
<!--            Reacción Widal-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.reaccion_widal" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([61])"
                          class="text-subtitle2"
              >
                Reacción Widal
              </q-checkbox>
            </div>
<!--            Glicemia-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.glicemia" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([31])"
                          class="text-subtitle2"
              >
                Glicemia
              </q-checkbox>
            </div>
<!--            ☐ RPR para sífilis – VDRL-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.rpr_sifilis" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([62])"
                          class="text-subtitle2"
              >
                RPR para sífilis – VDRL
              </q-checkbox>
            </div>
<!--            ☐ Gasometría arterial o venosa-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.gasometria" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([30])"
                          class="text-subtitle2"
              >
                Gasometría arterial o venosa
              </q-checkbox>
            </div>
<!--&lt;!&ndash;           Hemoglobina y hematocrito-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.hemoglobina" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([17])"
                          class="text-subtitle2"
              >
                Tamizaje  Neonatal
              </q-checkbox>
            </div>
<!--            Hemograma completo-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.hemograma_completo" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([6])"
                          class="text-subtitle2"
              >
                Hemograma completo
              </q-checkbox>
            </div>
<!--            VIH-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.vih" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([58])"
                          class="text-subtitle2"
              >
                VIH
              </q-checkbox>
            </div>
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.perfil" :true-value="1" :false-value="null" dense
                          @update:model-value="selecinarCodigo([116,118,119,120,121])"
                          class="text-subtitle2"
              >
                Perfil Tiroideo
              </q-checkbox>
            </div>
<!--            Ihonograma-->
            <div class="col-12 col-md-6">
              <q-checkbox v-model="extras.ihonograma" :true-value="1" :false-value="null" dense
                @update:model-value="selecinarCodigo([35])"
                          class="text-subtitle2"
              >
                Ionograma
              </q-checkbox>
            </div>

          </div>
          <div style="
            margin: 4px 0;
            padding: 10px 12px;
            background-color: #fff9c4;
            background-image: radial-gradient(#fdd835 1.5px, transparent 1.5px);
            background-size: 14px 14px;
            border: 2px solid #f9a825;
            border-radius: 8px;
          ">
            <div class="row items-center q-mb-sm">
              <q-icon name="manage_search" color="amber-9" size="20px" class="q-mr-xs" />
              <span style="font-weight:700; font-size:13px; color:#795548;">Buscar y filtrar servicios</span>
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input v-model="serviciosFilter" dense outlined
                         label="Nombre / código / subárea" clearable
                         bg-color="white"
                >
                  <template #prepend><q-icon name="search" color="amber-9" /></template>
                </q-input>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="serviciosAreaId" :options="areas" option-label="name" option-value="id"
                          dense outlined clearable label="Filtrar por área" emit-value map-options
                          bg-color="white"
                >
                  <template #prepend><q-icon name="science" color="amber-9" /></template>
                </q-select>
              </div>
            </div>
          </div>

          <div class="row q-col-gutter-xs">
            <div class="col-12">
              <q-expansion-item
                v-for="area in areas"
                :key="area.id || area.name"
                :label="area.name"
                icon="science"
                expand-separator
                dense
                default-opened
                v-show="filteredServicios(area).length > 0"
              >
                <q-card flat>
                  <q-card-section class="q-pa-xs">
                    <div class="row q-col-gutter-xs">
                      <div v-for="servicio in filteredServicios(area)" :key="servicio.id || servicio.codigo"
                           class="col-12 col-sm-6">
<!--                        <pre>{{servicio}}</pre>-->
                        <q-checkbox v-model="servicio.seleccionado" :true-value="1" :false-value="0" dense
                          @update:model-value="onServicioToggle(servicio, $event)"
                        >
                          <div>
                            {{ textCapitalize(servicio.nombre) }}
                            <span class="text-primary">(Bs. {{ servicio.precio }})</span>
                          </div>
                          <div>
                            <small class="text-grey">
                              {{ servicio.codigo ? 'Código: ' + servicio.codigo + ' • ' : '' }}
                              {{ servicio.subarea ? 'Subárea: ' + textCapitalize(servicio.subarea) : '' }}
                            </small>
                          </div>
                        </q-checkbox>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>

              <div v-if="areas.length === 0" class="text-center text-grey q-mt-md">
                No hay servicios configurados.
              </div>
            </div>
          </div>

          <div class="text-right q-mt-sm">
            <q-btn flat label="Cancelar" :loading="loading" @click="$router.push({ path: '/solicitudes' })" />
            <q-btn color="primary" label="Guardar" type="submit" class="q-ml-xs" :loading="loading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  <q-dialog v-model="consentimientoDialog" persistent>
    <q-card style="width: min(920px, 96vw); max-width: 96vw;">
      <q-card-section class="row items-center">
        <div class="text-h6">Consentimiento de la solicitud</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-separator />
      <q-card-section>
        <div class="row q-col-gutter-sm q-mb-sm">
          <div class="col-12 col-sm-6">
            <b>Paciente:</b> {{ consentimiento.nombre_completo || '-' }}
            <q-btn flat dense icon="content_copy" color="primary" @click="consentimiento.declarante_nombre = consentimiento.nombre_completo" />
          </div>
          <div class="col-12 col-sm-3"><b>CI:</b> {{ consentimiento.ci || '-' }}</div>
          <div class="col-12 col-sm-3"><b>Solicitud:</b> #{{ solicitudCreadaId || '-' }}</div>
        </div>

        <q-form @submit.prevent="guardarConsentimientoNuevaSolicitud">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-3">
              <q-input v-model="consentimiento.fecha_recepcion" type="date" dense outlined label="Fecha recepcion" />
            </div>
            <div class="col-12 col-sm-3">
              <q-input v-model="consentimiento.hora_recepcion" type="text" dense outlined label="Hora recepcion" maxlength="10" />
            </div>
            <div class="col-12 col-sm-3">
              <q-input v-model="consentimiento.fecha_solicitud" type="date" dense outlined label="Fecha solicitud medico" />
            </div>
            <div class="col-12 col-sm-3">
              <q-input v-model="consentimiento.fecha_consentimiento" type="date" dense outlined label="Fecha consentimiento" />
            </div>

            <div class="col-12 col-sm-4">
              <q-toggle v-model="consentimiento.medicamento" :true-value="1" :false-value="0" label="Medicamento" />
            </div>
            <div class="col-12 col-sm-8" v-if="consentimiento.medicamento">
              <q-input v-model="consentimiento.tratamiento" dense outlined label="Tratamiento" />
            </div>

            <div class="col-12 col-sm-4">
              <q-select v-model="consentimiento.condicion" :options="['BASAL', 'AYUNO PROL', 'POST PRANDIAL', 'ETAPA_GESTACION']" dense outlined label="Condicion" clearable />
            </div>
            <div class="col-12 col-sm-4" v-if="consentimiento.condicion === 'ETAPA_GESTACION'">
              <q-input v-model="consentimiento.etapa_gestacion" dense outlined label="Etapa gestacion" />
            </div>
            <div class="col-12 col-sm-4">
              <q-select v-model="consentimiento.tipo" :options="['ACEPTA', 'RECHAZA']" dense outlined label="Tipo consentimiento" clearable />
            </div>

            <div class="col-12 col-sm-6">
              <q-input v-model="consentimiento.declarante_nombre" dense outlined label="Yo declarante" />
            </div>
            <div class="col-12 col-sm-6">
              <q-select v-model="consentimiento.declarante_condicion" :options="['Padre', 'Madre', 'Abuelo/a', 'Hijo/a', 'Propio', 'Otros']" dense outlined label="En condicion de" clearable />
            </div>
            <div class="col-12" v-if="consentimiento.declarante_condicion === 'Otros'">
              <q-input v-model="consentimiento.declarante_condicion_otro" dense outlined label="Otra condicion" />
            </div>

            <div class="col-12"><q-separator class="q-my-sm" /></div>
            <div class="col-12"><div class="text-subtitle2 q-mb-xs">Tipo de muestra</div></div>

            <div class="col-12 col-sm-6 col-md-3">
              <q-checkbox v-model="consentimiento.m_liquidos" :true-value="1" :false-value="0" dense label="Liquidos" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
              <q-checkbox v-model="consentimiento.m_esputo" :true-value="1" :false-value="0" dense label="Esputo" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
              <q-checkbox v-model="consentimiento.m_secreciones" :true-value="1" :false-value="0" dense label="Secreciones" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
              <q-checkbox v-model="consentimiento.m_orina" :true-value="1" :false-value="0" dense label="Orina" />
            </div>
<!--            <div class="col-12 col-sm-6 col-md-3">-->
<!--              <q-input v-model="consentimiento.hr_recoleccion_orina" type="text" dense outlined label="HR recoleccion" maxlength="10" />-->
<!--            </div>-->
            <div class="col-12 col-sm-6 col-md-3">
              <q-checkbox v-model="consentimiento.m_heces" :true-value="1" :false-value="0" dense label="HR recoleccion heces" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
              <q-input v-model="consentimiento.hr_recoleccion_heces" type="text" dense outlined label="HR recoleccion" maxlength="10" />
            </div>

            <div class="col-12">
              <q-input v-model="consentimiento.observaciones" type="textarea" autogrow dense outlined label="Observaciones" />
            </div>
          </div>
          <div class="text-right q-mt-md">
            <q-btn flat label="Omitir" @click="omitirConsentimiento" :disable="consentimientoLoading" />
            <q-btn color="primary" label="Guardar consentimiento" class="q-ml-sm" type="submit" :loading="consentimientoLoading" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
  <q-dialog v-model="dialogDoctorNew">
    <q-card style="min-width: 400px; max-width: 600px;">
      <q-card-section class="row items-center">
        <div class="text-h6">
          Nuevo doctor
        </div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-separator />

      <q-card-section>
        <q-form @submit.prevent="guardarDoctor">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-input
                v-model="doctor.nombre"
                label="Nombre"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="doctor.especialidad"
                label="Especialidad"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="doctor.ci"
                label="CI"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="doctor.telefono"
                label="Teléfono"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="doctor.email"
                label="Email"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-input
                v-model="doctor.registro"
                label="Registro profesional"
                dense outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="doctor.establecimiento_id"
                :options="establecimientos"
                option-label="nombre"
                option-value="id"
                emit-value
                map-options
                label="Establecimiento de salud"
                dense outlined
              />
              <!--                <pre>{{establecimientos}}</pre>-->
            </div>
<!--            <div class="col-12 col-sm-6">-->
<!--              <q-select-->
<!--                v-model="doctor.estado"-->
<!--                :options="['ACTIVO', 'INACTIVO']"-->
<!--                label="Estado"-->
<!--                dense outlined-->
<!--                clearable-->
<!--              />-->
<!--            </div>-->
          </div>

          <div class="text-right q-mt-md">
            <q-btn flat label="Cancelar" v-close-popup :loading="loading" />
            <q-btn
              color="primary"
              label="Guardar"
              type="submit"
              class="q-ml-sm"
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
  <q-dialog v-model="dialogEstablecimientoNew">
    <q-card style="min-width: 420px; max-width: 620px;">
      <q-card-section class="row items-center">
        <div class="text-h6">Nuevo establecimiento</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-separator />
      <q-card-section>
        <q-form @submit.prevent="guardarEstablecimientoRapido">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-input v-model="establecimientoNew.nombre" label="Nombre" dense outlined />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="establecimientoNew.tipo"
                :options="['PUBLICO', 'PRIVADO']"
                label="Tipo"
                dense
                outlined
              />
            </div>
            <div class="col-12 col-sm-6">
              <q-select
                v-model="establecimientoNew.nivel"
                :options="['NIVEL I', 'NIVEL II', 'NIVEL III']"
                label="Nivel"
                dense
                outlined
              />
            </div>
            <div class="col-12">
              <q-input v-model="establecimientoNew.direccion" label="Dirección" dense outlined />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="establecimientoNew.telefono_contacto" label="Teléfono" dense outlined />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="establecimientoNew.inicial" label="Inicial" dense outlined />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="establecimientoNew.responsable_laboratorio" label="Responsable" dense outlined />
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="establecimientoNew.telefono_responsable" label="Tel. responsable" dense outlined />
            </div>
          </div>

          <div class="text-right q-mt-md">
            <q-btn flat label="Cancelar" v-close-popup :disable="savingEstablecimiento" />
            <q-btn color="primary" label="Guardar" type="submit" class="q-ml-sm" :loading="savingEstablecimiento" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
  <q-dialog v-model="dialogUnidadSolicitante">
    <q-card style="min-width: 760px; max-width: 960px; width: 92vw;">
      <q-card-section class="row items-center">
        <div class="text-h6">Unidades solicitantes</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>
      <q-separator />
      <q-card-section>
        <div class="row q-col-gutter-sm q-mb-md">
          <div class="col-12 col-md-5">
            <q-input
              v-model="unidadSolicitanteForm.nombre"
              label="Nombre de la unidad solicitante"
              dense
              outlined
              autofocus
              @update:model-value="autoAbreviatura"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="unidadSolicitanteForm.abreviatura"
              label="Abreviatura"
              dense
              outlined
              hint="Única, p.ej. CP"
              :rules="[v => !!v || 'Requerida', v => v.length <= 20 || 'Máx 20 chars']"
            >
              <template #append>
                <q-btn flat dense round icon="auto_fix_high" size="xs" color="primary"
                  @click="unidadSolicitanteForm.abreviatura = generarAbreviatura(unidadSolicitanteForm.nombre)"
                >
                  <q-tooltip>Regenerar</q-tooltip>
                </q-btn>
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-4 row items-center justify-end q-gutter-sm">
            <q-btn
              flat
              no-caps
              label="Limpiar"
              @click="resetUnidadSolicitanteForm"
              :disable="unidadSolicitanteLoading"
            />
            <q-btn
              color="primary"
              no-caps
              :label="unidadSolicitanteEditando ? 'Actualizar' : 'Crear'"
              @click="guardarUnidadSolicitante"
              :loading="unidadSolicitanteLoading"
            />
          </div>
          <div class="col-12">
            <q-input
              v-model="unidadSolicitanteSearch"
              label="Buscar unidad solicitante"
              dense
              outlined
              debounce="250"
            >
              <template #append>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>

        <q-table
          :rows="unidadesSolicitantesFiltradas"
          :columns="unidadSolicitanteColumns"
          row-key="id"
          flat
          bordered
          dense
          :loading="unidadSolicitanteLoading"
          :rows-per-page-options="[10, 20, 50, 0]"
        >
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn-dropdown
                color="primary"
                label="Acciones"
                dense no-caps
                size="sm"
                dropdown-icon="expand_more"
              >
                <q-list dense>
                  <q-item clickable v-close-popup @click="editarUnidadSolicitante(props.row)">
                    <q-item-section avatar><q-icon name="edit" color="primary" size="sm" /></q-item-section>
                    <q-item-section>Editar</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="eliminarUnidadSolicitante(props.row)">
                    <q-item-section avatar><q-icon name="delete" color="negative" size="sm" /></q-item-section>
                    <q-item-section class="text-negative">Eliminar</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script>
import moment from 'moment'

export default {
  name: 'SolicitudForm',
  props: {
    solicitudProp: {
      type: Object,
      default: null
    }
  },
  data () {
    return {
      extras: {},
      moment: moment,
      dialogDoctorNew: false,
      dialogEstablecimientoNew: false,
      dialogUnidadSolicitante: false,
      savingEstablecimiento: false,
      unidadSolicitanteLoading: false,
      unidadSolicitanteEditando: false,
      unidadSolicitanteSearch: '',
      unidadSolicitanteColumns: [
        { name: 'actions',      label: 'Acciones',     align: 'center' },
        { name: 'abreviatura',  label: 'Abrev.',       field: 'abreviatura', align: 'left' },
        { name: 'nombre',       label: 'Nombre',       field: 'nombre',      align: 'left' }
      ],
      unidadSolicitanteForm: {
        id: null,
        nombre: '',
        abreviatura: ''
      },
      doctor: {
        estado: 'ACTIVO'
      },
      establecimientoNew: {
        nombre: '',
        tipo: 'PUBLICO',
        nivel: 'NIVEL I',
        direccion: '',
        telefono_contacto: '',
        inicial: '',
        responsable_laboratorio: '',
        telefono_responsable: '',
        estado: 'ACTIVO'
      },
      loading: false,
      solicitud: {},
      unidadesSolicitantes: [],
      unidadesSolicitantesAll: [],
      doctoresOptions: [],
      doctoresOptionsAll: [],
      codigosSugeridos: {
        SI: null,
        NO: null
      },
      codigoEditadoManual: false,
      nroRegistroEditadoManual: false,
      areas: [],
      establecimientos: [],
      establecimientosPublicos: [],
      establecimientosPublicosAll: [],
      establecimientosPrivados: [],
      establecimientosPrivadosAll: [],
      establecimientosAll: [],
      searchCi: '',
      serviciosFilter: '',
      serviciosAreaId: null,
      diagnosticos: [],
      diagnosticosAll: [],
      // ── Gestión de diagnósticos (admin) ──
      dialogDiagnosticos: false,
      loadingDiag: false,
      loadingDiagTable: false,
      diagEditando: null,
      diagForm: { cie10: '', especialidad: '', servicio: '' },
      diagSearch: '',
      diagRows: [],
      diagPagination: { page: 1, rowsPerPage: 15, rowsNumber: 0 },
      columnsDiag: [
        { name: 'acciones',    label: '',              field: 'id',           align: 'center', style: 'width:72px' },
        { name: 'cie10',       label: 'CIE-10 / Descripción', field: 'cie10',     align: 'left' },
        { name: 'especialidad',label: 'Especialidad',  field: 'especialidad', align: 'left' },
        { name: 'servicio',    label: 'Servicio',      field: 'servicio',     align: 'left' },
      ],
      loadingPacientes: false,
      pacientesOptions: [],
      consentimientoDialog: false,
      consentimientoLoading: false,
      solicitudCreadaId: null,
      consentimiento: {
        id: null,
        solicitude_id: null,
        paciente_id: null,
        nombre_completo: '',
        ci: '',
        fecha_recepcion: '',
        hora_recepcion: '',
        fecha_solicitud: '',
        fecha_consentimiento: '',
        medicamento: 0,
        tratamiento: '',
        condicion: '',
        etapa_gestacion: '',
        tipo: '',
        declarante_nombre: '',
        declarante_condicion: '',
        declarante_condicion_otro: '',
        m_orina: 0,
        hr_recoleccion_orina: '',
        m_liquidos: 0,
        m_esputo: 0,
        m_secreciones: 0,
        m_heces: 0,
        hr_recoleccion_heces: '',
        observaciones: ''
      }
    }
  },
  computed: {
    isAdmin () {
      return this.$store?.user?.role === 'Administrador'
    },
    edadadmY () {
      if (!this.solicitud.paciente_fecha_nac) return ''

      const nacimiento = moment(this.solicitud.paciente_fecha_nac, 'YYYY-MM-DD')
      if (!nacimiento.isValid()) return ''

      const hoy = moment()

      const years = hoy.diff(nacimiento, 'years')
      nacimiento.add(years, 'years')

      const months = hoy.diff(nacimiento, 'months')
      nacimiento.add(months, 'months')

      const days = hoy.diff(nacimiento, 'days')

      return `${days}d ${months}m ${years}A`
    },
    selectedServicios() {
      const selected = []
      this.areas.forEach(area => {
        (area.servicios || []).forEach(servicio => {
          if (servicio.seleccionado) {
            selected.push({
              area: area.name,
              servicio: servicio.nombre,
              precio: servicio.precio
            })
          }
        })
      })
      return selected
    },
    currentEstablecimiento () {
      if (!this.solicitud.establecimiento_salud) return null
      return this.establecimientos.find(e => e.nombre === this.solicitud.establecimiento_salud) || null
    },
    establecimientoServiciosBase () {
      return this.establecimientos.find(e => e.id === 1) || null
    },
    totalServiciosSeleccionados () {
      let total = 0
      this.areas.forEach(a => (a.servicios || []).forEach(s => { if (s.seleccionado) total++ }))
      return total
    },
    unidadesSolicitantesFiltradas () {
      const text = String(this.unidadSolicitanteSearch || '').trim().toLowerCase()

      if (!text) return this.unidadesSolicitantesAll

      return this.unidadesSolicitantesAll.filter(item => {
        const nombre = String(item.nombre || '').toLowerCase()
        return nombre.includes(text) || String(item.id).includes(text)
      })
    }
  },
  mounted () {
    this.cargarCatalogosSolicitud()
  },
  methods: {
    cargarCatalogosSolicitud () {
      this.loading = true
      this.$axios.get('solicitudes-create-catalogos')
        .then(res => {
          this.aplicarCatalogosSolicitud(res.data)
          this.inicializarSolicitudDesdeCatalogos()
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'No se pudieron cargar los catálogos')
        })
        .finally(() => {
          this.loading = false
        })
    },
    aplicarCatalogosSolicitud (data) {
      this.doctoresOptions = data.doctores || []
      this.doctoresOptionsAll = data.doctores || []
      this.areas = data.areas || []
      this.codigosSugeridos = data.codigos_sugeridos || { SI: null, NO: null }

      const establecimientos = data.establecimientos || []
      this.establecimientos = establecimientos
      this.establecimientosAll = establecimientos
      this.establecimientosPublicos = establecimientos.filter(e => e.tipo === 'PUBLICO')
      this.establecimientosPublicosAll = establecimientos.filter(e => e.tipo === 'PUBLICO')
      this.establecimientosPrivados = establecimientos.filter(e => e.tipo === 'PRIVADO')
      this.establecimientosPrivadosAll = establecimientos.filter(e => e.tipo === 'PRIVADO')

      const unidadesSolicitantes = data.unidades_solicitantes || []
      this.unidadesSolicitantes = unidadesSolicitantes
      this.unidadesSolicitantesAll = unidadesSolicitantes
    },
    inicializarSolicitudDesdeCatalogos () {
      console.log('SolicitudForm mounted with solicitudProp:', this.solicitudProp)
      if (this.solicitudProp) {
        this.solicitud = { ...this.solicitudProp }
        this.sincronizarUnidadSolicitanteSeleccionada()
        this.codigoEditadoManual = true
        this.nroRegistroEditadoManual = true
        this.areas.forEach(area => {
          (area.servicios || []).forEach(s => {
            const found = this.solicitud.servicios.find(ss => ss.id === s.id)
            s.seleccionado = found ? 1 : 0
          })
        })
        return
      }

      this.initSolicitud()
      this.aplicarCodigoSugerido()
      this.aplicarNroRegistroSugerido()
      this.sincronizarUnidadSolicitanteSeleccionada()
      this.resetServiciosSelection()
    },
    sincronizarUnidadSolicitanteSeleccionada () {
      if (this.solicitud.unidad_solicitante_id) {
        this.onSelectUnidadSolicitante(this.solicitud.unidad_solicitante_id)
        return
      }

      const sala = String(this.solicitud.sala || '').trim().toLowerCase()
      if (!sala) return

      const encontrada = this.unidadesSolicitantesAll.find(item =>
        String(item.abreviatura || '').trim().toLowerCase() === sala ||
        String(item.nombre || '').trim().toLowerCase() === sala
      )
      if (!encontrada) return

      this.solicitud.unidad_solicitante_id = encontrada.id
      this.solicitud.sala = encontrada.abreviatura || encontrada.nombre
    },
    filterUnidadesSolicitantes (val, update) {
      update(() => {
        const text = (val || '').toLowerCase().trim()

        if (!text) {
          this.unidadesSolicitantes = this.unidadesSolicitantesAll
          return
        }

        this.unidadesSolicitantes = this.unidadesSolicitantesAll
          .filter(item =>
            String(item.nombre || '').toLowerCase().includes(text) ||
            String(item.abreviatura || '').toLowerCase().includes(text)
          )
          .slice(0, 50)
      })
    },
    onSelectUnidadSolicitante (id) {
      if (!id) {
        this.solicitud.unidad_solicitante_id = null
        this.solicitud.sala = ''
        return
      }

      const unidad = this.unidadesSolicitantesAll.find(item => item.id === id)
      if (!unidad) return

      this.solicitud.unidad_solicitante_id = unidad.id
      this.solicitud.sala = unidad.abreviatura || unidad.nombre
    },
    abrirDialogUnidadSolicitante () {
      this.dialogUnidadSolicitante = true
      this.resetUnidadSolicitanteForm()
    },
    generarAbreviatura (nombre) {
      const palabras = (nombre || '').trim().split(/\s+/).filter(w => w.length > 0)
      if (palabras.length === 0) return ''
      if (palabras.length === 1) return palabras[0].substring(0, 4).toUpperCase()
      return palabras.map(w => w[0].toUpperCase()).join('')
    },
    autoAbreviatura (nombre) {
      if (!this.unidadSolicitanteEditando) {
        this.unidadSolicitanteForm.abreviatura = this.generarAbreviatura(nombre)
      }
    },
    resetUnidadSolicitanteForm () {
      this.unidadSolicitanteEditando = false
      this.unidadSolicitanteForm = {
        id: null,
        nombre: '',
        abreviatura: ''
      }
    },
    editarUnidadSolicitante (row) {
      this.unidadSolicitanteEditando = true
      this.unidadSolicitanteForm = {
        id: row.id,
        nombre: row.nombre,
        abreviatura: row.abreviatura || ''
      }
    },
    guardarUnidadSolicitante () {
      const nombre      = String(this.unidadSolicitanteForm.nombre || '').trim()
      const abreviatura = String(this.unidadSolicitanteForm.abreviatura || '').trim().toUpperCase()

      if (!nombre) {
        this.$alert?.error ? this.$alert.error('Ingrese el nombre de la unidad solicitante') : null
        return
      }
      if (!abreviatura) {
        this.$alert?.error ? this.$alert.error('Ingrese la abreviatura de la unidad solicitante') : null
        return
      }

      this.unidadSolicitanteLoading = true
      const payload = { nombre, abreviatura }

      const request = this.unidadSolicitanteEditando
        ? this.$axios.put(`unidad-solicitantes/${this.unidadSolicitanteForm.id}`, payload)
        : this.$axios.post('unidad-solicitantes', payload)

      request
        .then(res => {
          const item = res.data
          const index = this.unidadesSolicitantesAll.findIndex(x => x.id === item.id)

          if (index >= 0) {
            this.unidadesSolicitantesAll.splice(index, 1, item)
          } else {
            this.unidadesSolicitantesAll.unshift(item)
          }

          this.unidadesSolicitantesAll = [...this.unidadesSolicitantesAll].sort((a, b) => a.nombre.localeCompare(b.nombre))
          this.unidadesSolicitantes = this.unidadesSolicitantesAll

          if (this.solicitud.unidad_solicitante_id === item.id || !this.solicitud.unidad_solicitante_id) {
            this.onSelectUnidadSolicitante(item.id)
          }

          this.resetUnidadSolicitanteForm()
          this.$alert?.success ? this.$alert.success('Unidad solicitante guardada') : null
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert?.error ? this.$alert.error('Error al guardar unidad solicitante: ' + msg) : null
        })
        .finally(() => {
          this.unidadSolicitanteLoading = false
        })
    },
    eliminarUnidadSolicitante (row) {
      this.$q.dialog({
        title: 'Confirmar',
        message: `¿Eliminar la unidad solicitante "${row.nombre}"?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.unidadSolicitanteLoading = true
        this.$axios.delete(`unidad-solicitantes/${row.id}`)
          .then(() => {
            this.unidadesSolicitantesAll = this.unidadesSolicitantesAll.filter(item => item.id !== row.id)
            this.unidadesSolicitantes = this.unidadesSolicitantesAll

            if (this.solicitud.unidad_solicitante_id === row.id) {
              this.onSelectUnidadSolicitante(null)
            }

            if (this.unidadSolicitanteForm.id === row.id) {
              this.resetUnidadSolicitanteForm()
            }

            this.$alert?.success ? this.$alert.success('Unidad solicitante eliminada') : null
          })
          .catch(e => {
            const msg = e.response?.data?.message || e.message
            this.$alert?.error ? this.$alert.error('Error al eliminar unidad solicitante: ' + msg) : null
          })
          .finally(() => {
            this.unidadSolicitanteLoading = false
          })
      })
    },
    onCodigoManualChange () {
      this.codigoEditadoManual = true
    },
    onNroRegistroManualChange () {
      this.nroRegistroEditadoManual = true
    },
    aplicarCodigoSugerido () {
      if (this.codigoEditadoManual || this.solicitud.id) return
      const sugerido = this.codigosSugeridos[this.solicitud.tipo_atencion]
      this.solicitud.codigo = sugerido ?? ''
    },
    aplicarNroRegistroSugerido () {
      if (this.nroRegistroEditadoManual || this.solicitud.id) return
      this.solicitud.nro_registro = this.generarNroRegistroTemporal()
    },
    generarNroRegistroTemporal () {
      const nombreCompleto = String(this.solicitud.paciente_nombre || '').trim().toUpperCase()
      const fechaNac = this.solicitud.paciente_fecha_nac

      if (!nombreCompleto || !fechaNac) return ''

      const partes = nombreCompleto.split(/\s+/)
      let nombre = partes[0]
      let apPat = partes[0]
      let apMat = partes[0]

      if (partes.length >= 3) {
        nombre = partes[0]
        apPat = partes[partes.length - 2]
        apMat = partes[partes.length - 1]
      } else if (partes.length === 2) {
        nombre = partes[0]
        apPat = partes[1]
        apMat = partes[1]
      }

      const fecha = moment(fechaNac, 'YYYY-MM-DD', true)
      if (!fecha.isValid()) return `${nombre[0] || ''}${apPat[0] || ''}${apMat[0] || ''}`

      return `${nombre[0] || ''}${apPat[0] || ''}${apMat[0] || ''}${fecha.format('DDMMYY')}`
    },
    toUpperText (value) {
      if (value === null || value === undefined) return value
      return String(value).toLocaleUpperCase('es')
    },
    setUpperSolicitudField (field, value) {
      this.solicitud[field] = this.toUpperText(value)
      if (field === 'paciente_nombre') this.aplicarNroRegistroSugerido()
    },
    normalizePacienteUpperFields () {
      this.solicitud.paciente_nombre = this.toUpperText(this.solicitud.paciente_nombre || '')
      this.solicitud.paciente_direccion = this.toUpperText(this.solicitud.paciente_direccion || '')
      this.solicitud.paciente_telefono = this.toUpperText(this.solicitud.paciente_telefono || '')
      this.solicitud.paciente_genero = this.toUpperText(this.solicitud.paciente_genero || '')
      this.solicitud.paciente_discapacidad_cual = this.toUpperText(this.solicitud.paciente_discapacidad_cual || '')
      this.solicitud.paciente_discapacidad_otro = this.toUpperText(this.solicitud.paciente_discapacidad_otro || '')
    },
    onServicioToggle (servicio, value) {
      if (value !== 1) return
      const nombre = (servicio.nombre || '').toUpperCase().trim()

      // UREA → auto-seleccionar NITRÓGENO UREICO SÉRICO (NUS)
      if (nombre === 'UREA') {
        this.areas.forEach(area => {
          (area.servicios || []).forEach(s => {
            const n = (s.nombre || '').toUpperCase()
            if (n.includes('NITROGENO UREICO') || n.includes('NUS')) {
              s.seleccionado = 1
            }
          })
        })
      }
    },

    selecinarCodigo(codigos){
      // this.areas.forEach(area => {
      //   (area.servicios || []).forEach(s => {
      //     if(s.codigo === codigo){
      //       // s.seleccionado = 1 si esta mascao desmaracar
      //       s.seleccionado = s.seleccionado === 1 ? 0 : 1
      //     }
      //   })
      // })
      for (const codigo of codigos) {
        this.areas.forEach(area => {
          (area.servicios || []).forEach(s => {
            if (s.codigo === codigo) {
              s.seleccionado = s.seleccionado === 1 ? 0 : 1
            }
          })
        })
      }
    },
    consentimientoBaseDesdeSolicitud (s) {
      return {
        id: null,
        solicitude_id: s?.id || null,
        paciente_id: s?.paciente_id || null,
        nombre_completo: s?.paciente_nombre || '',
        ci: s?.paciente_ci || '',
        fecha_recepcion: moment().format('YYYY-MM-DD'),
        hora_recepcion: moment().format('HH:mm'),
        fecha_solicitud: s?.fecha_solicitud || '',
        fecha_consentimiento: moment().format('YYYY-MM-DD'),
        medicamento: 0,
        tratamiento: '',
        condicion: '',
        etapa_gestacion: '',
        tipo: '',
        declarante_nombre: '',
        declarante_condicion: '',
        declarante_condicion_otro: '',
        m_orina: 0,
        hr_recoleccion_orina: '',
        m_liquidos: 0,
        m_esputo: 0,
        m_secreciones: 0,
        m_heces: 0,
        hr_recoleccion_heces: '',
        observaciones: ''
      }
    },
    abrirConsentimientoNuevaSolicitud (solicitudCreada) {
      this.solicitudCreadaId = solicitudCreada?.id || null
      this.consentimiento = this.consentimientoBaseDesdeSolicitud(solicitudCreada)
      this.consentimientoLoading = true
      this.consentimientoDialog = true
      this.$axios
        .get(`solicitudes/${this.solicitudCreadaId}/consentimiento`)
        .then(res => {
          this.consentimiento = { ...this.consentimiento, ...res.data }
          // si no tiene el nombre
          if (!this.consentimiento.nombre_completo) {
            this.consentimiento.nombre_completo = this.solicitud.paciente_nombre || ''
          }
        })
        .finally(() => { this.consentimientoLoading = false })
    },
    omitirConsentimiento () {
      this.consentimientoDialog = false
      this.$alert?.success ? this.$alert.success('Solicitud creada correctamente') : null
      this.resetFormParaNuevaSolicitud()
    },
    guardarConsentimientoNuevaSolicitud () {
      if (!this.solicitudCreadaId) return
      this.consentimientoLoading = true
      this.$axios
        .post(`solicitudes/${this.solicitudCreadaId}/consentimiento`, this.consentimiento)
        .then(() => {
          this.$alert?.success ? this.$alert.success('Solicitud y consentimiento guardados correctamente') : null
          this.consentimientoDialog = false
          // imprimrir
          // http://localhost:8000/api/solicitudes/472/consentimiento/print
          const url = `${this.$axios.defaults.baseURL}/solicitudes/${this.solicitudCreadaId}/consentimiento/print`
          window.open(url, '_blank')
          this.resetFormParaNuevaSolicitud()
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert?.error ? this.$alert.error('Error al guardar consentimiento: ' + msg) : null
        })
        .finally(() => { this.consentimientoLoading = false })
    },
    rnnnGet(tipo){
      this.loading = true
      this.$axios.get(`pacientes/nn-rn/${tipo}`).then(res => {
        this.solicitud.paciente_nombre = res.data
        this.nroRegistroEditadoManual = false
        this.aplicarNroRegistroSugerido()
        this.$alert?.success ? this.$alert.success(`${tipo} generado`) : null
      }).catch(e => {
        const msg = e.response?.data?.message || e.response?.data?.error || e.message
        this.$alert?.error ? this.$alert.error(msg) : null
      }).finally(() => {
        this.loading = false
      })
    },
    blockSearchSpecialChars (e) {
      if (/[\/\.\*\-]/.test(e.key)) e.preventDefault()
    },
    onPastePaciente (e) {
      const text = (e.clipboardData || window.clipboardData).getData('text')
      const clean = text.replace(/[\/\.\*\-]/g, '')
      this.buscarPacientesInput((this.solicitud.paciente_nombre || '') + clean)
    },
    buscarPacientesInput (val) {
      this.solicitud.paciente_nombre = (val || '').replace(/[\/\.\*\-]/g, '').toUpperCase()
      clearTimeout(this._pacienteTimer)
      if (!val || val.trim().length < 2) {
        this.pacientesOptions = []
        return
      }
      this._pacienteTimer = setTimeout(() => {
        this.loadingPacientes = true
        this.$axios.get('pacientes', { params: { search: val.trim(), per_page: 10 } })
          .then(res => { this.pacientesOptions = res.data.data || [] })
          .catch(() => { this.pacientesOptions = [] })
          .finally(() => { this.loadingPacientes = false })
      }, 350)
    },
    limpiarPaciente () {
      this.solicitud.paciente_id = null
      this.solicitud.paciente_nombre = ''
      this.pacientesOptions = []
    },
    openDialogEstablecimiento (tipo) {
      this.establecimientoNew = {
        nombre: this.solicitud.establecimiento_salud || '',
        tipo: tipo || (this.solicitud.tipo_atencion === 'SI' ? 'PUBLICO' : 'PRIVADO'),
        nivel: 'NIVEL I',
        direccion: '',
        telefono_contacto: '',
        inicial: '',
        responsable_laboratorio: '',
        telefono_responsable: '',
        estado: 'ACTIVO'
      }
      this.dialogEstablecimientoNew = true
    },
    guardarEstablecimientoRapido () {
      if (!this.establecimientoNew.nombre || !this.establecimientoNew.nombre.trim()) {
        this.$alert?.error ? this.$alert.error('Ingrese el nombre del establecimiento') : null
        return
      }

      this.savingEstablecimiento = true
      this.$axios.post('establecimientos', this.establecimientoNew)
        .then(res => {
          const est = res.data
          this.establecimientos.unshift(est)
          this.establecimientosAll.unshift(est)
          if (est.tipo === 'PUBLICO') {
            this.establecimientosPublicos.unshift(est)
            this.establecimientosPublicosAll.unshift(est)
          } else {
            this.establecimientosPrivados.unshift(est)
            this.establecimientosPrivadosAll.unshift(est)
          }
          this.solicitud.establecimiento_salud = est.nombre
          this.onEstablecimientoChange()
          this.dialogEstablecimientoNew = false
          this.$alert?.success ? this.$alert.success('Establecimiento agregado') : null
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert?.error ? this.$alert.error('Error al guardar establecimiento: ' + msg) : null
        })
        .finally(() => {
          this.savingEstablecimiento = false
        })
    },
    guardarDoctor() {
      this.loading = true
      this.$axios.post('doctores', this.doctor)
        .then(res => {
          this.$alert?.success ? this.$alert.success('Doctor guardado correctamente') : console.log('Doctor guardado correctamente')
          this.dialogDoctorNew = false
          this.loadDoctores()
          this.solicitud.doctor_id = res.data.id
          this.onSelectDoctor(res.data.id)
          // reset doctor form
          this.doctor = {
            estado: 'ACTIVO'
          }
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert?.error ? this.$alert.error('Error al guardar doctor: ' + msg) : console.error(msg)
        })
        .finally(() => { this.loading = false })
    },
    filterDoctores(val, update) {
      update(() => {
        const text = (val || '').toLowerCase().trim()

        if (!text) {
          this.doctoresOptions = this.doctoresOptionsAll
          return
        }

        this.doctoresOptions = this.doctoresOptionsAll
          .filter(d => {
            const nombre = String(d.nombre || '').toLowerCase()
            const esp = String(d.especialidad || '').toLowerCase()
            const ci = String(d.ci || '').toLowerCase()
            const tel = String(d.telefono || '').toLowerCase()
            return (
              nombre.includes(text) ||
              esp.includes(text) ||
              ci.includes(text) ||
              tel.includes(text)
            )
          })
          .slice(0, 50) // 🔥 limita resultados (performance)
      })
    },
    onFilterDiagnosticos (val, update, abort) {
      const text = (val || '').trim()
      if (text.length < 2) {
        update(() => { this.diagnosticos = [] })
        return
      }
      this.$axios.get('diagnosticos', { params: { search: text, per_page: 20 } })
        .then(res => {
          update(() => { this.diagnosticos = res.data.data || [] })
        })
        .catch(() => { abort() })
    },
    diagnosticosGet () {
      this.$axios.get('diagnosticos').then(res => {
        this.diagnosticos = res.data
        this.diagnosticosAll = res.data
      })
    },

    // ── Gestión de diagnósticos (admin) ──────────────────────────────────

    abrirGestionDiagnosticos () {
      this.dialogDiagnosticos = true
      this.nuevoDiagnostico()
      this.fetchDiagnosticosAdmin(1)
    },

    nuevoDiagnostico () {
      this.diagEditando = null
      this.diagForm = { cie10: '', especialidad: '', servicio: '' }
    },

    editarDiagnostico (row) {
      this.diagEditando = row.id
      this.diagForm = { cie10: row.cie10, especialidad: row.especialidad, servicio: row.servicio }
    },

    async fetchDiagnosticosAdmin (page) {
      this.loadingDiagTable = true
      try {
        const res = await this.$axios.get('diagnosticos', {
          params: { page, per_page: this.diagPagination.rowsPerPage, search: this.diagSearch || undefined }
        })
        const p = res.data
        this.diagRows = p.data
        this.diagPagination = { ...this.diagPagination, page: p.current_page, rowsPerPage: p.per_page, rowsNumber: p.total }
      } finally {
        this.loadingDiagTable = false
      }
    },

    onDiagRequest (props) {
      this.diagPagination.rowsPerPage = props.pagination.rowsPerPage
      this.fetchDiagnosticosAdmin(props.pagination.page)
    },

    async guardarDiagnostico () {
      this.loadingDiag = true
      try {
        if (this.diagEditando) {
          await this.$axios.put(`diagnosticos/${this.diagEditando}`, this.diagForm)
          this.$alert?.success('Diagnóstico actualizado')
        } else {
          await this.$axios.post('diagnosticos', this.diagForm)
          this.$alert?.success('Diagnóstico creado')
        }
        this.nuevoDiagnostico()
        await this.fetchDiagnosticosAdmin(this.diagPagination.page)
      } catch (e) {
        const msg = e.response?.data?.message || 'Error al guardar'
        this.$alert?.error(msg)
      } finally {
        this.loadingDiag = false
      }
    },

    eliminarDiagnostico (row) {
      this.$alert.dialog(`¿Eliminar "${row.cie10}"?`).onOk(async () => {
        try {
          await this.$axios.delete(`diagnosticos/${row.id}`)
          this.$alert?.success('Diagnóstico eliminado')
          if (this.diagEditando === row.id) this.nuevoDiagnostico()
          await this.fetchDiagnosticosAdmin(this.diagPagination.page)
        } catch (e) {
          this.$alert?.error('Error al eliminar')
        }
      })
    },
    resetFormParaNuevaSolicitud () {
      this.extras = {}
      this.diagnosticos = []
      this.solicitudCreadaId = null
      this.cargarCatalogosSolicitud()
    },

    initSolicitud () {
      this.solicitud = {
        paciente_id: null,
        doctor_id: null,
        unidad_solicitante_id: null,
        codigo_solicitud: '',
        codigo: '',
        nro_registro: '',
        tipo_atencion: 'SI',
        tipo_otro: '',
        fecha_solicitud: moment().format('YYYY-MM-DD'),
        hora_solicitud: moment().format('HH:mm'),
        establecimiento_salud: 'Hospital General',
        zona_establecimiento: '',
        diagnostico_clinico: '',
        estado: 'CREADO',

        paciente_nombre: '',
        paciente_ci: '',
        paciente_telefono: '',
        paciente_direccion: '',
        paciente_fecha_nac: new Date().toISOString().substring(0, 10),
        paciente_genero: '',
        paciente_edad: null,
        paciente_discapacidad: 0,
        paciente_discapacidad_cual: '',
        paciente_discapacidad_otro: '',
        paciente_embarazo: 0,
        paciente_fum: '',
        paciente_sem_gest: null,

        doctor_nombre: '',
        doctor_especialidad: '',
        doctor_ci: '',
        doctor_telefono: '',
        doctor_email: '',
        doctor_registro: '',

        tipo_paciente_externo: '',
        autorizado_por: '',
        sala: '',
        cama: '',
      }
      this.codigoEditadoManual = false
      this.nroRegistroEditadoManual = false
      this.searchCi = ''
      this.serviciosFilter = ''
      this.serviciosAreaId = null
    },

    onCalculateEdad () {
      if (!this.solicitud.paciente_fecha_nac) return
      const birthDate = moment(this.solicitud.paciente_fecha_nac, 'YYYY-MM-DD')
      if (!birthDate.isValid()) return
      this.solicitud.paciente_edad = moment().diff(birthDate, 'years')
      this.aplicarNroRegistroSugerido()
    },

    textCapitalize (str) {
      if (!str) return ''
      return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
    },

    resetServiciosSelection () {
      this.areas.forEach(area => (area.servicios || []).forEach(s => { s.seleccionado = 0 }))
    },

    loadDoctores () {
      this.$axios.get('doctores').then(res => {
        this.doctoresOptions = res.data
        this.doctoresOptionsAll = res.data
      })
    },

    onChangeCi (val) {
      this.searchCi = val
      const name = (this.solicitud.paciente_nombre || '').toUpperCase()
      if (name.includes('RN')) return
      this.buscarPacientePorCi()
    },

    buscarPacientePorCi () {
      if (!this.searchCi) return
      this.loading = true
      this.solicitud.paciente_id = null
      this.solicitud.paciente_nombre = ''
      this.solicitud.paciente_telefono = ''
      this.solicitud.paciente_direccion = ''
      this.solicitud.paciente_fecha_nac = ''
      this.solicitud.paciente_genero = ''
      this.solicitud.paciente_edad = null
      this.solicitud.paciente_discapacidad = 0
      this.solicitud.paciente_discapacidad_cual = ''
      this.solicitud.paciente_discapacidad_otro = ''
      this.solicitud.paciente_embarazo = 0
      this.solicitud.paciente_fum = ''
      this.solicitud.paciente_sem_gest = null
      this.$axios
        .get(`pacientes/buscar-ci/${this.searchCi}`)
        .then(res => { this.onSelectPaciente(res.data) })
        .catch(() => {
          // paciente no encontrado vaciar los demas campos
        })
        .finally(() => { this.loading = false })
    },

    onSelectPaciente (p) {
      if (!p) return
      this.solicitud.paciente_id = p.id
      this.solicitud.paciente_nombre = p.nombre_completo
      this.solicitud.paciente_ci = p.ci
      this.solicitud.paciente_telefono = p.telefono
      this.solicitud.paciente_direccion = p.direccion
      this.solicitud.paciente_fecha_nac = p.fecha_nac
      this.solicitud.paciente_genero = p.genero
      this.solicitud.paciente_edad = p.edad
      this.solicitud.paciente_discapacidad = p.discapacidad ?? 0
      this.solicitud.paciente_discapacidad_cual = p.discapacidad_cual || ''
      this.solicitud.paciente_discapacidad_otro = p.discapacidad_otro || ''
      this.solicitud.paciente_embarazo = p.embarazo ?? 0
      this.solicitud.paciente_fum = p.fum || ''
      this.solicitud.paciente_sem_gest = p.sem_gest
      this.nroRegistroEditadoManual = false
      this.aplicarNroRegistroSugerido()
    },

    onSelectDoctor (id) {
      const d = this.doctoresOptions.find(x => x.id === id)
      if (!d) return
      this.solicitud.doctor_id = d.id
      this.solicitud.doctor_nombre = d.nombre
      this.solicitud.doctor_especialidad = d.especialidad
      this.solicitud.doctor_ci = d.ci
      this.solicitud.doctor_telefono = d.telefono
      this.solicitud.doctor_email = d.email
      this.solicitud.doctor_registro = d.registro
      if (d.establecimiento?.nombre) this.solicitud.establecimiento_salud = d.establecimiento.nombre
    },

    onTipoAtencionChange () {
      // this.resetServiciosSelection()
      if (this.solicitud.tipo_atencion === 'NO') this.solicitud.establecimiento_salud = ''
      else this.solicitud.tipo_otro = ''
      this.aplicarCodigoSugerido()
    },

    onEstablecimientoChange () {
      // Las prestaciones ya no dependen del establecimiento seleccionado.
    },

    filteredServicios (area) {
      let servicios = area.servicios || []

      if (this.serviciosAreaId && area.id !== this.serviciosAreaId) return []

      if (this.solicitud.tipo_atencion === 'SI') {
        const est = this.establecimientoServiciosBase
        if (est && Array.isArray(est.servicio_ids) && est.servicio_ids.length) {
          const allowed = new Set(est.servicio_ids)
          servicios = servicios.filter(s => allowed.has(s.id))
        }
      }

      const text = (this.serviciosFilter || '').toLowerCase().trim()
      if (!text) return servicios

      return servicios.filter(s => {
        const nombre = String(s.nombre ?? '').toLowerCase()
        const sub = String(s.subarea ?? '').toLowerCase()
        const codigo = String(s.codigo ?? '').toLowerCase()
        return nombre.includes(text) || sub.includes(text) || codigo.includes(text)
      })
    },

    guardar () {
      this.normalizePacienteUpperFields()
      // armar servicios
      this.solicitud.servicios = []
      this.areas.forEach(area => {
        (area.servicios || []).forEach(servicio => {
          if (servicio.seleccionado) {
            this.solicitud.servicios.push({
              id: servicio.id,
              nombre: servicio.nombre,
              precio: servicio.precio,
              area_id: area.id
            })
          }
        })
      })

      if (this.solicitud.servicios.length === 0) {
        this.$alert?.error ? this.$alert.error('Seleccione al menos un servicio') : alert('Seleccione al menos un servicio')
        return
      }
      // if (!this.solicitud.paciente_ci) {
      //   this.$alert?.error ? this.$alert.error('Coloque la CI del paciente') : alert('Coloque la CI del paciente')
      //   return
      // }


      if (this.solicitud.id) {
        if (this.solicitud.codigo == null){
          this.actulizarSolicitud()
        }else{
          this.$q.dialog({
            title: 'Confirmar',
            message: 'La solicitud ya tiene un código asignado, ¿desea actualizar la información?',
            cancel: true,
            persistent: true
          }).onOk(() => {
            this.actulizarSolicitud()
          })
        }
      }else{
        this.loading = true
        this.$axios
          .post('solicitudes', this.solicitud)
          .then((res) => {
            const solicitudCreada = res.data || {}
            this.$q.dialog({
              size: '400px',
              title: 'Solicitud guardada',
              message: '<span class="text-primary" style="font-size: 20px">¿Desea llenar el consentimiento ahora?</span>' +
                '<br><small>Puede llenarlo después desde la sección de solicitudes</small>',
              html: true,
              cancel: true,
              ok: {
                label: 'Sí, llenar ahora',
                color: 'primary'
              },
              cancel: {
                label: 'No'
              },
              persistent: true
            }).onOk(() => {
              this.abrirConsentimientoNuevaSolicitud(solicitudCreada)
            }).onCancel(() => {
              this.$alert?.success ? this.$alert.success('Solicitud guardada correctamente') : null
              this.resetFormParaNuevaSolicitud()
            })
          })
          .catch(e => {
            const msg = e.response?.data?.message || e.message
            this.$alert?.error ? this.$alert.error('Error al guardar: ' + msg) : console.error(msg)
          })
          .finally(() => { this.loading = false })
      }
    },
    actulizarSolicitud () {
      this.normalizePacienteUpperFields()
      this.loading = true
      this.$axios
        .put(`solicitudes/${this.solicitud.id}`, this.solicitud)
        .then(() => {
          this.$alert.success('Actualizado correctamente')
          this.$router.push({ path: '/solicitudes' })
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert.error('Error al actualizar: ' + msg)
        })
        .finally(() => { this.loading = false })
    }
  }
}
</script>
