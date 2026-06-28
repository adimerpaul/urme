<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- ENCABEZADO -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">Hematología</div>
          <div class="text-caption text-grey-7">
            Hemograma, recuento diferencial, coagulograma y grupo sanguíneo
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            flat
            icon="refresh"
            label="Refrescar"
            no-caps
            class="q-mr-sm"
            :disable="loading"
            @click="load"
          />
          <q-btn
            flat
            icon="print"
            label="Imprimir"
            no-caps
            class="q-mr-sm"
            :disable="loading || !header"
            @click="printHematologia"
          />
          <q-btn
            flat
            icon="arrow_back"
            label="Volver"
            no-caps
            class="q-mr-xs"
            @click="$router.back()"
          />
          <q-btn
            color="primary"
            icon="save"
            label="Guardar"
            no-caps
            :loading="loading"
            @click="onSubmit"
          />
        </div>
      </q-card-section>

      <q-separator />
<!--      <table class="form-grid" style="margin-top:2px;">-->
<!--        <tr>-->
<!--          <td style="width:15%"><span class="label">CÓDIGO:</span></td>-->
<!--          <td style="width:10%"><div class="line clip">{{ $solicitud->codigo ?? $solicitud->id }}</div></td>-->
<!--          <td style="width:10%"><span class="label">ATENCION:</span></td>-->
<!--          <td style="width:15%"><div class="line clip">{{ ($solicitud->tipo_atencion ?? '') === 'SI' ? 'SUS' : 'EXT' }}</div></td>-->
<!--          <td colspan="2" style="width:20%"><span class="label">NRO. REGISTRO:</span></td>-->
<!--          <td colspan="2" style="width:30%"><div class="line clip">{{ $solicitud->nro_registro ?? '-' }}</div></td>-->
<!--        </tr>-->

<!--        <tr>-->
<!--          <td><span class="label">PACIENTE:</span></td>-->
<!--          <td colspan="3"><div class="line clip">{{ $solicitud->paciente_nombre ?? '-' }}</div></td>-->
<!--          <td><span class="label">EDAD:</span></td>-->
<!--          <td><div class="line clip">{{ $solicitud->paciente_edad ?? '-' }}</div></td>-->
<!--          <td><span class="label">SEXO:</span></td>-->
<!--          <td><div class="line clip">{{ $solicitud->paciente_genero ?? '-' }}</div></td>-->
<!--        </tr>-->

<!--        <tr>-->
<!--          <td><span class="label">MEDICO SOL.:</span></td>-->
<!--          <td colspan="3"><div class="line clip">{{ $solicitud->doctor_nombre ?? '-' }}</div></td>-->
<!--          <td><span class="label">DX:</span></td>-->
<!--          <td colspan="3"><div class="line clip">{{ $solicitud->diagnostico_select ?? '-' }}</div></td>-->
<!--        </tr>-->

<!--        <tr>-->
<!--          <td colspan="2"><span class="label">CODIGO MUESTRA:</span></td>-->
<!--          <td colspan="6" class="line clip">-->
<!--            {{ ($solicitud->codigo ?? '-') . '-' . ($solicitud->nro_registro ?? '-') }}-->
<!--          </td>-->
<!--        </tr>-->

<!--        <tr>-->
<!--          <td><span class="label">EST. DE SALUD:</span></td>-->
<!--          <td colspan="3"><div class="line clip">{{ $solicitud->establecimiento_salud ?? '-' }}</div></td>-->
<!--          <td colspan="2"><span class="label">FECHA DE RESULTADO:</span></td>-->
<!--          <td colspan="2"><div class="line clip">{{ $solicitud->fecha_finalizacion ?? '-' }}</div></td>-->
<!--        </tr>-->
<!--      </table>-->
      <InfoServicio :header="header" :fecha_fin="form.created_at"/>
<!--      <pre>{{form}}</pre>-->

      <q-inner-loading :showing="loading && !formLoaded">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>

    <!-- FORMULARIO PRINCIPAL -->
    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <q-form @submit.prevent="onSubmit">
          <!-- HEMOGRAMA BÁSICO -->
          <div class="section-title q-mb-xs">
            <div class="row">
              <div class="col-12">
                <div class="section-title q-mb-xs">Muestra rechazada</div>
              </div>
              <div class="col-12 col-md-4">

                <q-toggle v-model="form.muestra_rechazada" label="¿Muestra rechazada?" true-value="Si" false-value="No"
                          @update:model-value="form.muestra_observacion = ''"
                />
              </div>
              <div class="col-12 col-md-4" v-if="form.muestra_rechazada === 'Si'">
                <div class="section-title q-mb-xs">Observación</div>
                <q-input
                  v-model="form.muestra_observacion"
                  type="textarea"
                  dense
                  outlined
                  label="Observación de la muestra"
                />
              </div>
              <div class="col-12"></div>
              <div class="col-12 col-md-3">
                Hemograma
              </div>
              <div class="col-12 col-md-3">
                <q-select
                  v-model="form.hemograma_metodo"
                  dense
                  outlined
                  label="Método"
                  :options="['Automática', 'SemiAutomática', 'Manual']"
                />
              </div>
              <div class="col-12 col-md-3">
                <q-select
                  v-model="form.hemograma_equipo"
                  dense
                  outlined
                  label="Equipo"
                  :options="['Mindray BC 5130', 'Mindray BC 3000 Plus', 'Otro']"
                />
              </div>
            </div>
<!--            methodo equipo-->
          </div>

          <q-markup-table dense flat bordered square class="bg-white q-mb-md">
            <thead>
            <tr>
              <th class="text-left">Analito</th>
              <th class="text-left">Resultado</th>
              <th class="text-left">Rango de referencia</th>
              <th class="text-left">Unidad</th>
            </tr>
            </thead>

            <tbody>
            <tr>
              <td>Glóbulos rojos</td>
              <td>
                <q-input
                  v-model.number="form.globulos_rojos"
                  dense
                  outlined
                  type="number"
                  step="0.1"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Globulos Rojos', form.globulos_rojos) ? 'text-negative text-weight-bold' : ''
                    ]"

                />
