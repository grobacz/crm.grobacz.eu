<template>
  <div class="settings-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Configuration</p>
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">
          Manage company details, regional preferences, and application behavior.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Settings</span>
          <strong class="stat-value">{{ settings.length }}</strong>
        </div>
        <router-link to="/settings/users" class="btn btn-secondary">Manage Users</router-link>
      </div>
    </section>

    <div v-if="loadError" class="alert alert-error">{{ loadError }}</div>

    <div class="content-grid">
      <section class="panel" v-for="group in settingGroups" :key="group.title">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">{{ group.kicker }}</p>
            <h2>{{ group.title }}</h2>
          </div>
        </div>

        <form class="settings-form" @submit.prevent="saveGroup(group)">
          <div v-for="setting in group.settings" :key="setting.settingKey" class="setting-field">
            <label>
              <span class="field-label">{{ formatLabel(setting.settingKey) }}</span>
              <input
                v-model="formValues[setting.settingKey]"
                type="text"
                class="field-input"
              >
            </label>
            <p v-if="setting.description" class="field-hint">{{ setting.description }}</p>
          </div>

          <div v-if="groupErrors[group.title]" class="alert alert-error">
            {{ groupErrors[group.title] }}
          </div>
          <div v-if="groupSuccess[group.title]" class="alert alert-success">
            {{ groupSuccess[group.title] }}
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="savingGroup === group.title">
              {{ savingGroup === group.title ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { graphqlRequest } from '../graphql/client'

const SETTINGS_QUERY = `
  query Settings {
    settings {
      id
      settingKey
      settingValue
      description
    }
  }
`

const UPDATE_SETTINGS_MUTATION = `
  mutation UpdateSettings($settings: [SettingInput!]!) {
    updateSettings(settings: $settings) {
      id
      settingKey
      settingValue
    }
  }
`

const COMPANY_KEYS = ['company_name', 'company_email']
const REGIONAL_KEYS = ['default_currency', 'date_format', 'timezone']
const APP_KEYS = ['items_per_page']

const settings = ref([])
const loadError = ref('')
const savingGroup = ref('')
const groupErrors = reactive({})
const groupSuccess = reactive({})
const formValues = reactive({})

const settingGroups = computed(() => {
  const findByKey = (key) => settings.value.find(s => s.settingKey === key)

  return [
    {
      title: 'Company',
      kicker: 'Identity',
      keys: COMPANY_KEYS,
      settings: COMPANY_KEYS.map(findByKey).filter(Boolean)
    },
    {
      title: 'Regional',
      kicker: 'Locale',
      keys: REGIONAL_KEYS,
      settings: REGIONAL_KEYS.map(findByKey).filter(Boolean)
    },
    {
      title: 'Application',
      kicker: 'Behavior',
      keys: APP_KEYS,
      settings: APP_KEYS.map(findByKey).filter(Boolean)
    }
  ].filter(g => g.settings.length > 0)
})

function formatLabel(key) {
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}

async function loadSettings() {
  try {
    const data = await graphqlRequest(SETTINGS_QUERY)
    settings.value = data.settings || []
    settings.value.forEach(s => {
      formValues[s.settingKey] = s.settingValue
    })
  } catch (error) {
    loadError.value = 'Failed to load settings.'
  }
}

async function saveGroup(group) {
  savingGroup.value = group.title
  groupErrors[group.title] = ''
  groupSuccess[group.title] = ''

  try {
    const input = group.settings.map(s => ({
      key: s.settingKey,
      value: formValues[s.settingKey] || ''
    }))

    await graphqlRequest(UPDATE_SETTINGS_MUTATION, { settings: input })
    groupSuccess[group.title] = 'Settings saved.'
  } catch (error) {
    groupErrors[group.title] = 'Failed to save settings.'
  } finally {
    savingGroup.value = ''
  }
}

onMounted(loadSettings)
</script>

<style scoped>
.settings-page {
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
    linear-gradient(135deg, #2d3748 0%, #4a5568 44%, #718096 100%);
  color: #fff;
  box-shadow: 0 22px 48px rgba(45, 55, 72, 0.22);
}

.hero-copy { max-width: 640px; }

.eyebrow, .panel-kicker {
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
  color: rgba(255, 255, 255, 0.86);
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
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 24px;
  align-items: start;
}

.panel {
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid #d7e2ee;
  box-shadow: 0 18px 36px rgba(15, 43, 69, 0.08);
  padding: 24px;
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

.settings-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.setting-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-label {
  font-size: 13px;
  font-weight: 600;
  color: #173956;
}

.field-input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #cdd8e5;
  background: #ffffff;
  color: #173956;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
}

.field-hint {
  font-size: 12px;
  color: #7a90a3;
  margin: 0;
}

.alert {
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
  justify-content: flex-end;
  margin-top: 8px;
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
  text-decoration: none;
  transition: transform 0.15s ease, opacity 0.2s ease;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(102, 126, 234, 0.26);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.18);
  color: inherit;
  border: 1px solid rgba(255, 255, 255, 0.26);
}
</style>
