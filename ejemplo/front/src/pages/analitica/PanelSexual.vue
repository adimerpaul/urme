<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-weight-bold">Panel ITS (PCR)</div>
          <div class="text-caption text-grey-7">Panel Infecciones de Transmisión Sexual por PCR</div>
        </div>
        <q-space />
        <q-btn flat icon="arrow_back" label="Volver" @click="$router.back()" />
        <q-btn color="primary" icon="save" label="Guardar" @click="save" :loading="loading" />
        <q-btn class="q-ml-sm" outline color="primary" icon="print" label="Imprimir" no-caps
               :disable="!solicitud" @click="printPdf" />
      </q-card-section>

      <q-separator />

<!--      <q-card-section class="q-pa-sm">-->
<!--        <div class="row q-col-gutter-sm text-caption">-->
<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Paciente</div>-->
<!--            <div class="text-body2 text-weight-medium">{{ solicitud?.paciente_nombre }}</div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Edad: <b>{{ solicitud?.paciente_edad }}</b> • Género: <b>{{ solicitud?.paciente_genero }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Médico solicitante</div>-->
<!--            <div class="text-body2 text-weight-medium">{{ solicitud?.doctor_nombre || 'N/A' }}</div>-->
<!--            <div class="text-grey-7 q-mt-xs">Fecha solicitud: <b>{{ solicitud?.fecha_solicitud }}</b></div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Solicitud</div>-->
<!--            <div class="row items-center q-col-gutter-xs q-mt-xs">-->
<!--              <div class="col-auto">-->
<!--                <q-chip square color="primary" text-color="white" dense>N° {{ solicitud?.codigo }}</q-chip>-->
<!--              </div>-->
<!--              <div class="col-auto">-->
<!--                <q-chip square outline color="primary" dense>{{ solicitud?.estado }}</q-chip>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </q-card-section>-->
      <InfoServicio :header="solicitud" />
      <div class="q-pa-xs">
        <div class="text-h6">Codigo interno:</div>
        <q-input v-model="form.numeracion" outlined dense label="Código interno" />
      </div>
      <q-card-section>
        <q-markup-table dense bordered>
          <thead>
          <tr>
            <th>Patógeno</th>
            <th style="width: 220px;">Resultado</th>
            <th style="width: 180px;">Valores de referencia</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.key">
            <td><div class="text-weight-medium">{{ row.label }}</div></td>

            <td>
              <q-select
                v-model="form[row.key]"
                :options="opciones"
                dense outlined
                emit-value map-options
              >
                <template #selected>
                  <q-chip
                    dense square
                    :color="form[row.key] === 'DETECTADO' ? 'dark' : 'grey-3'"
                    :text-color="form[row.key] === 'DETECTADO' ? 'white' : 'dark'"
                  >
                    {{ form[row.key] || '—' }}
                  </q-chip>
                </template>
              </q-select>
            </td>

            <td class="text-center">NO DETECTADO</td>
          </tr>
          </tbody>
        </q-markup-table>

<!--        <div class="q-mt-md">-->
<!--          <div class="text-subtitle2 q-mb-xs">Observaciones</div>-->
<!--          <q-input v-model="form.observaciones" type="textarea" outlined autogrow />-->
<!--        </div>-->
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="text-subtitle2 q-mb-xs">Observaciones</div>
            <q-input v-model="form.observaciones" type="textarea" outlined autogrow dense />
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtitle2 q-mb-xs">Equipo utilizado</div>
            <!--            Mic Cycler y Quant Studio 5-->
            <q-select v-model="form.equipo" :options="['Mic Cycler','Quant Studio 5']" label="Equipo utilizado" outlined dense />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import InfoServicio from "components/InfoServicio.vue";

export default {
  name: 'PanelSexualPage',
  components: {InfoServicio},
  data () {
    return {
      loading: false,
      opciones: ['NO DETECTADO', 'DETECTADO'],
      solicitud: null,
      form: {
        chlamydia_trachomatis: 'NO DETECTADO',
        mycoplasma_genitalium: 'NO DETECTADO',
        neisseria_gonorrhoeae: 'NO DETECTADO',
        trichomonas_vaginalis: 'NO DETECTADO',
        ureaplasma_urealyticum: 'NO DETECTADO',
        ureaplasma_parvum: 'NO DETECTADO',
        mycoplasma_hominis: 'NO DETECTADO',
        hsv_1: 'NO DETECTADO',
        hsv_2: 'NO DETECTADO',
        treponema_pallidum: 'NO DETECTADO',
        candida_albicans: 'NO DETECTADO',
        gardnerella_vaginalis: 'NO DETECTADO',
        observaciones: ''
      },
      rows: [
        { key: 'chlamydia_trachomatis', label: 'Chlamydia trachomatis' },
        { key: 'mycoplasma_genitalium', label: 'Mycoplasma genitalium' },
        { key: 'neisseria_gonorrhoeae', label: 'Neisseria gonorrhoeae' },
        { key: 'trichomonas_vaginalis', label: 'Trichomonas vaginalis' },
        { key: 'ureaplasma_urealyticum', label: 'Ureaplasma urealyticum' },
        { key: 'ureaplasma_parvum', label: 'Ureaplasma parvum' },
        { key: 'mycoplasma_hominis', label: 'Mycoplasma hominis' },
        { key: 'hsv_1', label: 'HSV-1' },
        { key: 'hsv_2', label: 'HSV-2' },
        { key: 'treponema_pallidum', label: 'Treponema pallidum' },
        { key: 'candida_albicans', label: 'Candida albicans' },
        { key: 'gardnerella_vaginalis', label: 'Gardnerella vaginalis' }
      ]
    }
  },
  mounted () { this.load() },
  methods: {
    async load () {
      this.loading = true
      const { data } = await this.$axios.get(`/panel-sexual/solicitud/${this.$route.params.id}`)
      this.form = data.panel
      this.solicitud = data.solicitud
      this.loading = false
    },
    async save () {
      this.loading = true
      await this.$axios.post(`/panel-sexual/solicitud/${this.$route.params.id}`, this.form)
      this.$alert.success('Resultado guardado correctamente')
      this.loading = false
      this.$router.back()
    },
    printPdf () {
      const code = this.form.code
      const url = `${this.$axios.defaults.baseURL}/panel-sexual/solicitud/${code}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>
