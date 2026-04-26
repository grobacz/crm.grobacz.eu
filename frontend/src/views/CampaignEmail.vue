<template>
  <div class="campaign-email-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Campaigns</p>
        <h1 class="page-title">Email Campaigns</h1>
        <p class="page-subtitle">
          Build a simple outbound email campaign, target lead or customer segments, and watch the worker process move each recipient through the simulated delivery lifecycle.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Campaigns</span>
          <strong class="stat-value">{{ campaigns.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" :disabled="isRefreshing" @click="refreshPage">
          {{ isRefreshing ? 'Refreshing...' : 'Refresh campaigns' }}
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
            <p class="panel-kicker">New campaign</p>
            <h2>Create campaign</h2>
          </div>
          <span class="panel-note">
            Targets only include records that have an email address on file.
          </span>
        </div>

        <form class="campaign-form" novalidate @submit.prevent="submitCampaign">
          <label class="field" :class="{ 'field-has-error': formErrors.name }">
            <span>Campaign name</span>
            <input
              v-model.trim="form.name"
              type="text"
              maxlength="160"
              placeholder="April re-engagement"
              @input="clearFieldError('name')"
            >
            <small v-if="formErrors.name" class="field-error">{{ formErrors.name }}</small>
          </label>

          <label class="field" :class="{ 'field-has-error': formErrors.subject }">
            <span>Email subject</span>
            <input
              v-model.trim="form.subject"
              type="text"
              maxlength="255"
              placeholder="A quick update for your account"
              @input="clearFieldError('subject')"
            >
            <small v-if="formErrors.subject" class="field-error">{{ formErrors.subject }}</small>
          </label>

          <div class="field-grid">
            <label class="field" :class="{ 'field-has-error': formErrors.targetType }">
              <span>Target group</span>
              <select v-model="form.targetType" @change="handleTargetTypeChange">
                <option v-for="option in targetTypeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <small v-if="formErrors.targetType" class="field-error">{{ formErrors.targetType }}</small>
            </label>

            <label class="field" :class="{ 'field-has-error': formErrors.targetSegment }">
              <span>Target segment</span>
              <select v-model="form.targetSegment" @change="clearFieldError('targetSegment')">
                <option v-for="option in segmentOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <small v-if="formErrors.targetSegment" class="field-error">{{ formErrors.targetSegment }}</small>
            </label>
          </div>

          <label class="field" :class="{ 'field-has-error': formErrors.content }">
            <span>Email body</span>
            <textarea
              v-model.trim="form.content"
              rows="8"
              placeholder="Write the campaign message that will be simulated for the selected audience."
              @input="clearFieldError('content')"
            />
            <small v-if="formErrors.content" class="field-error">{{ formErrors.content }}</small>
          </label>

          <div v-if="submitError" class="alert alert-error">
            {{ submitError }}
          </div>

          <div v-if="submitSuccess" class="alert alert-success">
            {{ submitSuccess }}
          </div>

          <div class="form-actions">
            <p class="form-helper">
              The worker will start moving recipients from `new` to `sending`, `opened`, and `completed` automatically.
            </p>
            <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
              {{ isSubmitting ? 'Creating campaign...' : 'Create campaign' }}
            </button>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Campaign queue</p>
            <h2>Recent email campaigns</h2>
          </div>
          <span class="panel-note">
            {{ campaigns.length === 0 ? 'No campaigns yet.' : `${campaigns.length} campaign${campaigns.length === 1 ? '' : 's'} loaded` }}
          </span>
        </div>

        <div v-if="isLoadingList" class="list-state">
          Loading campaigns...
        </div>

        <div v-else-if="campaigns.length === 0" class="list-state empty-state">
          <strong>No email campaigns yet.</strong>
          <span>Create the first campaign using the form on the left.</span>
        </div>

        <div v-else class="campaign-list">
          <article
            v-for="campaign in campaigns"
            :key="campaign.id"
            class="campaign-card"
            :class="{ 'campaign-card-selected': selectedCampaignId === campaign.id }"
            @click="selectCampaign(campaign.id)"
          >
            <div class="campaign-card-head">
              <div>
                <div class="campaign-title-row">
                  <h3>{{ campaign.name }}</h3>
                  <span class="status-pill" :class="`status-${campaign.status}`">{{ campaign.status }}</span>
                </div>
                <p class="campaign-subtitle">{{ campaign.subject }}</p>
              </div>
              <div class="campaign-meta">
                <span>{{ formatDateTime(campaign.createdAt) }}</span>
                <span>{{ campaign.recipientCount }} recipients</span>
              </div>
            </div>

            <div class="campaign-audience">
              <span class="audience-pill">{{ campaign.targetType }}</span>
              <span class="audience-pill audience-pill-segment">{{ getSegmentLabel(campaign.targetType, campaign.targetSegment) }}</span>
            </div>

            <div class="stacked-progress" aria-hidden="true">
              <span class="progress-new" :style="{ width: getProgressWidth(campaign.newCount, campaign.recipientCount) }" />
              <span class="progress-sending" :style="{ width: getProgressWidth(campaign.sendingCount, campaign.recipientCount) }" />
              <span class="progress-opened" :style="{ width: getProgressWidth(campaign.openedCount, campaign.recipientCount) }" />
              <span class="progress-completed" :style="{ width: getProgressWidth(campaign.completedCount, campaign.recipientCount) }" />
            </div>

            <div class="campaign-count-grid">
              <div class="count-card">
                <span class="count-label">New</span>
                <strong>{{ campaign.newCount }}</strong>
              </div>
              <div class="count-card">
                <span class="count-label">Sending</span>
                <strong>{{ campaign.sendingCount }}</strong>
              </div>
              <div class="count-card">
                <span class="count-label">Opened</span>
                <strong>{{ campaign.openedCount }}</strong>
              </div>
              <div class="count-card">
                <span class="count-label">Completed</span>
                <strong>{{ campaign.completedCount }}</strong>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>

    <section class="panel detail-panel">
      <div class="panel-header">
        <div>
          <p class="panel-kicker">Campaign progress</p>
          <h2>{{ selectedCampaign ? selectedCampaign.name : 'Select a campaign' }}</h2>
        </div>
        <span v-if="selectedCampaign" class="panel-note">
          {{ selectedCampaign.recipientCount }} recipients · {{ selectedCampaign.progressPercent }}% completed
        </span>
      </div>

      <div v-if="isLoadingDetail" class="list-state">
        Loading campaign detail...
      </div>

      <div v-else-if="!selectedCampaign" class="list-state empty-state">
        <strong>No campaign selected.</strong>
        <span>Choose a campaign from the list to inspect recipient progress.</span>
      </div>

      <div v-else class="campaign-detail">
        <div class="detail-summary-grid">
          <div class="summary-card">
            <span class="summary-label">Status</span>
            <strong class="status-pill" :class="`status-${selectedCampaign.status}`">{{ selectedCampaign.status }}</strong>
          </div>
          <div class="summary-card">
            <span class="summary-label">Target</span>
            <strong>{{ selectedCampaign.targetType }} / {{ getSegmentLabel(selectedCampaign.targetType, selectedCampaign.targetSegment) }}</strong>
          </div>
          <div class="summary-card">
            <span class="summary-label">Started</span>
            <strong>{{ selectedCampaign.startedAt ? formatDateTime(selectedCampaign.startedAt) : 'Waiting for worker' }}</strong>
          </div>
          <div class="summary-card">
            <span class="summary-label">Completed</span>
            <strong>{{ selectedCampaign.completedAt ? formatDateTime(selectedCampaign.completedAt) : 'In progress' }}</strong>
          </div>
        </div>

        <div class="detail-copy-grid">
          <div class="message-card">
            <span class="summary-label">Subject</span>
            <strong>{{ selectedCampaign.subject }}</strong>
            <p>{{ selectedCampaign.content }}</p>
          </div>
          <div class="message-card">
            <span class="summary-label">Recipient totals</span>
            <div class="progress-metric" v-for="metric in progressMetrics" :key="metric.key">
              <div class="progress-metric-head">
                <span>{{ metric.label }}</span>
                <strong>{{ metric.value }}</strong>
              </div>
              <div class="progress-track">
                <span class="progress-fill" :class="metric.className" :style="{ width: getProgressWidth(metric.value, selectedCampaign.recipientCount) }" />
              </div>
            </div>
          </div>
        </div>

        <div class="recipient-list-header">
          <h3>Chosen target list</h3>
          <span>{{ selectedCampaign.recipients.length }} recipients</span>
        </div>

        <div class="recipient-list">
          <article v-for="recipient in selectedCampaign.recipients" :key="recipient.id" class="recipient-card">
            <div class="recipient-card-head">
              <div>
                <h4>{{ recipient.recipientName }}</h4>
                <p>{{ recipient.recipientEmail }}</p>
              </div>
              <span class="status-pill" :class="`status-${recipient.status}`">{{ recipient.status }}</span>
            </div>

            <div class="recipient-meta-grid">
              <div class="meta-item">
                <span class="meta-label">Queued</span>
                <strong>{{ formatDateTime(recipient.createdAt) }}</strong>
              </div>
              <div class="meta-item">
                <span class="meta-label">Sent</span>
                <strong>{{ recipient.sentAt ? formatDateTime(recipient.sentAt) : 'Pending' }}</strong>
              </div>
              <div class="meta-item">
                <span class="meta-label">Opened</span>
                <strong>{{ recipient.openedAt ? formatDateTime(recipient.openedAt) : 'Pending' }}</strong>
              </div>
              <div class="meta-item">
                <span class="meta-label">Completed</span>
                <strong>{{ recipient.completedAt ? formatDateTime(recipient.completedAt) : 'Pending' }}</strong>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { graphqlRequest } from '../graphql/client'

const CAMPAIGN_LIST_FIELDS = `
  id
  name
  subject
  targetType
  targetSegment
  status
  createdAt
  startedAt
  completedAt
  recipientCount
  newCount
  sendingCount
  openedCount
  completedCount
  progressPercent
`

const CAMPAIGN_DETAIL_FIELDS = `
  ${CAMPAIGN_LIST_FIELDS}
  content
  recipients {
    id
    recipientType
    recipientId
    recipientName
    recipientEmail
    status
    createdAt
    updatedAt
    sentAt
    openedAt
    completedAt
  }
`

const EMAIL_CAMPAIGNS_QUERY = `
  query EmailCampaignsPage($limit: Int!) {
    emailCampaigns(limit: $limit) {
      ${CAMPAIGN_LIST_FIELDS}
    }
  }
`

const EMAIL_CAMPAIGN_QUERY = `
  query EmailCampaignDetail($id: String!) {
    emailCampaign(id: $id) {
      ${CAMPAIGN_DETAIL_FIELDS}
    }
  }
`

const CREATE_EMAIL_CAMPAIGN_MUTATION = `
  mutation CreateEmailCampaign(
    $name: String!
    $subject: String!
    $content: String!
    $targetType: String!
    $targetSegment: String!
  ) {
    createEmailCampaign(
      name: $name
      subject: $subject
      content: $content
      targetType: $targetType
      targetSegment: $targetSegment
    ) {
      ${CAMPAIGN_DETAIL_FIELDS}
    }
  }
`

const TARGET_SEGMENT_OPTIONS = {
  lead: [
    { value: 'new', label: 'New leads' },
    { value: 'qualified', label: 'Qualified leads' },
    { value: 'converted', label: 'Converted leads' }
  ],
  customer: [
    { value: 'active', label: 'Active customers' },
    { value: 'inactive', label: 'Inactive customers' },
    { value: 'vip', label: 'VIP customers' }
  ]
}

const targetTypeOptions = [
  { value: 'lead', label: 'Leads' },
  { value: 'customer', label: 'Customers' }
]

const campaigns = ref([])
const selectedCampaign = ref(null)
const selectedCampaignId = ref(null)
const isLoadingList = ref(true)
const isLoadingDetail = ref(false)
const isSubmitting = ref(false)
const isRefreshing = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const formErrors = reactive({
  name: '',
  subject: '',
  content: '',
  targetType: '',
  targetSegment: ''
})

const form = reactive({
  name: '',
  subject: '',
  content: '',
  targetType: 'lead',
  targetSegment: 'new'
})

let refreshTimer = null

const segmentOptions = computed(() => {
  return TARGET_SEGMENT_OPTIONS[form.targetType] ?? []
})

const progressMetrics = computed(() => {
  if (!selectedCampaign.value) {
    return []
  }

  return [
    { key: 'new', label: 'New', value: selectedCampaign.value.newCount, className: 'progress-new' },
    { key: 'sending', label: 'Sending', value: selectedCampaign.value.sendingCount, className: 'progress-sending' },
    { key: 'opened', label: 'Opened', value: selectedCampaign.value.openedCount, className: 'progress-opened' },
    { key: 'completed', label: 'Completed', value: selectedCampaign.value.completedCount, className: 'progress-completed' }
  ]
})

function getGraphqlErrorMessage(error, fallbackMessage) {
  return error?.response?.errors?.[0]?.message || fallbackMessage
}

function getGraphqlFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors || {}
}

function clearFieldError(field) {
  formErrors[field] = ''
}

function clearFormErrors() {
  Object.keys(formErrors).forEach((field) => {
    formErrors[field] = ''
  })
}

function resetForm() {
  form.name = ''
  form.subject = ''
  form.content = ''
  form.targetType = 'lead'
  form.targetSegment = 'new'
  clearFormErrors()
}

function handleTargetTypeChange() {
  clearFieldError('targetType')
  clearFieldError('targetSegment')
  form.targetSegment = segmentOptions.value[0]?.value ?? ''
}

function getSegmentLabel(targetType, targetSegment) {
  return TARGET_SEGMENT_OPTIONS[targetType]?.find((option) => option.value === targetSegment)?.label || targetSegment
}

function getProgressWidth(value, total) {
  if (!total) {
    return '0%'
  }

  return `${Math.max(0, Math.min(100, (value / total) * 100))}%`
}

function formatDateTime(value) {
  if (!value) {
    return 'Unavailable'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return 'Unavailable'
  }

  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

async function loadCampaigns() {
  const data = await graphqlRequest(EMAIL_CAMPAIGNS_QUERY, { limit: 20 })
  campaigns.value = data.emailCampaigns ?? []

  if (!selectedCampaignId.value && campaigns.value.length > 0) {
    selectedCampaignId.value = campaigns.value[0].id
  }

  if (selectedCampaignId.value && !campaigns.value.some((campaign) => campaign.id === selectedCampaignId.value)) {
    selectedCampaignId.value = campaigns.value[0]?.id ?? null
  }
}

async function loadSelectedCampaign() {
  if (!selectedCampaignId.value) {
    selectedCampaign.value = null
    return
  }

  isLoadingDetail.value = true

  try {
    const data = await graphqlRequest(EMAIL_CAMPAIGN_QUERY, { id: selectedCampaignId.value })
    selectedCampaign.value = data.emailCampaign ?? null
  } finally {
    isLoadingDetail.value = false
  }
}

async function refreshPage() {
  loadError.value = ''
  isRefreshing.value = true

  try {
    await loadCampaigns()
    await loadSelectedCampaign()
  } catch (error) {
    loadError.value = getGraphqlErrorMessage(error, 'Unable to load email campaigns right now.')
  } finally {
    isLoadingList.value = false
    isRefreshing.value = false
  }
}

function selectCampaign(campaignId) {
  if (selectedCampaignId.value === campaignId) {
    return
  }

  selectedCampaignId.value = campaignId
  void loadSelectedCampaign()
}

async function submitCampaign() {
  submitError.value = ''
  submitSuccess.value = ''
  clearFormErrors()
  isSubmitting.value = true

  try {
    const data = await graphqlRequest(CREATE_EMAIL_CAMPAIGN_MUTATION, {
      name: form.name,
      subject: form.subject,
      content: form.content,
      targetType: form.targetType,
      targetSegment: form.targetSegment
    })

    const createdCampaign = data.createEmailCampaign
    submitSuccess.value = `Campaign "${createdCampaign.name}" was created.`
    selectedCampaignId.value = createdCampaign.id
    selectedCampaign.value = createdCampaign
    resetForm()
    await loadCampaigns()
  } catch (error) {
    const fieldErrors = getGraphqlFieldErrors(error)
    Object.keys(formErrors).forEach((field) => {
      formErrors[field] = fieldErrors[field] || ''
    })
    submitError.value = Object.values(fieldErrors).some(Boolean)
      ? 'Please review the highlighted campaign fields and try again.'
      : getGraphqlErrorMessage(error, 'Unable to create the email campaign.')
  } finally {
    isSubmitting.value = false
  }
}

function startRefreshLoop() {
  stopRefreshLoop()
  refreshTimer = window.setInterval(() => {
    void refreshPage()
  }, 4000)
}

function stopRefreshLoop() {
  if (refreshTimer !== null) {
    window.clearInterval(refreshTimer)
    refreshTimer = null
  }
}

onMounted(async () => {
  await refreshPage()
  startRefreshLoop()
})

onBeforeUnmount(() => {
  stopRefreshLoop()
})
</script>

<style scoped>
.campaign-email-page {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.hero-card {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding: 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.86), transparent 32%),
    linear-gradient(135deg, #102647 0%, #1b4f7d 50%, #117a65 100%);
  color: #f8fbff;
  box-shadow: 0 22px 48px rgba(16, 38, 71, 0.24);
}

.hero-copy {
  max-width: 720px;
}

.eyebrow,
.panel-kicker,
.stat-label,
.summary-label,
.count-label,
.meta-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.page-title {
  margin: 8px 0 10px;
  font-size: 34px;
  line-height: 1.15;
}

.page-subtitle {
  color: rgba(248, 251, 255, 0.82);
  line-height: 1.6;
}

.hero-stats {
  min-width: 220px;
  display: grid;
  gap: 16px;
  align-content: start;
}

.stat-chip {
  padding: 18px 20px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.16);
  backdrop-filter: blur(12px);
}

.stat-value {
  display: block;
  margin-top: 10px;
  font-size: 34px;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(320px, 420px) minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.panel {
  border-radius: 24px;
  border: 1px solid #d9e4ef;
  background: #ffffff;
  box-shadow: 0 18px 36px rgba(15, 43, 69, 0.08);
}

.form-panel,
.list-panel,
.detail-panel {
  padding: 24px;
}

.form-panel {
  position: sticky;
  top: 24px;
  background:
    linear-gradient(180deg, rgba(242, 248, 255, 0.96), rgba(255, 255, 255, 1) 40%),
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
  color: #14324e;
}

.panel-note {
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
  text-align: right;
}

.campaign-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #17324d;
  font-size: 14px;
  font-weight: 600;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  border: 1px solid #d7e2ee;
  border-radius: 16px;
  padding: 13px 15px;
  font-size: 14px;
  color: #17324d;
  background: #fbfdff;
}

.field textarea {
  resize: vertical;
  min-height: 180px;
}

.field-has-error input,
.field-has-error select,
.field-has-error textarea {
  border-color: #d76e6e;
  background: #fff7f7;
}

.field-error {
  color: #b23838;
  font-size: 12px;
}

.alert {
  padding: 14px 16px;
  border-radius: 18px;
}

.alert-error {
  background: #fff1f1;
  border: 1px solid #f3cccc;
  color: #9f2d2d;
}

.alert-success {
  background: #edf9f0;
  border: 1px solid #c9ebd0;
  color: #1c7c39;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.form-helper {
  color: #5e768d;
  font-size: 13px;
  line-height: 1.5;
}

.btn {
  min-height: 44px;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(135deg, #0f766e 0%, #0f9a8f 100%);
  color: #ffffff;
}

.btn-secondary {
  background: #edf5fc;
  color: #1d5a88;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.72;
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

.campaign-list,
.recipient-list {
  display: grid;
  gap: 16px;
}

.campaign-card,
.recipient-card,
.summary-card,
.message-card,
.count-card {
  border-radius: 20px;
  border: 1px solid #dbe5ef;
  background: #fbfdff;
}

.campaign-card {
  padding: 18px;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.campaign-card:hover {
  border-color: #88abc9;
  transform: translateY(-1px);
}

.campaign-card-selected {
  border-color: #1d5a88;
  box-shadow: 0 12px 24px rgba(29, 90, 136, 0.12);
}

.campaign-card-head,
.campaign-title-row,
.recipient-card-head,
.progress-metric-head,
.recipient-list-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.campaign-title-row h3,
.recipient-card h4,
.recipient-list-header h3 {
  color: #17324d;
}

.campaign-subtitle,
.recipient-card p {
  margin-top: 8px;
  color: #5f768d;
}

.campaign-meta {
  display: grid;
  justify-items: end;
  gap: 6px;
  color: #5f768d;
  font-size: 13px;
}

.campaign-audience {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 16px 0;
}

.audience-pill,
.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.audience-pill {
  background: #eef5fb;
  color: #1d5a88;
}

.audience-pill-segment {
  background: #fff5df;
  color: #9a650f;
}

.status-new {
  background: #eef3f7;
  color: #5f7286;
}

.status-sending {
  background: #e7f0fb;
  color: #1c61b4;
}

.status-opened {
  background: #fff3de;
  color: #9a650f;
}

.status-completed {
  background: #e8f7ee;
  color: #117a43;
}

.stacked-progress,
.progress-track {
  width: 100%;
  overflow: hidden;
  border-radius: 999px;
  background: #eaf1f7;
}

.stacked-progress {
  display: flex;
  min-height: 12px;
  margin-bottom: 16px;
}

.progress-track {
  min-height: 10px;
}

.progress-fill,
.stacked-progress span {
  display: block;
  height: 100%;
}

.progress-new {
  background: #c9d5df;
}

.progress-sending {
  background: #5a9ad4;
}

.progress-opened {
  background: #e0a11c;
}

.progress-completed {
  background: #2f9d62;
}

.campaign-count-grid,
.detail-summary-grid,
.recipient-meta-grid,
.detail-copy-grid {
  display: grid;
  gap: 14px;
}

.campaign-count-grid,
.detail-summary-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.detail-copy-grid {
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
  margin: 20px 0;
}

.count-card,
.summary-card,
.message-card,
.recipient-card {
  padding: 16px;
}

.summary-card strong,
.count-card strong {
  display: block;
  margin-top: 8px;
  color: #17324d;
}

.message-card {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.message-card p {
  color: #526b83;
  line-height: 1.6;
  white-space: pre-wrap;
}

.progress-metric {
  display: grid;
  gap: 8px;
}

.progress-metric + .progress-metric {
  margin-top: 12px;
}

.recipient-list-header {
  margin-bottom: 14px;
  color: #607991;
}

.recipient-meta-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 16px;
}

.meta-item strong {
  display: block;
  margin-top: 6px;
  color: #17324d;
}

@media (max-width: 1120px) {
  .content-grid,
  .detail-copy-grid {
    grid-template-columns: 1fr;
  }

  .form-panel {
    position: static;
  }
}

@media (max-width: 860px) {
  .hero-card,
  .panel-header,
  .form-actions,
  .campaign-card-head,
  .recipient-card-head,
  .recipient-list-header {
    flex-direction: column;
  }

  .field-grid,
  .campaign-count-grid,
  .detail-summary-grid,
  .recipient-meta-grid {
    grid-template-columns: 1fr;
  }

  .campaign-meta {
    justify-items: start;
  }

  .panel-note {
    text-align: left;
  }
}
</style>
