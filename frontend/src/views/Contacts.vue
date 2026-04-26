<template>
  <div class="contacts-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Contact Management</p>
        <h1 class="page-title">Manage customer contacts</h1>
        <p class="page-subtitle">
          Link contacts to customers, mark primary representatives, and keep communication details current.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Total contacts</span>
          <strong class="stat-value">{{ contacts.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" @click="loadContacts" :disabled="isLoading">
          {{ isLoading ? 'Refreshing...' : 'Refresh list' }}
        </button>
      </div>
    </section>

    <div v-if="loadError" class="alert alert-error">
      {{ loadError }}
    </div>

    <div class="content-grid">
      <section class="panel form-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">{{ isEditingContact ? 'Edit contact' : 'New contact' }}</p>
            <h2>{{ isEditingContact ? 'Update record' : 'Create record' }}</h2>
          </div>
          <span class="panel-note">
            {{ isEditingContact ? 'Update contact details without changing the linked customer.' : 'Every contact must belong to a customer record.' }}
          </span>
        </div>

        <div class="form-insights">
          <div class="insight-card">
            <span class="insight-label">With email</span>
            <strong class="insight-value">{{ contactsWithEmail }}</strong>
          </div>
          <div class="insight-card">
            <span class="insight-label">Primary</span>
            <strong class="insight-value">{{ primaryContactsCount }}</strong>
          </div>
        </div>

        <form class="contact-form" novalidate @submit.prevent="submitContact">
          <div class="field-grid">
            <label class="field field-full" :class="{ 'field-has-error': formErrors.name }">
              <span>Name *</span>
              <input
                v-model.trim="form.name"
                :class="{ 'input-invalid': formErrors.name }"
                type="text"
                name="name"
                placeholder="Jane Doe"
                maxlength="255"
                required
                @input="handleFieldInput('name')"
                @blur="handleFieldBlur('name')"
              >
              <small v-if="formErrors.name" class="field-error">{{ formErrors.name }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.email }">
              <span>Email</span>
              <input
                v-model.trim="form.email"
                :class="{ 'input-invalid': formErrors.email }"
                type="email"
                name="email"
                placeholder="jane@example.com"
                maxlength="255"
                @input="handleFieldInput('email')"
                @blur="handleFieldBlur('email')"
              >
              <small v-if="formErrors.email" class="field-error">{{ formErrors.email }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.phone }">
              <span>Phone</span>
              <input
                v-model.trim="form.phone"
                :class="{ 'input-invalid': formErrors.phone }"
                type="text"
                name="phone"
                placeholder="+48 555 111 222"
                maxlength="20"
                @input="handleFieldInput('phone')"
                @blur="handleFieldBlur('phone')"
              >
              <small v-if="formErrors.phone" class="field-error">{{ formErrors.phone }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.title }">
              <span>Job title</span>
              <input
                v-model.trim="form.title"
                :class="{ 'input-invalid': formErrors.title }"
                type="text"
                name="title"
                placeholder="Sales Director"
                maxlength="255"
                @input="handleFieldInput('title')"
                @blur="handleFieldBlur('title')"
              >
              <small v-if="formErrors.title" class="field-error">{{ formErrors.title }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.customerId }">
              <span>Customer *</span>
              <select
                v-model="form.customerId"
                :class="{ 'input-invalid': formErrors.customerId }"
                name="customerId"
                :disabled="isEditingContact"
                @change="handleFieldInput('customerId')"
              >
                <option value="">Select a customer</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
              </select>
              <small v-if="formErrors.customerId" class="field-error">{{ formErrors.customerId }}</small>
            </label>

            <label class="field field-checkbox">
              <input
                v-model="form.isPrimary"
                type="checkbox"
                name="isPrimary"
              >
              <span>Primary contact</span>
            </label>
          </div>

          <div v-if="submitError" class="alert alert-error">
            {{ submitError }}
          </div>

          <div v-if="submitSuccess" class="alert alert-success">
            {{ submitSuccess }}
          </div>

          <div class="form-actions">
            <p class="form-helper">
              {{ isEditingContact ? 'Save changes to keep contact details accurate.' : 'Create a contact and assign it to the relevant customer.' }}
            </p>
            <div class="form-action-buttons">
              <button
                v-if="isEditingContact"
                class="btn btn-tertiary"
                type="button"
                :disabled="isSubmitting"
                @click="cancelEditing"
              >
                Cancel
              </button>
              <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? (isEditingContact ? 'Saving changes...' : 'Saving contact...') : (isEditingContact ? 'Save contact' : 'Add contact') }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Contact list</p>
            <h2>All contacts</h2>
          </div>
          <span class="panel-note">
            {{ isLoading ? 'Loading from CRM...' : `${filteredContacts.length} visible record${filteredContacts.length === 1 ? '' : 's'}` }}
          </span>
        </div>

        <div v-if="statusUpdateError" class="alert alert-error">
          {{ statusUpdateError }}
        </div>

        <div class="list-toolbar">
          <label class="search-field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
            <input
              v-model.trim="searchTerm"
              type="text"
              name="contact-search"
              placeholder="Search name, email, phone, or customer"
            >
          </label>

          <div class="toolbar-pills">
            <span class="toolbar-pill">With email {{ contactsWithEmail }}</span>
            <span class="toolbar-pill">With phone {{ contactsWithPhone }}</span>
            <span class="toolbar-pill">Primary {{ primaryContactsCount }}</span>
          </div>
        </div>

        <div v-if="isLoading" class="list-state">
          Loading contacts...
        </div>

        <div v-else-if="contacts.length === 0" class="list-state empty-state">
          <strong>No contacts yet.</strong>
          <span>Create the first contact using the form on the left.</span>
        </div>

        <div v-else-if="filteredContacts.length === 0" class="list-state empty-state">
          <strong>No matching contacts.</strong>
          <span>Try a broader search to see the full contact list.</span>
        </div>

        <div v-else class="contact-list">
          <article v-for="contact in filteredContacts" :key="contact.id" class="contact-card">
            <div class="contact-card-head">
              <div class="contact-identity">
                <div class="contact-avatar">{{ getContactInitials(contact) }}</div>
                <div class="contact-copy">
                  <div class="contact-title-row">
                    <h3>{{ contact.name }}</h3>
                    <span v-if="contact.isPrimary" class="primary-badge">Primary</span>
                    <span class="customer-badge">{{ contact.customer?.name || 'Unknown customer' }}</span>
                  </div>
                  <p class="contact-subtitle">
                    <span v-if="contact.email">{{ contact.email }}</span>
                    <span v-if="contact.email && contact.phone" class="contact-divider">•</span>
                    <span>{{ contact.phone || 'No phone on file' }}</span>
                  </p>
                  <p v-if="contact.title" class="contact-title-line">{{ contact.title }}</p>
                </div>
              </div>
              <div class="contact-date">{{ formatCreatedAt(contact.createdAt) }}</div>
            </div>

            <div class="contact-actions">
              <span class="engagement-pill" :class="`engagement-${contact.isPrimary ? 'success' : 'neutral'}`">
                {{ contact.isPrimary ? 'Primary contact' : 'Regular contact' }}
              </span>

              <div class="contact-control-groups">
                <button
                  type="button"
                  class="edit-contact-button"
                  :class="{ 'edit-contact-active': editingContactId === contact.id }"
                  :disabled="isSubmitting && editingContactId === contact.id"
                  @click="startEditing(contact)"
                >
                  {{ editingContactId === contact.id ? 'Editing' : 'Edit details' }}
                </button>

                <button
                  type="button"
                  class="delete-contact-button"
                  :disabled="isSubmitting"
                  @click="deleteContact(contact)"
                >
                  Delete
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { graphqlRequest } from '../graphql/client'
import { useFormValidation, getGraphqlErrorMessage, getGraphqlFieldErrors } from '../composables/useFormValidation'

const CONTACTS_QUERY = `
  query ContactsPage {
    contacts {
      id
      name
      email
      phone
      title
      isPrimary
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const CUSTOMERS_QUERY = `
  query CustomersForContacts {
    customers {
      id
      name
    }
  }
`

const CREATE_CONTACT_MUTATION = `
  mutation CreateContact($name: String!, $email: String, $phone: String, $title: String, $isPrimary: Boolean, $customerId: String!) {
    createContact(name: $name, email: $email, phone: $phone, title: $title, isPrimary: $isPrimary, customerId: $customerId) {
      id
      name
      email
      phone
      title
      isPrimary
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const UPDATE_CONTACT_MUTATION = `
  mutation UpdateContact($id: String!, $name: String, $email: String, $phone: String, $title: String, $isPrimary: Boolean) {
    updateContact(id: $id, name: $name, email: $email, phone: $phone, title: $title, isPrimary: $isPrimary) {
      id
      name
      email
      phone
      title
      isPrimary
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const DELETE_CONTACT_MUTATION = `
  mutation DeleteContact($id: String!) {
    deleteContact(id: $id)
  }
`

const FORM_FIELDS = ['name', 'email', 'phone', 'title', 'customerId']

const contacts = ref([])
const customers = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const statusUpdateError = ref('')
const searchTerm = ref('')
const editingContactId = ref(null)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  title: '',
  customerId: '',
  isPrimary: false
})

const isEditingContact = computed(() => editingContactId.value !== null)

const sortedContacts = computed(() => {
  return [...contacts.value].sort((left, right) => {
    const leftTime = left.createdAt ? new Date(left.createdAt).getTime() : 0
    const rightTime = right.createdAt ? new Date(right.createdAt).getTime() : 0
    return rightTime - leftTime
  })
})

const filteredContacts = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return sortedContacts.value
  }
  return sortedContacts.value.filter((contact) => {
    return [
      contact.name,
      contact.email,
      contact.phone,
      contact.customer?.name
    ].some((value) => value?.toLowerCase().includes(query))
  })
})

const contactsWithEmail = computed(() => contacts.value.filter((c) => c.email).length)
const contactsWithPhone = computed(() => contacts.value.filter((c) => c.phone).length)
const primaryContactsCount = computed(() => contacts.value.filter((c) => c.isPrimary).length)

function normalizeContact(contact) {
  return {
    ...contact,
    name: contact.name || '',
    email: contact.email || null,
    phone: contact.phone || null,
    title: contact.title || null,
    isPrimary: Boolean(contact.isPrimary),
    customer: contact.customer ?? null
  }
}

const {
  formErrors,
  clearFormErrors,
  validateField,
  validateForm,
  applyServerFieldErrors,
  handleFieldInput: _handleFieldInput,
  handleFieldBlur: _handleFieldBlur
} = useFormValidation(FORM_FIELDS, {
  name: () => {
    if (!form.name) return 'Contact name is required.'
    if (form.name.length > 255) return 'Contact name must be 255 characters or fewer.'
    return ''
  },
  email: () => {
    if (!form.email) return ''
    if (form.email.length > 255) return 'Email must be 255 characters or fewer.'
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) return 'Enter a valid email address.'
    return ''
  },
  phone: () => {
    if (!form.phone) return ''
    if (form.phone.length > 20) return 'Phone number must be 20 characters or fewer.'
    if (!/^\+?[\d\s().-]{7,20}$/.test(form.phone)) return 'Use a valid phone format with digits, spaces, parentheses, dots, or dashes.'
    return ''
  },
  title: () => {
    if (!form.title) return ''
    if (form.title.length > 255) return 'Job title must be 255 characters or fewer.'
    return ''
  },
  customerId: () => {
    if (!form.customerId) return 'Customer is required.'
    return ''
  }
})

function handleFieldInput(field) {
  submitError.value = ''
  submitSuccess.value = ''
  _handleFieldInput(field)
}

function handleFieldBlur(field) {
  _handleFieldBlur(field)
}

function resetForm() {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.title = ''
  form.customerId = ''
  form.isPrimary = false
  editingContactId.value = null
  clearFormErrors()
}

function startEditing(contact) {
  submitError.value = ''
  submitSuccess.value = ''
  clearFormErrors()
  editingContactId.value = contact.id
  form.name = contact.name || ''
  form.email = contact.email || ''
  form.phone = contact.phone || ''
  form.title = contact.title || ''
  form.customerId = contact.customer?.id || ''
  form.isPrimary = Boolean(contact.isPrimary)
}

function cancelEditing() {
  submitError.value = ''
  submitSuccess.value = ''
  resetForm()
}

function getContactInitials(contact) {
  return (contact.name || 'C')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function formatCreatedAt(value) {
  if (!value) return 'Date unavailable'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Date unavailable'
  return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

async function loadContacts() {
  isLoading.value = true
  loadError.value = ''
  try {
    const [contactsData, customersData] = await Promise.all([
      graphqlRequest(CONTACTS_QUERY),
      graphqlRequest(CUSTOMERS_QUERY)
    ])
    contacts.value = (contactsData.contacts ?? []).map(normalizeContact)
    customers.value = customersData.customers ?? []
  } catch (error) {
    loadError.value = getGraphqlErrorMessage(error, 'Unable to load contacts right now.')
  } finally {
    isLoading.value = false
  }
}

async function deleteContact(contact) {
  if (!confirm(`Delete contact "${contact.name}"?`)) return
  statusUpdateError.value = ''
  try {
    await graphqlRequest(DELETE_CONTACT_MUTATION, { id: contact.id })
    contacts.value = contacts.value.filter((c) => c.id !== contact.id)
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to delete the contact right now.')
  }
}

async function submitContact() {
  submitError.value = ''
  submitSuccess.value = ''
  clearFormErrors()

  if (!validateForm()) {
    submitError.value = 'Please review the highlighted fields and try again.'
    return
  }

  isSubmitting.value = true
  try {
    const variables = {
      name: form.name,
      email: form.email || null,
      phone: form.phone || null,
      title: form.title || null,
      isPrimary: form.isPrimary
    }

    if (isEditingContact.value) {
      const data = await graphqlRequest(UPDATE_CONTACT_MUTATION, { id: editingContactId.value, ...variables })
      const updated = normalizeContact(data.updateContact)
      contacts.value = contacts.value.map((c) => (c.id === updated.id ? updated : c))
      submitSuccess.value = `Contact "${updated.name}" was updated.`
      resetForm()
      return
    }

    const data = await graphqlRequest(CREATE_CONTACT_MUTATION, { ...variables, customerId: form.customerId })
    const created = normalizeContact(data.createContact)
    contacts.value = [created, ...contacts.value.filter(({ id }) => id !== created.id)]
    submitSuccess.value = `Contact "${created.name}" was added.`
    resetForm()
  } catch (error) {
    const serverFieldErrors = getGraphqlFieldErrors(error)
    applyServerFieldErrors(serverFieldErrors)
    submitError.value = Object.values(serverFieldErrors).some(Boolean)
      ? 'Please review the highlighted fields and try again.'
      : getGraphqlErrorMessage(error, 'Unable to save this contact.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  await loadContacts()
})
</script>

<style scoped>
.contacts-page {
  display: flex;
  flex-direction: column;
  gap: 26px;
}

.hero-card {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding: 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.86), transparent 34%),
    linear-gradient(135deg, #3a2a5a 0%, #6e4fa3 44%, #9b7ed8 100%);
  color: #f5f0ff;
  box-shadow: 0 22px 48px rgba(50, 30, 80, 0.22);
}

.hero-copy {
  max-width: 640px;
}

.eyebrow,
.panel-kicker {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.page-title {
  font-size: 34px;
  line-height: 1.15;
  margin-bottom: 10px;
}

.page-subtitle {
  font-size: 15px;
  line-height: 1.6;
  color: rgba(245, 240, 255, 0.86);
}

.hero-stats {
  min-width: 250px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
}

.stat-chip {
  min-width: 190px;
  padding: 18px 20px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(12px);
}

.stat-label {
  display: block;
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.8;
}

.stat-value {
  display: block;
  margin-top: 10px;
  font-size: 34px;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(320px, 390px) minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.panel {
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid #d7e2ee;
  box-shadow: 0 18px 36px rgba(15, 43, 69, 0.08);
}

.form-panel,
.list-panel {
  padding: 24px;
}

.form-panel {
  position: sticky;
  top: 24px;
  background:
    linear-gradient(180deg, rgba(245, 240, 255, 0.95), rgba(255, 255, 255, 1) 36%),
    #ffffff;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 20px;
}

.panel-header h2 {
  font-size: 22px;
  color: #12314d;
}

.panel-note {
  max-width: 240px;
  font-size: 12px;
  line-height: 1.5;
  color: #6a7f92;
  text-align: right;
}

.form-insights {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 22px;
}

.insight-card {
  padding: 14px 16px;
  border-radius: 18px;
  background: #f2f7fc;
  border: 1px solid #dce7f2;
}

.insight-label {
  display: block;
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #6a7f95;
}

.insight-value {
  display: block;
  margin-top: 8px;
  font-size: 28px;
  color: #133553;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #173956;
}

.field span {
  font-size: 13px;
  font-weight: 600;
}

.field input,
.field select {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #cdd8e5;
  background: #ffffff;
  color: #173956;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field input:focus,
.field select:focus {
  outline: none;
  border-color: #6e4fa3;
  box-shadow: 0 0 0 4px rgba(110, 79, 163, 0.12);
}

.field-full {
  grid-column: 1 / -1;
}

.field-checkbox {
  flex-direction: row;
  align-items: center;
  gap: 10px;
}

.field-checkbox input {
  width: auto;
}

.field-has-error input,
.field-has-error select,
.input-invalid {
  border-color: #d64d4d;
  box-shadow: 0 0 0 4px rgba(214, 77, 77, 0.1);
}

.field-error {
  font-size: 12px;
  color: #c43f3f;
}

.alert {
  margin-top: 18px;
  padding: 12px 14px;
  border-radius: 14px;
  font-size: 14px;
}

.alert-error {
  background: #fff1f1;
  border: 1px solid #f2c4c4;
  color: #9e2f2f;
}

.alert-success {
  background: #eef9f1;
  border: 1px solid #c2e4ca;
  color: #256245;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.form-action-buttons {
  display: flex;
  align-items: center;
  gap: 12px;
}

.form-helper {
  font-size: 13px;
  line-height: 1.5;
  color: #688097;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 18px;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  transition: transform 0.15s ease, opacity 0.2s ease, box-shadow 0.2s ease;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #3a2a5a 0%, #6e4fa3 100%);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(58, 42, 90, 0.26);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.18);
  color: inherit;
  border: 1px solid rgba(255, 255, 255, 0.26);
}

.btn-tertiary {
  background: #eef3f7;
  color: #45627c;
  border: 1px solid #d5e1ec;
}

.list-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  align-items: center;
  margin-bottom: 20px;
}

.search-field {
  flex: 1 1 240px;
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 48px;
  padding: 0 16px;
  border-radius: 16px;
  border: 1px solid #d7e2ee;
  background: #f8fbfd;
  color: #648098;
}

.search-field svg {
  width: 18px;
  height: 18px;
}

.search-field input {
  flex: 1;
  border: none;
  background: transparent;
  color: #173956;
  font-size: 14px;
}

.search-field input:focus {
  outline: none;
}

.toolbar-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.toolbar-pill {
  display: inline-flex;
  align-items: center;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 999px;
  background: #eff5fb;
  color: #45627c;
  font-size: 13px;
  font-weight: 600;
}

.list-state {
  display: grid;
  place-items: center;
  min-height: 240px;
  border-radius: 20px;
  border: 1px dashed #d3deea;
  background: #fbfdff;
  color: #668097;
  text-align: center;
}

.empty-state strong {
  display: block;
  margin-bottom: 8px;
  color: #173956;
}

.contact-list {
  display: grid;
  gap: 16px;
}

.contact-card {
  padding: 20px;
  border-radius: 22px;
  border: 1px solid #dbe5f0;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(248, 251, 253, 1)),
    #ffffff;
}

.contact-card-head,
.contact-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.contact-control-groups {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 12px;
  align-items: center;
}

.edit-contact-button {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid #b8a3d8;
  background: #f0eaf8;
  color: #4a306e;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.edit-contact-button:hover:not(:disabled) {
  border-color: #4a306e;
  background: #e2d8f0;
}

.edit-contact-active {
  background: #4a306e;
  border-color: #4a306e;
  color: #ffffff;
}

.delete-contact-button {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid #e0b0b0;
  background: #fceeee;
  color: #8c2a2a;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.delete-contact-button:hover:not(:disabled) {
  border-color: #8c2a2a;
  background: #f5d6d6;
}

.contact-identity {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.contact-avatar {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 18px;
  background: linear-gradient(135deg, #9b7ed8 0%, #6e4fa3 100%);
  color: #f5f0ff;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.contact-copy {
  min-width: 0;
}

.contact-title-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.contact-title-row h3 {
  font-size: 20px;
  color: #173956;
}

.primary-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eaf7ec;
  color: #2c7741;
  font-size: 12px;
  font-weight: 700;
}

.customer-badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f1f6fb;
  color: #577189;
  font-size: 12px;
  font-weight: 700;
}

.contact-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 6px;
  font-size: 14px;
  color: #69839a;
}

.contact-divider {
  color: #8ca0b4;
}

.contact-title-line {
  margin-top: 4px;
  font-size: 13px;
  color: #7a90a3;
}

.contact-date {
  font-size: 12px;
  color: #7a90a3;
}

.engagement-pill {
  display: inline-flex;
  align-items: center;
  min-height: 32px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.engagement-neutral {
  background: #eef3f7;
  color: #5a7288;
}

.engagement-success {
  background: #e7f7ea;
  color: #2c7741;
}

@media (max-width: 1120px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .form-panel {
    position: static;
  }
}

@media (max-width: 860px) {
  .hero-card,
  .contact-card-head,
  .contact-actions,
  .panel-header,
  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .hero-stats {
    align-items: flex-start;
  }

  .field-grid {
    grid-template-columns: 1fr;
  }

  .panel-note {
    max-width: none;
    text-align: left;
  }
}
</style>
