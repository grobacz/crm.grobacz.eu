<template>
  <div class="campaign-calls-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Campaigns</p>
        <h1 class="page-title">Call Logs</h1>
        <p class="page-subtitle">
          Track the live calling queue and review completed customer and lead calls from one place.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Logged calls</span>
          <strong class="stat-value">{{ callLogs.length }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" :disabled="isLoadingLogs" @click="refreshCallLogs">
          {{ isLoadingLogs ? 'Refreshing...' : 'Refresh logs' }}
        </button>
      </div>
    </section>

    <div v-if="loadError" class="alert alert-error">
      {{ loadError }}
    </div>

    <section v-if="activeCall" class="panel active-call-panel">
      <div class="panel-header">
        <div>
          <p class="panel-kicker">Live call</p>
          <h2>{{ activeCall.targetName }}</h2>
        </div>
        <span class="panel-note">
          {{ activeCall.targetType }} · {{ formatPhone(activeCall.targetPhone) }}
        </span>
      </div>

      <div class="active-call-grid">
        <div class="active-call-card">
          <span class="card-label">Started</span>
          <strong>{{ formatDateTime(activeCall.startedAt) }}</strong>
        </div>
        <div class="active-call-card">
          <span class="card-label">Elapsed</span>
          <strong>{{ formatDuration(activeCallDurationSeconds) }}</strong>
        </div>
        <div class="active-call-card">
          <span class="card-label">Record type</span>
          <strong class="type-pill" :class="`type-pill-${activeCall.targetType}`">{{ activeCall.targetType }}</strong>
        </div>
      </div>

      <div class="active-call-actions">
        <button
          class="btn btn-primary stop-call-button"
          type="button"
          :disabled="stoppingCallId === activeCall.id"
          @click="handleStopCall"
        >
          {{ stoppingCallId === activeCall.id ? 'Stopping call...' : 'Stop call' }}
        </button>
      </div>
    </section>

    <section class="panel">
      <div class="panel-header">
        <div>
          <p class="panel-kicker">History</p>
          <h2>Recent call logs</h2>
        </div>
        <span class="panel-note">
          {{ activeCall ? 'One active call in progress.' : 'No active call in progress.' }}
        </span>
      </div>

      <div v-if="isLoadingLogs" class="list-state">
        Loading call logs...
      </div>

      <div v-else-if="callLogs.length === 0" class="list-state empty-state">
        <strong>No call logs yet.</strong>
        <span>Start a call from the leads or customers list to create the first log.</span>
      </div>

      <div v-else class="call-log-list">
        <article v-for="callLog in callLogs" :key="callLog.id" class="call-log-card">
          <div class="call-log-head">
            <div>
              <div class="call-log-title-row">
                <h3>{{ callLog.targetName }}</h3>
                <span class="type-pill" :class="`type-pill-${callLog.targetType}`">{{ callLog.targetType }}</span>
                <span v-if="callLog.isActive" class="live-pill">Live</span>
              </div>
              <p class="call-log-subtitle">
                <a :href="`tel:${callLog.targetPhone}`">{{ formatPhone(callLog.targetPhone) }}</a>
              </p>
            </div>
            <div class="call-log-meta">
              <span>{{ formatDateTime(callLog.startedAt) }}</span>
              <span>{{ callLog.isActive ? 'In progress' : formatDuration(callLog.durationSeconds) }}</span>
            </div>
          </div>

          <div class="call-log-timeline">
            <div class="timeline-item">
              <span class="timeline-label">Started</span>
              <strong>{{ formatDateTime(callLog.startedAt) }}</strong>
            </div>
            <div class="timeline-item">
              <span class="timeline-label">Ended</span>
              <strong>{{ callLog.endedAt ? formatDateTime(callLog.endedAt) : 'Still active' }}</strong>
            </div>
            <div class="timeline-item">
              <span class="timeline-label">Duration</span>
              <strong>{{ callLog.isActive ? formatDuration(activeCallDurationSeconds) : formatDuration(callLog.durationSeconds) }}</strong>
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCallCenterStore } from '../store/callCenter'

const callCenterStore = useCallCenterStore()
const { activeCall, callLogs, isLoadingLogs, stoppingCallId } = storeToRefs(callCenterStore)

const loadError = ref('')
const elapsedSeconds = ref(0)
let elapsedTimer = null

const activeCallDurationSeconds = computed(() => {
  if (!activeCall.value) {
    return 0
  }

  return elapsedSeconds.value
})

function updateElapsedSeconds() {
  if (!activeCall.value?.startedAt) {
    elapsedSeconds.value = 0
    return
  }

  const startedAt = new Date(activeCall.value.startedAt)
  const startedAtMs = startedAt.getTime()

  if (Number.isNaN(startedAtMs)) {
    elapsedSeconds.value = 0
    return
  }

  elapsedSeconds.value = Math.max(0, Math.floor((Date.now() - startedAtMs) / 1000))
}

