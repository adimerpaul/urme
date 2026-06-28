<template>
  <q-page class="q-pa-sm">

    <!-- FILTROS -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-xs q-pb-xs">
        <div class="col-12 col-sm-auto">
          <q-input v-model="filters.from" type="date" dense outlined label="Desde" style="min-width:140px" />
        </div>
        <div class="col-12 col-sm-auto">
          <q-input v-model="filters.to" type="date" dense outlined label="Hasta" style="min-width:140px" />
        </div>
        <div class="col-12 col-sm">
          <q-input v-model="filters.search" dense outlined clearable label="Buscar por código, paciente o CI"
                   @keyup.enter="fetchRows" @clear="fetchRows">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </div>
        <div class="col-auto">
          <q-btn color="primary" icon="search" label="Buscar" no-caps :loading="loading" @click="fetchRows" />
        </div>
        <div class="col-auto" v-if="selected.length">
          <q-btn color="teal" icon="assignment_turned_in"
                 :label="`Registrar entrega (${selected.length})`"
                 no-caps :loading="saving" @click="registrarEntrega" />
        </div>
      </q-card-section>

      <q-card-section class="q-pt-xs q-pb-sm">
        <div class="row q-gutter-xs items-center">
          <span class="text-caption text-grey-6 q-mr-xs">Estado:</span>
          <q-chip dense square clickable class="q-ma-none text-caption"
                  :color="filters.estado === '' ? 'blue-grey-7' : 'blue-grey-2'"
                  :text-color="filters.estado === '' ? 'white' : 'blue-grey-9'"
                  icon="list" @click="setEstado('')">Todos</q-chip>
          <q-chip dense square clickable class="q-ma-none text-caption"
                  :color="filters.estado === 'pendiente' ? 'orange-7' : 'orange-2'"
                  :text-color="filters.estado === 'pendiente' ? 'white' : 'orange-10'"
                  icon="hourglass_empty" @click="setEstado('pendiente')">Pendiente entrega</q-chip>
          <q-chip dense square clickable class="q-ma-none text-caption"
                  :color="filters.estado === 'entregado' ? 'teal-7' : 'teal-2'"
                  :text-color="filters.estado === 'entregado' ? 'white' : 'teal-10'"
                  icon="check_circle" @click="setEstado('entregado')">Entregados</q-chip>
          <div class="q-ml-auto row items-center q-gutter-xs">
            <q-checkbox dense v-model="allSelected" color="teal" label="Seleccionar todos" />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- ESTADO CARGA -->
    <div v-if="loading" class="text-center q-pa-lg text-grey-6">
      <q-spinner color="primary" size="28px" class="q-mr-sm" /> Cargando...
    </div>
    <div v-else-if="!areaGroups.length" class="text-center q-pa-lg text-grey-5">
      Sin resultados
    </div>

    <!-- TABLA POR ÁREA -->
    <q-markup-table v-else dense flat bordered class="sil-table">
      <thead>
        <tr class="bg-blue-grey-1">
          <th class="text-left" style="width:160px">Área</th>
          <th class="text-left">Códigos</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="group in areaGroups" :key="group.area">
          <td class="text-weight-bold text-blue-grey-8"
              style="font-size:13px; vertical-align:top; padding-top:10px;">
            {{ group.area }}
          </td>
          <td>
            <div class="row items-center q-gutter-sm flex-wrap q-py-xs">
              <div
                v-for="sol in group.solicitudes"
                :key="sol.id + '-' + group.area"
                class="row items-center no-wrap codigo-chip"
                :class="isEntregado(sol, group.area) ? 'entregado' : 'pendiente'"
                @click="!isEntregado(sol, group.area) && toggleSelected(sol.id, group.area)"
              >
                <!-- Checkbox o check icon -->
                <q-checkbox
                  v-if="!isEntregado(sol, group.area)"
                  dense
                  :model-value="isSelected(sol.id, group.area)"
                  color="teal"
                  class="q-mr-xs"
                  @update:model-value="toggleSelected(sol.id, group.area)"
                  @click.stop
                />
                <q-icon v-else name="check_circle" color="teal" size="14px" class="q-mr-xs" />

                <!-- Código -->
                <span class="text-weight-bold" style="font-size:15px;"
                      :class="isEntregado(sol, group.area) ? 'text-teal-8' : 'text-primary'">
                  {{ sol.codigo }}
                </span>

                <!-- Info entrega inline -->
                <template v-if="isEntregado(sol, group.area)">
                  <span class="text-grey-6 q-ml-xs" style="font-size:11px; white-space:nowrap;">
                    · {{ getEntrega(sol, group.area).user?.name }}
                    <q-icon name="schedule" size="10px" class="q-mx-xs" />{{ getEntrega(sol, group.area).hora_entrega?.slice(0,5) }}
                  </span>
                </template>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </q-markup-table>

    <!-- TOTAL -->
    <div class="q-px-xs q-py-sm">
      <span class="text-caption text-grey-6">Total: {{ pagination.total }}</span>
    </div>

  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'EntregaResultadosPage',
  data () {
    return {
      loading: false,
      saving: false,
      rows: [],
      // cada elemento: { solicitude_id, area }
      selected: [],
      filters: {
        from: moment().startOf('month').format('YYYY-MM-DD'),
        to: moment().endOf('month').format('YYYY-MM-DD'),
        search: '',
        estado: 'pendiente',
      },
      pagination: { page: 1, per_page: 9999, total: 0 },
    }
  },
  computed: {
    areaGroups () {
      const map = new Map()
      this.rows.forEach(row => {
        const areas = [...new Set(row.servicios.map(s => s.area?.name || 'Sin área'))]
        areas.forEach(areaName => {
          if (!map.has(areaName)) map.set(areaName, [])
          if (!map.get(areaName).find(r => r.id === row.id)) {
            map.get(areaName).push(row)
          }
        })
      })
      return [...map.entries()].map(([area, solicitudes]) => ({ area, solicitudes }))
    },
    allPendientes () {
      const items = []
      this.areaGroups.forEach(group => {
        group.solicitudes.forEach(sol => {
          if (!this.isEntregado(sol, group.area)) {
            items.push({ solicitude_id: sol.id, area: group.area })
          }
        })
      })
      return items
    },
    allSelected: {
      get () {
        const pendientes = this.allPendientes
        if (!pendientes.length) return false
        return pendientes.every(p => this.isSelected(p.solicitude_id, p.area))
      },
      set (val) {
        if (val) {
          this.selected = this.allPendientes
        } else {
          this.selected = []
        }
      },
    },
  },
  mounted () {
    this.fetchRows()
  },
  methods: {
    isEntregado (sol, area) {
      return (sol.entrega_resultados || []).some(e => e.area === area)
    },
    getEntrega (sol, area) {
      return (sol.entrega_resultados || []).find(e => e.area === area) || {}
    },
    isSelected (solId, area) {
      return this.selected.some(s => s.solicitude_id === solId && s.area === area)
    },
    toggleSelected (solId, area) {
      const idx = this.selected.findIndex(s => s.solicitude_id === solId && s.area === area)
      if (idx >= 0) this.selected.splice(idx, 1)
      else this.selected.push({ solicitude_id: solId, area })
    },
    setEstado (val) {
      this.filters.estado = val
      this.pagination.page = 1
      this.fetchRows()
    },
    async fetchRows () {
      this.loading = true
      this.selected = []
      try {
        const res = await this.$axios.get('entrega-resultados', {
          params: {
            ...this.filters,
            page: this.pagination.page,
            per_page: this.pagination.per_page,
          },
        })
        this.rows = res.data.rows || []
        this.pagination.total = res.data.pagination?.total || 0
      } catch (e) {
        this.$alert?.error?.('Error al cargar datos')
      } finally {
        this.loading = false
      }
    },
    async registrarEntrega () {
      if (!this.selected.length) return
      this.saving = true
      try {
        const res = await this.$axios.post('entrega-resultados/registrar', {
          items: this.selected,
        })
        this.$alert?.success?.(`Entrega registrada para ${res.data.count} ítem(s)`)
        this.selected = []
        await this.fetchRows()
      } catch (e) {
        this.$alert?.error?.(e.response?.data?.message || 'Error al registrar entrega')
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<style scoped>
.codigo-chip {
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}
.codigo-chip.pendiente {
  background: #e3f2fd;
  border: 1px solid #90caf9;
}
.codigo-chip.pendiente:hover {
  background: #bbdefb;
}
.codigo-chip.entregado {
  background: #e0f2f1;
  border: 1px solid #80cbc4;
  cursor: default;
}
</style>
