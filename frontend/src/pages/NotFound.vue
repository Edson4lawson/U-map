<template>
  <div class="min-h-screen flex flex-col justify-center items-center px-4 relative overflow-hidden bg-slate-950 text-white">
    <!-- Orbital background glow rings -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary/20 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute -bottom-45 -right-40 w-96 h-96 bg-secondary/20 rounded-full blur-3xl animate-pulse"></div>

    <div class="max-w-md w-full text-center relative z-10 space-y-8" data-aos="zoom-in">
      <!-- 404 Visual Art -->
      <div class="relative flex justify-center items-center">
        <h1 class="text-[120px] font-black tracking-tighter leading-none bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600 bg-clip-text text-transparent opacity-85 select-none select-none">
          404
        </h1>
        <!-- Floating locator pin icon -->
        <div class="absolute w-16 h-16 bg-gradient-to-tr from-secondary to-pink-500 rounded-2xl flex items-center justify-center shadow-lg shadow-pink-500/30 transform rotate-12 -translate-y-8 animate-bounce">
          <Icon icon="ph:map-pin-bold" class="w-8 h-8 text-white" />
        </div>
      </div>

      <!-- Main Messages -->
      <div class="space-y-3">
        <h2 class="text-2xl font-bold tracking-tight">Oups ! Lieu introuvable</h2>
        <p class="text-slate-400 text-sm leading-relaxed max-w-sm mx-auto">
          Il semblerait que vous vous soyez égaré en dehors des limites du campus de l'UAC. Ce bâtiment ou cette salle n'existe pas ou a été déplacée.
        </p>
      </div>

      <!-- Quick Action Buttons -->
      <div class="flex flex-col gap-3 max-w-xs mx-auto">
        <router-link to="/map" class="btn-primary flex items-center justify-center gap-2 py-3.5 shadow-lg shadow-blue-500/20 active:scale-95 transition-all">
          <Icon icon="ph:map-trifold-bold" class="w-5 h-5" />
          <span>Explorer la carte U-Map</span>
        </router-link>
        
        <router-link to="/" class="py-3 px-6 bg-white/5 border border-white/10 hover:bg-white/10 text-white rounded-2xl font-semibold text-sm active:scale-95 transition-all flex items-center justify-center gap-2">
          <Icon icon="ph:house-bold" class="w-5 h-5" />
          <span>Retour à l'accueil</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { useMeta } from '../composables/useMeta'

useMeta('Page introuvable', "La page demandée n'existe pas sur le guide U-map de l'UAC.")
</script>

<style scoped>
.scale-in {
  animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scaleIn {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
