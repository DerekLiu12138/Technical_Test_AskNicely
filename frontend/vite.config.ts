import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: true,
    port: 5173,
    // Allow connections from these hosts
    allowedHosts: ['localhost', '127.0.0.1', 'nginx', 'frontend']
  }
})