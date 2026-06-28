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
          <!--          <q-badge color="green-8" text-color="white" class="text-bold">SIL</q-badge>-->
          <div class="text-subtitle1 text-weight-medium" style="line-height: 0.9">
            Dashboard de Gestión <br>
            <q-badge color="warning" text-color="black" v-if="roleText" class="text-bold">
              <span style="font-size: 10px; text-transform: lowercase;">
              {{ $store.user.area ? $store.user.area.name : roleText }}
              </span>
            </q-badge>
            <q-badge v-if="$store.user.unidad" color="teal-8" text-color="white" class="text-bold ">
              <span style="font-size: 10px;text-transform: lowercase;width: 120px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                <q-icon name="apartment" size="12px" class="q-mr-xs" />{{ $store.user.unidad.nombre }}
              </span>
            </q-badge>
          </div>
        </div>

        <q-space />

        <div class="row items-center q-gutter-sm">

          <q-btn-dropdown flat unelevated no-caps dropdown-icon="expand_more">
            <template v-slot:label>
              <div class="row items-center no-wrap q-gutter-sm">
                <q-avatar rounded>
                  <q-img :src="`${$url}/../images/${$store.user.avatar}`" width="40px" height="40px" v-if="$store.user.avatar"/>
                  <q-icon name="person" v-else />
                </q-avatar>
                <div class="text-left" style="line-height: 1">
                  <div class="ellipsis" style="max-width: 130px;">
                    {{ $store.user.username }}
                  </div>
                  <q-chip dense size="10px" :color="$filters.color($store.user.role)" text-color="white">
                    {{ $store.user.role }}
                  </q-chip>
                </div>
              </div>
            </template>

            <q-item clickable v-ripple v-close-popup @click="$router.push('/perfil')">
              <q-item-section avatar>
                <q-icon name="manage_accounts" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Mi perfil</q-item-label>
              </q-item-section>
            </q-item>

            <q-item clickable v-ripple v-close-popup @click="$router.push('/cambiar-password')">
              <q-item-section avatar>
                <q-icon name="lock_reset" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Cambiar contraseña</q-item-label>
              </q-item-section>
            </q-item>

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
        </div>
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
            <q-avatar size="42px" class="drawer-brand__logo" rounded>
              <q-img src="/logo.png" width="72px" />
            </q-avatar>
            <div class="drawer-brand__text">
