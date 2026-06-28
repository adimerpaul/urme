<template>
  <q-page class="q-pa-md">
    <q-table
      ref="tablePacientes"
      :rows="rows"
      :columns="columns"
      title="Pacientes"
      dense flat bordered
      row-key="id"
      :filter="filter"
      :pagination.sync="pagination"
      :rows-per-page-options="[10, 20, 50]"
      :loading="loading"
      @request="onRequest"
    >
      <!-- TOP -->
      <template #top-right>
        <q-btn
          color="positive"
          icon="add_circle_outline"
          label="Nuevo"
          @click="nuevo"
          no-caps
          class="q-mr-sm"
          :loading="loading"
        />
        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar"
          @click="getPacientes"
          no-caps
          class="q-mr-sm"
          :loading="loading"
        />
        <q-input dense outlined v-model="filter" label="Buscar" debounce="500">
          <template #append><q-icon name="search" /></template>
        </q-input>
      </template>

      <!-- ACCIONES -->
      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" color="primary" dense size="10px" no-caps>
            <q-list>
              <q-item clickable v-close-popup @click="verHistorico(props.row)">
                <q-item-section avatar><q-icon name="history" color="primary" /></q-item-section>
                <q-item-section>Histórico</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="editar(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section>Editar</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="eliminar(props.row.id)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <!-- BOTTOM CON PAGINACIÓN BONITA -->
      <template #bottom="scope">
        <div class="row items-center justify-between q-pa-sm full-width">
          <!-- Info -->
          <div class="col-12 col-sm-4 text-caption q-mb-xs q-mb-sm-none">
            <div>
              Mostrando
              <b>
                {{ firstRowIndex(scope.pagination) }}
                -
                {{ lastRowIndex(scope.pagination) }}
              </b>
              de
              <b>{{ scope.pagination.rowsNumber }}</b>
              pacientes
            </div>
          </div>

          <!-- Controles -->
          <div class="col-12 col-sm-8">
            <div class="row items-center justify-end q-gutter-sm">

              <!-- selector filas por página -->
              <div class="col-auto">
                <q-select
                  v-model="scope.pagination.rowsPerPage"
                  :options="[10, 20, 50]"
                  dense outlined
                  options-dense
                  style="width: 90px"
                  label="Filas"
                  @update:model-value="val => onChangeRowsPerPage(val, scope)"
                />
              </div>

              <!-- paginación -->
              <div class="col-auto">
                <q-pagination
                  v-model="scope.pagination.page"
                  :max="pagesNumber"
                  max-pages="7"
                  boundary-links
                  direction-links
                  icon-first="first_page"
                  icon-last="last_page"
                  icon-prev="chevron_left"
                  icon-next="chevron_right"
                  size="sm"
                  @update:model-value="val => onChangePage(val, scope)"
                />
              </div>
            </div>
          </div>
        </div>
      </template>
    </q-table>

    <!-- ═══════════════════════ DIÁLOGO HISTÓRICO ═══════════════════════ -->
    <q-dialog v-model="dialogHistorico" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column no-wrap" style="height:100vh">

        <!-- Barra superior -->
        <q-bar class="bg-primary text-white q-pa-md" style="min-height:52px;height:auto">
          <q-icon name="history" size="20px" />
          <span class="text-weight-bold q-ml-sm">Histórico del Paciente</span>
          <q-space />
          <q-input v-model="historicoFechaDesde" type="date" dense dark borderless
            label="Desde" style="max-width:130px" class="q-mr-xs" />
          <q-input v-model="historicoFechaHasta" type="date" dense dark borderless
            label="Hasta" style="max-width:130px" class="q-mr-xs" />
          <q-btn flat dense no-caps icon="search" label="Filtrar" class="q-mr-xs" @click="fetchHistorico(1)" />
          <q-btn flat dense no-caps icon="print"       label="Imprimir" class="q-mr-xs" @click="printHistorico" />
          <q-btn flat dense no-caps icon="table_view"  label="Excel"    class="q-mr-xs" :loading="excelLoading" @click="exportHistoricoExcel" />
          <q-btn flat dense icon="close" v-close-popup />
        </q-bar>

        <!-- Ficha del paciente -->
        <div class="bg-blue-1 q-px-lg q-py-md">
          <div class="row items-center q-col-gutter-md">
            <div class="col-auto">
              <q-avatar color="primary" text-color="white" size="56px" font-size="28px">
                {{ (pacienteHistorico.nombre_completo || '?').charAt(0) }}
              </q-avatar>
            </div>
            <div class="col">
              <div class="text-h6 text-weight-bold text-primary">{{ pacienteHistorico.nombre_completo }}</div>
              <div class="row q-gutter-x-lg q-mt-xs text-caption text-grey-8">
                <span v-if="pacienteHistorico.codigo"><q-icon name="qr_code" size="13px" /> <b>{{ pacienteHistorico.codigo }}</b></span>
                <span><q-icon name="badge" size="13px" /> CI: <b>{{ pacienteHistorico.ci || '—' }}</b></span>
                <span><q-icon name="person" size="13px" /> {{ pacienteHistorico.genero || '—' }}</span>
                <span><q-icon name="cake" size="13px" /> {{ pacienteHistorico.fecha_nac || '—' }}</span>
                <span><q-icon name="phone" size="13px" /> {{ pacienteHistorico.telefono || '—' }}</span>
                <span v-if="pacienteHistorico.direccion"><q-icon name="home" size="13px" /> {{ pacienteHistorico.direccion }}</span>
              </div>
            </div>
            <div class="col-auto text-center">
              <div class="text-h4 text-primary text-weight-bold">{{ historicoPagination.rowsNumber }}</div>
              <div class="text-caption text-grey-7">solicitudes totales</div>
            </div>
          </div>
        </div>

        <q-separator />

        <!-- Tabla de solicitudes -->
        <div class="col overflow-auto">
          <q-table
            :rows="historicoRows"
            :columns="columnsHistorico"
            row-key="id"
            dense flat
            :loading="loadingHistorico"
            v-model:pagination="historicoPagination"
            :rows-per-page-options="[10, 15, 25, 50]"
            @request="onHistoricoRequest"
            class="full-height"
          >
            <!-- Estado chip -->
            <template v-slot:body-cell-estado="props">
              <q-td :props="props">
                <q-chip dense :color="colorEstado(props.row.estado)" text-color="white" style="font-size:11px">
                  {{ props.row.estado || '—' }}
                </q-chip>
              </q-td>
            </template>

            <!-- Áreas + pruebas -->
            <template v-slot:body-cell-areas_pruebas="props">
              <q-td :props="props" style="max-width:240px">
                <div v-if="props.row.areas" class="text-caption text-weight-medium text-primary">{{ props.row.areas }}</div>
                <div v-if="props.row.pruebas" class="text-caption text-grey-7" style="white-space:normal">{{ props.row.pruebas }}</div>
              </q-td>
            </template>

            <!-- Establecimiento -->
            <template v-slot:body-cell-establecimiento="props">
              <q-td :props="props">
                <div class="text-caption text-weight-medium">{{ props.row.establecimiento_nombre || '—' }}</div>
                <div v-if="props.row.unidad_solicitante" class="text-caption text-grey-6">{{ props.row.unidad_solicitante }}</div>
                <div v-if="props.row.sala || props.row.cama" class="text-caption text-grey-6">
                  <span v-if="props.row.sala">Sala: {{ props.row.sala }}</span>
                  <span v-if="props.row.cama" class="q-ml-xs">Cama: {{ props.row.cama }}</span>
                </div>
              </q-td>
            </template>

            <!-- Servicios realizados -->
            <template v-slot:body-cell-realizados="props">
              <q-td :props="props" class="text-center">
                <q-badge
                  :color="props.row.cant_realizados >= props.row.cant_servicios && props.row.cant_servicios > 0 ? 'positive' : 'orange'"
                  :label="`${props.row.cant_realizados}/${props.row.cant_servicios}`"
                />
              </q-td>
            </template>

            <!-- Bottom personalizado -->
            <template #bottom="scope">
              <div class="row items-center justify-between q-pa-sm full-width">
                <div class="col text-caption">
                  Mostrando <b>{{ (scope.pagination.page - 1) * scope.pagination.rowsPerPage + 1 }}</b>
                  – <b>{{ Math.min(scope.pagination.page * scope.pagination.rowsPerPage, historicoPagination.rowsNumber) }}</b>
                  de <b>{{ historicoPagination.rowsNumber }}</b> solicitudes
                </div>
                <div class="col-auto row items-center q-gutter-sm">
                  <q-select
                    v-model="scope.pagination.rowsPerPage"
                    :options="[10, 15, 25, 50]"
                    dense outlined options-dense style="width:80px" label="Filas"
                    @update:model-value="val => { historicoPagination.rowsPerPage = val; fetchHistorico(1) }"
                  />
                  <q-pagination
                    v-model="scope.pagination.page"
                    :max="Math.ceil(historicoPagination.rowsNumber / historicoPagination.rowsPerPage)"
                    max-pages="7" boundary-links direction-links
                    icon-first="first_page" icon-last="last_page"
                    size="sm"
                    @update:model-value="val => fetchHistorico(val)"
                  />
                </div>
              </div>
            </template>
          </q-table>
        </div>
      </q-card>
    </q-dialog>

    <!-- ═══════════════════════ DIÁLOGO CREAR/EDITAR ══════════════════════ -->
    <q-dialog v-model="dialog">
      <q-card style="min-width: 400px;">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ editando ? 'Editar Paciente' : 'Nuevo Paciente' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="guardar">
            <q-input v-uppercase v-model="paciente.nombre_completo" label="Nombre completo" dense outlined required />
            <q-input v-model="paciente.ci" label="CI" dense outlined />
            <q-input v-model="paciente.telefono" label="Teléfono/Celular" dense outlined />
            <q-input v-uppercase v-model="paciente.direccion" label="Dirección" dense outlined />
            <q-input
              v-model="paciente.fecha_nac"
              type="date"
              label="Fecha de nacimiento"
              dense outlined
              @update:model-value="calculateEdad"
              clearable
            />
            <q-select
              v-model="paciente.genero"
              label="Género"
              :options="['F','M','OTRO']"
              dense
              outlined
            />
            <div class="q-pa-xs q-mt-sm" style="border: 1px solid #ccc; border-radius: 4px;">
              <span>Edad calculada: </span>
              <span class="text-h6">
                {{ edadCaculado }}
              </span>
            </div>
            <div class="q-mt-sm">
              <q-toggle
                v-model="paciente.discapacidad"
                label="Discapacidad"
                :true-value="1"
                :false-value="0"
              />
            </div>
            <q-select
              v-if="paciente.discapacidad"
              v-model="paciente.discapacidad_cual"
              label="¿Cuál?"
              :options="discapacidades"
              dense
              outlined
            />
            <q-input
              v-if="paciente.discapacidad_cual === 'Otros'"
              v-model="paciente.discapacidad_otro"
              label="Especifique"
              dense
              outlined
            />
            <div class="q-mt-sm">
              <q-toggle
                v-model="paciente.embarazo"
                label="Embarazo"
                :true-value="1"
                :false-value="0"
              />
            </div>
            <q-input
              v-if="paciente.embarazo"
              v-model="paciente.fum"
              type="date"
              label="FUM"
              dense
              outlined
              @update:model-value="calculateFum"
            />
            <q-input
              v-if="paciente.embarazo"
              v-model="paciente.sem_gest"
              type="number"
              label="Semanas gestación"
              dense
              outlined
            />
            <div class="text-right q-mt-md">
              <q-btn flat label="Cancelar" v-close-popup :loading="loading" />
              <q-btn color="primary" label="Guardar" type="submit" class="q-ml-sm" :loading="loading" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import moment from 'moment';

