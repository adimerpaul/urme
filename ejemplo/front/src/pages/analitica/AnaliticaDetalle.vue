<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- BREADCRUMB / VOLVER -->
    <div class="row items-center q-mb-sm">
      <div class="col">
        <q-breadcrumbs>
          <template v-slot:separator>
            <q-icon size="1.2em" name="arrow_forward" />
          </template>
          <q-breadcrumbs-el icon="science" label="Área Analítica" to="/analitica" />
          <q-breadcrumbs-el
            :label="solicitud ? ('Solicitud #' + solicitud.id) : 'Detalle'"
          />
        </q-breadcrumbs>
      </div>
      <div class="col-auto">
        <q-btn
          flat
          dense
          icon="arrow_back"
          label="Volver"
          no-caps
          @click="$router.back()"
        />
      </div>
    </div>

    <!-- ENCABEZADO TIPO PLANILLA -->
    <q-card flat bordered class="q-mb-sm">
      <q-card flat bordered class="q-pa-sm bg-grey-1">
        <q-card-actions align="right">
          <q-btn-dropdown
            flat
            color="primary"
            icon="print"
            label="Opciones de impresión"
            no-caps
            v-close-popup
          >
            <q-list dense>
              <q-item clickable @click="imprimir">
                <q-item-section avatar>
                  <q-icon name="print" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Imprimir Analítica</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="mandarDoctor">
                <q-item-section avatar>
                  <q-icon name="send" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Mandar informe al doctor</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
<!--          <q-btn-->
<!--            flat-->
<!--            color="primary"-->
<!--            icon="close"-->
<!--            label="Cancelar"-->
<!--            no-caps-->
<!--            @click="$router.back()"-->
<!--          />-->
<!--          actulizar btn-->
          <q-btn
            flat
            color="primary"
            icon="refresh"
            :loading="saving"
            no-caps
            @click="cargarSolicitud"
          />
          <q-btn
            unelevated
            color="primary"
            icon="done_all"
            :loading="saving"
            no-caps
            @click="guardarAnalitica"
          >
            Guardar Analítica y Finalizar
          </q-btn>
        </q-card-actions>
      </q-card>
      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm">
          <div class="col-12 text-center">
            <div class="text-subtitle1 text-weight-bold">
              {{$store.user.area?.name}}
            </div>
<!--            <div class="text-caption text-grey-7">-->
<!--              (Diseño estilo planilla, similar al formato Word)-->
<!--            </div>-->
          </div>
        </div>

        <q-separator class="q-my-sm" />

        <div class="row q-col-gutter-sm text-caption">
          <div class="col-12 col-sm-4">
            <div><b>Fecha / Hora recepción:</b></div>
            <div>{{ solicitud?.fecha_envio_analitica || '-' }}</div>

            <div class="q-mt-xs"><b>N° SUS / EXT:</b></div>
            <div>{{ solicitud?.codigo || '-' }}</div>

            <div class="q-mt-xs"><b>Código paciente:</b></div>
            <div>{{ solicitud?.paciente?.id || '-' }}</div>
          </div>

          <div class="col-12 col-sm-4">
            <div><b>Nombre paciente:</b></div>
            <div>
              {{
                solicitud?.paciente_nombre ||
                solicitud?.paciente?.nombre_completo ||
                '-'
              }}
            </div>

            <div class="q-mt-xs"><b>Edad:</b></div>
            <div>
              {{
                solicitud?.paciente_edad ||
                solicitud?.paciente?.edad ||
                '-'
              }} años
            </div>

            <div class="q-mt-xs"><b>Sexo:</b></div>
            <div>
              {{
                solicitud?.paciente_genero ||
                solicitud?.paciente?.genero ||
                '-'
              }}
            </div>
          </div>

          <div class="col-12 col-sm-4">
            <div><b>Médico solicitante:</b></div>
            <div>
              {{ solicitud?.doctor_nombre || solicitud?.doctor?.name || '-' }}
            </div>

            <div class="q-mt-xs"><b>Diagnóstico clínico:</b></div>
            <div>{{ solicitud?.diagnostico_clinico || '-' }}</div>
            <div>
              <span class="text-bold">Muestras: </span><br>
