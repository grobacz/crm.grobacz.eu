<template>
  <div class="leads-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Lead Management</p>
        <h1 class="page-title">{{ currentFilterMeta.heading }}</h1>
        <p class="page-subtitle">
          {{ currentFilterMeta.description }}
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Total leads</span>
          <strong class="stat-value">{{ leads.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" @click="loadLeads" :disabled="isLoading">
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
            <p class="panel-kicker">{{ isEditingLead ? 'Edit lead' : 'New lead' }}</p>
            <h2>{{ isEditingLead ? 'Update record' : 'Create record' }}</h2>
          </div>
          <span class="panel-note">
            {{ isEditingLead ? 'Use this form to backfill missing lead details.' : 'Only one contact method is required: email or phone.' }}
          </span>
        </div>

        <div class="form-insights">
          <div class="insight-card">
            <span class="insight-label">With email</span>
            <strong class="insight-value">{{ leadsWithEmail }}</strong>
          </div>
          <div class="insight-card">
            <span class="insight-label">With phone</span>
            <strong class="insight-value">{{ leadsWithPhone }}</strong>
          </div>
        </div>

        <form class="lead-form" novalidate @submit.prevent="submitLead">
          <div class="field-grid">
            <label class="field field-full" :class="{ 'field-has-error': formErrors.name }">
              <span>Name</span>
              <input
                v-model.trim="form.name"
                :class="{ 'input-invalid': formErrors.name }"
                type="text"
                name="name"
                placeholder="Alex Morgan"
                maxlength="255"
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
                placeholder="alex@example.com"
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

            <label class="field field-full" :class="{ 'field-has-error': formErrors.company }">
              <span>Company</span>
              <input
                v-model.trim="form.company"
                :class="{ 'input-invalid': formErrors.company }"
                type="text"
                name="company"
                placeholder="Northwind Labs"
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
              {{ isEditingLead ? 'Save changes to enrich this lead without converting it into a customer.' : 'Leads stay separate from customer records until an explicit conversion flow is added.' }}
            </p>
            <div class="form-action-buttons">
              <button
                v-if="isEditingLead"
                class="btn btn-tertiary"
                type="button"
                :disabled="isSubmitting"
                @click="cancelEditing"
              >
                Cancel
              </button>
              <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? (isEditingLead ? 'Saving changes...' : 'Saving lead...') : (isEditingLead ? 'Save lead' : 'Add lead') }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Lead list</p>
            <h2>{{ currentFilterMeta.listTitle }}</h2>
          </div>
          <span class="panel-note">
            {{ isLoading ? 'Loading from CRM...' : `${filteredLeads.length} visible record${filteredLeads.length === 1 ? '' : 's'}` }}
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
              name="lead-search"
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
            <span class="toolbar-pill">Qualified {{ qualifiedLeadCount }}</span>
            <span class="toolbar-pill">Converted {{ convertedLeadCount }}</span>
            <span class="toolbar-pill">Missing company {{ leadsMissingCompany }}</span>
          </div>
        </div>

        <div v-if="isLoading" class="list-state">
          Loading leads...
        </div>

        <div v-else-if="leads.length === 0" class="list-state empty-state">
          <strong>No leads yet.</strong>
          <span>Create the first lead using the form on the left.</span>
        </div>

        <div v-else-if="filteredLeads.length === 0" class="list-state empty-state">
          <strong>No matching leads.</strong>
          <span>Try a broader search or clear the filter to see the full list.</span>
        </div>

        <div v-else class="lead-list">
          <article v-for="lead in filteredLeads" :key="lead.id" class="lead-card">
            <div class="lead-card-head">
              <div class="lead-identity">
                <div class="lead-avatar">{{ getLeadInitials(lead) }}</div>
                <div class="lead-copy">
                  <div class="lead-title-row">
                    <h3>{{ getLeadDisplayName(lead) }}</h3>
                    <span class="company-badge">{{ lead.company || 'Independent lead' }}</span>
                  </div>
                  <p class="lead-subtitle">
                    <span v-if="lead.email">
                      <a :href="`mailto:${lead.email}`">{{ lead.email }}</a>
                    </span>
                    <span v-if="lead.email && lead.phone" class="lead-divider">•</span>
                    <span>{{ lead.phone || 'No phone on file' }}</span>
                  </p>
                </div>
              </div>
              <div class="lead-status">
                <span class="lifecycle-pill" :class="`lifecycle-${getStatusMeta(lead.status).tone}`">
                  {{ getStatusMeta(lead.status).label }}
                </span>
                <span class="lead-date">{{ formatCreatedAt(lead.createdAt) }}</span>
              </div>
            </div>

            <div class="lead-stats">
              <div class="stat-tile">
                <span class="tile-label">Contact</span>
                <strong class="tile-value">{{ getContactReadiness(lead) }}</strong>
              </div>
              <div class="stat-tile">
                <span class="tile-label">Profile</span>
                <strong class="tile-value">{{ getProfileCompleteness(lead) }}</strong>
              </div>
              <div class="stat-tile">
                <span class="tile-label">Bucket</span>
                <strong class="tile-value">{{ getStatusMeta(lead.status).shortLabel }}</strong>
              </div>
            </div>

            <div class="lead-actions">
              <span class="engagement-pill" :class="`engagement-${getEngagementMeta(lead).tone}`">
                {{ getEngagementMeta(lead).label }}
              </span>

              <div class="lead-control-groups">
                <button
                  type="button"
                  class="call-control-button"
                  :class="{
                    'call-control-active': isLeadCallActive(lead),
                    'call-control-busy': isLeadCallBlocked(lead)
                  }"
                  :disabled="isLeadCallDisabled(lead)"
                  @click="toggleLeadCall(lead)"
                >
                  {{ getLeadCallButtonLabel(lead) }}
                </button>

                <button
                  type="button"
                  class="edit-lead-button"
                  :class="{ 'edit-lead-active': editingLeadId === lead.id }"
                  :disabled="isSubmitting && editingLeadId === lead.id"
                  @click="startEditing(lead)"
                >
                  {{ editingLeadId === lead.id ? 'Editing' : 'Edit details' }}
                </button>

                <div class="status-controls" :class="{ 'status-controls-disabled': isUpdatingLeadStatus(lead.id) }">
                  <button
                    v-for="option in leadStatusOptions"
                    :key="option.value"
                    type="button"
                    class="status-control-button"
                    :class="{
                      'status-control-active': lead.status === option.value,
                      'status-control-pending': isUpdatingLeadStatus(lead.id)
                    }"
                    :disabled="isUpdatingLeadStatus(lead.id)"
                    @click="updateLeadStatus(lead, option.value)"
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
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { graphqlRequest } from '../graphql/client'
import { useCallCenterStore } from '../store/callCenter'

