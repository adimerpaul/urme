<template>
  <q-page class="q-pa-sm cierres-caja">
    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm" style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver los cierres de caja</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-mb-xs">
        <div>
          <div class="text-h6 text-weight-bold">Cierres de caja</div>
          <div class="text-caption text-grey-6">Un cierre por usuario y por día</div>
        </div>
        <q-space />
        <q-btn flat round dense color="primary" icon="refresh" :loading="loading" @click="cargar">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>

      <div class="row q-col-gutter-xs q-mb-xs items-center">
        <div class="col-auto">
          <q-input v-model="filtro.fecha_inicio" label="Desde" dense outlined type="date"
                   style="width:150px" @update:model-value="buscar" />
        </div>
        <div class="col-auto">
          <q-input v-model="filtro.fecha_fin" label="Hasta" dense outlined type="date"
                   style="width:150px" @update:model-value="buscar" />
        </div>
        <!-- El filtro por usuario solo aparece si el usuario puede listar usuarios. -->
        <div v-if="usuarios.length" class="col-auto">
          <q-select v-model="filtro.user_id" label="Usuario" dense outlined clearable
                    :options="usuarios" option-value="id" option-label="name"
                    emit-value map-options style="width:220px" @update:model-value="buscar" />
        </div>
        <q-space />
        <div class="col-auto text-caption text-grey-7">
          Sistema: <b>{{ money(totalSistema) }} Bs</b>
          <span class="q-mx-xs">·</span>
          Declarado: <b class="text-primary">{{ money(totalDeclarado) }} Bs</b>
          <span class="q-mx-xs">·</span>
          Diferencia:
          <b :class="totalDiferencia === 0 ? 'text-positive'
            : (totalDiferencia > 0 ? 'text-blue-8' : 'text-negative')">
            {{ money(totalDiferencia) }} Bs
          </b>
        </div>
      </div>

      <q-markup-table dense flat bordered separator="cell" class="full-width tabla-compacta">
        <thead>
          <tr class="bg-grey-1 text-grey-7 text-uppercase">
            <th class="text-left">Fecha</th>
            <th class="text-left">Usuario</th>
            <th class="text-right">Ventas del día</th>
            <th class="text-right">Sistema (Bs)</th>
            <th class="text-right">Declarado (Bs)</th>
            <th class="text-right">Diferencia</th>
            <th class="text-left">Cerrado el</th>
            <th class="text-center">Corrección</th>
            <th class="text-left">Comentario</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="9" class="text-center q-pa-md"><q-spinner color="primary" size="24px" /></td>
          </tr>
          <tr v-else-if="!cierres.length">
            <td colspan="9" class="text-center text-grey-5 q-pa-md">Sin cierres registrados</td>
          </tr>
          <tr v-else v-for="row in cierres" :key="row.id">
            <td>{{ formatSoloFecha(row.fecha) }}</td>
            <td>{{ row.user?.name || '—' }}</td>
            <td class="text-right">{{ row.cantidad_ventas }}</td>
            <td class="text-right">{{ money(row.monto_sistema) }}</td>
            <td class="text-right text-weight-bold">{{ money(row.monto) }}</td>
            <td class="text-right">
              <q-badge :color="Number(row.diferencia) === 0 ? 'positive'
                : (Number(row.diferencia) > 0 ? 'blue-7' : 'negative')">
                {{ money(row.diferencia) }}
              </q-badge>
            </td>
            <td>{{ formatFecha(row.fecha_hora) }}</td>
            <td class="text-center">
              <q-badge v-if="row.modificado_en" color="orange-8">
                {{ formatFecha(row.modificado_en) }}
              </q-badge>
              <span v-else class="text-grey-5">—</span>
            </td>
            <td>{{ row.comentario || '—' }}</td>
          </tr>
        </tbody>
      </q-markup-table>

      <div class="row items-center justify-between q-mt-xs q-px-xs">
        <div class="text-caption text-grey-6">
          Total: {{ total }} | Página {{ page }} de {{ paginas }}
        </div>
        <q-pagination v-model="page" :max="paginas" :max-pages="6"
                      boundary-links direction-links size="sm" @update:model-value="cargar" />
      </div>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { formatBoliviaDate, formatBoliviaDateTime } from '../../../addons/dateTime'

const { proxy } = getCurrentInstance()

const canVer = computed(() => proxy.$store.hasPermission('Ver Cierres Caja'))

const cierres = ref([])
const usuarios = ref([])
const loading = ref(false)
const page = ref(1)
const total = ref(0)
const porPagina = 15
const filtro = ref({ fecha_inicio: '', fecha_fin: '', user_id: null })

const paginas = computed(() => Math.max(1, Math.ceil(total.value / porPagina)))
const totalDeclarado = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto || 0), 0))
const totalSistema = computed(() => cierres.value.reduce((suma, c) => suma + Number(c.monto_sistema || 0), 0))
const totalDiferencia = computed(() => Math.round((totalDeclarado.value - totalSistema.value) * 100) / 100)

function money (v) { return Number(v || 0).toFixed(2) }
function formatFecha (v) { return formatBoliviaDateTime(v) }
function formatSoloFecha (v) { return formatBoliviaDate(v, '—') }

function buscar () {
  page.value = 1
  cargar()
}

async function cargar () {
  if (!canVer.value) return
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('cierres-caja', {
      params: {
        fecha_inicio: filtro.value.fecha_inicio || undefined,
        fecha_fin: filtro.value.fecha_fin || undefined,
        user_id: filtro.value.user_id || undefined,
        page: page.value,
        per_page: porPagina,
      },
    })
    cierres.value = data.data || []
    total.value = data.total || 0
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los cierres')
  } finally {
    loading.value = false
  }
}

async function cargarUsuarios () {
  try {
    const { data } = await proxy.$axios.get('users')
    usuarios.value = data.data || data || []
  } catch {
    // El filtro por usuario es opcional: sin la lista la pantalla sigue funcionando.
  }
}

watch(() => proxy.$store.isLogged, logged => {
  if (logged && canVer.value) {
    cargar()
    cargarUsuarios()
  }
}, { immediate: true })
</script>

<style scoped>
.tabla-compacta :deep(th),
.tabla-compacta :deep(td) {
  font-size: 11px;
  padding: 3px 8px;
}

.cierres-caja :deep(.q-field--dense .q-field__control),
.cierres-caja :deep(.q-field--dense .q-field__marginal) {
  height: 30px;
  min-height: 30px;
}
.cierres-caja :deep(.q-field--dense .q-field__native),
.cierres-caja :deep(.q-field--dense .q-field__label) {
  font-size: 11px;
}
</style>
