<template>
  <q-page class="q-pa-sm lab-compacto column no-wrap">

    <!-- Cabecera -->
    <div class="row items-center q-mb-xs">
      <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/solicitudes-laboratorio">
        <q-tooltip>Volver</q-tooltip>
      </q-btn>
      <div>
        <div class="text-subtitle1 text-weight-bold">{{ editId ? 'Modificar laboratorio' : 'Crear laboratorio' }}</div>
      </div>
      <q-space />
      <q-btn v-if="!editId" dense outline no-caps color="primary" icon="receipt_long"
             label="Cargar venta pagada" class="q-mr-sm" @click="abrirVentasLaboratorio" />
      <q-btn v-if="editId" dense outline no-caps color="teal" icon="label"
             label="Imprimir rótulo" class="q-mr-sm" @click="imprimirRotulo" />
      <q-btn v-if="editId" dense outline no-caps color="indigo" icon="history"
             label="Auditoría" class="q-mr-sm" @click="abrirAuditoria" />
      <q-btn dense no-caps color="positive" icon="save" class="q-mr-sm"
             :label="editId ? 'Guardar e imprimir' : 'Crear e imprimir'"
             :loading="saving" :disable="!form.producto_ids.length || soloLectura" @click="guardar" />
      <div class="text-caption text-grey-7">
        {{ seleccionados.length }} prueba(s) · <b class="text-primary">Bs {{ money(total) }}</b>
      </div>
    </div>

    <q-banner v-if="soloLectura" dense rounded class="bg-orange-1 text-orange-10 q-mb-xs">
      <template #avatar><q-icon name="lock" color="orange-9" /></template>
      Esta solicitud incluye perfiles de exámenes y los perfiles están deshabilitados: se puede ver e imprimir,
      pero no modificar.
    </q-banner>

    <q-form ref="formRef" @submit.prevent="guardar" class="col column no-wrap">

      <!-- Datos de la solicitud: fila 1 paciente, doctor, fecha, hora y código; fila 2 diagnóstico y observaciones -->
      <q-card flat bordered class="q-pa-xs q-mb-xs">
        <div class="row q-col-gutter-xs">
          <div class="col-12 col-sm-6 col-md-4">
            <q-select v-model="form.paciente_id" :options="pacientesOpciones" option-value="id"
                      :option-label="pacienteLabel" emit-value map-options use-input input-debounce="200"
                      dense outlined label="Paciente *" :rules="[required]" hide-bottom-space
                      :loading="pacientesLoading"
                      @filter="filtrarPacientes" @virtual-scroll="cargarMasPacientes">
              <template #prepend><q-icon name="person" size="16px" /></template>
              <template #after>
                <q-btn flat round dense icon="person_add" color="primary" size="sm" @click="abrirPacienteNuevo">
                  <q-tooltip>Crear paciente nuevo</q-tooltip>
                </q-btn>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <q-select v-model="form.doctor_id" :options="doctores" option-value="id" option-label="nombre"
                      emit-value map-options clearable dense outlined label="Doctor solicitante"
                      use-input input-debounce="200" hide-bottom-space @filter="filtrarDoctores">
              <template #prepend><q-icon name="medical_information" size="16px" /></template>
              <template #no-option>
                <q-item clickable @click="abrirDoctorNuevo">
                  <q-item-section avatar><q-icon name="add" color="primary" /></q-item-section>
                  <q-item-section class="text-primary">Crear doctor nuevo</q-item-section>
                </q-item>
              </template>
              <template #after>
                <q-btn flat round dense icon="add" color="primary" size="sm" @click="abrirDoctorNuevo">
                  <q-tooltip>Agregar doctor que no existe</q-tooltip>
                </q-btn>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-sm-4 col-md-2">
            <q-input v-model="form.fecha_solicitud" type="date" dense outlined label="Fecha *"
                     :rules="[required]" hide-bottom-space />
          </div>
          <div class="col-12 col-sm-4 col-md-1">
            <q-input v-model="form.hora_solicitud" type="time" dense outlined label="Hora *"
                     :rules="[required]" hide-bottom-space />
          </div>
          <div class="col-12 col-sm-4 col-md-2">
            <q-input :model-value="form.codigo_laboratorio" @update:model-value="normalizarCodigoLaboratorio"
                     dense outlined :label="codigoObligatorio ? 'Código laboratorio *' : 'Código laboratorio'" maxlength="20"
                     :rules="codigoObligatorio ? [required, codigoLaboratorioValido] : [codigoLaboratorioOpcional]" hide-bottom-space />
          </div>
          <div class="col-12 col-md-6">
            <q-input v-model="form.diagnostico_clinico" v-uppercase dense outlined
                     label="Diagnóstico clínico" hide-bottom-space />
          </div>
          <div class="col-12 col-md-6">
            <q-input v-model="form.observaciones" v-uppercase dense outlined
                     label="Observaciones" hide-bottom-space />
          </div>
        </div>
      </q-card>

      <!-- Cuerpo: catálogo a la izquierda, resultados a la derecha (scroll independiente) -->
      <div class="row q-col-gutter-xs col lab-cuerpo">

        <!-- Catálogo de pruebas -->
        <div class="col-12 col-md-4 column no-wrap">
          <q-card flat bordered class="column no-wrap full-height">
            <div class="row items-center q-pa-xs q-gutter-xs bg-grey-1">
              <div class="text-caption text-weight-bold text-grey-8">
                PRUEBAS · {{ seleccionados.length }}/{{ laboratorios.length }}
              </div>
              <q-space />
              <q-input v-model="buscarLaboratorio" dense outlined clearable placeholder="Buscar…"
                       bg-color="white" style="width:150px" hide-bottom-space>
                <template #prepend><q-icon name="search" size="16px" /></template>
              </q-input>
            </div>
            <q-separator />
            <div class="col lab-scroll">
              <div v-if="!laboratoriosFiltrados.length" class="text-center text-grey-5 q-pa-md text-caption">
                Sin resultados
              </div>
              <div v-for="laboratorio in laboratoriosFiltrados" :key="laboratorio.id"
                   class="row items-center no-wrap lab-item cursor-pointer"
                   :class="{ 'bg-green-1': form.producto_ids.includes(laboratorio.id) || !!perfilQueIncluye(laboratorio.id), 'text-grey-5': !!laboratorio.motivo_no_vendible }"
                   @click="toggleLaboratorio(laboratorio.id)">
                <q-checkbox dense size="xs" :model-value="form.producto_ids.includes(laboratorio.id) || !!perfilQueIncluye(laboratorio.id)"
                            :disable="!!perfilQueIncluye(laboratorio.id)"
                            @click.stop @update:model-value="toggleLaboratorio(laboratorio.id)" />
                <div class="col q-ml-xs ellipsis">
                  <div class="text-caption text-weight-medium ellipsis">
                    {{ laboratorio.nombre }}
                    <q-badge v-if="laboratorio.es_perfil" color="deep-purple" class="q-ml-xs">PERFIL</q-badge>
                  </div>
                  <div class="lab-sub text-grey-6 ellipsis">
                    {{ laboratorio.codigo || 'S/C' }} ·
                    <template v-if="laboratorio.es_perfil">{{ laboratorio.examen_ids?.length || 0 }} exámenes</template>
                    <template v-else>{{ laboratorio.laboratorio_datos?.length || 0 }} datos</template>
                    <template v-if="perfilQueIncluye(laboratorio.id)"> · incluido en {{ perfilQueIncluye(laboratorio.id).nombre }}</template>
                  </div>
                  <div v-if="laboratorio.motivo_no_vendible" class="lab-sub text-negative ellipsis">{{ laboratorio.motivo_no_vendible }}</div>
                </div>
                <div class="lab-sub text-primary text-weight-bold q-ml-xs">{{ money(laboratorio.precio) }}</div>
              </div>
            </div>
          </q-card>
        </div>

        <!-- Datos a completar -->
        <div class="col-12 col-md-8 column no-wrap">
          <q-card flat bordered class="column no-wrap full-height">
            <div class="row items-center q-pa-xs bg-grey-1">
              <div class="text-caption text-weight-bold text-grey-8">DATOS DE LAS PRUEBAS SELECCIONADAS</div>
              <q-space />
              <q-btn v-if="renglones.length" flat dense no-caps size="sm" color="grey-7"
                     :icon="compacto ? 'unfold_more' : 'unfold_less'"
                     :label="compacto ? 'Expandir' : 'Compactar'" @click="compacto = !compacto" />
            </div>
            <q-separator />
            <div class="col lab-scroll q-pa-xs">
              <div v-if="!renglones.length" class="text-center text-grey-5 q-pa-md text-caption">
                Seleccione pruebas del listado de la izquierda
              </div>
              <template v-for="renglon in renglones" :key="renglon.clave">
              <!-- Cabecera de un perfil: se mueve y se quita entero; sus exámenes van debajo -->
              <div v-if="renglon.tipo === 'perfil'"
                   class="q-mb-none q-mt-xs prueba-ordenable"
                   :class="{ 'prueba-ordenable--arrastrando': laboratorioArrastrado === renglon.perfil.id }"
                   @dragover.prevent @drop="soltarLaboratorio(renglon.indice)">
                <div class="row items-center bg-deep-purple-1 q-px-xs q-py-none rounded-borders prueba-ordenable__cabecera"
                     draggable="true" @dragstart="iniciarArrastre(renglon.perfil.id, $event)" @dragend="finalizarArrastre">
                  <q-icon name="drag_indicator" size="18px" color="grey-7" class="q-mr-xs cursor-grab">
                    <q-tooltip>Arrastre para mover el perfil completo</q-tooltip>
                  </q-icon>
                  <q-icon name="playlist_add_check" size="14px" color="deep-purple" class="q-mr-xs" />
                  <span class="text-caption text-weight-bold">{{ renglon.perfil.nombre }}</span>
                  <q-badge color="deep-purple" class="q-ml-xs">PERFIL</q-badge>
                  <span class="lab-sub text-grey-7 q-ml-xs">{{ renglon.cantidad }} examen(es)</span>
                  <q-space />
                  <span class="lab-sub text-grey-7">Bs {{ money(renglon.perfil.precio) }}</span>
                  <q-btn flat dense round size="xs" icon="close" color="negative" class="q-ml-xs"
                         @click="toggleLaboratorio(renglon.perfil.id)">
                    <q-tooltip>Quitar el perfil completo</q-tooltip>
                  </q-btn>
                </div>
              </div>
              <div v-else class="q-mb-xs prueba-ordenable"
                   :class="{ 'prueba-ordenable--arrastrando': laboratorioArrastrado === renglon.laboratorio.id, 'q-ml-md': renglon.perfil }"
                   @dragover.prevent @drop="soltarLaboratorio(renglon.indice)">
                <div v-if="renglon.perfil" class="row items-center bg-grey-2 q-px-xs q-py-none rounded-borders">
                  <q-icon name="biotech" size="14px" color="deep-purple" class="q-mr-xs" />
                  <span class="text-caption text-weight-bold">{{ renglon.laboratorio.nombre }}</span>
                  <q-space />
                  <span class="lab-sub text-deep-purple">Incluido en {{ renglon.perfil.nombre }}</span>
                </div>
                <div v-else class="row items-center bg-blue-grey-1 q-px-xs q-py-none rounded-borders prueba-ordenable__cabecera"
                     draggable="true" @dragstart="iniciarArrastre(renglon.laboratorio.id, $event)" @dragend="finalizarArrastre">
                  <q-icon name="drag_indicator" size="18px" color="grey-7" class="q-mr-xs cursor-grab">
                    <q-tooltip>Arrastre para cambiar el orden de impresión</q-tooltip>
                  </q-icon>
                  <q-icon name="biotech" size="14px" color="primary" class="q-mr-xs" />
                  <span class="text-caption text-weight-bold">{{ renglon.laboratorio.nombre }}</span>
                  <q-space />
                  <span class="lab-sub text-grey-7">Bs {{ money(renglon.laboratorio.precio) }}</span>
                  <q-btn flat dense round size="xs" icon="close" color="negative" class="q-ml-xs"
                         @click="toggleLaboratorio(renglon.laboratorio.id)">
                    <q-tooltip>Quitar prueba</q-tooltip>
                  </q-btn>
                </div>
                <template v-for="laboratorio in [renglon.laboratorio]" :key="laboratorio.id">
                <div v-if="laboratorio.descripcion_html" class="q-pa-xs text-caption" v-html="laboratorio.descripcion_html" />
                <div class="row q-col-gutter-xs q-mt-none">
                  <div v-for="dato in laboratorio.laboratorio_datos" :key="dato.id"
                       :class="compacto ? 'col-6 col-md-3' : 'col-12 col-md-6'">
                    <div class="row items-center no-wrap q-gutter-xs">
                      <div class="col">
                    <!-- Dato con lista cerrada de valores -->
                    <q-select v-if="!dato.formula && dato.opciones?.length"
                              :model-value="valores[dato.id] || ''"
                              dense outlined clearable hide-bottom-space
                              :label="dato.nombre" :suffix="dato.unidad || undefined"
                              :options="dato.opciones.map(o => o.valor)"
                              @update:model-value="valor => valores[dato.id] = valor">
                      <q-tooltip v-if="dato.rango_referencia"><div>Referencia:</div><div class="html-rango" v-html="dato.rango_referencia_html" /></q-tooltip>
                    </q-select>
                    <q-input v-else-if="!dato.formula" :model-value="valores[dato.id] || ''"
                             dense outlined :label="dato.nombre" hide-bottom-space
                             :suffix="dato.unidad || undefined"
                             @update:model-value="valor => valores[dato.id] = valor">
                      <q-tooltip v-if="dato.rango_referencia"><div>Referencia:</div><div class="html-rango" v-html="dato.rango_referencia_html" /></q-tooltip>
                    </q-input>
                    <q-input v-else :model-value="calcularDato(laboratorio, dato)"
                             dense outlined readonly color="deep-purple" hide-bottom-space
                             :label="dato.nombre" :suffix="dato.unidad || undefined">
                      <template #prepend><q-icon name="functions" size="14px" color="deep-purple" /></template>
                      <q-tooltip>Fórmula: {{ dato.formula.formula }}</q-tooltip>
                    </q-input>
                      </div>
                      <q-checkbox v-model="visibles[dato.id]" dense size="sm" label="Visible" color="primary">
                        <q-tooltip>Mostrar este dato en la impresión</q-tooltip>
                      </q-checkbox>
                    </div>
                  </div>
                </div>
                </template>
              </div>
              </template>
            </div>
          </q-card>
        </div>
      </div>

      <!-- Barra de acciones fija -->
      <div class="row items-center q-gutter-sm q-mt-xs">
        <q-btn v-if="alertas.length" dense flat no-caps color="orange-9" icon="warning"
               :label="`${alertas.length} validación(es) sin cumplir`" @click="alertasDialog = true">
          <q-tooltip>Ver los mensajes de validación pendientes</q-tooltip>
        </q-btn>
        <q-space />
        <div class="text-subtitle1 text-weight-bold">Total: <span class="text-primary">Bs {{ money(total) }}</span></div>
        <q-btn flat dense label="Cancelar" no-caps color="grey-7" to="/solicitudes-laboratorio" />
        <q-btn v-if="editId" outline dense label="Imprimir rótulo" icon="label"
               no-caps color="teal" @click="imprimirRotulo" />
        <q-btn type="submit" color="positive" icon="save" dense padding="4px 14px"
               :label="editId ? 'Guardar e imprimir' : 'Crear e imprimir'"
               no-caps :loading="saving" :disable="!form.producto_ids.length || soloLectura" />
      </div>
    </q-form>

    <!-- DIALOG AUDITORÍA -->
    <q-dialog v-model="auditoriaDialog">
      <q-card class="column no-wrap" style="width:min(96vw,900px);max-width:900px;height:min(82vh,720px)">
        <q-card-section class="row items-center bg-indigo text-white q-py-sm">
          <q-icon name="history" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Historial de modificaciones</div>
            <div class="text-caption">Solicitud #{{ editId }} · cambios de datos, pruebas y resultados</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="auditoriaDialog = false" />
        </q-card-section>
        <q-card-section class="col scroll q-pa-sm">
          <div v-if="auditoriaLoading" class="text-center q-pa-xl">
            <q-spinner color="indigo" size="36px" />
          </div>
          <div v-else-if="!auditorias.length" class="text-center text-grey-6 q-pa-xl">
            No existen registros de auditoría para esta solicitud.
          </div>
          <q-list v-else bordered separator>
            <q-expansion-item v-for="auditoria in auditorias" :key="auditoria.id" dense expand-separator>
              <template #header>
                <q-item-section avatar>
                  <q-icon :name="iconoEventoAuditoria(auditoria.evento)"
                          :color="colorEventoAuditoria(auditoria.evento)" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ auditoria.entidad }}</q-item-label>
                  <q-item-label caption>{{ auditoria.usuario }} · {{ formatoFechaHora(auditoria.fecha) }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="colorEventoAuditoria(auditoria.evento)" outline>
                    {{ etiquetaEventoAuditoria(auditoria.evento) }}
                  </q-badge>
                </q-item-section>
              </template>
              <q-card>
                <q-card-section class="q-pa-sm">
                  <q-markup-table dense flat bordered separator="cell">
                    <thead><tr class="bg-grey-1"><th class="text-left">Campo</th><th class="text-left">Anterior</th><th class="text-left">Nuevo</th></tr></thead>
                    <tbody>
                      <tr v-for="cambio in auditoria.cambios" :key="cambio.campo">
                        <td class="text-weight-medium">{{ etiquetaCampoAuditoria(cambio.campo) }}</td>
                        <td class="valor-auditoria">{{ mostrarValorAuditoria(cambio.anterior) }}</td>
                        <td class="valor-auditoria">{{ mostrarValorAuditoria(cambio.nuevo) }}</td>
                      </tr>
                      <tr v-if="!auditoria.cambios.length"><td colspan="3" class="text-center text-grey-6">Sin valores detallados</td></tr>
                    </tbody>
                  </q-markup-table>
                </q-card-section>
              </q-card>
            </q-expansion-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG VENTAS PAGADAS DE LABORATORIO -->
    <q-dialog v-model="ventasDialog">
      <q-card class="column no-wrap" style="width:min(96vw,1050px);max-width:1050px;height:min(82vh,720px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="payments" size="22px" class="q-mr-sm" />
          <div>
            <div class="text-subtitle1 text-weight-bold">Ventas pagadas de laboratorio</div>
            <div class="text-caption">Seleccione una venta para cargar al paciente y sus análisis</div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="ventasDialog = false" />
        </q-card-section>
        <q-card-section class="col column no-wrap q-pa-sm">
          <q-table ref="ventasTable" class="col" dense flat bordered row-key="id"
                   :rows="ventasLaboratorio" :columns="ventasColumns" :loading="ventasLoading"
                   v-model:pagination="ventasPagination" :rows-per-page-options="[10, 15, 25, 50]"
                   @request="cargarVentasLaboratorio" no-data-label="No hay ventas pagadas de laboratorio">
            <template #top-right>
              <div class="row items-center q-gutter-sm">
                <q-input v-model="ventasFecha" dense outlined type="date" label="Día de pago"
                         @update:model-value="recargarVentas" />
                <q-input v-model="ventasBuscar" dense outlined clearable debounce="300"
                       placeholder="Paciente, CI, análisis o venta…" style="width:300px"
                       @update:model-value="recargarVentas">
                  <template #prepend><q-icon name="search" /></template>
                </q-input>
              </div>
            </template>
            <template #body-cell-pago="props">
              <q-td :props="props">
                <div>{{ formatoFechaHora(props.row.fecha_hora_cobro || props.row.fecha_hora) }}</div>
                <div class="text-caption text-grey-6">
                  {{ props.row.cobrado_por?.name || props.row.user?.name || '—' }}
                </div>
              </q-td>
            </template>
            <template #body-cell-laboratorios="props">
              <q-td :props="props">
                <q-chip v-for="detalle in props.row.detalles" :key="detalle.id" dense square
                        color="green-1" text-color="green-9" class="q-ma-xs">
                  {{ detalle.producto?.nombre || detalle.nombre }}
                </q-chip>
              </q-td>
            </template>
            <template #body-cell-accion="props">
              <q-td :props="props">
                <q-btn dense unelevated no-caps color="positive" icon="check_circle"
                       label="Seleccionar" @click="aplicarVentaLaboratorio(props.row)" />
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG AVISOS DE PERFILES (cambios de composición, venta cargada) -->
    <q-dialog v-model="avisos.abierto">
      <q-card style="width:min(96vw,620px)">
        <q-card-section class="row items-center bg-deep-purple text-white q-py-sm">
          <q-icon name="playlist_add_check" size="20px" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">{{ avisos.titulo }}</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-sm" style="max-height:60vh;overflow-y:auto">
          <q-list bordered separator dense>
            <q-item v-for="(aviso, idx) in avisos.mensajes" :key="idx">
              <q-item-section avatar><q-icon name="info" color="deep-purple" /></q-item-section>
              <q-item-section><q-item-label>{{ aviso }}</q-item-label></q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-sm">
          <template v-if="avisos.alConfirmar">
            <q-btn flat dense color="grey-7" label="Revisar" no-caps v-close-popup />
            <q-btn dense padding="4px 14px" color="deep-purple" no-caps label="Guardar con estos cambios"
                   icon-right="save" @click="confirmarAvisos" />
          </template>
          <q-btn v-else dense padding="4px 14px" color="deep-purple" no-caps label="Entendido" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG MENSAJES DE VALIDACIÓN -->
    <q-dialog v-model="alertasDialog">
      <q-card style="width:min(96vw,560px)">
        <q-card-section class="row items-center bg-orange-9 text-white q-py-sm">
          <q-icon name="warning" size="20px" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">Validaciones sin cumplir</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-sm" style="max-height:60vh;overflow-y:auto">
          <q-list bordered separator dense>
            <q-item v-for="(alerta, idx) in alertas" :key="idx">
              <q-item-section avatar><q-icon name="error_outline" color="orange-9" /></q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ alerta.mensaje }}</q-item-label>
                <q-item-label caption>
                  {{ alerta.laboratorio }} · <code>{{ alerta.expresion }}</code> = <b>{{ alerta.obtenido }}</b>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-sm">
          <q-btn flat dense color="grey-7" label="Corregir" no-caps v-close-popup />
          <q-btn v-if="confirmandoGuardado" dense padding="4px 14px" color="orange-9" no-caps
                 label="Guardar de todos modos" icon-right="save" @click="continuarGuardado" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG NUEVO PACIENTE -->
    <q-dialog v-model="pacienteDialog" persistent>
      <q-card style="width:min(96vw,460px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="person_add" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">Nuevo paciente</span>
          <q-space /><q-btn flat round dense icon="close" @click="pacienteDialog = false" />
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="guardarPaciente">
            <q-input v-model="pacienteNuevo.nombre_completo" label="Nombre completo *" dense outlined
                     class="q-mb-sm" :rules="[required]" v-uppercase autofocus />
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-input v-model="pacienteNuevo.ci" label="CI" dense outlined v-uppercase />
              </div>
              <div class="col-6">
                <q-select v-model="pacienteNuevo.sexo" label="Sexo" dense outlined clearable
                          :options="[{ label: 'Masculino', value: 'M' }, { label: 'Femenino', value: 'F' }]"
                          emit-value map-options />
              </div>
              <div class="col-6">
                <q-input v-model="pacienteNuevo.fecha_nacimiento" label="Fecha de nacimiento"
                         dense outlined type="date" clearable :max="hoyFecha" />
              </div>
              <div class="col-6">
                <q-input v-model="pacienteNuevo.telefono" label="Teléfono" dense outlined />
              </div>
            </div>
            <q-input v-model="pacienteNuevo.direccion" label="Dirección" dense outlined class="q-mb-md" v-uppercase />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="pacienteDialog = false" />
              <q-btn color="primary" label="Crear y seleccionar" icon-right="person_add"
                     type="submit" no-caps :loading="savingPaciente" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG NUEVO DOCTOR -->
    <q-dialog v-model="doctorDialog" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo doctor</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="guardarDoctor">
            <q-input v-model="doctorNuevo.nombre" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[required]" v-uppercase autofocus />
            <q-select v-model="doctorNuevo.especialidad_ids" label="Especialidades" dense outlined class="q-mb-md"
                      multiple use-chips :options="especialidades"
                      option-value="id" option-label="nombre" emit-value map-options />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="doctorDialog = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingDoctor" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'