function startElapsedTimer() {
  stopElapsedTimer()
  updateElapsedSeconds()

  if (!activeCall.value) {
    return
  }

  elapsedTimer = window.setInterval(updateElapsedSeconds, 1000)
}

function stopElapsedTimer() {
  if (elapsedTimer !== null) {
    window.clearInterval(elapsedTimer)
    elapsedTimer = null
  }
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

function formatDuration(durationSeconds) {
  if (typeof durationSeconds !== 'number' || durationSeconds < 0) {
    return '0s'
  }

  const hours = Math.floor(durationSeconds / 3600)
  const minutes = Math.floor((durationSeconds % 3600) / 60)
  const seconds = durationSeconds % 60

  if (hours > 0) {
    return `${hours}h ${minutes}m ${seconds}s`
  }

  if (minutes > 0) {
    return `${minutes}m ${seconds}s`
  }

  return `${seconds}s`
}

function formatPhone(value) {
  return value || 'No phone on file'
}

async function refreshCallLogs() {
  loadError.value = ''

  try {
    await Promise.all([
      callCenterStore.loadActiveCall(true),
      callCenterStore.loadCallLogs(50, true)
    ])

    startElapsedTimer()
  } catch (error) {
    loadError.value = error?.response?.errors?.[0]?.message || 'Unable to load call logs right now.'
  }
}

async function handleStopCall() {
  loadError.value = ''

  try {
    await callCenterStore.stopCall()
    startElapsedTimer()
  } catch (error) {
    loadError.value = error?.response?.errors?.[0]?.message || 'Unable to stop the active call right now.'
  }
}

onMounted(async () => {
  await refreshCallLogs()
})

onBeforeUnmount(() => {
  stopElapsedTimer()
})
</script>

<style scoped>
.campaign-calls-page {
  display: grid;
  gap: 24px;
}

.hero-card,
.panel {
  border-radius: 28px;
  border: 1px solid #dbe5ef;
  background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.hero-card {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 28px;
}

.hero-copy {
  max-width: 620px;
}

.eyebrow,
.panel-kicker,
.stat-label,
.card-label,
.timeline-label {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 11px;
  font-weight: 700;
  color: #6a7f92;
}

.page-title,
.panel h2 {
  color: #17324d;
}

.page-title {
  margin-top: 10px;
  font-size: 34px;
}

.page-subtitle {
  margin-top: 10px;
  color: #5b748c;
  line-height: 1.6;
}

.hero-stats {
  display: grid;
  gap: 12px;
  align-content: start;
  min-width: 220px;
}

.stat-chip {
  padding: 16px 18px;
  border-radius: 20px;
  background: #eff6fd;
  border: 1px solid #d8e6f4;
}

.stat-value {
  display: block;
  margin-top: 6px;
  font-size: 28px;
  color: #17324d;
}

.btn {
  min-height: 46px;
  padding: 0 18px;
  border: none;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.btn-secondary {
  background: #eef5fb;
  color: #214f77;
}

.btn-primary {
  background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
  color: #ffffff;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
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

.panel {
  padding: 24px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.panel-note {
  color: #5e768d;
  font-size: 14px;
}

.active-call-grid,
.call-log-timeline {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.active-call-grid {
  margin: 20px 0;
}

.active-call-card,
.timeline-item {
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid #dce7f1;
  background: #fbfdff;
}

.active-call-card strong,
.timeline-item strong {
  display: block;
  margin-top: 8px;
  color: #17324d;
  font-size: 18px;
}

.type-pill,
.live-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.type-pill-customer {
  background: #e7f0fb;
  color: #1c61b4;
}

.type-pill-lead {
  background: #fff1da;
  color: #9c661b;
}

.live-pill {
  background: #e8fbef;
  color: #0f8a5f;
}

.active-call-actions {
  display: flex;
  justify-content: flex-end;
}

.stop-call-button {
  min-width: 160px;
}

.list-state {
  display: grid;
  place-items: center;
  min-height: 220px;
  text-align: center;
  border: 1px dashed #d2deea;
  border-radius: 20px;
  background: #fbfdff;
  color: #5e768d;
}

.empty-state strong {
  color: #17324d;
}

.call-log-list {
  display: grid;
  gap: 16px;
}

.call-log-card {
  padding: 20px;
  border-radius: 22px;
  border: 1px solid #dbe5ef;
  background: #ffffff;
}

.call-log-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 18px;
}

.call-log-title-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.call-log-title-row h3 {
  color: #17324d;
  font-size: 20px;
}

.call-log-subtitle {
  margin-top: 8px;
}

.call-log-subtitle a {
  color: #1e5a89;
  text-decoration: none;
}

.call-log-meta {
  display: grid;
  justify-items: end;
  gap: 6px;
  color: #5e768d;
  font-size: 14px;
}

@media (max-width: 960px) {
  .hero-card,
  .panel-header,
  .call-log-head {
    flex-direction: column;
  }

  .active-call-grid,
  .call-log-timeline {
    grid-template-columns: 1fr;
  }

  .call-log-meta {
    justify-items: start;
  }
}
</style>
