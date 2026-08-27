import { ref, computed } from 'vue'

export interface PaginationOptions {
  page?: number
  perPage?: number
  total?: number
}

export function usePagination(options: PaginationOptions = {}) {
  const page = ref(options.page || 1)
  const perPage = ref(options.perPage || 10)
  const total = ref(options.total || 0)

  const totalPages = computed(() => Math.ceil(total.value / perPage.value))
  const hasNextPage = computed(() => page.value < totalPages.value)
  const hasPrevPage = computed(() => page.value > 1)
  const from = computed(() => (page.value - 1) * perPage.value + 1)
  const to = computed(() => Math.min(page.value * perPage.value, total.value))

  const setPage = (newPage: number) => {
    if (newPage >= 1 && newPage <= totalPages.value) {
      page.value = newPage
    }
  }

  const nextPage = () => {
    if (hasNextPage.value) {
      page.value++
    }
  }

  const prevPage = () => {
    if (hasPrevPage.value) {
      page.value--
    }
  }

  const firstPage = () => {
    page.value = 1
  }

  const lastPage = () => {
    page.value = totalPages.value
  }

  const setPerPage = (newPerPage: number) => {
    perPage.value = newPerPage
    page.value = 1 // Reset to first page when changing per page
  }

  const setTotal = (newTotal: number) => {
    total.value = newTotal
    // Adjust current page if it's beyond the new total pages
    if (page.value > totalPages.value) {
      page.value = Math.max(1, totalPages.value)
    }
  }

  const reset = () => {
    page.value = 1
    perPage.value = options.perPage || 10
  }

  // Get visible page numbers for pagination controls
  const visiblePages = computed(() => {
    const pages: number[] = []
    const delta = 2 // Number of pages to show on each side of current page

    for (let i = Math.max(2, page.value - delta); i <= Math.min(totalPages.value - 1, page.value + delta); i++) {
      pages.push(i)
    }

    // Always show first page
    if (page.value > delta + 2) {
      pages.unshift(1)
      if (page.value > delta + 3) {
        pages.splice(1, 0, -1) // -1 represents ellipsis
      }
    }

    // Always show last page
    if (page.value < totalPages.value - delta - 1) {
      pages.push(totalPages.value)
      if (page.value < totalPages.value - delta - 2) {
        pages.splice(pages.length - 1, 0, -1) // -1 represents ellipsis
      }
    }

    return pages
  })

  return {
    page,
    perPage,
    total,
    totalPages,
    hasNextPage,
    hasPrevPage,
    from,
    to,
    visiblePages,
    setPage,
    nextPage,
    prevPage,
    firstPage,
    lastPage,
    setPerPage,
    setTotal,
    reset
  }
}