import { imprimirRotuloSolicitudLaboratorio } from '../../../addons/solicitudLaboratorioRotuloPrint'
import { imprimirSolicitudLaboratorio } from '../../../addons/solicitudLaboratorioPrint'

const { proxy } = getCurrentInstance()
const pacientesOpciones = ref([])
const pacientesLoading = ref(false)
const pacientesPage = ref(1)
const pacientesLastPage = ref(1)
const pacienteSearch = ref('')
const doctores = ref([])
const doctoresTodos = ref([])
const especialidades = ref([])
const doctorDialog = ref(false)
const savingDoctor = ref(false)
const doctorNuevo = ref({ nombre: '', especialidad_ids: [] })
const pacienteDialog = ref(false)
const savingPaciente = ref(false)
const pacienteNuevo = ref({})
const laboratorios = ref([])
const buscarLaboratorio = ref('')
const compacto = ref(true)
const saving = ref(false)
const alertasDialog = ref(false)
const confirmandoGuardado = ref(false)
const valores = ref({})
const visibles = ref({})
const laboratorioArrastrado = ref(null)
const ventasDialog = ref(false)
const auditoriaDialog = ref(false)
const auditoriaLoading = ref(false)
const auditorias = ref([])
const ventasTable = ref(null)
const ventasLaboratorio = ref([])
const ventasLoading = ref(false)
const ventasBuscar = ref('')
const ventasPagination = ref({ page: 1, rowsPerPage: 15, rowsNumber: 0 })
const editId = ref(proxy.$route.query.id || null)
const now = new Date()
const hoyFecha = now.toISOString().slice(0, 10)
const ventasFecha = ref(hoyFecha)
const formRef = ref(null)
// Código laboratorio guardado al abrir la edición: sin él (solicitud antigua) el campo es opcional
const codigoLaboratorioGuardado = ref('')
const codigoObligatorio = computed(() => !editId.value || !!codigoLaboratorioGuardado.value)
const form = ref({
  paciente_id: null,
  doctor_id: null,
  codigo_laboratorio: '',
  fecha_solicitud: now.toISOString().slice(0, 10),
  hora_solicitud: now.toTimeString().slice(0, 5),
  diagnostico_clinico: '',
  observaciones: '',
  producto_ids: [],
})

