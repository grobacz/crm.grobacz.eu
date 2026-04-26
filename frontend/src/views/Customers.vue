<template>
  <div class="customers-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Customer Management</p>
        <h1 class="page-title">{{ currentFilterMeta.heading }}</h1>
        <p class="page-subtitle">
          {{ currentFilterMeta.description }}
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Total customers</span>
          <strong class="stat-value">{{ customers.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" @click="loadCustomers" :disabled="isLoading">
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
            <p class="panel-kicker">New customer</p>
            <h2>Create record</h2>
          </div>
          <span class="panel-note">Fields marked with * are required.</span>
        </div>

        <div class="form-insights">
          <div class="insight-card">
            <span class="insight-label">Records with company</span>
            <strong class="insight-value">{{ customersWithCompany }}</strong>
          </div>
          <div class="insight-card">
            <span class="insight-label">Records with phone</span>
            <strong class="insight-value">{{ customersWithPhone }}</strong>
          </div>
        </div>

        <form class="customer-form" novalidate @submit.prevent="submitCustomer">
          <div class="field-grid">
            <label class="field field-full" :class="{ 'field-has-error': formErrors.name }">
              <span>Name *</span>
              <input
                v-model.trim="form.name"
                :class="{ 'input-invalid': formErrors.name }"
                type="text"
                name="name"
                placeholder="Acme Industries"
                maxlength="255"
                required
                @input="handleFieldInput('name')"
                @blur="handleFieldBlur('name')"
              >
              <small v-if="formErrors.name" class="field-error">{{ formErrors.name }}</small>
            </label>

            <label class="field field-full" :class="{ 'field-has-error': formErrors.email }">
              <span>Email *</span>
              <input
                v-model.trim="form.email"
                :class="{ 'input-invalid': formErrors.email }"
                type="email"
                name="email"
                placeholder="ops@acme.test"
                maxlength="255"
                required
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

            <label class="field" :class="{ 'field-has-error': formErrors.company }">
              <span>Company</span>
              <input
                v-model.trim="form.company"
                :class="{ 'input-invalid': formErrors.company }"
                type="text"
                name="company"
                placeholder="Acme Group"
                maxlength="255"
                @input="handleFieldInput('company')"
                @blur="handleFieldBlur('company')"
              >
              <small v-if="formErrors.company" class="field-error">{{ formErrors.company }}</small>
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
              Create a clean customer record now and enrich it with contacts and deals later.
            </p>
            <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
              {{ isSubmitting ? 'Saving customer...' : 'Add customer' }}
            </button>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Customer list</p>
            <h2>{{ currentFilterMeta.listTitle }}</h2>
          </div>
          <span class="panel-note">
            {{ isLoading ? 'Loading from CRM...' : `${filteredCustomers.length} visible record${filteredCustomers.length === 1 ? '' : 's'}` }}
          </span>
        </div>

        <div v-if="statusUpdateError" class="alert alert-error">
          {{ statusUpdateError }}
        </div>

        <div v-if="callActionError" class="alert alert-error">
          {{ callActionError }}
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
              name="customer-search"
              placeholder="Search name, company, email, or phone"
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
            <span class="toolbar-pill">With company {{ customersWithCompany }}</span>
            <span class="toolbar-pill">VIP {{ customersWithVipStatus }}</span>
            <span class="toolbar-pill">Active deals {{ customersWithDeals }}</span>
          </div>
        </div>

        <div v-if="isLoading" class="list-state">
          Loading customers...
        </div>

        <div v-else-if="customers.length === 0" class="list-state empty-state">
          <strong>No customers yet.</strong>
          <span>Create the first customer using the form on the left.</span>
        </div>

        <div v-else-if="filteredCustomers.length === 0" class="list-state empty-state">
          <strong>No matching customers.</strong>
          <span>Try a broader search or clear the filter to see the full customer base.</span>
        </div>

        <div v-else class="customer-list">
          <article v-for="customer in filteredCustomers" :key="customer.id" class="customer-card">
            <div class="customer-card-head">
              <div class="customer-identity">
                <div class="customer-avatar">{{ getCustomerInitials(customer.name) }}</div>
                <div class="customer-copy">
                  <div class="customer-title-row">
                    <h3>{{ customer.name }}</h3>
                    <span class="company-badge">{{ customer.company || 'Independent account' }}</span>
                  </div>
                  <p class="customer-subtitle">
                    <a :href="`mailto:${customer.email}`">{{ customer.email }}</a>
                    <span class="customer-divider">•</span>
                    <span>{{ customer.phone || 'No phone on file' }}</span>
                  </p>
                </div>
              </div>
              <div class="customer-status">
                <span class="lifecycle-pill" :class="`lifecycle-${getLifecycleStatusMeta(customer.status).tone}`">
                  {{ getLifecycleStatusMeta(customer.status).label }}
                </span>
                <span class="vip-pill" :class="`vip-${getVipMeta(customer.isVip).tone}`">
                  {{ getVipMeta(customer.isVip).label }}
                </span>
                <span class="customer-date">{{ formatCreatedAt(customer.createdAt) }}</span>
              </div>
            </div>

            <div class="customer-stats">
              <div class="stat-tile">
                <span class="tile-label">Contacts</span>
                <strong class="tile-value">{{ customer.contacts.length }}</strong>
              </div>
              <div class="stat-tile">
                <span class="tile-label">Deals</span>
                <strong class="tile-value">{{ customer.deals.length }}</strong>
              </div>
              <div class="stat-tile">
                <span class="tile-label">Profile</span>
                <strong class="tile-value">{{ getProfileCompleteness(customer) }}</strong>
              </div>
            </div>

            <div class="customer-actions">
              <span class="engagement-pill" :class="`engagement-${getEngagementStatus(customer).tone}`">
                {{ getEngagementStatus(customer).label }}
              </span>

              <div class="customer-control-groups">
                <button
                  type="button"
                  class="call-control-button"
                  :class="{
                    'call-control-active': isCustomerCallActive(customer),
                    'call-control-busy': isCustomerCallBlocked(customer)
                  }"
                  :disabled="isCustomerCallDisabled(customer)"
                  @click="toggleCustomerCall(customer)"
                >
                  {{ getCustomerCallButtonLabel(customer) }}
                </button>

                <div class="status-controls" :class="{ 'status-controls-disabled': isUpdatingCustomerStatus(customer.id) }">
                  <button
                    v-for="option in customerStatusOptions"
                    :key="option.value"
                    type="button"
                    class="status-control-button"
                    :class="{
                      'status-control-active': customer.status === option.value,
                      'status-control-pending': isUpdatingCustomerStatus(customer.id)
                    }"
                    :disabled="isUpdatingCustomerStatus(customer.id)"
                    @click="updateCustomerStatus(customer, option.value)"
                  >
                    {{ option.shortLabel }}
                  </button>
                </div>

                <div class="vip-controls" :class="{ 'status-controls-disabled': isUpdatingCustomerStatus(customer.id) }">
                  <button
                    type="button"
                    class="vip-control-button"
                    :class="{
                      'vip-control-active': customer.isVip,
                      'status-control-pending': isUpdatingCustomerStatus(customer.id)
                    }"
                    :disabled="isUpdatingCustomerStatus(customer.id)"
                    @click="updateCustomerVip(customer, !customer.isVip)"
                  >
                    {{ customer.isVip ? 'Disable VIP' : 'Enable VIP' }}
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
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { graphqlRequest } from '../graphql/client'
import { useCallCenterStore } from '../store/callCenter'
import { useFormValidation, getGraphqlErrorMessage, getGraphqlFieldErrors, normalizeEmail } from '../composables/useFormValidation'