const LEADS_QUERY = `
  query LeadsPage {
    leads {
      id
      name
      email
      phone
      company
      status
      createdAt
      updatedAt
    }
  }
`

const CREATE_LEAD_MUTATION = `
  mutation CreateLead($name: String, $email: String, $phone: String, $company: String) {
    createLead(name: $name, email: $email, phone: $phone, company: $company) {
      id
      name
      email
      phone
      company
      status
      createdAt
      updatedAt
    }
  }
`

const UPDATE_LEAD_STATUS_MUTATION = `
  mutation UpdateLeadStatus($id: String!, $status: String) {
    updateLead(id: $id, status: $status) {
      id
      status
      updatedAt
    }
  }
`

const UPDATE_LEAD_MUTATION = `
  mutation UpdateLead($id: String!, $name: String, $email: String, $phone: String, $company: String) {
    updateLead(id: $id, name: $name, email: $email, phone: $phone, company: $company) {
      id
      name
      email
      phone
      company
      status
      createdAt
      updatedAt
    }
  }
`

const route = useRoute()
const router = useRouter()
const callCenterStore = useCallCenterStore()
const { activeCall, startingTargetKey, stoppingCallId } = storeToRefs(callCenterStore)

const LEAD_STATUS_OPTIONS = [
  { value: 'new', label: 'New', shortLabel: 'New', tone: 'new' },
  { value: 'qualified', label: 'Qualified', shortLabel: 'Qualified', tone: 'qualified' },
  { value: 'converted', label: 'Converted', shortLabel: 'Converted', tone: 'converted' }
]