const ventasColumns = [
  { name: 'id', label: 'Venta', align: 'left', field: row => `#${row.id}`, sortable: true },
  { name: 'paciente', label: 'Paciente', align: 'left', field: row => row.paciente?.nombre_completo || '—', sortable: true },
  { name: 'ci', label: 'CI', align: 'left', field: row => row.paciente?.ci || '—' },
  { name: 'pago', label: 'Pago / cobrado por', align: 'left' },
  { name: 'laboratorios', label: 'Análisis pagados', align: 'left' },
  { name: 'total', label: 'Total Bs', align: 'right', field: row => (row.detalles || []).reduce((sum, item) => sum + Number(item.total || 0), 0).toFixed(2) },
  { name: 'accion', label: '', align: 'right' },
]

const laboratoriosFiltrados = computed(() => {
  const search = buscarLaboratorio.value.toLowerCase().trim()
  return search
    ? laboratorios.value.filter(item => `${item.nombre} ${item.codigo || ''}`.toLowerCase().includes(search))
    : laboratorios.value
})
// ── Perfiles de exámenes ────────────────────────────────────────
// form.producto_ids es lo elegido en el catálogo, en orden: exámenes sueltos y
// perfiles. Cada perfil se reemplaza por sus exámenes; uno compartido por dos
// perfiles queda solo en el primero (el backend aplica las mismas reglas).
// Viene con form-data (no depende de que /me haya respondido).
const perfilesHabilitado = ref(false)
const porId = computed(() => new Map(laboratorios.value.map(item => [item.id, item])))
const solicitudPerfiles = ref([]) // perfiles guardados en la solicitud (edición)
const composicionGuardada = ref({}) // perfil_producto_id → ids de examen guardados
const resultadosGuardados = ref(new Set()) // ids de examen con algún resultado cargado
const soloLectura = computed(() => !!editId.value && !perfilesHabilitado.value && solicitudPerfiles.value.length > 0)
const avisos = ref({ abierto: false, titulo: '', mensajes: [], alConfirmar: null })
const totalGuardado = ref(0)
let composicionConfirmada = false