<!--                @update:model-value="calculateHematimetricos"-->
              </td>
              <td>{{ rangoTexto('Globulos Rojos') }}</td>
              <td>{{ rangoUnidad('Globulos Rojos') }}</td>
            </tr>

            <tr>
              <td>Glóbulos blancos</td>
              <td>
                <q-input
                  v-model.number="form.globulos_blancos"
                  dense
                  outlined
                  type="number"
                  step="0.1"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Globulos Blancos (Leucocitos)', form.globulos_blancos) ? 'text-negative text-weight-bold' : ''
                    ]"
                />
              </td>
              <td>{{ rangoTexto('Globulos Blancos (Leucocitos)') }}</td>
              <td>{{ rangoUnidad('Globulos Blancos (Leucocitos)') }}</td>
            </tr>

            <tr>
              <td>Plaquetas</td>
              <td>
                <q-input
                  v-model.number="form.plaquetas"
                  dense
                  outlined
                  type="number"
                  step="0.1"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Plaquetas', form.plaquetas) ? 'text-negative text-weight-bold' : ''
                    ]"
                />
              </td>
              <td>{{ rangoTexto('Plaquetas') }}</td>
              <td>{{ rangoUnidad('Plaquetas') }}</td>
            </tr>

            <tr >
              <td>Hemoglobina</td>
              <td>
                <q-input
                  v-model.number="form.hemoglobina"
                  dense
                  outlined
                  type="number"
                  step="0.01"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Hemoglobina', form.hemoglobina) ? 'text-negative text-weight-bold' : ''
                    ]"

                />
<!--                @update:model-value="calculateHematimetricos"-->
              </td>
              <td>{{ rangoTexto('Hemoglobina') }}</td>
              <td>{{ rangoUnidad('Hemoglobina') }}</td>
            </tr>

            <tr>
              <td>Hematocrito</td>
              <td>
                <q-input
                  v-model.number="form.hematocrito"
                  dense
                  outlined
                  type="number"
                  step="0.01"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Hematocrito', form.hematocrito) ? 'text-negative text-weight-bold' : ''
                    ]"

                />
<!--                @update:model-value="calculateHematimetricos"-->
              </td>
              <td>{{ rangoTexto('Hematocrito') }}</td>
              <td>{{ rangoUnidad('Hematocrito') }}</td>
            </tr>

