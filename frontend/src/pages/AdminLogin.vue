<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Animated Background Orbs -->
    <div class="absolute top-[-20%] left-[-10%] w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl animate-pulse-slow"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 1.5s"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[300px] bg-cyan-500/5 rounded-full blur-3xl animate-float"></div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-md">
      
      <!-- Logo & Brand -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-2xl shadow-blue-500/30 mb-4 transform hover:scale-110 transition-transform duration-300">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-display font-bold text-white tracking-tight">U-Map Admin</h1>
        <p class="text-blue-300/60 text-sm mt-2">Panneau d'administration sécurisé</p>
      </div>

      <!-- Card -->
      <div class="bg-white/[0.04] backdrop-blur-2xl border border-white/[0.08] rounded-3xl p-8 shadow-2xl">
        
        <!-- Error Alert -->
        <div v-if="error" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
            <Icon icon="ph:warning-bold" class="w-4 h-4 text-red-400" />
          </div>
          <p class="text-red-300 text-sm">{{ error }}</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          
          <!-- Password Input -->
          <div>
            <label class="block text-xs font-semibold text-blue-300/50 uppercase tracking-widest mb-2">Mot de passe administrateur</label>
            <div class="relative group">
              <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-2xl blur opacity-0 group-focus-within:opacity-100 transition-opacity duration-500"></div>
              <div class="relative flex items-center">
                <Icon icon="ph:lock-simple-bold" class="absolute left-4 w-5 h-5 text-blue-400/40 group-focus-within:text-blue-400 transition-colors" />
                <input 
                  v-model="password" 
                  :type="showPassword ? 'text' : 'password'" 
                  required 
                  autocomplete="current-password"
                  placeholder="••••••••••••"
                  class="w-full bg-white/[0.04] border border-white/[0.08] rounded-2xl py-4 pl-12 pr-12 text-white placeholder-white/20 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/30 outline-none transition-all duration-300 text-sm"
                />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-4 text-white/30 hover:text-white/60 transition-colors">
                  <Icon :icon="showPassword ? 'ph:eye-slash' : 'ph:eye'" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <button 
            type="submit" 
            :disabled="loading"
            class="w-full relative group overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 rounded-2xl transition-all duration-300 shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="relative z-10 flex items-center justify-center gap-2">
              <Icon v-if="loading" icon="ph:spinner-gap-bold" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="ph:sign-in-bold" class="w-5 h-5" />
              {{ loading ? 'Connexion...' : 'Accéder au Dashboard' }}
            </span>
            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
          </button>
        </form>
      </div>

      <!-- Footer -->
      <div class="text-center mt-8">
        <router-link to="/" class="text-blue-400/40 hover:text-blue-400 text-xs transition-colors inline-flex items-center gap-1">
          <Icon icon="ph:arrow-left" class="w-3 h-3" />
          Retour à l'application
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { adminService } from '../services/adminService'

const router = useRouter()
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true
  try {
    await adminService.login(password.value)
    router.push('/admin/dashboard')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>