function esPerfil (id) {
  return porId.value.get(id)?.es_perfil === true
}
function examenesDePerfil (id) {
  return porId.value.get(id)?.examen_ids || []
}
/* Bloques en el orden elegido: cada perfil con sus exámenes, o un examen suelto. */
const bloques = computed(() => {
  const asignados = new Set()
  return form.value.producto_ids.map((id, indice) => {
    const item = porId.value.get(id)
    if (!item) return null
    if (!item.es_perfil) return { indice, perfil: null, examenes: [item] }
    const examenes = item.examen_ids
      .filter(examenId => !asignados.has(examenId))
      .map(examenId => { asignados.add(examenId); return porId.value.get(examenId) })
      .filter(Boolean)
    return { indice, perfil: item, examenes }
  }).filter(Boolean)
})
/* Exámenes que irán a la solicitud (sin repetir): sus datos se completan. */
const seleccionados = computed(() => bloques.value.flatMap(bloque => bloque.examenes))
/* Renglones del panel de la derecha: la cabecera de cada perfil y cada examen. */
const renglones = computed(() => bloques.value.flatMap(bloque => [
  ...(bloque.perfil ? [{ clave: `p${bloque.perfil.id}`, tipo: 'perfil', perfil: bloque.perfil, cantidad: bloque.examenes.length, indice: bloque.indice }] : []),
  ...bloque.examenes.map(laboratorio => ({ clave: `e${laboratorio.id}`, tipo: 'examen', laboratorio, perfil: bloque.perfil, indice: bloque.indice })),
]))
/* Perfil elegido que ya incluye este examen (null si ninguno). */
function perfilQueIncluye (examenId) {
  if (!perfilesHabilitado.value || esPerfil(examenId)) return null
  const perfilId = form.value.producto_ids.find(id => esPerfil(id) && examenesDePerfil(id).includes(examenId))
  return perfilId ? porId.value.get(perfilId) : null
}
// Los exámenes de un perfil no suman: el perfil se cobra por su precio. En solo
// lectura (perfiles apagados) se muestra el total guardado.
const total = computed(() => soloLectura.value ? totalGuardado.value : bloques.value.reduce((sum, bloque) => sum + Number(
  bloque.perfil ? bloque.perfil.precio : bloque.examenes[0].precio,
) || 0, 0))

