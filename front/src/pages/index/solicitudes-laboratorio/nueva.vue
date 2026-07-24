<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">{{ editId ? 'Modificar laboratorio' : 'Crear laboratorio para paciente' }}</div>
        <div class="text-grey-7">{{ editId ? 'Actualice los datos y vuelva a generar el informe.' : 'Seleccione el paciente y las pruebas solicitadas.' }}</div>
      </div>
      <q-space />
      <q-btn flat icon="arrow_back" label="Volver" no-caps to="/solicitudes-laboratorio" />
    </div>

    <q-form @submit.prevent="guardar">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="text-subtitle1 text-weight-bold">Datos de la solicitud</q-card-section>
        <q-separator />
        <q-card-section class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-select v-model="form.paciente_id" :options="pacientesOpciones" option-value="id"
                      :option-label="pacienteLabel" emit-value map-options use-input input-debounce="200"
                      dense outlined label="Paciente *" :rules="[required]" :loading="pacientesLoading"
                      @filter="filtrarPacientes" @virtual-scroll="cargarMasPacientes">
              <template #prepend><q-icon name="person" /></template>
            </q-select>
          </div>
          <div class="col-12 col-md-6">
            <q-select v-model="form.doctor_id" :options="doctores" option-value="id" option-label="nombre"
                      emit-value map-options clearable dense outlined label="Doctor solicitante">
              <template #prepend><q-icon name="medical_information" /></template>
            </q-select>
          </div>
          <div class="col-6 col-md-3">
            <q-input v-model="form.fecha_solicitud" type="date" dense outlined label="Fecha *" :rules="[required]" />
          </div>
          <div class="col-6 col-md-3">
            <q-input v-model="form.hora_solicitud" type="time" dense outlined label="Hora *" :rules="[required]" />
          </div>
          <div class="col-12 col-md-6">
            <q-input v-model="form.diagnostico_clinico" v-uppercase dense outlined label="Diagnóstico clínico" />
          </div>
          <div class="col-12">
            <q-input v-model="form.observaciones" v-uppercase dense outlined type="textarea" autogrow label="Observaciones" />
          </div>
        </q-card-section>
      </q-card>

      <q-card flat bordered>
        <q-card-section class="row items-center">
          <div>
            <div class="text-subtitle1 text-weight-bold">Pruebas de laboratorio</div>
            <div class="text-caption text-grey-7">{{ seleccionados.length }} seleccionadas</div>
          </div>
          <q-space />
          <q-input v-model="buscarLaboratorio" dense outlined clearable label="Buscar prueba">
            <template #prepend><q-icon name="search" /></template>
          </q-input>
        </q-card-section>
        <q-separator />
        <q-card-section>
          <div class="row q-col-gutter-sm">
            <div v-for="laboratorio in laboratoriosFiltrados" :key="laboratorio.id" class="col-12 col-sm-6 col-lg-4">
              <q-card flat bordered class="full-height cursor-pointer"
                      :class="{ 'bg-green-1': form.producto_ids.includes(laboratorio.id) }"
                      @click="toggleLaboratorio(laboratorio.id)">
                <q-card-section class="row no-wrap items-start q-pa-sm">
                  <q-checkbox :model-value="form.producto_ids.includes(laboratorio.id)"
                              @click.stop @update:model-value="toggleLaboratorio(laboratorio.id)" />
                  <div class="q-ml-sm">
                    <div class="text-weight-medium">{{ laboratorio.nombre }}</div>
                    <div class="text-caption text-grey-7">
                      {{ laboratorio.codigo || 'SIN CÓDIGO' }} · {{ laboratorio.laboratorio_datos?.length || 0 }} datos
                    </div>
                    <div class="text-primary text-weight-bold">Bs {{ money(laboratorio.precio) }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
          <q-list v-if="seleccionados.length" bordered separator class="q-mt-md">
            <q-expansion-item v-for="laboratorio in seleccionados" :key="laboratorio.id"
                              default-opened icon="biotech" :label="laboratorio.nombre"
                              :caption="`${laboratorio.laboratorio_datos?.length || 0} datos para completar`">
              <q-card>
                <q-card-section class="row q-col-gutter-sm">
                  <div v-for="dato in laboratorio.laboratorio_datos" :key="dato.id" class="col-12 col-md-6">
                    <q-input v-if="!dato.formula" :model-value="valores[dato.id] || ''"
                             dense outlined :label="dato.nombre"
                             :suffix="dato.unidad || undefined"
                             :hint="dato.rango_referencia ? `Referencia: ${dato.rango_referencia}` : undefined"
                             @update:model-value="valor => valores[dato.id] = valor" />
                    <q-input v-else :model-value="calcularDato(laboratorio, dato)"
                             dense outlined readonly color="deep-purple"
                             :label="dato.nombre" :suffix="dato.unidad || undefined"
                             :hint="`Fórmula: ${dato.formula.formula}`">
                      <template #prepend><q-icon name="functions" color="deep-purple" /></template>
                    </q-input>
                  </div>
                </q-card-section>
              </q-card>
            </q-expansion-item>
          </q-list>
        </q-card-section>
        <q-separator />
        <q-card-actions align="right" class="q-pa-md">
          <div class="text-h6 q-mr-md">Total: Bs {{ money(total) }}</div>
          <q-btn flat label="Cancelar" no-caps to="/solicitudes-laboratorio" />
          <q-btn type="submit" color="positive" icon="save"
                 :label="editId ? 'Guardar e imprimir' : 'Crear e imprimir'"
                 no-caps :loading="saving" :disable="!form.producto_ids.length" />
        </q-card-actions>
      </q-card>
    </q-form>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()
const pacientesOpciones = ref([])
const pacientesLoading = ref(false)
const pacientesPage = ref(1)
const pacientesLastPage = ref(1)
const pacienteSearch = ref('')
const doctores = ref([])
const laboratorios = ref([])
const buscarLaboratorio = ref('')
const saving = ref(false)
const valores = ref({})
const editId = ref(proxy.$route.query.id || null)
const now = new Date()
const form = ref({
  paciente_id: null,
  doctor_id: null,
  fecha_solicitud: now.toISOString().slice(0, 10),
  hora_solicitud: now.toTimeString().slice(0, 5),
  diagnostico_clinico: '',
  observaciones: '',
  producto_ids: [],
})

const laboratoriosFiltrados = computed(() => {
  const search = buscarLaboratorio.value.toLowerCase().trim()
  return search
    ? laboratorios.value.filter(item => `${item.nombre} ${item.codigo || ''}`.toLowerCase().includes(search))
    : laboratorios.value
})
const seleccionados = computed(() => laboratorios.value.filter(item => form.value.producto_ids.includes(item.id)))
const total = computed(() => seleccionados.value.reduce((sum, item) => sum + Number(item.precio || 0), 0))

async function cargarDatos () {
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/form-data')
    doctores.value = data.doctores
    laboratorios.value = data.laboratorios
    await buscarPacientes('', 1)
    if (editId.value) await cargarSolicitud()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los datos')
  }
}
async function cargarSolicitud () {
  const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${editId.value}`)
  form.value = {
    paciente_id: data.paciente_id,
    doctor_id: data.doctor_id,
    fecha_solicitud: data.fecha_solicitud,
    hora_solicitud: String(data.hora_solicitud || '').slice(0, 5),
    diagnostico_clinico: data.diagnostico_clinico || '',
    observaciones: data.observaciones || '',
    producto_ids: (data.laboratorio_items || []).map(item => item.producto_id).filter(Boolean),
  }
  if (data.paciente && !pacientesOpciones.value.some(item => item.id === data.paciente.id)) {
    pacientesOpciones.value.unshift(data.paciente)
  }
  for (const item of data.laboratorio_items || []) {
    for (const resultado of item.resultados || []) {
      if (resultado.producto_laboratorio_dato_id) {
        valores.value[resultado.producto_laboratorio_dato_id] = resultado.valor || ''
      }
    }
  }
}
async function buscarPacientes (search, page, append = false) {
  pacientesLoading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/pacientes', {
      params: { q: search || undefined, page, per_page: 20 },
    })
    pacientesOpciones.value = append
      ? [...pacientesOpciones.value, ...data.data]
      : data.data
    pacientesPage.value = data.current_page
    pacientesLastPage.value = data.last_page
  } finally {
    pacientesLoading.value = false
  }
}
function filtrarPacientes (value, update, abort) {
  buscarPacientes(value.trim(), 1)
    .then(() => {
      pacienteSearch.value = value.trim()
      update(() => {})
    })
    .catch(abort)
}
async function cargarMasPacientes ({ to, ref }) {
  if (pacientesLoading.value || pacientesPage.value >= pacientesLastPage.value) return
  if (to < pacientesOpciones.value.length - 5) return
  await buscarPacientes(pacienteSearch.value, pacientesPage.value + 1, true)
  ref.refresh()
}
function pacienteLabel (paciente) {
  return `${paciente.nombre_completo}${paciente.ci ? ` · CI ${paciente.ci}` : ''}`
}
function toggleLaboratorio (id) {
  const index = form.value.producto_ids.indexOf(id)
  if (index >= 0) form.value.producto_ids.splice(index, 1)
  else form.value.producto_ids.push(id)
}
async function guardar () {
  if (!form.value.producto_ids.length) {
    proxy.$alert.error('Seleccione al menos una prueba de laboratorio')
    return
  }
  saving.value = true
  const ventanaImpresion = window.open('', '_blank')
  try {
    const resultados = seleccionados.value.flatMap(laboratorio =>
      (laboratorio.laboratorio_datos || []).map(dato => ({
        producto_laboratorio_dato_id: dato.id,
        valor: dato.formula ? calcularDato(laboratorio, dato) : (valores.value[dato.id] || null),
      }))
    )
    const payload = {
      ...form.value,
      resultados,
    }
    const { data } = editId.value
      ? await proxy.$axios.put(`solicitudes-laboratorio/${editId.value}`, payload)
      : await proxy.$axios.post('solicitudes-laboratorio', payload)
    await abrirPdf(data.id, ventanaImpresion)
    proxy.$alert.success(`Laboratorio ${data.codigo_solicitud} ${editId.value ? 'actualizado' : 'creado'}`)
    proxy.$router.push('/solicitudes-laboratorio')
  } catch (error) {
    ventanaImpresion?.close()
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el laboratorio')
  } finally {
    saving.value = false
  }
}
async function abrirPdf (id, ventana) {
  const response = await proxy.$axios.get(`solicitudes-laboratorio/${id}/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
  if (ventana) ventana.location.href = url
  else window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 60000)
}
function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
function firstValidationError (error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
function calcularDato (laboratorio, dato, procesando = new Set()) {
  if (!dato.formula) return valores.value[dato.id] || ''
  if (procesando.has(dato.id)) return ''
  const siguientes = new Set(procesando)
  siguientes.add(dato.id)
  const porVariable = Object.fromEntries((laboratorio.laboratorio_datos || []).map(item => [item.nombre_variable, item]))

  try {
    const resultado = evaluarExpresion(dato.formula.formula, variable => {
      const dependencia = porVariable[variable]
      if (!dependencia) throw new Error('Variable desconocida')
      const valor = dependencia.formula
        ? calcularDato(laboratorio, dependencia, siguientes)
        : valores.value[dependencia.id]
      const numero = Number(valor)
      if (valor === '' || valor === null || valor === undefined || !Number.isFinite(numero)) throw new Error('Dato pendiente')
      return numero
    })
    return Number.isFinite(resultado) ? String(Math.round(resultado * 100) / 100) : ''
  } catch {
    return ''
  }
}
function evaluarExpresion (expression, resolveVariable) {
  const tokens = expression.match(/[a-z][a-z0-9_]*|\d+(?:\.\d+)?|[()+\-*/]/gi) || []
  if (tokens.join('') !== expression.replace(/\s+/g, '')) throw new Error('Expresión inválida')
  let position = 0
  const parseFactor = () => {
    const token = tokens[position++]
    if (token === '(') {
      const value = parseExpression()
      if (tokens[position++] !== ')') throw new Error('Paréntesis inválidos')
      return value
    }
    if (token === '-') return -parseFactor()
    if (/^\d/.test(token || '')) return Number(token)
    if (/^[a-z]/i.test(token || '')) return resolveVariable(token.toLowerCase())
    throw new Error('Factor inválido')
  }
  const parseTerm = () => {
    let value = parseFactor()
    while (tokens[position] === '*' || tokens[position] === '/') {
      const operator = tokens[position++]
      const right = parseFactor()
      value = operator === '*' ? value * right : value / right
    }
    return value
  }
  const parseExpression = () => {
    let value = parseTerm()
    while (tokens[position] === '+' || tokens[position] === '-') {
      const operator = tokens[position++]
      const right = parseTerm()
      value = operator === '+' ? value + right : value - right
    }
    return value
  }
  const result = parseExpression()
  if (position !== tokens.length) throw new Error('Expresión incompleta')
  return result
}

cargarDatos()
</script>
