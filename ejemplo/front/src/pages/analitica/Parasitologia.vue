<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- ENCABEZADO -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">Parasitología</div>
          <div class="text-caption text-grey-7">
            Macroscopía + coproparasitológico (simple / seriado)
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

      <!-- DATOS DE LA SOLICITUD -->
<!--      <q-card-section v-if="header" class="q-pa-sm">-->
<!--        <div class="row q-col-gutter-sm text-caption">-->
<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Paciente</div>-->
<!--            <div class="text-body2 text-weight-medium">{{ pacienteNombre }}</div>-->
<!--            <div class="text-grey-7 q-mt-xs">-->
<!--              Edad: <b>{{ pacienteEdad }}</b> • Género: <b>{{ pacienteGenero }}</b>-->
<!--            </div>-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <div class="text-grey-7">Médico solicitante</div>-->
<!--            <div class="text-body2 text-weight-medium">{{ doctorNombre }}</div>-->
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
<!--                <q-chip square outline color="primary" dense class="badge-estado">-->
<!--                  {{ solicitudEstado }}-->
<!--                </q-chip>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </q-card-section>-->
      <InfoServicio :header="header" :fecha_fin="form.created_at" />

      <q-inner-loading :showing="loading && !formLoaded">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>

    <!-- FORMULARIO -->
    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <q-form @submit.prevent="onSubmit">



          <div class="row q-col-gutter-sm">
            <!-- MACROSCOPÍA -->
            <div class="col-12 col-md-4">
              <q-card flat bordered class="bg-white">
                <q-card-section class="q-pa-sm">
                  <div class="section-title q-mb-sm">
                    <q-icon name="biotech" class="q-mr-xs" />
                    MACROSCOPÍA
                  </div>

                  <q-select v-model="form.olor" dense outlined label="Olor"
                            :options="['CARACTERÍSTICO','FÉTIDO','ÁCIDO','OTRO']" class="q-mb-sm" />
                  <q-select v-model="form.color" dense outlined label="Color"
                            :options="['MARRÓN','AMARILLO','VERDOSO','NEGRUZCO','OTRO']" class="q-mb-sm" />
                  <q-select v-model="form.consistencia" dense outlined label="Consistencia"
                            :options="['SÓLIDA','LÍQUIDA','PASTOSA','DIARREICA','DIARREICA CON MOCO','OTROS']" class="q-mb-sm" />
<!--                            CONSISTENCIA: SOLIDA, LIQUIDA, PASTOSA, DIARREICA, DIARREICA CON MOCO, OTROS-->
<!--                            :options="['SOLIDA','LIQUIDA','PASTOSA','DIARREICA','DIARREICA CON MOCO','OTROS']" class="q-mb-sm" />-->


                  <q-input v-model="form.otros" dense outlined type="textarea" autogrow label="Otros (Opcional)" class="q-mb-sm" />
                  <q-select v-model="form.bacterias" dense outlined label="Bacterias"
                            :options="['ESCASAS','MODERADAS','ABUNDANTE','NO SE OBSERVA']" />
                </q-card-section>
              </q-card>
            </div>

            <!-- COPROPARASITOLÓGICO -->
            <div class="col-12 col-md-8">
              <q-card flat bordered class="bg-white">
                <q-card-section class="q-pa-sm">
                  <div class="row items-center q-col-gutter-sm q-mb-sm">
                    <div class="col">
                      <div class="section-title">
                        <q-icon name="science" class="q-mr-xs" />
                        COPROPARASITOLÓGICO
                      </div>
                    </div>
                    <div class="col-auto">
<!--                      <div class="row q-col-gutter-sm q-mb-sm">-->
<!--                        <div class="col-12 col-md-3">-->
                          <q-select
                            v-model="form.tipo"
                            :options="['SIMPLE', 'SERIADO']"
                            dense
                            outlined
                            label="Tipo"
                            @update:model-value="onTipoChange"
                          />
