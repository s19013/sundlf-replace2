import './styles/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import SvgIcon from '@jamescoyle/vue-icon'
import PrimeVue from 'primevue/config'

// env.d.tsの呼び出し方に合わせないと型エラーが出る
import { Ziggy } from '@/shared/api/ziggy'
import { ZiggyVue } from 'ziggy-js'

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
    options: {
      darkModeSelector: '.my-app-dark',
    },
  },
})
app.use(ZiggyVue, Ziggy)

// 一部コンポーネントをグローバルに登録しどこからでも使えるようにする
app.component('SvgIcon', SvgIcon)

app.mount('#app')
