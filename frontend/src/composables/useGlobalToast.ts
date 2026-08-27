import { useToast } from './useToast'

// Singleton instance for global toast management
let globalToastInstance: ReturnType<typeof useToast> | null = null

export function useGlobalToast() {
  if (!globalToastInstance) {
    globalToastInstance = useToast()
  }
  return globalToastInstance
}
