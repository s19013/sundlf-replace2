/// <reference types="vite/client" />

// このパッケージにはtypescript用の型がない。なので手動で定義するしかない。
declare module '@jamescoyle/vue-icon' {
  import type { DefineComponent } from 'vue'

  const SvgIcon: DefineComponent<{
    type?: string
    path: string
    size?: string | number
    viewbox?: string
    flip?: 'horizontal' | 'vertical' | 'both' | 'none'
    rotate?: number
  }>

  export default SvgIcon
}

// こちらも同様
declare module '@/app/ziggy' {
  import type { Config } from 'ziggy-js'
  const Ziggy: Config
  export { Ziggy }
}
