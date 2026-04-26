<template>
  <div class="deals-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Deal Management</p>
        <h1 class="page-title">Manage pipeline deals</h1>
        <p class="page-subtitle">
          Track deal value, status, and close dates across the full sales pipeline.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Total deals</span>
          <strong class="stat-value">{{ deals.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" @click="loadDeals" :disabled="isLoading">
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
            <p class="panel-kicker">{{ isEditingDeal ? 'Edit deal' : 'New deal' }}</p>
            <h2>{{ isEditingDeal ? 'Update record' : 'Create record' }}</h2>
          </div>
          <span class="panel-note">
            {{ isEditingDeal ? 'Update deal details without changing the linked customer.' : 'Link every deal to an existing customer record.' }}
          </span>
        </div>

        <div class="form-insights">
          <div class="insight-card">
            <span class="insight-label">Pipeline value</span>
            <strong class="insight-value">{{ totalPipelineValue }}</strong>
          </div>
          <div class="insight-card">
            <span class="insight-label">Open deals</span>
            <strong class="insight-value">{{ openDealsCount }}</strong>
          </div>
        </div>

        <form class="deal-form" novalidate @submit.prevent="submitDeal">
          <div class="field-grid">
            <label class="field field-full" :class="{ 'field-has-error': formErrors.title }">
              <span>Title *</span>
              <input
                v-model.trim="form.title"
                :class="{ 'input-invalid': formErrors.title }"
                type="text"
                name="title"
                placeholder="Enterprise License 2024"
                maxlength="255"
                required
                @input="handleFieldInput('title')"
                @blur="handleFieldBlur('title')"
              >
              <small v-if="formErrors.title" class="field-error">{{ formErrors.title }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.value }">
              <span>Value</span>
              <input
                v-model.trim="form.value"
                :class="{ 'input-invalid': formErrors.value }"
                type="number"
                name="value"
                placeholder="25000"
                step="0.01"
                @input="handleFieldInput('value')"
                @blur="handleFieldBlur('value')"
              >
              <small v-if="formErrors.value" class="field-error">{{ formErrors.value }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.status }">
              <span>Status</span>
              <select
                v-model="form.status"
                :class="{ 'input-invalid': formErrors.status }"
                name="status"
                @change="handleFieldInput('status')"
              >
                <option v-for="option in dealStatusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <small v-if="formErrors.status" class="field-error">{{ formErrors.status }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.closeDate }">
              <span>Close date</span>
              <input
                v-model="form.closeDate"
                :class="{ 'input-invalid': formErrors.closeDate }"
                type="date"
                name="closeDate"
                @input="handleFieldInput('closeDate')"
                @blur="handleFieldBlur('closeDate')"
              >
              <small v-if="formErrors.closeDate" class="field-error">{{ formErrors.closeDate }}</small>
            </label>

            <label class="field field-full" :class="{ 'field-has-error': formErrors.customerId }">
              <span>Customer *</span>
              <select
                v-model="form.customerId"
                :class="{ 'input-invalid': formErrors.customerId }"
                name="customerId"
                :disabled="isEditingDeal"
                @change="handleFieldInput('customerId')"
              >
                <option value="">Select a customer</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }}
                </option>
              </select>
              <small v-if="formErrors.customerId" class="field-error">{{ formErrors.customerId }}</small>
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
              {{ isEditingDeal ? 'Save changes to keep the pipeline accurate.' : 'Create a deal and link it to the relevant customer record.' }}
            </p>
            <div class="form-action-buttons">
              <button
                v-if="isEditingDeal"
                class="btn btn-tertiary"
                type="button"
                :disabled="isSubmitting"
                @click="cancelEditing"
              >
                Cancel
              </button>
              <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? (isEditingDeal ? 'Saving changes...' : 'Saving deal...') : (isEditingDeal ? 'Save deal' : 'Add deal') }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Deal list</p>
            <h2>All deals</h2>
          </div>
          <span class="panel-note">
            {{ isLoading ? 'Loading from CRM...' : `${filteredDeals.length} visible record${filteredDeals.length === 1 ? '' : 's'}` }}
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
              name="deal-search"
              placeholder="Search title, status, or customer"
            >
          </label>

          <div class="toolbar-pills filter-pills">
            <button
              v-for="filter in statusFilters"
              :key="filter.value"
              type="button"
              class="toolbar-pill toolbar-pill-button"
              :class="{ 'toolbar-pill-active': selectedStatusFilter === filter.value }"
              @click="setStatusFilter(filter.value)"
            >
              {{ filter.label }}
            </button>
          </div>

          <div class="toolbar-pills">
            <span class="toolbar-pill">Open {{ openDealsCount }}</span>
            <span class="toolbar-pill">Won {{ wonDealsCount }}</span>
            <span class="toolbar-pill">Lost {{ lostDealsCount }}</span>
          </div>
        </div>

        <div v-if="isLoading" class="list-state">
          Loading deals...
        </div>

        <div v-else-if="deals.length === 0" class="list-state empty-state">
          <strong>No deals yet.</strong>
          <span>Create the first deal using the form on the left.</span>
        </div>

        <div v-else-if="filteredDeals.length === 0" class="list-state empty-state">
          <strong>No matching deals.</strong>
          <span>Try a broader search or clear the filter to see the full pipeline.</span>
        </div>

        <div v-else class="deal-list">
          <article v-for="deal in filteredDeals" :key="deal.id" class="deal-card">
            <div class="deal-card-head">
              <div class="deal-identity">
                <div class="deal-avatar">{{ getDealInitials(deal) }}</div>
                <div class="deal-copy">
                  <div class="deal-title-row">
                    <h3>{{ deal.title }}</h3>
                    <span class="customer-badge">{{ deal.customer?.name || 'Unknown customer' }}</span>
                  </div>
                  <p class="deal-subtitle">
                    <span>Value: {{ formatCurrency(deal.value) }}</span>
                    <span class="deal-divider">•</span>
                    <span>Close: {{ formatCloseDate(deal.closeDate) }}</span>
                  </p>
                </div>
              </div>
              <div class="deal-status">
                <span class="lifecycle-pill" :class="`lifecycle-${getStatusMeta(deal.status).tone}`">
                  {{ getStatusMeta(deal.status).label }}
                </span>
                <span class="deal-date">{{ formatCreatedAt(deal.createdAt) }}</span>
              </div>
            </div>

            <div class="deal-actions">
              <span class="engagement-pill" :class="`engagement-${getEngagementMeta(deal).tone}`">
                {{ getEngagementMeta(deal).label }}
              </span>

              <div class="deal-control-groups">
                <button
                  type="button"
                  class="edit-deal-button"
                  :class="{ 'edit-deal-active': editingDealId === deal.id }"
                  :disabled="isSubmitting && editingDealId === deal.id"
                  @click="startEditing(deal)"
                >
                  {{ editingDealId === deal.id ? 'Editing' : 'Edit details' }}
                </button>

                <button
                  type="button"
                  class="delete-deal-button"
                  :disabled="isSubmitting"
                  @click="deleteDeal(deal)"
                >
                  Delete
                </button>

                <div class="status-controls" :class="{ 'status-controls-disabled': isUpdatingDealStatus(deal.id) }">
                  <button
                    v-for="option in dealStatusOptions"
                    :key="option.value"
                    type="button"
                    class="status-control-button"
                    :class="{
                      'status-control-active': deal.status === option.value,
                      'status-control-pending': isUpdatingDealStatus(deal.id)
                    }"
                    :disabled="isUpdatingDealStatus(deal.id)"
                    @click="updateDealStatus(deal, option.value)"
                  >
                    {{ option.shortLabel }}
                  </button>
                </div>
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
import { useRoute, useRouter } from 'vue-router'
import { graphqlRequest } from '../graphql/client'
import { useFormValidation, getGraphqlErrorMessage, getGraphqlFieldErrors } from '../composables/useFormValidation'

