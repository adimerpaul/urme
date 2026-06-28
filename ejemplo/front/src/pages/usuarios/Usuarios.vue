<template>
  <q-page class="q-pa-md">
    <q-table :rows="users" :columns="columns" dense wrap-cells flat bordered :rows-per-page-options="[0]"
             title="Usuarios" :filter="filter">
      <template v-slot:top-right>
        <q-btn color="positive" label="Nuevo" @click="userNew" no-caps icon="add_circle_outline" :loading="loading"
               class="q-mr-sm"/>
        <q-btn color="secondary" label="Unidades" @click="abrirUnidades(false)" no-caps icon="business"
               class="q-mr-sm"/>
        <q-btn v-if="canGestionarTiempoRegistro" color="deep-orange" label="Tiempo de registro"
               @click="$router.push('/usuarios/herramientas')" no-caps icon="schedule" class="q-mr-sm"/>
        <q-btn color="primary" label="Actualizar" @click="usersGet" no-caps icon="refresh" :loading="loading"/>
        <q-input v-model="filter" label="Buscar" dense outlined>
          <template v-slot:append>
            <q-icon name="search"/>
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable @click="userEdit(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="edit"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Editar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="userDelete(props.row.id)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="delete"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Eliminar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="userResetPassword(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="lock_reset"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Restablecer contraseña</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="cambiarAvatar(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="image"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Cambiar avatar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="abrirFirma(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="draw"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Agregar firma</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="abrirSello(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="approval"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Subir sello</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="subpartidasShow(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="category"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Subpartidas</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="permisosShow(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="lock" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Permisos</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
      <template v-slot:body-cell-role="props">
        <q-td :props="props">
          <q-chip :label="props.row.role"
                  :color="$filters.color(props.row.role)"
                  text-color="white" dense size="14px"/>
        </q-td>
      </template>
      <template v-slot:body-cell-avatar="props">
        <q-td :props="props">
          <q-avatar rounded>
            <q-img :src="`${$url}/../images/${props.row.avatar}`" width="40px" height="40px" v-if="props.row.avatar"/>
            <q-icon name="person" size="40px" v-else/>
          </q-avatar>
        </q-td>
      </template>
      <template v-slot:body-cell-permissions="props">
        <q-td :props="props">
          <div class="row items-center q-col-gutter-xs">

            <!-- hasta 3 chips visibles -->
            <q-chip
              v-for="(perm, idx) in (props.row.permissions || []).slice(0, 3)"
              :key="perm.id"
              dense
              color="grey-3"
              text-color="black"
              size="12px"
              class="q-mr-xs q-mb-xs"
            >
              {{ perm.name }}
            </q-chip>

            <!-- si hay más, badge + tooltip con el listado completo -->
            <template v-if="(props.row.permissions || []).length > 3">
              <q-badge outline color="primary" class="q-ml-xs">
                +{{ (props.row.permissions || []).length - 3 }}
                <q-tooltip anchor="top middle" self="bottom middle" :offset="[0,8]">
                  <div class="text-left">
                    <div
                      v-for="perm in props.row.permissions"
                      :key="perm.id"
                    >• {{ perm.name }}</div>
                  </div>
                </q-tooltip>
              </q-badge>
            </template>

            <!-- sin permisos -->
            <q-badge v-if="!(props.row.permissions || []).length" color="grey-5" text-color="white" outline>
              Sin permisos
            </q-badge>
          </div>
        </q-td>
      </template>
      <!--      <template v-slot:body-cell-permisos="props">-->
      <!--        <q-td :props="props">-->
      <!--          <ul class="pm-0">-->
      <!--            <li class="pm-0" v-for="permiso in props.row.userPermisos" :key="permiso.id">-->
      <!--              {{ permiso?.permiso?.nombre }}-->
      <!--            </li>-->
      <!--          </ul>-->
      <!--        </q-td>-->
      <!--      </template>-->
    </q-table>
<!--    <pre>{{ users }}</pre>-->
    <q-dialog v-model="userDialog" persistent :maximized="$q.screen.lt.sm">
      <q-card class="user-dialog-card">
        <q-card-section class="q-pb-none row items-center">
          <q-icon name="person" size="20px" class="text-primary q-mr-sm"/>
          <span class="text-weight-bold">{{ actionUser }} usuario</span>
          <q-space/>
          <q-btn icon="close" flat round dense @click="userDialog = false"/>
        </q-card-section>
        <q-card-section class="q-pt-sm user-dialog-body">
          <q-form @submit="user.id ? userPut() : userPost()">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input v-model="user.name" label="Nombre" dense outlined :rules="[val => !!val || 'Campo requerido']"/>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="user.username" label="Usuario" dense outlined :rules="[val => !!val || 'Campo requerido']"/>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="user.email" label="Email" dense outlined/>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="user.celular" label="Celular" dense outlined/>
              </div>
              <div class="col-12 col-sm-6">
                <q-input v-model="user.ci" label="CI" dense outlined/>
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="user.max_pedidos"
                  label="Máx. pedidos por mes"
                  dense outlined
                  type="number"
                  min="1"
                  hint="Pedidos activos que puede hacer al mes (mín. 1)"
                />
              </div>
              <div class="col-12 col-sm-6" v-if="!user.id">
                <q-input v-model="user.password" label="Contraseña" dense outlined :rules="[val => !!val || 'Campo requerido']"/>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="user.role" label="Rol" dense outlined :options="roles" :rules="[val => !!val || 'Campo requerido']"/>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="user.area_id" label="Area" dense outlined :options="areas"
                          :option-label="'name'" :option-value="'id'" emit-value map-options clearable/>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="user.establecimiento_id" label="Establecimiento" dense outlined
                          :options="filteredEstablecimientos" option-label="label" option-value="value"
                          emit-value map-options use-input input-debounce="0"
                          @filter="filterEstablecimientos" clearable>
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-12 col-sm-6">
                <q-select v-model="user.unidad_id" label="Unidad" dense outlined
                          :options="filteredUnidadesOpts" option-label="nombre" option-value="id"
                          emit-value map-options use-input input-debounce="0"
                          @filter="filterUnidades" clearable>
                  <template v-slot:no-option>
                    <q-item><q-item-section class="text-grey">Sin resultados</q-item-section></q-item>
                  </template>
                </q-select>
              </div>
              <div class="col-6">
                <q-checkbox v-model="user.mostrar_firma" label="Mostrar firma" dense/>
              </div>
              <div class="col-6">
                <q-checkbox v-model="user.mostrar_sello" label="Mostrar sello" dense/>
              </div>
            </div>

            <q-separator class="q-my-sm"/>

            <div class="row items-center q-mb-xs">
              <q-icon name="admin_panel_settings" size="18px" class="text-primary q-mr-xs"/>
              <span class="text-weight-bold text-body2">Permisos</span>
              <q-space/>
              <q-badge color="primary" outline>{{ selectedPermissionsCount }} activos</q-badge>
            </div>
            <q-input v-model="permFilter" dense outlined clearable placeholder="Filtrar permisos..." class="q-mb-sm">
              <template v-slot:prepend><q-icon name="search"/></template>
            </q-input>
            <div class="user-dialog-perms-body">
              <div v-if="loading && !permissions.length" class="text-center q-pa-md">
                <q-spinner-dots color="primary" size="30px"/>
              </div>
              <q-list v-else dense class="permission-groups">
                <q-expansion-item
                  v-for="group in filteredPermissionGroups"
                  :key="group.key"
                  dense dense-toggle expand-separator
                  :default-opened="group.defaultOpened || Boolean(permFilter)"
                  header-class="permission-group-header"
                >
                  <template v-slot:header>
                    <q-item-section avatar class="permission-group-header__icon">
                      <q-icon :name="group.icon" size="19px"/>
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-weight-bold">{{ group.label }}</q-item-label>
                      <q-item-label caption>{{ group.checkedCount }}/{{ group.permissions.length }} activos</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <div class="row no-wrap">
                        <q-btn dense flat round icon="done_all" size="sm" @click.stop="setGroupPermissions(group, true)">
                          <q-tooltip>Activar grupo</q-tooltip>
                        </q-btn>
                        <q-btn dense flat round icon="remove_done" size="sm" @click.stop="setGroupPermissions(group, false)">
                          <q-tooltip>Desactivar grupo</q-tooltip>
                        </q-btn>
                      </div>
                    </q-item-section>
                  </template>
                  <div class="permission-grid">
                    <q-item v-for="perm in group.permissions" :key="perm.id" dense clickable
                            class="permission-row" @click="perm.checked = !perm.checked">
                      <q-item-section avatar class="permission-row__icon">
                        <q-icon :name="perm.checked ? 'check_circle' : 'radio_button_unchecked'"
                                :color="perm.checked ? 'primary' : 'grey-5'" size="18px"/>
                      </q-item-section>
                      <q-item-section>
                        <q-item-label lines="2" class="permission-row__label">{{ perm.name }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <q-toggle v-model="perm.checked" dense color="primary" @click.stop/>
                      </q-item-section>
                    </q-item>
                  </div>
                </q-expansion-item>
              </q-list>
              <div v-if="!loading && filteredPermissionGroups.length === 0" class="permission-empty">
                No hay permisos para mostrar
              </div>
            </div>

            <div class="text-right q-mt-sm">
              <q-btn color="negative" label="Cancelar" @click="userDialog = false" no-caps :loading="loading"/>
              <q-btn color="primary" :label="user.id ? 'Guardar' : 'Crear'" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <!--    dialogPermisos-->
    <!--    <q-dialog v-model="dialogPermisos" persistent>-->
    <!--      <q-card>-->
    <!--        <q-card-section class="q-pb-none row items-center text-bold">-->
    <!--          Permisos-->
    <!--          <q-space />-->
    <!--          <q-btn icon="close" flat round dense @click="dialogPermisos = false" />-->
    <!--        </q-card-section>-->
    <!--        <q-card-section class="q-pt-none">-->
    <!--          <q-list dense>-->
    <!--            <q-item v-for="permiso in permissions" :key="permiso.id">-->
    <!--              <q-item-section>-->
    <!--                <q-item-label>{{ permiso.nombre }}</q-item-label>-->
    <!--              </q-item-section>-->
    <!--              <q-item-section side>-->
    <!--                <q-toggle v-model="permiso.checked" />-->
    <!--              </q-item-section>-->
    <!--            </q-item>-->
    <!--          </q-list>-->
    <!--          &lt;!&ndash;          <pre>{{ user }}</pre>&ndash;&gt;-->
    <!--        </q-card-section>-->
    <!--        <q-card-actions align="right">-->
    <!--          <q-btn color="negative" label="Cancelar" @click="dialogPermisos = false" no-caps :loading="loading" />-->
    <!--          <q-btn color="primary" label="Guardar" @click="permisosPost" no-caps :loading="loading" />-->
    <!--        </q-card-actions>-->
    <!--      </q-card>-->
    <!--    </q-dialog>-->
    <q-dialog v-model="cambioAvatarDialogo" persistent>
      <q-card>
        <q-card-section class="q-pb-none row items-center text-bold">
          Cambiar avatar
          <q-space/>
          <q-btn icon="close" flat round dense @click="cambioAvatarDialogo = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="userPut()">
            <!--            <q-avatar>-->
            <div>
              <div style="position: relative;top: 0;left: 0;">
                <q-btn icon="edit" size="10px" class="absolute q-mt-sm q-ml-sm" @click="$refs.fileInput.click()" dense
                       outline label="Cambiar foto" no-caps/>
              </div>
              <img :src="`${$url}/../images/${user.avatar}`" width="300px" height="300px" v-if="user.avatar"/>
              <q-icon name="person" size="100px" v-else/>
              <input ref="fileInput" type="file" style="display: none;" @change="onFileChange" accept="image/*"/>
            </div>
            <!--            </q-avatar>-->
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="cambioAvatarDialogo = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogPermisos" persistent :maximized="$q.screen.lt.sm">
      <q-card class="permission-card">
        <q-card-section class="permission-card__header">
          <div class="row items-center no-wrap full-width">
            <q-icon name="admin_panel_settings" size="22px" class="text-primary q-mr-sm" />
            <div class="permission-card__title">
              <div class="text-weight-bold">Permisos</div>
              <div class="text-caption text-grey-7 ellipsis">
                {{ user.username }}
              </div>
            </div>
            <q-space />
            <q-badge color="primary" outline class="q-mr-sm">
              {{ selectedPermissionsCount }} activos
            </q-badge>
            <q-btn icon="close" flat round dense @click="dialogPermisos = false" />
          </div>
        </q-card-section>

        <q-card-section class="permission-card__filters">
          <q-input v-model="permFilter" dense outlined clearable placeholder="Filtrar permisos...">
            <template v-slot:prepend>
              <q-icon name="search" />
            </template>
          </q-input>
        </q-card-section>

        <q-separator />

        <q-card-section class="permission-card__body">
          <q-list dense class="permission-groups">
            <q-expansion-item
              v-for="group in filteredPermissionGroups"
              :key="group.key"
              dense
              dense-toggle
              expand-separator
              :default-opened="group.defaultOpened || Boolean(permFilter)"
              header-class="permission-group-header"
            >
              <template v-slot:header>
                <q-item-section avatar class="permission-group-header__icon">
                  <q-icon :name="group.icon" size="19px" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ group.label }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ group.checkedCount }}/{{ group.permissions.length }} activos
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn dense flat round icon="done_all" size="sm" @click.stop="setGroupPermissions(group, true)">
                      <q-tooltip>Activar grupo</q-tooltip>
                    </q-btn>
                    <q-btn dense flat round icon="remove_done" size="sm" @click.stop="setGroupPermissions(group, false)">
                      <q-tooltip>Desactivar grupo</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </template>

              <div class="permission-grid">
                <q-item
                  v-for="perm in group.permissions"
                  :key="perm.id"
                  dense
                  clickable
                  class="permission-row"
                  @click="perm.checked = !perm.checked"
                >
                  <q-item-section avatar class="permission-row__icon">
                    <q-icon
                      :name="perm.checked ? 'check_circle' : 'radio_button_unchecked'"
                      :color="perm.checked ? 'primary' : 'grey-5'"
                      size="18px"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label lines="2" class="permission-row__label">
                      {{ perm.name }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-toggle v-model="perm.checked" dense color="primary" @click.stop />
                  </q-item-section>
                </q-item>
              </div>
            </q-expansion-item>
          </q-list>

          <div v-if="filteredPermissionGroups.length === 0" class="permission-empty">
            No hay permisos para mostrar
          </div>
        </q-card-section>

        <q-card-actions align="right" class="permission-card__actions">
          <q-btn color="negative" label="Cancelar" @click="dialogPermisos = false" no-caps :loading="loading" />
          <q-btn color="primary" label="Guardar" @click="permisosPost" no-caps :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo de subpartidas -->
    <q-dialog v-model="dialogSubpartidas" persistent :maximized="$q.screen.lt.sm">
      <q-card class="subpartida-card">
        <q-card-section class="row items-center q-pb-none">
          <q-icon name="category" color="primary" size="22px" class="q-mr-sm"/>
          <div>
            <div class="text-weight-bold">Subpartidas</div>
            <div class="text-caption text-grey-7">{{ user.username }}</div>
          </div>
          <q-space/>
          <q-badge color="primary" outline class="q-mr-sm">
            {{ subpartidasSeleccionadas.length }} seleccionadas
          </q-badge>
          <q-btn icon="close" flat round dense @click="dialogSubpartidas = false"/>
        </q-card-section>

        <q-card-section class="q-pt-sm q-pb-xs">
          <q-input v-model="subpartidaFilter" dense outlined clearable placeholder="Buscar subpartida...">
            <template v-slot:prepend><q-icon name="search"/></template>
          </q-input>
        </q-card-section>

        <q-separator/>

        <q-card-section style="max-height: min(65vh, 520px); overflow-y: auto; padding: 8px 12px;">
          <div v-if="loading" class="text-center q-pa-md">
            <q-spinner color="primary" size="30px"/>
          </div>
          <template v-else>
            <div v-if="subpartidasAgrupadas.length === 0" class="text-grey-6 text-center q-pa-md">
              Sin resultados
            </div>
            <q-expansion-item
              v-for="partida in subpartidasAgrupadas"
              :key="partida.id"
              dense dense-toggle expand-separator
              :default-opened="!!subpartidaFilter || partida.tieneSeleccionadas"
              header-class="text-weight-bold"
            >
              <template v-slot:header>
                <q-item-section avatar style="min-width:28px">
                  <q-icon name="folder" size="18px" color="amber-8"/>
                </q-item-section>
                <q-item-section>
                  <span>{{ partida.codigo }} — {{ partida.nombre }}</span>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn dense flat round icon="done_all" size="sm"
                           @click.stop="marcarGrupo(partida.subpartidas, true)">
                      <q-tooltip>Marcar todas</q-tooltip>
                    </q-btn>
                    <q-btn dense flat round icon="remove_done" size="sm"
                           @click.stop="marcarGrupo(partida.subpartidas, false)">
                      <q-tooltip>Desmarcar todas</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </template>

              <div class="subpartida-grid q-pb-sm">
                <q-item
                  v-for="sp in partida.subpartidas"
                  :key="sp.id"
                  dense clickable
                  class="subpartida-row"
                  @click="toggleSubpartida(sp.id)"
                >
                  <q-item-section avatar style="min-width:28px">
                    <q-icon
                      :name="subpartidasSeleccionadas.includes(sp.id) ? 'check_box' : 'check_box_outline_blank'"
                      :color="subpartidasSeleccionadas.includes(sp.id) ? 'primary' : 'grey-4'"
                      size="20px"
                    />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-caption">
                      <span class="text-weight-medium">{{ sp.codigo }}</span> — {{ sp.nombre }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </div>
            </q-expansion-item>
          </template>
        </q-card-section>

        <q-separator/>

        <q-card-actions align="right" class="q-pa-sm">
          <q-btn label="Cancelar" flat color="negative" @click="dialogSubpartidas = false" no-caps/>
          <q-btn label="Guardar" color="primary" icon="save" @click="subpartidasPost" no-caps :loading="loading"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo de firma -->
    <q-dialog v-model="firmaDialogo" persistent @show="firmaInit">
      <q-card style="width: 540px; max-width: 98vw">
        <q-card-section class="row items-center q-pb-none">
          <q-icon name="draw" color="primary" class="q-mr-sm"/>
          <div class="text-weight-bold">Firma — {{ user.name }}</div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="firmaDialogo = false"/>
        </q-card-section>

        <q-card-section>
          <div class="text-caption text-grey-6 q-mb-xs">Dibuje su firma en el área de abajo (funciona con dedo en celular)</div>
          <div style="border: 2px solid #90caf9; border-radius: 6px; background: #fff; cursor: crosshair; touch-action: none; overflow: hidden;">
            <canvas
              ref="firmaCanvas"
              style="width: 100%; display: block; height: 200px;"
              @mousedown="firmaStart"
              @mousemove="firmaMove"
              @mouseup="firmaEnd"
              @mouseleave="firmaEnd"
              @touchstart.prevent="firmaStart"
              @touchmove.prevent="firmaMove"
              @touchend="firmaEnd"
            />
          </div>

          <!-- previsualización firma actual -->
          <div v-if="user.firma" class="q-mt-sm text-caption text-grey-7">
            Firma actual:
            <img :src="`${$url}/../images/${user.firma}`" style="max-height: 60px; max-width: 100%; display: block; margin-top: 4px; border: 1px solid #eee;"/>
          </div>
        </q-card-section>

        <q-card-actions class="row justify-between q-px-md q-pb-md">
          <q-btn label="Limpiar" icon="refresh" flat color="grey-7" @click="firmaClear" no-caps/>
          <div>
            <q-btn label="Cancelar" flat color="negative" @click="firmaDialogo = false" no-caps class="q-mr-sm"/>
            <q-btn label="Guardar" icon="save" color="primary" @click="firmaGuardar" no-caps :loading="loading"/>
          </div>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo de sello -->
    <q-dialog v-model="selloDialogo" persistent>
      <q-card style="width: 360px">
        <q-card-section class="row items-center q-pb-none">
          <q-icon name="approval" color="secondary" class="q-mr-sm"/>
          <div class="text-weight-bold">Sello — {{ user.name }}</div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="selloDialogo = false"/>
        </q-card-section>

        <q-card-section class="text-center">
          <div style="position: relative; display: inline-block;">
            <img v-if="user.sello" :src="`${$url}/../images/${user.sello}`"
                 style="max-width: 280px; max-height: 280px; object-fit: contain; border: 1px solid #eee; display: block;"/>
            <div v-else class="q-pa-lg text-grey-4">
              <q-icon name="approval" size="80px"/>
              <div class="text-caption q-mt-sm">Sin sello</div>
            </div>
          </div>
          <div class="q-mt-md">
            <q-btn icon="upload" label="Seleccionar imagen" no-caps outline color="primary"
                   @click="$refs.selloInput.click()"/>
            <input ref="selloInput" type="file" style="display: none;" @change="onSelloChange" accept="image/*"/>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn label="Cerrar" flat @click="selloDialogo = false" no-caps/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo de unidades: selección + CRUD -->
    <q-dialog v-model="dialogoUnidades" persistent>
      <q-card style="width: 520px; max-width: 95vw">
        <q-card-section class="row items-center q-pb-none">
          <q-icon name="business" color="secondary" class="q-mr-sm"/>
          <div class="text-weight-bold">Unidades</div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="dialogoUnidades = false"/>
        </q-card-section>

        <!-- Formulario crear / editar -->
        <q-card-section class="q-pt-sm q-pb-none">
          <q-form @submit.prevent="editandoUnidad ? unidadPut() : unidadPost()" class="row q-col-gutter-xs items-center">
            <div class="col">
              <q-input
                v-model="inputNombreUnidad"
                :label="editandoUnidad ? 'Modificar unidad' : 'Nueva unidad'"
                dense outlined placeholder="Nombre de la unidad"
                :color="editandoUnidad ? 'primary' : 'positive'"
              />
            </div>
            <div class="col-auto row no-wrap q-col-gutter-xs">
              <q-btn v-if="editandoUnidad" icon="close" flat dense color="grey-7" @click="editandoUnidad = null; inputNombreUnidad = ''">
                <q-tooltip>Cancelar edición</q-tooltip>
              </q-btn>
              <q-btn type="submit" :icon="editandoUnidad ? 'save' : 'add'"
                     :color="editandoUnidad ? 'primary' : 'positive'" dense :loading="loading">
                <q-tooltip>{{ editandoUnidad ? 'Guardar cambios' : 'Agregar' }}</q-tooltip>
              </q-btn>
            </div>
          </q-form>
        </q-card-section>

        <!-- Filtro -->
        <q-card-section class="q-pt-sm q-pb-xs">
          <q-input v-model="unidadFilter" dense outlined clearable placeholder="Buscar...">
            <template v-slot:prepend><q-icon name="search"/></template>
          </q-input>
        </q-card-section>

        <!-- Lista -->
        <q-card-section style="max-height: 340px; overflow-y: auto; padding-top: 0">
          <q-list bordered separator dense>
            <q-item v-if="filteredUnidades.length === 0" class="text-grey-6 text-caption">
              <q-item-section>Sin resultados</q-item-section>
            </q-item>
            <q-item v-for="u in filteredUnidades" :key="u.id" dense :active="editandoUnidad?.id === u.id" active-class="bg-blue-1">
              <q-item-section avatar style="min-width: 0">
                <q-btn-dropdown dense flat color="primary" dropdown-icon="more_vert" no-icon-animation size="sm">
                  <q-list dense style="min-width: 140px">
                    <q-item v-if="modoSeleccionUnidad" clickable v-close-popup @click="seleccionarUnidad(u)">
                      <q-item-section avatar><q-icon name="check_circle" color="primary" size="18px"/></q-item-section>
                      <q-item-section>Seleccionar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="editarUnidad(u)">
                      <q-item-section avatar><q-icon name="edit" color="primary" size="18px"/></q-item-section>
                      <q-item-section>Modificar</q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup @click="unidadDelete(u.id)">
                      <q-item-section avatar><q-icon name="delete" color="negative" size="18px"/></q-item-section>
                      <q-item-section class="text-negative">Eliminar</q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-caption">{{ u.nombre }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn label="Cerrar" flat @click="dialogoUnidades = false" no-caps/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialog Tiempo de registro de usuarios -->
    <q-dialog v-model="dialogoHerramientaUsuario" persistent>
      <q-card style="width: min(94vw, 500px)">
        <q-card-section class="row items-center q-pb-xs">
          <q-icon name="schedule" color="deep-orange" size="22px" class="q-mr-sm"/>
          <div>
            <div class="text-subtitle1 text-weight-bold">Tiempo de registro de usuarios</div>
            <div class="text-caption text-grey-7">Período en que los usuarios pueden crear su propia cuenta</div>
          </div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="dialogoHerramientaUsuario = false"/>
        </q-card-section>

        <q-separator/>

        <q-card-section class="q-pa-sm q-px-md">
          <q-banner v-if="herramientaSettings.registro_habilitado" rounded
                    class="bg-green-1 text-green-10 q-mb-md" style="border:1px solid #a5d6a7">
            <template #avatar><q-icon name="check_circle" color="green-7" size="24px"/></template>
            <div class="text-weight-bold">Registro habilitado</div>
            <div class="text-body2">
              {{ formatFecha(herramientaSettings.fecha_inicio_registro) }} al {{ formatFecha(herramientaSettings.fecha_fin_registro) }}
            </div>
          </q-banner>
          <q-banner v-else rounded class="bg-orange-1 text-orange-10 q-mb-md" style="border:1px solid #ffcc80">
            <template #avatar><q-icon name="block" color="orange-7" size="24px"/></template>
            <div class="text-weight-bold">Registro deshabilitado</div>
            <div class="text-body2">No hay un período activo en este momento.</div>
          </q-banner>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-6">
              <q-input v-model="herramientaForm.fecha_inicio_registro" outlined dense type="datetime-local" label="Fecha y hora inicio" :disable="herramientaSaving">
                <template #prepend><q-icon name="event"/></template>
              </q-input>
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="herramientaForm.fecha_fin_registro" outlined dense type="datetime-local" label="Fecha y hora fin" :disable="herramientaSaving">
                <template #prepend><q-icon name="event_available"/></template>
              </q-input>
            </div>
          </div>
        </q-card-section>

        <q-separator/>

        <q-card-actions align="right" class="q-pa-sm">
          <q-btn flat no-caps color="grey-8" icon="clear" label="Limpiar"
                 :disable="herramientaSaving" @click="herramientaForm.fecha_inicio_registro = ''; herramientaForm.fecha_fin_registro = ''"/>
          <q-btn unelevated no-caps color="deep-orange" icon="save" label="Guardar"
                 :loading="herramientaSaving" @click="guardarHerramientaUsuario"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>
<script>
import moment from 'moment'

const HOSPITAL_PERMISSION_NAMES = [
  'Dashboard',
  'Usuarios',
  'Pacientes',
  'Doctores',
  'Establecimientos',
  'Servicios',
  'Tiempo creación de usuario',
]

const ALMACEN_PERMISSION_NAMES = [
  'Módulo inventario',
  'Modulo compras',
  'Modulo detalle compras',
  'Módulo movimiento',
  'Módulo de faltantes y sobrantes',
  'Ver Pedidos',
  'Ver todos los pedidos',
  'Crear Pedidos',
  'Crear Pedidos de Emergencia',
  'Editar Pedidos',
  'Anular Pedidos',
  'Imprimir Pedidos',
  'Ver Despachos',
  'Crear Despachos',
  'Editar Despachos',
  'Anular Despachos',
  'Imprimir Despachos',
  'Ver Solicitudes SAP',
  'Crear Solicitudes SAP',
  'Editar Solicitudes SAP',
  'Eliminar Solicitudes SAP',
  'Anular Solicitudes SAP',
  'Imprimir Solicitudes SAP',
  'Ver Herramientas Almacén',
  'Herramientas de Almacén',
  'Reporte Valorado',
  'Reporte por Unidad',
  'Imprimir Compras',
]

const PERMISSION_GROUPS = [
  {
    key: 'hospital',
    label: 'Hospital',
    icon: 'local_hospital',
    defaultOpened: true,
    names: HOSPITAL_PERMISSION_NAMES,
  },
  {
    key: 'laboratorio',
    label: 'Laboratorio',
    icon: 'science',
    defaultOpened: true,
    names: [],
  },
  {
    key: 'almacen',
    label: 'Almacén SIA',
    icon: 'warehouse',
    defaultOpened: true,
    names: ALMACEN_PERMISSION_NAMES,
  },
]

export default {
  name: 'UsuariosPage',
  data() {
    return {
      users: [],
      user: {},
      userDialog: false,
      loading: false,
      actionUser: '',
      gestiones: [],
      filter: '',
      roles: ['Medico', 'Admision', 'Preanalitica', 'Analitica', 'Almacén', 'Administrador'],
      // areas : [
      //   'Administracion',
      //   'Hematologia',
      //   'Quimica Sanguinea',
      //   'Uruanalisis y Parasitologia',
      //   'Bacterologia',
      //   'Inmunologia',
      // ],
      columns: [
        {name: 'actions', label: 'Acciones', align: 'center'},
        {name: 'name', label: 'Nombre', align: 'left', field: 'name'},
        {name: 'username', label: 'Usuario', align: 'left', field: 'username'},
        {name: 'avatar', label: 'Avatar', align: 'left', field: (row) => row.avatar},
        {name: 'role', label: 'Rol', align: 'left', field: 'role'},
        {name: 'celular', label: 'Celular', align: 'left', field: 'celular'},
        { name: 'permissions', label: 'Permisos', align: 'left',
          field: row => (row.permissions || []).map(p => p.name).join(', ')
        },
        // area
        {name: 'area', label: 'Area', align: 'left', field: row => row.area?.name || ''},
        // establecimiento
        {name: 'establecimiento', label: 'Establecimiento', align: 'left', field: row => row.establecimiento?.nombre || ''},
        {name: 'unidad', label: 'Unidad', align: 'left', field: row => row.unidad?.nombre || ''},
      ],
      permissions: [],
      dialogPermisos: false,
      permFilter: '',
      cambioAvatarDialogo: false,
      dialogSubpartidas: false,
      todasSubpartidas: [],
      subpartidasSeleccionadas: [],
      subpartidaFilter: '',
      firmaDialogo: false,
      firmaDrawing: false,
      firmaLastX: 0,
      firmaLastY: 0,
      selloDialogo: false,
      docentes: [],
      establecimientos: [],
      areas: [],
      unidades: [],
      filteredUnidadesOpts: [],
      filteredEstablecimientos: [],
      dialogoUnidades: false,
      modoSeleccionUnidad: false,
      nuevaUnidad: { nombre: '' },
      editandoUnidad: null,
      inputNombreUnidad: '',
      unidadFilter: '',
      dialogoHerramientaUsuario: false,
      herramientaSettings: { fecha_inicio_registro: null, fecha_fin_registro: null, registro_habilitado: false },
      herramientaForm: { fecha_inicio_registro: '', fecha_fin_registro: '' },
      herramientaSaving: false,
    }
  },
  async mounted() {
    // this.docentes = await this.$axios.get('docentes').then(res => res.data)
    this.usersGet()
    this.areasGet()
    this.unidadesGet()
    // this.permissionsGet()
    this.$axios.get('establecimientos').then(res => {
      this.establecimientos = res.data
      this.filteredEstablecimientos = res.data.map(e => ({ label: e.nombre, value: e.id }))
    }).catch(error => {
      this.$alert.error(error.response.data.message)
    })
  },
  methods: {
    async subpartidasShow(user) {
      this.user = { ...user }
      this.dialogSubpartidas = true
      this.loading = true
      this.subpartidaFilter = ''
      try {
        const [todas, seleccionadas] = await Promise.all([
          this.$axios.get('subpartidas').then(r => r.data),
          this.$axios.get(`users/${user.id}/subpartidas`).then(r => r.data),
        ])
        this.todasSubpartidas = todas
        this.subpartidasSeleccionadas = seleccionadas
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando subpartidas')
      } finally {
        this.loading = false
      }
    },
    async subpartidasPost() {
      this.loading = true
      try {
        await this.$axios.put(`users/${this.user.id}/subpartidas`, {
          subpartidas: this.subpartidasSeleccionadas,
        })
        this.dialogSubpartidas = false
        this.$alert.success('Subpartidas actualizadas')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.loading = false
      }
    },
    toggleSubpartida(id) {
      const idx = this.subpartidasSeleccionadas.indexOf(id)
      if (idx === -1) this.subpartidasSeleccionadas.push(id)
      else this.subpartidasSeleccionadas.splice(idx, 1)
    },
    marcarGrupo(subpartidas, valor) {
      subpartidas.forEach(sp => {
        const idx = this.subpartidasSeleccionadas.indexOf(sp.id)
        if (valor && idx === -1) this.subpartidasSeleccionadas.push(sp.id)
        if (!valor && idx !== -1) this.subpartidasSeleccionadas.splice(idx, 1)
      })
    },
    areasGet() {
      this.$axios.get('areas').then(res => {
        this.areas = res.data
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      })
    },
    unidadesGet() {
      this.$axios.get('unidades').then(res => {
        this.unidades = res.data
        this.filteredUnidadesOpts = res.data
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al cargar unidades')
      })
    },
    filterEstablecimientos(val, update) {
      update(() => {
        const q = val.toLowerCase()
        this.filteredEstablecimientos = this.establecimientos
          .map(e => ({ label: e.nombre, value: e.id }))
          .filter(e => !q || e.label.toLowerCase().includes(q))
      })
    },
    filterUnidades(val, update) {
      update(() => {
        const q = val.toLowerCase()
        this.filteredUnidadesOpts = this.unidades
          .filter(u => !q || u.nombre.toLowerCase().includes(q))
      })
    },
    async abrirHerramientaUsuario() {
      this.dialogoHerramientaUsuario = true
      try {
        const res = await this.$axios.get('herramientas-usuario')
        this.herramientaSettings = res.data
        this.herramientaForm.fecha_inicio_registro = res.data.fecha_inicio_registro || ''
        this.herramientaForm.fecha_fin_registro    = res.data.fecha_fin_registro    || ''
      } catch {}
    },
    async guardarHerramientaUsuario() {
      const inicio = this.herramientaForm.fecha_inicio_registro
      const fin    = this.herramientaForm.fecha_fin_registro
      if ((inicio && !fin) || (!inicio && fin)) {
        this.$alert.error('Debes definir ambas fechas o dejar las dos vacías')
        return
      }
      if (inicio && fin && fin < inicio) {
        this.$alert.error('La fecha fin debe ser igual o posterior a la fecha inicio')
        return
      }
      this.herramientaSaving = true
      try {
        const res = await this.$axios.put('herramientas-usuario', {
          fecha_inicio_registro: inicio || null,
          fecha_fin_registro:    fin    || null,
        })
        this.herramientaSettings = res.data
        this.$alert.success('Configuración guardada')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.herramientaSaving = false
      }
    },
    formatFecha(val) {
      if (!val) return '-'
      return moment(val).format('DD/MM/YYYY HH:mm')
    },
    abrirUnidades(seleccion) {
      this.modoSeleccionUnidad = seleccion
      this.unidadFilter = ''
      this.inputNombreUnidad = ''
      this.editandoUnidad = null
      this.dialogoUnidades = true
    },
    editarUnidad(u) {
      this.editandoUnidad = { ...u }
      this.inputNombreUnidad = u.nombre
    },
    unidadPut() {
      if (!this.inputNombreUnidad.trim()) return
      this.loading = true
      this.$axios.put('unidades/' + this.editandoUnidad.id, { nombre: this.inputNombreUnidad })
        .then(res => {
          const idx = this.unidades.findIndex(u => u.id === this.editandoUnidad.id)
          if (idx !== -1) this.unidades.splice(idx, 1, res.data)
          this.filteredUnidadesOpts = [...this.unidades]
          this.editandoUnidad = null
          this.inputNombreUnidad = ''
          this.$alert.success('Unidad actualizada')
        })
        .catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al actualizar')
        })
        .finally(() => { this.loading = false })
    },
    seleccionarUnidad(unidad) {
      this.user.unidad_id = unidad.id
      this.dialogoUnidades = false
    },
    unidadPost() {
      if (!this.inputNombreUnidad.trim()) return
      this.loading = true
      this.$axios.post('unidades', { nombre: this.inputNombreUnidad }).then(res => {
        this.unidades.push(res.data)
        this.filteredUnidadesOpts = [...this.unidades]
        this.inputNombreUnidad = ''
        this.$alert.success('Unidad agregada')
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al guardar')
      }).finally(() => { this.loading = false })
    },
    unidadDelete(id) {
      this.$alert.dialog('¿Eliminar esta unidad?').onOk(() => {
        this.$axios.delete('unidades/' + id).then(() => {
          this.unidades = this.unidades.filter(u => u.id !== id)
          this.$alert.success('Unidad eliminada')
        }).catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al eliminar')
        })
      })
    },
    onFileChange(event) {
      const file = event.target.files[0]
      const formData = new FormData()
      formData.append('avatar', file)
      this.loading = true
      this.$axios.post(this.user.id + '/avatar', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }).then(res => {
        this.cambioAvatarDialogo = false
        this.$alert.success('Avatar actualizado')
        this.usersGet()
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    cambiarAvatar(user) {
      this.cambioAvatarDialogo = true
      this.user = {...user}
    },
    // ── Firma ──────────────────────────────────────────────
    abrirFirma(user) {
      this.user = { ...user }
      this.firmaDialogo = true
    },
    firmaInit() {
      this.$nextTick(() => {
        const canvas = this.$refs.firmaCanvas
        if (!canvas) return
        canvas.width  = canvas.offsetWidth  || 500
        canvas.height = canvas.offsetHeight || 200
        const ctx = canvas.getContext('2d')
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, canvas.width, canvas.height)
      })
    },
    firmaClear() {
      const canvas = this.$refs.firmaCanvas
      const ctx = canvas.getContext('2d')
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, 0, canvas.width, canvas.height)
    },
    firmaGetPos(e) {
      const canvas = this.$refs.firmaCanvas
      const rect   = canvas.getBoundingClientRect()
      const scaleX = canvas.width  / rect.width
      const scaleY = canvas.height / rect.height
      const src = e.touches ? e.touches[0] : e
      return {
        x: (src.clientX - rect.left) * scaleX,
        y: (src.clientY - rect.top)  * scaleY,
      }
    },
    firmaStart(e) {
      this.firmaDrawing = true
      const pos = this.firmaGetPos(e)
      this.firmaLastX = pos.x
      this.firmaLastY = pos.y
    },
    firmaMove(e) {
      if (!this.firmaDrawing) return
      const pos = this.firmaGetPos(e)
      const ctx = this.$refs.firmaCanvas.getContext('2d')
      ctx.beginPath()
      ctx.strokeStyle = '#000000'
      ctx.lineWidth   = 2.5
      ctx.lineCap     = 'round'
      ctx.lineJoin    = 'round'
      ctx.moveTo(this.firmaLastX, this.firmaLastY)
      ctx.lineTo(pos.x, pos.y)
      ctx.stroke()
      this.firmaLastX = pos.x
      this.firmaLastY = pos.y
    },
    firmaEnd() {
      this.firmaDrawing = false
    },
    firmaGuardar() {
      const canvas = this.$refs.firmaCanvas
      this.loading = true
      canvas.toBlob(blob => {
        const formData = new FormData()
        formData.append('firma', blob, 'firma.png')
        this.$axios.post(this.user.id + '/firma', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        }).then(() => {
          this.firmaDialogo = false
          this.$alert.success('Firma guardada')
          this.usersGet()
        }).catch(error => {
          this.$alert.error(error.response?.data?.message || 'Error al guardar firma')
        }).finally(() => { this.loading = false })
      }, 'image/png')
    },
    // ── Sello ─────────────────────────────────────────────
    abrirSello(user) {
      this.user = { ...user }
      this.selloDialogo = true
    },
    onSelloChange(event) {
      const file = event.target.files[0]
      if (!file) return
      const formData = new FormData()
      formData.append('sello', file)
      this.loading = true
      this.$axios.post(this.user.id + '/sello', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then(res => {
        this.user.sello = res.data.sello
        this.$alert.success('Sello guardado')
        this.usersGet()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error al guardar sello')
      }).finally(() => { this.loading = false })
    },
    // permisosPost() {
    //   this.loading = true
    //   const permissions = this.permissions.filter(p => p.checked).map(p => p.id)
    //   this.$axios.post('permisos', {
    //     user_id: this.user.id,
    //     permissions
    //   }).then(res => {
    //     this.dialogPermisos = false
    //     this.$alert.success('Permisos actualizados')
    //     this.usersGet()
    //   }).catch(error => {
    //     this.$alert.error(error.response.data.message)
    //   }).finally(() => {
    //     this.loading = false
    //   })
    // },
    // permissionsGet() {
    //   this.$axios.get('permisos').then(res => {
    //     this.permissions = res.data
    //   }).catch(error => {
    //     this.$alert.error(error.response.data.message)
    //   })
    // },
    async userNew() {
      this.user = {
        name: '',
        email: '',
        celular: '',
        password: '',
        area_id: null,
        username: '',
        cargo: '',
        role: 'Usuario',
        unidad_id: this.unidades.find(u => u.nombre.includes('INGENIERÍA'))?.id || null,
        max_pedidos: 1,
      }
      this.actionUser = 'Nuevo'
      this.permFilter = ''
      this.permissions = []
      this.userDialog = true
      this.loading = true
      try {
        const all = await this.$axios.get('permissions').then(r => r.data)
        this.permissions = all.map(p => ({ ...p, checked: false }))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando permisos')
      } finally {
        this.loading = false
      }
    },
    usersGet() {
      this.loading = true
      this.users = []
      this.$axios.get('users').then(res => {
        this.users = res.data
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    gestionGet() {
      this.loading = true
      this.$axios.get('gestiones').then(res => {
        this.gestiones = res.data
        this.loading = false
      }).catch(error => {
        this.$alert.error(error.response.data.message)
        this.loading = false
      })
    },
    async userPost() {
      this.loading = true
      try {
        const res = await this.$axios.post('users', this.user)
        const newUserId = res.data.id
        const ids = this.permissions.filter(p => p.checked).map(p => p.id)
        if (ids.length > 0) {
          await this.$axios.put(`users/${newUserId}/permissions`, { permissions: ids })
        }
        this.userDialog = false
        this.$alert.success('Usuario creado')
        this.usersGet()
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'Error al crear')
      } finally {
        this.loading = false
      }
    },
    async userPut() {
      this.loading = true
      try {
        await this.$axios.put('users/' + this.user.id, this.user)
        const ids = this.permissions.filter(p => p.checked).map(p => p.id)
        await this.$axios.put(`users/${this.user.id}/permissions`, { permissions: ids })
        this.usersGet()
        this.userDialog = false
        this.$alert.success('Usuario actualizado')
      } catch (error) {
        this.$alert.error(error.response?.data?.message || 'Error al actualizar')
      } finally {
        this.loading = false
      }
    },
    async permisosShow(user) {
      this.user = { ...user }
      this.dialogPermisos = true
      this.loading = true
      try {
        // 1) Traer todos los permisos
        const all = await this.$axios.get('permissions').then(r => r.data)
        // 2) Traer permisos del usuario (array de IDs)
        const userPermIds = await this.$axios.get(`users/${user.id}/permissions`).then(r => r.data)

        // 3) Mezclar con checked
        this.permissions = all.map(p => ({
          ...p,
          checked: userPermIds.includes(p.id)
        }))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando permisos')
      } finally {
        this.loading = false
      }
    },

    async permisosPost() {
      this.loading = true
      try {
        const ids = this.permissions.filter(p => p.checked).map(p => p.id)
        await this.$axios.put(`users/${this.user.id}/permissions`, { permissions: ids })
        this.dialogPermisos = false
        this.$alert.success('Permisos actualizados')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.loading = false
      }
    },
    permissionGroupFor(permissionName) {
      if (HOSPITAL_PERMISSION_NAMES.includes(permissionName)) return PERMISSION_GROUPS[0]
      if (ALMACEN_PERMISSION_NAMES.includes(permissionName)) return PERMISSION_GROUPS[2]
      return PERMISSION_GROUPS[1]
    },
    normalizePermissionText(value) {
      return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
    },
    sortPermissionsByGroupOrder(permissions, orderNames) {
      const order = new Map(orderNames.map((name, index) => [name, index]))
      return [...permissions].sort((a, b) => {
        const aIndex = order.has(a.name) ? order.get(a.name) : 999
        const bIndex = order.has(b.name) ? order.get(b.name) : 999
        if (aIndex !== bIndex) return aIndex - bIndex
        return a.name.localeCompare(b.name)
      })
    },
    setGroupPermissions(group, checked) {
      group.permissions.forEach(permission => {
        permission.checked = checked
      })
    },
    userResetPassword(user) {
      this.$alert.dialog(`¿Restablecer la contraseña de "${user.username}" a 123456?`)
        .onOk(() => {
          this.$axios.put('resetPassword/' + user.id)
            .then(() => {
              this.$alert.success(`Contraseña de ${user.username} restablecida a 123456`)
            })
            .catch(error => {
              this.$alert.error(error.response?.data?.message || 'Error al restablecer')
            })
        })
    },
    async userEdit(user) {
      this.user = { ...user }
      this.actionUser = 'Editar'
      this.permFilter = ''
      this.permissions = []
      this.userDialog = true
      this.loading = true
      try {
        const [all, userPermIds] = await Promise.all([
          this.$axios.get('permissions').then(r => r.data),
          this.$axios.get(`users/${user.id}/permissions`).then(r => r.data),
        ])
        this.permissions = all.map(p => ({ ...p, checked: userPermIds.includes(p.id) }))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando permisos')
      } finally {
        this.loading = false
      }
    },
    userDelete(id) {
      this.$alert.dialog('¿Desea eliminar el user?')
        .onOk(() => {
          this.loading = true
          this.$axios.delete('users/' + id).then(res => {
            this.usersGet()
            this.$alert.success('User eliminado')
          }).catch(error => {
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            this.loading = false
          })
        })
    },
  },
  computed: {
    canGestionarTiempoRegistro() {
      const role = this.$store?.user?.role
      const perms = this.$store?.permissions || []
      return role === 'Administrador' || perms.includes('Tiempo creación de usuario')
    },
    subpartidasAgrupadas() {
      const q = (this.subpartidaFilter || '').toLowerCase()
      const map = new Map()
      this.todasSubpartidas.forEach(sp => {
        const p = sp.partida
        if (!p) return
        const nombre = `${sp.codigo} ${sp.nombre}`.toLowerCase()
        if (q && !nombre.includes(q)) return
        if (!map.has(p.id)) {
          map.set(p.id, {
            id: p.id,
            codigo: p.codigo,
            nombre: p.nombre,
            subpartidas: [],
            get tieneSeleccionadas() { return this.subpartidas.some(s => this._sel?.includes(s.id)) },
          })
        }
        map.get(p.id).subpartidas.push(sp)
      })
      const grupos = [...map.values()]
      grupos.forEach(g => { g._sel = this.subpartidasSeleccionadas })
      return grupos
    },
    filteredUnidades() {
      const q = (this.unidadFilter || '').toLowerCase()
      if (!q) return this.unidades
      return this.unidades.filter(u => u.nombre.toLowerCase().includes(q))
    },
    permissionGroups() {
      const groups = PERMISSION_GROUPS.map(group => ({
        ...group,
        permissions: [],
      }))
      const groupMap = new Map(groups.map(group => [group.key, group]))

      this.permissions.forEach(permission => {
        const group = this.permissionGroupFor(permission.name)
        groupMap.get(group.key).permissions.push(permission)
      })

      return groups
        .map(group => ({
          ...group,
          permissions: this.sortPermissionsByGroupOrder(group.permissions, group.names),
        }))
        .filter(group => group.permissions.length > 0)
    },
    filteredPermissionGroups() {
      const filter = this.normalizePermissionText(this.permFilter)

      return this.permissionGroups
        .map(group => {
          const permissions = filter
            ? group.permissions.filter(permission => this.normalizePermissionText(permission.name).includes(filter))
            : group.permissions

          return {
            ...group,
            permissions,
            checkedCount: permissions.filter(permission => permission.checked).length,
          }
        })
        .filter(group => group.permissions.length > 0)
    },
    selectedPermissionsCount() {
      return this.permissions.filter(permission => permission.checked).length
    }
  },
}
</script>

<style scoped>
.user-dialog-card {
  width: min(94vw, 780px);
  max-width: 780px;
  display: flex;
  flex-direction: column;
}

.user-dialog-body {
  overflow-y: auto;
  max-height: calc(90vh - 64px);
  flex: 1;
}

.user-dialog-perms-body {
  max-height: 280px;
  overflow-y: auto;
  background: #f7fafc;
  border-radius: 8px;
  padding: 6px;
}

@media (max-width: 599px) {
  .user-dialog-card {
    width: 100%;
    height: 100%;
    max-width: none;
    border-radius: 0;
  }

  .user-dialog-body {
    max-height: calc(100vh - 64px);
  }

  .user-dialog-perms-body {
    max-height: 220px;
  }
}

.subpartida-card {
  width: min(94vw, 720px);
  max-width: 720px;
}
.subpartida-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 4px;
  padding: 4px 8px;
}
.subpartida-row {
  min-height: 34px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
}
.permission-card {
  width: min(94vw, 780px);
  max-width: 780px;
}

