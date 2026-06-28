<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-weight-bold">Panel Respiratorio (PCR)</div>
          <div class="text-caption text-grey-7">Resultados Panel Respiratorio por PCR en tiempo real</div>
        </div>
        <q-space />
        <q-btn flat icon="arrow_back" label="Volver" @click="$router.back()" />
        <q-btn color="primary" icon="save" label="Guardar" @click="save" :loading="loading" />
        <q-btn
          class="q-ml-sm"
          outline
          color="primary"
          icon="print"
          label="Imprimir"
          no-caps
          :disable="!solicitud"
          @click="printPdf"
        />
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
<!--                <q-chip square outline color="primary" class="badge-estado" dense>{{ solicitud?.estado }}</q-chip>-->
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
            <td>
              <div class="text-weight-medium">{{ row.label }}</div>
            </td>

            <td>
              <q-select
                v-model="form[row.key]"
                :options="opciones"
                dense
                outlined
                emit-value
                map-options
              >
                <template #selected>
                  <q-chip
                    dense
                    square
                    :color="form[row.key] === 'DETECTADO' ? 'negative' : 'grey-3'"
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
  name: 'PanelRespiratorioPage',
  components: {InfoServicio},
  data () {
    return {
      loading: false,
      opciones: ['NO DETECTADO', 'DETECTADO'],
      solicitud: null,
      form: {
        vrs_ab: 'NO DETECTADO',
        influenza_b: 'NO DETECTADO',
        influenza_a: 'NO DETECTADO',
        sars_cov_2: 'NO DETECTADO',
        streptococcus_pyogenes: 'NO DETECTADO',
        adenovirus: 'NO DETECTADO',
        rhinovirus: 'NO DETECTADO',
        coronavirus_229e_oc43: 'NO DETECTADO',
        parainfluenza_1_2: 'NO DETECTADO',
        coronavirus_nl63_hku1: 'NO DETECTADO',
        parainfluenza_3_4: 'NO DETECTADO',
        haemophilus_influenzae: 'NO DETECTADO',
        bordetella_pertussis: 'NO DETECTADO',
        streptococcus_pneumoniae: 'NO DETECTADO',
        bocavirus: 'NO DETECTADO',
        mycoplasma_pneumoniae: 'NO DETECTADO',
        metapneumovirus: 'NO DETECTADO',
        enterovirus: 'NO DETECTADO',
        legionella_pneumophila: 'NO DETECTADO',
        observaciones: ''
      },
      rows: [
        { key: 'vrs_ab', label: 'Virus Sincitial Respiratorio A, B' },
        { key: 'influenza_b', label: 'Influenza B' },
        { key: 'influenza_a', label: 'Influenza A' },
        { key: 'sars_cov_2', label: 'SARS CoV-2' },
        { key: 'streptococcus_pyogenes', label: 'Streptococcus pyogenes' },
        { key: 'adenovirus', label: 'Adenovirus' },
        { key: 'rhinovirus', label: 'Rhinovirus' },
        { key: 'coronavirus_229e_oc43', label: 'Coronavirus 229E/OC43' },
        { key: 'parainfluenza_1_2', label: 'Parainfluenza 1,2' },
        { key: 'coronavirus_nl63_hku1', label: 'Coronavirus NL63/HKU1' },
        { key: 'parainfluenza_3_4', label: 'Parainfluenza 3,4' },
        { key: 'haemophilus_influenzae', label: 'Haemophilus influenzae' },
        { key: 'bordetella_pertussis', label: 'Bordetella pertussis' },
        { key: 'streptococcus_pneumoniae', label: 'Streptococcus pneumoniae' },
        { key: 'bocavirus', label: 'Bocavirus' },
        { key: 'mycoplasma_pneumoniae', label: 'Mycoplasma pneumoniae' },
        { key: 'metapneumovirus', label: 'Metapneumovirus' },
        { key: 'enterovirus', label: 'Enterovirus' },
        { key: 'legionella_pneumophila', label: 'Legionella pneumophila' }
      ]
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    async load () {
      this.loading = true
      const { data } = await this.$axios.get(`/panel-respiratorio/solicitud/${this.$route.params.id}`)
      this.form = data.panel
      this.solicitud = data.solicitud
      this.loading = false
    },
    async save () {
      this.loading = true
      await this.$axios.post(`/panel-respiratorio/solicitud/${this.$route.params.id}`, this.form)
      this.$alert.success('Resultado guardado correctamente')
      this.loading = false
      // this. ir a traz
      this.$router.back()
    },
    printPdf () {
      const code = this.form.code
      const url = `${this.$axios.defaults.baseURL}/panel-respiratorio/solicitud/${code}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>
