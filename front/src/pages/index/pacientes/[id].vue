<template>
  <q-page class="q-pa-md">
    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="40px" />
    </div>

    <template v-else-if="paciente.id">
      <!-- ═══ HEADER ═══════════════════════════════════════════════ -->
      <div class="row items-center q-gutter-sm q-mb-md">
        <q-btn flat round dense icon="arrow_back" @click="proxy.$router.push('/pacientes')" />
        <q-avatar :style="'background:' + avatarColor(paciente.nombre_completo) + ';color:#fff'" size="40px">
          {{ initials(paciente.nombre_completo) }}
        </q-avatar>
        <div>
          <div class="text-h6 text-weight-bold">{{ paciente.nombre_completo }}</div>
          <div class="row items-center q-gutter-xs">
            <q-badge :color="estadoColor(paciente.estado_internacion)">
              <q-icon name="monitor_heart" size="12px" class="q-mr-xs" />
              {{ estadoLabel(paciente.estado_internacion) }}
            </q-badge>
          </div>
        </div>
        <q-space />
        <q-btn v-if="canEditar" outline round dense icon="edit" color="primary" @click="pacEdit">
          <q-tooltip>Editar paciente</q-tooltip>
        </q-btn>
        <q-btn v-if="canEliminar" outline round dense icon="delete" color="negative" @click="pacDelete">
          <q-tooltip>Eliminar paciente</q-tooltip>
        </q-btn>
      </div>

      <!-- ═══ FICHA DEL PACIENTE ═══════════════════════════════════ -->
      <q-card flat bordered class="q-mb-md ficha-card">
        <div class="row q-col-gutter-md">
          <div class="col-6 col-sm-3 ficha-field">
            <q-icon name="wc" color="primary" size="18px" />
            <div>
              <div class="text-caption text-grey-6">Sexo</div>
              <div class="text-body2 text-weight-medium">{{ paciente.sexo === 'M' ? 'Masculino' : paciente.sexo === 'F' ? 'Femenino' : '—' }}</div>
            </div>
          </div>
          <div class="col-6 col-sm-3 ficha-field">
            <q-icon name="badge" color="primary" size="18px" />
            <div>
              <div class="text-caption text-grey-6">CI</div>
              <div class="text-body2 text-weight-medium">{{ paciente.ci || '—' }}</div>
            </div>
          </div>
          <div class="col-6 col-sm-3 ficha-field">
            <q-icon name="toggle_on" color="primary" size="18px" />
            <div>
              <div class="text-caption text-grey-6">Estado</div>
              <div class="text-body2 text-weight-medium">{{ paciente.estado || '—' }}</div>
            </div>
          </div>
          <div class="col-6 col-sm-3 ficha-field">
            <q-icon name="phone" color="primary" size="18px" />
            <div>
              <div class="text-caption text-grey-6">Teléfono</div>
              <div class="text-body2 text-weight-medium">{{ paciente.telefono || '—' }}</div>
            </div>
          </div>
          <div class="col-12 ficha-field">
            <q-icon name="home" color="primary" size="18px" />
            <div>
              <div class="text-caption text-grey-6">Dirección</div>
              <div class="text-body2 text-weight-medium">{{ paciente.direccion || '—' }}</div>
            </div>
          </div>
        </div>
      </q-card>

      <!-- ═══ INTERNACIONES ═══════════════════════════════════════ -->
      <div class="row items-center q-mb-xs">
        <q-icon name="local_hospital" color="primary" size="20px" class="q-mr-xs" />
        <span class="text-subtitle1 text-weight-bold">Internaciones</span>
        <q-space />
        <q-btn v-if="canCrearInt" color="primary" label="Nueva internación" icon="add_circle_outline"
               no-caps dense unelevated @click="intNew" />
      </div>
      <q-separator class="q-mb-sm" />

      <div v-if="!paciente.internaciones.length" class="text-center text-grey-5 q-pa-lg">
        <q-icon name="event_busy" size="40px" color="grey-4" />
        <div>Sin internaciones registradas</div>
      </div>

      <template v-else>
        <q-tabs v-model="tab" dense align="left" active-color="primary" indicator-color="primary" class="q-mb-sm bg-blue-1 rounded-borders">
          <q-tab v-for="(int, idx) in paciente.internaciones" :key="int.id" :name="int.id" no-caps>
            <div class="row items-center q-gutter-xs">
              <q-icon name="event" size="16px" />
              <span>Internación {{ idx + 1 }}<template v-if="int.fecha_ingreso"> — {{ int.fecha_ingreso }}</template></span>
            </div>
          </q-tab>
        </q-tabs>

        <q-tab-panels v-model="tab" animated class="bg-transparent">
          <q-tab-panel v-for="int in paciente.internaciones" :key="int.id" :name="int.id" class="q-pa-none">
            <q-card flat bordered class="q-mb-md internacion-card">
              <div class="internacion-header row items-center q-px-md q-py-sm">
                <q-icon name="local_hospital" size="20px" color="white" class="q-mr-sm" />
                <span class="text-subtitle2 text-weight-bold text-white">Internación</span>
                <q-badge color="white" text-color="primary" class="q-ml-sm">
                  <q-icon name="hotel" size="12px" class="q-mr-xs" />
                  {{ int.dias_internado ?? '—' }} {{ int.dias_internado === 1 ? 'día' : 'días' }}
                </q-badge>
                <q-badge v-if="int.tipo_paciente" color="indigo" class="q-ml-sm">{{ int.tipo_paciente }}</q-badge>
                <q-badge v-if="int.sala" color="deep-purple" class="q-ml-sm">
                  <q-icon name="meeting_room" size="12px" class="q-mr-xs" />{{ int.sala }}
                </q-badge>
                <q-space />
                <q-btn dense flat round icon="print" color="white" :loading="printingId === int.id" @click="imprimir(int.id)">
                  <q-tooltip>Imprimir proforma</q-tooltip>
                </q-btn>
                <q-btn v-if="canEditarInt" dense flat round icon="edit" color="white" @click="intEdit(int)">
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn v-if="canEliminarInt" dense flat round icon="delete" color="white" @click="intDelete(int.id)">
                  <q-tooltip>Eliminar</q-tooltip>
                </q-btn>
              </div>

              <div class="q-pa-md">
                <div class="row q-col-gutter-md q-mb-md">
                  <div class="col-6 col-sm-3 ficha-field">
                    <q-icon name="login" color="primary" size="16px" />
                    <div>
                      <div class="text-caption text-grey-6">Fecha de ingreso</div>
                      <div class="text-body2 text-weight-medium">{{ int.fecha_ingreso || '—' }}</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-3 ficha-field">
                    <q-icon name="logout" color="primary" size="16px" />
                    <div>
                      <div class="text-caption text-grey-6">Fecha de alta</div>
                      <div class="text-body2 text-weight-medium">{{ int.fecha_alta || '—' }}</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-3 ficha-field">
                    <q-icon name="qr_code_2" color="primary" size="16px" />
                    <div>
                      <div class="text-caption text-grey-6">Código H.C.</div>
                      <div class="text-body2 text-weight-medium">{{ int.codigo_hc || '—' }}</div>
                    </div>
                  </div>
                </div>

                <!-- ═══ CARGOS ═══════════════════════════════════ -->
                <div class="row items-center q-mb-xs">
                  <q-icon name="receipt_long" color="primary" size="18px" class="q-mr-xs" />
                  <span class="text-subtitle2 text-weight-bold">Cargos de internación</span>
                  <q-space />
                  <q-btn v-if="canCrearInt" color="positive" label="Agregar cargo" icon="add_circle_outline"
                         no-caps dense unelevated @click="itemNew(int)" />
                </div>
                <q-markup-table dense flat bordered separator="cell">
                  <thead>
                    <tr class="bg-blue-1">
                      <th class="text-left">Ítem</th>
                      <th class="text-right" style="width:80px">Cant.</th>
                      <th class="text-right" style="width:90px">Precio</th>
                      <th class="text-right" style="width:100px">Total</th>
                      <th class="text-left" style="width:130px">Registrado por</th>
                      <th class="text-left" style="width:80px">Hora</th>
                      <th style="width:70px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!int.items || !int.items.length">
                      <td colspan="7" class="text-center text-grey-5 q-pa-sm">Sin cargos registrados</td>
                    </tr>
                    <tr v-for="item in int.items" :key="item.id">
                      <td>{{ item.nombre }}</td>
                      <td class="text-right">{{ formatCantidad(item.cantidad) }}</td>
                      <td class="text-right">{{ formatMoney(item.precio) }}</td>
                      <td class="text-right text-weight-medium">{{ formatMoney(item.total) }}</td>
                      <td>
                        <q-icon name="person" size="14px" color="grey-6" class="q-mr-xs" />{{ item.user?.name || '—' }}
                      </td>
                      <td>{{ formatHora(item.created_at) }}</td>
                      <td class="text-right">
                        <q-btn v-if="canEditarInt" dense flat round icon="edit" size="sm" @click="itemEdit(int, item)" />
                        <q-btn v-if="canEliminarInt" dense flat round icon="delete" color="negative" size="sm" @click="itemDelete(int, item.id)" />
                      </td>
                    </tr>
                  </tbody>
                  <tfoot v-if="int.items && int.items.length">
                    <tr class="bg-blue-1">
                      <td colspan="3" class="text-right text-weight-bold">TOTAL</td>
                      <td class="text-right text-weight-bold">{{ formatMoney(totalItems(int)) }}</td>
                      <td colspan="3"></td>
                    </tr>
                  </tfoot>
                </q-markup-table>
              </div>
            </q-card>
          </q-tab-panel>
        </q-tab-panels>
      </template>
    </template>

    <!-- DIALOG EDITAR PACIENTE -->
    <q-dialog v-model="dialogPac" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="badge" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">Editar paciente</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogPac = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="pacSave">
            <q-input v-model="pacForm.nombre_completo" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase>
              <template v-slot:prepend><q-icon name="person" /></template>
            </q-input>
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-select v-model="pacForm.sexo" label="Sexo" dense outlined clearable
                          :options="[{label:'Masculino',value:'M'},{label:'Femenino',value:'F'}]"
                          emit-value map-options>
                  <template v-slot:prepend><q-icon name="wc" /></template>
                </q-select>
              </div>
              <div class="col-6">
                <q-input v-model="pacForm.ci" label="CI" dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="badge" /></template>
                </q-input>
              </div>
            </div>
            <q-input v-model="pacForm.estado" label="Estado" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="toggle_on" /></template>
            </q-input>
            <q-input v-model="pacForm.direccion" label="Dirección" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="home" /></template>
            </q-input>
            <q-input v-model="pacForm.telefono" label="Teléfono" dense outlined class="q-mb-md" v-uppercase>
              <template v-slot:prepend><q-icon name="phone" /></template>
            </q-input>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogPac = false" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="savingPac" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG INTERNACION -->
    <q-dialog v-model="dialogInt" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="local_hospital" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ actionInt }} internación</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogInt = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="intSave">
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-input v-model="int.fecha_ingreso" label="Fecha de ingreso" dense outlined type="date">
                  <template v-slot:prepend><q-icon name="login" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model="int.fecha_alta" label="Fecha de alta" dense outlined type="date">
                  <template v-slot:prepend><q-icon name="logout" /></template>
                </q-input>
              </div>
            </div>
            <q-input v-model="int.tipo_paciente" label="Tipo de paciente" dense outlined class="q-mb-sm" v-uppercase>
              <template v-slot:prepend><q-icon name="category" /></template>
            </q-input>
            <div class="row q-col-gutter-sm q-mb-md">
              <div class="col-6">
                <q-input v-model="int.codigo_hc" label="Código H.C." dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="qr_code_2" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model="int.sala" label="Sala" dense outlined v-uppercase>
                  <template v-slot:prepend><q-icon name="meeting_room" /></template>
                </q-input>
              </div>
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogInt = false" />
              <q-btn color="primary" :label="int.id ? 'Guardar' : 'Crear'"
                     type="submit" no-caps :loading="savingInt" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG CARGO (ITEM) -->
    <q-dialog v-model="dialogItem" persistent>
      <q-card style="width:min(96vw,480px)">
        <q-card-section class="row items-center bg-primary text-white q-py-sm">
          <q-icon name="receipt_long" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ actionItem }} cargo</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialogItem = false" />
        </q-card-section>
        <q-card-section style="padding:14px 16px">
          <q-form @submit.prevent="itemSave">
            <q-select v-model="filtroTipoProducto" label="Categoría" dense outlined clearable
                      class="q-mb-sm" :options="allTipoProductos" option-value="id" option-label="nombre"
                      emit-value map-options @update:model-value="onFiltroTipoChange">
              <template v-slot:prepend><q-icon name="category" /></template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-badge :color="scope.opt.color || 'primary'" style="width:16px;height:16px" />
                  </q-item-section>
                  <q-item-section>{{ scope.opt.nombre }}</q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-select v-model="item.producto_id" label="Producto del catálogo (opcional)" dense outlined
                      class="q-mb-sm" clearable use-input input-debounce="300"
                      :options="productoOptions" option-value="id" option-label="nombre"
                      emit-value map-options @filter="filterProductos" @update:model-value="onProductoSelected">
              <template v-slot:prepend><q-icon name="inventory_2" /></template>
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                    <q-item-label caption>
                      <q-badge v-if="scope.opt.tipo_producto" :color="scope.opt.tipo_producto.color || 'primary'" class="q-mr-xs">
                        {{ scope.opt.tipo_producto.nombre }}
                      </q-badge>
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <div class="text-weight-bold text-primary">{{ formatMoney(scope.opt.precio) }} Bs.</div>
                  </q-item-section>
                </q-item>
              </template>
              <template v-slot:no-option>
                <q-item><q-item-section class="text-grey">Sin resultados — puede escribir un ítem libre abajo</q-item-section></q-item>
              </template>
            </q-select>
            <q-input v-model="item.nombre" label="Nombre del ítem *" dense outlined class="q-mb-sm"
                     :rules="[v => !!v || 'Requerido']" v-uppercase>
              <template v-slot:prepend><q-icon name="label" /></template>
            </q-input>
            <div class="row q-col-gutter-sm q-mb-sm">
              <div class="col-6">
                <q-input v-model.number="item.cantidad" label="Cantidad" dense outlined type="number" step="0.01" min="0.01">
                  <template v-slot:prepend><q-icon name="numbers" /></template>
                </q-input>
              </div>
              <div class="col-6">
                <q-input v-model.number="item.precio" label="Precio unitario (Bs.)" dense outlined type="number" step="0.01" min="0">
                  <template v-slot:prepend><q-icon name="attach_money" /></template>
                </q-input>
              </div>
            </div>
            <div class="text-right text-subtitle2 text-weight-bold text-primary q-mb-md">
              Total: {{ formatMoney((item.cantidad || 0) * (item.precio || 0)) }} Bs.
            </div>
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialogItem = false" />
              <q-btn color="primary" :label="item.id ? 'Guardar' : 'Agregar'"
                     type="submit" no-caps :loading="savingItem" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

