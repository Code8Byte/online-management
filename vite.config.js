import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/lkh_sortir', // adjust according to local setup if testing
        changeOrigin: true
      }
    }
  }
})
