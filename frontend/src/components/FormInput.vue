<template>
  <div class="mb-4">
    <label 
      v-if="label" 
      :for="id"
      class="block text-sm font-medium mb-2"
      :class="labelClass"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="handleInput"
        @blur="handleBlur"
        class="w-full px-4 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2"
        :class="inputClasses"
      />
      <span v-if="icon" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
        <component :is="icon" class="w-5 h-5" />
      </span>
    </div>
    <p v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Mail, Lock, User, MapPin, Phone } from '@lucide/vue'

interface Props {
  id?: string
  label?: string
  type?: 'text' | 'email' | 'password' | 'tel'
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  required?: boolean
  error?: string
  hint?: string
  icon?: 'mail' | 'lock' | 'user' | 'mapPin' | 'phone'
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  required: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: []
}>()

const iconComponents = {
  mail: Mail,
  lock: Lock,
  user: User,
  mapPin: MapPin,
  phone: Phone
}

const icon = computed(() => props.icon ? iconComponents[props.icon] : null)

const inputClasses = computed(() => {
  const base = 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100'
  const focus = 'focus:border-blue-500 dark:focus:border-blue-400 focus:ring-blue-500/20'
  const error = props.error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''
  const disabled = props.disabled ? 'opacity-50 cursor-not-allowed' : ''
  
  return `${base} ${focus} ${error} ${disabled}`
})

const labelClass = computed(() => {
  return 'text-gray-700 dark:text-gray-300'
})

const handleInput = (e: Event) => {
  const target = e.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const handleBlur = () => {
  emit('blur')
}
</script>