const CUSTOMERS_QUERY = `
  query CustomersPage {
    customers {
      id
      name
      email
      phone
      company
      status
      isVip
      createdAt
      contacts {
        id
      }
      deals {
        id
      }
    }
  }
`

const CREATE_CUSTOMER_MUTATION = `
  mutation CreateCustomer($name: String!, $email: String!, $phone: String, $company: String) {
    createCustomer(name: $name, email: $email, phone: $phone, company: $company) {
      id
      name
      email
      phone
      company
      status
      isVip
      createdAt
      contacts {
        id
      }
      deals {
        id
      }
    }
  }
`

const UPDATE_CUSTOMER_STATUS_MUTATION = `
  mutation UpdateCustomerStatus($id: String!, $status: String, $isVip: Boolean) {
    updateCustomer(id: $id, status: $status, isVip: $isVip) {
      id
      status
      isVip
      updatedAt
    }
  }
`

const route = useRoute()
const router = useRouter()
const callCenterStore = useCallCenterStore()
const { activeCall, startingTargetKey, stoppingCallId } = storeToRefs(callCenterStore)

const CUSTOMER_STATUS_OPTIONS = [
  { value: 'active', label: 'Active', shortLabel: 'Active', tone: 'active' },
  { value: 'inactive', label: 'Inactive', shortLabel: 'Inactive', tone: 'inactive' }
]

