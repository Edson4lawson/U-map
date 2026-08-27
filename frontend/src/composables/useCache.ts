interface CacheItem<T> {
  data: T
  timestamp: number
  ttl: number
}

class CacheManager {
  private cache: Map<string, CacheItem<any>> = new Map()
  private defaultTTL = 5 * 60 * 1000 // 5 minutes default

  set<T>(key: string, data: T, ttl?: number): void {
    const item: CacheItem<T> = {
      data,
      timestamp: Date.now(),
      ttl: ttl || this.defaultTTL
    }
    this.cache.set(key, item)
  }

  get<T>(key: string): T | null {
    const item = this.cache.get(key)
    
    if (!item) {
      return null
    }

    const now = Date.now()
    const isExpired = now - item.timestamp > item.ttl

    if (isExpired) {
      this.cache.delete(key)
      return null
    }

    return item.data as T
  }

  has(key: string): boolean {
    return this.cache.has(key) && this.get(key) !== null
  }

  delete(key: string): boolean {
    return this.cache.delete(key)
  }

  clear(): void {
    this.cache.clear()
  }

  clearExpired(): void {
    const now = Date.now()
    for (const [key, item] of this.cache.entries()) {
      if (now - item.timestamp > item.ttl) {
        this.cache.delete(key)
      }
    }
  }

  size(): number {
    return this.cache.size
  }
}

// Singleton instance
const cacheManager = new CacheManager()

export function useCache() {
  const set = <T>(key: string, data: T, ttl?: number) => {
    cacheManager.set(key, data, ttl)
  }

  const get = <T>(key: string): T | null => {
    return cacheManager.get<T>(key)
  }

  const has = (key: string): boolean => {
    return cacheManager.has(key)
  }

  const remove = (key: string): boolean => {
    return cacheManager.delete(key)
  }

  const clear = (): void => {
    cacheManager.clear()
  }

  const clearExpired = (): void => {
    cacheManager.clearExpired()
  }

  const size = (): number => {
    return cacheManager.size()
  }

  // Cache with fetch wrapper
  const fetch = async <T>(
    key: string,
    fetcher: () => Promise<T>,
    ttl?: number
  ): Promise<T> => {
    const cached = get<T>(key)
    if (cached !== null) {
      return cached
    }

    const data = await fetcher()
    set(key, data, ttl)
    return data
  }

  return {
    set,
    get,
    has,
    remove,
    clear,
    clearExpired,
    size,
    fetch
  }
}

// LocalStorage cache for persistence
export function useLocalStorageCache() {
  const prefix = 'umap_cache_'

  const getKey = (key: string): string => {
    return `${prefix}${key}`
  }

  const set = <T>(key: string, data: T, ttl?: number): void => {
    const item: CacheItem<T> = {
      data,
      timestamp: Date.now(),
      ttl: ttl || 5 * 60 * 1000
    }
    localStorage.setItem(getKey(key), JSON.stringify(item))
  }

  const get = <T>(key: string): T | null => {
    const itemStr = localStorage.getItem(getKey(key))
    if (!itemStr) {
      return null
    }

    try {
      const item: CacheItem<T> = JSON.parse(itemStr)
      const now = Date.now()
      const isExpired = now - item.timestamp > item.ttl

      if (isExpired) {
        localStorage.removeItem(getKey(key))
        return null
      }

      return item.data
    } catch {
      localStorage.removeItem(getKey(key))
      return null
    }
  }

  const has = (key: string): boolean => {
    return get(key) !== null
  }

  const remove = (key: string): boolean => {
    const item = localStorage.getItem(getKey(key))
    localStorage.removeItem(getKey(key))
    return item !== null
  }

  const clear = (): void => {
    Object.keys(localStorage)
      .filter(key => key.startsWith(prefix))
      .forEach(key => localStorage.removeItem(key))
  }

  const clearExpired = (): void => {
    const now = Date.now()
    Object.keys(localStorage)
      .filter(key => key.startsWith(prefix))
      .forEach(key => {
        const itemStr = localStorage.getItem(key)
        if (itemStr) {
          try {
            const item: CacheItem<any> = JSON.parse(itemStr)
            if (now - item.timestamp > item.ttl) {
              localStorage.removeItem(key)
            }
          } catch {
            localStorage.removeItem(key)
          }
        }
      })
  }

  return {
    set,
    get,
    has,
    remove,
    clear,
    clearExpired
  }
}
