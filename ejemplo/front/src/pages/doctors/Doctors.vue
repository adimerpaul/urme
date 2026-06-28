<template>
  <q-page class="q-pa-md">
    <q-card flat bordered>
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-sm-5">
          <q-input
            dense
            outlined
            v-model="filter"
            label="Buscar doctor"
            debounce="300"
            clearable
          >
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-12 col-sm-7 row justify-end items-center q-gutter-sm">
          <q-btn-dropdown
            color="teal"
            icon="download"
            label="Exportar"
            no-caps
            dense
          >
            <q-list>
              <q-item clickable v-close-popup @click="exportarExcel">
                <q-item-section avatar><q-icon name="table_view" color="green" /></q-item-section>
                <q-item-section>Excel</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="exportarPdf">
                <q-item-section avatar><q-icon name="picture_as_pdf" color="red" /></q-item-section>
                <q-item-section>PDF</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>

          <q-btn
            color="primary"
            icon="add_circle_outline"
            label="Nuevo doctor"
            no-caps
            @click="nuevo"
            :loading="loading"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-table
      class="q-mt-md"
      :rows="rowsOrdenados"
      :columns="columns"
      row-key="id"
      dense flat bordered
      :rows-per-page-options="[0]"
      :filter="filter"
      title="Doctores"
      :loading="loading"
    >
      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-chip
            v-if="props.row.estado === 'ACTIVO'"
            dense
            color="positive"
            text-color="white"
            icon="check_circle"
            style="font-size:11px"
          >
            ACTIVO
          </q-chip>
          <div v-else class="row items-center no-wrap text-grey-6">
            <q-icon name="visibility_off" size="16px" class="q-mr-xs" />
            <span style="font-size:11px">INACTIVO</span>
          </div>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" color="primary" dense size="10px" no-caps>
            <q-list>
              <q-item clickable v-close-popup @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="toggleEstado(props.row)">
                <q-item-section avatar>
                  <q-icon
                    :name="props.row.estado === 'ACTIVO' ? 'visibility_off' : 'visibility'"
                    :color="props.row.estado === 'ACTIVO' ? 'orange' : 'positive'"
                  />
                </q-item-section>
                <q-item-section>{{ props.row.estado === 'ACTIVO' ? 'Inactivar' : 'Activar' }}</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="eliminar(props.row.id)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <!-- Diálogo crear/editar -->
    <q-dialog v-model="dialog">
      <q-card style="min-width: 400px; max-width: 600px;">
        <q-card-section class="row items-center">
          <div class="text-h6">
            {{ editando ? 'Editar doctor' : 'Nuevo doctor' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit="guardar">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <q-input
                  v-uppercase
                  v-model="doctor.nombre"
                  label="Nombre"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-uppercase
                  v-model="doctor.especialidad"
                  label="Especialidad"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="doctor.ci"
                  label="CI"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="doctor.telefono"
                  label="Teléfono"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="doctor.email"
                  label="Email"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="doctor.registro"
                  label="Registro profesional"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="doctor.establecimiento_id"
                  :options="establecimientos"
                  option-label="nombre"
                  option-value="id"
                  emit-value
                  map-options
                  label="Establecimiento de salud"
                  dense outlined
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="doctor.estado"
                  :options="['ACTIVO', 'INACTIVO']"
                  label="Estado"
                  dense outlined
                  clearable
                />
              </div>
            </div>

            <div class="text-right q-mt-md">
              <q-btn flat label="Cancelar" v-close-popup :loading="loading" />
              <q-btn
                color="primary"
                label="Guardar"
                type="submit"
                class="q-ml-sm"
                :loading="loading"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'DoctoresPage',
  data () {
    return {
      rows: [],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left', sortable: true },
        { name: 'especialidad', label: 'Especialidad', field: 'especialidad', align: 'left', sortable: true },
        { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
        { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
        { name: 'email', label: 'Email', field: 'email', align: 'left' },
        { name: 'registro', label: 'Registro', field: 'registro', align: 'left' },
        { name: 'establecimiento', label: 'Establecimiento', field: row => row.establecimiento ? row.establecimiento.nombre : '', align: 'left', sortable: true },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center', sortable: true },
      ],
      filter: '',
      dialog: false,
      editando: false,
      loading: false,
      doctor: {},
      establecimientos: [],
    };
  },
  computed: {
    rowsOrdenados () {
      return [...this.rows].sort((a, b) =>
        (a.nombre || '').localeCompare(b.nombre || '', 'es', { sensitivity: 'base' })
      );
    }
  },
  mounted () {
    this.getDoctores();
    this.getEstablecimientos();
  },
  methods: {
    getEstablecimientos () {
      this.$axios.get('establecimientos')
        .then(res => {
          this.establecimientos = res.data;
        });
    },
    getDoctores () {
      this.loading = true;
      this.$axios.get('doctores')
        .then(res => {
          this.rows = res.data;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    nuevo () {
      this.doctor = {
        nombre: '',
        especialidad: '',
        ci: '',
        telefono: '',
        email: '',
        registro: '',
        establecimiento_id: null,
        estado: 'ACTIVO',
      };
      this.editando = false;
      this.dialog = true;
    },
    editar (row) {
      this.doctor = { ...row };
      this.editando = true;
      this.dialog = true;
    },
    toggleEstado (row) {
      const nuevoEstado = row.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
      this.$axios.put(`doctores/${row.id}`, { ...row, estado: nuevoEstado })
        .then(() => {
          row.estado = nuevoEstado;
          this.$alert?.success(`Doctor ${nuevoEstado === 'ACTIVO' ? 'activado' : 'inactivado'} correctamente`);
        })
        .catch(e => {
          this.$alert?.error('Error al cambiar estado: ' + (e.response?.data?.message || e.message));
        });
    },
    guardar () {
      this.loading = true;

      const req = this.editando
        ? this.$axios.put(`doctores/${this.doctor.id}`, this.doctor)
        : this.$axios.post('doctores', this.doctor);

      req.then(() => {
        this.$alert?.success('Guardado correctamente');
        this.dialog = false;
        this.getDoctores();
      })
        .catch(e => {
          const msg = e.response?.data?.message || e.message;
          this.$alert?.error('Error al guardar: ' + msg);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    eliminar (id) {
      this.$alert.dialog('¿Eliminar doctor?').onOk(() => {
        this.$axios.delete(`doctores/${id}`).then(() => {
          this.$alert?.success('Eliminado');
          this.getDoctores();
        });
      });
    },

    async exportarExcel () {
      try {
        const res = await this.$axios.get('doctores/export/excel', { responseType: 'blob' });
        this._descargar(res.data, 'doctores.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      } catch {
        this.$alert?.error('Error al generar el Excel');
      }
    },

    async exportarPdf () {
      try {
        const res = await this.$axios.get('doctores/export/pdf', { responseType: 'blob' });
        this._descargar(res.data, 'doctores.pdf', 'application/pdf');
      } catch {
        this.$alert?.error('Error al generar el PDF');
      }
    },

    _descargar (blob, nombre, tipo) {
      const url = URL.createObjectURL(new Blob([blob], { type: tipo }));
      const a = document.createElement('a');
      a.href = url;
      a.download = nombre;
      a.click();
      URL.revokeObjectURL(url);
    },
  },
};
</script>