<!--              tiposMuestra-->
              <ul v-for="muestra in tiposMuestra" :key="muestra.id" style="padding-left: 1em; margin: 0;">
                <li v-if="muestra.selected">
                  {{ muestra.area_tipo_muestra?.tipo_muestra || muestra.nombre || '-' }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- LISTA DE ÁREAS, SERVICIOS Y RANGOS -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="q-pa-sm">
        <q-skeleton
          v-if="loading || !solicitud"
          type="rect"
          height="120px"
          class="q-mb-sm"
        />

        <div v-else>
          <div
            v-for="area in areasConRangos"
            :key="area.id"
            class="q-mb-md"
          >
            <q-card flat bordered>
              <q-card-section class="bg-grey-2 q-pa-sm row items-center">
                <div class="col">
                  <div class="text-subtitle2">
                    Área: {{ area.name }}
                  </div>
                  <div class="text-caption text-grey-7">
                    <b>Servicios vinculados:</b>
                    {{ area.servicios.map(s => s.nombre).join(', ') || '—' }}
                  </div>
                  <div
                    v-if="esHematologia(area)"
                    class="text-caption text-grey-8 q-mt-xs"
                  >
                  </div>
                </div>
                <div class="col-auto">
                  <q-chip
                    dense
                    outline
                    color="primary"
                    icon="biotech"
                    :label="(area.rangos || []).length + ' parámetros'"
                  />
                </div>
              </q-card-section>

              <q-separator />

              <q-card-section class="q-pa-sm">
                <!-- ÁREA 1: HEMATOLOGÍA - Sangre entera -->
                <q-form v-if="area.id === 1 && areaExtras[area.id]">
                  <div class="row q-col-gutter-sm text-caption q-mb-md">
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Sangre entera</div>
                      <q-option-group
                        v-model="areaExtras[area.id].aceptada"
                        type="radio"
                        :options="[
                          { label: 'Aceptada', value: 'ACEPTADA' },
                          { label: 'Rechazada', value: 'RECHAZADA' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Presencia de coágulo</div>
                      <q-option-group
                        v-model="areaExtras[area.id].coagulo"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Volumen adecuado</div>
                      <q-option-group
                        v-model="areaExtras[area.id].volumen"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Identificación</div>
                      <q-option-group
                        v-model="areaExtras[area.id].identificacion"
                        type="radio"
                        :options="[
                          { label: 'Adecuada', value: 'ADECUADA' },
                          { label: 'Inadecuada', value: 'INADECUADA' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Equipo</div>
                      <q-select
                        v-model="areaExtras[area.id].equipo"
                        :options="['Mindray C3510','Mindray 5000']"
                        dense
                        outlined
                        emit-value
                        map-options
                        placeholder="Seleccione equipo"
                      />
                    </div>
                  </div>
                </q-form>

                <!-- ÁREA 2: QUÍMICA SANGUÍNEA - Suero -->
                <q-form v-if="area.id === 2 && areaExtras[area.id]">
                  <div class="row q-col-gutter-sm text-caption q-mb-md">
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Suero</div>
                      <q-option-group
                        v-model="areaExtras[area.id].aceptada"
                        type="radio"
                        :options="[
                          { label: 'Aceptada', value: 'ACEPTADA' },
                          { label: 'Rechazada', value: 'RECHAZADA' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Muestra hemolizada</div>
                      <q-option-group
                        v-model="areaExtras[area.id].hemolizada"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Volumen insuficiente</div>
                      <q-option-group
                        v-model="areaExtras[area.id].volumen_insuficiente"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Identificación inadecuada</div>
                      <q-option-group
                        v-model="areaExtras[area.id].identificacion"
                        type="radio"
                        :options="[
                          { label: 'Adecuada', value: 'ADECUADA' },
                          { label: 'Inadecuada', value: 'INADECUADA' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Equipo</div>
                      <q-select
                        v-model="areaExtras[area.id].equipo"
                        :options="['Mindray 240 –STAT FAX 4500-RADIOMETER','Mindray']"
                        dense
                        outlined
                        emit-value
                        map-options
                        placeholder="Seleccione equipo"
                      />
                    </div>
                  </div>
                </q-form>

                <!-- ÁREA 3: SALA / CAMA / TIPO DE PACIENTE -->
                <q-form v-if="area.id === 3 && areaExtras[area.id]">
                  <div class="row q-col-gutter-sm text-caption q-mb-md">
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Sala</div>
                      <q-input
                        v-model="areaExtras[area.id].sala"
                        dense
                        outlined
                      />
                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Cama</div>
                      <q-input
                        v-model="areaExtras[area.id].cama"
                        dense
                        outlined
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Paciente ambulatorio</div>
                      <q-option-group
                        v-model="areaExtras[area.id].paciente_ambulatorio"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-3 q-mt-sm">
                      <div class="text-weight-medium q-mb-xs">Paciente interno</div>
                      <q-option-group
                        v-model="areaExtras[area.id].paciente_interno"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                  </div>
                </q-form>

                <!-- ÁREA 4: MICROBIOLOGÍA  - (ejemplo vacío) -->
                <q-form v-if="area.id === 4 && areaExtras[area.id]">
                  <div class="row q-col-gutter-sm text-caption q-mb-md">
                    <div class="col-12">
                      <!-- SISTEMA DE PROCESAMIENTO + ENRIQUECIMIENTO BACTERIANA -->
                      <div class="row q-col-gutter-sm q-mb-md">
                        <!-- SISTEMA DE PROCESAMIENTO -->
                        <div class="col-12 col-sm-6">
                          <q-markup-table dense bordered flat class="full-width">
                            <thead>
                            <tr>
                              <th colspan="2" class="text-center">
                                Sistema de Procesamiento
                              </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                              <td class="text-left">Aerobio</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_proc_aerobio"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            <tr>
                              <td class="text-left">Anaerobio</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_proc_anaerobio"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            <tr>
                              <td class="text-left">Microaerofilia</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_proc_microaerofilia"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            </tbody>
                          </q-markup-table>
                        </div>

                        <!-- ENRIQUECIMIENTO BACTERIANA -->
                        <div class="col-12 col-sm-6">
                          <q-markup-table dense bordered flat class="full-width">
                            <thead>
                            <tr>
                              <th colspan="2" class="text-center">
                                Enriquecimiento Bacteriana
                              </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                              <td class="text-left">Caldo de Enriquecimiento</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_enr_caldo_enriquecimiento"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            <tr>
                              <td class="text-left">Caldo Selenito</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_enr_caldo_selenito"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            <tr>
                              <td class="text-left">Caldo Tetrationato</td>
                              <td class="text-center">
                                <q-checkbox
                                  v-model="areaExtras[area.id].baar_enr_caldo_tetrationato"
                                  dense
                                  :false-value="null"
                                  :true-value="true"
                                />
                              </td>
                            </tr>
                            <tr>
                              <td class="text-left">Otros</td>
                              <td class="text-center">
                                <q-input
                                  v-model="areaExtras[area.id].baar_enr_otros"
                                  dense
                                  outlined
                                />
                              </td>
                            </tr>
                            </tbody>
                          </q-markup-table>
                        </div>
                      </div>

                    </div>
                    <div class="col-12">
                      <q-markup-table dense bordered flat class="full-width q-mb-md">
                        <thead>
                        <tr>
                          <th class="text-left">Medio</th>
                          <th class="text-center">Ureocuento<br />UFC/ml</th>
                          <th class="text-center">Hemólisis<br />Alfa</th>
                          <th class="text-center">Hemólisis<br />Beta</th>
                          <th class="text-center">Con IsoVitalex</th>
                          <th class="text-center">Sin IsoVitalex</th>
                          <th class="text-center">Lactosa (+)</th>
                          <th class="text-center">Lactosa (-)</th>
                        </tr>
                        </thead>

                        <tbody>
                        <!-- FILA 1: Agar Nutritivo / Cled -->
                        <tr>
                          <td>Agar Nutritivo / Cled</td>
                          <!-- Celda blanca (tiene input) -->
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_ureo_nutritivo"
                              dense
                              outlined
                            />
                          </td>
                          <!-- Celdas negras (sin input) -->
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                        </tr>

                        <!-- FILA 2: Agar Sangre -->
                        <tr>
                          <td>Agar Sangre</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <!-- Celdas blancas (tienen input) -->
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_hemo_alfa_sangre"
                              dense
                              outlined
                            />
                          </td>
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_hemo_beta_sangre"
                              dense
                              outlined
                            />
                          </td>
                          <!-- Resto negras -->
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                        </tr>

                        <!-- FILA 3: Agar Chocolate -->
                        <tr>
                          <td>Agar Chocolate</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <!-- Celdas blancas (tienen input) -->
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_iso_con_chocolate"
                              dense
                              outlined
                            />
                          </td>
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_iso_sin_chocolate"
                              dense
                              outlined
                            />
                          </td>
                          <!-- Resto negras -->
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                        </tr>

                        <!-- FILA 4: Agar Mc Conkey -->
                        <tr>
                          <td>Agar Mc Conkey</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <!-- Celda blanca Lactosa (+) -->
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_lactosa_pos_mcconkey"
                              dense
                              outlined
                            />
                          </td>
                          <td class="bg-grey-10">&nbsp;</td>
                        </tr>

                        <!-- FILA 5: Agar SS -->
                        <tr>
                          <td>Agar SS</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <!-- Celda blanca Lactosa (-) -->
                          <td class="text-center">
                            <q-input
                              v-model="areaExtras[area.id].baar_lactosa_neg_ss"
                              dense
                              outlined
                            />
                          </td>
                        </tr>

                        <!-- FILA 6: Otros (sin inputs; ajusta si quieres campos aquí) -->
                        <tr>
                          <td>Otros</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                          <td class="bg-grey-10">&nbsp;</td>
                        </tr>
                        </tbody>
                      </q-markup-table>

                    </div>
                    <div class="col-12 col-sm-3">
                      <div class="text-weight-medium q-mb-xs">Gran positivo / Gran negativo</div>
                      <q-option-group
                        v-model="areaExtras[area.id].gran_positivo_negativo"
                        type="radio"
                        :options="[
                          { label: 'Gran positivo', value: 'GRAN_POSITIVO' },
                          { label: 'Gran negativo', value: 'GRAN_NEGATIVO' }
                        ]"
                        dense
                      />
<!--                      morologia coco bacilo cocobacilo-->
                      <div class="text-weight-medium q-mb-xs q-mt-sm">Morfología: coco / bacilo / cocobacilo</div>
                      <q-option-group
                        v-model="areaExtras[area.id].morfologia"
                        type="radio"
                        :options="[
                          { label: 'Coco', value: 'COCO' },
                          { label: 'Bacilo', value: 'BACILO' },
                          { label: 'Cocobacilo', value: 'COCOBACILO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-5">
                      <b>Bioquimiopatia</b>
                      <q-markup-table dense bordered flat class="full-width">
                        <thead>
                        <tr>
                          <th colspan="2" class="text-left">Prueba</th>
                          <th class="text-center">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                          <td>Catalasa</td>
                          <td>TSI</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['catalasa_resultado']" dense outlined placeholder="P1/P2/P3/P4"/>
                          </td>
                        </tr>
                        <tr>
                          <td>Coagulasa</td>
                          <td>Citrato</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['coagulasa_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        <tr>
                          <td>Manitol</td>
                          <td>Lia</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['manitol_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        <tr>
                          <td>ODH</td>
                          <td>MIO</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['odh_resultado']" dense outlined placeholder="P1/P2/P3"/>
                          </td>
                        </tr>
                        <tr>
                          <td>Urea</td>
                          <td>Urea</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['urea_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        <tr>
                          <td>Novobiocina</td>
                          <td>Oxidasa</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['novobiocina_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        <tr>
                          <td></td>
                          <td>Bilis Espulina</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['bilis_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        <tr>
                          <td></td>
                          <td>PYS</td>
                          <td class="text-center">
                            <q-input v-model="areaExtras[area.id]['pys_resultado']" dense outlined placeholder="P1"/>
                          </td>
                        </tr>
                        </tbody>
                      </q-markup-table>
                      <div class="text-weight-medium q-mt-sm">Serotipia Si / No</div>
                      <q-option-group
                        inline
                        v-model="areaExtras[area.id].serotipia"
                        type="radio"
                        :options="[
                          { label: 'Sí', value: 'SI' },
                          { label: 'No', value: 'NO' }
                        ]"
                        dense
                      />
                    </div>
                    <div class="col-12 col-sm-4">
                      <div class="text-weight-medium q-mb-xs">Hemocultivos</div>
                      <q-markup-table dense bordered flat class="full-width">
                        <thead>
                        <tr>
                          <th class="text-left">Lectura de hemocultivo</th>
                          <th class="text-center">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                          <td class="text-left">24 horas</td>
                          <td class="text-center">
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_24h']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">48 horas</td>
<!--                          <td class="text-center"><q-input v-model="areaExtras[area.id]['hemocultivo_48h']" dense outlined placeholder="Resultado"/></td>-->
                          <td class="text-center">
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_48h']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">72 horas</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_72h']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_72h']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">5 días</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_5d']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_5d']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">7 días</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_7d']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_7d']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">10 días</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_10d']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_10d']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">15 días</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_15d']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_15d']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        <tr>
                          <td class="text-left">21 días</td>
                          <td class="text-center">
<!--                            <q-input v-model="areaExtras[area.id]['hemocultivo_21d']" dense outlined placeholder="Resultado"/>-->
                            <q-select
                              clearable
                              v-model="areaExtras[area.id]['hemocultivo_21d']"
                              :options="[
                                '+',
                                '+ / +',
                                '-',
                                '- / -',
                                '- / +',
                                '+ / -'
                              ]"
                              dense
                              outlined
                              placeholder="Seleccione resultado"
                            />
                          </td>
                        </tr>
                        </tbody>
                      </q-markup-table>
                    </div>
                    <div class="col-12">
                      <q-editor
                        v-model="qeditor"
                        :dense="$q.screen.lt.md"
                        :toolbar="[
        [
          {
            label: $q.lang.editor.align,
            icon: $q.iconSet.editor.align,
            fixedLabel: true,
            list: 'only-icons',
            options: ['left', 'center', 'right', 'justify']
          },
          {
            label: $q.lang.editor.align,
            icon: $q.iconSet.editor.align,
            fixedLabel: true,
            options: ['left', 'center', 'right', 'justify']
          }
        ],
        ['bold', 'italic', 'strike', 'underline', 'subscript', 'superscript'],
        ['token', 'hr', 'link', 'custom_btn'],
        ['print', 'fullscreen'],
        [
          {
            label: $q.lang.editor.formatting,
            icon: $q.iconSet.editor.formatting,
            list: 'no-icons',
            options: [
              'p',
              'h1',
              'h2',
              'h3',
              'h4',
              'h5',
              'h6',
              'code'
            ]
          },
          {
            label: $q.lang.editor.fontSize,
            icon: $q.iconSet.editor.fontSize,
            fixedLabel: true,
            fixedIcon: true,
            list: 'no-icons',
            options: [
              'size-1',
              'size-2',
              'size-3',
              'size-4',
              'size-5',
              'size-6',
              'size-7'
            ]
          },
          {
            label: $q.lang.editor.defaultFont,
            icon: $q.iconSet.editor.font,
            fixedIcon: true,
            list: 'no-icons',
            options: [
              'default_font',
              'arial',
              'arial_black',
              'comic_sans',
              'courier_new',
              'impact',
              'lucida_grande',
              'times_new_roman',
              'verdana'
            ]
          },
          'removeFormat'
        ],
        ['quote', 'unordered', 'ordered', 'outdent', 'indent'],

        ['undo', 'redo'],
        ['viewsource']
      ]"
                        :fonts="{
        arial: 'Arial',
        arial_black: 'Arial Black',
        comic_sans: 'Comic Sans MS',
        courier_new: 'Courier New',
        impact: 'Impact',
        lucida_grande: 'Lucida Grande',
        times_new_roman: 'Times New Roman',
        verdana: 'Verdana'
      }"
                      />
                    </div>
                  </div>
                </q-form>

                <!-- TABLA DE PARÁMETROS -->
                <q-markup-table dense bordered flat class="full-width" v-if="area.rangos.length > 0">
                  <thead>
                  <tr>
                    <th class="text-left">Parámetro</th>
                    <th class="text-center">Resultado</th>
                    <th class="text-center">Unidad</th>
                    <th class="text-center">Rango de referencia</th>
                    <th class="text-left">Interpretación</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr
                    v-for="r in area.rangos"
                    :key="r.id"
                  >
                    <td class="text-left text-caption">
                      {{ r.rango_nombre }}
                    </td>

                    <!-- CELDA RESULTADO -->
                    <td class="text-center">
                      <!-- HEMATOLOGÍA: dos columnas AUTO / MANUAL -->
                      <div
                        v-if="esHematologia(area)"
                        class="row q-col-gutter-xs"
                        style="max-width: 260px; margin: 0 auto;"
                      >
                        <div class="col-6">
                          <q-input
                            v-model="resultados[area.id][r.id].valor_automatizado"
                            dense
                            outlined
                            label="Auto"
                          >
                            <template v-slot:prepend>
                              <q-icon
                                v-if="getEstadoRango(area.id, r, 'valor_automatizado') !== null"
                                :name="getEstadoRango(area.id, r, 'valor_automatizado') === 'ok' ? 'check_circle' : 'highlight_off'"
                                :color="getEstadoRango(area.id, r, 'valor_automatizado') === 'ok' ? 'blue-6' : 'red'"
                                size="16px"
                              />
                            </template>
                          </q-input>
                        </div>
                        <div class="col-6">
                          <q-input
                            v-model="resultados[area.id][r.id].valor_manual"
                            dense
                            outlined
                            label="Manual"
                          >
                            <template v-slot:prepend>
                              <q-icon
                                v-if="getEstadoRango(area.id, r, 'valor_manual') !== null"
                                :name="getEstadoRango(area.id, r, 'valor_manual') === 'ok' ? 'check_circle' : 'highlight_off'"
                                :color="getEstadoRango(area.id, r, 'valor_manual') === 'ok' ? 'blue-6' : 'red'"
                                size="16px"
                              />
                            </template>
                          </q-input>
                        </div>
                      </div>

                      <!-- OTRAS ÁREAS: solo un valor -->
                      <div v-else style="max-width: 140px; margin: 0 auto;">
                        <q-input
                          v-model="resultados[area.id][r.id].valor"
                          dense
                          outlined
                        >
                          <template v-slot:prepend>
                            <q-icon
                              v-if="getEstadoRango(area.id, r) !== null"
                              :name="getEstadoRango(area.id, r) === 'ok' ? 'check_circle' : 'highlight_off'"
                              :color="getEstadoRango(area.id, r) === 'ok' ? 'blue-6' : 'red'"
                              size="16px"
                            />
                          </template>
                        </q-input>
                      </div>
                    </td>

                    <td class="text-center text-caption">
                      {{ r.unidad || '' }}
                    </td>
                    <td class="text-center text-caption">
                      {{ formatRango(r) }}
                    </td>
                    <td class="text-left text-caption">
                      {{ r.interpretacion || '' }}
                    </td>
                  </tr>
                  </tbody>
                </q-markup-table>
                <div v-else>
                  <div class="row">
                    <div class="col-12 col-md-9 q-pa-xs">
                      <q-card v-for="solicitude_formulario in solicitud.solicitude_formularios" :key="solicitude_formulario.formulario_id" flat bordered>
                        <q-card-section>
                          <div class="text-h6 text-primary q-mb-sm row items-center">
                            {{ solicitude_formulario.nombre }}
                            <q-space />
                            <q-btn
                              dense
                              flat
                              round
                              color="red"
                              icon="delete"
                              @click="solicitud.solicitude_formularios = solicitud.solicitude_formularios.filter(f => f.formulario_id !== solicitude_formulario.formulario_id)"
                            />
                          </div>
                          <q-editor
                            v-model="solicitude_formulario.html"
                          />
                        </q-card-section>
                      </q-card>
<!--                      <pre>{{areasConRangos}}</pre>-->
<!--                      <pre>{{solicitud.solicitude_formularios}}</pre>-->
<!--                      [-->
<!--                      {-->
<!--                      "solicitude_id": 2,-->
<!--                      "formulario_id": 27,-->
<!--                      "nombre": "Marcadores Cardiacos – Troponina I (cTnI)",-->
<!--                      "html": "<table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" width=\"100%\">\n    <thead>\n        <tr>\n            <th>ANALITO</th>\n            <th colspan=\"2\">RESULTADOS</th>\n            <th>VALORES DE REFERENCIA</th>\n        </tr>\n    </thead>\n    <tbody>\n        <tr>\n            <td>TROPONINA-I (cTnI)</td>\n            <td></td>\n            <td>ng/ml</td>\n            <td>Menor a 1,3 ng/ml</td>\n        </tr>\n    </tbody>\n</table>"-->
<!--                      }-->
<!--                      ]-->
                    </div>
                    <div class="col-12 col-md-3">
                      <div class="text-caption text-grey-7 text-center">
                        Seleccióne un formulario para insertar <br>
                        <q-input dense outlined v-model="buscarFormulario" placeholder="Buscar formulario" @update:model-value="filtrarFormulario" clearable />
                        <br>
                        <template  v-for="formulario in formularios" :key="formulario.id">
                          <q-btn class="q-ma-xs" size="sm" color="primary" :label="formulario.nombre" no-caps @click="agregarFormulario(formulario)" />
                          <br>
                        </template>
                      </div>
                    </div>
                  </div>
<!--                  <pre>{{formularios}}</pre>-->
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div
            v-if="!areasConRangos.length"
            class="text-caption text-grey-7 text-center q-mt-md"
          >
            La solicitud no tiene áreas con rangos configurados.
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'AnaliticaDetallePage',
  data () {
    return {
      buscarFormulario: '',
      qeditor: '',
      formularios: [],
      formulariosAll: [],
      loading: false,
      saving: false,
      solicitud: null,
      resultados: {},      // { [area_id]: { [rango_id]: { valor, valor_automatizado, valor_manual } } }
      areaExtras: {}       // { [area_id]: { campo: valor } } (sangre entera, suero, sala/cama, etc.)
    }
  },
  computed: {
    areasConRangos () {
      if (!this.solicitud || !this.solicitud.servicios) return []

      const map = {}
      this.solicitud.servicios.forEach(s => {
        const area = s.area
        if (!area) return
        if (!map[area.id]) {
          map[area.id] = {
            id: area.id,
            name: area.name,
            rangos: area.rangos || [],
            servicios: []
          }
        }
        map[area.id].servicios.push({
          id: s.id,
          nombre: s.nombre
        })
      })

      return Object.values(map)
    },
    tiposMuestra () {
      return (this.solicitud && this.solicitud.pre_analitica_muestras)
        ? this.solicitud.pre_analitica_muestras
        : []
    }
  },
  mounted () {
    this.cargarSolicitud()
    this.formulariosGet()
  },
  methods: {
    agregarFormulario(formulario) {
      // verificar si ya se agrego
      if (this.solicitud.solicitude_formularios &&
          this.solicitud.solicitude_formularios.find(f => f.formulario_id === formulario.id)) {
        this.$alert?.info?.('El formulario ya fue agregado.')
        return
      }
      this.solicitud.solicitude_formularios = this.solicitud.solicitude_formularios || []
      this.solicitud.solicitude_formularios.push({
        solicitude_id: this.solicitud.id,
        formulario_id: formulario.id,
        nombre: formulario.nombre,
        html: formulario.html,
        area_id: formulario.area_id
      })
    },
    filtrarFormulario() {
      const busqueda = this.buscarFormulario?.trim().toLowerCase()
      if (!busqueda) {
        this.formularios = this.formulariosAll
        return
      }
      this.formularios = this.formulariosAll.filter(f => {
        return f.nombre.toLowerCase().includes(busqueda)
      })
    },
    formulariosGet () {
      this.$axios
        .get('formularios')
        .then(res => {
          this.formularios = res.data.data || []
          this.formulariosAll = res.data.data || []
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          this.$alert?.error?.('Error al cargar formularios: ' + msg)
        })
    },
    mandarDoctor () {
      if (!this.solicitud) return

      const apiBase = this.$axios.defaults.baseURL || ''
      const backBase = apiBase.replace(/\/api\/?$/, '')

      const codigo = this.solicitud.nro_registro
      if (!codigo) {
        this.$alert?.error?.('La solicitud no tiene nro_registro generado.')
        return
      }

      const urlReporte = `${backBase}/public/reportes/${codigo}`

      let phone = this.solicitud.doctor_telefono || ''
      phone = phone.replace(/\D/g, '')

      if (!phone) {
        this.$alert?.error?.('El médico no tiene teléfono registrado.')
        return
      }

      const mensaje = `Dr(a). ${this.solicitud.doctor_nombre}, le envío los resultados de laboratorio del paciente ${this.solicitud.paciente_nombre}. Puede verlos aquí: ${urlReporte}`

      const text = encodeURIComponent(mensaje)

      const urlWhatsapp = `https://wa.me/591${phone}?text=${text}`

      window.open(urlWhatsapp, '_blank')
    },

    imprimir () {
      if (!this.solicitud || !this.solicitud.id) return

      const apiBase = this.$axios.defaults.baseURL || ''
      const backBase = apiBase.replace(/\/api\/?$/, '')

      const url = `${backBase}/api/solicitudes/${this.solicitud.id}/analitica-pdf`
      window.open(url, '_blank')
    },

    esHematologia (area) {
      if (!area) return false
      if (area.id === 1) return true
      const name = String(area.name || '').toUpperCase()
      return name.includes('HEMATO')
    },

    getValorResultado (areaId, rangoId, field = 'valor') {
      const area = this.resultados[areaId]
      if (!area || !area[rangoId]) return ''
      return area[rangoId][field]
    },

    parseValorNumerico (valor) {
      if (valor === null || valor === undefined) return null
      if (typeof valor === 'number') return isNaN(valor) ? null : valor

      const texto = String(valor).replace(',', '.').trim()
      if (!texto) return null

      const num = Number(texto)
      return isNaN(num) ? null : num
    },

    getEstadoRango (areaId, rango, field = 'valor') {
      const bruto = this.getValorResultado(areaId, rango.id, field)
      const valor = this.parseValorNumerico(bruto)
      if (valor === null) return null

      const min = rango.rango_minimo
      const max = rango.rango_maximo

      if (min != null && valor < min) return 'out'
      if (max != null && valor > max) return 'out'
      return 'ok'
    },

    formatRango (r) {
      const min = r.rango_minimo
      const max = r.rango_maximo
      if (min == null && max == null) return ''
      if (min != null && max != null) return `${min} - ${max}`
      if (min != null) return `≥ ${min}`
      return `≤ ${max}`
    },

    inicializarResultados () {
      const res = {}
      this.areasConRangos.forEach(area => {
        if (!res[area.id]) res[area.id] = {}
        ;(area.rangos || []).forEach(r => {
          res[area.id][r.id] = {
            valor: '',
            valor_automatizado: '',
            valor_manual: ''
          }
        })
      })
      this.resultados = res
    },

    aplicarResultadosDesdeBackend () {
      if (!this.solicitud || !this.solicitud.resultados) return

      this.solicitud.resultados.forEach(row => {
        const areaId = row.area_id
        const rangoId = row.area_rango_id
        if (!this.resultados[areaId] || !this.resultados[areaId][rangoId]) {
          return
        }
        this.resultados[areaId][rangoId] = {
          valor: row.valor_final ?? '',
          valor_automatizado: row.valor_automatizado ?? '',
          valor_manual: row.valor_manual ?? ''
        }
      })
    },

    inicializarAreaExtras () {
      const extras = { ...this.areaExtras }

      this.areasConRangos.forEach(area => {
        const id = area.id
        if (!extras[id]) extras[id] = {}

        if (id === 1) {
          extras[id] = {
            aceptada: extras[id].aceptada ?? null,
            coagulo: extras[id].coagulo ?? null,
            volumen: extras[id].volumen ?? null,
            identificacion: extras[id].identificacion ?? null,
            equipo: extras[id].equipo ?? null
          }
        } else if (id === 2) {
          extras[id] = {
            aceptada: extras[id].aceptada ?? null,
            hemolizada: extras[id].hemolizada ?? null,
            volumen_insuficiente: extras[id].volumen_insuficiente ?? null,
            identificacion: extras[id].identificacion ?? null,
            equipo: extras[id].equipo ?? null
          }
        } else if (id === 3) {
          extras[id] = {
            sala: extras[id].sala ?? '',
            cama: extras[id].cama ?? '',
            paciente_ambulatorio: extras[id].paciente_ambulatorio ?? null,
            paciente_interno: extras[id].paciente_interno ?? null
          }
        }
      })

      this.areaExtras = extras
    },

    aplicarExtrasDesdeBackend () {
      if (!this.solicitud || !this.solicitud.propiedades) return

      const extras = { ...this.areaExtras }

      this.solicitud.propiedades.forEach(p => {
        const areaId = p.area_id
        if (!extras[areaId]) extras[areaId] = {}
        extras[areaId][p.campo] = p.valor
      })

      this.areaExtras = extras
    },

    cargarSolicitud () {
      const id = this.$route.params.id
      if (!id) return

      this.loading = true
      this.$axios
        .get(`solicitudes-area-analitica/${id}`)
        .then(res => {
          this.solicitud = res.data
          this.inicializarResultados()
          this.aplicarResultadosDesdeBackend()
          this.inicializarAreaExtras()
          this.aplicarExtrasDesdeBackend()
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          this.$alert?.error?.('Error al cargar solicitud: ' + msg)
        })
        .finally(() => {
          this.loading = false
        })
    },

    guardarAnalitica () {
      if (!this.solicitud || !this.solicitud.id) return

      this.saving = true
      this.$axios
        .post(`solicitudes/${this.solicitud.id}/analitica`, {
          resultados: this.resultados,
          propiedades_area: this.areaExtras,
          formularios: this.solicitud.solicitude_formularios || []
        })
        .then(() => {
          this.$alert?.success?.(
            'Analítica guardada y solicitud finalizada'
          )
          // guarad u ir a analitica
          this.$router.push('/analitica')
        })
        .catch(err => {
          console.error(err)
          const msg = err.response?.data?.message || err.message
          this.$alert?.error?.('Error al guardar analítica: ' + msg)
        })
        .finally(() => {
          this.saving = false
        })
    }
  }
}
</script>
