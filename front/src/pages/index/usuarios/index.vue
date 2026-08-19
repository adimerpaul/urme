<template>
  <q-page class="q-pa-md">

    <!-- Sin acceso -->
    <div v-if="proxy.$store.isLogged && !canVer"
         class="column items-center justify-center q-gutter-sm"
         style="min-height:320px">
      <q-icon name="lock" size="72px" color="grey-4" />
      <div class="text-h6 text-grey-5">Sin acceso</div>
      <div class="text-body2 text-grey-6">No tiene permiso para ver usuarios</div>
    </div>

    <template v-else>
      <div class="row items-end q-col-gutter-sm q-mb-md">
        <div class="col">
          <div class="text-h5 text-weight-bold">Usuarios</div>
          <div class="text-body2 text-grey-6">Cuentas con acceso al sistema</div>
        </div>
        <div class="col-auto row items-center q-gutter-sm">
          <q-input v-model="filter" placeholder="Buscar…" dense outlined rounded
                   clearable bg-color="white" style="width:200px">
            <template v-slot:prepend><q-icon name="search" /></template>
          </q-input>
          <q-btn outline rounded no-caps color="grey-7" icon="refresh" @click="usersGet">
            <q-tooltip>Actualizar</q-tooltip>
          </q-btn>
          <q-btn v-if="canCrear" rounded unelevated no-caps color="primary"
                 label="Nuevo usuario" icon="add" @click="userNew" />
        </div>
      </div>

      <q-table
        :rows="users"
        :columns="columns"
        row-key="id"
        dense
        flat
        bordered
        class="rounded-borders"
        table-header-class="bg-grey-1 text-grey-7 text-uppercase"
        :rows-per-page-options="[0]"
        :filter="filter"
        :loading="loading"
      >

        <template v-slot:body-cell-avatar="props">
          <q-td :props="props" auto-width>
            <q-avatar
              size="34px"
              :style="canEditar ? 'cursor:pointer;border:2px solid #e2e8f0' : 'border:2px solid #e2e8f0'"
              @click="canEditar && openDialog(props.row)"
            >
              <img :src="imgUrl(props.row.avatar || 'default.png')"
                   style="object-fit:cover;width:100%;height:100%"
                   @error="$event.target.src = imgUrl('default.png')" />
            </q-avatar>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown
              v-if="canEditar || canEliminar"
              label="Opciones" no-caps size="10px" dense rounded unelevated color="primary"
            >
              <q-list>
                <q-item v-if="canEditar" clickable v-close-popup @click="openDialog(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEditar" clickable v-close-popup @click="userResetPassword(props.row)">
                  <q-item-section avatar><q-icon name="lock_reset" /></q-item-section>
                  <q-item-section><q-item-label>Restablecer contraseña</q-item-label></q-item-section>
                </q-item>
                <q-item v-if="canEliminar" clickable v-close-popup @click="userDelete(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                  <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>

        <template v-slot:body-cell-perms="props">
          <q-td :props="props">
            <q-badge v-if="(props.row.permissions||[]).length" rounded color="teal-1" text-color="primary" class="text-weight-bold">
              {{ (props.row.permissions||[]).length }} permisos
            </q-badge>
            <q-badge v-else rounded color="grey-3" text-color="grey-7">Sin permisos</q-badge>
          </q-td>
        </template>
      </q-table>

      <!-- ── Dialog único: datos + avatar + permisos ── -->
      <q-dialog v-model="dialog" persistent :maximized="$q.screen.lt.sm">
        <q-card class="user-card">
          <q-card-section class="row items-center q-pb-sm bg-primary text-white">
            <q-icon name="person" size="20px" class="q-mr-sm" />
            <span class="text-subtitle1 text-weight-bold">{{ actionUser }} usuario</span>
            <q-space />
            <q-btn icon="close" flat round dense color="white" @click="dialog = false" />
          </q-card-section>

          <q-card-section class="user-card__body">
            <q-form ref="formRef" @submit.prevent="saveUser">
              <div class="row q-col-gutter-md items-start q-mb-sm">
                <div class="col-auto">
                  <div class="avatar-upload" @click="$refs.fileInput.click()">
                    <q-avatar size="80px" style="border:2px solid #e2e8f0">
                      <img :src="avatarPreview || (user.avatar ? imgUrl(user.avatar) : imgUrl('default.png'))"
                           style="object-fit:cover;width:100%;height:100%"
                           @error="$event.target.src = imgUrl('default.png')" />
                    </q-avatar>
                    <div class="avatar-upload__overlay">
                      <q-icon name="photo_camera" size="22px" color="white" />
                    </div>
                  </div>
                  <input ref="fileInput" type="file" accept="image/*" style="display:none"
                         @change="onAvatarChange" />
                  <div class="text-caption text-grey-6 text-center q-mt-xs" style="width:80px">Foto</div>
                </div>

                <div class="col">
                  <div class="row q-col-gutter-sm">
                    <div class="col-12">
                      <q-input v-model="user.name" label="Nombre completo" dense outlined
                               :rules="[v => !!v || 'Requerido']" />
                    </div>
                    <div class="col-12">
                      <q-input v-model="user.username" label="Nombre de usuario" dense outlined
                               :rules="[v => !!v || 'Requerido']" :disable="!!user.id" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.email" label="Email" dense outlined />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.celular" label="Celular" dense outlined />
                </div>
                <div class="col-12 col-sm-6">
                  <q-input v-model="user.ci" label="Cédula de identidad" dense outlined />
                </div>
                <div v-if="!user.id" class="col-12 col-sm-6">
                  <q-input v-model="user.password" label="Contraseña" dense outlined
                           :rules="[v => !!v || 'Requerido']" />
                </div>
              </div>

              <!-- Permisos — solo con Gestionar Permisos -->
              <template v-if="canPermisos">
                <q-separator class="q-mb-sm" />
                <div class="row items-center q-mb-xs">
                  <q-icon name="admin_panel_settings" size="18px" color="primary" class="q-mr-xs" />
                  <span class="text-subtitle2 text-weight-bold">Permisos</span>
                  <q-space />
                  <q-badge color="primary" outline>{{ checkedCount }} activos</q-badge>
                  <q-btn dense flat size="sm" icon="done_all" class="q-ml-xs" @click="setAll(true)">
                    <q-tooltip>Activar todos</q-tooltip>
                  </q-btn>
                  <q-btn dense flat size="sm" icon="remove_done" @click="setAll(false)">
                    <q-tooltip>Desactivar todos</q-tooltip>
                  </q-btn>
                </div>
                <q-input v-model="permFilter" dense outlined clearable placeholder="Filtrar…" class="q-mb-xs">
                  <template v-slot:prepend><q-icon name="search" /></template>
                </q-input>
                <div v-if="loadingPerms" class="text-center q-pa-sm">
                  <q-spinner-dots color="primary" size="28px" />
                </div>
                <div v-else class="perm-groups">
                  <div v-for="group in groupedPermissions" :key="group.name" class="perm-group">
                    <div class="perm-group__title">
                      <q-icon :name="group.icon" size="16px" />
                      {{ group.name }}
                      <q-badge rounded color="grey-3" text-color="grey-8" class="q-ml-xs">
                        {{ group.permissions.filter(p => p.checked).length }}/{{ group.permissions.length }}
                      </q-badge>
                    </div>
                    <div class="perm-grid">
                      <label v-for="perm in group.permissions" :key="perm.id" class="perm-item"
                             :class="{ 'perm-item--on': perm.checked }">
                        <q-checkbox v-model="perm.checked" dense size="sm" color="primary" />
                        <span class="perm-item__label">{{ perm.name }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </template>

              <div class="row justify-end q-gutter-sm q-mt-md">
                <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialog = false" />
                <q-btn color="primary" :label="user.id ? 'Guardar cambios' : 'Crear usuario'"
                       type="submit" no-caps :loading="saving" icon-right="save" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()

// ── Permisos reactivos ───────────────────────────────────────────
const canVer      = computed(() => proxy.$store.hasPermission('Ver Usuarios'))
const canCrear    = computed(() => proxy.$store.hasPermission('Crear Usuarios'))
const canEditar   = computed(() => proxy.$store.hasPermission('Editar Usuarios'))
const canEliminar = computed(() => proxy.$store.hasPermission('Eliminar Usuarios'))
const canPermisos = computed(() => proxy.$store.hasPermission('Gestionar Permisos'))

// ── Estado ───────────────────────────────────────────────────────
const users       = ref([])
const user        = ref({})
const dialog      = ref(false)
const loading     = ref(false)
const saving      = ref(false)
const actionUser  = ref('')
const filter      = ref('')

const permissions   = ref([])
const loadingPerms  = ref(false)
const permFilter    = ref('')
const avatarFile    = ref(null)
const avatarPreview = ref(null)

const IMG_BASE = (import.meta.env.VITE_API_BACK || '').replace(/\/api\/?$/, '')

function imgUrl (filename) {
  return IMG_BASE + '/images/' + filename
}

const columns = [
  { name: 'avatar',   label: '',         align: 'center' },
  { name: 'actions',  label: 'Acciones', align: 'center' },
  { name: 'name',     label: 'Nombre',   align: 'left', field: 'name',     sortable: true },
  { name: 'username', label: 'Usuario',  align: 'left', field: 'username', sortable: true },
  { name: 'email',    label: 'Email',    align: 'left', field: 'email' },
  { name: 'celular',  label: 'Celular',  align: 'left', field: 'celular' },
  { name: 'ci',       label: 'CI',       align: 'left', field: 'ci' },
  { name: 'perms',    label: 'Permisos', align: 'left' },
]

const filteredPerms = computed(() => {
  const q = (permFilter.value || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
  if (!q) return permissions.value
  const limpiar = (t) => (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
  return permissions.value.filter(p =>
    limpiar(p.name).includes(q) || limpiar(p.modulo).includes(q)
  )
})

// Cada permiso trae su módulo desde el backend (columna `modulo`).
// El orden de esta lista es el orden en que se muestran los grupos;
// cualquier módulo nuevo aparece igual al final, nunca se oculta un permiso.
const MODULO_ICONOS = {
  Dashboard: 'dashboard',
  Usuarios: 'manage_accounts',
  Pacientes: 'badge',
  Internaciones: 'local_hotel',
  Doctores: 'medical_information',
  Farmacia: 'medication',
  'Productos Farmacia': 'inventory_2',
  Vencimientos: 'event_busy',
  Compras: 'shopping_cart',
  Ventas: 'point_of_sale',
  Caja: 'lock_clock',
  Seguros: 'verified_user',
  Laboratorio: 'biotech',
  Reportes: 'summarize',
  Configuracion: 'settings',
  Otros: 'apps',
}

const groupedPermissions = computed(() => {
  const orden = Object.keys(MODULO_ICONOS)
  const groups = []

  filteredPerms.value.forEach(permission => {
    const modulo = permission.modulo || 'Otros'
    let group = groups.find(g => g.name === modulo)
    if (!group) {
      group = { name: modulo, icon: MODULO_ICONOS[modulo] || 'apps', permissions: [] }
      groups.push(group)
    }
    group.permissions.push(permission)
  })

  return groups.sort((a, b) => {
    const ia = orden.indexOf(a.name)
    const ib = orden.indexOf(b.name)
    return (ia === -1 ? orden.length : ia) - (ib === -1 ? orden.length : ib)
  })
})

const checkedCount = computed(() => permissions.value.filter(p => p.checked).length)

// ── Carga inicial — espera a que se resuelvan los permisos ───────
let fetched = false
watch(() => proxy.$store.isLogged, (val) => {
  if (val && !fetched) { fetched = true; usersGet() }
}, { immediate: true })

// ── CRUD ─────────────────────────────────────────────────────────
function usersGet () {
  loading.value = true
  proxy.$axios.get('users').then(res => {
    users.value = res.data
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

async function openDialog (row = null) {
  avatarFile.value    = null
  avatarPreview.value = null
  permFilter.value    = ''
  permissions.value   = []

  if (row) {
    user.value    = { ...row }
    actionUser.value = 'Editar'
  } else {
    user.value    = { name: '', username: '', email: '', celular: '', ci: '', password: '' }
    actionUser.value = 'Nuevo'
  }

  dialog.value = true

  if (canPermisos.value) {
    loadingPerms.value = true
    try {
      const [all, userPermIds] = await Promise.all([
        proxy.$axios.get('permissions').then(r => r.data),
        row ? proxy.$axios.get('users/' + row.id + '/permissions').then(r => r.data) : Promise.resolve([]),
      ])
      permissions.value = all.map(p => ({ ...p, checked: userPermIds.includes(p.id) }))
    } catch {
      proxy.$alert.error('Error cargando permisos')
    } finally {
      loadingPerms.value = false
    }
  }
}

function userNew () { openDialog(null) }

function onAvatarChange (e) {
  const file = e.target.files[0]
  if (!file) return
  avatarFile.value    = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveUser () {
  saving.value = true
  try {
    let savedId = user.value.id

    if (savedId) {
      await proxy.$axios.put('users/' + savedId, user.value)
    } else {
      const res = await proxy.$axios.post('users', user.value)
      savedId = res.data.id
    }

    if (avatarFile.value) {
      const form = new FormData()
      form.append('avatar', avatarFile.value)
      const res = await proxy.$axios.post('users/' + savedId + '/avatar', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      user.value.avatar = res.data.avatar
    }

    if (canPermisos.value) {
      const ids = permissions.value.filter(p => p.checked).map(p => p.id)
      const permissionResponse = await proxy.$axios.put('users/' + savedId + '/permissions', { permissions: ids })

      if (Number(savedId) === Number(proxy.$store.user?.id)) {
        const currentPermissions = permissionResponse.data || []
        proxy.$store.permissions = currentPermissions
        localStorage.setItem('permissionsUrme', JSON.stringify(currentPermissions))
      }
    }

    dialog.value = false
    proxy.$alert.success(user.value.id ? 'Usuario actualizado' : 'Usuario creado')
    usersGet()
  } catch (err) {
    proxy.$alert.error(err.response?.data?.message || 'Error al guardar')
  } finally {
    saving.value = false
  }
}

function userDelete (id) {
  proxy.$alert.dialog('¿Desea eliminar el usuario?').onOk(() => {
    loading.value = true
    proxy.$axios.delete('users/' + id).then(() => {
      proxy.$alert.success('Usuario eliminado')
      usersGet()
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al eliminar')
    }).finally(() => { loading.value = false })
  })
}

function userResetPassword (row) {
  proxy.$alert.dialog(`¿Restablecer contraseña de "${row.username}" a 123456?`).onOk(() => {
    proxy.$axios.put('users/' + row.id + '/reset-password').then(() => {
      proxy.$alert.success(`Contraseña de ${row.username} restablecida a 123456`)
    }).catch(err => {
      proxy.$alert.error(err.response?.data?.message || 'Error al restablecer')
    })
  })
}

function setAll (val) {
  filteredPerms.value.forEach(p => { p.checked = val })
}
</script>

<style scoped>
.user-card {
  width: min(96vw, 720px);
  max-width: 720px;
  display: flex;
  flex-direction: column;
}

.user-card__body {
  overflow-y: auto;
  max-height: calc(90vh - 56px);
  padding: 14px 16px;
}

.avatar-upload {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  cursor: pointer;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}

.avatar-upload__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.38);
  opacity: 0;
  transition: opacity 0.18s;
  border-radius: 50%;
}

.avatar-upload:hover .avatar-upload__overlay {
  opacity: 1;
}

.perm-groups {
  max-height: 300px;
  overflow-y: auto;
  background: #f7fafc;
  border-radius: 8px;
  padding: 7px;
}

.perm-group + .perm-group {
  margin-top: 9px;
}

.perm-group__title {
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0 2px 5px;
  color: #455a64;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 3px;
}

.perm-item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 7px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  user-select: none;
  transition: background 0.12s;
}

.perm-item:hover { background: #eef7f2; }

.perm-item--on {
  border-color: #8fd4bb;
  background: #e4f2ec;
}

.perm-item__label {
  font-size: 11.5px;
  line-height: 1.2;
  color: #2d3748;
}

@media (max-width: 599px) {
  .user-card {
    width: 100%;
    max-width: none;
    height: 100%;
    border-radius: 0;
  }

  .user-card__body { max-height: calc(100vh - 56px); }

  .perm-grid { grid-template-columns: 1fr 1fr; }
}
</style>