<!--              <div class="drawer-brand__title">SIL</div>-->
              <div class="drawer-brand__caption">Hospital General <br>San Juan de Dios</div>
            </div>
          </div>

          <div class="drawer-eyebrow">
            Módulos
          </div>

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
                  <q-item-label class="drawer-section__label">
                    {{ section.title }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge class="drawer-section__count" rounded>
                    {{ visibleSectionLinks(section).length }}
                  </q-badge>
                </q-item-section>
              </template>

              <q-list dense class="drawer-submenu">
                <template v-for="link in visibleSectionLinks(section)" :key="link.title">
                  <!-- Grupo anidado con children -->
                  <q-expansion-item
                    v-if="link.children"
                    dense dense-toggle
                    :default-opened="link.children.some(c => linkIsActive(c))"
                    header-class="drawer-subgroup-header"
                    class="drawer-subgroup-item"
                  >
                    <template v-slot:header>
                      <q-item-section avatar class="drawer-menu-link__avatar">
                        <q-icon :name="link.icon" size="17px" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label class="drawer-menu-link__label" lines="1">
                          {{ link.title }}
                        </q-item-label>
                      </q-item-section>
                    </template>
                    <q-item
                      v-for="child in visibleChildren(link)"
                      :key="child.title"
                      dense
                      :clickable="Boolean(child.link)"
                      :disable="!child.link"
                      v-bind="child.link ? { to: child.link, exact: child.exact !== false } : {}"
                      class="drawer-menu-link drawer-menu-link--child"
                      :active="linkIsActive(child)"
                      active-class="drawer-menu-link--active"
                    >
                      <q-item-section avatar class="drawer-menu-link__avatar">
                        <q-icon :name="child.icon" size="15px" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label class="drawer-menu-link__label" lines="1">
                          {{ child.title }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-expansion-item>

                  <!-- Link simple -->
                  <q-item
                    v-else
                    dense
                    :clickable="Boolean(link.link)"
                    :disable="!link.link"
                    v-bind="link.link ? { to: link.link, exact: link.exact !== false } : {}"
                    class="drawer-menu-link"
                    :active="linkIsActive(link)"
                    active-class="drawer-menu-link--active"
                  >
                    <q-item-section avatar class="drawer-menu-link__avatar">
                      <q-icon :name="link.icon" size="17px" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="drawer-menu-link__label" lines="1">
                        {{ link.title }}
                      </q-item-label>
                      <q-item-label
                        v-if="link.caption"
                        caption
                        class="drawer-menu-link__caption"
                        lines="1"
                      >
                        {{ link.caption }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-list>
            </q-expansion-item>
          </q-list>

          <div class="drawer-footer">
            <div>SIL v{{ $version }}</div>
            <div>© {{ new Date().getFullYear() }} Hospital General San Juan de Dios</div>
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

const menuSections = [
  {
    title: 'Hospital',
    icon: 'local_hospital',
    defaultOpened: true,
    links: [
      { title: 'Resumen', icon: 'dashboard', link: '/', can: 'Dashboard' },
      { title: 'Usuarios', icon: 'people', link: '/usuarios', can: 'Usuarios' },
      { title: 'Tiempo de registro', icon: 'schedule', link: '/usuarios/herramientas', can: 'Tiempo creación de usuario' },
      { title: 'Pacientes', icon: 'folder_shared', link: '/pacientes', can: 'Pacientes' },
      { title: 'Doctores', icon: 'medical_services', link: '/doctores', can: 'Doctores' },
      { title: 'Establecimientos', icon: 'domain_add', link: '/establecimientos', can: 'Establecimientos' },
      { title: 'Prestaciones', icon: 'room_service', link: '/servicios', can: 'Servicios' },
      { title: 'Consentimientos', icon: 'assignment_turned_in', link: '/consentimientos', can: 'Consentimientos' },
    ],
  },
  {
    title: 'Laboratorio SIL',
    icon: 'science',
    defaultOpened: false,
    links: [
      { title: 'Admisión', icon: 'request_page', link: '/solicitudes', can: 'Solicitudes' },
      { title: 'Recojo de Resultados', icon: 'inventory_2', link: '/recogidos', can: 'Solicitudes' },
      { title: 'Entrega de Resultados', icon: 'assignment_turned_in', link: '/entrega-resultados', can: 'Entrega de resultados' },
      { title: 'Preanalítica', icon: 'science', link: '/area-preanalitica', can: ['Area preanalitica', 'Area Preanalitica'] },
      { title: 'Estados preanalítica', icon: 'analytics', link: '/area-preanalitica-procesadas', can: ['Area preanalitica', 'Area Preanalitica'] },
      { title: 'Analítica', icon: 'biotech', link: '/analitica', can: 'Analitica', match: '/analitica' },
      { title: 'Formularios', icon: 'description', link: '/formularios', can: 'Formularios' },
    ],
  },
  {
    title: 'Almacén SIA',
    icon: 'warehouse',
    defaultOpened: false,
    links: [
      { title: 'Inventario', icon: 'inventory', link: '/almacen/inventario', can: 'Módulo inventario' },
      { title: 'Productos por vencer', icon: 'hourglass_bottom', link: '/almacen/productos-por-vencer', can: 'Módulo inventario' },
      { title: 'Productos vencidos', icon: 'warning', link: '/almacen/productos-vencidos', can: 'Módulo inventario' },
      { title: 'Proveedores', icon: 'local_shipping', link: '/almacen/proveedores', can: 'Módulo inventario' },
      { title: 'Compras nuevas', icon: 'add_shopping_cart', link: '/almacen/compras/nueva', can: ['Modulo compras', 'Módulo movimiento'] },
      { title: 'Compras detalle', icon: 'receipt_long', link: '/almacen/compras', can: ['Modulo detalle compras', 'Módulo movimiento'] },
      { title: 'Pedidos nuevos', icon: 'post_add', link: '/pedidos/nuevo', can: 'Crear Pedidos' },
      { title: 'Pedido de emergencia', icon: 'warning', link: '/pedidos/emergencia', can: 'Crear Pedidos de Emergencia' },
      { title: 'Pedidos detalles', icon: 'shopping_bag', link: '/pedidos', can: 'Ver Pedidos' },
      { title: 'Nuevo despacho', icon: 'local_shipping', link: '/despachos/nuevo', can: 'Crear Despachos' },
      { title: 'Despachos', icon: 'inventory', link: '/despachos', can: 'Ver Despachos' },
      { title: 'Solicitudes SAP', icon: 'description', link: '/solicitudes-sap', can: 'Ver Solicitudes SAP' },
      { title: 'Nueva solicitud SAP', icon: 'post_add', link: '/solicitudes-sap/nueva', can: 'Crear Solicitudes SAP' },
      { title: 'Herramientas', icon: 'build', link: '/almacen/herramientas', can: ['Ver Herramientas Almacén', 'Herramientas de Almacén'] },
      {
        title: 'Reportes', icon: 'insert_chart_outlined',
        children: [
          { title: 'Reporte Totales', icon: 'assessment', link: '/almacen/reporte-valorado', can: 'Reporte Valorado' },
          { title: 'Resumen y Detalle', icon: 'table_chart', link: '/almacen/reporte-resumen-detalle', can: 'Reporte Valorado' },
          { title: 'Reporte por Unidad', icon: 'domain', link: '/almacen/reporte-unidad', can: 'Reporte por Unidad' },
        ],
      },
      // { title: 'Faltantes y sobrantes', icon: 'rule', can: 'Módulo de faltantes y sobrantes', caption: 'Módulo' },
    ],
  },
  {
    title: 'Reportes',
    icon: 'insert_chart',
    defaultOpened: false,
    links: [
      { title: 'Servicios resumen', icon: 'bar_chart', link: '/reportes/servicios-resumen', can: ['Reportes'] },
      { title: 'Consentimientos', icon: 'assignment', link: '/reporte/consentimiento', can: 'Consentimientos' },
      { title: 'Solicitudes', icon: 'summarize', link: '/reporte/solicitudes', can: ['Solicitudes', 'Consentimientos'] },
    ],
  },
]

const userPermissions = computed(() => proxy.$store.permissions || [])
const visibleMenuSections = computed(() => (
  menuSections.filter(section => visibleSectionLinks(section).length > 0)
))

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function hasPermission (perm) {
  if (Array.isArray(perm)) return perm.some(item => hasPermission(item))
  return userPermissions.value.includes(perm)
}

function visibleChildren (link) {
  return (link.children || []).filter(child => hasPermission(child.can))
}

function visibleSectionLinks (section) {
  return section.links.filter(link => {
    if (link.children) return visibleChildren(link).length > 0
    return hasPermission(link.can)
  })
}

function linkIsActive (link) {
  if (!link.link) return false
  if (link.match) return proxy.$route.path.startsWith(link.match)
  return proxy.$route.path === link.link
}

function sectionIsActive (section) {
  return section.links.some(link => {
    if (link.children) return link.children.some(child => linkIsActive(child))
    return linkIsActive(link)
  })
}

function sectionHeaderClass (section) {
  return sectionIsActive(section)
    ? 'drawer-section-header drawer-section-header--active'
    : 'drawer-section-header'
}
function logout () {
  proxy.$alert.dialog('¿Desea salir del sistema?')
    .onOk(() => {
      proxy.$axios.post('/logout')
        .then(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          proxy.$store.permissions = []
          localStorage.removeItem('tokenSil')
          localStorage.removeItem('permissionsSil')
          proxy.$router.push('/login')
        })
        // .catch(() => proxy.$alert.error('Error al cerrar sesión. Intente nuevamente.'))
        .catch(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          proxy.$store.permissions = []
          localStorage.removeItem('tokenSil')
          localStorage.removeItem('permissionsSil')
          proxy.$router.push('/login')
        })
    })
}

const roleText = computed(() => {
  const role = proxy.$store.user.role
  if (!role) return ''
  if (role === 'Administrador') return 'Administrador'
  return role
})
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

.drawer-brand__logo {
  flex: 0 0 auto;
  background: #ffffff;
}

.drawer-brand__text {
  min-width: 0;
  line-height: 1.05;
}

.drawer-brand__title {
  font-size: 13px;
  font-weight: 800;
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

.drawer-menu-link__caption {
  color: rgba(255, 255, 255, 0.5);
  font-size: 10px;
  line-height: 1;
}

.drawer-menu-link--active {
  background: #0b76c5;
  color: #ffffff !important;
  box-shadow: inset 3px 0 0 #ffd166;
}

.drawer-menu-link.q-item--disabled {
  opacity: 0.62 !important;
}

.drawer-footer {
  padding: 8px 9px 6px;
  margin-top: 8px;
  color: rgba(255, 255, 255, 0.58);
  font-size: 10px;
  line-height: 1.35;
}

.drawer-subgroup-item {
  margin: 1px 5px 1px 13px;
}

:deep(.drawer-subgroup-header) {
  min-height: 28px;
  padding: 0 7px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.86);
}

.drawer-menu-link--child {
  margin-left: 26px !important;
}

.drawer-logout {
  min-height: 30px;
  margin: 2px 5px 0;
  border-radius: 9px;
  color: #ffe6e3;
  background: rgba(229, 57, 53, 0.16);
}
</style>
