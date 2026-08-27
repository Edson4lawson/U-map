<template>
  <div class="login-root">
    <!-- Animated background -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-container">
      <div class="login-card text-center">
        <div v-if="loading" class="py-8">
          <Icon icon="ph:spinner-gap-bold" class="spin-icon text-5xl text-blue-500 mx-auto mb-4" />
          <h2 class="text-xl font-semibold text-white">Connexion magique en cours...</h2>
          <p class="text-gray-400 mt-2">Veuillez patienter pendant que nous validons votre lien.</p>
        </div>

        <div v-else-if="error" class="py-8">
          <Icon icon="ph:x-circle-fill" class="text-5xl text-red-500 mx-auto mb-4" />
          <h2 class="text-xl font-semibold text-white">Erreur de connexion</h2>
          <p class="text-red-400 mt-2">{{ error }}</p>
          <router-link to="/login" class="submit-btn mt-6 inline-flex justify-center items-center">
            Retour à la page de connexion
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { useMeta } from '../composables/useMeta'

useMeta('Connexion Magic Link', 'Connexion sécurisée sans mot de passe via lien magique sur U-map.', { canonicalPath: '/magic-link-login' })

const router = useRouter()
const route = useRoute()

const loading = ref(true)
const error = ref('')

onMounted(async () => {
  const token = route.query.token
  if (!token) {
    error.value = 'Token de connexion magique manquant.'
    loading.value = false
    return
  }

  try {
    await authService.loginWithMagicLink(token)
    router.push('/')
  } catch (e) {
    error.value = e.message || 'Lien magique invalide ou expiré.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
@import '../styles/design-system.css';
@import '../styles/animations.css';

.login-root {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  overflow: hidden;
  background-color: #0b0f19;
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: radial-gradient(circle at 50% 50%, black, transparent 80%);
  pointer-events: none;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
  pointer-events: none;
}

.orb-1 {
  top: 10%;
  left: 20%;
  width: 300px;
  height: 300px;
  background: var(--primary, #3b82f6);
}

.orb-2 {
  bottom: 10%;
  right: 20%;
  width: 400px;
  height: 400px;
  background: var(--accent, #a855f7);
}

.login-container {
  width: 100%;
  max-width: 460px;
  position: relative;
  z-index: 10;
}

.login-card {
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
