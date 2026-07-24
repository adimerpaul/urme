<template>
  <q-page class="q-pa-sm bg-grey-2">
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">Configuración Química Sanguínea</div>
          <div class="text-caption text-grey-7">
            Controla qué variables se muestran en la captura de química sanguínea según las prestaciones de la solicitud
          </div>
        </div>
        <div class="col-auto">
          <q-btn flat icon="refresh" label="Refrescar" no-caps :disable="loading" @click="load" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-py-xs">
        <q-banner dense rounded class="bg-blue-1 text-blue-10">
          <template v-slot:avatar>
            <q-icon name="info" color="primary" />
          </template>
          Una variable <b>sin prestaciones asignadas se muestra siempre</b>.
          Si tiene prestaciones, solo se muestra cuando la solicitud incluye alguna de ellas.
          Si se desactiva, no se muestra nunca.
        </q-banner>
      </q-card-section>
    </q-card>

    <q-card flat bordered>
      <q-card-section class="q-pa-sm">
        <q-input v-model="filter" dense outlined debounce="200" placeholder="Filtrar variable o nombre" class="q-mb-sm" style="max-width: 300px">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-markup-table dense flat bordered square class="bg-white">
          <thead>
          <tr>
            <th class="text-left" style="width: 200px">Variable</th>
            <th class="text-left" style="width: 200px">Nombre</th>
            <th class="text-left" style="width: 170px">Sección</th>
            <th class="text-center" style="width: 80px">Activo</th>
            <th class="text-left">Prestaciones que la muestran</th>
          </tr>
          </thead>
          <tbody>
          <template v-for="(grupo, seccion) in datosPorSeccion" :key="seccion">
            <tr class="bg-grey-3">
              <td colspan="5" class="text-weight-bold text-uppercase">{{ seccion }}</td>
            </tr>
            <tr v-for="dato in grupo" :key="dato.id">
              <td><span class="var-tag">{{ dato.variable }}</span></td>
              <td>{{ dato.nombre }}</td>
              <td>{{ dato.seccion }}</td>
              <td class="text-center">
                <q-toggle
                  v-model="dato.activo"
                  dense
                  :true-value="true"
                  :false-value="false"
                  :disable="dato.saving"
                  @update:model-value="save(dato)"
                />
              </td>
              <td>
                <q-select
                  v-model="dato.servicio_ids"
                  :options="servicioOptions"
                  multiple
                  emit-value
                  map-options
                  dense
                  outlined
                  use-chips
                  clearable
                  :loading="dato.saving"
                  :disable="dato.saving"
                  placeholder="Siempre visible"
                  @update:model-value="save(dato)"
                />
              </td>
            </tr>
          </template>
          <tr v-if="!loading && !datosFiltrados.length">
            <td colspan="5" class="text-center text-grey-7">Sin datos. Ejecute las migraciones del backend.</td>
          </tr>
          </tbody>
        </q-markup-table>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner size="42px" />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'ConfiguracionQuimicaSanguineaPage',
  data () {
    return {
      loading: false,
      filter: '',
      datos: [],
      servicios: []
    }
  },
  computed: {
    servicioOptions () {
      return this.servicios.map(s => ({
        label: s.estado === 'INACTIVO' ? `${s.nombre} (INACTIVO)` : s.nombre,
        value: s.id
      }))
    },
    datosFiltrados () {
      const f = (this.filter || '').toLowerCase().trim()
      if (!f) return this.datos
      return this.datos.filter(d =>
        (d.variable || '').toLowerCase().includes(f) ||
        (d.nombre || '').toLowerCase().includes(f)
      )
    },
    datosPorSeccion () {
      const grupos = {}
      this.datosFiltrados.forEach(d => {
        const s = d.seccion || 'Otros'
        if (!grupos[s]) grupos[s] = []
        grupos[s].push(d)
      })
      return grupos
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    load () {
      this.loading = true
      this.$axios.get('datos-quimica-sanguinea')
        .then(res => {
          this.servicios = res.data.servicios || []
          this.datos = (res.data.datos || []).map(d => ({
            ...d,
            activo: Boolean(d.activo),
            servicio_ids: (d.prestaciones || []).map(p => p.id),
            saving: false
          }))
        })
        .catch(e => {
          this.$alert.error('Error al cargar configuración: ' + (e.response?.data?.message || e.message))
        })
        .finally(() => { this.loading = false })
    },
    save (dato) {
      dato.saving = true
      this.$axios.put(`datos-quimica-sanguinea/${dato.id}`, {
        activo: dato.activo,
        servicio_ids: dato.servicio_ids || []
      })
        .then(res => {
          dato.prestaciones = res.data.prestaciones || []
          dato.servicio_ids = dato.prestaciones.map(p => p.id)
        })
        .catch(e => {
          this.$alert.error('Error al guardar: ' + (e.response?.data?.message || e.message))
          this.load()
        })
        .finally(() => { dato.saving = false })
    }
  }
}
</script>

<style scoped>
.var-tag {
  font-family: monospace;
  font-size: 11px;
  background: #eceff1;
  border: 1px solid #cfd8dc;
  border-radius: 4px;
  padding: 1px 5px;
  color: #37474f;
}
.q-markup-table th {
  font-size: 0.75rem;
  background: #f5f5f5;
}
.q-markup-table td {
  font-size: 0.75rem;
}
</style>
