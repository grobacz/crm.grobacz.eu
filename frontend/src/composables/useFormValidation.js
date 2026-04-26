import { reactive } from 'vue'

export function useFormValidation(fields, validators) {
  const formErrors = reactive(Object.fromEntries(fields.map((field) => [field, ''])))

  function clearFormErrors() {
    for (const field of fields) {
      formErrors[field] = ''
    }
  }

  function setFieldError(field, message) {
    formErrors[field] = message
  }

  function validateField(field) {
    const validator = validators[field]
    const message = validator ? validator() : ''
    setFieldError(field, message)

    return message === ''
  }

  function validateForm() {
    return fields.every((field) => validateField(field))
  }

  function applyServerFieldErrors(errors) {
    for (const field of fields) {
      setFieldError(field, errors[field] || '')
    }
  }

  function handleFieldInput(field, customCallback = null) {
    if (typeof customCallback === 'function') {
      customCallback()
      return
    }

    if (formErrors[field]) {
      validateField(field)
    }
  }

  function handleFieldBlur(field, customCallback = null) {
    if (typeof customCallback === 'function') {
      customCallback()
      return
    }

    validateField(field)
  }

  return {
    formErrors,
    clearFormErrors,
    validateField,
    validateForm,
    applyServerFieldErrors,
    handleFieldInput,
    handleFieldBlur
  }
}

export function getGraphqlErrorMessage(error, fallbackMessage) {
  return error?.response?.errors?.[0]?.message || fallbackMessage
}

export function getGraphqlFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors || {}
}

export function normalizeEmail(value) {
  return value.trim().toLowerCase()
}