const STATUS_FILTER_META = {
  all: {
    heading: 'Manage leads before they become customers',
    description: 'Capture incomplete pre-customer records, triage them by status, and keep the qualification queue separate from the customer base.',
    listTitle: 'All leads'
  },
  new: {
    heading: 'Review brand-new inbound leads',
    description: 'This queue keeps newly captured prospects together so the team can qualify them before they graduate into the active sales process.',
    listTitle: 'New leads'
  },
  qualified: {
    heading: 'Work the qualified lead queue',
    description: 'Qualified leads have enough signal to pursue further, even if their customer record does not exist yet.',
    listTitle: 'Qualified leads'
  },
  converted: {
    heading: 'Track leads already marked converted',
    description: 'Converted leads remain visible here until a proper lead-to-customer conversion workflow is implemented.',
    listTitle: 'Converted leads'
  }
}

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const PHONE_PATTERN = /^\+?[\d\s().-]{7,20}$/
const FORM_FIELDS = ['name', 'email', 'phone', 'company']

const leads = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const statusUpdateError = ref('')
const callActionError = ref('')
const searchTerm = ref('')
const editingLeadId = ref(null)
const statusUpdateState = reactive({})
const leadStatusOptions = LEAD_STATUS_OPTIONS
const statusFilters = [
  { value: 'all', label: 'All' },
  { value: 'new', label: 'New' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'converted', label: 'Converted' }
]

const form = reactive({
  name: '',
  email: '',
  phone: '',
  company: ''
})

const formErrors = reactive({
  name: '',
  email: '',
  phone: '',
  company: ''
})

const isEditingLead = computed(() => editingLeadId.value !== null)