.permission-card__header {
  padding: 12px 14px 8px;
}

.permission-card__title {
  min-width: 0;
  line-height: 1.15;
}

.permission-card__filters {
  padding: 6px 14px 12px;
}

.permission-card__body {
  max-height: min(62vh, 560px);
  overflow: auto;
  padding: 8px 10px;
  background: #f7fafc;
}

.permission-groups {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

:deep(.permission-group-header) {
  min-height: 40px;
  border: 1px solid #dfe7ee;
  border-radius: 8px;
  background: #ffffff;
}

.permission-group-header__icon {
  min-width: 30px;
}

.permission-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 6px;
  padding: 8px 2px 2px;
}

.permission-row {
  min-height: 36px;
  padding: 3px 8px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #ffffff;
}

.permission-row__icon {
  min-width: 28px;
}

.permission-row__label {
  font-size: 12px;
  line-height: 1.15;
  letter-spacing: 0;
}

.permission-empty {
  padding: 24px 8px;
  color: #607d8b;
  text-align: center;
}

.permission-card__actions {
  padding: 10px 14px;
  background: #ffffff;
}

@media (max-width: 599px) {
  .permission-card {
    width: 100%;
    height: 100%;
    max-width: none;
    border-radius: 0;
  }

  .permission-card__body {
    max-height: calc(100vh - 176px);
    padding: 8px;
  }

  .permission-grid {
    grid-template-columns: 1fr;
  }

  .permission-card__actions {
    position: sticky;
    bottom: 0;
    z-index: 1;
  }
}
</style>
