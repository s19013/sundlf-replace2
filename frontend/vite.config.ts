import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // --hostと同義
    host: true,
    port: 5173,
    watch: {
      // Dockerボリュームマウント環境でinotifyイベントが伝播しない問題を回避
      usePolling: true,
      interval: 300,
    },
    hmr: {
      // HMR WebSocket接続先を明示
      // host: 'localhost',
      host: '0.0.0.0',
      port: 5173,
    },
  },
})
