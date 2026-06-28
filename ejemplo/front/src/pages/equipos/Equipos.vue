<template>
  <q-page class="q-pa-md bg-grey-2">
    <!-- Filtros -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-sm-4">
          <q-input
            dense outlined
            v-model="filter"
            label="Buscar equipo"
            debounce="300"
            @update:model-value="fetchRows"
          >
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>

        <div class="col-12 col-sm-3">
          <q-select
            v-model="filterEstado"
            :options="estadoOptions"
            dense outlined emit-value map-options
            label="Estado"
            clearable
            @update:model-value="fetchRows"
          />
        </div>

        <div class="col-12 col-sm-3">
          <q-select
            v-model="filterServicioId"
            :options="serviciosOptions"
            dense outlined emit-value map-options
            label="Servicio (filtrar)"
            clearable
            option-value="id"
            option-label="nombre"
            @update:model-value="fetchRows"
          />
        </div>

        <div class="col-12 col-sm-2 row justify-end">
          <q-btn
            color="primary"
            icon="add_circle_outline"
            label="Nuevo equipo"
            no-caps
            @click="nuevo"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabla -->
    <q-table
      class="q-mt-md"
      :rows="rows"
      :columns="columns"
      row-key="id"
      dense flat bordered
      :rows-per-page-options="[0]"
      :loading="loading"
      title="Equipos de laboratorio"
    >
      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-chip
            dense
            :color="props.row.estado === 'ACTIVO' ? 'positive' : 'grey-6'"
            text-color="white"
            :icon="props.row.estado === 'ACTIVO' ? 'check_circle' : 'pause_circle'"
          >
            {{ props.row.estado }}
          </q-chip>
        </q-td>
      </template>

      <template #body-cell-servicio="props">
        <q-td :props="props">
          <span v-if="props.row.servicio">{{ props.row.servicio.nombre }}</span>
          <span v-else class="text-grey-5">— Todos —</span>
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
              <q-item clickable v-close-popup @click="eliminar(props.row.id)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <!-- Diálogo -->
    <q-dialog v-model="dialog">
      <q-card style="min-width: 420px">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ editando ? 'Editar equipo' : 'Nuevo equipo' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit.prevent="guardar">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="equipo.nombre"
                  label="Nombre del equipo"
                  dense outlined autofocus
                  :rules="[v => !!v || 'Requerido']"
                >
                  <template #prepend><q-icon name="build" /></template>
                </q-input>
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="equipo.estado"
                  :options="estadoOptions"
                  emit-value map-options
                  label="Estado"
                  dense outlined
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-select
                  v-model="equipo.servicio_id"
                  :options="serviciosOptions"
                  emit-value map-options
                  option-value="id"
                  option-label="nombre"
                  label="Servicio asociado (opcional)"
                  dense outlined clearable
                  hint="Vacío = disponible para todos"
                />
              </div>
            </div>

            <div class="text-right q-mt-md">
              <q-btn flat label="Cancelar" v-close-popup :loading="loading" />
              <q-btn
                color="primary" label="Guardar"
                type="submit" class="q-ml-sm"
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
  name: 'EquiposPage',
  data () {
    return {
      rows: [],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left', sortable: true },
        { name: 'servicio', label: 'Servicio', field: row => row.servicio?.nombre || '—', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center' }
      ],
      filter: '',
      filterEstado: null,
      filterServicioId: null,
      estadoOptions: [
        { label: 'Activo', value: 'ACTIVO' },
        { label: 'Inactivo', value: 'INACTIVO' }
      ],
      serviciosOptions: [],
      loading: false,
      dialog: false,
      editando: false,
      equipo: {}
    }
  },

  mounted () {
    this.fetchRows()
    this.fetchServicios()
  },

  methods: {
    fetchRows () {
      this.loading = true
      this.$axios.get('equipos', {
        params: {
          q: this.filter || null,
          estado: this.filterEstado || null,
          servicio_id: this.filterServicioId || null
        }
      })
        .then(res => { this.rows = res.data })
        .finally(() => { this.loading = false })
    },

    fetchServicios () {
      this.$axios.get('servicios', { params: { estado: 'ACTIVO' } })
        .then(res => { this.serviciosOptions = res.data || [] })
    },

    nuevo () {
      this.equipo = { nombre: '', estado: 'ACTIVO', servicio_id: null }
      this.editando = false
      this.dialog = true
    },

    editar (row) {
      this.equipo = { ...row }
      this.editando = true
      this.dialog = true
    },

    guardar () {
      this.loading = true
      const req = this.editando
        ? this.$axios.put(`equipos/${this.equipo.id}`, this.equipo)
        : this.$axios.post('equipos', this.equipo)

      req
        .then(() => {
          this.$alert?.success('Guardado correctamente')
          this.dialog = false
          this.fetchRows()
        })
        .catch(e => {
          this.$alert?.error('Error: ' + (e.response?.data?.message || e.message))
        })
        .finally(() => { this.loading = false })
    },

    eliminar (id) {
      this.$alert.dialog('¿Eliminar este equipo?').onOk(() => {
        this.$axios.delete(`equipos/${id}`)
          .then(() => {
            this.$alert?.success('Eliminado')
            this.fetchRows()
          })
      })
    }
  }
}
</script>
