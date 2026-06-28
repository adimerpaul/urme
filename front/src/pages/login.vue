<template>
  <q-layout class="login-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="login-bg-overlay"></div>

        <div class="login-wrap">
          <q-card flat bordered class="login-card">

            <q-card-section class="q-pt-lg text-center">
              <q-img src="logo.png" width="110px" class="q-mb-sm" ratio="1" fit="contain" />
              <div class="text-subtitle2 text-grey-7 brand-chip">
                <b>Hospital General</b> · San Juan de Dios
              </div>
            </q-card-section>

            <q-separator spaced />

            <!-- ── Login ── -->
            <template v-if="vista === 'login'">
              <q-card-section class="q-pt-none">
                <q-form @submit.prevent="login">
                  <div class="text-h6 text-bold q-mb-xs">Iniciar sesión</div>
                  <div class="text-body2 text-grey-7 q-mb-md">
                    Accede al panel usando tus credenciales.
                  </div>

                  <div class="q-mb-sm text-caption text-grey-7">Nombre de usuario</div>
                  <q-input v-model="username" outlined dense placeholder="Nombre de usuario"
                           :rules="[v => !!v || 'Ingrese su nombre de usuario']" class="q-mb-md">
                    <template #prepend><q-icon name="account_circle" size="18px"/></template>
                  </q-input>

                  <div class="q-mb-sm text-caption text-grey-7">Contraseña</div>
                  <q-input v-model="password" outlined dense
                           :type="showPassword ? 'text' : 'password'" placeholder="Contraseña"
                           :rules="[v => !!v || 'Ingrese su contraseña']" class="q-mb-md">
                    <template #prepend><q-icon name="lock" size="18px"/></template>
                    <template #append>
                      <q-icon :name="showPassword ? 'visibility' : 'visibility_off'"
                              size="18px" class="cursor-pointer" @click="showPassword = !showPassword"/>
                    </template>
                  </q-input>

                  <q-btn color="primary" label="Iniciar sesión" class="full-width btnLogin"
                         no-caps unelevated size="16px" :loading="loading" type="submit"/>
                </q-form>
              </q-card-section>

              <q-card-section class="q-pt-none text-center">
                <q-separator spaced/>
                <div class="text-body2">
                  ¿Eres usuario nuevo?
                  <q-btn flat dense no-caps color="primary" class="text-weight-medium"
                         label="Regístrate" @click="irRegistro"/>
                </div>
                <div class="text-caption text-grey-6 q-mt-xs">
                  © {{ year }} San Juan de Dios. Todos los derechos reservados.
                </div>
              </q-card-section>
            </template>

            <!-- ── Cambiar contraseña obligatorio (clave 123456) ── -->
            <template v-else-if="vista === 'cambiar'">
              <q-card-section class="q-pt-none">
                <div class="row items-center q-mb-xs">
                  <q-icon name="lock_reset" color="warning" size="22px" class="q-mr-sm"/>
                  <span class="text-h6 text-bold">Cambia tu contraseña</span>
                </div>
                <q-banner rounded class="bg-orange-1 text-orange-9 q-mb-md" dense>
                  <template #avatar><q-icon name="warning" color="warning"/></template>
                  Por seguridad debes establecer una nueva contraseña antes de continuar.
                </q-banner>

                <q-form @submit.prevent="cambiarPassword">
                  <div class="q-mb-sm text-caption text-grey-7">Nueva contraseña</div>
                  <q-input v-model="newPassword" outlined dense
                           :type="showNewPassword ? 'text' : 'password'" placeholder="Nueva contraseña"
                           :rules="[v => !!v || 'Campo requerido', v => v.length >= 6 || 'Mínimo 6 caracteres']"
                           class="q-mb-md">
                    <template #prepend><q-icon name="lock" size="18px"/></template>
                    <template #append>
                      <q-icon :name="showNewPassword ? 'visibility' : 'visibility_off'"
                              size="18px" class="cursor-pointer" @click="showNewPassword = !showNewPassword"/>
                    </template>
                  </q-input>

                  <div class="q-mb-sm text-caption text-grey-7">Repetir nueva contraseña</div>
                  <q-input v-model="newPasswordConfirm" outlined dense
                           :type="showNewPasswordConfirm ? 'text' : 'password'" placeholder="Repetir nueva contraseña"
                           :rules="[v => !!v || 'Campo requerido', v => v === newPassword || 'Las contraseñas no coinciden']"
                           class="q-mb-md">
                    <template #prepend><q-icon name="lock_reset" size="18px"/></template>
                    <template #append>
                      <q-icon :name="showNewPasswordConfirm ? 'visibility' : 'visibility_off'"
                              size="18px" class="cursor-pointer" @click="showNewPasswordConfirm = !showNewPasswordConfirm"/>
                    </template>
                  </q-input>

                  <q-btn color="primary" label="Guardar y entrar" class="full-width btnLogin"
                         no-caps unelevated size="16px" icon="check_circle"
                         :loading="loadingChange" type="submit"/>
                </q-form>
              </q-card-section>
            </template>

            <!-- ── Registro ── -->
            <template v-else-if="vista === 'registro'">
              <q-card-section class="q-pt-none">
                <div class="row items-center q-mb-sm">
                  <q-icon name="person_add" color="primary" size="22px" class="q-mr-sm"/>
                  <span class="text-h6 text-bold">Crear cuenta</span>
                  <q-space/>
                  <q-btn flat dense round icon="arrow_back" @click="vista = 'login'">
                    <q-tooltip>Volver al login</q-tooltip>
                  </q-btn>
                </div>

                <q-form @submit.prevent="registrar">
                  <!-- Banner estado de registro -->
                  <q-banner v-if="registroEstado.habilitado === false" rounded
                            class="bg-red-1 text-red-10 q-mb-md" style="border:1px solid #ef9a9a">
                    <template #avatar><q-icon name="access_time" color="red-7" size="22px"/></template>
                    <div class="text-weight-bold">Registro no disponible</div>
                    <div class="text-body2" v-if="registroEstado.fecha_inicio && registroEstado.fecha_fin">
                      El horario de registro es del <b>{{ fmtDt(registroEstado.fecha_inicio) }}</b>
                      al <b>{{ fmtDt(registroEstado.fecha_fin) }}</b>
                    </div>
                    <div class="text-body2" v-else>
                      Por favor comuníquese con el administrador.
                    </div>
                  </q-banner>
                  <q-banner v-else-if="registroEstado.habilitado === true" rounded
                            class="bg-green-1 text-green-10 q-mb-md" style="border:1px solid #a5d6a7">
                    <template #avatar><q-icon name="check_circle" color="green-7" size="22px"/></template>
                    <div class="text-weight-bold">Registro habilitado</div>
                    <div class="text-body2">
                      Hasta el <b>{{ fmtDt(registroEstado.fecha_fin) }}</b>
                    </div>
                  </q-banner>

                  <div class="q-mb-sm text-caption text-grey-7">Nombre completo <span class="text-negative">*</span></div>
                  <q-input v-model="reg.name" outlined dense placeholder="Ej: Adimer Paul Chambi Ajata"
                           :rules="[v => !!v || 'Campo requerido']" class="q-mb-xs"
                           @update:model-value="actualizarUsername">
                    <template #prepend><q-icon name="badge" size="18px"/></template>
                  </q-input>

                  <!-- Preview username -->
                  <div v-if="reg.usernamePreview" class="q-mb-md q-mt-xs" style="overflow:hidden">
                    <q-chip dense :color="usernameModificado ? 'orange-2' : 'blue-1'"
                            :text-color="usernameModificado ? 'orange-9' : 'primary'"
                            icon="alternate_email" style="max-width:100%">
                      Tu usuario será: <strong class="q-ml-xs">{{ reg.usernamePreview }}</strong>
                    </q-chip>
                    <div v-if="usernameModificado" class="text-caption text-orange-9 q-mt-xs">
                      <q-icon name="info" size="12px"/> Ya existe un usuario con ese nombre, se asignará <b>{{ reg.usernamePreview }}</b>
                    </div>
                  </div>

                  <div class="row q-col-gutter-sm q-mb-xs">
                    <div class="col-12 col-sm-6">
                      <div class="q-mb-xs text-caption text-grey-7">Email <span class="text-grey-5">(opcional)</span></div>
                      <q-input v-model="reg.email" outlined dense placeholder="correo@ejemplo.com" type="email">
                        <template #prepend><q-icon name="email" size="18px"/></template>
                      </q-input>
                    </div>
                    <div class="col-12 col-sm-6">
                      <div class="q-mb-xs text-caption text-grey-7">Celular <span class="text-grey-5">(opcional)</span></div>
                      <q-input v-model="reg.celular" outlined dense placeholder="Celular">
                        <template #prepend><q-icon name="phone" size="18px"/></template>
                      </q-input>
                    </div>
                  </div>

                  <div class="q-mb-xs text-caption text-grey-7">
                    CI (Carnet) <span class="text-negative">*</span>
                    <span class="text-grey-5 q-ml-xs">— será tu contraseña inicial</span>
                  </div>
                  <q-input v-model="reg.ci" outlined dense placeholder="Número de carnet"
                           :rules="[v => !!v || 'Campo requerido']" class="q-mb-sm">
                    <template #prepend><q-icon name="credit_card" size="18px"/></template>
                  </q-input>

                  <div class="q-mb-xs text-caption text-grey-7">Unidad <span class="text-negative">*</span></div>
                  <q-select v-model="reg.unidad_id" outlined dense
                            :options="unidades" option-label="nombre" option-value="id"
                            emit-value map-options placeholder="Selecciona tu unidad" class="q-mb-md"
                            :rules="[v => !!v || 'Selecciona tu unidad']">
                    <template #prepend><q-icon name="business" size="18px"/></template>
                    <template #no-option>
                      <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                    </template>
                  </q-select>

                  <q-btn color="positive" label="Crear cuenta" class="full-width btnLogin"
                         no-caps unelevated size="16px" icon="person_add"
                         :loading="loadingReg" type="submit"
                         :disable="registroEstado.habilitado === false"/>
                </q-form>
              </q-card-section>

              <q-card-section class="q-pt-none text-center">
                <q-separator spaced/>
                <div class="text-caption text-grey-6">
                  © {{ year }} San Juan de Dios. Todos los derechos reservados.
                </div>
                <div class="text-caption text-grey-5 q-mt-xs">
                  ¿Dudas? Escríbenos al
                  <a href="https://wa.me/59169603027" target="_blank" class="text-positive text-weight-medium" style="text-decoration:none">
                    WhatsApp +591 69603027
                  </a>
                </div>
              </q-card-section>
            </template>

            <!-- ── Credenciales creadas ── -->
            <template v-else-if="vista === 'credenciales'">
              <q-card-section class="q-pt-none text-center">
                <q-icon name="check_circle" color="positive" size="52px"/>
                <div class="text-h6 text-weight-bold q-mt-sm">¡Cuenta creada!</div>
                <div class="text-body2 text-grey-7 q-mt-xs q-mb-md">
                  Guarda estos datos, los necesitarás para ingresar
                </div>

                <q-banner rounded class="bg-blue-1 text-primary q-mb-sm text-left">
                  <template #avatar><q-icon name="account_circle" color="primary" size="22px"/></template>
                  <div class="text-caption text-grey-7">Tu nombre de usuario</div>
                  <div class="text-h6 text-weight-bold">{{ credenciales.username }}</div>
                </q-banner>

                <q-banner rounded class="bg-green-1 text-positive text-left">
                  <template #avatar><q-icon name="lock" color="positive" size="22px"/></template>
                  <div class="text-caption text-grey-7">Tu contraseña (tu carnet)</div>
                  <div class="text-h6 text-weight-bold">{{ credenciales.password }}</div>
                </q-banner>

                <div class="text-caption text-grey-6 q-mt-sm">
                  Puedes cambiar tu contraseña desde "Mi perfil" una vez adentro
                </div>
              </q-card-section>

              <q-card-section class="q-pt-none">
                <q-btn color="primary" label="Ingresar al sistema" class="full-width btnLogin"
                       no-caps unelevated size="16px" icon="login"
                       :loading="loadingIngreso" @click="ingresarAlSistema"/>
              </q-card-section>
            </template>

          </q-card>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>