/*
 * Agrega un perfil o un examen a la selección aplicando las reglas de duplicados.
 * Devuelve los avisos (texto) y si se agregó.
 */
function agregarSeleccion (id) {
  const item = porId.value.get(id)
  if (!item || form.value.producto_ids.includes(id)) return { agregado: false, avisos: [] }
  const mensajes = []
  if (item.es_perfil) {
    if (item.motivo_no_vendible) return { agregado: false, avisos: [item.motivo_no_vendible] }
    const incluidos = form.value.producto_ids.filter(otro => !esPerfil(otro) && item.examen_ids.includes(otro))
    incluidos.forEach(otro => {
      form.value.producto_ids.splice(form.value.producto_ids.indexOf(otro), 1)
      mensajes.push(`${porId.value.get(otro).nombre} ya está en ${item.nombre}: se quitó el examen suelto.`)
    })
    item.examen_ids.forEach(examenId => {
      const otroPerfil = perfilQueIncluye(examenId)
      if (otroPerfil) mensajes.push(`${porId.value.get(examenId)?.nombre} ya está en ${otroPerfil.nombre}: aparecerá una sola vez, en ${otroPerfil.nombre}.`)
    })
  } else {
    const perfil = perfilQueIncluye(id)
    if (perfil) return { agregado: false, avisos: [`${item.nombre} está incluido en ${perfil.nombre}: no se agrega suelto.`] }
  }
  form.value.producto_ids.push(id)
  precargarDefectos(id)
  precargarVisibilidad(id)
  return { agregado: true, avisos: mensajes }
}
function mostrarAvisos (titulo, mensajes, alConfirmar = null) {
  avisos.value = { abierto: true, titulo, mensajes, alConfirmar }
}
function confirmarAvisos () {
  const accion = avisos.value.alConfirmar
  avisos.value.abierto = false
  if (accion) accion()
}
/* Resultados cargados en los exámenes de un bloque de perfil (para confirmar antes de quitarlo). */
function resultadosCargadosDe (examenes) {
  return examenes.reduce((suma, examen) => suma + (examen.laboratorio_datos || [])
    .filter(dato => !dato.formula && valores.value[dato.id] && valores.value[dato.id] !== dato.valor_defecto).length, 0)
}

// Mensajes de validación configurados en cada laboratorio que no se cumplen
const alertas = computed(() => seleccionados.value.flatMap(laboratorio =>
  (laboratorio.laboratorio_validaciones || [])
    .filter(validacion => validacion.activo !== false)
    .map(validacion => evaluarValidacion(laboratorio, validacion))
    .filter(Boolean)
))

function abrirVentasLaboratorio () {
  ventasDialog.value = true
  ventasFecha.value = hoyFecha
  ventasPagination.value.page = 1
  cargarVentasLaboratorio({ pagination: ventasPagination.value })
}

async function cargarVentasLaboratorio ({ pagination }) {
  ventasLoading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/ventas-laboratorio', {
      params: {
        q: ventasBuscar.value || undefined,
        fecha: ventasFecha.value || undefined,
        page: pagination.page,
        per_page: pagination.rowsPerPage,
      },
    })
    ventasLaboratorio.value = data.data || []
    ventasPagination.value = {
      page: data.current_page,
      rowsPerPage: data.per_page,
      rowsNumber: data.total,
    }
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar las ventas de laboratorio')
  } finally {
    ventasLoading.value = false
  }
}

function recargarVentas () {
  ventasPagination.value.page = 1
  ventasTable.value?.requestServerInteraction()
}

function formatoFechaHora (value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
}