const STATUS_FILTER_META = {
  all: {
    heading: 'Manage customers across the full CRM',
    description: 'Review every customer, update lifecycle status inline, and keep VIP marking independent from whether the account is active or inactive.',
    listTitle: 'All customers'
  },
  active: {
    heading: 'Manage active customers',
    description: 'This view focuses on customers currently in rotation so the sales team can keep active accounts clean and current.',
    listTitle: 'Active customers'
  },
  inactive: {
    heading: 'Review inactive customers',
    description: 'Use this filtered queue to revisit dormant accounts, update their lifecycle state, or move them back into the active pipeline.',
    listTitle: 'Inactive customers'
  },
  vip: {
    heading: 'Track VIP customers',
    description: 'This filtered segment keeps high-priority accounts together so you can maintain special handling and visibility.',
    listTitle: 'VIP customers'
  }
}

const customers = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const statusUpdateError = ref('')
const callActionError = ref('')
const searchTerm = ref('')
const statusUpdateState = reactive({})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  company: ''
})

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const PHONE_PATTERN = /^\+?[\d\s().-]{7,20}$/
const FORM_FIELDS = ['name', 'email', 'phone', 'company']
const customerStatusOptions = CUSTOMER_STATUS_OPTIONS
const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'vip', label: 'VIP' }
]

