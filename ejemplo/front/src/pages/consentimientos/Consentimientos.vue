<!-- src/pages/ConsentimientosPage.vue -->
<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.from" type="date" dense outlined label="Desde" />
        </div>
        <div class="col-12 col-sm-3">
          <q-input v-model="filters.to" type="date" dense outlined label="Hasta" />
        </div>
        <div class="col-12 col-sm-3">
          <q-select
            v-model="filters.tipo"
            :options="['', 'ACEPTA', 'RECHAZA']"
            dense outlined
            label="Tipo"
          />
        </div>
        <div class="col-12 col-sm-3 text-right">
          <q-btn
            color="primary"
            icon="search"
            label="Filtrar"
            @click="getConsentimientos"
            no-caps
            :loading="loading"
            class="q-mr-sm"
          />
          <q-btn
            color="positive"
            icon="add_circle_outline"
            label="Nuevo"
            @click="nuevo"
            no-caps
            :loading="loading"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-table
      class="q-mt-md"
      :rows="rows"
      :columns="columns"
      title="Consentimientos"
      dense flat bordered
      :rows-per-page-options="[0]"
      row-key="id"
      :filter="filter"
    >
      <template #top-right>
        <q-input dense outlined v-model="filter" label="Buscar">
          <template #append><q-icon name="search" /></template>
        </q-input>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" color="primary" dense size="10px" no-caps>
            <q-list>
              <q-item clickable v-close-popup @click="ver(props.row)">
                <q-item-section avatar><q-icon name="visibility" /></q-item-section>
                <q-item-section>Ver</q-item-section>
              </q-item>

              <q-item clickable v-close-popup @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>

              <q-item clickable v-close-popup @click="imprimir(props.row)">
                <q-item-section avatar><q-icon name="picture_as_pdf" /></q-item-section>
                <q-item-section>Imprimir</q-item-section>
              </q-item>

              <q-item clickable v-close-popup @click="eliminar(props.row.id)">
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <!-- Diálogo -->
    <q-dialog v-model="dialog">
      <q-card style="width: min(900px, 96vw); max-width: 96vw;">
        <q-card-section class="row items-center">
          <div class="text-h6">
            {{ soloVer ? 'Ver consentimiento' : (editando ? 'Editar consentimiento' : 'Nuevo consentimiento') }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit.prevent="onSubmit">
            <!-- Búsqueda de paciente -->
<!--            <div class="row q-col-gutter-sm">-->
<!--              <div class="col-12 col-sm-4">-->
<!--                <q-input-->
<!--                  v-model="searchCi"-->
<!--                  label="Buscar paciente por CI"-->
<!--                  dense outlined-->
<!--                  :readonly="soloVer"-->
<!--                >-->
<!--                  <template #append>-->
<!--                    <q-btn-->
<!--                      flat dense icon="search"-->
<!--                      @click="buscarPacientePorCi"-->
<!--                      :disable="soloVer"-->
<!--                    />-->
<!--                  </template>-->
<!--                </q-input>-->
<!--              </div>-->
<!--              <div class="col-12 col-sm-8">-->
<!--                <q-select-->
<!--                  v-model="consentimiento.paciente_id"-->
<!--                  :options="pacientesOptions"-->
<!--                  option-value="id"-->
<!--                  option-label="nombre_completo"-->
<!--                  emit-value map-options-->
<!--                  dense outlined-->
<!--                  label="Seleccionar paciente"-->
<!--                  :disable="soloVer"-->
<!--                  @update:model-value="onSelectPaciente"-->
<!--                />-->
<!--              </div>-->
<!--            </div>-->

<!--            <q-separator class="q-my-sm" />-->

            <!-- Datos del paciente (se rellenan solos o manualmente) -->
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-3">
                <q-input
                  v-model="consentimiento.ci"
                  label="CI"
                  dense outlined
                  :readonly="soloVer"
                  :debounce="600"
                  @update:model-value="buscarPacientePorCi"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="consentimiento.nombre_completo"
                  label="Nombre completo"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-3">
                <q-input
                  v-model="consentimiento.telefono"
                  label="Teléfono"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12">
                <q-input
                  v-model="consentimiento.direccion"
                  label="Dirección"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.fecha_nac"
                  type="date"
                  label="Fecha de nacimiento"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-select
                  v-model="consentimiento.genero"
                  :options="['F', 'M', 'OTRO']"
                  label="Género"
                  dense outlined
                  :disable="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model.number="consentimiento.edad"
                  type="number"
                  label="Edad"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-toggle
                  v-model="consentimiento.discapacidad"
                  :true-value="1"
                  :false-value="0"
                  label="Discapacidad"
                  :disable="soloVer"
                />
              </div>
              <div class="col-12 col-sm-8" v-if="consentimiento.discapacidad">
                <q-input
                  v-model="consentimiento.discapacidad_cual"
                  label="Discapacidad (cual)"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-8" v-if="consentimiento.discapacidad">
                <q-input
                  v-model="consentimiento.discapacidad_otro"
                  label="Discapacidad (otro)"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-toggle
                  v-model="consentimiento.embarazo"
                  :true-value="1"
                  :false-value="0"
                  label="Embarazo"
                  :disable="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4" v-if="consentimiento.embarazo">
                <q-input
                  v-model="consentimiento.fum"
                  type="date"
                  label="FUM"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4" v-if="consentimiento.embarazo">
                <q-input
                  v-model.number="consentimiento.sem_gest"
                  type="number"
                  label="Semanas gestación"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-toggle
                  v-model="consentimiento.medicamento"
                  :true-value="1"
                  :false-value="0"
                  label="Medicamento"
                  :disable="soloVer"
                />
              </div>
              <div class="col-12 col-sm-8" v-if="consentimiento.medicamento">
                <q-input
                  v-model="consentimiento.tratamiento"
                  label="Tratamiento"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="consentimiento.condicion"
                  :options="['BASAL', 'AYUNO PROL', 'POST PRANDIAL']"
                  label="Condición"
                  dense outlined
                  :disable="soloVer"
                />
              </div>
            </div>

            <q-separator class="q-my-sm" />

            <!-- Datos de consentimiento -->
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.fecha_recepcion"
                  type="date"
                  label="Fecha recepción"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.hora_recepcion"
                  type="text"
                  label="Hora recepción"
                  dense outlined
                  maxlength="10"
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.fecha_consentimiento"
                  type="date"
                  label="Fecha consentimiento"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.fecha_solicitud"
                  type="date"
                  label="Fecha Medico Solicitante"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12 col-sm-4">
                <q-select
                  v-model="consentimiento.tipo"
                  :options="['ACEPTA', 'RECHAZA']"
                  label="Tipo consentimiento"
                  clearable
                  dense outlined
                  :disable="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-input
                  v-model="consentimiento.declarante_nombre"
                  label="Yo (declarante)"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-sm-4">
                <q-select
                  v-model="consentimiento.declarante_condicion"
                  :options="['Padre', 'Madre', 'Abuelo/a', 'Hijo/a', 'Propio', 'Otros']"
                  label="Condicion del declarante"
                  dense outlined
                  clearable
                  :disable="soloVer"
                />
                <q-input
                  v-if="consentimiento.declarante_condicion === 'Otros'"
                  v-model="consentimiento.declarante_condicion_otro"
                  label="Especifique condicion del declarante"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>

              <div class="col-12"><q-separator class="q-my-sm" /></div>

              <div class="col-12">
                <div class="text-subtitle2 q-mb-xs">Tipo de muestra</div>
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_liquidos" :true-value="1" :false-value="0" dense label="Liquidos" :disable="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_esputo" :true-value="1" :false-value="0" dense label="Esputo" :disable="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_secreciones" :true-value="1" :false-value="0" dense label="Secreciones" :disable="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_orina" :true-value="1" :false-value="0" dense label="Orina" :disable="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model="consentimiento.hr_recoleccion_orina" type="text" label="HR recoleccion orina" dense outlined maxlength="10" :readonly="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-checkbox v-model="consentimiento.m_heces" :true-value="1" :false-value="0" dense label="Heces" :disable="soloVer" />
              </div>
              <div class="col-12 col-sm-6 col-md-3">
                <q-input v-model="consentimiento.hr_recoleccion_heces" type="text" label="HR recoleccion heces" dense outlined maxlength="10" :readonly="soloVer" />
              </div>

              <div class="col-12 col-md-6">
                <q-input
                  v-model="consentimiento.observaciones"
                  type="textarea"
                  label="Observaciones"
                  autogrow
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model="consentimiento.hr_recoleccion"
                  label="HR Recolección"
                  dense outlined
                  :readonly="soloVer"
                />
              </div>
            </div>

            <div class="text-right q-mt-md">
              <template v-if="soloVer">
                <q-btn flat label="Cerrar" v-close-popup :loading="loading" />
              </template>
              <template v-else>
                <q-btn flat label="Cancelar" v-close-popup :loading="loading" />
                <q-btn
                  color="primary"
                  label="Guardar"
                  type="submit"
                  class="q-ml-sm"
                  :loading="loading"
                />
              </template>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from "moment";

export default {
  name: 'ConsentimientosPage',
  data () {
    return {
      rows: [],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        {
          name: 'fecha_consentimiento',
          label: 'Fecha',
          field: row => row.fecha_consentimiento,
          format: v => v || '',
        },
        {
          name: 'paciente',
          label: 'Paciente',
          field: row => row.paciente?.nombre_completo || row.nombre_completo,
        },
        { name: 'ci', label: 'CI', field: 'ci' },
        { name: 'tipo', label: 'Tipo', field: 'tipo' },
      ],
      filter: '',
      dialog: false,
      editando: false,
      soloVer: false,
      loading: false,
      consentimiento: {},
      filters: {
        from: moment().format('YYYY-MM-DD'),
        to: moment().format('YYYY-MM-DD'),
        tipo: '',
      },
      pacientesOptions: [],
      searchCi: '',
    };
  },
  mounted () {
    this.getConsentimientos();
    this.loadPacientes();
  },
  methods: {
    consentimientoBase () {
      return {
        paciente_id: null,
        fecha_recepcion: moment().format('YYYY-MM-DD'),
        hora_recepcion: moment().format('HH:mm'),
        fecha_solicitud: '',
        nombre_completo: '',
        fecha_nac: '',
        genero: '',
        edad: null,
        ci: '',
        telefono: '',
        direccion: '',
        discapacidad: 0,
        discapacidad_cual: '',
        discapacidad_otro: '',
        embarazo: 0,
        fum: '',
        sem_gest: null,
        medicamento: 0,
        tratamiento: '',
        condicion: '',
        etapa_gestacion: '',
        tipo: '',
        declarante_nombre: '',
        declarante_condicion: '',
        declarante_condicion_otro: '',
        fecha_consentimiento: moment().format('YYYY-MM-DD'),
        m_orina: 0,
        hr_recoleccion_orina: '',
        m_liquidos: 0,
        m_esputo: 0,
        m_secreciones: 0,
        m_heces: 0,
        hr_recoleccion_heces: '',
        observaciones: '',
        hr_recoleccion: '',
      };
    },
    getConsentimientos () {
      this.loading = true;
      this.$axios
        .get('consentimientos', { params: this.filters })
        .then(res => {
          this.rows = res.data;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    loadPacientes () {
      this.$axios.get('pacientes').then(res => {
        this.pacientesOptions = res.data;
      });
    },
    nuevo () {
      this.consentimiento = this.consentimientoBase();
      this.searchCi = '';
      this.editando = false;
      this.soloVer = false;
      this.dialog = true;
    },
    editar (row) {
      this.consentimiento = {
        ...this.consentimientoBase(),
        ...row,
        paciente_id: row.paciente_id,
      };
      this.editando = true;
      this.soloVer = false;
      this.dialog = true;
    },
    ver (row) {
      this.consentimiento = {
        ...this.consentimientoBase(),
        ...row,
        paciente_id: row.paciente_id,
      };
      this.editando = false;
      this.soloVer = true;
      this.dialog = true;
    },
    onSubmit () {
      if (this.soloVer) {
        return;
      }
      this.guardar();
    },
    guardar () {
      this.loading = true;
      const req = this.editando
        ? this.$axios.put(`consentimientos/${this.consentimiento.id}`, this.consentimiento)
        : this.$axios.post('consentimientos', this.consentimiento);

      req.then(() => {
        this.$alert.success('Guardado correctamente');
        this.dialog = false;
        this.getConsentimientos();
      })
        .catch(e => {
          this.$alert.error('Error al guardar: ' + (e.response?.data?.message || e.message));
        })
        .finally(() => {
          this.loading = false;
        });
    },
    eliminar (id) {
      this.$alert.dialog('¿Eliminar consentimiento?').onOk(() => {
        this.$axios.delete(`consentimientos/${id}`).then(() => {
          this.$alert.success('Eliminado');
          this.getConsentimientos();
        });
      });
    },
    buscarPacientePorCi () {
      console.log('a')
      // if (!this.searchCi || this.soloVer) return;
      this.loading = true;
      this.$axios
        .get(`pacientes/buscar-ci/${this.consentimiento.ci}`)
        .then(res => {
          console.log(res.data);
          // this.onSelectPaciente(res.data.id);
          // {
          //   "id": 106,
          //   "fecha_recepcion": null,
          //   "hora_recepcion": null,
          //   "nombre_completo": "Adimer Paul Chambi Ajata",
          //   "fecha_nac": "1989-04-02",
          //   "genero": "M",
          //   "edad": 36,
          //   "ci": "7336199",
          //   "telefono": "69603027",
          //   "direccion": "calle x",
          //   "discapacidad": 0,
          //   "discapacidad_cual": null,
          //   "discapacidad_otro": null,
          //   "embarazo": 0,
          //   "fum": null,
          //   "sem_gest": null
          // }
          this.consentimiento.paciente_id = res.data.id;
          this.consentimiento.nombre_completo = res.data.nombre_completo;
          this.consentimiento.ci = res.data.ci;
          this.consentimiento.telefono = res.data.telefono;
          this.consentimiento.direccion = res.data.direccion;
          this.consentimiento.fecha_nac = res.data.fecha_nac;
          this.consentimiento.genero = res.data.genero;
          this.consentimiento.edad = res.data.edad;
          this.consentimiento.discapacidad = res.data.discapacidad;
          this.consentimiento.discapacidad_cual = res.data.discapacidad_cual;
          this.consentimiento.discapacidad_otro = res.data.discapacidad_otro;
          this.consentimiento.embarazo = res.data.embarazo;
          this.consentimiento.fum = res.data.fum;
          this.consentimiento.sem_gest = res.data.sem_gest;
        })
        .catch(() => {
          // this.$alert.error('Paciente no encontrado. Puede llenar los datos manualmente.');
        })
        .finally(() => {
          this.loading = false;
        });
    },
    onSelectPaciente (id) {
      const p = this.pacientesOptions.find(x => x.id === id);
      if (!p) return;
      this.consentimiento.paciente_id = p.id;
      this.consentimiento.nombre_completo = p.nombre_completo;
      this.consentimiento.ci = p.ci;
      this.consentimiento.telefono = p.telefono;
      this.consentimiento.direccion = p.direccion;
      this.consentimiento.fecha_nac = p.fecha_nac;
      this.consentimiento.genero = p.genero;
      this.consentimiento.edad = p.edad;
      this.consentimiento.discapacidad = p.discapacidad;
      this.consentimiento.discapacidad_cual = p.discapacidad_cual;
      this.consentimiento.discapacidad_otro = p.discapacidad_otro;
      this.consentimiento.embarazo = p.embarazo;
      this.consentimiento.fum = p.fum;
      this.consentimiento.sem_gest = p.sem_gest;
    },
    imprimir (row) {
      const url = `${process.env.API_URL || this.$axios.defaults.baseURL}/consentimientos/${row.id}/print`;
      window.open(url, '_blank');
    },
  },
};
</script>

