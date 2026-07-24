<template>
  <q-page class="q-pa-md">
    <div
      v-if="proxy.$store.isLogged && !canVer"
      class="column items-center justify-center q-gutter-sm"
      style="min-height: 320px"
    >
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver productos</div>
    </div>

    <template v-else-if="proxy.$store.isLogged">
      <div class="row items-center q-col-gutter-sm q-mb-md">
        <div class="col">
          <div class="text-h5 text-weight-bold">Laboratorio</div>
          <div class="text-body2 text-grey-6">
            Productos registrados en el tipo LABORATORIOS
          </div>
        </div>
        <div class="col-auto">
          <q-badge color="primary" class="q-pa-sm">
            {{ pagination.rowsNumber }} productos
          </q-badge>
        </div>
      </div>

      <q-card flat bordered>
        <q-card-section class="row items-center q-col-gutter-sm q-pa-sm">
          <div class="col-12 col-sm">
            <q-input
              v-model="search"
              dense
              outlined
              clearable
              debounce="400"
              placeholder="Buscar por nombre, código o marca"
              @update:model-value="buscar"
            >
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-auto">
            <q-btn
              flat
              round
              color="primary"
              icon="refresh"
              :loading="loading"
              @click="cargar"
            >
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>

        <q-separator />

        <q-table
          v-model:pagination="pagination"
          flat
          dense
          row-key="id"
          :rows="productos"
          :columns="columns"
          :loading="loading"
          :rows-per-page-options="[10, 20, 50]"
          no-data-label="No existen productos del tipo LABORATORIOS"
          @request="onRequest"
        >
          <template #body-cell-codigo="props">
            <q-td :props="props">
              <q-badge outline color="primary">
                {{ props.row.codigo || 'S/C' }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-nombre="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.nombre }}</div>
              <div v-if="props.row.descripcion" class="text-caption text-grey-6">
                {{ props.row.descripcion }}
              </div>
            </q-td>
          </template>

          <template #body-cell-tipo="props">
            <q-td :props="props">
              <q-badge :color="props.row.tipo_producto?.color || 'primary'">
                {{ props.row.tipo_producto?.nombre || 'LABORATORIOS' }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-precio="props">
            <q-td :props="props" class="text-weight-bold">
              Bs {{ money(props.row.precio) }}
            </q-td>
          </template>
        </q-table>
      </q-card>
    </template>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'

const { proxy } = getCurrentInstance()

const productos = ref([])
const loading = ref(false)
const search = ref('')
const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0,
  sortBy: 'nombre',
  descending: false,
})

const columns = [
  {
    name: 'codigo',
    label: 'Código',
    field: 'codigo',
    align: 'left',
  },
  {
    name: 'nombre',
    label: 'Producto',
    field: 'nombre',
    align: 'left',
    sortable: true,
  },
  {
    name: 'marca',
    label: 'Marca',
    field: row => row.marca || '—',
    align: 'left',
  },
  {
    name: 'unidad',
    label: 'Unidad',
    field: row => row.unidad?.nombre || row.unidad?.abreviatura || '—',
    align: 'left',
  },
  {
    name: 'tipo',
    label: 'Tipo',
    field: row => row.tipo_producto?.nombre,
    align: 'center',
  },
  {
    name: 'precio',
    label: 'Precio',
    field: 'precio',
    align: 'right',
    sortable: true,
  },
]

const canVer = computed(() => proxy.$store.hasPermission('Ver Productos'))

watch(
  () => proxy.$store.isLogged,
  logged => {
    if (logged && canVer.value) cargar()
  },
  { immediate: true },
)

function money (value) {
  return Number(value || 0).toFixed(2)
}

function buscar () {
  pagination.value.page = 1
  cargar()
}

function onRequest ({ pagination: requestedPagination }) {
  pagination.value = requestedPagination
  cargar()
}

async function cargar () {
  if (!canVer.value) return

  loading.value = true
  try {
    const { data } = await proxy.$axios.get('productos', {
      params: {
        tipo: 'LABORATORIOS',
        q: search.value || undefined,
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
      },
    })

    productos.value = data.data || []
    pagination.value = {
      ...pagination.value,
      page: data.current_page || 1,
      rowsPerPage: data.per_page || pagination.value.rowsPerPage,
      rowsNumber: data.total || 0,
    }
  } catch (error) {
    proxy.$alert.error(
      error.response?.data?.message || 'No se pudieron cargar los productos de laboratorio',
    )
  } finally {
    loading.value = false
  }
}
</script>