async function abrirAuditoria () {
  auditoriaDialog.value = true
  auditoriaLoading.value = true
  try {
    const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${editId.value}/auditoria`)
    auditorias.value = data || []
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar la auditoría')
  } finally {
    auditoriaLoading.value = false
  }
}

function etiquetaEventoAuditoria (evento) {
  return { created: 'Creado', updated: 'Modificado', deleted: 'Eliminado', restored: 'Restaurado' }[evento] || evento
}
function iconoEventoAuditoria (evento) {
  return { created: 'add_circle', updated: 'edit', deleted: 'delete', restored: 'restore' }[evento] || 'history'
}
function colorEventoAuditoria (evento) {
  return { created: 'positive', updated: 'orange-8', deleted: 'negative', restored: 'teal' }[evento] || 'grey-7'
}
function etiquetaCampoAuditoria (campo) {
  const etiquetas = {
    valor: 'Resultado', visible: 'Visible en impresión', orden: 'Orden', producto_nombre: 'Prueba',
    precio: 'Precio', estado: 'Estado', paciente_id: 'Paciente', doctor_id: 'Doctor',
    fecha_solicitud: 'Fecha', hora_solicitud: 'Hora', diagnostico_clinico: 'Diagnóstico clínico',
    observaciones: 'Observaciones', codigo_solicitud: 'Código de solicitud', total: 'Total',
    codigo_laboratorio: 'Código laboratorio',
    nombre: 'Dato', unidad: 'Unidad', metodo: 'Método', muestra: 'Muestra', rango_referencia: 'Referencia',
  }
  return etiquetas[campo] || campo.replaceAll('_', ' ')
}
function mostrarValorAuditoria (valor) {
  if (valor === null || valor === undefined || valor === '') return '—'
  if (valor === true || valor === 1) return 'Sí'
  if (valor === false || valor === 0) return 'No'
  return String(valor)
}

function aplicarVentaLaboratorio (venta) {
  if (perfilesHabilitado.value) {
    aplicarVentaConPerfiles(venta)
    return
  }
  const idsDisponibles = new Set(laboratorios.value.map(item => item.id))
  const productoIds = [...new Set((venta.detalles || [])
    .map(detalle => detalle.producto_id)
    .filter(id => idsDisponibles.has(id)))]

  if (!productoIds.length) {
    proxy.$alert.error('La venta no contiene análisis disponibles en el catálogo actual')
    return
  }

  form.value.paciente_id = venta.paciente_id
  form.value.doctor_id = venta.doctor_id || null
  form.value.producto_ids = productoIds
  valores.value = {}
  visibles.value = {}
  productoIds.forEach(precargarDefectos)
  productoIds.forEach(precargarVisibilidad)

  if (venta.paciente && !pacientesOpciones.value.some(item => item.id === venta.paciente.id)) {
    pacientesOpciones.value.unshift(venta.paciente)
  }
  if (venta.doctor && !doctoresTodos.value.some(item => item.id === venta.doctor.id)) {
    doctoresTodos.value.unshift(venta.doctor)
    doctores.value = doctoresTodos.value
  }

  ventasDialog.value = false
  proxy.$alert.success(`Venta #${venta.id} cargada: ${productoIds.length} análisis marcado(s)`)
}

/*
 * Con perfiles: cada línea de la venta (perfil o examen) entra con las mismas
 * reglas que al elegirla a mano. Se avisa si una línea tenía cantidad mayor a 1
 * (se carga una vez) o si no está disponible en el catálogo.
 */
function aplicarVentaConPerfiles (venta) {
  const mensajes = []
  const lineas = venta.detalles || []
  const productoIds = [...new Set(lineas.map(detalle => detalle.producto_id).filter(Boolean))]
  lineas.forEach(detalle => {
    const nombre = detalle.producto?.nombre || detalle.nombre
    if (Number(detalle.cantidad) > 1) {
      mensajes.push(`La venta tiene ${Number(detalle.cantidad)} × ${nombre}: se carga una sola vez.`)
    }
    if (!porId.value.has(detalle.producto_id)) {
      mensajes.push(`${nombre} no está disponible en el catálogo actual: no se cargó.`)
    }
  })

  form.value.producto_ids = []
  valores.value = {}
  visibles.value = {}
  productoIds.filter(id => porId.value.has(id)).forEach(id => mensajes.push(...agregarSeleccion(id).avisos))
  if (!form.value.producto_ids.length) {
    proxy.$alert.error('La venta no contiene análisis disponibles en el catálogo actual')
    return
  }

  form.value.paciente_id = venta.paciente_id
  form.value.doctor_id = venta.doctor_id || null
  if (venta.paciente && !pacientesOpciones.value.some(item => item.id === venta.paciente.id)) {
    pacientesOpciones.value.unshift(venta.paciente)
  }
  if (venta.doctor && !doctoresTodos.value.some(item => item.id === venta.doctor.id)) {
    doctoresTodos.value.unshift(venta.doctor)
    doctores.value = doctoresTodos.value
  }

  ventasDialog.value = false
  proxy.$alert.success(`Venta #${venta.id} cargada: ${form.value.producto_ids.length} análisis o perfil(es) marcado(s)`)
  if (mensajes.length) mostrarAvisos(`Venta #${venta.id}: revise antes de guardar`, mensajes)
}

async function cargarDatos () {
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/form-data')
    doctoresTodos.value = data.doctores
    doctores.value = data.doctores
    laboratorios.value = data.laboratorios
    perfilesHabilitado.value = data.perfiles_habilitado === true
    await buscarPacientes('', 1)
    if (editId.value) await cargarSolicitud()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los datos')
  }
}
async function cargarSolicitud () {
  const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${editId.value}`)
  codigoLaboratorioGuardado.value = data.codigo_laboratorio || ''
  form.value = {
    paciente_id: data.paciente_id,
    doctor_id: data.doctor_id,
    codigo_laboratorio: data.codigo_laboratorio || '',
    fecha_solicitud: data.fecha_solicitud,
    hora_solicitud: String(data.hora_solicitud || '').slice(0, 5),
    diagnostico_clinico: data.diagnostico_clinico || '',
    observaciones: data.observaciones || '',
    producto_ids: (data.laboratorio_items || []).map(item => item.producto_id).filter(Boolean),
  }
  solicitudPerfiles.value = data.laboratorio_perfiles || []
  if (perfilesHabilitado.value) reconstruirSeleccion(data)
  if (data.paciente && !pacientesOpciones.value.some(item => item.id === data.paciente.id)) {
    pacientesOpciones.value.unshift(data.paciente)
  }
  for (const item of data.laboratorio_items || []) {
    for (const resultado of item.resultados || []) {
      if (resultado.producto_laboratorio_dato_id) {
        valores.value[resultado.producto_laboratorio_dato_id] = resultado.valor || ''
        visibles.value[resultado.producto_laboratorio_dato_id] = resultado.visible !== false
      }
    }
  }
  totalGuardado.value = Number(data.total || 0)
}

/*
 * Al editar con perfiles: la selección vuelve a ser perfiles + exámenes sueltos,
 * en el orden guardado (el perfil tiene el orden de su primer examen). También
 * se guarda la composición de cada perfil para avisar si cambió en el catálogo.
 */
function reconstruirSeleccion (data) {
  const items = data.laboratorio_items || []
  const perfilesPorFila = new Map(solicitudPerfiles.value.map(perfil => [perfil.id, perfil]))
  const entradas = [
    ...solicitudPerfiles.value.filter(perfil => perfil.perfil_producto_id)
      .map(perfil => ({ orden: perfil.orden, prioridad: 0, id: perfil.perfil_producto_id })),
    ...items.filter(item => !item.solicitud_laboratorio_perfil_id && item.producto_id)
      .map(item => ({ orden: item.orden, prioridad: 1, id: item.producto_id })),
  ].sort((a, b) => a.orden - b.orden || a.prioridad - b.prioridad)
  form.value.producto_ids = [...new Set(entradas.map(entrada => entrada.id))]

  const composicion = {}
  items.filter(item => item.solicitud_laboratorio_perfil_id).forEach(item => {
    const perfilId = perfilesPorFila.get(item.solicitud_laboratorio_perfil_id)?.perfil_producto_id
    if (perfilId) (composicion[perfilId] ||= []).push(item.producto_id)
  })
  composicionGuardada.value = composicion
  resultadosGuardados.value = new Set(items
    .filter(item => (item.resultados || []).some(resultado => !resultado.formula && String(resultado.valor ?? '').trim() !== ''))
    .map(item => item.producto_id))
}

/*
 * Diferencias entre la composición guardada de cada perfil y la del catálogo:
 * qué exámenes entran y cuáles salen (y si los que salen tienen resultados).
 * Un examen que sale del perfil pero sigue en la solicitud no se pierde.
 */
function cambiosDeComposicion () {
  const quedan = new Set(seleccionados.value.map(examen => examen.id))
  const nombre = id => porId.value.get(id)?.nombre || `#${id}`
  const mensajes = []
  form.value.producto_ids.filter(id => esPerfil(id) && composicionGuardada.value[id]).forEach(perfilId => {
    const antes = composicionGuardada.value[perfilId]
    const ahora = examenesDePerfil(perfilId)
    const perfil = porId.value.get(perfilId).nombre
    ahora.filter(id => !antes.includes(id)).forEach(id => mensajes.push(`Entra a ${perfil}: ${nombre(id)}.`))
    antes.filter(id => !ahora.includes(id) && !quedan.has(id)).forEach(id => mensajes.push(
      `Sale de ${perfil}: ${nombre(id)}${resultadosGuardados.value.has(id) ? ' — tiene resultados cargados, que se quitarán' : ' (sin resultados cargados)'}.`,
    ))
  })
  return mensajes
}
async function buscarPacientes (search, page, append = false) {
  pacientesLoading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/pacientes', {
      params: { q: search || undefined, page, per_page: 20 },
    })
    pacientesOpciones.value = append
      ? [...pacientesOpciones.value, ...data.data]
      : data.data
    pacientesPage.value = data.current_page
    pacientesLastPage.value = data.last_page
  } finally {
    pacientesLoading.value = false
  }
}
function filtrarPacientes (value, update, abort) {
  buscarPacientes(value.trim(), 1)
    .then(() => {
      pacienteSearch.value = value.trim()
      update(() => {})
    })
    .catch(abort)
}
async function cargarMasPacientes ({ to, ref }) {
  if (pacientesLoading.value || pacientesPage.value >= pacientesLastPage.value) return
  if (to < pacientesOpciones.value.length - 5) return
  await buscarPacientes(pacienteSearch.value, pacientesPage.value + 1, true)
  ref.refresh()
}
function pacienteLabel (paciente) {
  return `${paciente.nombre_completo}${paciente.ci ? ` · CI ${paciente.ci}` : ''}`
}
function abrirPacienteNuevo () {
  pacienteNuevo.value = {
    nombre_completo: '', ci: '', sexo: null, fecha_nacimiento: null,
    telefono: '', direccion: '',
  }
  pacienteDialog.value = true
}
async function guardarPaciente () {
  savingPaciente.value = true
  try {
    const { data } = await proxy.$axios.post('pacientes', pacienteNuevo.value)
    pacientesOpciones.value = [data, ...pacientesOpciones.value.filter(item => item.id !== data.id)]
    form.value.paciente_id = data.id
    pacienteDialog.value = false
    proxy.$alert.success('Paciente creado y seleccionado')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el paciente')
  } finally {
    savingPaciente.value = false
  }
}
function filtrarDoctores (value, update) {
  const search = value.toLowerCase().trim()
  update(() => {
    doctores.value = search
      ? doctoresTodos.value.filter(item => String(item.nombre || '').toLowerCase().includes(search))
      : doctoresTodos.value
  })
}
async function abrirDoctorNuevo () {
  doctorNuevo.value = { nombre: '', especialidad_ids: [] }
  doctorDialog.value = true
  if (!especialidades.value.length) {
    try {
      const { data } = await proxy.$axios.get('especialidades')
      especialidades.value = data || []
    } catch { /* silent */ }
  }
}
async function guardarDoctor () {
  savingDoctor.value = true
  try {
    const { data } = await proxy.$axios.post('doctores', doctorNuevo.value)
    doctoresTodos.value = [data, ...doctoresTodos.value]
    doctores.value = doctoresTodos.value
    form.value.doctor_id = data.id
    doctorDialog.value = false
    proxy.$alert.success('Doctor creado')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el doctor')
  } finally {
    savingDoctor.value = false
  }
}
function toggleLaboratorio (id) {
  if (soloLectura.value) return
  const index = form.value.producto_ids.indexOf(id)
  if (!perfilesHabilitado.value) {
    if (index >= 0) form.value.producto_ids.splice(index, 1)
    else {
      form.value.producto_ids.push(id)
      precargarDefectos(id)
      precargarVisibilidad(id)
    }
    return
  }
  if (index < 0) {
    const { avisos: mensajes } = agregarSeleccion(id)
    mensajes.forEach(mensaje => proxy.$alert.warning(mensaje))
    return
  }
  // Quitar un perfil quita sus exámenes: si tienen resultados cargados, se confirma.
  const bloque = bloques.value.find(item => item.perfil?.id === id)
  const cargados = bloque ? resultadosCargadosDe(bloque.examenes) : 0
  const quitar = () => form.value.producto_ids.splice(form.value.producto_ids.indexOf(id), 1)
  if (cargados) {
    proxy.$alert.dialog(`Se quitará el perfil ${bloque.perfil.nombre} con ${cargados} resultado(s) cargado(s) en sus exámenes. ¿Continuar?`)
      .onOk(quitar)
  } else {
    quitar()
  }
}