const sortedCustomers = computed(() => {
  return [...customers.value].sort((left, right) => {
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

const filteredCustomers = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  const lifecycleFilteredCustomers = selectedStatusFilter.value === 'all'
    ? sortedCustomers.value
    : selectedStatusFilter.value === 'vip'
      ? sortedCustomers.value.filter((customer) => customer.isVip)
      : sortedCustomers.value.filter((customer) => customer.status === selectedStatusFilter.value)

  if (!query) {
    return lifecycleFilteredCustomers
  }

  return lifecycleFilteredCustomers.filter((customer) => {
    return [
      customer.name,
      customer.email,
      customer.phone,
      customer.company
    ].some((value) => value?.toLowerCase().includes(query))
  })
})

const customersWithCompany = computed(() => {
  return customers.value.filter((customer) => customer.company).length
})

const customersWithPhone = computed(() => {
  return customers.value.filter((customer) => customer.phone).length
})

const customersWithDeals = computed(() => {
  return customers.value.filter((customer) => (customer.deals?.length ?? 0) > 0).length
})

const customersWithVipStatus = computed(() => {
  return customers.value.filter((customer) => customer.isVip).length
})

function normalizeCustomer(customer) {
  return {
    ...customer,
    status: customer.status || 'active',
    isVip: Boolean(customer.isVip),
    contacts: customer.contacts ?? [],
    deals: customer.deals ?? []
  }
}

function resetForm() {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.company = ''
  clearFormErrors()
}

function getTargetKey(targetType, targetId) {
  return callCenterStore.getTargetKey(targetType, targetId)
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
    if (!form.name) {
      return 'Customer name is required.'
    }
    if (form.name.length > 255) {
      return 'Customer name must be 255 characters or fewer.'
    }
    return ''
  },
  email: () => {
    if (!form.email) {
      return 'Email is required.'
    }
    if (form.email.length > 255) {
      return 'Email must be 255 characters or fewer.'
    }
    if (!EMAIL_PATTERN.test(form.email)) {
      return 'Enter a valid email address.'
    }
    const normalizedEmail = normalizeEmail(form.email)
    const hasDuplicate = customers.value.some((customer) => {
      return normalizeEmail(customer.email || '') === normalizedEmail
    })
    if (hasDuplicate) {
      return 'This email is already used by another customer.'
    }
    return ''
  },
  phone: () => {
    if (!form.phone) {
      return ''
    }
    if (form.phone.length > 20) {
      return 'Phone number must be 20 characters or fewer.'
    }
    if (!PHONE_PATTERN.test(form.phone)) {
      return 'Use a valid phone format with digits, spaces, parentheses, dots, or dashes.'
    }
    return ''
  },
  company: () => {
    if (!form.company) {
      return ''
    }
    if (form.company.length > 255) {
      return 'Company name must be 255 characters or fewer.'
    }
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

function setStatusFilter(status) {
  router.push(status === 'all'
    ? { path: '/customers' }
    : { path: '/customers', query: { status } }
  )
}

function getCustomerInitials(name) {
  return (name || 'N A')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function getLifecycleStatusMeta(status) {
  const selectedStatus = customerStatusOptions.find((option) => option.value === status)

  if (selectedStatus) {
    return selectedStatus
  }

  return customerStatusOptions[0]
}

function getVipMeta(isVip) {
  return isVip
    ? { label: 'VIP enabled', shortLabel: 'VIP on', tone: 'vip-on' }
    : { label: 'Standard', shortLabel: 'VIP off', tone: 'vip-off' }
}

function getEngagementStatus(customer) {
  if ((customer.deals?.length ?? 0) > 0) {
    return {
      label: 'Pipeline active',
      tone: 'success'
    }
  }

  if ((customer.contacts?.length ?? 0) > 0) {
    return {
      label: 'Contacted',
      tone: 'info'
    }
  }

  return {
    label: 'New record',
    tone: 'neutral'
  }
}

function getProfileCompleteness(customer) {
  const completedFields = [customer.email, customer.phone, customer.company].filter(Boolean).length
  return `${Math.round((completedFields / 3) * 100)}%`
}

function isCustomerCallActive(customer) {
  return activeCall.value?.targetType === 'customer' && activeCall.value?.targetId === customer.id
}

function isCustomerCallBlocked(customer) {
  return activeCall.value !== null && !isCustomerCallActive(customer)
}

function isCustomerCallDisabled(customer) {
  if (!customer.phone) {
    return true
  }

  if (startingTargetKey.value === getTargetKey('customer', customer.id)) {
    return true
  }

  if (isCustomerCallActive(customer) && stoppingCallId.value === activeCall.value?.id) {
    return true
  }

  return isCustomerCallBlocked(customer)
}

function getCustomerCallButtonLabel(customer) {
  if (!customer.phone) {
    return 'No phone'
  }

  if (startingTargetKey.value === getTargetKey('customer', customer.id)) {
    return 'Starting...'
  }

  if (isCustomerCallActive(customer)) {
    return stoppingCallId.value === activeCall.value?.id ? 'Stopping...' : 'Stop call'
  }

  if (isCustomerCallBlocked(customer)) {
    return 'Busy'
  }

  return 'Start call'
}

async function toggleCustomerCall(customer) {
  callActionError.value = ''

  try {
    if (isCustomerCallActive(customer)) {
      await callCenterStore.stopCall(activeCall.value?.id)
      return
    }

    await callCenterStore.startCall('customer', customer.id)
  } catch (error) {
    callActionError.value = getGraphqlErrorMessage(error, 'Unable to update the call state right now.')
  }
}

function formatCreatedAt(value) {
  if (!value) {
    return 'Date unavailable'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return 'Date unavailable'
  }

  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

async function loadCustomers() {
  isLoading.value = true
  loadError.value = ''

  try {
    const data = await graphqlRequest(CUSTOMERS_QUERY)
    customers.value = (data.customers ?? []).map(normalizeCustomer)
  } catch (error) {
    loadError.value = getGraphqlErrorMessage(error, 'Unable to load customers right now.')
  } finally {
    isLoading.value = false
  }
}

function isUpdatingCustomerStatus(customerId) {
  return statusUpdateState[customerId] === true
}

async function updateCustomerStatus(customer, status) {
  if (customer.status === status || isUpdatingCustomerStatus(customer.id)) {
    return
  }

  statusUpdateError.value = ''
  statusUpdateState[customer.id] = true

  try {
    const data = await graphqlRequest(UPDATE_CUSTOMER_STATUS_MUTATION, {
      id: customer.id,
      status
    })

    const updatedCustomer = data.updateCustomer
    customers.value = customers.value.map((existingCustomer) => {
      if (existingCustomer.id !== customer.id) {
        return existingCustomer
      }

      return normalizeCustomer({
        ...existingCustomer,
        ...updatedCustomer
      })
    })
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to update the customer status right now.')
  } finally {
    delete statusUpdateState[customer.id]
  }
}

async function updateCustomerVip(customer, isVip) {
  if (customer.isVip === isVip || isUpdatingCustomerStatus(customer.id)) {
    return
  }

  statusUpdateError.value = ''
  statusUpdateState[customer.id] = true

  try {
    const data = await graphqlRequest(UPDATE_CUSTOMER_STATUS_MUTATION, {
      id: customer.id,
      isVip
    })

    const updatedCustomer = data.updateCustomer
    customers.value = customers.value.map((existingCustomer) => {
      if (existingCustomer.id !== customer.id) {
        return existingCustomer
      }

      return normalizeCustomer({
        ...existingCustomer,
        ...updatedCustomer
      })
    })
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to update the customer VIP flag right now.')
  } finally {
    delete statusUpdateState[customer.id]
  }
}

async function submitCustomer() {
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
      email: form.email,
      phone: form.phone || null,
      company: form.company || null
    }

    const data = await graphqlRequest(CREATE_CUSTOMER_MUTATION, variables)
    const createdCustomer = normalizeCustomer(data.createCustomer)
    customers.value = [createdCustomer, ...customers.value.filter(({ id }) => id !== createdCustomer.id)]
    submitSuccess.value = `Customer "${createdCustomer.name}" was added.`
    resetForm()
  } catch (error) {
    const serverFieldErrors = getGraphqlFieldErrors(error)
    applyServerFieldErrors(serverFieldErrors)
    submitError.value = Object.values(serverFieldErrors).some(Boolean)
      ? 'Please review the highlighted fields and try again.'
      : getGraphqlErrorMessage(error, 'Unable to save this customer.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    loadCustomers(),
    callCenterStore.loadActiveCall()
  ])
})
</script>

<style scoped>
.customers-page {
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
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.82), transparent 34%),
    linear-gradient(135deg, #123b63 0%, #1e5b92 48%, #3f9c80 100%);
  color: #f7fbff;
  box-shadow: 0 22px 48px rgba(18, 60, 105, 0.26);
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
  color: rgba(247, 251, 255, 0.84);
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
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(12px);
}

.stat-label {
  display: block;
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.78;
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
    linear-gradient(180deg, rgba(244, 249, 255, 0.95), rgba(255, 255, 255, 1) 36%),
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
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
  text-align: right;
}

.form-insights {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
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
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #6a7f95;
}

.insight-value {
  display: block;
  margin-top: 8px;
  font-size: 24px;
  color: #17324d;
}

.customer-form {
  display: grid;
  gap: 16px;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field {
  display: grid;
  gap: 8px;
}

.field-has-error span {
  color: #9f1239;
}

.field-full {
  grid-column: 1 / -1;
}

.field span {
  color: #26415b;
  font-size: 14px;
  font-weight: 600;
}

.field input {
  width: 100%;
  padding: 13px 14px;
  border: 1px solid #cfd9e6;
  border-radius: 14px;
  font-size: 14px;
  color: #17324d;
  background: #f8fbff;
  transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
}

.field input:focus {
  outline: none;
  border-color: #1d5fa2;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(29, 95, 162, 0.14);
}

.field input.input-invalid {
  border-color: #fda4af;
  background: #fff7f8;
  box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.12);
}

.field-error {
  font-size: 12px;
  line-height: 1.5;
  color: #be123c;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 4px;
}

.form-helper {
  color: #5c738b;
  font-size: 13px;
  line-height: 1.6;
}

.btn {
  border: none;
  border-radius: 14px;
  padding: 12px 18px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s, opacity 0.2s;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #123c69 0%, #1d5fa2 100%);
  color: #ffffff;
  box-shadow: 0 12px 22px rgba(29, 95, 162, 0.22);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.22);
}