const canEditar      = computed(() => proxy.$store.hasPermission('Editar Pacientes'))
const canEliminar    = computed(() => proxy.$store.hasPermission('Eliminar Pacientes'))
const canCrearInt    = computed(() => proxy.$store.hasPermission('Crear Internaciones'))
const canEditarInt   = computed(() => proxy.$store.hasPermission('Editar Internaciones'))
const canEliminarInt = computed(() => proxy.$store.hasPermission('Eliminar Internaciones'))

const loading  = ref(false)
const paciente = ref({ internaciones: [] })
const tab      = ref(null)

const PALETTE = ['#1565C0', '#2E7D32', '#E65100', '#B71C1C', '#6A1B9A', '#00838F', '#4E342E', '#37474F', '#0277BD', '#558B2F']

function avatarColor (name) {
  let h = 0
  for (const c of (name || '').toUpperCase()) h = c.charCodeAt(0) + ((h << 5) - h)
  return PALETTE[Math.abs(h) % PALETTE.length]
}

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function estadoLabel (estado) {
  return { INTERNADO: 'Internado', ALTA: 'Alta', NO_INTERNADO: 'No internado' }[estado] || estado
}
function estadoColor (estado) {
  return { INTERNADO: 'orange', ALTA: 'positive', NO_INTERNADO: 'grey-6' }[estado] || 'grey-6'
}