const DEALS_QUERY = `
  query DealsPage {
    deals {
      id
      title
      value
      status
      closeDate
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const CUSTOMERS_QUERY = `
  query CustomersForDeals {
    customers {
      id
      name
    }
  }
`

const CREATE_DEAL_MUTATION = `
  mutation CreateDeal($title: String!, $value: Float, $status: String, $closeDate: String, $customerId: String!) {
    createDeal(title: $title, value: $value, status: $status, closeDate: $closeDate, customerId: $customerId) {
      id
      title
      value
      status
      closeDate
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const UPDATE_DEAL_MUTATION = `
  mutation UpdateDeal($id: String!, $title: String, $value: Float, $status: String, $closeDate: String) {
    updateDeal(id: $id, title: $title, value: $value, status: $status, closeDate: $closeDate) {
      id
      title
      value
      status
      closeDate
      createdAt
      customer {
        id
        name
      }
    }
  }
`

const UPDATE_DEAL_STATUS_MUTATION = `
  mutation UpdateDealStatus($id: String!, $status: String) {
    updateDeal(id: $id, status: $status) {
      id
      status
    }
  }
`

const DELETE_DEAL_MUTATION = `
  mutation DeleteDeal($id: String!) {
    deleteDeal(id: $id)
  }
`

const route = useRoute()
const router = useRouter()

const DEAL_STATUS_OPTIONS = [
  { value: 'open', label: 'Open', shortLabel: 'Open', tone: 'open' },
  { value: 'won', label: 'Won', shortLabel: 'Won', tone: 'won' },
  { value: 'lost', label: 'Lost', shortLabel: 'Lost', tone: 'lost' },
  { value: 'pending', label: 'Pending', shortLabel: 'Pending', tone: 'pending' }
]

const STATUS_FILTER_META = {
  all: { heading: 'Manage pipeline deals', listTitle: 'All deals' },
  open: { heading: 'Open pipeline deals', listTitle: 'Open deals' },
  won: { heading: 'Closed-won deals', listTitle: 'Won deals' },
  lost: { heading: 'Closed-lost deals', listTitle: 'Lost deals' },
  pending: { heading: 'Pending deals', listTitle: 'Pending deals' }
}

const FORM_FIELDS = ['title', 'value', 'status', 'closeDate', 'customerId']

const deals = ref([])
const customers = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const statusUpdateError = ref('')
const searchTerm = ref('')
const editingDealId = ref(null)
const statusUpdateState = reactive({})
const dealStatusOptions = DEAL_STATUS_OPTIONS
const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'open', label: 'Open' },
  { value: 'won', label: 'Won' },
  { value: 'lost', label: 'Lost' },
  { value: 'pending', label: 'Pending' }
]

