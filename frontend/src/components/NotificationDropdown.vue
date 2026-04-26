<template>
  <div class="notification-dropdown" ref="dropdownRef">
    <div class="notification-trigger" @click="toggleDropdown">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
      </svg>
      <span class="notification-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
    </div>

    <div v-if="isOpen" class="notification-panel">
      <div class="panel-header-row">
        <span class="panel-title">Notifications</span>
        <button
          v-if="unreadCount > 0"
          class="mark-all-btn"
          @click="handleMarkAllRead"
        >
          Mark all read
        </button>
      </div>

      <div v-if="isLoading" class="panel-state">Loading...</div>

      <div v-else-if="notifications.length === 0" class="panel-state">
        No notifications yet.
      </div>

      <div v-else class="notification-list">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'notification-unread': !notification.isRead }"
          @click="handleClick(notification)"
        >
          <div class="notification-dot" v-if="!notification.isRead"></div>
          <div class="notification-content">
            <p class="notification-message">
              {{ notification.activity?.message || 'New activity' }}
            </p>
            <span class="notification-time">{{ formatTime(notification.createdAt) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationStore } from '../store/notification'

const notificationStore = useNotificationStore()
const { notifications, unreadCount, isLoading } = storeToRefs(notificationStore)

const isOpen = ref(false)
const dropdownRef = ref(null)

function toggleDropdown() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    notificationStore.loadNotifications()
  }
}

async function handleClick(notification) {
  if (!notification.isRead) {
    await notificationStore.markAsRead(notification.id)
  }
}

async function handleMarkAllRead() {
  await notificationStore.markAllAsRead()
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now - date
  const diffMinutes = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMinutes < 1) return 'Just now'
  if (diffMinutes < 60) return `${diffMinutes}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(date)
}

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.notification-dropdown {
  position: relative;
}

.notification-trigger {
  position: relative;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  transition: background 0.3s;
}

.notification-trigger:hover {
  background: rgba(255, 255, 255, 0.2);
}

.notification-trigger svg {
  width: 20px;
  height: 20px;
  color: white;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ff4757;
  color: white;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 10px;
  font-weight: bold;
  line-height: 1;
}

.notification-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: -20px;
  width: 360px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
}

.panel-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-bottom: 1px solid #e8eef4;
}

.panel-title {
  font-size: 14px;
  font-weight: 700;
  color: #173956;
}

.mark-all-btn {
  font-size: 12px;
  color: #667eea;
  background: none;
  border: none;
  cursor: pointer;
  font-weight: 600;
}

.mark-all-btn:hover {
  text-decoration: underline;
}

.panel-state {
  padding: 40px 16px;
  text-align: center;
  color: #6a7f92;
  font-size: 14px;
}

.notification-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.15s;
  border-bottom: 1px solid #f0f4f8;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item:hover {
  background: #f8fafc;
}

.notification-unread {
  background: #f0f5ff;
}

.notification-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #667eea;
  margin-top: 6px;
  flex-shrink: 0;
}

.notification-content {
  min-width: 0;
  flex: 1;
}

.notification-message {
  font-size: 13px;
  color: #173956;
  line-height: 1.4;
  margin: 0;
  word-break: break-word;
}

.notification-time {
  font-size: 11px;
  color: #7a90a3;
  margin-top: 4px;
  display: block;
}
</style>
