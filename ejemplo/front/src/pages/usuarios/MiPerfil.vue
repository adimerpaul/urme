<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row justify-center q-col-gutter-md">

      <!-- Datos personales -->
      <div class="col-12 col-md-5">
        <q-card>
          <q-card-section class="q-pb-sm">
            <div class="row items-center">
              <q-icon name="manage_accounts" size="20px" class="text-primary q-mr-sm"/>
              <span class="text-subtitle1 text-weight-bold">Mis datos</span>
            </div>
          </q-card-section>

          <q-card-section class="text-center q-pt-none q-pb-sm">
            <div style="position: relative; display: inline-block;">
              <q-avatar rounded size="86px" style="border: 2px solid #e0e0e0; background: #f5f5f5;">
                <q-img v-if="form.avatar" :src="avatarUrl" ratio="1" fit="cover"/>
                <q-icon v-else name="person" size="56px" color="grey-5"/>
              </q-avatar>
              <q-btn round dense flat icon="photo_camera" color="primary"
                     class="absolute" style="bottom:-6px;right:-6px;background:white;border:1px solid #e0e0e0;"
                     @click="$refs.avatarInput.click()">
                <q-tooltip>Cambiar foto</q-tooltip>
              </q-btn>
              <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange"/>
            </div>
            <div class="text-caption text-grey-6 q-mt-xs">{{ $store.user.username }}</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-form @submit.prevent="guardar">
              <div class="row q-col-gutter-sm">
                <div class="col-12">
                  <q-input v-model="form.name" label="Nombre completo" dense outlined
                           :rules="[v => !!v || 'Campo requerido']"/>
                </div>
                <div class="col-12">
                  <q-input v-model="form.email" label="Email" dense outlined type="email"/>
                </div>
                <div class="col-6">
                  <q-input v-model="form.celular" label="Celular" dense outlined/>
                </div>
                <div class="col-6">
                  <q-input v-model="form.ci" label="CI" dense outlined/>
                </div>
                <div class="col-6">
                  <q-checkbox v-model="form.mostrar_firma" label="Mostrar firma" dense/>
                </div>
                <div class="col-6">
                  <q-checkbox v-model="form.mostrar_sello" label="Mostrar sello" dense/>
                </div>
              </div>
              <div class="text-right q-mt-sm">
                <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loadingDatos" icon="save"/>
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- Firma y Sello -->
      <div class="col-12 col-md-5">

        <!-- Firma -->
        <q-card class="q-mb-md">
          <q-card-section class="q-pb-sm">
            <div class="row items-center">
              <q-icon name="draw" size="20px" class="text-indigo q-mr-sm"/>
              <span class="text-subtitle1 text-weight-bold">Mi firma</span>
              <q-space/>
              <q-btn v-if="!firmaAbierta" dense no-caps unelevated color="indigo" icon="draw"
                     label="Firmar" @click="abrirFirma"/>
              <q-btn v-else dense flat no-caps color="grey-7" icon="close" label="Cerrar"
                     @click="firmaAbierta = false"/>
            </div>
          </q-card-section>

          <!-- Vista firma actual -->
          <q-card-section v-if="form.firma && !firmaAbierta" class="q-pt-none text-center">
            <div class="text-caption text-grey-6 q-mb-xs">Firma actual</div>
            <img :src="firmaUrl" style="max-height:80px;max-width:100%;border:1px solid #eee;border-radius:6px;"/>
            <div class="q-mt-xs">
              <q-btn dense flat no-caps color="indigo" icon="draw" label="Redibujar" @click="abrirFirma"/>
            </div>
          </q-card-section>

          <q-card-section v-if="!form.firma && !firmaAbierta" class="q-pt-none text-center text-grey-5 q-pb-sm">
            <q-icon name="draw" size="32px"/>
            <div class="text-caption">Sin firma. Presiona "Firmar" para dibujarla.</div>
          </q-card-section>

          <!-- Canvas de firma -->
          <template v-if="firmaAbierta">
            <q-card-section class="q-pt-none q-pb-xs">
              <div class="text-caption text-grey-6 q-mb-xs">Dibuja tu firma (funciona con dedo en celular)</div>
              <div style="border:2px solid #90caf9;border-radius:6px;background:#fff;cursor:crosshair;touch-action:none;overflow:hidden;">
                <canvas
                  ref="firmaCanvas"
                  style="width:100%;display:block;height:160px;"
                  @mousedown="firmaStart"
                  @mousemove="firmaMove"
                  @mouseup="firmaEnd"
                  @mouseleave="firmaEnd"
                  @touchstart.prevent="firmaStart"
                  @touchmove.prevent="firmaMove"
                  @touchend="firmaEnd"
                />
              </div>
            </q-card-section>
            <q-card-actions class="row justify-between q-px-md q-pb-md">
              <q-btn label="Limpiar" icon="refresh" flat color="grey-7" @click="firmaClear" no-caps dense/>
              <q-btn label="Guardar firma" icon="save" color="indigo" @click="firmaGuardar"
                     no-caps dense :loading="loadingFirma"/>
            </q-card-actions>
          </template>
        </q-card>

        <!-- Sello -->
        <q-card>
          <q-card-section class="q-pb-sm">
            <div class="row items-center">
              <q-icon name="approval" size="20px" class="text-teal q-mr-sm"/>
              <span class="text-subtitle1 text-weight-bold">Mi sello</span>
              <q-space/>
              <q-btn dense no-caps unelevated :color="form.sello ? 'teal' : 'teal'" icon="upload"
                     :label="form.sello ? 'Cambiar sello' : 'Subir sello'"
                     @click="$refs.selloInput.click()"/>
              <input ref="selloInput" type="file" accept="image/*" class="hidden" @change="onSelloChange"/>
            </div>
          </q-card-section>

          <q-card-section class="q-pt-none text-center">
            <template v-if="form.sello">
              <img :src="selloUrl" style="max-height:100px;max-width:100%;object-fit:contain;border:1px solid #eee;border-radius:6px;"/>
            </template>
            <template v-else>
              <q-icon name="approval" size="40px" color="grey-4"/>
              <div class="text-caption text-grey-5 q-mt-xs">Sin sello. Presiona "Subir sello" para agregar uno.</div>
            </template>
            <q-spinner-dots v-if="loadingSello" color="teal" size="28px" class="q-mt-sm"/>
          </q-card-section>
        </q-card>

      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'MiPerfilPage',
  data() {
    return {
      loadingDatos: false,
      loadingFirma: false,
      loadingSello: false,
      firmaAbierta: false,
      firmaDrawing: false,
      firmaLastX: 0,
      firmaLastY: 0,
      form: {
        name: '',
        email: '',
        celular: '',
        ci: '',
        mostrar_firma: false,
        mostrar_sello: false,
        avatar: '',
        firma: '',
        sello: '',
      },
    }
  },
  computed: {
    avatarUrl() { return `${this.$url}/../images/${this.form.avatar}` },
    firmaUrl()  { return `${this.$url}/../images/${this.form.firma}` },
    selloUrl()  { return `${this.$url}/../images/${this.form.sello}` },
  },
  mounted() {
    const u = this.$store.user
    this.form = {
      name:          u.name          || '',
      email:         u.email         || '',
      celular:       u.celular       || '',
      ci:            u.ci            || '',
      mostrar_firma: !!u.mostrar_firma,
      mostrar_sello: !!u.mostrar_sello,
      avatar:        u.avatar        || '',
      firma:         u.firma         || '',
      sello:         u.sello         || '',
    }
  },
  methods: {
    async guardar() {
      this.loadingDatos = true
      try {
        const res = await this.$axios.put('perfil', {
          name:          this.form.name,
          email:         this.form.email,
          celular:       this.form.celular,
          ci:            this.form.ci,
          mostrar_firma: this.form.mostrar_firma,
          mostrar_sello: this.form.mostrar_sello,
        })
        this.$store.user = { ...this.$store.user, ...res.data }
        this.$alert.success('Perfil actualizado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al guardar')
      } finally {
        this.loadingDatos = false
      }
    },

    // ── Avatar ───────────────────────────────────────────────
    onAvatarChange(e) {
      const file = e.target.files[0]
      if (!file) return
      const fd = new FormData()
      fd.append('avatar', file)
      this.loadingDatos = true
      this.$axios.post(`${this.$store.user.id}/avatar`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      }).then(res => {
        this.form.avatar = res.data.avatar
        this.$store.user = { ...this.$store.user, avatar: res.data.avatar }
        this.$alert.success('Foto actualizada')
      }).catch(e => {
        this.$alert.error(e.response?.data?.message || 'Error al subir foto')
      }).finally(() => { this.loadingDatos = false; e.target.value = '' })
    },

    // ── Firma ────────────────────────────────────────────────
    abrirFirma() {
      this.firmaAbierta = true
      this.$nextTick(() => {
        const canvas = this.$refs.firmaCanvas
        if (!canvas) return
        canvas.width  = canvas.offsetWidth  || 400
        canvas.height = canvas.offsetHeight || 160
        const ctx = canvas.getContext('2d')
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, canvas.width, canvas.height)
      })
    },
    firmaClear() {
      const canvas = this.$refs.firmaCanvas
      if (!canvas) return
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
      const p = this.firmaGetPos(e)
      this.firmaLastX = p.x
      this.firmaLastY = p.y
    },
    firmaMove(e) {
      if (!this.firmaDrawing) return
      const p   = this.firmaGetPos(e)
      const ctx = this.$refs.firmaCanvas.getContext('2d')
      ctx.beginPath()
      ctx.strokeStyle = '#000000'
      ctx.lineWidth   = 2.5
      ctx.lineCap     = 'round'
      ctx.lineJoin    = 'round'
      ctx.moveTo(this.firmaLastX, this.firmaLastY)
      ctx.lineTo(p.x, p.y)
      ctx.stroke()
      this.firmaLastX = p.x
      this.firmaLastY = p.y
    },
    firmaEnd() { this.firmaDrawing = false },
    firmaGuardar() {
      const canvas = this.$refs.firmaCanvas
      this.loadingFirma = true
      canvas.toBlob(blob => {
        const fd = new FormData()
        fd.append('firma', blob, 'firma.png')
        this.$axios.post(`${this.$store.user.id}/firma`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        }).then(res => {
          this.form.firma = res.data.firma
          this.$store.user = { ...this.$store.user, firma: res.data.firma }
          this.firmaAbierta = false
          this.$alert.success('Firma guardada')
        }).catch(e => {
          this.$alert.error(e.response?.data?.message || 'Error al guardar firma')
        }).finally(() => { this.loadingFirma = false })
      }, 'image/png')
    },

    // ── Sello ────────────────────────────────────────────────
    onSelloChange(e) {
      const file = e.target.files[0]
      if (!file) return
      const fd = new FormData()
      fd.append('sello', file)
      this.loadingSello = true
      this.$axios.post(`${this.$store.user.id}/sello`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      }).then(res => {
        this.form.sello = res.data.sello
        this.$store.user = { ...this.$store.user, sello: res.data.sello }
        this.$alert.success('Sello guardado')
      }).catch(e => {
        this.$alert.error(e.response?.data?.message || 'Error al subir sello')
      }).finally(() => { this.loadingSello = false; e.target.value = '' })
    },
  },
}
</script>