<!--                        </div>-->
<!--                      </div>-->
<!--                      <q-chip square color="primary" text-color="white" dense>-->
<!--                        {{ form.tipo }}-->
<!--                      </q-chip>-->
                    </div>
                  </div>

                  <!-- SIMPLE -->
                  <div v-if="form.tipo === 'SIMPLE'" class="q-mb-sm">
                    <q-input
                      v-model="form.descripcion_muestra"
                      dense
                      outlined
                      type="textarea"
                      autogrow
                      label="Descripción muestra"
                    />
                  </div>

                  <!-- SERIADO -->
                  <div v-else class="q-mb-sm">
                    <q-input v-model="form.descripcion_muestra_1" dense outlined type="textarea" autogrow label="Descripción muestra 1" class="q-mb-sm" />
                    <q-input v-model="form.descripcion_muestra_2" dense outlined type="textarea" autogrow label="Descripción muestra 2" class="q-mb-sm" />
                    <q-input v-model="form.descripcion_muestra_3" dense outlined type="textarea" autogrow label="Descripción muestra 3" />
                  </div>

                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-md-3">
                      <q-select v-model="form.sangre_oculta" outlined label="Sangre oculta"
                                :options="['NEGATIVO','POSITIVO']" dense/>
                    </div>

                    <div class="col-12 col-md-3">
                      <q-select v-model="form.prueba_rapida_rotavirus" outlined label="Prueba rápida Rotavirus"
                                :options="['NEGATIVO','POSITIVO']" dense />
                    </div>

                    <div class="col-12 col-md-3">
<!--                      <q-input v-model="form.moco_fecal_positivo" outlined label="Moco fecal" />-->
                      <q-select v-model="form.moco_fecal_positivo" outlined label="Moco fecal"
                                :options="['NEGATIVO','POSITIVO']" dense/>
                    </div>
                    <div class="col-12 col-md-3">
                      <q-input v-model="form.moco_fecal" outlined label="Moco fecal Descripcion" dense/>
                    </div>

                    <div class="col-12 col-md-4">
                      <q-select v-model="form.test_benedict" outlined label="Test de Benedict"
                                                      :options="['NEGATIVO','POSITIVO']" dense/>
                      <!--                                :options="['pH 5.0 ácido','pH 5.5 ácido','pH 6.0 ácido','pH 6.5 ácido','pH 7.0 neutro','pH 7.5 alcalino','pH 8.0 alcalino','pH 9.0 alcalino']" />-->
                      <!--                      para TEST DE BENEDICT poner las siguientes reacciones:                              pH 	5.0 ácido-->
                      <!--                      pH 	5.5 ácido-->
                      <!--                      pH 	6.0 ácido-->
                      <!--                      pH 	6.5 ácido-->
                      <!--                      pH 	7.0 neutro-->
                      <!--                      pH 	7.5 alcalino-->
                      <!--                      pH 	8.0 alcalino-->
                      <!--                      pH 	9.0 alcalino-->
                    </div>

                    <div class="col-12 col-md-4">
                      <q-select v-model="form.reaccion" outlined label="Reacción"
                                :options="['pH 5.0 ácido','pH 5.5 ácido','pH 6.0 ácido','pH 6.5 ácido','pH 7.0 neutro','pH 7.5 alcalino','pH 8.0 alcalino','pH 9.0 alcalino']"
                        dense
                      />
                    </div>
                  </div>

                  <q-separator spaced />
                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-md-12">
                      <q-select v-model="form.otros_examenes" dense outlined label="Otros exámenes"
                                :options="['AMEBAS EN FRESCO','EXAMEN DIRECTO PARA LEISHMANIA','GOTA GRUESA PARA MALARIA','MICROMÉTODO PARA CHAGAS','TÉCNICA DE GRAHAM','OTROS']" />
