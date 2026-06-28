<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-weight-bold">
            Virus del Papiloma Humano (PCR)
          </div>
          <div class="text-caption text-grey-7">
            Detección de HPV de alto riesgo por PCR en tiempo real
          </div>
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
<!--            <div class="text-body2 text-weight-medium">-->
<!--              {{ solicitud?.paciente_nombre }}-->
<!--            </div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Edad: <b>{{ solicitud?.paciente_edad }}</b> • Género:-->
<!--              <b>{{ solicitud?.paciente_genero }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Médico solicitante</div>-->
<!--            <div class="text-body2 text-weight-medium">-->
<!--              {{ solicitud?.doctor_nombre || 'N/A' }}-->
<!--            </div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Fecha solicitud:-->
<!--              <b>{{ solicitud?.fecha_solicitud }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Solicitud</div>-->
<!--            <div class="row items-center q-col-gutter-xs q-mt-xs">-->
<!--              <div class="col-auto">-->
<!--                <q-chip square color="primary" text-color="white" dense>-->
<!--                  N° {{ solicitud?.codigo }}-->
<!--                </q-chip>-->
<!--              </div>-->
<!--              <div class="col-auto">-->
<!--                <q-chip square outline color="primary" class="badge-estado" dense>-->
<!--                  {{ solicitud?.estado }}-->
<!--                </q-chip>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </q-card-section>-->
      <InfoServicio :header="solicitud" />
<!--      <pre>{{form}}</pre>-->
<!--      codigo_muestra-->
      <div class="q-pa-xs">
        <div class="text-h6">Codigo interno:</div>
        <q-input v-model="form.numeracion" outlined dense label="Código interno" />
      </div>

      <q-card-section>
        <q-markup-table dense bordered>
          <thead>
          <tr>
            <th>Determinación</th>
            <th>Resultado</th>
            <th>Valor de referencia</th>
          </tr>
          </thead>
          <tbody>
<!--          "hpv_16": "NO DETECTADO",-->
<!--          "hpv_18": "DETECTADO",-->
<!--          "hpv_45": null,-->
<!--          "hpv_26": null,-->
<!--          "hpv_31": null,-->
<!--          "hpv_33": null,-->
<!--          "hpv_35": null,-->
<!--          "hpv_39": null,-->
<!--          "hpv_51": null,-->
<!--          "hpv_52": null,-->
<!--          "hpv_53": null,-->
<!--          "hpv_56": null,-->
<!--          "hpv_58": null,-->
<!--          "hpv_59": null,-->
<!--          "hpv_66": null,-->
<!--          "hpv_67": null,-->
<!--          "hpv_68": null,-->
<!--          "hpv_69": null,-->
<!--          "hpv_70": null,-->
<!--          "hpv_73": null,-->
<!--          "hpv_82": null,-->
<!--          "hpv_97": null,-->
          <tr>
            <td>HPV alto riesgo</td>
            <td>
              <q-select v-model="form.hpv_alto_riesgo"
                        :options="opciones" dense outlined clearable />
            </td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 16</td>
            <td><q-select v-model="form.hpv_16" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 18</td>
            <td><q-select v-model="form.hpv_18" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 31</td>
            <td><q-select v-model="form.hpv_31" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 33</td>
            <td><q-select v-model="form.hpv_33" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 35</td>
            <td><q-select v-model="form.hpv_35" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 39</td>
            <td><q-select v-model="form.hpv_39" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 45</td>
            <td><q-select v-model="form.hpv_45" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 51</td>
            <td><q-select v-model="form.hpv_51" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 52</td>
            <td><q-select v-model="form.hpv_52" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 53</td>
            <td><q-select v-model="form.hpv_53" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 56</td>
            <td><q-select v-model="form.hpv_56" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 58</td>
            <td><q-select v-model="form.hpv_58" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 59</td>
            <td><q-select v-model="form.hpv_59" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 66</td>
            <td><q-select v-model="form.hpv_66" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 67</td>
            <td><q-select v-model="form.hpv_67" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 68</td>
            <td><q-select v-model="form.hpv_68" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 69</td>
            <td><q-select v-model="form.hpv_69" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 70</td>
            <td><q-select v-model="form.hpv_70" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 73</td>
            <td><q-select v-model="form.hpv_73" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 82</td>
            <td><q-select v-model="form.hpv_82" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          <tr>
            <td>HPV 97</td>
            <td><q-select v-model="form.hpv_97" :options="opciones" dense outlined clearable /></td>
            <td>NO DETECTADO</td>
          </tr>
          </tbody>
        </q-markup-table>

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
<!--        <div class="q-mt-md">-->
<!--        </div>-->
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import InfoServicio from "components/InfoServicio.vue";

export default {
  name: 'PapilomaHumanoPage',
  components: {InfoServicio},
  data () {
    return {
      loading: false,
      opciones: ['NO DETECTADO', 'DETECTADO'],
      solicitud: null,
      form: {
        hpv_alto_riesgo: null,
        // "hpv_16": "NO DETECTADO",
        // "hpv_18": "DETECTADO",
        // "hpv_45": null,
        // "hpv_26": null,
        // "hpv_31": null,
        // "hpv_33": null,
        // "hpv_35": null,
        // "hpv_39": null,
        // "hpv_51": null,
        // "hpv_52": null,
        // "hpv_53": null,
        // "hpv_56": null,
        // "hpv_58": null,
        // "hpv_59": null,
        // "hpv_66": null,
        // "hpv_67": null,
        // "hpv_68": null,
        // "hpv_69": null,
        // "hpv_70": null,
        // "hpv_73": null,
        // "hpv_82": null,
        // "hpv_97": null,
        hpv_16: null,
        hpv_18: null,
        hpv_45: null,
        hpv_31: null,
        hpv_33: null,
        hpv_35: null,
        hpv_39: null,
        hpv_51: null,
        hpv_52: null,
        hpv_53: null,
        hpv_56: null,
        hpv_58: null,
        hpv_59: null,
        hpv_66: null,
        hpv_67: null,
        hpv_68: null,
        hpv_69: null,
        hpv_70: null,
        hpv_73: null,
        hpv_82: null,
        hpv_97: null,
        observaciones: ''
      }
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    async load () {
      this.loading = true
      const { data } = await this.$axios.get(
        `/papiloma-humano/solicitud/${this.$route.params.id}`
      )
      // Object.assign(this.form, data.papiloma)
      this.form = data.papiloma
      this.solicitud = data.solicitud

      this.loading = false
    },
    async save () {
      this.loading = true
      await this.$axios.post(
        `/papiloma-humano/solicitud/${this.$route.params.id}`,
        this.form
      )
      this.$alert.success('Resultado guardado correctamente')
      this.loading = false
      // this.ir a tra o ir a a analitica
      this.$router.back()
    },
    printPdf() {
      const code = this.form.code
      const url = `${this.$axios.defaults.baseURL}/papiloma-humano/solicitud/${code}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>
