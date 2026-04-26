<template>
  <div class="dashboard">
    <div class="content-header">
      <h1 class="page-title">Dashboard Overview</h1>
      <div class="quick-actions">
        <button class="btn btn-outline" @click="$router.push('/customers')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add Customer
        </button>
        <button class="btn btn-primary" @click="$router.push('/campaigns/email')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          New Campaign
        </button>
      </div>
    </div>

    <div v-if="loadError" class="dashboard-alert">
      {{ loadError }}
    </div>

    <div class="dashboard-grid">
      <div class="card" v-for="stat in stats" :key="stat.title">
        <div class="card-header">
          <span class="card-title">{{ stat.title }}</span>
          <div class="card-icon" :class="stat.bgClass">
            <component :is="stat.icon" />
          </div>
        </div>
        <div class="stat-number">{{ isLoading ? '...' : stat.value }}</div>
        <div class="stat-change" :class="stat.changeType">
          {{ isLoading ? 'Loading live counts...' : stat.change }}
        </div>
      </div>
    </div>

    <div class="activity-list">
      <div class="card-header">
        <span class="card-title">Recent Activity</span>
      </div>
      
      <div class="activity-item" v-for="activity in recentActivities" :key="activity.id">
        <div class="activity-icon" :class="activity.bgClass">
          <component :is="activity.icon" />
        </div>
        <div class="activity-content">
          <div class="activity-text">{{ activity.text }}</div>
          <div class="activity-time">{{ activity.time }}</div>
        </div>
      </div>

      <div v-if="!isLoading && recentActivities.length === 0" class="activity-empty">
        No activity has been recorded yet.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { graphqlRequest } from '../graphql/client'

const UsersIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', width: '24', height: '24' }, [
  h('path', { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }),
  h('circle', { cx: '9', cy: '7', r: '4' }),
  h('path', { d: 'M23 21v-2a4 4 0 0 0-3-3.87' }),
  h('path', { d: 'M16 3.13a4 4 0 0 1 0 7.75' })
])

const LeadsIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', width: '24', height: '24' }, [
  h('path', { d: 'M16 18l6-6-6-6' }),
  h('path', { d: 'M8 6H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2' })
])

const WarningIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', width: '24', height: '24' }, [
  h('circle', { cx: '12', cy: '12', r: '10' }),
  h('circle', { cx: '12', cy: '12', r: '4' }),
  h('line', { x1: '12', y1: '8', x2: '12', y2: '12' }),
  h('line', { x1: '12', y1: '16', x2: '12.01', y2: '16' })
])

const PhoneIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', width: '24', height: '24' }, [
  h('path', { d: 'M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.89.33 1.76.61 2.59a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6.09 6.09l1.49-1.27a2 2 0 0 1 2.11-.45c.83.28 1.7.49 2.59.61A2 2 0 0 1 22 16.92z' })
])

const UserIcon = () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', width: '24', height: '24' }, [
  h('path', { d: 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2' }),
  h('circle', { cx: '12', cy: '7', r: '4' })
])

const DASHBOARD_COUNTS_QUERY = `
  query DashboardOverview($activityLimit: Int!) {
    customerCount
    contactCount
    dealCount
    recentActivities(limit: $activityLimit) {
      id
      entityType
      message
      createdAt
    }
  }
`

const counts = ref({
  customerCount: 0,
  contactCount: 0,
  dealCount: 0
})
const isLoading = ref(true)
const loadError = ref('')
const recentActivities = ref([])

const stats = computed(() => [
  {
    title: 'Total Customers',
    value: counts.value.customerCount.toLocaleString(),
    change: 'Live count from CRM records',
    changeType: 'neutral',
    bgClass: 'bg-blue',
    icon: UsersIcon
  },
  {
    title: 'Total Contacts',
    value: counts.value.contactCount.toLocaleString(),
    change: 'Live count from CRM records',
    changeType: 'neutral',
    bgClass: 'bg-green',
    icon: UserIcon
  },
  {
    title: 'Total Deals',
    value: counts.value.dealCount.toLocaleString(),
    change: 'Live count from CRM records',
    changeType: 'neutral',
    bgClass: 'bg-orange',
    icon: LeadsIcon
  }
])

