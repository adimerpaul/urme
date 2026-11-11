import { defineStore, acceptHMRUpdate } from 'pinia'

export const useCounterStore = defineStore('counter', {
  state: () => ({
    isLogged: false,
    user: {},
    permissions: [],
    // Interruptores del backend (GET /me → configuracion). Apagados hasta que responda.
    configuracion: {},
  }),

  getters: {
    hasPermission: (state) => (perm) => {
      if (Array.isArray(perm)) return perm.some(p => state.permissions.includes(p))
      return state.permissions.includes(perm)
    },
    perfilesLaboratorio: (state) => state.configuracion.lab_perfiles_habilitado === true,
  },

  actions: {
    logout () {
      this.isLogged = false
      this.user = {}
      this.permissions = []
      this.configuracion = {}
    },
  },
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCounterStore, import.meta.hot))
}
