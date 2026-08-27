import { ref, onMounted, onUnmounted } from 'vue'

export function useLazyLoad() {
  const isLoading = ref(false)
  const observer = ref<IntersectionObserver | null>(null)

  const observe = (element: HTMLElement, callback: () => void) => {
    if (!element) return

    observer.value = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isLoading.value = true
            callback()
            observer.value?.unobserve(element)
          }
        })
      },
      {
        rootMargin: '50px',
        threshold: 0.1
      }
    )

    observer.value.observe(element)
  }

  const unobserve = (element: HTMLElement) => {
    if (observer.value && element) {
      observer.value.unobserve(element)
    }
  }

  onUnmounted(() => {
    if (observer.value) {
      observer.value.disconnect()
    }
  })

  return {
    isLoading,
    observe,
    unobserve
  }
}

// Image lazy loading directive
export const lazyLoadDirective = {
  mounted(el: HTMLImageElement, binding: any) {
    const imageUrl = binding.value
    
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = new Image()
            img.onload = () => {
              el.src = imageUrl
              el.classList.remove('opacity-0')
              el.classList.add('opacity-100')
            }
            img.src = imageUrl
            observer.unobserve(el)
          }
        })
      },
      {
        rootMargin: '50px',
        threshold: 0.1
      }
    )

    observer.observe(el)
    
    // Store observer for cleanup
    el._lazyLoadObserver = observer
  },
  unmounted(el: HTMLImageElement) {
    if (el._lazyLoadObserver) {
      el._lazyLoadObserver.disconnect()
    }
  }
}
