<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="users"
      :columns="columns"
      row-key="id"
      dense
      flat
      bordered
      :rows-per-page-options="[0]"
      title="Usuarios"
      :filter="filter"
      :loading="loading"
    >
      <template v-slot:top-right>
        <q-btn color="positive" label="Nuevo" icon="add_circle_outline" no-caps @click="userNew" class="q-mr-sm" />
        <q-btn color="primary" label="Actualizar" icon="refresh" no-caps @click="usersGet" class="q-mr-sm" />
        <q-input v-model="filter" label="Buscar" dense outlined style="width:180px">
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </template>

      <template v-slot:body-cell-avatar="props">
        <q-td :props="props" auto-width>
          <q-avatar size="34px" style="cursor:pointer;border:2px solid #e2e8f0"
                    @click="openDialog(props.row)">
            <img :src="imgUrl(props.row.avatar || 'default.png')"
                 style="object-fit:cover;width:100%;height:100%"
                 @error="$event.target.src = imgUrl('default.png')" />
          </q-avatar>
        </q-td>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable v-close-popup @click="openDialog(props.row)">
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="userResetPassword(props.row)">
                <q-item-section avatar><q-icon name="lock_reset" /></q-item-section>
                <q-item-section><q-item-label>Restablecer contraseña</q-item-label></q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="userDelete(props.row.id)">
                <q-item-section avatar><q-icon name="delete" color="negative" /></q-item-section>
                <q-item-section><q-item-label class="text-negative">Eliminar</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <template v-slot:body-cell-perms="props">
        <q-td :props="props">
          <q-badge v-if="(props.row.permissions||[]).length" color="primary" outline>
            {{ (props.row.permissions||[]).length }} permisos
          </q-badge>
          <q-badge v-else color="grey-4" text-color="grey-7" outline>Sin permisos</q-badge>
        </q-td>
      </template>
    </q-table>

    <!-- ── Dialog único: datos + avatar + permisos ── -->
    <q-dialog v-model="dialog" persistent :maximized="$q.screen.lt.sm">
      <q-card class="user-card">
        <!-- Header -->
        <q-card-section class="row items-center q-pb-sm bg-primary text-white">
          <q-icon name="person" size="20px" class="q-mr-sm" />
          <span class="text-subtitle1 text-weight-bold">{{ actionUser }} usuario</span>
          <q-space />
          <q-btn icon="close" flat round dense color="white" @click="dialog = false" />
        </q-card-section>

        <q-card-section class="user-card__body">
          <q-form ref="formRef" @submit.prevent="saveUser">
            <!-- Avatar + campos principales -->
            <div class="row q-col-gutter-md items-start q-mb-sm">
              <!-- Avatar upload -->
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

              <!-- Nombre y usuario -->
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

            <!-- Campos secundarios -->
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

            <!-- Permisos -->
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
            <div v-else class="perm-grid">
              <label
                v-for="perm in filteredPerms"
                :key="perm.id"
                class="perm-item"
                :class="{ 'perm-item--on': perm.checked }"
              >
                <q-checkbox v-model="perm.checked" dense size="sm" color="primary" />
                <span class="perm-item__label">{{ perm.name }}</span>
              </label>
            </div>

            <!-- Footer -->
            <div class="row justify-end q-gutter-sm q-mt-md">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dialog = false" />
              <q-btn color="primary" :label="user.id ? 'Guardar cambios' : 'Crear usuario'"
                     type="submit" no-caps :loading="saving" icon-right="save" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, getCurrentInstance, onMounted } from 'vue'

const { proxy } = getCurrentInstance()

const users      = ref([])
const user       = ref({})
const dialog     = ref(false)
const loading    = ref(false)
const saving     = ref(false)
const actionUser = ref('')
const filter     = ref('')

const permissions  = ref([])
const loadingPerms = ref(false)
const permFilter   = ref('')
const avatarFile   = ref(null)
const avatarPreview = ref(null)

const PALETTE = ['#1565C0','#2E7D32','#E65100','#B71C1C','#6A1B9A','#00838F','#4E342E','#37474F','#0277BD','#558B2F']

function avatarColor (name) {
  let h = 0
  for (const c of (name || '').toUpperCase()) h = c.charCodeAt(0) + ((h << 5) - h)
  return PALETTE[Math.abs(h) % PALETTE.length]
}

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function avatarBg (row) {
  return row?.avatar || avatarPreview.value ? {} : { background: avatarColor(row?.name), color: '#fff' }
}

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
  return permissions.value.filter(p =>
    p.name.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').includes(q)
  )
})

const checkedCount = computed(() => permissions.value.filter(p => p.checked).length)

onMounted(() => usersGet())

function usersGet () {
  loading.value = true
  proxy.$axios.get('users').then(res => {
    users.value = res.data
  }).catch(err => {
    proxy.$alert.error(err.response?.data?.message || 'Error al cargar')
  }).finally(() => { loading.value = false })
}

async function openDialog (row = null) {
  avatarFile.value = null
  avatarPreview.value = null
  permFilter.value = ''

  if (row) {
    user.value = { ...row }
    actionUser.value = 'Editar'
  } else {
    user.value = { name: '', username: '', email: '', celular: '', ci: '', password: '' }
    actionUser.value = 'Nuevo'
  }

  dialog.value = true

  // Load permissions
  loadingPerms.value = true
  try {
    const [all, userPermIds] = await Promise.all([
      proxy.$axios.get('permissions').then(r => r.data),
      row ? proxy.$axios.get('users/' + row.id + '/permissions').then(r => r.data) : Promise.resolve([]),
    ])
    permissions.value = all.map(p => ({ ...p, checked: userPermIds.includes(p.id) }))
  } catch (err) {
    proxy.$alert.error('Error cargando permisos')
  } finally {
    loadingPerms.value = false
  }
}

function userNew () { openDialog(null) }

function onAvatarChange (e) {
  const file = e.target.files[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveUser () {
  saving.value = true
  try {
    let savedId = user.value.id

    // 1. Create or update user data
    if (savedId) {
      await proxy.$axios.put('users/' + savedId, user.value)
    } else {
      const res = await proxy.$axios.post('users', user.value)
      savedId = res.data.id
    }

    // 2. Upload avatar if file selected
    if (avatarFile.value) {
      const form = new FormData()
      form.append('avatar', avatarFile.value)
      const res = await proxy.$axios.post('users/' + savedId + '/avatar', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      // Update local avatar
      if (!user.value.id) user.value.id = savedId
      user.value.avatar = res.data.avatar
    }

    // 3. Sync permissions
    const ids = permissions.value.filter(p => p.checked).map(p => p.id)
    await proxy.$axios.put('users/' + savedId + '/permissions', { permissions: ids })

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

/* Avatar upload */
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

/* Permissions grid */
.perm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 3px;
  max-height: 220px;
  overflow-y: auto;
  background: #f7fafc;
  border-radius: 8px;
  padding: 6px;
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

.perm-item:hover {
  background: #eef4ff;
}

.perm-item--on {
  border-color: #90caf9;
  background: #e8f4fd;
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

  .user-card__body {
    max-height: calc(100vh - 56px);
  }

  .perm-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
