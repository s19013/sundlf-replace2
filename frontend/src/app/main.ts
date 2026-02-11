import './styles/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import PrimeVue from 'primevue/config'
// まだどれが良いか決めかねるから一旦全部出しとく
import Aura from '@primeuix/themes/aura'
// import Material from '@primeuix/themes/material'
// import Lara from '@primeuix/themes/lara'
// import Nora from '@primeuix/themes/nora'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
  },
})

app.mount('#app')
