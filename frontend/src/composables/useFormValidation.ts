import { ref, computed } from 'vue'

export interface ValidationRule {
  required?: boolean
  email?: boolean
  minLength?: number
  maxLength?: number
  pattern?: RegExp
  custom?: (value: string) => string | null
}

export interface FieldValidation {
  value: string
  error: string | null
  touched: boolean
  validate: () => void
  reset: () => void
}

export function useFormValidation() {
  const validateField = (value: string, rules: ValidationRule): string | null => {
    if (rules.required && !value.trim()) {
      return 'Ce champ est requis'
    }

    if (rules.email && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(value)) {
        return 'Adresse email invalide'
      }
    }

    if (rules.minLength && value.length < rules.minLength) {
      return `Minimum ${rules.minLength} caractères requis`
    }

    if (rules.maxLength && value.length > rules.maxLength) {
      return `Maximum ${rules.maxLength} caractères autorisés`
    }

    if (rules.pattern && value) {
      if (!rules.pattern.test(value)) {
        return 'Format invalide'
      }
    }

    if (rules.custom) {
      return rules.custom(value)
    }

    return null
  }

  const createField = (initialValue = '', rules: ValidationRule = {}): FieldValidation => {
    const value = ref(initialValue)
    const error = ref<string | null>(null)
    const touched = ref(false)

    const validate = () => {
      touched.value = true
      error.value = validateField(value.value, rules)
    }

    const reset = () => {
      value.value = initialValue
      error.value = null
      touched.value = false
    }

    return {
      value,
      error,
      touched,
      validate,
      reset
    }
  }

  const validateForm = (fields: FieldValidation[]): boolean => {
    let isValid = true
    fields.forEach(field => {
      field.validate()
      if (field.error) {
        isValid = false
      }
    })
    return isValid
  }

  return {
    validateField,
    createField,
    validateForm
  }
}

// Predefined validation rules
export const validationRules = {
  email: {
    required: true,
    email: true
  },
  password: {
    required: true,
    minLength: 8
  },
  name: {
    required: true,
    minLength: 2
  },
  phone: {
    pattern: /^[0-9+\s-]{8,20}$/
  }
}