</template>

<script setup>
import { getCurrentInstance, ref, computed, onMounted } from 'vue'

const { proxy } = getCurrentInstance()

// ── Login ────────────────────────────────────────────────
const vista                  = ref('login')   // 'login' | 'cambiar' | 'registro' | 'credenciales'
const username               = ref('')
const password               = ref('')
const showPassword           = ref(false)
const loading                = ref(false)
const year                   = computed(() => new Date().getFullYear())

// ── Cambiar contraseña obligatorio ───────────────────────
const newPassword            = ref('')
const newPasswordConfirm     = ref('')
const showNewPassword        = ref(false)
const showNewPasswordConfirm = ref(false)
const loadingChange          = ref(false)
let   tempToken              = ''

// ── Registro ─────────────────────────────────────────────
const loadingReg             = ref(false)
const unidades               = ref([])
const usernameModificado     = ref(false)
let   usernameTimer          = null
const registroEstado         = ref({ habilitado: null, fecha_inicio: null, fecha_fin: null })
const credenciales           = ref({ username: '', password: '' })
const loadingIngreso         = ref(false)
const reg                    = ref({
  name: '', email: '', celular: '', ci: '', unidad_id: null, usernamePreview: '',
})

onMounted(() => {
  proxy.$axios.get('unidades-public').then(res => {
    unidades.value = res.data || []
  }).catch(() => {})
})

