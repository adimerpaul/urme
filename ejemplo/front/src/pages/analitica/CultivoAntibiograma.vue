<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered>
      <q-card-section class="row items-center">
        <div>
          <div class="text-h6 text-weight-bold">Cultivo y Antibiograma</div>
          <div class="text-caption text-grey-7">Microbiología · Resultado + sensibilidad (S/R/I)</div>
        </div>
        <q-space />
        <q-btn flat icon="arrow_back" label="Volver" @click="$router.back()" />
        <q-btn color="primary" icon="save" label="Guardar" @click="save" :loading="loading" />
        <q-btn class="q-ml-sm" outline color="primary" icon="print" label="Imprimir" no-caps :disable="!solicitud" @click="printPdf" />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-sm">
        <div class="row q-col-gutter-sm text-caption">
          <div class="col-12 col-md-4">
            <div class="text-grey-7">Paciente</div>
            <div class="text-body2 text-weight-medium">{{ solicitud?.paciente_nombre }}</div>
            <div class="text-grey-7 q-mt-xs">
              Edad: <b>{{ solicitud?.paciente_edad }}</b> • Género: <b>{{ solicitud?.paciente_genero }}</b>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="text-grey-7">Médico solicitante</div>
            <div class="text-body2 text-weight-medium">{{ solicitud?.doctor_nombre || 'N/A' }}</div>
            <div class="text-grey-7 q-mt-xs">Fecha solicitud: <b>{{ solicitud?.fecha_solicitud }}</b></div>
          </div>

          <div class="col-12 col-md-4">
            <div class="text-grey-7">Solicitud</div>
            <div class="row items-center q-col-gutter-xs q-mt-xs">
              <div class="col-auto">
                <q-chip square color="primary" text-color="white" dense>N° {{ solicitud?.codigo }}</q-chip>
              </div>
              <div class="col-auto">
                <q-chip square outline color="primary" dense>{{ solicitud?.estado }}</q-chip>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pa-sm">
<!--        <div class="row q-col-gutter-sm">-->
<!--          <div class="col-12 col-md-4">-->
<!--            <q-input v-model="form.numero_identificacion" dense outlined label="Número de identificación (SUS)" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-4">-->
<!--            <q-input v-model="form.codigo_microbiologia" dense outlined label="Código microbiología" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-4">-->
<!--            <q-input v-model="form.institucion" dense outlined label="Institución" />-->
<!--          </div>-->

<!--          <div class="col-12 col-md-4">-->
<!--            <q-input v-model="form.cultivo_solicitado" dense outlined label="Cultivo solicitado (Urocultivo, etc.)" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-4">-->
<!--            <q-select v-model="form.localizacion" :options="['Interno','Externo']" dense outlined label="Localización" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-4">-->
<!--            <q-input v-model="form.servicio" dense outlined label="Servicio" />-->
<!--          </div>-->

<!--          <div class="col-12 col-md-3">-->
<!--            <q-input v-model="form.sala" dense outlined label="Sala" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input v-model="form.cama" dense outlined label="Cama" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input v-model="form.fecha_ingreso" type="date" dense outlined label="Fecha ingreso" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-3">-->
<!--            <q-input v-model="form.fecha_salida" type="date" dense outlined label="Fecha salida" />-->
<!--          </div>-->

<!--          <div class="col-12 col-md-6">-->
<!--            <q-input v-model="form.tincion_gram" dense outlined label="Tinción Gram (100x)" placeholder="Ej: Bacilos Gram(-) Abundante" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-6">-->
<!--            <q-input v-model="form.conteo_colonia" dense outlined label="Conteo de colonia" placeholder="Ej: > 100.000 UFC/ml" />-->
<!--          </div>-->

<!--          <div class="col-12 col-md-6">-->
<!--            <q-input v-model="form.microorganismo" dense outlined label="Microorganismo identificado" placeholder="Ej: Escherichia coli" />-->
<!--          </div>-->
<!--          <div class="col-12 col-md-6">-->
<!--            <q-input v-model="form.mecanismo_resistencia" dense outlined label="Mecanismo de resistencia (opcional)" placeholder="Ej: Positivo" />-->
<!--          </div>-->
<!--        </div>-->

