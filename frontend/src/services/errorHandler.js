import { createApp, h } from 'vue'
import ErrorHandler from '../components/ErrorHandler.vue'

let errorHandlerInstance = null

const show = (options) => {
  if (errorHandlerInstance) {
    errorHandlerInstance.unmount()
    errorHandlerInstance = null
  }

  const container = document.createElement('div')
  document.body.appendChild(container)

  const app = createApp({
    render() {
      return h(ErrorHandler, {
        ...options,
        onClose: () => {
          options.onClose?.()
          app.unmount()
          document.body.removeChild(container)
          errorHandlerInstance = null
        },
        onConfirm: () => {
          options.onConfirm?.()
          if (!options.showCancel) {
            app.unmount()
            document.body.removeChild(container)
            errorHandlerInstance = null
          }
        },
        onCancel: () => {
          options.onCancel?.()
          app.unmount()
          document.body.removeChild(container)
          errorHandlerInstance = null
        }
      })
    }
  })

  errorHandlerInstance = app
  app.mount(container)
}

const error = (message, title = 'Erreur') => {
  show({
    show: true,
    type: 'error',
    title,
    message,
    confirmText: 'OK'
  })
}

const success = (message, title = 'Succès') => {
  show({
    show: true,
    type: 'success',
    title,
    message,
    confirmText: 'OK'
  })
}

const warning = (message, title = 'Attention') => {
  show({
    show: true,
    type: 'warning',
    title,
    message,
    confirmText: 'OK'
  })
}

const info = (message, title = 'Information') => {
  show({
    show: true,
    type: 'info',
    title,
    message,
    confirmText: 'OK'
  })
}

const confirm = (message, title = 'Confirmation', onConfirm, onCancel) => {
  show({
    show: true,
    type: 'warning',
    title,
    message,
    confirmText: 'Confirmer',
    cancelText: 'Annuler',
    showCancel: true,
    onConfirm,
    onCancel
  })
}

export default {
  show,
  error,
  success,
  warning,
  info,
  confirm
}
