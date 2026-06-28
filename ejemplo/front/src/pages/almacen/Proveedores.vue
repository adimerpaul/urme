<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-sm-5">
          <q-input
            dense
            outlined
            v-model="filters.q"
            label="Buscar proveedor"
            debounce="300"
            clearable
            @update:model-value="getProveedores"
          >
            <template #append><q-icon name="search" /></template>
          </q-input>
        </div>

        <div class="col-12 col-sm-3">
          <q-select
            v-model="filters.estado"
            :options="estadoOptions"
            dense
            outlined
            emit-value
            map-options
            clearable
            label="Estado"
            @update:model-value="getProveedores"
          />
        </div>

        <div class="col-12 col-sm-4 text-right">
          <q-btn
            color="primary"
            icon="add_circle_outline"
            label="Nuevo"
            no-caps
            :loading="loading"
            @click="nuevo"
          />
          <q-btn
            class="q-ml-sm"
            flat
            icon="refresh"
            label="Actualizar"
            no-caps
            :loading="loading"
            @click="getProveedores"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-table
      :rows="rows"
      :columns="columns"
      row-key="id"
      dense
      flat
      bordered
      :rows-per-page-options="[0]"
      :loading="loading"
      title="Proveedores"
    >
      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-chip
            dense
            :color="(props.row.estado || 'Activo') === 'Activo' ? 'positive' : 'grey-6'"
            text-color="white"
            :icon="(props.row.estado || 'Activo') === 'Activo' ? 'check_circle' : 'pause_circle'"
          >
            {{ props.row.estado || 'Activo' }}
          </q-chip>
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
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section>Eliminar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <q-dialog v-model="dialog">
      <q-card style="min-width: 520px; max-width: 980px;">
        <q-card-section class="row items-center">
          <div class="text-h6">{{ editando ? 'Editar proveedor' : 'Nuevo proveedor' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit.prevent="guardar">
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.nombre" dense outlined label="Nombre *" autofocus />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="proveedor.estado"
                  dense
                  outlined
                  label="Estado"
                  emit-value
                  map-options
                  :options="estadoOptions"
                  clearable
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.carnet" dense outlined label="Carnet" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.nit" dense outlined label="NIT" />
              </div>

              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.razon_social" dense outlined label="Razón social" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.contacto" dense outlined label="Contacto" />
              </div>

              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.telefono" dense outlined label="Teléfono" />
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="proveedor.email" dense outlined type="email" label="Email" />
              </div>

              <div class="col-12">
                <q-input v-model="proveedor.direccion" dense outlined label="Dirección" />
              </div>

              <div class="col-12">
                <q-input v-model="proveedor.observacion" dense outlined type="textarea" autogrow label="Observación" />
              </div>
            </div>

            <div class="row q-gutter-sm q-mt-md">
              <q-space />
              <q-btn label="Cancelar" flat no-caps color="grey-8" v-close-popup />
              <q-btn
                type="submit"
                :label="editando ? 'Guardar cambios' : 'Crear proveedor'"
                no-caps
                unelevated
                color="primary"
                :loading="saving"
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
  name: 'ProveedoresPage',
  data () {
    return {
      loading: false,
      saving: false,
      dialog: false,
      editando: false,
      rows: [],
      filters: {
        q: '',
        estado: null
      },
      estadoOptions: [
        { label: 'Activo', value: 'Activo' },
        { label: 'Inactivo', value: 'Inactivo' }
      ],
      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
        { name: 'nit', label: 'NIT', field: 'nit', align: 'left' },
        { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
        { name: 'email', label: 'Email', field: 'email', align: 'left' },
        { name: 'estado', label: 'Estado', field: 'estado', align: 'center' }
      ],
      proveedor: {}
    }
  },
  mounted () {
    this.getProveedores()
  },
  methods: {
    getProveedores () {
      this.loading = true
      this.$axios.get('proveedores', {
        params: {
          q: this.filters.q || null,
          estado: this.filters.estado || null
        }
      })
        .then(res => {
          this.rows = res.data || []
        })
        .catch(e => {
          console.log(e)
          this.$alert?.error && this.$alert.error('No se pudo cargar proveedores')
        })
        .finally(() => {
          this.loading = false
        })
    },
    nuevo () {
      this.proveedor = {
        nombre: '',
        carnet: '',
        telefono: '',
        direccion: '',
        email: '',
        nit: '',
        razon_social: '',
        contacto: '',
        estado: 'Activo',
        observacion: ''
      }
      this.editando = false
      this.dialog = true
    },
    editar (row) {
      this.proveedor = { ...row }
      this.editando = true
      this.dialog = true
    },
    guardar () {
      if (!this.proveedor?.nombre) {
        this.$alert?.error && this.$alert.error('El nombre es obligatorio')
        return
      }

      this.saving = true
      const payload = { ...this.proveedor }
      const request = this.editando
        ? this.$axios.put(`proveedores/${this.proveedor.id}`, payload)
        : this.$axios.post('proveedores', payload)

      request
        .then(() => {
          this.$alert?.success && this.$alert.success(this.editando ? 'Proveedor actualizado' : 'Proveedor creado')
          this.dialog = false
          this.getProveedores()
        })
        .catch(e => {
          const msg = e.response?.data?.message || e.message
          this.$alert?.error && this.$alert.error('Error al guardar: ' + msg)
        })
        .finally(() => {
          this.saving = false
        })
    },
    eliminar (id) {
      const doDelete = () => {
        this.$axios.delete(`proveedores/${id}`).then(() => {
          this.$alert?.success && this.$alert.success('Proveedor eliminado')
          this.getProveedores()
        })
      }

      if (this.$alert && this.$alert.dialog) {
        this.$alert.dialog('¿Eliminar proveedor?').onOk(() => doDelete())
      } else {
        if (confirm('¿Eliminar proveedor?')) doDelete()
      }
    }
  }
}
</script>

