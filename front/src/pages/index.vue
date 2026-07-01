<template>
  <q-layout view="lHh Lpr lFf">
    <!-- HEADER -->
    <q-header class="bg-white text-black" bordered>
      <q-toolbar>
        <q-btn
          flat
          color="primary"
          :icon="leftDrawerOpen ? 'keyboard_double_arrow_left' : 'keyboard_double_arrow_right'"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          unelevated
          dense
        />
        <div class="row items-center q-gutter-sm">
          <div class="text-subtitle1 text-weight-medium" style="line-height: 0.9">
            Clínica URME
          </div>
        </div>

        <q-space />

        <q-btn-dropdown flat unelevated no-caps dropdown-icon="expand_more">
          <template v-slot:label>
            <div class="row items-center no-wrap q-gutter-sm">
              <q-avatar rounded size="32px" style="border:2px solid #e2e8f0">
                <img :src="$store.user.avatar ? imgUrlBase + '/images/' + $store.user.avatar : imgUrlBase + '/images/default.png'"
                     style="object-fit:cover;width:100%;height:100%"
                     @error="$event.target.src = imgUrlBase + '/images/default.png'" />
              </q-avatar>
              <div class="text-left" style="line-height: 1">
                <div class="ellipsis" style="max-width: 130px;">
                  {{ $store.user.username }}
                </div>
              </div>
            </div>
          </template>

          <q-separator />

          <q-item clickable v-ripple @click="logout" v-close-popup>
            <q-item-section avatar>
              <q-icon name="logout" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Salir</q-item-label>
            </q-item-section>
          </q-item>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <!-- DRAWER -->
    <q-drawer
      v-model="leftDrawerOpen"
      bordered
      show-if-above
      :width="236"
      :breakpoint="700"
      class="app-drawer text-white"
    >
      <q-scroll-area class="fit">
        <div class="drawer-shell">
          <div class="drawer-brand">
            <div class="drawer-brand__text">
              <div class="drawer-brand__caption">Clínica URME<br>Sistema de Laboratorio</div>
            </div>
          </div>

          <div class="drawer-eyebrow">Módulos</div>

          <q-list dense class="drawer-menu">
            <q-expansion-item
              v-for="section in visibleMenuSections"
              :key="section.title"
              dense
              dense-toggle
              expand-separator
              :default-opened="section.defaultOpened || sectionIsActive(section)"
              :header-class="sectionHeaderClass(section)"
            >
              <template v-slot:header>
                <q-item-section avatar class="drawer-section__icon">
                  <q-icon :name="section.icon" size="18px" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="drawer-section__label">{{ section.title }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge class="drawer-section__count" rounded>
                    {{ visibleSectionLinks(section).length }}
                  </q-badge>
                </q-item-section>
              </template>

              <q-list dense class="drawer-submenu">
                <q-item
                  v-for="link in visibleSectionLinks(section)"
                  :key="link.title"
                  dense
                  clickable
                  :to="link.link"
                  class="drawer-menu-link"
                  :active="linkIsActive(link)"
                  active-class="drawer-menu-link--active"
                >
                  <q-item-section avatar class="drawer-menu-link__avatar">
                    <q-icon :name="link.icon" size="17px" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="drawer-menu-link__label" lines="1">{{ link.title }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-expansion-item>
          </q-list>

          <div class="drawer-footer">
            <div>URME v{{ $version }}</div>
            <div>© {{ new Date().getFullYear() }} Clínica URME</div>
          </div>

          <q-item clickable class="drawer-logout" @click="logout">
            <q-item-section avatar class="drawer-menu-link__avatar">
              <q-icon name="logout" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Salir</q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </q-scroll-area>
    </q-drawer>

    <!-- PAGE -->
    <q-page-container class="bg-grey-2">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()

const leftDrawerOpen = ref(false)

const imgUrlBase = (import.meta.env.VITE_API_BACK || '').replace(/\/api\/?$/, '')

const PALETTE = ['#1565C0','#2E7D32','#E65100','#B71C1C','#6A1B9A','#00838F','#4E342E','#37474F','#0277BD','#558B2F']

function avatarColor (name) {
  let h = 0
  for (const c of (name || '').toUpperCase()) h = c.charCodeAt(0) + ((h << 5) - h)
  return PALETTE[Math.abs(h) % PALETTE.length]
}

function initials (name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

const menuSections = [
  {
    title: 'Administración',
    icon: 'admin_panel_settings',
    defaultOpened: true,
    links: [
      { title: 'Inicio',    icon: 'dashboard', link: '/',         can: null },
      { title: 'Usuarios',  icon: 'people',    link: '/usuarios', can: 'Ver Usuarios' },
    ],
  },
  {
    title: 'Farmacia',
    icon: 'medication',
    links: [
      { title: 'Productos', icon: 'inventory_2', link: '/farmacia', can: 'Ver Productos' },
    ],
  },
  {
    title: 'Seguros',
    icon: 'verified_user',
    links: [
      { title: 'Seguros', icon: 'verified_user', link: '/seguros', can: 'Ver Seguros' },
    ],
  },
]

const userPermissions = computed(() => proxy.$store.permissions || [])
// console.log(userPermissions)

const visibleMenuSections = computed(() =>
  menuSections.filter(section => visibleSectionLinks(section).length > 0)
)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function hasPermission (perm) {
  if (perm === null) return true
  if (Array.isArray(perm)) return perm.some(p => userPermissions.value.includes(p))
  return userPermissions.value.includes(perm)
}

function visibleSectionLinks (section) {
  return section.links.filter(link => hasPermission(link.can))
}

function linkIsActive (link) {
  return proxy.$route.path === link.link
}

function sectionIsActive (section) {
  return section.links.some(link => linkIsActive(link))
}

function sectionHeaderClass (section) {
  return sectionIsActive(section)
    ? 'drawer-section-header drawer-section-header--active'
    : 'drawer-section-header'
}

function logout () {
  proxy.$alert.dialog('¿Desea salir del sistema?').onOk(() => {
    proxy.$axios.post('/logout').finally(() => {
      proxy.$store.isLogged = false
      proxy.$store.user = {}
      proxy.$store.permissions = []
      localStorage.removeItem('tokenUrme')
      localStorage.removeItem('permissionsUrme')
      proxy.$router.push('/login')
    })
  })
}
</script>

<style>
.app-drawer {
  background: linear-gradient(180deg, #0b5ea8 0%, #0a477f 48%, #08355f 100%);
  color: #ffffff;
}

.app-drawer,
.app-drawer .q-drawer__content,
.app-drawer .q-scrollarea,
.app-drawer .q-scrollarea__container,
.app-drawer .q-scrollarea__content {
  background: linear-gradient(180deg, #0b5ea8 0%, #0a477f 48%, #08355f 100%) !important;
}

.drawer-shell {
  min-height: 100%;
  padding: 8px 7px 10px;
}

.drawer-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px;
  margin-bottom: 6px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.09);
}

.drawer-brand__text {
  min-width: 0;
  line-height: 1.05;
}

.drawer-brand__caption {
  margin-top: 2px;
  color: rgba(255, 255, 255, 0.72);
  font-size: 10px;
  line-height: 1.15;
}

.drawer-eyebrow {
  padding: 3px 8px 5px;
  color: rgba(255, 255, 255, 0.66);
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.drawer-menu {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.drawer-section-header {
  min-height: 30px;
  padding: 0 7px;
  margin: 1px 2px;
  border-radius: 9px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.08);
}

.drawer-section-header--active {
  background: rgba(87, 185, 255, 0.24);
  box-shadow: inset 3px 0 0 #8fd3ff;
}

.drawer-section__icon {
  min-width: 26px;
}

.drawer-section__label {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0;
}

.drawer-section__count {
  min-width: 20px;
  justify-content: center;
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 10px;
}

.drawer-submenu {
  padding: 1px 0 3px;
}

.drawer-menu-link {
  min-height: 28px;
  margin: 1px 5px 1px 13px;
  padding: 0 7px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.86);
}

.drawer-menu-link__avatar {
  min-width: 24px;
}

.drawer-menu-link__label {
  font-size: 11px;
  font-weight: 650;
  line-height: 1.1;
  letter-spacing: 0;
}

.drawer-menu-link--active {
  background: #0b76c5;
  color: #ffffff !important;
  box-shadow: inset 3px 0 0 #ffd166;
}

.drawer-footer {
  padding: 8px 9px 6px;
  margin-top: 8px;
  color: rgba(255, 255, 255, 0.58);
  font-size: 10px;
  line-height: 1.35;
}

.drawer-logout {
  min-height: 30px;
  margin: 2px 5px 0;
  border-radius: 9px;
  color: #ffe6e3;
  background: rgba(229, 57, 53, 0.16);
}
</style>