<!--        <q-separator class="q-my-md" />-->

        <div class="row items-center q-col-gutter-sm">
          <div class="col">
            <div class="text-subtitle2 text-weight-bold">Antibiograma</div>
            <div class="text-caption text-grey-7">Marca S / R / I por antibiótico</div>
          </div>

          <div class="col-auto">
            <q-btn color="primary" no-caps icon="add" label="Agregar fila" @click="addRow" />
          </div>
        </div>

        <q-markup-table dense bordered class="q-mt-sm">
          <thead>
          <tr>
            <th>Antibiótico</th>
            <th style="width: 160px;" class="text-center">Estado</th>
            <th style="width: 60px;" class="text-center">Acción</th>
          </tr>
          </thead>

          <tbody>
          <tr v-for="(r, i) in form.antibiograma" :key="i">
            <td>
              <q-input v-model="r.antibiotico" dense outlined placeholder="Ej: Cefotaxima" />
            </td>

            <td class="text-center">
              <q-select
                v-model="r.estado"
                :options="estadoOptions"
                dense
                outlined
                emit-value
                map-options
              >
                <template #selected>
                  <q-chip
                    dense
                    square
                    :color="chipColor(r.estado)"
                    :text-color="r.estado ? 'white' : 'dark'"
                  >
                    {{ r.estado || '—' }}
                  </q-chip>
                </template>
              </q-select>
            </td>

            <td class="text-center">
              <q-btn flat round dense icon="delete" color="negative" @click="removeRow(i)" />
            </td>
          </tr>

          <tr v-if="!form.antibiograma.length">
            <td colspan="3" class="text-center text-grey-7">Sin filas. Agrega con “Agregar fila”.</td>
          </tr>
          </tbody>
        </q-markup-table>

        <div class="q-mt-md">
          <div class="text-subtitle2 q-mb-xs">Observaciones</div>
          <q-input v-model="form.observaciones" type="textarea" outlined autogrow />
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'CultivoAntibiogramaPage',
  data () {
    return {
      loading: false,
      solicitud: null,
      estadoOptions: ['S', 'R', 'I', ''],
      form: {
        numero_identificacion: '',
        codigo_microbiologia: '',
        institucion: '',
        cultivo_solicitado: '',
        localizacion: '',
        servicio: '',
        sala: '',
        cama: '',
        fecha_ingreso: '',
        fecha_salida: '',
        tincion_gram: '',
        conteo_colonia: '',
        microorganismo: '',
        mecanismo_resistencia: '',
        observaciones: '',
        antibiograma: []
      }
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    chipColor (v) {
      if (v === 'R') return 'negative'
      if (v === 'S') return 'positive'
      if (v === 'I') return 'warning'
      return 'grey-3'
    },
    addRow () {
      this.form.antibiograma.push({ antibiotico: '', estado: '' })
    },
    removeRow (i) {
      this.form.antibiograma.splice(i, 1)
    },
    async load () {
      this.loading = true
      const { data } = await this.$axios.get(`/cultivo-antibiograma/solicitud/${this.$route.params.id}`)
      this.solicitud = data.solicitud
      this.form = {
        ...this.form,
        ...data.cultivo,
        antibiograma: Array.isArray(data.cultivo?.antibiograma) ? data.cultivo.antibiograma : []
      }
      this.loading = false
    },
    async save () {
      this.loading = true
      await this.$axios.post(`/cultivo-antibiograma/solicitud/${this.$route.params.id}`, this.form)
      this.$alert.success('Guardado correctamente')
      this.loading = false
    },
    printPdf () {
      const url = `${this.$axios.defaults.baseURL}/cultivo-antibiograma/solicitud/${this.solicitud.id}/pdf`
      window.open(url, '_blank')
    }
  }
}
</script>
