<template>
  <div class="fixed inset-0 w-full h-full -z-50 overflow-hidden">
    <video 
      autoplay 
      loop 
      muted 
      playsinline 
      class="absolute top-0 left-0 w-full h-full object-cover transition-opacity duration-1000"
      style="will-change: transform; transform: translateZ(0);"
    >
      <source :src="videoSrc" type="video/mp4">
      Your browser does not support the video tag.
    </video>
    <div class="absolute inset-0 bg-black/50"></div>
  </div>
</template>

<script setup>
import videoSrc from '../assets/Home-vi.mov'
</script>
