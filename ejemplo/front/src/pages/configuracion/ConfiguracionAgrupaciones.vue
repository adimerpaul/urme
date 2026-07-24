<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">Agrupaciones de prestaciones</div>
          <div class="text-caption text-grey-7">
            Grupos rápidos que aparecen al crear una solicitud: al marcar uno se seleccionan sus prestaciones
          </div>
        </div>
        <div class="col-auto">
          <q-btn flat icon="refresh" label="Refrescar" no-caps class="q-mr-sm" :disable="loading" @click="load" />
          <q-btn color="primary" icon="add" label="Nueva agrupación" no-caps @click="abrirNueva" />
        </div>
      </q-card-section>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <q-input v-model="filter" dense outlined debounce="200" placeholder="Filtrar agrupación" class="q-mb-sm" style="max-width: 300px">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-markup-table dense flat bordered square class="bg-white">
          <thead>
          <tr>
            <th class="text-center" style="width: 90px">Acciones</th>
            <th class="text-left" style="width: 280px">Nombre</th>
            <th class="text-center" style="width: 80px">Activo</th>
            <th class="text-left">Prestaciones que selecciona</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="agrupacion in agrupacionesFiltradas" :key="agrupacion.id">
            <td class="text-center">
              <q-btn flat dense round icon="edit" color="primary" size="sm" @click="abrirEditar(agrupacion)">
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat dense round icon="delete" color="negative" size="sm" @click="eliminar(agrupacion)">
                <q-tooltip>Eliminar</q-tooltip>
              </q-btn>
            </td>
            <td>{{ agrupacion.nombre }}</td>
            <td class="text-center">
              <q-toggle
                v-model="agrupacion.activo"
                dense
                :disable="agrupacion.saving"
                @update:model-value="guardarActivo(agrupacion)"
              />
            </td>
            <td>
              <q-chip
                v-for="p in agrupacion.prestaciones"
                :key="p.id"
                dense
                size="11px"
                color="blue-1"
                text-color="blue-10"
              >
                {{ p.nombre }}
              </q-chip>
              <span v-if="!(agrupacion.prestaciones || []).length" class="text-grey-6">Sin prestaciones</span>
            </td>
          </tr>
          <tr v-if="!loading && !agrupacionesFiltradas.length">
            <td colspan="4" class="text-center text-grey-7">Sin agrupaciones</td>
          </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>

    <!-- DIÁLOGO CREAR / EDITAR -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 560px; max-width: 95vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle1 text-weight-bold">
            {{ form.id ? 'Editar agrupación' : 'Nueva agrupación' }}
          </div>
          <q-space />
          <q-btn flat dense round icon="close" v-close-popup />
        </q-card-section>

        <q-card-section class="q-gutter-sm">
          <q-input v-model="form.nombre" dense outlined label="Nombre del grupo" autofocus />
          <q-toggle v-model="form.activo" label="Activo (visible al crear solicitud)" />
          <q-select
            v-model="form.servicio_ids"
            :options="servicioOptionsFiltradas"
            multiple
            emit-value
            map-options
            dense
            outlined
            use-chips
            use-input
            input-debounce="150"
            label="Prestaciones"
            @filter="filtrarServicios"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" no-caps v-close-popup :disable="saving" />
          <q-btn color="primary" icon="save" label="Guardar" no-caps :loading="saving" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'ConfiguracionAgrupacionesPage',
  data () {
    return {
      loading: false,
      saving: false,
      filter: '',
      agrupaciones: [],
      servicios: [],
      servicioOptionsFiltradas: [],
      dialog: false,
      form: {
        id: null,
        nombre: '',
        activo: true,
        servicio_ids: []
      }
    }
  },
  computed: {
    servicioOptions () {
      return this.servicios.map(s => ({
        label: (s.codigo ? `[${s.codigo}] ` : '') + s.nombre + (s.estado === 'INACTIVO' ? ' (INACTIVO)' : ''),
        value: s.id
      }))
    },
    agrupacionesFiltradas () {
      const f = (this.filter || '').toLowerCase().trim()
      if (!f) return this.agrupaciones
      return this.agrupaciones.filter(a => (a.nombre || '').toLowerCase().includes(f))
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    load () {
      this.loading = true
      this.$axios.get('agrupaciones')
        .then(res => {
          this.servicios = res.data.servicios || []
          this.agrupaciones = (res.data.agrupaciones || []).map(a => ({
            ...a,
            activo: Boolean(a.activo),
            saving: false
          }))
        })
        .catch(e => {
          this.$alert.error('Error al cargar agrupaciones: ' + (e.response?.data?.message || e.message))
        })
        .finally(() => { this.loading = false })
    },
    filtrarServicios (val, update) {
      update(() => {
        const f = (val || '').toLowerCase().trim()
        this.servicioOptionsFiltradas = !f
          ? this.servicioOptions
          : this.servicioOptions.filter(o => o.label.toLowerCase().includes(f))
      })
    },
    abrirNueva () {
      this.form = { id: null, nombre: '', activo: true, servicio_ids: [] }
      this.dialog = true
    },
    abrirEditar (agrupacion) {
      this.form = {
        id: agrupacion.id,
        nombre: agrupacion.nombre,
        activo: Boolean(agrupacion.activo),
        servicio_ids: (agrupacion.prestaciones || []).map(p => p.id)
      }
      this.dialog = true
    },
    guardar () {
      if (!this.form.nombre || !this.form.nombre.trim()) {
        this.$alert.error('El nombre es obligatorio')
        return
      }
      this.saving = true
      const payload = {
        nombre: this.form.nombre.trim(),
        activo: this.form.activo,
        servicio_ids: this.form.servicio_ids || []
      }
      const req = this.form.id
        ? this.$axios.put(`agrupaciones/${this.form.id}`, payload)
        : this.$axios.post('agrupaciones', payload)
      req
        .then(() => {
          this.$alert.success('Agrupación guardada')
          this.dialog = false
          this.load()
        })
        .catch(e => {
          this.$alert.error('Error al guardar: ' + (e.response?.data?.message || e.message))
        })
        .finally(() => { this.saving = false })
    },
    guardarActivo (agrupacion) {
      agrupacion.saving = true
      this.$axios.put(`agrupaciones/${agrupacion.id}`, { activo: agrupacion.activo })
        .catch(e => {
          this.$alert.error('Error al guardar: ' + (e.response?.data?.message || e.message))
          this.load()
        })
        .finally(() => { agrupacion.saving = false })
    },
    eliminar (agrupacion) {
      this.$alert.dialog(`¿Eliminar la agrupación "${agrupacion.nombre}"?`)
        .onOk(() => {
          this.$axios.delete(`agrupaciones/${agrupacion.id}`)
            .then(() => {
              this.$alert.success('Agrupación eliminada')
              this.load()
            })
            .catch(e => {
              this.$alert.error('Error al eliminar: ' + (e.response?.data?.message || e.message))
            })
        })
    }
  }
}
</script>

<style scoped>
.q-markup-table th {
  font-size: 0.75rem;
  background: #f5f5f5;
}
.q-markup-table td {
  font-size: 0.75rem;
}
</style>