function actualizarUsername() {
  const palabras = (reg.value.name || '').trim().toLowerCase().split(/\s+/).filter(Boolean)
  if (!palabras.length) {
    reg.value.usernamePreview = ''
    usernameModificado.value = false
    clearTimeout(usernameTimer)
    return
  }
  const base = palabras[0]
  const segunda = palabras[1] ? palabras[1][0] : ''
  const local = base + segunda
  reg.value.usernamePreview = local
  usernameModificado.value = false

  clearTimeout(usernameTimer)
  usernameTimer = setTimeout(async () => {
    try {
      const res = await proxy.$axios.get('username-preview', { params: { name: reg.value.name } })
      const real = res.data.username
      reg.value.usernamePreview = real
      usernameModificado.value = real !== local
    } catch {}
  }, 500)
}

function fmtDt(val) {
  if (!val) return ''
  const d = new Date(val)
  if (isNaN(d)) return val
  return d.toLocaleString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

async function irRegistro() {
  reg.value = { name: '', email: '', celular: '', ci: '', unidad_id: null, usernamePreview: '' }
  registroEstado.value = { habilitado: null, fecha_inicio: null, fecha_fin: null }
  vista.value = 'registro'
  try {
    const res = await proxy.$axios.get('registro-estado')
    registroEstado.value = res.data
  } catch {}
}


// ── Acciones ─────────────────────────────────────────────
function login() {
  loading.value = true
  proxy.$axios.post('/login', { username: username.value, password: password.value })
    .then(res => {
      const { user, token, must_change_password } = res.data
      proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
      proxy.$store.user = user

      if (must_change_password) {
        tempToken = token
        newPassword.value = ''
        newPasswordConfirm.value = ''
        vista.value = 'cambiar'
      } else {
        proxy.$store.isLogged    = true
        proxy.$store.permissions = (user.permissions || []).map(p => p.name)
        localStorage.setItem('tokenSil', token)
        localStorage.setItem('user', JSON.stringify(user))
        proxy.$alert.success('Bienvenido ' + user.name)
        proxy.$router.push('/')
      }
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'Error de autenticación')
    })
    .finally(() => { loading.value = false })
}