function iniciarArrastre (laboratorioId, event) {
  laboratorioArrastrado.value = laboratorioId
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(laboratorioId))
}

function soltarLaboratorio (indiceDestino) {
  const indiceOrigen = form.value.producto_ids.indexOf(laboratorioArrastrado.value)
  if (indiceOrigen < 0 || indiceOrigen === indiceDestino) return
  const [laboratorioId] = form.value.producto_ids.splice(indiceOrigen, 1)
  form.value.producto_ids.splice(indiceDestino, 0, laboratorioId)
}

function finalizarArrastre () {
  laboratorioArrastrado.value = null
}

/* Recupera la selección inicial desde el campo visible configurado para el
   dato. Al editar prevalece el valor que ya fue guardado en la solicitud. */
function precargarVisibilidad (productoId) {
  if (esPerfil(productoId)) return examenesDePerfil(productoId).forEach(precargarVisibilidad)
  const laboratorio = laboratorios.value.find(item => item.id === productoId)
  for (const dato of laboratorio?.laboratorio_datos || []) {
    if (visibles.value[dato.id] === undefined) visibles.value[dato.id] = dato.visible !== false
  }
}

/* Precarga el valor por defecto de cada dato al agregar una prueba. Solo
   rellena lo que está vacío: nunca pisa un resultado ya escrito ni uno
   recuperado al editar la solicitud. */