export default {
  name: 'PacientesPage',
  data () {
    return {
      rows: [],
      filter: '',
      dialog: false,
      editando: false,
      loading: false,
      paciente: {},
      pagination: {
        page: 1,
        rowsPerPage: 20,
        rowsNumber: 0,
        sortBy: 'id',
        descending: true
      },

      // ── Histórico ──
      dialogHistorico: false,
      loadingHistorico: false,
      excelLoading: false,
      historicoFechaDesde: '',
      historicoFechaHasta: '',
      pacienteHistorico: {},
      historicoRows: [],
      historicoPagination: { page: 1, rowsPerPage: 15, rowsNumber: 0 },
      columnsHistorico: [
        { name: 'fecha',          label: 'Fecha',          field: row => (row.fecha_creacion || '').substring(0, 10), align: 'left', style: 'width:90px' },
        { name: 'hora',           label: 'Hora',           field: 'hora_solicitud',  align: 'left', style: 'width:70px' },
        { name: 'codigo',         label: 'Código',         field: 'codigo_solicitud', align: 'left' },
        { name: 'nro_registro',   label: 'Nro Reg.',       field: 'nro_registro',    align: 'left' },
        { name: 'doctor',         label: 'Doctor / Médico', field: 'doctor_nombre',  align: 'left' },
        { name: 'tipo_atencion',  label: 'Tipo Prestación', field: 'tipo_atencion',  align: 'left' },
        { name: 'establecimiento',label: 'Establecimiento / Sala', field: row => row.establecimiento_nombre || '—', align: 'left' },
        { name: 'areas_pruebas',  label: 'Áreas / Pruebas', field: row => row.areas || '', align: 'left' },
        { name: 'realizados',     label: 'Realizados',     field: row => `${row.cant_realizados}/${row.cant_servicios}`, align: 'center', style: 'width:80px' },
        { name: 'estado',         label: 'Estado',         field: 'estado',          align: 'center', style: 'width:110px' },
      ],

      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'id', label: 'Código paciente', field: 'id', align: 'left' },
        { name: 'nombre_completo', label: 'Nombre', field: 'nombre_completo' },
        { name: 'ci', label: 'CI', field: 'ci' },
        { name: 'telefono', label: 'Teléfono', field: 'telefono' },
        { name: 'genero', label: 'Género', field: 'genero' },
        {
          name: 'edad',
          label: 'Edad',
          field: (row) => {
            if (row.fecha_nac) {
              const birthDate = moment(row.fecha_nac);
              const today = moment();
              const years = today.diff(birthDate, 'years');
              birthDate.add(years, 'years');
              const months = today.diff(birthDate, 'months');
              birthDate.add(months, 'months');
              const days = today.diff(birthDate, 'days');
              return `${years}a ${months}m ${days}d`;
            }
            return '';
          }
        }
      ],
      discapacidades: [
        'Visual',
        'Auditiva',
        'Física-Motora',
        'Intelectual',
        'Psicosocial',
        'Otros'
      ]
    };
  },
  computed: {
    edadCaculado () {
      if (this.paciente.fecha_nac) {
        const birthDate = moment(this.paciente.fecha_nac);
        const today = moment();
        const years = today.diff(birthDate, 'years');
        birthDate.add(years, 'years');
        const months = today.diff(birthDate, 'months');
        birthDate.add(months, 'months');
        const days = today.diff(birthDate, 'days');
        return `${years} años ${months} meses ${days} dias`;
      }
      return '';
    },
    // número total de páginas
    pagesNumber () {
      const { rowsPerPage, rowsNumber } = this.pagination;
      if (!rowsPerPage || rowsPerPage <= 0) return 1;
      return Math.max(1, Math.ceil(rowsNumber / rowsPerPage));
    }
  },
  mounted () {
    this.getPacientes();
  },
  methods: {

    // índices para el texto "Mostrando X - Y de Z"
    firstRowIndex (pag) {
      if (pag.rowsNumber === 0) return 0;
      return (pag.page - 1) * pag.rowsPerPage + 1;
    },
    lastRowIndex (pag) {
      if (pag.rowsNumber === 0) return 0;
      const last = pag.page * pag.rowsPerPage;
      return last > pag.rowsNumber ? pag.rowsNumber : last;
    },

    calculateFum () {
      if (this.paciente.fum) {
        const fumDate = new Date(this.paciente.fum);
        const today = new Date();
        const diffTime = Math.abs(today - fumDate);
        const diffWeeks = Math.floor(diffTime / (1000 * 60 * 60 * 24 * 7));
        this.paciente.sem_gest = diffWeeks;
      } else {
        this.paciente.sem_gest = null;
      }
    },
    calculateEdad () {
      if (this.paciente.fecha_nac) {
        const birthDate = new Date(this.paciente.fecha_nac);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        this.paciente.edad = age;
      } else {
        this.paciente.edad = null;
      }
    },

    // botón Actualizar / primera carga
    getPacientes () {
      if (this.$refs.tablePacientes) {
        this.$refs.tablePacientes.requestServerInteraction();
      } else {
        this.requestPacientes(this.pagination, this.filter);
      }
    },

    // handler QTable server-side
    onRequest (props) {
      const { page, rowsPerPage, sortBy, descending } = props.pagination;
      const filter = props.filter;

      // sincronizo mi objeto local con el del QTable
      this.pagination.page = page;
      this.pagination.rowsPerPage = rowsPerPage;
      this.pagination.sortBy = sortBy;
      this.pagination.descending = descending;

      this.requestPacientes(this.pagination, filter);
    },

    // llamada real al backend
    requestPacientes (pagination, filter) {
      this.loading = true;

      this.$axios.get('pacientes', {
        params: {
          page: pagination.page,
          per_page: pagination.rowsPerPage,
          search: filter || ''
        }
      })
        .then(res => {
          this.rows = res.data.data;
          // total de registros para todas las páginas
          this.pagination.rowsNumber = res.data.total;
        })
        .finally(() => {
          this.loading = false;
        });
    },

    // cuando cambia la página desde QPagination
    onChangePage (page, scope) {
      // scope.pagination ya tiene page por el v-model
      this.pagination.page = page;           // mantengo mi copia en sync
      if (this.$refs.tablePacientes) {
        this.$refs.tablePacientes.requestServerInteraction(); // dispara @request con la nueva page
      }
    },

    // cuando cambia las filas por página
    onChangeRowsPerPage (val, scope) {
      scope.pagination.page = 1;             // reset en el QTable
      this.pagination.rowsPerPage = val;
      this.pagination.page = 1;
      if (this.$refs.tablePacientes) {
        this.$refs.tablePacientes.requestServerInteraction();
      }
    },
    nuevo () {
      this.paciente = {
        nombre_completo: '',
        ci: '',
        telefono: '',
        direccion: '',
        fecha_nac: '',
        genero: '',
        edad: null,
        discapacidad: 0,
        discapacidad_cual: '',
        embarazo: 0,
        fum: '',
        sem_gest: null,
        discapacidad_otro: ''
      };
      this.editando = false;
      this.dialog = true;
    },

    editar (row) {
      this.paciente = { ...row };
      this.editando = true;
      this.dialog = true;
    },

    guardar () {
      this.loading = true;
      const req = this.editando
        ? this.$axios.put(`pacientes/${this.paciente.id}`, this.paciente)
        : this.$axios.post('pacientes', this.paciente);

      req.then(() => {
        this.$alert && this.$alert.success
          ? this.$alert.success('Guardado correctamente')
          : console.log('Guardado correctamente');
        this.dialog = false;
        this.getPacientes();
      })
        .catch((e) => {
          const msg = e.response?.data?.message || e.message;
          this.$alert && this.$alert.error
            ? this.$alert.error('Error al guardar: ' + msg)
            : console.error(msg);
        })
        .finally(() => {
          this.loading = false;
        });
    },

    eliminar (id) {
      this.$alert.dialog('¿Eliminar paciente?').onOk(() => {
        this.$axios.delete(`pacientes/${id}`).then(() => {
          this.$alert && this.$alert.success
            ? this.$alert.success('Eliminado')
            : console.log('Eliminado');
          this.getPacientes();
        });
      });
    },

    // ── Histórico ─────────────────────────────────────────────────────────

    verHistorico (row) {
      this.pacienteHistorico = { ...row };
      this.historicoRows = [];
      this.historicoPagination = { page: 1, rowsPerPage: 15, rowsNumber: 0 };
      this.historicoFechaDesde = '';
      this.historicoFechaHasta = '';
      this.dialogHistorico = true;
      this.fetchHistorico(1);
    },

    async fetchHistorico (page) {
      this.loadingHistorico = true;
      try {
        const res = await this.$axios.get(`pacientes/${this.pacienteHistorico.id}/historico`, {
          params: {
            page,
            per_page: this.historicoPagination.rowsPerPage,
            date_from: this.historicoFechaDesde || undefined,
            date_to: this.historicoFechaHasta || undefined
          }
        });
        const p = res.data.solicitudes;
        this.historicoRows = p.data;
        this.historicoPagination = {
          ...this.historicoPagination,
          page: p.current_page,
          rowsPerPage: p.per_page,
          rowsNumber: p.total
        };
        // actualizar ficha del paciente con datos frescos
        if (res.data.paciente) this.pacienteHistorico = res.data.paciente;
      } catch (e) {
        this.$alert?.error('Error al cargar el histórico');
      } finally {
        this.loadingHistorico = false;
      }
    },

    onHistoricoRequest (props) {
      this.historicoPagination.rowsPerPage = props.pagination.rowsPerPage;
      this.fetchHistorico(props.pagination.page);
    },

    async exportHistoricoExcel () {
      this.excelLoading = true;
      try {
        const res = await this.$axios.get(
          `pacientes/${this.pacienteHistorico.id}/historico/excel`,
          {
            responseType: 'blob',
            params: {
              date_from: this.historicoFechaDesde || undefined,
              date_to: this.historicoFechaHasta || undefined
            }
          }
        );
        const blob = new Blob([res.data], {
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `historico_${this.pacienteHistorico.nombre_completo}.xlsx`;
        a.click();
        URL.revokeObjectURL(a.href);
      } catch (e) {
        this.$alert?.error('Error al generar el Excel');
      } finally {
        this.excelLoading = false;
      }
    },

    colorEstado (estado) {
      const map = {
        CREADO: 'grey-7', ATENDIENDO: 'orange',
        ENVIADO_ANALITICA: 'indigo', ANALITICA_ATENDIENDO: 'blue', FINALIZADO: 'positive'
      };
      return map[estado] || 'blue-grey';
    },

    printHistorico () {
      const p = this.pacienteHistorico;
      const rows = this.historicoRows;

      const filasPaciente = `
        <tr><th>Nombre completo</th><td>${p.nombre_completo || ''}</td><th>Código</th><td>${p.codigo || ''}</td></tr>
        <tr><th>CI</th><td>${p.ci || '—'}</td><th>Género</th><td>${p.genero || '—'}</td></tr>
        <tr><th>Fecha nacimiento</th><td>${p.fecha_nac || '—'}</td><th>Teléfono</th><td>${p.telefono || '—'}</td></tr>
        <tr><th>Dirección</th><td colspan="3">${p.direccion || '—'}</td></tr>
      `;

      const filasTabla = rows.map((r, i) => `
        <tr class="${i % 2 === 0 ? 'par' : ''}">
          <td>${(r.fecha_creacion || '').substring(0, 10)}</td>
          <td>${r.hora_solicitud || ''}</td>
          <td>${r.codigo_solicitud || ''}</td>
          <td>${r.nro_registro || ''}</td>
          <td>${r.doctor_nombre || ''}</td>
          <td>${r.tipo_atencion || ''}</td>
          <td>${r.establecimiento_nombre || ''}</td>
          <td>${[r.sala ? 'Sala: ' + r.sala : '', r.cama ? 'Cama: ' + r.cama : ''].filter(Boolean).join(' ')}</td>
          <td>${r.areas || ''}</td>
          <td style="font-size:10px">${r.pruebas || ''}</td>
          <td style="text-align:center">${r.cant_realizados}/${r.cant_servicios}</td>
          <td><span class="estado estado-${(r.estado || '').toLowerCase()}">${r.estado || ''}</span></td>
        </tr>
      `).join('');

      const win = window.open('', '_blank');
      win.document.write(`<!DOCTYPE html><html lang="es"><head>
        <meta charset="UTF-8">
        <title>Histórico — ${p.nombre_completo}</title>
        <style>
          * { margin:0; padding:0; box-sizing:border-box; }
          body { font-family: Arial, sans-serif; font-size: 11px; color: #212121; }
          .header { background: #0D47A1; color: #fff; padding: 12px 20px; }
          .header h1 { font-size: 16px; }
          .header p  { font-size: 11px; opacity: .85; margin-top: 2px; }
          .ficha { margin: 12px 20px; border: 1px solid #90CAF9; border-radius: 4px; overflow: hidden; }
          .ficha table { width: 100%; border-collapse: collapse; }
          .ficha th { background: #E3F2FD; color: #1565C0; font-size: 10px; text-align: left; padding: 5px 8px; width: 16%; }
          .ficha td { padding: 5px 8px; border-top: 1px solid #E3F2FD; }
          h2 { margin: 16px 20px 6px; font-size: 13px; color: #1565C0; border-bottom: 2px solid #1565C0; padding-bottom: 3px; }
          .data-table { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
          .data-table th { background: #1565C0; color: #fff; padding: 5px 6px; font-size: 10px; text-align: left; }
          .data-table td { padding: 4px 6px; border-bottom: 1px solid #E0E0E0; vertical-align: top; font-size: 10px; }
          .data-table tr.par td { background: #EAF2FF; }
          .estado { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; color: #fff; }
          .estado-creado         { background: #757575; }
          .estado-atendiendo     { background: #EF6C00; }
          .estado-finalizado     { background: #2E7D32; }
          .estado-enviado_analitica { background: #3949AB; }
          .estado-analitica_atendiendo { background: #1565C0; }
          .footer { margin: 14px 20px 0; font-size: 9px; color: #9E9E9E; border-top: 1px solid #eee; padding-top: 6px; }
          @media print {
            @page { margin: 10mm; size: A4 landscape; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
          }
        </style>
      </head><body>
        <div class="header">
          <h1>Histórico Clínico del Paciente</h1>
          <p>Generado el ${new Date().toLocaleDateString('es-BO', { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' })}</p>
        </div>

        <div class="ficha">
          <table>${filasPaciente}</table>
        </div>

        <h2>Solicitudes y Prestaciones (${this.historicoPagination.rowsNumber} total — mostrando página actual)</h2>

        <table class="data-table">
          <thead>
            <tr>
              <th>Fecha</th><th>Hora</th><th>Código</th><th>Nro Reg.</th>
              <th>Doctor</th><th>Tipo Prestación</th><th>Establecimiento</th><th>Sala/Cama</th>
              <th>Áreas</th><th>Pruebas / Servicios</th><th>Realiz.</th><th>Estado</th>
            </tr>
          </thead>
          <tbody>${filasTabla}</tbody>
        </table>

        <div class="footer">
          SILL — Sistema de Información de Laboratorio Clínico &nbsp;|&nbsp; ${p.nombre_completo} &nbsp;|&nbsp; Código: ${p.codigo || '—'}
        </div>
      </body></html>`);
      win.document.close();
      win.focus();
      setTimeout(() => { win.print(); }, 400);
    }
  }
};
</script>