<!--            <tr v-if="canServicios('ÍNDICES HEMATIMÉTRICOS')">-->
<!--              <td>VCM</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.vcm"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('V.C.M.', form.vcm) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('V.C.M.') }}</td>-->
<!--              <td>{{ rangoUnidad('V.C.M.') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('ÍNDICES HEMATIMÉTRICOS')">-->
<!--              <td>HBCM</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.hbcm"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Hb.C.M.', form.hbcm) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Hb.C.M.') }}</td>-->
<!--              <td>{{ rangoUnidad('Hb.C.M.') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('ÍNDICES HEMATIMÉTRICOS')">-->
<!--              <td>CHCM</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.chcm"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('CHCM', form.chcm) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('CHCM') }}</td>-->
<!--              <td>{{ rangoUnidad('CHCM') }}</td>-->
<!--            </tr>-->

<!--            <tr>-->
<!--              <td>Leucocitos totales</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.leucocitos_totales"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Leucocitos totales', form.leucocitos_totales) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Leucocitos totales') }}</td>-->
<!--              <td>{{ rangoUnidad('Leucocitos totales') }}</td>-->
<!--            </tr>-->
            </tbody>
          </q-markup-table>
          <div class="section-title q-mb-xs"
               v-if="canServicios(['ÍNDICES HEMATIMÉTRICOS','HEMOGRAMA COMPLETO+ PLAQUETAS'])"
          >Indices hematimetricos</div>

          <q-markup-table dense flat bordered square class="bg-white q-mb-md"
                          v-if="canServicios(['ÍNDICES HEMATIMÉTRICOS','HEMOGRAMA COMPLETO+ PLAQUETAS'])"
          >
            <thead>
            <tr>
              <th class="text-left">Analito</th>
              <th class="text-left">Resultado</th>
              <th class="text-left">Rango de referencia</th>
              <th class="text-left">Unidad</th>
            </tr>
            </thead>

            <tbody>
<!--            <tr v-if="canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','MORFOLOGÍA DE GLÓBULOS ROJOS'])">-->
<!--              <td>Glóbulos rojos</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.globulos_rojos"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Globulos Rojos', form.globulos_rojos) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Globulos Rojos') }}</td>-->
<!--              <td>{{ rangoUnidad('Globulos Rojos') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">-->
<!--              <td>Glóbulos blancos</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.globulos_blancos"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Globulos Blancos (Leucocitos)', form.globulos_blancos) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Globulos Blancos (Leucocitos)') }}</td>-->
<!--              <td>{{ rangoUnidad('Globulos Blancos (Leucocitos)') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','HEMOGRAMA COMPLETO+ PLAQUETAS','RECUENTO DE PLAQUETAS'])">-->
<!--              <td>Plaquetas</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.plaquetas"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Plaquetas', form.plaquetas) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Plaquetas') }}</td>-->
<!--              <td>{{ rangoUnidad('Plaquetas') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','HEMATOCRITO Y HEMOGLOBINA'])">-->
<!--              <td>Hemoglobina</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.hemoglobina"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Hemoglobina', form.hemoglobina) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Hemoglobina') }}</td>-->
<!--              <td>{{ rangoUnidad('Hemoglobina') }}</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','HEMATOCRITO Y HEMOGLOBINA'])">-->
<!--              <td>Hematocrito</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.hematocrito"-->
<!--                  dense-->
<!--                  outlined-->
<!--                  type="number"-->
<!--                  step="0.01"-->
<!--                  :input-class="[-->
<!--                      'text-right',-->
<!--                      isOutOfRange('Hematocrito', form.hematocrito) ? 'text-negative text-weight-bold' : ''-->
<!--                    ]"-->
<!--                />-->
<!--              </td>-->
<!--              <td>{{ rangoTexto('Hematocrito') }}</td>-->
<!--              <td>{{ rangoUnidad('Hematocrito') }}</td>-->
<!--            </tr>-->

            <tr v-if="canServicios(['ÍNDICES HEMATIMÉTRICOS','HEMOGRAMA COMPLETO+ PLAQUETAS'])">
              <td>VCM</td>
              <td>
                <q-input
                  v-model.number="form.vcm"
                  dense
                  outlined
                  type="number"
                  step="0.01"
                  :input-class="[
                      'text-right',
                      isOutOfRange('V.C.M.', form.vcm) ? 'text-negative text-weight-bold' : ''
                    ]"
                />
              </td>
              <td>{{ rangoTexto('V.C.M.') }}</td>
              <td>{{ rangoUnidad('V.C.M.') }}</td>
            </tr>

            <tr v-if="canServicios(['ÍNDICES HEMATIMÉTRICOS','HEMOGRAMA COMPLETO+ PLAQUETAS'])">
              <td>HBCM</td>
              <td>
                <q-input
                  v-model.number="form.hbcm"
                  dense
                  outlined
                  type="number"
                  step="0.01"
                  :input-class="[
                      'text-right',
                      isOutOfRange('Hb.C.M.', form.hbcm) ? 'text-negative text-weight-bold' : ''
                    ]"
                />
              </td>
              <td>{{ rangoTexto('Hb.C.M.') }}</td>
              <td>{{ rangoUnidad('Hb.C.M.') }}</td>
            </tr>

            <tr v-if="canServicios(['ÍNDICES HEMATIMÉTRICOS','HEMOGRAMA COMPLETO+ PLAQUETAS'])">
              <td>CHCM</td>
              <td>
                <q-input
                  v-model.number="form.chcm"
                  dense
                  outlined
                  type="number"
                  step="0.01"
                  :input-class="[
                      'text-right',
                      isOutOfRange('CHCM', form.chcm) ? 'text-negative text-weight-bold' : ''
                    ]"
                />
              </td>
              <td>{{ rangoTexto('CHCM') }}</td>
              <td>{{ rangoUnidad('CHCM') }}</td>
            </tr>

            <!--            <tr>-->
            <!--              <td>Leucocitos totales</td>-->
            <!--              <td>-->
            <!--                <q-input-->
            <!--                  v-model.number="form.leucocitos_totales"-->
            <!--                  dense-->
            <!--                  outlined-->
            <!--                  type="number"-->
            <!--                  step="0.01"-->
            <!--                  :input-class="[-->
            <!--                      'text-right',-->
            <!--                      isOutOfRange('Leucocitos totales', form.leucocitos_totales) ? 'text-negative text-weight-bold' : ''-->
            <!--                    ]"-->
            <!--                />-->
            <!--              </td>-->
            <!--              <td>{{ rangoTexto('Leucocitos totales') }}</td>-->
            <!--              <td>{{ rangoUnidad('Leucocitos totales') }}</td>-->
            <!--            </tr>-->
            </tbody>
          </q-markup-table>

          <!-- RECUENTO DIFERENCIAL -->
          <div class="section-title q-mb-xs">Recuento diferencial</div>

          <q-markup-table dense flat bordered square class="bg-white q-mb-md">
            <thead>
            <tr>
              <th class="text-left">Célula</th>
              <th class="text-left">%</th>
              <th class="text-left">Valor absoluto</th>
              <th class="text-left">Rango % ref.</th>
              <th class="text-left">Rango absoluto ref.</th>
            </tr>
            </thead>

            <tbody>
            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Basófilos</td>
              <td>
                <q-input v-model.number="form.basofilos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Basofilos', form.basofilos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.basofilos_absoluto = (((form.basofilos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                         "
                />
              </td>
              <td>
                <q-input v-model.number="form.basofilos_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Basilos (Absoluto)', form.basofilos_absoluto) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.basofilos_porcentaje = ( (form.basofilos_absoluto || 0) / (form.globulos_blancos || 1) ) * 100;
                          "
                />
              </td>
              <td>{{ rangoTexto('Basofilos') }}</td>
              <td>{{ rangoTexto('Basilos (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Eosinófilos</td>
              <td>
                <q-input v-model.number="form.eosinofilos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Eosinofilos', form.eosinofilos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.eosinofilos_absoluto = (((form.eosinofilos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.eosinofilos_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Eosinofilos (Absoluto)', form.eosinofilos_absoluto) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Eosinofilos') }}</td>
              <td>{{ rangoTexto('Eosinofilos (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Cayados</td>
              <td>
                <q-input v-model.number="form.cayados_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Cayados', form.cayados_porcentaje) ? 'text-negative text-weight-bold' : '']"
                          @update:model-value="
                              form.cayados_absoluto = (((form.cayados_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                            "
                />
              </td>
              <td>
                <q-input v-model.number="form.cayados_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Cayados (Absoluto)', form.cayados_absoluto) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Cayados') }}</td>
              <td>{{ rangoTexto('Cayados (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Segmentados</td>
              <td>
                <q-input v-model.number="form.segmentados_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Segmentados', form.segmentados_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.segmentados_absoluto = (((form.segmentados_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.segmentados_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Segmentados (Absoluto)', form.segmentados_absoluto) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Segmentados') }}</td>
              <td>{{ rangoTexto('Segmentados (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Linfocitos</td>
              <td>
                <q-input v-model.number="form.linfocitos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Linfocitos', form.linfocitos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.linfocitos_absoluto = (((form.linfocitos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.linfocitos_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Linfocitos (Absoluto)', form.linfocitos_absoluto) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Linfocitos') }}</td>
              <td>{{ rangoTexto('Linfocitos (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Monocitos</td>
              <td>
                <q-input v-model.number="form.monocitos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Monocitos', form.monocitos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                          @update:model-value="
                              form.monocitos_absoluto = (((form.monocitos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                            "
                />
              </td>
              <td>
                <q-input v-model.number="form.monocitos_absoluto" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Monocitos (Absoluto)', form.monocitos_absoluto) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Monocitos') }}</td>
              <td>{{ rangoTexto('Monocitos (Absoluto)') }}</td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Blastos</td>
              <td>
                <q-input v-model.number="form.blastos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('BLASTOS', form.blastos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.blastos_absoluto = (((form.blastos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.blastos_absoluto" dense outlined type="number" step="0.01" input-class="text-right" />
              </td>
              <td>{{ rangoTexto('BLASTOS') }}</td>
              <td></td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Metamielocitos</td>
              <td>
                <q-input v-model.number="form.metamielocitos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('METAMIELOCITO', form.metamielocitos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.metamielocitos_absoluto = (((form.metamielocitos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.metamielocitos_absoluto" dense outlined type="number" step="0.01" input-class="text-right" />
              </td>
              <td>{{ rangoTexto('METAMIELOCITO') }}</td>
              <td></td>
            </tr>

            <tr v-if="canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS')">
              <td>Eritroblastos</td>
              <td>
                <q-input v-model.number="form.eritroblastos_porcentaje" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('ERITROBLASTOS', form.eritroblastos_porcentaje) ? 'text-negative text-weight-bold' : '']"
                         @update:model-value="
                            form.eritroblastos_absoluto = (((form.eritroblastos_porcentaje || 0) / 100) * (form.globulos_blancos || 0)).toFixed(2);
                          "
                />
              </td>
              <td>
                <q-input v-model.number="form.eritroblastos_absoluto" dense outlined type="number" step="0.01" input-class="text-right" />
              </td>
              <td>{{ rangoTexto('ERITROBLASTOS') }}</td>
              <td></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
              <th class="text-left">Total</th>
              <th class="text-right">
                {{ totalDiferencial.toFixed(2) }}
              </th>
              <th></th>
              <th></th>
              <th></th>
            </tr>
            </tfoot>
          </q-markup-table>

          <!-- MORFOLOGÍA ERITROCITOS -->
<!--          <div class="section-title q-mb-xs">Morfología de eritrocitos</div>-->
<!--          <q-input-->
<!--            v-model="form.morfologia_eritrocitos"-->
<!--            type="textarea"-->
<!--            dense-->
<!--            outlined-->
<!--            autogrow-->
<!--            class="bg-white q-mb-md"-->
<!--            placeholder="Anisocitosis, poiquilocitosis, hipocromía, etc."-->
<!--          />-->

          <!-- COAGULOGRAMA -->
          <div class="section-title q-mb-xs">
            <div class="row">
              <div class="col-12 col-md-3">
                Coagulograma
              </div>
              <div class="col-12 col-md-3">
                <q-select
                  v-model="form.coagulograma_metodo"
                  dense
                  outlined
                  label="Método"
                  :options="['Automática', 'SemiAutomática', 'Manual']"
                />
              </div>
              <div class="col-12 col-md-3">
                <q-select
                  v-model="form.coagulograma_equipo"
                  dense
                  outlined
                  label="Equipo"
                  :options="['Mindray BC 3510','Coatro', 'Otro']"
                />
                <!--                Moundray c-3510-->
              </div>
            </div>
            <!--            methodo equipo-->
          </div>

          <q-markup-table dense flat bordered square class="bg-white q-mb-md">
            <thead>
            <tr>
              <th class="text-left">Prueba</th>
              <th class="text-left">Resultado</th>
              <th class="text-left">Rango de referencia</th>
              <th class="text-left">Unidad</th>
            </tr>
            </thead>

            <tbody>
            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">
              <td>Tiempo de protrombina (TP)</td>
              <td>
<!--                <q-input v-model.number="form.tiempo_protrombina" dense outlined type="number" step="0.01" input-class="text-right" />-->
                <q-select v-model.number="form.tiempo_protrombina" dense outlined input-class="text-right"
                          :options="tiempos"
                          @update:model-value="
        form.actividad_protrombina= tablaTP.find(item => item.segundos === form.tiempo_protrombina)?.porcentaje?.toFixed(2) || null;
        form.inr= tablaTP.find(item => item.segundos === form.tiempo_protrombina)?.inr || null;
"
                />
              </td>
              <td>11 – 15</td>
              <td>seg</td>
            </tr>

            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">
              <td>Actividad de protrombina</td>
              <td>
                <q-input v-model.number="form.actividad_protrombina" dense outlined type="number" step="0.01" input-class="text-right" />
              </td>
              <td>70 – 100</td>
              <td>%</td>
            </tr>

            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">
              <td>INR</td>
              <td>
                <q-input v-model.number="form.inr" dense outlined type="number" step="0.01" input-class="text-right" />
              </td>
              <td>0.8 – 1.2</td>
              <td>-</td>
            </tr>

            <tr v-if="canServicios('ERITROSEDIMENTACIÓN (VSG- VES)')">
              <td>V.S.G</td>
              <td>
                <q-input v-model.number="form.ves" dense outlined type="number" step="0.1" input-class="text-right" />
              </td>
              <td>&lt; 20</td>
              <td>mm/h</td>
            </tr>

            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)'])">
              <td>APTT</td>
              <td>
                <q-input v-model.number="form.aptt" dense outlined type="number" step="0.1" input-class="text-right" />
              </td>
              <td>24 – 35</td>
              <td>seg</td>
            </tr>

            <tr v-if="canServicios('FIBRINÓGENO')">
              <td>Fibrinógeno</td>
              <td>
                <q-input v-model.number="form.fibrinogeno" dense outlined type="number" step="1"
                         :input-class="['text-right', isOutOfRange('FIBRINOGENO', form.fibrinogeno) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('FIBRINOGENO') || '200 - 400 mg/dl' }}</td>
              <td>{{ rangoUnidad('FIBRINOGENO') || 'mg/dl' }}</td>
            </tr>

            <tr v-if="canServicios('FIBRINÓGENO')">
              <td>Dímeros D</td>
              <td>
                <q-input v-model.number="form.dimeros_d" dense outlined type="number" step="0.01"
                         :input-class="['text-right', isOutOfRange('Dimeros D', form.dimeros_d) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>{{ rangoTexto('Dimeros D') || '0 - 0.40 ug/ml' }}</td>
              <td>{{ rangoUnidad('Dimeros D') || 'ug/ml' }}</td>
            </tr>

<!--            <tr v-if="canServicios('RECUENTO DE RETICULOCITOS')">-->
<!--              <td>IPR</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.ipr" dense outlined type="number" step="0.01"-->
<!--                  :input-class="['text-right', isOutOfRange('IPR', form.ipr) ? 'text-negative text-weight-bold' : '']"-->
<!--                />-->
<!--              </td>-->
<!--              <td>-->
<!--                {{ rangoTexto('IPR') }}-->
<!--              </td>-->
<!--              <td>-->
<!--                {{ rangoUnidad('IPR') }}-->
<!--              </td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('RECUENTO DE RETICULOCITOS')">-->
<!--              <td>RETICULOCITOS</td>-->
<!--              <td>-->
<!--                <q-input-->
<!--                  v-model.number="form.ipr2" dense outlined type="number" step="0.01"-->
<!--                  :input-class="['text-right', isOutOfRange('Reticulocitos', form.ipr2) ? 'text-negative text-weight-bold' : '']"-->
<!--                />-->
<!--              </td>-->
<!--              <td>-->
<!--                {{ rangoTexto('Reticulocitos') }}-->
<!--              </td>-->
<!--              <td>-->
<!--                {{ rangoUnidad('Reticulocitos') }}-->
<!--              </td>-->
<!--            </tr>-->
            </tbody>
          </q-markup-table>
          <div class="section-title q-mb-xs">OTROS</div>

          <q-markup-table dense flat bordered square class="bg-white q-mb-md">
            <thead>
            <tr>
              <th class="text-left">Prueba</th>
              <th class="text-left">Resultado</th>
              <th class="text-left">Rango de referencia</th>
              <th class="text-left">Unidad</th>
            </tr>
            </thead>

            <tbody>
<!--            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">-->
<!--              <td>Tiempo de protrombina (TP)</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.tiempo_protrombina" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>11 – 15</td>-->
<!--              <td>seg</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">-->
<!--              <td>Actividad de protrombina</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.actividad_protrombina" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>70 – 100</td>-->
<!--              <td>%</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)'])">-->
<!--              <td>INR</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.inr" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>0.8 – 1.2</td>-->
<!--              <td>-</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)'])">-->
<!--              <td>APTT</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.aptt" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>24 – 35</td>-->
<!--              <td>seg</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('FIBRINÓGENO')">-->
<!--              <td>Fibrinógeno</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.fibrinogeno" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>2.0 – 4.0</td>-->
<!--              <td>g/L</td>-->
<!--            </tr>-->

<!--            <tr v-if="canServicios('ERITROSEDIMENTACIÓN (VSG- VES)')">-->
<!--              <td>V.E.S</td>-->
<!--              <td>-->
<!--                <q-input v-model.number="form.ves" dense outlined type="number" step="0.01" input-class="text-right" />-->
<!--              </td>-->
<!--              <td>&lt; 20</td>-->
<!--              <td>mm/h</td>-->
<!--            </tr>-->
            <tr v-if="canServicios('RECUENTO DE RETICULOCITOS')">
              <td>RETICULOCITOS</td>
              <td>
                <q-input
                  v-model.number="form.ipr2" dense outlined type="number" step="0.1"
                  :input-class="['text-right', isOutOfRange('Reticulocitos', form.ipr2) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>
                {{ rangoTexto('Reticulocitos') }}
              </td>
              <td>
                {{ rangoUnidad('Reticulocitos') }}
              </td>
            </tr>
            <tr v-if="canServicios('RECUENTO DE RETICULOCITOS')">
              <td>IRC</td>
              <td>
                <q-input
                  v-model.number="form.rc" dense outlined type="number" step="0.1" readonly
                  :input-class="['text-right', isOutOfRange('IPR', form.rc) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>
                {{ rangoTexto('RC') }}
              </td>
              <td>
                {{ rangoUnidad('RC') }}
              </td>
            </tr>
            <tr v-if="canServicios('RECUENTO DE RETICULOCITOS')">
              <td>IPR</td>
              <td>
                <q-input
                  v-model.number="form.ipr" dense outlined type="number" step="0.1" readonly
                  :input-class="['text-right', isOutOfRange('IPR', form.ipr) ? 'text-negative text-weight-bold' : '']"
                />
              </td>
              <td>
                {{ rangoTexto('IPR') }}
              </td>
              <td>
                {{ rangoUnidad('IPR') }}
              </td>
            </tr>

            </tbody>
          </q-markup-table>
            <div class="section-title q-mb-xs">FROTIS DE SANGRE PERIFERICA</div>
            <div class="row">
              <div class="col-12 col-md-4">
                <q-input
                  v-if="canServicios(['FROTIS SANGUÍNEO/LEUCOGRAMA','MORFOLOGÍA DE GLÓBULOS ROJOS'])"
                  v-model="form.serie_roja"
                  type="textarea"
                  dense
                  outlined
                  autogrow
                  class="bg-white q-mb-md"
                  label="Serie roja"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-if="canServicios(['FROTIS SANGUÍNEO/LEUCOGRAMA'])"
                  v-model="form.serie_blanca"
                  type="textarea"
                  dense
                  outlined
                  autogrow
                  class="bg-white q-mb-md"
                  label="Serie blanca"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-if="canServicios(['FROTIS SANGUÍNEO/LEUCOGRAMA'])"
                  v-model="form.serie_plaqueta"
                  type="textarea"
                  dense
                  outlined
                  autogrow
                  class="bg-white q-mb-md"
                  label="Serie plaquetaria"
                />
              </div>
            </div>

          <!-- GRUPO SANGUÍNEO -->
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-sm-4" v-if="canServicios('GRUPO SANGUÍNEO Y FACTOR')">
              <div class="section-title q-mb-xs">Grupo sanguíneo</div>
              <q-select
                v-model="form.grupo_sanguineo"
                :options="['O', 'A', 'B', 'AB']"
                dense
                outlined
                clearable
                class="bg-white"
                label="Grupo"
              />
            </div>

            <div class="col-12 col-sm-4" v-if="canServicios('GRUPO SANGUÍNEO Y FACTOR')">
              <div class="section-title q-mb-xs">Factor Rh</div>
              <q-select
                v-model="form.factor_rh"
                :options="['Positivo', 'Negativo']"
                dense
                outlined
                clearable
                class="bg-white"
                label="Rh"
              />
            </div>
            <div class="col-12 q-pt-md">
              <div class="section-title q-mb-xs">Observaciones</div>
            </div>
            <div class="col-12 col-sm-4">
              <q-input
                v-model="form.observaciones"
                type="textarea"
                dense
                outlined
                class="bg-white q-mb-xs"
                label="Observaciones"
              />
            </div>
<!--            <div class="col-12 col-sm-4">-->
<!--&lt;!&ndash;              <q-input v-model="form.metodo" dense outlined class="bg-white q-mb-xs" label="Método (A, M, M/SA, etc.)" />&ndash;&gt;-->
<!--&lt;!&ndash;              Automática SemiAutomática Manual&ndash;&gt;-->
<!--              <q-select-->
<!--                v-model="form.metodo"-->
<!--                :options="['Automática', 'SemiAutomática', 'Manual']"-->
<!--                dense-->
<!--                outlined-->
<!--                clearable-->
<!--                class="bg-white"-->
<!--                label="Método"-->
<!--              />-->
<!--            </div>-->
<!--            <div class="col-12 col-sm-4">-->
<!--              <q-select-->
<!--                v-model="form.equipo"-->
<!--                :options="['Mindray C3510', 'Mindray 3000', 'Otro']"-->
<!--                dense-->
<!--                outlined-->
<!--                clearable-->
<!--                class="bg-white"-->
<!--                label="Equipo"-->
<!--              />-->
<!--            </div>-->

            <div class="col-12 col-sm-4">
<!--              $table->string('equipo_otro', 100)->nullable();-->
<!--              <q-select-->
<!--                v-model="form.equipo"-->
<!--                :options="['Mindray C3510', 'Mindray 5000', 'Otro']"-->
<!--                dense-->
<!--                outlined-->
<!--                clearable-->
<!--                class="bg-white"-->
<!--                label="Equipo"-->
<!--              />-->
              <q-input
                v-if="form.equipo === 'Otro'"
                v-model="form.equipo_otro"
                dense
                outlined
                class="bg-white q-mb-xs"
                label="Especifique otro equipo"
              />
            </div>
          </div>

          <!-- BOTONES -->
          <div class="text-right q-mt-md">
            <q-btn flat label="Cancelar" no-caps class="q-mr-sm" :disable="loading" @click="$router.back()" />
            <q-btn color="primary" icon="save" label="Guardar" type="submit" no-caps :loading="loading" />
          </div>
        </q-form>
      </q-card-section>

      <q-inner-loading :showing="loading && formLoaded">
        <q-spinner size="42px"/>
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import moment from "moment";
import InfoServicio from "components/InfoServicio.vue";

export default {
  name: 'HematologiaPage',
  components: {InfoServicio},
  data () {
    return {
      solicitudId: this.$route.params.id,
      loading: false,
      header: null,
      tablaTP: [
        { segundos: 12.0, porcentaje: 100,  inr: 1.00 },
        { segundos: 12.2, porcentaje: 97,   inr: 1.01 },
        { segundos: 12.4, porcentaje: 94,   inr: 1.03 },
        { segundos: 12.6, porcentaje: 91,   inr: 1.05 },
        { segundos: 12.8, porcentaje: 90,   inr: 1.07 },
        { segundos: 13.0, porcentaje: 88,   inr: 1.09 },
        { segundos: 13.2, porcentaje: 85,   inr: 1.11 },
        { segundos: 13.4, porcentaje: 82,   inr: 1.13 },
        { segundos: 13.6, porcentaje: 80,   inr: 1.15 },
        { segundos: 13.8, porcentaje: 77,   inr: 1.16 },
        { segundos: 14.0, porcentaje: 74,   inr: 1.18 },
        { segundos: 14.2, porcentaje: 71,   inr: 1.20 },
        { segundos: 14.4, porcentaje: 69,   inr: 1.22 },
        { segundos: 14.6, porcentaje: 67,   inr: 1.24 },
        { segundos: 14.8, porcentaje: 66,   inr: 1.25 },
        { segundos: 15.0, porcentaje: 64,   inr: 1.27 },
        { segundos: 15.2, porcentaje: 63,   inr: 1.29 },
        { segundos: 15.4, porcentaje: 62,   inr: 1.31 },
        { segundos: 15.6, porcentaje: 61,   inr: 1.33 },
        { segundos: 15.8, porcentaje: 60,   inr: 1.35 },
        { segundos: 16.0, porcentaje: 59,   inr: 1.37 },
        { segundos: 16.2, porcentaje: 58,   inr: 1.39 },
        { segundos: 16.4, porcentaje: 57,   inr: 1.40 },
        { segundos: 16.6, porcentaje: 56,   inr: 1.43 },
        { segundos: 16.8, porcentaje: 55,   inr: 1.44 },
        { segundos: 17.0, porcentaje: 54,   inr: 1.46 },
        { segundos: 17.2, porcentaje: 53,   inr: 1.48 },
        { segundos: 17.4, porcentaje: 52,   inr: 1.49 },
        { segundos: 17.6, porcentaje: 51,   inr: 1.52 },
        { segundos: 17.8, porcentaje: 50,   inr: 1.54 },
        { segundos: 18.0, porcentaje: 49,   inr: 1.56 },
        { segundos: 18.2, porcentaje: 48,   inr: 1.58 },
        { segundos: 18.4, porcentaje: 47.5, inr: 1.60 },
        { segundos: 18.6, porcentaje: 47,   inr: 1.61 },
        { segundos: 18.8, porcentaje: 46,   inr: 1.63 },
        { segundos: 19.0, porcentaje: 45,   inr: 1.65 },
        { segundos: 19.2, porcentaje: 44.5, inr: 1.67 },
        { segundos: 19.4, porcentaje: 44,   inr: 1.69 },
        { segundos: 19.6, porcentaje: 43,   inr: 1.71 },
        { segundos: 19.8, porcentaje: 42.5, inr: 1.73 },
        { segundos: 20.0, porcentaje: 42,   inr: 1.75 },
        { segundos: 20.2, porcentaje: 41,   inr: 1.77 },
        { segundos: 20.4, porcentaje: 40.5, inr: 1.79 },
        { segundos: 20.6, porcentaje: 40,   inr: 1.81 },
        { segundos: 20.8, porcentaje: 39.5, inr: 1.83 },
        { segundos: 21.0, porcentaje: 39,   inr: 1.85 },
        { segundos: 21.2, porcentaje: 38,   inr: 1.87 },
        { segundos: 21.4, porcentaje: 37.5, inr: 1.88 },
        { segundos: 21.6, porcentaje: 37,   inr: 1.90 },
        { segundos: 21.8, porcentaje: 36.5, inr: 1.92 },
        { segundos: 22.0, porcentaje: 36,   inr: 1.94 },
        { segundos: 22.2, porcentaje: 35.5, inr: 1.96 },
        { segundos: 22.4, porcentaje: 35,   inr: 1.98 },
        { segundos: 22.6, porcentaje: 34.5, inr: 2.00 },
        { segundos: 22.8, porcentaje: 34,   inr: 2.02 },
        { segundos: 23.0, porcentaje: 33.5, inr: 2.04 },
        { segundos: 23.2, porcentaje: 33,   inr: 2.06 },
        { segundos: 23.4, porcentaje: 32.5, inr: 2.08 },
        { segundos: 23.6, porcentaje: 32,   inr: 2.10 },
        { segundos: 23.8, porcentaje: 31.5, inr: 2.12 },
        { segundos: 24.0, porcentaje: 31,   inr: 2.14 },
        { segundos: 24.2, porcentaje: 31,   inr: 2.16 },
        { segundos: 24.4, porcentaje: 30.5, inr: 2.18 },
        { segundos: 24.6, porcentaje: 30,   inr: 2.20 },
        { segundos: 24.8, porcentaje: 29.5, inr: 2.22 },
        { segundos: 25.0, porcentaje: 29,   inr: 2.24 },
        { segundos: 25.2, porcentaje: 28.5, inr: 2.26 },
        { segundos: 25.4, porcentaje: 28,   inr: 2.28 },
        { segundos: 25.6, porcentaje: 27.5, inr: 2.30 },
        { segundos: 25.8, porcentaje: 27.5, inr: 2.32 }
      ],
      tiempos: [],
      formLoaded: false,
      rangos: [],
      form: {
        globulos_rojos: null,
        globulos_blancos: null,
        plaquetas: null,
        hemoglobina: null,
        hematocrito: null,
        vcm: null,
        hbcm: null,
        chcm: null,
        leucocitos_totales: null,

        basofilos_porcentaje: null,
        basofilos_absoluto: null,
        eosinofilos_porcentaje: null,
        eosinofilos_absoluto: null,
        cayados_porcentaje: null,
        cayados_absoluto: null,
        segmentados_porcentaje: null,
        segmentados_absoluto: null,
        linfocitos_porcentaje: null,
        linfocitos_absoluto: null,
        monocitos_porcentaje: null,
        monocitos_absoluto: null,
        blastos_porcentaje: null,
        blastos_absoluto: null,
        metamielocitos_porcentaje: null,
        metamielocitos_absoluto: null,
        eritroblastos_porcentaje: null,
        eritroblastos_absoluto: null,

        morfologia_eritrocitos: '',

        tiempo_protrombina: null,
        actividad_protrombina: null,
        inr: null,
        aptt: null,
        fibrinogeno: null,
        dimeros_d: null,
        ves: null,
        ipr: null,
        rc: null,
        ipr2: null,

        grupo_sanguineo: '',
        factor_rh: '',
        metodo: '',
        equipo: '',
        muestra_rechazada: 'No',
      }
    }
  },
  computed: {
    factorFC(){
      // Factor de corrección = Hematocrito del paciente / Hematocrito normal (45%)
      const ht = parseFloat(this.form.hematocrito)
      if (!isNaN(ht) && ht !== 0) {
        return ht / 45
      } else {
        return 1
      }
    },
    totalDiferencial () {
      const n = (v) => {
        const x = parseFloat(v)
        return Number.isFinite(x) ? x : 0
      }

      return (
        n(this.form.basofilos_porcentaje) +
        n(this.form.eosinofilos_porcentaje) +
        n(this.form.cayados_porcentaje) +
        n(this.form.segmentados_porcentaje) +
        n(this.form.linfocitos_porcentaje) +
        n(this.form.monocitos_porcentaje) +
        n(this.form.blastos_porcentaje) +
        n(this.form.metamielocitos_porcentaje) +
        n(this.form.eritroblastos_porcentaje)
      )
    },
    tiempoTranscurrido () {
      console.log(this.header.fecha_creacion)
      console.log(this.header.fecha_finalizacion)
       // con monent forma h m s
      const start = this.header ? moment(this.header.fecha_creacion) : null
      const end = this.header && this.header.fecha_finalizacion ? moment(this.header.fecha_finalizacion) : moment()
      if (!start || !end) return ''
      const duration = moment.duration(end.diff(start))
      const hours = Math.floor(duration.asHours())
      const minutes = duration.minutes()
      const seconds = duration.seconds()
      return `${hours}h ${minutes}m ${seconds}s`
    }
  },
  watch: {
    'form.globulos_rojos' () {
      this.calculateHematimetricos()
    },
    'form.hemoglobina' () {
      this.calculateHematimetricos()
    },
    'form.hematocrito' () {
      this.calculateHematimetricos()
      this.calculateReticulocitos()
    },
    'form.ipr2' () {
      this.calculateReticulocitos()
    }
  },
  mounted () {
    this.load()
    // tiempos sacar de tablaTP
    this.tiempos = this.tablaTP.map(item => item.segundos)
  },

  methods: {
    calculateHematimetricos () {
      // Fórmulas (Hb en g/dL, Hto en fracción L/L, GR en X10⁶/µL):
      //   VCM  = Hto × 1000 / GR        → fL
      //   HBCM = Hb  × 10   / GR        → pg
      //   CHCM = Hb  / Hto              → g/dL
      const gr = parseFloat(this.form.globulos_rojos)
      const hb = parseFloat(this.form.hemoglobina)
      const ht = parseFloat(this.form.hematocrito)

      this.form.vcm = (!isNaN(ht) && !isNaN(gr) && gr !== 0)
        ? parseFloat(((ht * 1000) / gr).toFixed(1))
        : null

      this.form.hbcm = (!isNaN(hb) && !isNaN(gr) && gr !== 0)
        ? parseFloat(((hb * 10) / gr).toFixed(1))
        : null

      this.form.chcm = (!isNaN(hb) && !isNaN(ht) && ht !== 0)
        ? parseFloat((hb / ht).toFixed(1))
        : null
    },
    getReticulocitoFactorCorreccion () {
      // Tabla estándar (Hto en fracción L/L):
      //   Hto ≥ 0.40 → 1.0
      //   Hto ≥ 0.30 → 1.5
      //   Hto ≥ 0.20 → 2.0
      //   Hto < 0.20 → 2.5
      const ht = parseFloat(this.form.hematocrito)
      if (isNaN(ht)) return null
      if (ht >= 0.40) return 1.0
      if (ht >= 0.30) return 1.5
      if (ht >= 0.20) return 2.0
      return 2.5
    },
    calculateReticulocitos () {
      // IRC (Índice de Reticulocitos Corregido) = RET × (Hto × 100) / 45
      //   con Hto en fracción y RET en %.
      // IPR (Índice Productor de Reticulocitos) = IRC / factor_corrección
      const reticulocitos = parseFloat(this.form.ipr2)
      const hematocrito = parseFloat(this.form.hematocrito)
      const factorCorreccion = this.getReticulocitoFactorCorreccion()

      this.form.rc = (!isNaN(reticulocitos) && !isNaN(hematocrito))
        ? parseFloat((reticulocitos * (hematocrito * 100) / 45).toFixed(2))
        : null

      this.form.ipr = (this.form.rc !== null && factorCorreccion)
        ? parseFloat((this.form.rc / factorCorreccion).toFixed(2))
        : null
    },
    // ========= servicio match =========
    canServicios (can) {
      const norm = (v) => String(v ?? '').replace(/\s+/g, ' ').trim().toLowerCase()
      if (!this.header || !Array.isArray(this.header.servicios)) return false
      const targets = Array.isArray(can) ? can : [can]
      const wanted = targets.map(norm)
      return this.header.servicios.some(s => wanted.includes(norm(s.nombre)))
    },

    // ========= api =========
    async load () {
      try {
        this.loading = true
        this.formLoaded = false
        const { data } = await this.$axios.get(`/hematologia/solicitud/${this.solicitudId}`)
        this.header = data.solicitud || null
        this.form = Object.assign({}, this.form, data.hematologia || {})
        // console.log(data.solicitud)
        const muestra_rechazada = data.solicitud?.muestra_rechazada || 'No'
        const muestra_observacion = data.solicitud?.muestra_observacion || ''
        this.form.muestra_rechazada = muestra_rechazada
        this.form.muestra_observacion = muestra_observacion
        this.rangos = data.rangos || []
        this.calculateHematimetricos()
        this.calculateReticulocitos()
        this.formLoaded = true
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        if (this.$alert && this.$alert.error) this.$alert.error('Error al cargar hematología: ' + msg)
        else console.error(msg)
      } finally {
        this.loading = false
      }
    },

    async save () {
      try {
        this.loading = true
        const res = await this.$axios.post(`/hematologia/solicitud/${this.solicitudId}`, this.form)
        if (this.$alert && this.$alert.success) this.$alert.success('Hematología guardada correctamente')
        else console.log('Hematología guardada correctamente')
        const code = res.data?.code
        if (code) {
          const url = `${this.$axios.defaults.baseURL}/hematologia/solicitud/${code}/pdf`
          window.open(url, '_blank')
        }
        this.$router.push('/analitica')
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        if (this.$alert && this.$alert.error) this.$alert.error('Error al guardar: ' + msg)
        else console.error(msg)
      } finally {
        this.loading = false
      }
    },

    printHematologia () {
      // const id = this.solicitudId
      const code = this.form?.code || ''
      // console.log('Imprimir hematología:', this.form)
      const url = `${this.$axios.defaults.baseURL}/hematologia/solicitud/${code}/pdf`
      window.open(url, '_blank')
    },

    onSubmit () {
      this.save()
    },

    // ========= rangos =========
    normalizeNombre (s) {
      return (s ?? '').toString().toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .trim()
    },
    getRango (nombre) {
      if (!this.rangos || !Array.isArray(this.rangos)) return null
      const target = this.normalizeNombre(nombre)
      return this.rangos.find(r => this.normalizeNombre(r.rango_nombre) === target) || null
    },

    rangoTexto (nombre) {
      const r = this.getRango(nombre)
      if (!r) return ''
      // if (r.rango_minimo !== null && r.rango_maximo !== null) return `${r.rango_minimo} - ${r.rango_maximo}`
      if (r.interpretacion) return r.interpretacion
      return ''
    },

    rangoUnidad (nombre) {
      const r = this.getRango(nombre)
      return r && r.unidad ? r.unidad : ''
    },

    isOutOfRange (nombre, valor) {
      const r = this.getRango(nombre)
      const num = parseFloat(valor)
      if (!r || isNaN(num)) return false
      if (r.rango_minimo !== null && num < r.rango_minimo) return true
      if (r.rango_maximo !== null && num > r.rango_maximo) return true
      return false
    }
  }
}
</script>

<style scoped>
.section-title {
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.badge-estado {
  font-size: 0.7rem;
  text-transform: uppercase;
}
.q-markup-table th {
  font-size: 0.75rem;
  background: #f5f5f5;
}
.q-markup-table td {
  font-size: 0.75rem;
}
.bg-white {
  background-color: #ffffff;
}
</style>