function formatMoney (v) {
  return Number(v || 0).toFixed(2)
}
function formatCantidad (v) {
  const n = Number(v || 0)
  return Number.isInteger(n) ? String(n) : n.toFixed(2)
}
function formatHora (dt) {
  if (!dt) return '—'
  const d = new Date(dt.replace(' ', 'T'))
  return isNaN(d) ? '—' : d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}
function totalItems (int) {
  return (int.items || []).reduce((s, it) => s + Number(it.total || 0), 0)
}

function fetchPaciente () {
  loading.value = true
  proxy.$axios.get('pacientes/' + proxy.$route.params.id).then(res => {
    paciente.value = res.data
    if (paciente.value.internaciones?.length && !tab.value) {
      tab.value = paciente.value.internaciones[paciente.value.internaciones.length - 1].id
    }
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; fetchPaciente() }
}, { immediate: true })

// ── Editar paciente ────────────────────────────────────────────
const dialogPac = ref(false)
const savingPac = ref(false)
const pacForm   = ref({})

function pacEdit () { pacForm.value = { ...paciente.value }; dialogPac.value = true }

async function pacSave () {
  savingPac.value = true
  try {
    await proxy.$axios.put('pacientes/' + paciente.value.id, pacForm.value)
    proxy.$alert.success('Paciente actualizado')
    dialogPac.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingPac.value = false
  }
}