<!--                      <template v-if="form.otros_examenes === 'OTROS'">-->
<!--                        <br>-->
<!--                        <q-input-->
<!--                          v-model="form.otros_examenes_otros"-->
<!--                          dense-->
<!--                          outlined-->
<!--                          type="textarea"-->
<!--                          autogrow-->
<!--                          label="Especifique otros exámenes"-->
<!--                        />-->
<!--                      </template>-->
                    </div>
                    <div class="col-12 col-md-12">
<!--                      otros_examenes_descripcion-->
                      <q-input
                        v-model="form.otros_examenes_otros"
                        dense
                        outlined
                        type="textarea"
                        label="Descripción otros exámenes"
                      />
                    </div>
<!--                    <div class="col-12 col-md-12" v-if="form.otros_examenes === 'OTROS'">-->
<!--                      &lt;!&ndash;                      otros_examenes_descripcion&ndash;&gt;-->
<!--                      <q-input-->
<!--                        v-model="form.otros_resultados"-->
<!--                        dense-->
<!--                        outlined-->
<!--                        type="textarea"-->
<!--                        label="Resultados otros exámenes"-->
<!--                      />-->
<!--                    </div>-->
                  </div>

                </q-card-section>
              </q-card>
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
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import InfoServicio from "components/InfoServicio.vue";

export default {
  name: 'ParasitologiaPage',
  components: {InfoServicio},
  data () {
    return {
      solicitudId: this.$route.params.id,
      loading: false,
      header: null,
      formLoaded: false,
      form: {
        tipo: 'SIMPLE',

        olor: null,
        color: null,
        consistencia: null,
        bacterias: null,
        otros: null,

        descripcion_muestra: null,
        descripcion_muestra_1: null,
        descripcion_muestra_2: null,
        descripcion_muestra_3: null,

        sangre_oculta: null,
        prueba_rapida_rotavirus: null,
        moco_fecal: null,
        test_benedict: null,
        reaccion: null,
        otros_examenes: null
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
    onTipoChange () {
      if (this.form.tipo === 'SIMPLE') {
        this.form.descripcion_muestra_1 = null
        this.form.descripcion_muestra_2 = null
        this.form.descripcion_muestra_3 = null
      } else {
        this.form.descripcion_muestra = null
      }
    },
    async load () {
      try {
        this.loading = true
        this.formLoaded = false
        const { data } = await this.$axios.get(`/parasitologia/solicitud/${this.solicitudId}`)
        this.header = data.solicitud || null
        this.form = Object.assign({}, this.form, data.parasitologia || {})
        if (!this.form.tipo) this.form.tipo = 'SIMPLE'
        this.formLoaded = true
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        this.$alert?.error ? this.$alert.error('Error al cargar parasitología: ' + msg) : console.error(msg)
      } finally {
        this.loading = false
      }
    },
    async save () {
      try {
        this.loading = true
        const res = await this.$axios.post(`/parasitologia/solicitud/${this.solicitudId}`, this.form)
        this.$alert?.success ? this.$alert.success('Parasitología guardada correctamente') : console.log('OK')
        const code = res.data?.code
        if (code) {
          const url = `${this.$axios.defaults.baseURL}/parasitologia/solicitud/${code}/pdf`
          window.open(url, '_blank')
        }
        this.$router.back()
      } catch (e) {
        const msg = e.response?.data?.message || e.message
        this.$alert?.error ? this.$alert.error('Error al guardar: ' + msg) : console.error(msg)
      } finally {
        this.loading = false
      }
    },
    onSubmit () {
      this.save()
    },
    printPdf () {
      const code = this.form.code
      const url = `${this.$axios.defaults.baseURL}/parasitologia/solicitud/${code}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>

<style scoped>
.section-title {
  font-size: 0.9rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.badge-estado {
  font-size: 0.7rem;
  text-transform: uppercase;
}
.bg-white {
  background: #fff;
}
</style>
