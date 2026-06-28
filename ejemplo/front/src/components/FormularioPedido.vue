<template>
  <div>
    <q-input
      v-model="form.nombre_usuario"
      label="Nombre Usuario"
      filled
      dense
      class="q-mb-md"
      :rules="[val => val && val.length > 0 || 'Requerido']"
    />

    <q-input
      v-model="form.fecha_hora"
      type="datetime-local"
      label="Fecha y Hora"
      filled
      dense
      class="q-mb-md"
      :rules="[val => val || 'Requerido']"
    />

    <div class="text-subtitle2 q-mb-md">Productos</div>

    <div class="q-mb-md">
      <q-table
        :rows="form.items"
        :columns="columns"
        row-key="id"
        flat
        bordered
        dense
      >
        <template #body-cell-producto="props">
          <q-td :props="props">
            <q-select
              v-model="props.row.producto_id"
              :options="productos"
              option-value="id"
              option-label="nombre"
              filled
              dense
              emit-value
              map-options
              @update:model-value="updateProduct(props.row)"
            />
          </q-td>
        </template>
        <template #body-cell-cantidad="props">
          <q-td :props="props">
            <q-input
              v-model.number="props.row.cantidad"
              type="number"
              filled
              dense
              @update:model-value="calculateSubtotal(props.row)"
            />
          </q-td>
        </template>
        <template #body-cell-precio_unitario="props">
          <q-td :props="props">
            <q-input
              v-model.number="props.row.precio_unitario"
              type="number"
              filled
              dense
              readonly
            />
          </q-td>
        </template>
        <template #body-cell-subtotal="props">
          <q-td :props="props">
            {{ formatCurrency(props.row.subtotal) }}
          </q-td>
        </template>
        <template #body-cell-acciones="props">
          <q-td :props="props">
            <q-btn
              color="negative"
              icon="delete"
              size="sm"
              flat
              @click="removeItem(props.rowIndex)"
            />
          </q-td>
        </template>
      </q-table>
    </div>

    <q-btn
      color="primary"
      label="Agregar Producto"
      icon="add"
      @click="addItem"
      class="q-mb-md"
    />

    <div class="row justify-end q-mb-md">
      <div class="text-h6">Total: {{ formatCurrency(totalAmount) }}</div>
    </div>

    <div class="row justify-end q-gutter-md">
      <q-btn
        color="secondary"
        label="Cancelar"
        @click="$emit('cancel')"
        flat
      />
      <q-btn
        color="positive"
        label="Guardar Pedido"
        @click="savePedido"
        :loading="loading"
        :disable="form.items.length === 0"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, getCurrentInstance, onMounted } from 'vue'

const emit = defineEmits(['saved', 'cancel'])
const { proxy } = getCurrentInstance()

const loading = ref(false)
const productos = ref([])

const form = ref({
  nombre_usuario: '',
  fecha_hora: new Date().toISOString().slice(0, 16),
  items: []
})

const columns = [
  { name: 'producto_id', label: 'Producto', field: 'producto_id', align: 'left' },
  { name: 'cantidad', label: 'Cantidad', field: 'cantidad', align: 'left' },
  { name: 'precio_unitario', label: 'Precio', field: 'precio_unitario', align: 'left' },
  { name: 'subtotal', label: 'Subtotal', field: 'subtotal', align: 'left' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' }
]

const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.subtotal || 0), 0)
})

onMounted(async () => {
  try {
    const response = await proxy.$axios.get('almacen-items')
    productos.value = response.data.data || response.data
  } catch (error) {
    console.error('Error loading products:', error)
  }
})

const addItem = () => {
  form.value.items.push({
    id: Date.now(),
    producto_id: null,
    cantidad: 1,
    precio_unitario: 0,
    subtotal: 0
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const updateProduct = (item) => {
  const producto = productos.value.find(p => p.id === item.producto_id)
  if (producto) {
    item.precio_unitario = Number(producto.precio_unitario ?? producto.precio ?? 0)
    calculateSubtotal(item)
  }
}

const calculateSubtotal = (item) => {
  item.subtotal = (item.cantidad || 0) * (item.precio_unitario || 0)
}

const savePedido = async () => {
  if (form.value.items.length === 0) {
    return
  }

  loading.value = true
  try {
    await proxy.$axios.post('pedidos', {
      items: form.value.items.map(item => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad,
        precio_unitario: item.precio_unitario,
      }))
    })
    emit('saved')
  } catch (error) {
    console.error('Error saving pedido:', error)
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR'
  }).format(value)
}
</script>