const form = reactive({
  title: '',
  value: '',
  status: 'open',
  closeDate: '',
  customerId: ''
})

const isEditingDeal = computed(() => editingDealId.value !== null)

const sortedDeals = computed(() => {
  return [...deals.value].sort((left, right) => {
    const leftTime = left.createdAt ? new Date(left.createdAt).getTime() : 0
    const rightTime = right.createdAt ? new Date(right.createdAt).getTime() : 0
    return rightTime - leftTime
  })
})

const selectedStatusFilter = computed(() => {
  const value = typeof route.query.status === 'string' ? route.query.status.toLowerCase() : 'all'
  return STATUS_FILTER_META[value] ? value : 'all'
})

const currentFilterMeta = computed(() => {
  return STATUS_FILTER_META[selectedStatusFilter.value]
})

const filteredDeals = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  const lifecycleFiltered = selectedStatusFilter.value === 'all'
    ? sortedDeals.value
    : sortedDeals.value.filter((deal) => deal.status === selectedStatusFilter.value)

  if (!query) {
    return lifecycleFiltered
  }

  return lifecycleFiltered.filter((deal) => {
    return [
      deal.title,
      deal.status,
      deal.customer?.name
    ].some((value) => value?.toLowerCase().includes(query))
  })
})

const openDealsCount = computed(() => deals.value.filter((d) => d.status === 'open').length)
const wonDealsCount = computed(() => deals.value.filter((d) => d.status === 'won').length)
const lostDealsCount = computed(() => deals.value.filter((d) => d.status === 'lost').length)

const totalPipelineValue = computed(() => {
  const total = deals.value.reduce((sum, deal) => {
    return sum + (parseFloat(deal.value) || 0)
  }, 0)
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(total)
})