function precargarDefectos (productoId) {
  if (esPerfil(productoId)) return examenesDePerfil(productoId).forEach(precargarDefectos)
  const laboratorio = laboratorios.value.find(item => item.id === productoId)
  for (const dato of laboratorio?.laboratorio_datos || []) {
    if (dato.formula || !dato.valor_defecto) continue
    if (!valores.value[dato.id]) valores.value[dato.id] = dato.valor_defecto
  }
}
async function guardar () {
  // El botón de la cabecera no es submit: se validan aquí las reglas de los campos obligatorios
  if (!(await formRef.value.validate(true))) {
    proxy.$alert.error('Revise los campos obligatorios marcados en rojo')
    return
  }
  if (!form.value.producto_ids.length) {
    proxy.$alert.error('Seleccione al menos una prueba de laboratorio')
    return
  }
  // Si la composición de un perfil cambió en el catálogo, se muestra qué entra y qué sale.
  if (editId.value && perfilesHabilitado.value && !composicionConfirmada) {
    const cambios = cambiosDeComposicion()
    if (cambios.length) {
      mostrarAvisos('La composición de un perfil cambió en el catálogo', cambios, () => {
        composicionConfirmada = true
        guardar()
      })
      return
    }
  }
  composicionConfirmada = false
  // Si alguna regla configurada no se cumple, se avisa antes de guardar
  if (alertas.value.length) {
    confirmandoGuardado.value = true
    alertasDialog.value = true
    return
  }
  await guardarConfirmado()
}
function continuarGuardado () {
  alertasDialog.value = false
  confirmandoGuardado.value = false
  guardarConfirmado()
}
async function guardarConfirmado () {
  confirmandoGuardado.value = false
  saving.value = true
  try {
    const resultados = seleccionados.value.flatMap(laboratorio =>
      (laboratorio.laboratorio_datos || []).map(dato => ({
        producto_laboratorio_dato_id: dato.id,
        valor: dato.formula ? calcularDato(laboratorio, dato) : (valores.value[dato.id] || null),
        visible: visibles.value[dato.id] !== false,
      }))
    )
    const payload = {
      ...form.value,
      resultados,
    }
    const { data } = editId.value
      ? await proxy.$axios.put(`solicitudes-laboratorio/${editId.value}`, payload)
      : await proxy.$axios.post('solicitudes-laboratorio', payload)
    proxy.$alert.success(`Laboratorio ${data.codigo_completo} ${editId.value ? 'actualizado' : 'creado'}`)
    ;(data.avisos || []).forEach(aviso => proxy.$alert.warning(aviso))
    try {
      await imprimirSolicitudLaboratorio(proxy.$axios, data.id)
    } catch {
      proxy.$alert.warning('La solicitud está guardada. Puede reintentar la impresión desde la lista de solicitudes.')
    }
    proxy.$router.push('/solicitudes-laboratorio')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el laboratorio')
  } finally {
    saving.value = false
  }
}
async function imprimirRotulo () {
  try {
    const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${editId.value}`)
    imprimirRotuloSolicitudLaboratorio(data, proxy.$imgBase)
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo imprimir el rótulo')
  }
}
function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
/* Mientras se escribe: mayúsculas y sin espacios (el backend aplica la misma normalización). */
function normalizarCodigoLaboratorio (value) {
  form.value.codigo_laboratorio = String(value || '').toUpperCase().replace(/\s+/g, '').slice(0, 20)
}
function codigoLaboratorioValido (value) {
  return /^[A-Z0-9]+(-[A-Z0-9]+)*$/.test(value || '') || 'Solo letras, números y guiones (sin guion al inicio o al final ni dos seguidos)'
}
function codigoLaboratorioOpcional (value) {
  return !value || codigoLaboratorioValido(value)
}
function firstValidationError (error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
// Evalúa una regla; devuelve el detalle de la alerta si NO se cumple, o null
function evaluarValidacion (laboratorio, validacion) {
  const porVariable = Object.fromEntries((laboratorio.laboratorio_datos || [])
    .map(item => [item.nombre_variable, item]))
  let resultado
  try {
    resultado = evaluarExpresion(validacion.expresion, variable => {
      const dependencia = porVariable[variable]
      if (!dependencia) throw new Error('Variable desconocida')
      const valor = dependencia.formula
        ? calcularDato(laboratorio, dependencia)
        : valores.value[dependencia.id]
      const numero = Number(valor)
      if (valor === '' || valor === null || valor === undefined || !Number.isFinite(numero)) {
        throw new Error('Dato pendiente')
      }
      return numero
    })
  } catch {
    return null // faltan datos por llenar: todavía no se evalúa
  }
  if (!Number.isFinite(resultado)) return null

  const valor = Number(validacion.valor)
  const hasta = Number(validacion.valor_hasta)
  const obtenido = Math.round(resultado * 10000) / 10000
  const cumple = {
    '=': obtenido === valor,
    '!=': obtenido !== valor,
    '>': obtenido > valor,
    '>=': obtenido >= valor,
    '<': obtenido < valor,
    '<=': obtenido <= valor,
    entre: obtenido >= valor && obtenido <= hasta,
  }[validacion.operador]

  return cumple
    ? null
    : { laboratorio: laboratorio.nombre, mensaje: validacion.mensaje, expresion: validacion.expresion, obtenido }
}

function calcularDato (laboratorio, dato, procesando = new Set()) {
  if (!dato.formula) return valores.value[dato.id] || ''
  if (procesando.has(dato.id)) return ''
  const siguientes = new Set(procesando)
  siguientes.add(dato.id)
  const porVariable = Object.fromEntries((laboratorio.laboratorio_datos || []).map(item => [item.nombre_variable, item]))

  try {
    const resultado = evaluarExpresion(dato.formula.formula, variable => {
      const dependencia = porVariable[variable]
      if (!dependencia) throw new Error('Variable desconocida')
      const valor = dependencia.formula
        ? calcularDato(laboratorio, dependencia, siguientes)
        : valores.value[dependencia.id]
      const numero = Number(valor)
      if (valor === '' || valor === null || valor === undefined || !Number.isFinite(numero)) throw new Error('Dato pendiente')
      return numero
    })
    return Number.isFinite(resultado) ? String(Math.round(resultado * 100) / 100) : ''
  } catch {
    return ''
  }
}
function evaluarExpresion (expression, resolveVariable) {
  const tokens = expression.match(/[a-z][a-z0-9_]*|\d+(?:\.\d+)?|[()+\-*/]/gi) || []
  if (tokens.join('') !== expression.replace(/\s+/g, '')) throw new Error('Expresión inválida')
  let position = 0
  const parseFactor = () => {
    const token = tokens[position++]
    if (token === '(') {
      const value = parseExpression()
      if (tokens[position++] !== ')') throw new Error('Paréntesis inválidos')
      return value
    }
    if (token === '-') return -parseFactor()
    if (/^\d/.test(token || '')) return Number(token)
    if (/^[a-z]/i.test(token || '')) return resolveVariable(token.toLowerCase())
    throw new Error('Factor inválido')
  }
  const parseTerm = () => {
    let value = parseFactor()
    while (tokens[position] === '*' || tokens[position] === '/') {
      const operator = tokens[position++]
      const right = parseFactor()
      value = operator === '*' ? value * right : value / right
    }
    return value
  }
  const parseExpression = () => {
    let value = parseTerm()
    while (tokens[position] === '+' || tokens[position] === '-') {
      const operator = tokens[position++]
      const right = parseTerm()
      value = operator === '+' ? value + right : value - right
    }
    return value
  }
  const result = parseExpression()
  if (position !== tokens.length) throw new Error('Expresión incompleta')
  return result
}

cargarDatos()
</script>

<style scoped>
/* Altura fija: el cuerpo hace scroll interno, la página no crece */
.lab-compacto > .q-form,
.lab-cuerpo,
.lab-cuerpo > div,
.lab-cuerpo .q-card {
  min-height: 0;
}
.lab-cuerpo {
  height: calc(100vh - 250px);
}
.lab-scroll {
  overflow-y: auto;
  min-height: 140px;
}
/* En pantallas chicas se apilan y la página vuelve a hacer scroll normal */
@media (max-width: 1023px) {
  .lab-cuerpo {
    height: auto;
  }
  .lab-scroll {
    max-height: 45vh;
  }
}
.lab-item {
  padding: 1px 4px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.lab-item:hover {
  background: rgba(0, 0, 0, 0.04);
}
.lab-sub {
  font-size: 10px;
  line-height: 1.2;
}
.prueba-ordenable {
  transition: opacity 0.15s ease;
}
.prueba-ordenable--arrastrando {
  opacity: 0.45;
}
.prueba-ordenable__cabecera {
  cursor: grab;
}
.prueba-ordenable__cabecera:active {
  cursor: grabbing;
}
.valor-auditoria {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  max-width: 300px;
}

/* Campos compactos sin reducir la zona reservada para el label flotante.
   QField outlined+dense necesita 40 px para separar label y valor. */
.lab-compacto :deep(.q-field--dense .q-field__control),
.lab-compacto :deep(.q-field--dense .q-field__marginal) {
  height: 40px;
  min-height: 40px;
}
.lab-compacto :deep(.q-field--dense .q-field__native),
.lab-compacto :deep(.q-field--dense .q-field__input),
.lab-compacto :deep(.q-field--dense .q-field__label),
.lab-compacto :deep(.q-field--dense .q-field__suffix) {
  font-size: 11px;
}
.lab-compacto :deep(.q-field--dense .q-field__append),
.lab-compacto :deep(.q-field--dense .q-field__prepend) {
  height: 40px;
}
.lab-compacto :deep(.q-field__bottom) {
  min-height: 0;
  padding: 0;
}
</style>