function pacDelete () {
  proxy.$alert.dialog('¿Desea eliminar el paciente?').onOk(() => {
    proxy.$axios.delete('pacientes/' + paciente.value.id).then(() => {
      proxy.$alert.success('Paciente eliminado')
      proxy.$router.push('/pacientes')
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}

// ── CRUD Internaciones ───────────────────────────────────────
const dialogInt = ref(false)
const savingInt = ref(false)
const actionInt = ref('Nueva')
const int       = ref({})

function intNew ()     { int.value = { paciente_id: paciente.value.id, fecha_ingreso: '', tipo_paciente: '', fecha_alta: '', codigo_hc: '', sala: '' }; actionInt.value = 'Nueva';  dialogInt.value = true }
function intEdit (row) { int.value = { ...row }; actionInt.value = 'Editar'; dialogInt.value = true }

async function intSave () {
  savingInt.value = true
  try {
    if (int.value.id) {
      await proxy.$axios.put('internaciones/' + int.value.id, int.value)
      proxy.$alert.success('Internación actualizada')
    } else {
      await proxy.$axios.post('internaciones', int.value)
      proxy.$alert.success('Internación creada')
    }
    dialogInt.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingInt.value = false
  }
}

function intDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar la internación?').onOk(() => {
    proxy.$axios.delete('internaciones/' + id).then(() => {
      proxy.$alert.success('Internación eliminada')
      fetchPaciente()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}

// ── Imprimir proforma ────────────────────────────────────────
const printingId = ref(null)

async function imprimir (internacionId) {
  printingId.value = internacionId
  try {
    const res = await proxy.$axios.get('internaciones/' + internacionId + '/pdf', { responseType: 'blob' })
    window.open(window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' })), '_blank')
  } catch (err) {
    proxy.$alert.error('Error al generar el PDF')
  } finally {
    printingId.value = null
  }
}

// ── Cargos (items) ────────────────────────────────────────────
const dialogItem      = ref(false)
const savingItem      = ref(false)
const actionItem      = ref('Nuevo')
const item            = ref({})
const currentInt      = ref(null)
const productoOptions = ref([])
const allTipoProductos = ref([])
const filtroTipoProducto = ref(null)
let lastProductoFilter = ''

function fetchTipoProductos () {
  proxy.$axios.get('tipo-productos').then(res => {
    allTipoProductos.value = res.data
  }).catch(() => {})
}

function itemNew (intRow) {
  currentInt.value = intRow
  item.value = { producto_id: null, nombre: '', cantidad: 1, precio: 0 }
  actionItem.value = 'Nuevo'
  productoOptions.value = []
  filtroTipoProducto.value = null
  if (!allTipoProductos.value.length) fetchTipoProductos()
  dialogItem.value = true
}

function itemEdit (intRow, row) {
  currentInt.value = intRow
  item.value = { ...row }
  actionItem.value = 'Editar'
  productoOptions.value = []
  filtroTipoProducto.value = null
  if (!allTipoProductos.value.length) fetchTipoProductos()
  dialogItem.value = true
}

function filterProductos (val, update) {
  lastProductoFilter = val
  proxy.$axios.get('productos', {
    params: { q: val, tipo_producto_id: filtroTipoProducto.value, per_page: 30 },
  }).then(res => {
    update(() => { productoOptions.value = res.data.data || [] })
  }).catch(() => update(() => { productoOptions.value = [] }))
}

function onFiltroTipoChange () {
  filterProductos(lastProductoFilter, (cb) => cb())
}

function onProductoSelected (id) {
  const prod = productoOptions.value.find(p => p.id === id)
  if (prod) {
    item.value.nombre = prod.nombre
    item.value.precio = Number(prod.precio || 0)
  }
}

async function itemSave () {
  savingItem.value = true
  try {
    if (item.value.id) {
      await proxy.$axios.put('internacion-items/' + item.value.id, item.value)
      proxy.$alert.success('Cargo actualizado')
    } else {
      await proxy.$axios.post('internaciones/' + currentInt.value.id + '/items', item.value)
      proxy.$alert.success('Cargo agregado')
    }
    dialogItem.value = false
    fetchPaciente()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    savingItem.value = false
  }
}

function itemDelete (intRow, id) {
  proxy.$alert.dialog('¿Desea eliminar el cargo?').onOk(() => {
    proxy.$axios.delete('internacion-items/' + id).then(() => {
      proxy.$alert.success('Cargo eliminado')
      fetchPaciente()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    })
  })
}
</script>

<style scoped>
.ficha-card {
  border-left: 4px solid #1565C0;
}

.ficha-field {
  display: flex;
  align-items: center;
  gap: 8px;
}

.internacion-card {
  overflow: hidden;
}

.internacion-header {
  background: linear-gradient(90deg, #0D47A1 0%, #1565C0 100%);
}
</style>