const activityDisplayMap = {
  customer: {
    icon: UsersIcon,
    bgClass: 'bg-purple'
  },
  contact: {
    icon: UserIcon,
    bgClass: 'bg-info'
  },
  deal: {
    icon: LeadsIcon,
    bgClass: 'bg-warning'
  },
  call: {
    icon: PhoneIcon,
    bgClass: 'bg-green'
  },
  campaign: {
    icon: LeadsIcon,
    bgClass: 'bg-blue'
  }
}

function formatRelativeTime(value) {
  const timestamp = new Date(value)
  const diffMs = Date.now() - timestamp.getTime()

  if (Number.isNaN(timestamp.getTime())) {
    return ''
  }

  const minute = 60 * 1000
  const hour = 60 * minute
  const day = 24 * hour

  if (diffMs < minute) {
    return 'Just now'
  }

  if (diffMs < hour) {
    const minutes = Math.floor(diffMs / minute)
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`
  }

  if (diffMs < day) {
    const hours = Math.floor(diffMs / hour)
    return `${hours} hour${hours === 1 ? '' : 's'} ago`
  }

  const days = Math.floor(diffMs / day)
  return `${days} day${days === 1 ? '' : 's'} ago`
}

function mapActivity(activity) {
  const display = activityDisplayMap[activity.entityType] ?? {
    icon: WarningIcon,
    bgClass: 'bg-warning'
  }

  return {
    id: activity.id,
    text: activity.message,
    time: formatRelativeTime(activity.createdAt),
    bgClass: display.bgClass,
    icon: display.icon
  }
}

onMounted(async () => {
  try {
    const data = await graphqlRequest(DASHBOARD_COUNTS_QUERY, { activityLimit: 8 })
    counts.value = {
      customerCount: data.customerCount ?? 0,
      contactCount: data.contactCount ?? 0,
      dealCount: data.dealCount ?? 0
    }
    recentActivities.value = (data.recentActivities ?? []).map(mapActivity)
  } catch (error) {
    loadError.value = 'Unable to load dashboard metrics.'
  } finally {
    isLoading.value = false
  }
})
</script>

<style scoped>
.dashboard {
  padding: 0;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-title {
  font-size: 24px;
  color: #2c3e50;
}

.quick-actions {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-outline {
  background: transparent;
  border: 2px solid #667eea;
  color: #667eea;
}

.btn-outline:hover {
  background: #667eea;
  color: white;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.dashboard-alert {
  margin-bottom: 20px;
  padding: 14px 16px;
  border: 1px solid #f5c2c7;
  border-radius: 12px;
  background: #f8d7da;
  color: #842029;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border: 1px solid #e9ecef;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  color: #2c3e50;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.bg-blue { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.bg-green { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.bg-orange { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.bg-red { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

.stat-number {
  font-size: 32px;
  font-weight: bold;
  color: #2c3e50;
}

.stat-change {
  font-size: 12px;
  margin-top: 5px;
}

.stat-change.positive {
  color: #28a745;
}

.stat-change.negative {
  color: #dc3545;
}

.stat-change.neutral {
  color: #6c757d;
}

.activity-list {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.activity-item {
  display: flex;
  gap: 15px;
  padding: 15px 0;
  border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-purple { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.bg-info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
.bg-warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

.activity-content {
  flex: 1;
}

.activity-text {
  color: #2c3e50;
  line-height: 1.5;
}

.activity-time {
  font-size: 12px;
  color: #6c757d;
  margin-top: 5px;
}

.activity-empty {
  color: #6c757d;
  padding: 12px 0 4px;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