.alert {
  padding: 12px 14px;
  border-radius: 14px;
  font-size: 14px;
}

.alert-error {
  background: #fff1f2;
  color: #be123c;
  border: 1px solid #fecdd3;
}

.alert-success {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.list-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  padding: 16px 18px;
  border-radius: 18px;
  background: #f7fafc;
  border: 1px solid #e2eaf2;
}

.search-field {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 12px 14px;
  border-radius: 14px;
  background: #ffffff;
  border: 1px solid #d5e0eb;
}

.search-field svg {
  width: 18px;
  height: 18px;
  color: #678099;
  flex-shrink: 0;
}

.search-field input {
  width: 100%;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #17324d;
}

.search-field input:focus {
  outline: none;
}

.toolbar-pills {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.filter-pills {
  flex: 1;
  justify-content: flex-start;
}

.toolbar-pill {
  padding: 9px 12px;
  border-radius: 999px;
  background: #e9f1f8;
  color: #24425f;
  font-size: 12px;
  font-weight: 700;
}

.toolbar-pill-button {
  border: 1px solid transparent;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s, border-color 0.2s, transform 0.2s;
}

.toolbar-pill-button:hover {
  transform: translateY(-1px);
  background: #ddebf8;
}

.toolbar-pill-active {
  background: linear-gradient(135deg, #123c69 0%, #1d5fa2 100%);
  color: #ffffff;
  border-color: rgba(18, 60, 105, 0.25);
}

.list-state {
  min-height: 180px;
  display: grid;
  place-items: center;
  padding: 24px;
  border-radius: 18px;
  border: 1px dashed #cbd5e1;
  color: #5b7088;
  background: #f8fbff;
  text-align: center;
}

.empty-state {
  gap: 8px;
}

.customer-list {
  display: grid;
  gap: 18px;
}

.customer-card {
  padding: 20px;
  border: 1px solid #d9e4ef;
  border-radius: 20px;
  background:
    linear-gradient(180deg, rgba(247, 250, 255, 0.95), rgba(255, 255, 255, 1)),
    #ffffff;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.customer-card-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 18px;
}

.customer-identity {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
}

.customer-avatar {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #123c69 0%, #1d5fa2 100%);
  color: #ffffff;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.08em;
  flex-shrink: 0;
}

.customer-copy {
  min-width: 0;
}

.customer-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.customer-title-row h3 {
  font-size: 20px;
  color: #12314d;
}

.company-badge {
  padding: 6px 10px;
  border-radius: 999px;
  background: #edf4fb;
  color: #31557a;
  font-size: 12px;
  font-weight: 700;
}

.customer-subtitle {
  margin-top: 6px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 14px;
  color: #64748b;
}

.customer-subtitle a {
  color: #17324d;
  text-decoration: none;
}

.customer-divider {
  color: #8ca0b4;
}

.customer-status {
  flex-shrink: 0;
  display: grid;
  justify-items: end;
  gap: 8px;
}

.lifecycle-pill,
.engagement-pill,
.vip-pill {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.lifecycle-active {
  background: #e9f2ff;
  color: #1c61b4;
}

.lifecycle-vip {
  background: #fff4dd;
  color: #a16207;
}

.lifecycle-inactive {
  background: #eff4f8;
  color: #5f7286;
}

.vip-vip-on {
  background: #fff4dd;
  color: #a16207;
}

.vip-vip-off {
  background: #f4f8fc;
  color: #617385;
}

.engagement-success {
  background: #e9fbf4;
  color: #0f8a5f;
}

.engagement-info {
  background: #e9f2ff;
  color: #1c61b4;
}

.engagement-neutral {
  background: #eff4f8;
  color: #5f7286;
}

.customer-date {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f6fb;
  color: #56718c;
  font-size: 12px;
  font-weight: 700;
}

.customer-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 16px;
}

.stat-tile {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #e3ebf3;
  background: #fbfdff;
}

.tile-label {
  display: block;
  margin-bottom: 8px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #6b7f94;
}

.tile-value {
  display: block;
  font-size: 24px;
  line-height: 1;
  color: #17324d;
}

.customer-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #e7eef6;
}

.customer-control-groups {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.call-control-button {
  border: none;
  min-height: 42px;
  padding: 0 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, #0f766e 0%, #0f9a8f 100%);
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.call-control-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(15, 154, 143, 0.22);
}

.call-control-button:disabled {
  cursor: not-allowed;
  opacity: 0.72;
}

.call-control-active {
  background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
}

.call-control-busy {
  background: #dde6ef;
  color: #587188;
  box-shadow: none;
}

.status-controls {
  display: inline-flex;
  gap: 8px;
  padding: 6px;
  border-radius: 999px;
  background: #f4f8fc;
  border: 1px solid #dbe6f1;
}

.status-controls-disabled {
  opacity: 0.72;
}

.status-control-button {
  border: none;
  background: transparent;
  color: #4b647d;
  font-size: 12px;
  font-weight: 800;
  padding: 9px 12px;
  border-radius: 999px;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s, transform 0.2s;
}

.status-control-button:hover:not(:disabled) {
  background: #e6eef7;
  transform: translateY(-1px);
}

.status-control-button:disabled {
  cursor: not-allowed;
}

.status-control-active {
  background: linear-gradient(135deg, #123c69 0%, #1d5fa2 100%);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(29, 95, 162, 0.18);
}

.status-control-pending {
  opacity: 0.78;
}

.vip-controls {
  display: inline-flex;
  padding: 6px;
  border-radius: 999px;
  background: #fff8e8;
  border: 1px solid #f3dfac;
}

.vip-control-button {
  border: none;
  background: transparent;
  color: #8f5b00;
  font-size: 12px;
  font-weight: 800;
  padding: 9px 12px;
  border-radius: 999px;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s, transform 0.2s;
}

.vip-control-button:hover:not(:disabled) {
  background: #fdecc4;
  transform: translateY(-1px);
}

.vip-control-button:disabled {
  cursor: not-allowed;
}

.vip-control-active {
  background: linear-gradient(135deg, #f0b429 0%, #d97706 100%);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(217, 119, 6, 0.2);
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .form-panel {
    position: static;
  }
}

@media (max-width: 720px) {
  .hero-card,
  .panel-header,
  .customer-card-head,
  .customer-actions,
  .form-actions,
  .list-toolbar {
    flex-direction: column;
  }

  .hero-stats {
    min-width: 0;
    align-items: stretch;
  }

  .panel-note {
    text-align: left;
  }

  .field-grid,
  .form-insights,
  .customer-stats {
    grid-template-columns: 1fr;
  }

  .customer-status {
    justify-items: start;
  }

  .customer-control-groups {
    width: 100%;
    justify-content: flex-start;
  }

  .toolbar-pills {
    justify-content: flex-start;
  }

  .status-controls {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