const sortedLeads = computed(() => {
  return [...leads.value].sort((left, right) => {
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

const filteredLeads = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  const lifecycleFilteredLeads = selectedStatusFilter.value === 'all'
    ? sortedLeads.value
    : sortedLeads.value.filter((lead) => lead.status === selectedStatusFilter.value)

  if (!query) {
    return lifecycleFilteredLeads
  }

  return lifecycleFilteredLeads.filter((lead) => {
    return [
      lead.name,
      lead.email,
      lead.phone,
      lead.company
    ].some((value) => value?.toLowerCase().includes(query))
  })
})

const leadsWithEmail = computed(() => {
  return leads.value.filter((lead) => lead.email).length
})

const leadsWithPhone = computed(() => {
  return leads.value.filter((lead) => lead.phone).length
})

const qualifiedLeadCount = computed(() => {
  return leads.value.filter((lead) => lead.status === 'qualified').length
})

const convertedLeadCount = computed(() => {
  return leads.value.filter((lead) => lead.status === 'converted').length
})

const leadsMissingCompany = computed(() => {
  return leads.value.filter((lead) => !lead.company).length
})

function normalizeLead(lead) {
  return {
    ...lead,
    name: lead.name || null,
    email: lead.email || null,
    phone: lead.phone || null,
    company: lead.company || null,
    status: lead.status || 'new'
  }
}

function getGraphqlErrorMessage(error, fallbackMessage) {
  return error?.response?.errors?.[0]?.message || fallbackMessage
}

function getGraphqlFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors || {}
}

function getTargetKey(targetType, targetId) {
  return callCenterStore.getTargetKey(targetType, targetId)
}

function clearFormErrors() {
  for (const field of FORM_FIELDS) {
    formErrors[field] = ''
  }
}

function resetForm() {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.company = ''
  editingLeadId.value = null
  clearFormErrors()
}

function applyServerFieldErrors(errors) {
  for (const field of FORM_FIELDS) {
    formErrors[field] = errors[field] || ''
  }
}

function normalizeEmail(value) {
  return value.trim().toLowerCase()
}

function validateName() {
  if (!form.name) {
    return ''
  }

  if (form.name.length > 255) {
    return 'Lead name must be 255 characters or fewer.'
  }

  return ''
}

function validateEmail() {
  if (!form.email) {
    if (!form.phone) {
      return 'Provide at least an email or a phone number.'
    }

    return ''
  }

  if (form.email.length > 255) {
    return 'Email must be 255 characters or fewer.'
  }

  if (!EMAIL_PATTERN.test(form.email)) {
    return 'Enter a valid email address.'
  }

  const normalizedEmail = normalizeEmail(form.email)
  const hasDuplicate = leads.value.some((lead) => {
    if (lead.id === editingLeadId.value) {
      return false
    }

    return normalizeEmail(lead.email || '') === normalizedEmail
  })

  if (hasDuplicate) {
    return 'This email is already used by another lead.'
  }

  return ''
}

function validatePhone() {
  if (!form.phone) {
    if (!form.email) {
      return 'Provide at least an email or a phone number.'
    }

    return ''
  }

  if (form.phone.length > 20) {
    return 'Phone number must be 20 characters or fewer.'
  }

  if (!PHONE_PATTERN.test(form.phone)) {
    return 'Use a valid phone format with digits, spaces, parentheses, dots, or dashes.'
  }

  return ''
}

function validateCompany() {
  if (!form.company) {
    return ''
  }

  if (form.company.length > 255) {
    return 'Company name must be 255 characters or fewer.'
  }

  return ''
}

function validateField(field) {
  const validators = {
    name: validateName,
    email: validateEmail,
    phone: validatePhone,
    company: validateCompany
  }

  const validator = validators[field]
  const message = validator ? validator() : ''
  formErrors[field] = message

  return message === ''
}

function validateForm() {
  const results = FORM_FIELDS.map((field) => validateField(field))

  return results.every(Boolean)
}

function handleFieldInput(field) {
  submitError.value = ''
  submitSuccess.value = ''

  if (field === 'email' || field === 'phone') {
    validateField('email')
    validateField('phone')
    return
  }

  if (formErrors[field]) {
    validateField(field)
  }
}

function handleFieldBlur(field) {
  if (field === 'email' || field === 'phone') {
    validateField('email')
    validateField('phone')
    return
  }

  validateField(field)
}

function setStatusFilter(status) {
  router.push(status === 'all'
    ? { path: '/leads' }
    : { path: '/leads', query: { status } }
  )
}

function getLeadDisplayName(lead) {
  return lead.name || lead.email || lead.phone || 'Untitled lead'
}

function fillForm(lead) {
  form.name = lead.name || ''
  form.email = lead.email || ''
  form.phone = lead.phone || ''
  form.company = lead.company || ''
}

function startEditing(lead) {
  submitError.value = ''
  submitSuccess.value = ''
  clearFormErrors()
  editingLeadId.value = lead.id
  fillForm(lead)
}

function cancelEditing() {
  submitError.value = ''
  submitSuccess.value = ''
  resetForm()
}

function getLeadInitials(lead) {
  return getLeadDisplayName(lead)
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function getStatusMeta(status) {
  return leadStatusOptions.find((option) => option.value === status) || leadStatusOptions[0]
}

function getContactReadiness(lead) {
  if (lead.email && lead.phone) {
    return 'Full'
  }

  if (lead.email || lead.phone) {
    return 'Partial'
  }

  return 'Missing'
}

function getProfileCompleteness(lead) {
  const completedFields = [lead.name, lead.email, lead.phone, lead.company].filter(Boolean).length
  return `${Math.round((completedFields / 4) * 100)}%`
}

function getEngagementMeta(lead) {
  if (lead.status === 'converted') {
    return { label: 'Marked converted', tone: 'success' }
  }

  if (lead.status === 'qualified') {
    return { label: 'Ready for follow-up', tone: 'info' }
  }

  return { label: 'Needs qualification', tone: 'neutral' }
}

function isLeadCallActive(lead) {
  return activeCall.value?.targetType === 'lead' && activeCall.value?.targetId === lead.id
}

function isLeadCallBlocked(lead) {
  return activeCall.value !== null && !isLeadCallActive(lead)
}

function isLeadCallDisabled(lead) {
  if (!lead.phone) {
    return true
  }

  if (startingTargetKey.value === getTargetKey('lead', lead.id)) {
    return true
  }

  if (isLeadCallActive(lead) && stoppingCallId.value === activeCall.value?.id) {
    return true
  }

  return isLeadCallBlocked(lead)
}

function getLeadCallButtonLabel(lead) {
  if (!lead.phone) {
    return 'No phone'
  }

  if (startingTargetKey.value === getTargetKey('lead', lead.id)) {
    return 'Starting...'
  }

  if (isLeadCallActive(lead)) {
    return stoppingCallId.value === activeCall.value?.id ? 'Stopping...' : 'Stop call'
  }

  if (isLeadCallBlocked(lead)) {
    return 'Busy'
  }

  return 'Start call'
}

async function toggleLeadCall(lead) {
  callActionError.value = ''

  try {
    if (isLeadCallActive(lead)) {
      await callCenterStore.stopCall(activeCall.value?.id)
      return
    }

    await callCenterStore.startCall('lead', lead.id)
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

async function loadLeads() {
  isLoading.value = true
  loadError.value = ''

  try {
    const data = await graphqlRequest(LEADS_QUERY)
    leads.value = (data.leads ?? []).map(normalizeLead)
  } catch (error) {
    loadError.value = getGraphqlErrorMessage(error, 'Unable to load leads right now.')
  } finally {
    isLoading.value = false
  }
}

function isUpdatingLeadStatus(leadId) {
  return statusUpdateState[leadId] === true
}

async function updateLeadStatus(lead, status) {
  if (lead.status === status || isUpdatingLeadStatus(lead.id)) {
    return
  }

  statusUpdateError.value = ''
  statusUpdateState[lead.id] = true

  try {
    const data = await graphqlRequest(UPDATE_LEAD_STATUS_MUTATION, {
      id: lead.id,
      status
    })

    const updatedLead = data.updateLead
    leads.value = leads.value.map((existingLead) => {
      if (existingLead.id !== lead.id) {
        return existingLead
      }

      return normalizeLead({
        ...existingLead,
        ...updatedLead
      })
    })
  } catch (error) {
    statusUpdateError.value = getGraphqlErrorMessage(error, 'Unable to update the lead status right now.')
  } finally {
    delete statusUpdateState[lead.id]
  }
}

async function submitLead() {
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
      name: form.name || null,
      email: form.email || null,
      phone: form.phone || null,
      company: form.company || null
    }

    if (isEditingLead.value) {
      const data = await graphqlRequest(UPDATE_LEAD_MUTATION, {
        id: editingLeadId.value,
        ...variables
      })
      const updatedLead = normalizeLead(data.updateLead)
      leads.value = leads.value.map((lead) => {
        return lead.id === updatedLead.id ? updatedLead : lead
      })
      submitSuccess.value = `Lead "${getLeadDisplayName(updatedLead)}" was updated.`
      resetForm()
      return
    }

    const data = await graphqlRequest(CREATE_LEAD_MUTATION, variables)
    const createdLead = normalizeLead(data.createLead)
    leads.value = [createdLead, ...leads.value.filter(({ id }) => id !== createdLead.id)]
    submitSuccess.value = `Lead "${getLeadDisplayName(createdLead)}" was added.`
    resetForm()
  } catch (error) {
    const serverFieldErrors = getGraphqlFieldErrors(error)
    applyServerFieldErrors(serverFieldErrors)
    submitError.value = Object.values(serverFieldErrors).some(Boolean)
      ? 'Please review the highlighted fields and try again.'
      : getGraphqlErrorMessage(error, 'Unable to save this lead.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    loadLeads(),
    callCenterStore.loadActiveCall()
  ])
})
</script>

<style scoped>
.leads-page {
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
    linear-gradient(135deg, #58411a 0%, #9f6b20 44%, #d48a34 100%);
  color: #fff8ef;
  box-shadow: 0 22px 48px rgba(97, 63, 18, 0.22);
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
  color: rgba(255, 248, 239, 0.86);
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
    linear-gradient(180deg, rgba(255, 249, 241, 0.95), rgba(255, 255, 255, 1) 36%),
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
  background: #f7f8fc;
  border: 1px solid #e1e8f0;
}

.insight-label {
  display: block;
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #74879b;
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

.field input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #cdd8e5;
  background: #ffffff;
  color: #173956;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field input:focus {
  outline: none;
  border-color: #c5811f;
  box-shadow: 0 0 0 4px rgba(197, 129, 31, 0.12);
}

.field-full {
  grid-column: 1 / -1;
}

.field-has-error input,
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
  background: linear-gradient(135deg, #ad6d16 0%, #cf8631 100%);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(173, 109, 22, 0.26);
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

.lead-list {
  display: grid;
  gap: 16px;
}

.lead-card {
  padding: 20px;
  border-radius: 22px;
  border: 1px solid #dbe5f0;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(248, 251, 253, 1)),
    #ffffff;
}

.lead-card-head,
.lead-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.lead-control-groups {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 12px;
  align-items: center;
}

.call-control-button {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #0f766e 0%, #0f9a8f 100%);
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.call-control-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(15, 154, 143, 0.22);
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

.lead-identity {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.lead-avatar {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f1c37b 0%, #da8d23 100%);
  color: #fffaf2;
  font-weight: 700;
  letter-spacing: 0.06em;
}

.lead-copy {
  min-width: 0;
}

.lead-title-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.lead-title-row h3 {
  font-size: 20px;
  color: #173956;
}

.company-badge {
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

.lead-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 6px;
  font-size: 14px;
  color: #69839a;
}

.lead-subtitle a {
  color: #9c661b;
  text-decoration: none;
}

.lead-status {
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

.lifecycle-new {
  background: #fff1da;
  color: #9c661b;
}

.lifecycle-qualified {
  background: #e3f1ff;
  color: #1f5f9e;
}

.lifecycle-converted {
  background: #eaf7ec;
  color: #2c7741;
}

.lead-date {
  font-size: 12px;
  color: #7a90a3;
}

.lead-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin: 18px 0;
}

.stat-tile {
  padding: 14px 16px;
  border-radius: 18px;
  background: #f7fafc;
  border: 1px solid #e0e8f1;
}

.tile-label {
  display: block;
  font-size: 11px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #72869a;
}

.tile-value {
  display: block;
  margin-top: 8px;
  font-size: 18px;
  color: #173956;
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

.status-controls {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
}

.edit-lead-button {
  min-height: 36px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid #d9c39a;
  background: #fff7ea;
  color: #9c661b;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.edit-lead-button:hover:not(:disabled) {
  border-color: #9c661b;
  background: #f9ebcf;
}

.edit-lead-active {
  background: #9c661b;
  border-color: #9c661b;
  color: #ffffff;
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
  border-color: #9c661b;
  color: #9c661b;
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
  .lead-card-head,
  .lead-actions,
  .panel-header,
  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .hero-stats,
  .lead-status {
    align-items: flex-start;
  }

  .field-grid,
  .lead-stats,
  .form-insights {
    grid-template-columns: 1fr;
  }

  .panel-note {
    max-width: none;
    text-align: left;
  }
}
</style>