function normalizeDeal(deal) {
  return {
    ...deal,
    status: deal.status || 'open',
    value: deal.value ?? null,
    closeDate: deal.closeDate || null,
    customer: deal.customer ?? null
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
  title: () => {
    if (!form.title) return 'Deal title is required.'
    if (form.title.length > 255) return 'Deal title must be 255 characters or fewer.'
    return ''
  },
  value: () => {
    if (!form.value) return ''
    if (isNaN(parseFloat(form.value))) return 'Value must be a valid number.'
    return ''
  },
  status: () => {
    if (!form.status) return 'Status is required.'
    return ''
  },
  closeDate: () => {
    if (!form.closeDate) return ''
    const date = new Date(form.closeDate)
    if (Number.isNaN(date.getTime())) return 'Close date must be a valid date.'
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
  form.title = ''
  form.value = ''
  form.status = 'open'
  form.closeDate = ''
  form.customerId = ''
  editingDealId.value = null
  clearFormErrors()
}

function startEditing(deal) {
  submitError.value = ''
  submitSuccess.value = ''
  clearFormErrors()
  editingDealId.value = deal.id
  form.title = deal.title || ''
  form.value = deal.value ?? ''
  form.status = deal.status || 'open'
  form.closeDate = deal.closeDate || ''
  form.customerId = deal.customer?.id || ''
}

function cancelEditing() {
  submitError.value = ''
  submitSuccess.value = ''
  resetForm()
}

function getDealInitials(deal) {
  return (deal.title || 'D')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function getStatusMeta(status) {
  return dealStatusOptions.find((option) => option.value === status) || dealStatusOptions[0]
}

function getEngagementMeta(deal) {
  if (deal.status === 'won') {
    return { label: 'Closed won', tone: 'success' }
  }
  if (deal.status === 'lost') {
    return { label: 'Closed lost', tone: 'inactive' }
  }
  if (deal.status === 'pending') {
    return { label: 'Awaiting decision', tone: 'info' }
  }
  return { label: 'In progress', tone: 'neutral' }
}

function formatCurrency(value) {
  const num = parseFloat(value)
  if (isNaN(num)) return '—'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num)
}

function formatCloseDate(value) {
  if (!value) return 'No close date'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Invalid date'
  return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(date)
}

function formatCreatedAt(value) {
  if (!value) return 'Date unavailable'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Date unavailable'
  return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

function setStatusFilter(status) {
  router.push(status === 'all'
    ? { path: '/deals' }
    : { path: '/deals', query: { status } }
  )
}

async function loadDeals() {
  isLoading.value = true
  loadError.value = ''
  try {
    const [dealsData, customersData] = await Promise.all([
      graphqlRequest(DEALS_QUERY),
      graphqlRequest(CUSTOMERS_QUERY)
    ])
    deals.value = (dealsData.deals ?? []).map(normalizeDeal)
    customers.value = customersData.customers ?? []
  } catch (error) {
    loadError.value = getGraphqlErrorMessage(error, 'Unable to load deals right now.')
  } finally {
    isLoading.value = false
  }
}

function isUpdatingDealStatus(dealId) {
  return statusUpdateState[dealId] === true
}

async function updateDealStatus(deal, status) {
  if (deal.status === status || isUpdatingDealStatus(deal.id)) return
  statusUpdateError.value = ''
  statusUpdateState[deal.id] = true
  try {
    const data = await graphqlRequest(UPDATE_DEAL_STATUS_MUTATION, { id: deal.id, status })
    const updated = data.updateDeal
    deals.value = deals.value.map((existing) => {
      if (existing.id !== deal.id) return existing
      return normalizeDeal({ ...existing, ...updated })
    })
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to update the deal status right now.')
  } finally {
    delete statusUpdateState[deal.id]
  }
}

async function deleteDeal(deal) {
  if (!confirm(`Delete deal "${deal.title}"?`)) return
  statusUpdateError.value = ''
  try {
    await graphqlRequest(DELETE_DEAL_MUTATION, { id: deal.id })
    deals.value = deals.value.filter((d) => d.id !== deal.id)
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to delete the deal right now.')
  }
}

async function submitDeal() {
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
      title: form.title,
      value: form.value ? parseFloat(form.value) : null,
      status: form.status || null,
      closeDate: form.closeDate || null
    }

    if (isEditingDeal.value) {
      const data = await graphqlRequest(UPDATE_DEAL_MUTATION, { id: editingDealId.value, ...variables })
      const updated = normalizeDeal(data.updateDeal)
      deals.value = deals.value.map((d) => (d.id === updated.id ? updated : d))
      submitSuccess.value = `Deal "${updated.title}" was updated.`
      resetForm()
      return
    }

    const data = await graphqlRequest(CREATE_DEAL_MUTATION, { ...variables, customerId: form.customerId })
    const created = normalizeDeal(data.createDeal)
    deals.value = [created, ...deals.value.filter(({ id }) => id !== created.id)]
    submitSuccess.value = `Deal "${created.title}" was added.`
    resetForm()
  } catch (error) {
    const serverFieldErrors = getGraphqlFieldErrors(error)
    applyServerFieldErrors(serverFieldErrors)
    submitError.value = Object.values(serverFieldErrors).some(Boolean)
      ? 'Please review the highlighted fields and try again.'
      : getGraphqlErrorMessage(error, 'Unable to save this deal.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  await loadDeals()
})
</script>

<style scoped>
.deals-page {
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
    linear-gradient(135deg, #1a4a3a 0%, #2d8a6e 44%, #4ecda3 100%);
  color: #f0fff8;
  box-shadow: 0 22px 48px rgba(22, 90, 70, 0.22);
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
  color: rgba(240, 255, 248, 0.86);
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
    linear-gradient(180deg, rgba(240, 255, 248, 0.95), rgba(255, 255, 255, 1) 36%),
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
  border-color: #2d8a6e;
  box-shadow: 0 0 0 4px rgba(45, 138, 110, 0.12);
}

.field-full {
  grid-column: 1 / -1;
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
  background: linear-gradient(135deg, #1a4a3a 0%, #2d8a6e 100%);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(26, 74, 58, 0.26);
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

.toolbar-pill-button {
  border: 1px solid transparent;
  cursor: pointer;
}

.toolbar-pill-active {
  background: #14395a;
  color: #ffffff;
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

.deal-list {
  display: grid;
  gap: 16px;
}

.deal-card {
  padding: 20px;
  border-radius: 22px;
  border: 1px solid #dbe5f0;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(248, 251, 253, 1)),
    #ffffff;
}

.deal-card-head,
.deal-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.deal-control-groups {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 12px;
  align-items: center;
}

.edit-deal-button {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid #9bc9b5;
  background: #e8f5ef;
  color: #1a5c44;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.edit-deal-button:hover:not(:disabled) {
  border-color: #1a5c44;
  background: #d4ede2;
}

.edit-deal-active {
  background: #1a5c44;
  border-color: #1a5c44;
  color: #ffffff;
}

.delete-deal-button {
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

.delete-deal-button:hover:not(:disabled) {
  border-color: #8c2a2a;
  background: #f5d6d6;
}

.deal-identity {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.deal-avatar {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 18px;
  background: linear-gradient(135deg, #4ecda3 0%, #2d8a6e 100%);
  color: #f0fff8;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.deal-copy {
  min-width: 0;
}

.deal-title-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.deal-title-row h3 {
  font-size: 20px;
  color: #173956;
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

.deal-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 6px;
  font-size: 14px;
  color: #69839a;
}

.deal-divider {
  color: #8ca0b4;
}

.deal-status {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.lifecycle-pill,
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

.lifecycle-open {
  background: #e3f1ff;
  color: #1f5f9e;
}

.lifecycle-won {
  background: #eaf7ec;
  color: #2c7741;
}

.lifecycle-lost {
  background: #fff1f1;
  color: #8c2a2a;
}

.lifecycle-pending {
  background: #fff1da;
  color: #9c661b;
}

.deal-date {
  font-size: 12px;
  color: #7a90a3;
}

.engagement-neutral {
  background: #eef3f7;
  color: #5a7288;
}

.engagement-info {
  background: #e4f0ff;
  color: #1f5f9e;
}

.engagement-success {
  background: #e7f7ea;
  color: #2c7741;
}

.engagement-inactive {
  background: #f0f0f0;
  color: #6a6a6a;
}

.status-controls {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
}

.status-control-button {
  min-height: 36px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid #d5e1ec;
  background: #ffffff;
  color: #45627c;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.status-control-button:hover:not(:disabled) {
  border-color: #1a5c44;
  color: #1a5c44;
}

.status-control-active {
  background: #173956;
  border-color: #173956;
  color: #ffffff;
}

.status-control-pending,
.status-controls-disabled .status-control-button {
  opacity: 0.7;
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
  .deal-card-head,
  .deal-actions,
  .panel-header,
  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .hero-stats,
  .deal-status {
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
