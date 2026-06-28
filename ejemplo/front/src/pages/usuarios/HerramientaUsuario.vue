<template>
  <q-page class="q-pa-sm bg-grey-2">
    <div class="row items-center q-mb-sm">
      <div>
        <div class="text-h6 text-weight-bold">Herramientas de Usuarios</div>
        <div class="text-caption text-grey-7">Configuración del módulo de registro de usuarios</div>
      </div>
    </div>

    <div class="row q-col-gutter-sm">
      <!-- Ventana de registro -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section class="row items-center q-pb-xs">
            <q-icon name="schedule" size="22px" color="primary" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-bold">Ventana de registro de usuarios</div>
              <div class="text-caption text-grey-7">
                Período en que los usuarios pueden crear su propia cuenta
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <!-- Estado actual -->
          <q-card-section class="q-pa-sm">
            <q-banner
              v-if="settings.registro_habilitado"
              rounded
              class="bg-green-1 text-green-10 q-mb-sm"
              style="border: 1px solid #a5d6a7;"
            >
              <template #avatar>
                <q-icon name="check_circle" color="green-7" size="26px" />
              </template>
              <div class="text-weight-bold">Registro habilitado</div>
              <div class="text-body2">
                {{ formatDate(settings.fecha_inicio_registro) }}
                al
                {{ formatDate(settings.fecha_fin_registro) }}
              </div>
            </q-banner>
            <q-banner
              v-else
              rounded
              class="bg-orange-1 text-orange-10 q-mb-sm"
              style="border: 1px solid #ffcc80;"
            >
              <template #avatar>
                <q-icon name="block" color="orange-7" size="26px" />
              </template>
              <div class="text-weight-bold">Registro deshabilitado</div>
              <div class="text-body2">No hay un período activo en este momento.</div>
            </q-banner>
          </q-card-section>

          <!-- Formulario -->
          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.fecha_inicio_registro"
                  outlined dense type="datetime-local"
                  label="Fecha y hora inicio"
                  :disable="!canManage || loading"
                >
                  <template #prepend><q-icon name="event" /></template>
                </q-input>
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.fecha_fin_registro"
                  outlined dense type="datetime-local"
                  label="Fecha y hora fin"
                  :disable="!canManage || loading"
                >
                  <template #prepend><q-icon name="event_available" /></template>
                </q-input>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions align="right" class="q-pa-sm">
            <q-btn flat no-caps color="grey-8" icon="clear" label="Limpiar fechas"
                   :disable="!canManage || saving" @click="clearFechas" />
            <q-btn unelevated no-caps color="primary" icon="save" label="Guardar"
                   :disable="!canManage" :loading="saving" @click="save" />
          </q-card-actions>
        </q-card>
      </div>

      <!-- Panel datos actuales -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section class="row items-center q-pb-xs">
            <q-icon name="info" size="22px" color="blue-6" class="q-mr-sm" />
            <div class="text-subtitle1 text-weight-bold">Valores actuales en base de datos</div>
          </q-card-section>
          <q-separator />
          <q-card-section>
            <div v-if="loading" class="text-center q-pa-md">
              <q-spinner color="primary" size="32px" />
            </div>
            <div v-else class="db-table">
              <div class="db-row db-header">
                <span>Nombre</span>
                <span>Valor</span>
              </div>
              <div class="db-row">
                <span class="db-key">fecha_inicio_registro</span>
                <span class="db-val">{{ settings.fecha_inicio_registro || '(vacío)' }}</span>
              </div>
              <div class="db-row">
                <span class="db-key">fecha_fin_registro</span>
                <span class="db-val">{{ settings.fecha_fin_registro || '(vacío)' }}</span>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import moment from 'moment'

const { proxy } = getCurrentInstance()
const $q = useQuasar()

const loading = ref(false)
const saving  = ref(false)

const settings = ref({
  fecha_inicio_registro: null,
  fecha_fin_registro:    null,
  registro_habilitado:   false,
})

const form = ref({
  fecha_inicio_registro: '',
  fecha_fin_registro:    '',
})

const userPermissions = computed(() => proxy.$store.permissions || [])
const isAdmin = computed(() => proxy.$store.user?.role === 'Administrador')
const canManage = computed(() =>
  isAdmin.value || userPermissions.value.includes('Tiempo creación de usuario')
)

onMounted(fetchSettings)

async function fetchSettings () {
  loading.value = true
  try {
    const res = await proxy.$axios.get('herramientas-usuario')
    settings.value = res.data
    form.value.fecha_inicio_registro = res.data.fecha_inicio_registro || ''
    form.value.fecha_fin_registro    = res.data.fecha_fin_registro    || ''
  } finally {
    loading.value = false
  }
}

function formatDate (val) {
  if (!val) return '-'
  return moment(val).format('DD/MM/YYYY HH:mm')
}

function clearFechas () {
  form.value.fecha_inicio_registro = ''
  form.value.fecha_fin_registro    = ''
}

async function save () {
  const inicio = form.value.fecha_inicio_registro
  const fin    = form.value.fecha_fin_registro

  if ((inicio && !fin) || (!inicio && fin)) {
    $q.notify({ color: 'negative', message: 'Debes definir ambas fechas o dejar las dos vacías', position: 'top' })
    return
  }
  if (inicio && fin && fin < inicio) {
    $q.notify({ color: 'negative', message: 'La fecha fin debe ser igual o posterior a la fecha inicio', position: 'top' })
    return
  }

  saving.value = true
  try {
    const res = await proxy.$axios.put('herramientas-usuario', {
      fecha_inicio_registro: inicio || null,
      fecha_fin_registro:    fin    || null,
    })
    settings.value = res.data
    $q.notify({ color: 'positive', message: 'Configuración guardada', position: 'top' })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.db-table { border: 1px solid #e5eaf2; border-radius: 8px; overflow: hidden; }
.db-row { display: grid; grid-template-columns: 1fr 1fr; padding: 8px 12px; border-bottom: 1px solid #f0f2f5; font-size: 13px; }
.db-row:last-child { border-bottom: none; }
.db-header { background: #f7f9fc; font-weight: 700; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; }
.db-key { font-family: monospace; color: #1565c0; font-size: 12px; }
.db-val { font-weight: 600; color: #1f2937; }
</style>