function cambiarPassword() {
  loadingChange.value = true
  proxy.$axios.put('cambiar-password', {
    password_actual:             '123456',
    password_nuevo:              newPassword.value,
    password_nuevo_confirmation: newPasswordConfirm.value,
  }).then(() => {
    const user = proxy.$store.user
    proxy.$store.isLogged    = true
    proxy.$store.permissions = (user.permissions || []).map(p => p.name)
    localStorage.setItem('tokenSil', tempToken)
    localStorage.setItem('user', JSON.stringify(user))
    proxy.$alert.success('Contraseña actualizada. ¡Bienvenido!')
    proxy.$router.push('/')
  }).catch(err => {
    proxy.$alert.error(err?.response?.data?.message || 'Error al cambiar contraseña')
  }).finally(() => { loadingChange.value = false })
}

function registrar() {
  proxy.$alert.dialog('Se va a crear tu cuenta con los datos ingresados. ¿Continuar?').onOk(async () => {
    loadingReg.value = true
    try {
      const regRes = await proxy.$axios.post('register', {
        name:      reg.value.name,
        email:     reg.value.email || null,
        celular:   reg.value.celular || null,
        ci:        reg.value.ci,
        unidad_id: reg.value.unidad_id || null,
      })
      credenciales.value = { username: regRes.data.username, password: regRes.data.password }
      vista.value = 'credenciales'
    } catch (err) {
      proxy.$alert.error(err?.response?.data?.message || 'Error al crear la cuenta')
    } finally {
      loadingReg.value = false
    }
  })
}

async function ingresarAlSistema() {
  loadingIngreso.value = true
  try {
    const loginRes = await proxy.$axios.post('/login', {
      username: credenciales.value.username,
      password: credenciales.value.password,
    })
    const { user, token } = loginRes.data
    proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
    proxy.$store.user        = user
    proxy.$store.isLogged    = true
    proxy.$store.permissions = (user.permissions || []).map(p => p.name)
    localStorage.setItem('tokenSil', token)
    localStorage.setItem('user', JSON.stringify(user))
    proxy.$router.push('/')
  } catch (err) {
    proxy.$alert.error(err?.response?.data?.message || 'Error al ingresar')
  } finally {
    loadingIngreso.value = false
  }
}
</script>

<style scoped>
.login-layout {
  background-image: url('/bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  min-height: 100vh;
}
.full-height { min-height: 100vh; position: relative; }
.login-bg-overlay {
  position: absolute; inset: 0;
  backdrop-filter: blur(3px);
  background: radial-gradient(1200px 800px at 70% 40%, rgba(0,0,0,0.12), rgba(0,0,0,0.25));
}
.login-wrap {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
  margin: 0 auto;
  padding: 24px 12px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100vh;
}
.login-card {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}
</style>
