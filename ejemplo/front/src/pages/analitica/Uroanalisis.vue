<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- ENCABEZADO -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">Uroanálisis</div>
          <div class="text-caption text-grey-7">
            Examen general de orina: examen físico, microscópico y químico.
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
          <q-btn
            class="q-ml-sm"
            outline
            color="primary"
            icon="print"
            label="Imprimir"
            no-caps
            :disable="!formLoaded"
            @click="printPdf"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- DATOS DE SOLICITUD / PACIENTE -->
<!--      <q-card-section v-if="header" class="q-pa-sm">-->
<!--        <div class="row q-col-gutter-sm text-caption">-->
<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Paciente</div>-->
<!--            <div class="text-body2 text-weight-medium">-->
<!--              {{ pacienteNombre }}-->
<!--            </div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Edad: <b>{{ pacienteEdad }}</b>-->
<!--              • Género: <b>{{ pacienteGenero }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Médico solicitante</div>-->
<!--            <div class="text-body2 text-weight-medium">-->
<!--              {{ doctorNombre }}-->
<!--            </div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Fecha solicitud: <b>{{ solicitudFecha }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Solicitud</div>-->
<!--            <div class="row items-center q-col-gutter-xs q-mt-xs">-->
<!--              <div class="col-auto">-->
<!--                <q-chip square color="primary" text-color="white" dense>-->
<!--                  N° {{ solicitudCodigo }}-->
<!--                </q-chip>-->
<!--              </div>-->
<!--              <div class="col-auto">-->
<!--                <q-chip-->
<!--                  square-->
<!--                  outline-->
<!--                  color="primary"-->
<!--                  class="badge-estado"-->
<!--                  dense-->
<!--                >-->
<!--                  {{ solicitudEstado }}-->
<!--                </q-chip>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </q-card-section>-->
      <InfoServicio :header="header" :fecha_fin="form.created_at"/>
      <q-inner-loading :showing="loading && !formLoaded">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>

    <!-- FORMULARIO PRINCIPAL -->
    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <q-form @submit.prevent="onSubmit">
          <!-- MATERIAL / MÉTODO -->
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-sm-6">
              <div class="section-title q-mb-xs">Material de ensayo</div>
              <q-input
                v-model="form.material_ensayo"
                dense
                outlined
                class="bg-white"
                placeholder="ORINA"
              />
            </div>
            <div class="col-12 col-sm-6">
              <div class="section-title q-mb-xs">Método</div>
              <q-input
                v-model="form.metodo"
                dense
                outlined
                class="bg-white"
                placeholder="Manual / Microscópico / Tira reactiva"
              />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <!-- COLUMNA IZQUIERDA: FÍSICO + MICROSCÓPICO + OTROS -->
            <div class="col-12 col-md-7">
              <!-- EXAMEN FÍSICO -->
              <div class="section-title q-mb-xs">
                Examen físico
              </div>
              <q-markup-table
                dense
                flat
                bordered
                square
                class="bg-white q-mb-md"
              >
                <thead>
                <tr>
                  <th class="text-left">Examen</th>
                  <th class="text-left">Res.</th>
                  <th class="text-left">Unidades</th>
                  <th class="text-left">Rango</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>Cantidad</td>
                  <td>
                    <q-input
                      v-model.number="form.cantidad"
                      dense
                      outlined
                      type="number"
                      step="0.1"
                      input-class="text-right"
                    />
                  </td>
                  <td>ml</td>
                  <td>*</td>
                </tr>
                <tr>
                  <td>Color</td>
                  <td>
                    <q-select
                      v-model="form.color"
                      :options="colorOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>Amarillo</td>
                </tr>
                <tr>
                  <td>Olor</td>
                  <td>
                    <q-select
                      v-model="form.olor"
                      :options="olorOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>Sui-generis</td>
                </tr>
                <tr>
                  <td>Aspecto</td>
                  <td>
                    <q-select
                      v-model="form.aspecto"
                      :options="aspectoOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>Límpido</td>
                </tr>
                <tr>
                  <td>Reacción (pH)</td>
                  <td>
<!--                    <q-input-->
<!--                      v-model="form.reaccion"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      placeholder="pH 6.0 ácido"-->
<!--                    />-->
<!--                    select with options from 5.0 to 9-->
                    <q-select
                      v-model="form.reaccion"
                      :options="['pH 5.0 ácido', 'pH 5.5 ácido', 'pH 6.0 ácido', 'pH 6.5 ácido', 'pH 7.0 neutro', 'pH 7.5 alcalino', 'pH 8.0 alcalino', 'pH 8.5 alcalino', 'pH 9.0 alcalino']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>pH 6.0 ácido</td>
                </tr>
                <tr>
                  <td>Densidad</td>
                  <td>
<!--                    <q-input-->
<!--                      v-model.number="form.densidad"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      type="number"-->
<!--                      step="0.001"-->
<!--                      input-class="text-right"-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-center text-[10px]"><option value="1.000">1.000</option><option value="1.005">1.005</option><option value="1.010">1.010</option><option value="1.015">1.015</option><option value="1.020">1.020</option><option value="1.025">1.025</option></select>-->
                    <q-select
                      v-model="form.densidad"
                      :options="['1.000', '1.005', '1.010', '1.015', '1.020', '1.025', '1.030']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>mmHg</td>
                  <td>1.025</td>
                </tr>
                <tr>
                  <td>Espuma</td>
                  <td>
<!--                    <q-select-->
<!--                      v-model="form.espuma"-->
<!--                      :options="espumaOptions"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      emit-value-->
<!--                      map-options-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-center text-[10px]"><option value="Fugaz">Fugaz</option><option value="Blanco Fugaz">Blanco Fugaz</option><option value="Persistente">Persistente</option></select>-->
                    <q-select
                      v-model="form.espuma"
                      :options="['Fugaz', 'Blanco fugaz', 'Persistente','Amarillo fugaz', 'Amarillo persistente','Otros','Blanco persistente']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>Blanco fugaz</td>
                </tr>
                <tr>
                  <td>Sedimento</td>
                  <td>
<!--                    <q-select-->
<!--                      v-model="form.sedimento"-->
<!--                      :options="sedimentoOptions"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      emit-value-->
<!--                      map-options-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-center text-[10px]"><option value="Muy Escaso">Muy Escaso</option><option value="Escaso">Escaso</option><option value="Moderado">Moderado</option><option value="Abundante">Abundante</option></select>-->
                    <q-select
                      v-model="form.sedimento"
                      :options="['Muy Escaso', 'Escaso', 'Moderado', 'Abundante']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>*</td>
                  <td>Escaso</td>
                </tr>
                </tbody>
              </q-markup-table>

              <!-- EXAMEN MICROSCÓPICO (SEDIMENTO) -->
              <div class="section-title q-mb-xs">
                Examen microscópico (sedimento)
              </div>
              <q-markup-table
                dense
                flat
                bordered
                square
                class="bg-white q-mb-md"
              >
                <thead>
                <tr>
                  <th class="text-left">Examen</th>
                  <th class="text-left">Sedimento</th>
                  <th class="text-left">Unidades</th>
                  <th class="text-left">Rango</th>
                </tr>
                </thead>
                <tbody>
<!--                <tr>-->
<!--                  <td>Células epiteliales</td>-->
<!--                  <td>-->
<!--                    <q-input-->
<!--                      v-model="form.celulas_epiteliales"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      input-class="text-right"-->
<!--                      placeholder="0-1"-->
<!--                    />-->
<!--                  </td>-->
<!--                  <td>xcampo/uL</td>-->
<!--                  <td>0-1</td>-->
<!--                </tr>-->
                <tr>
                  <td>Leucocitos</td>
                  <td>
                    <q-input
                      v-model="form.leucocitos"
                      dense
                      outlined
                      input-class="text-right"
                      placeholder="0-1"
                    />
                  </td>
                  <td>xcampo/uL</td>
                  <td>0-1</td>
                </tr>
                <tr>
                  <td>Hematies (Eritrocitos)</td>
                  <td>
                    <q-input
                      v-model="form.hematies"
                      dense
                      outlined
                      input-class="text-right"
                    />
                  </td>
                  <td>xcampo/uL</td>
                  <td>0-1</td>
                </tr>
                <tr>
                  <td>Morfología eritrocitaria</td>
                  <td>
                    <div class="row">
                      <div class="col-6">
<!--                        <q-select-->
<!--                          v-model="form.morfologia_eritrocitaria"-->
<!--                          :options="['NORMAL', 'DISMORFICO', 'ISOMORFICO', 'ESTRELLADO (CRENADOS)', 'FANTASMA', 'SEPTADOS', 'POLIDIVERTICULADOS', 'ESPICULADOS', 'ANULARES', 'MONODIVERTICULARES', 'MIXTOS', 'VACIOS','ESQUISTOCITOS']"-->
<!--                          dense-->
<!--                          outlined-->
<!--                          emit-value-->
<!--                          map-options-->
<!--                        />-->
                        <q-input v-model="form.morfologia_eritrocitaria" dense outlined placeholder="Morfología eritrocitaria" />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.valor_morfologia"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>*</td>
                </tr>
                <tr>
                  <td></td>
                  <td>
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.morfologia_eritrocitaria2"
                          :options="['NORMAL', 'DISMORFICO', 'ISOMORFICO', 'ESTRELLADO (CRENADOS)', 'FANTASMA', 'SEPTADOS', 'POLIDIVERTICULADOS', 'ESPICULADOS', 'ANULARES', 'MONODIVERTICULARES', 'MIXTOS', 'VACIOS','ESQUISTOCITOS']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.valor_morfologia2"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>*</td>
                </tr>
                <tr>
                  <td>Bacterias</td>
                  <td>
<!--                    <q-input-->
<!--                      v-model="form.bacterias"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      placeholder="Escaso / ++ / +++"-->
<!--                    />-->
<!--                    No Observa-->
<!--                    Escaso-->
<!--                    Moderado-->
<!--                    Abundante-->
                    <q-select
                      v-model="form.bacterias"
                      :options="['No Observa', 'Escaso', 'Moderado', 'Abundante']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>xcampo/uL</td>
                  <td>Escaso</td>
                </tr>
                <tr>
                  <td>Filamento mucoide</td>
                  <td>
<!--                    <q-input-->
<!--                      v-model="form.filamento_mucoide"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      placeholder="Escaso / ++ / +++"-->
<!--                    />-->
<!--                    :options="['No Observa', 'Escaso', 'Moderado', 'Abundante']"-->
                    <q-select
                      v-model="form.filamento_mucoide"
                      :options="['No Observa', 'Escaso', 'Moderado', 'Abundante']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                  <td>xcampo/uL</td>
                  <td>*</td>
                </tr>
<!--ALTER TABLE uroanalisis-->
<!--ADD COLUMN cilindros2 VARCHAR(255) NULL,-->
<!--ADD COLUMN celulas_epiteliales2 VARCHAR(255) NULL,-->
<!--ADD COLUMN cristales2 VARCHAR(255) NULL,-->
<!--ADD COLUMN cilindros_valor2 VARCHAR(255) NULL,-->
<!--ADD COLUMN celulas_epiteliales_valor2 VARCHAR(255) NULL,-->
<!--ADD COLUMN cristales_valor2 VARCHAR(255) NULL;-->
                <tr>
                  <td>Cilindros</td>
                  <td>
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.cilindros"
                          :options="['Hialino', 'Granuloso', 'Cereo', 'Leucocitario', 'Eritrocitario', 'Mixto', 'Epithelial', 'Bacteriano']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.valor_cilindros"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
<!--                    form.cilindros valor-->

                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                <tr>
                  <td>
                  </td>
                  <td>
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.cilindros2"
                          :options="['Hialino', 'Granuloso', 'Cereo', 'Leucocitario', 'Eritrocitario', 'Mixto', 'Epithelial', 'Bacteriano']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.cilindros_valor2"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                <tr>
                  <td>
                    Células Epiteliales
                  </td>
                  <td>
<!--                    <q-input-->
<!--                      v-model="form.celulas"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      placeholder="#"-->
<!--                    />-->
<!--                    const opcionesCristales = [-->
<!--                    "Oxalato de calcio dihidratado",-->
<!--                    "Fosfato amorfo",-->
<!--                    "Urato amorfo",-->
<!--                    "Fosfato triple de amonio y magnesio",-->
<!--                    "Ácido Úrico",-->
<!--                    "OTROS"-->
<!--                    ];-->
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.celulas"
                          :options="['Escamosas', 'Transicionales', 'Renal']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.valor_celulas"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                <tr>
                  <td>
                  </td>
                  <td>
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.celulas_epiteliales2"
                          :options="['Escamosas', 'Transicionales', 'Renal']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.celulas_epiteliales_valor2"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                <tr>
                  <td>Cristales</td>
                  <td>
<!--                    <q-input-->
<!--                      v-model="form.cristales"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      placeholder="# / Fosfato amorfo / etc."-->
<!--                    />-->
<!--                    const opcionesCelulas = [-->
<!--                    "Escamosas",-->
<!--                    "Transicionales",-->
<!--                    "Renal"-->
<!--                    ];-->
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.cristales"
                          :options="['Oxalato de calcio dihidratado', 'Fosfato amorfo', 'Urato amorfo', 'Fosfato triple de amonio y magnesio', 'Ácido Úrico', 'OTROS']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.valor_cristales"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                <tr>
                  <td>
                  </td>
                  <td>
                    <div class="row">
                      <div class="col-6">
                        <q-select
                          v-model="form.cristales2"
                          :options="['Oxalato de calcio dihidratado', 'Fosfato amorfo', 'Urato amorfo', 'Fosfato triple de amonio y magnesio', 'Ácido Úrico', 'OTROS']"
                          dense
                          outlined
                          emit-value
                          map-options
                        />
                      </div>
                      <div class="col-6">
                        <q-input
                          v-model="form.cristales_valor2"
                          dense
                          outlined
                          placeholder="valor"
                        />
                      </div>
                    </div>
                  </td>
                  <td>xcampo/uL</td>
                  <td>#</td>
                </tr>
                </tbody>
              </q-markup-table>

              <!-- OTROS EXÁMENES -->
              <div class="section-title q-mb-xs">
                Otros
              </div>
              <q-markup-table
                dense
                flat
                bordered
                square
                class="bg-white q-mb-md"
              >
                <thead>
                <tr>
                  <th class="text-left">Examen</th>
                  <th class="text-left">Res.</th>
                  <th class="text-left">Unidades</th>
                  <th class="text-left">Rango</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>Otros</td>
                  <td>
                    <q-input
                      v-model="form.otros"
                      dense
                      outlined
                      placeholder="NORMAL / Alteraciones..."
                    />
                  </td>
                  <td></td>
                  <td></td>
                </tr>
                </tbody>
              </q-markup-table>
            </div>

            <!-- COLUMNA DERECHA: EXAMEN QUÍMICO -->
            <div class="col-12 col-md-5">
              <div class="section-title q-mb-xs">
                Examen químico
              </div>
              <q-markup-table
                dense
                flat
                bordered
                square
                class="bg-white q-mb-md"
              >
                <thead>
                <tr>
                  <th class="text-left">Examen químico</th>
                  <th class="text-left">Res.</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td>Proteínas</td>
                  <td>
                    <q-select
                      v-model="form.proteinas"
                      :options="proteinasOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Glucosa</td>
                  <td>
                    <q-select
                      v-model="form.glucosa"
                      :options="glucosaOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Sangre</td>
                  <td>
<!--                    <q-select-->
<!--                      v-model="form.sangre"-->
<!--                      :options="sangreOptions"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      emit-value-->
<!--                      map-options-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-right text-[10px]"><option value="NO CONTIENE">NO CONTIENE</option><option value="TRAZAS">TRAZAS</option><option value="CONTIENE + (50 cel./ul)">CONTIENE + (50 cel./ul)</option><option value="CONTIENE ++ (80 cel./ul)">CONTIENE ++ (80 cel./ul)</option><option value="CONTIENE +++ (200 cel./ul)">CONTIENE +++ (200 cel./ul)</option></select>-->
                    <q-select
                      v-model="form.sangre"
                      :options="['NO CONTIENE', 'TRAZAS', 'CONTIENE + ', 'CONTIENE ++ ', 'CONTIENE +++ ']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Cetonas</td>
                  <td>
<!--                    <q-select-->
<!--                      v-model="form.cetonas"-->
<!--                      :options="cetonasOptions"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      emit-value-->
<!--                      map-options-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-right text-[10px]"><option value="NO CONTIENE">NO CONTIENE</option><option value="TRAZAS (5mg/dl)">TRAZAS (5mg/dl)</option><option value="CONTIENE+ (15mg/dl)">CONTIENE+ (15mg/dl)</option><option value="CONTIENE++ (40mg/dl)">CONTIENE++ (40mg/dl)</option><option value="CONTIENE+++ (80mg/dl)">CONTIENE+++ (80mg/dl)</option><option value="CONTIENE++++ (160mg/dl)">CONTIENE++++ (160mg/dl)</option></select>-->
                    <q-select
                      v-model="form.cetonas"
                      :options="['NO CONTIENE', 'TRAZAS','5mg/dl', '15mg/dl', '40mg/dl', '80mg/dl', '160mg/dl']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Bilirrubina</td>
                  <td>
<!--                    <q-select-->
<!--                      v-model="form.bilirrubina"-->
<!--                      :options="bilirrubinaOptions"-->
<!--                      dense-->
<!--                      outlined-->
<!--                      emit-value-->
<!--                      map-options-->
<!--                    />-->
<!--                    <select class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none px-1 py-0 text-right text-[10px]"><option value="NO CONTIENE">NO CONTIENE</option><option value="CONTIENE+(1mg/dl)">CONTIENE+(1mg/dl)</option><option value="CONTIENE++(2mg/dl)">CONTIENE++(2mg/dl)</option><option value="CONTIENE+++(4mg/dl)">CONTIENE+++(4mg/dl)</option></select>-->
                    <q-select
                      v-model="form.bilirrubina"
                      :options="['NO CONTIENE', '1 mg/dl', '2 mg/dl', '>=4 mg/dl']"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Urobilinógeno</td>
                  <td>
                    <q-select
                      v-model="form.urobilinogeno"
                      :options="urobilinogenoOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                <tr>
                  <td>Nitritos</td>
                  <td>
                    <q-select
                      v-model="form.nitritos"
                      :options="nitritosOptions"
                      dense
                      outlined
                      emit-value
                      map-options
                    />
                  </td>
                </tr>
                </tbody>
              </q-markup-table>

              <!-- OBSERVACIONES GENERALES -->
              <div class="section-title q-mb-xs">
                Observaciones
              </div>
              <q-input
                v-model="form.observaciones"
                type="textarea"
                dense
                outlined
                autogrow
                class="bg-white"
                placeholder="Observaciones clínicas, correlación con cuadro, etc."
              />
            </div>
          </div>

          <!-- BOTONES -->
          <div class="text-right q-mt-md">
            <q-btn
              flat
              label="Cancelar"
              no-caps
              class="q-mr-sm"
              :disable="loading"
              @click="$router.back()"
            />
            <q-btn
              color="primary"
              icon="save"
              label="Guardar"
              type="submit"
              no-caps
              :loading="loading"
            />
          </div>
        </q-form>
      </q-card-section>

      <q-inner-loading :showing="loading && formLoaded">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import InfoServicio from "components/InfoServicio.vue";

export default {
  name: 'UroanalisisPage',
  components: {InfoServicio},

  data () {
    return {
      solicitudId: this.$route.params.id,
      loading: false,
      header: null,
      formLoaded: false,

      // Opciones para selects (puedes ajustarlas a tu gusto) agrgar amarrilo pagico
      colorOptions: ['Amarillo', 'Ámbar', 'Rojo', 'Pardo', 'Incoloro', 'Otros', 'Amarillo pajizo'],
      olorOptions: ['Sui-generis', 'Fétido', 'Inodoro', 'Medicamentoso', 'Otros'],
      aspectoOptions: ['Límpido', 'Turbio', 'Opalescente','Ligeramente Opalescente'],
      espumaOptions: ['Ausente', 'Escasa', 'Moderada', 'Abundante'],
      sedimentoOptions: ['Ausente', 'Escaso', 'Moderado', 'Abundante'],

      proteinasOptions: [
        'NO CONTIENE',
        'TRAZAS',
        '15 mg/dl',
        '30 mg/dl',
        '100 mg/dl',
        '300 mg/dl',
        '>=2000 mg/dl'
      ],
      glucosaOptions: [
        'NO CONTIENE',
        'TRAZAS',
        '250 mg/dl',
        '500 mg/dl',
        '1000 mg/dl',
        '>=2000 mg/dl'
      ],
      sangreOptions: ['NO CONTIENE', 'TRAZAS', 'POSITIVO +', 'POSITIVO ++', 'POSITIVO +++'],
      cetonasOptions: ['NO CONTIENE', 'TRAZAS', 'POSITIVO +', 'POSITIVO ++', 'POSITIVO +++'],
      bilirrubinaOptions: [
        'NO CONTIENE',
        'CONTIENE + (1 mg/dl)',
        'CONTIENE ++ (2 mg/dl)',
        'CONTIENE +++ (4 mg/dl)'
      ],
      urobilinogenoOptions: [
        'NORMAL (0.2 UE/dl)',
        '1 UE/dl',
        '2 UE/dl',
        '4 UE/dl',
        '8 UE/dl',
        '>=12 UE/dl'
      ],
      nitritosOptions: ['NEGATIVO', 'POSITIVO'],

      form: {
        otros: '',
        material_ensayo: 'ORINA',
        metodo: 'MANUAL/MICROSCÓPICO/TIRA REACTIVA',
        cantidad: null,
        color: 'Amarillo',
        olor: 'Sui-generis',
        aspecto: 'Límpido',
        reaccion: 'pH 6.0 ácido',
        densidad: null,
        espuma: 'Blanco fugaz',
        sedimento: 'Escaso',
        celulas_epiteliales: null,
        leucocitos: null,
        hematies: null,
        bacterias: null,
        filamento_mucoide: null,
        cilindros: null,
        valor_cilindros: null,
        celulas: null,
        valor_celulas: null,
        cristales: null,
        valor_cristales: null,
        morfologia_eritrocitaria: null,
        valor_morfologia: null,
        proteinas: 'NO CONTIENE',
        glucosa: 'NO CONTIENE',
        sangre: 'NO CONTIENE',
        cetonas: 'NO CONTIENE',
        bilirrubina: 'NO CONTIENE',
        urobilinogeno: 'NORMAL (0.2 UE/dl)',
        nitritos: 'NEGATIVO',
        observaciones: ''
      }
    }
  },

  computed: {
    pacienteNombre () {
      const h = this.header
      if (!h) return '-'
      if (h.paciente && h.paciente.nombre_completo) return h.paciente.nombre_completo
      return h.paciente_nombre || '-'
    },
    pacienteEdad () {
      const h = this.header
      if (!h) return '-'
      if (h.paciente && h.paciente.edad) return h.paciente.edad
      return h.paciente_edad || '-'
    },
    pacienteGenero () {
      const h = this.header
      if (!h) return '-'
      if (h.paciente && h.paciente.genero) return h.paciente.genero
      return h.paciente_genero || '-'
    },
    doctorNombre () {
      const h = this.header
      if (!h) return '-'
      if (h.doctor && h.doctor.nombre) return h.doctor.nombre
      return h.doctor_nombre || '-'
    },
    solicitudFecha () {
      const h = this.header
      if (!h) return '-'
      return h.fecha_solicitud || '-'
    },
    solicitudCodigo () {
      const h = this.header
      if (!h) return '-'
      return h.nro_registro || h.codigo_solicitud || h.id || '-'
    },
    solicitudEstado () {
      const h = this.header
      if (!h) return '-'
      return h.estado || '-'
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    async load () {
      try {
        this.loading = true
        this.formLoaded = false

        const { data } = await this.$axios.get(
          `/uroanalisis/solicitud/${this.solicitudId}`
        )

        this.header = data.solicitud || null
        this.form = Object.assign({}, this.form, data.uroanalisis || {})
        this.formLoaded = true
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        if (this.$alert && this.$alert.error) {
          this.$alert.error('Error al cargar uroanálisis: ' + msg)
        } else {
          console.error(msg)
        }
      } finally {
        this.loading = false
      }
    },

    async save () {
      try {
        this.loading = true
        const res = await this.$axios.post(
          `/uroanalisis/solicitud/${this.solicitudId}`,
          this.form
        )

        if (this.$alert && this.$alert.success) {
          this.$alert.success('Uroanálisis guardado correctamente')
        } else {
          console.log('Uroanálisis guardado correctamente')
        }
        const code = res.data?.code
        if (code) {
          const url = `${this.$axios.defaults.baseURL}/uroanalisis/solicitud/${code}/pdf`
          window.open(url, '_blank')
        }
        this.$router.back()
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        if (this.$alert && this.$alert.error) {
          this.$alert.error('Error al guardar: ' + msg)
        } else {
          console.error(msg)
        }
      } finally {
        this.loading = false
      }
    },
    printPdf () {
      const code = this.form.code
      const url = `${this.$axios.defaults.baseURL}/uroanalisis/solicitud/${code}/pdf`
      window.open(url, '_blank')
    },
    onSubmit () {
      this.save()
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
