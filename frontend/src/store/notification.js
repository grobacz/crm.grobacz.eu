import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { graphqlRequest } from '../graphql/client'
import { useUserStore } from './user'

const NOTIFICATIONS_QUERY = `
  query Notifications($userId: String!, $limit: Int) {
    notifications(userId: $userId, limit: $limit) {
      id
      isRead
      createdAt
      activity {
        id
        message
        action
        entityType
        createdAt
      }
    }
  }
`

const UNREAD_COUNT_QUERY = `
  query UnreadCount($userId: String!) {
    unreadNotificationCount(userId: $userId)
  }
`

const MARK_READ_MUTATION = `
  mutation MarkRead($id: String!) {
    markNotificationRead(id: $id)
  }
`

const MARK_ALL_READ_MUTATION = `
  mutation MarkAllRead($userId: String!) {
    markAllNotificationsRead(userId: $userId)
  }
`

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const isLoading = ref(false)

  async function loadNotifications(limit = 20) {
    const userStore = useUserStore()
    if (!userStore.currentUserId) return

    isLoading.value = true
    try {
      const data = await graphqlRequest(NOTIFICATIONS_QUERY, {
        userId: userStore.currentUserId,
        limit
      })
      notifications.value = data.notifications || []
    } catch (error) {
      console.error('Failed to load notifications:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function loadUnreadCount() {
    const userStore = useUserStore()
    if (!userStore.currentUserId) return

    try {
      const data = await graphqlRequest(UNREAD_COUNT_QUERY, {
        userId: userStore.currentUserId
      })
      unreadCount.value = data.unreadNotificationCount || 0
    } catch (error) {
      console.error('Failed to load unread count:', error)
    }
  }

  async function markAsRead(notificationId) {
    try {
      await graphqlRequest(MARK_READ_MUTATION, { id: notificationId })
      notifications.value = notifications.value.map(n =>
        n.id === notificationId ? { ...n, isRead: true } : n
      )
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  async function markAllAsRead() {
    const userStore = useUserStore()
    if (!userStore.currentUserId) return

    try {
      await graphqlRequest(MARK_ALL_READ_MUTATION, { userId: userStore.currentUserId })
      notifications.value = notifications.value.map(n => ({ ...n, isRead: true }))
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error)
    }
  }

  return {
    notifications,
    unreadCount,
    isLoading,
    loadNotifications,
    loadUnreadCount,
    markAsRead,
    markAllAsRead
  }
})
