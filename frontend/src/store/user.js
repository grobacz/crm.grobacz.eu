import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { graphqlRequest } from '../graphql/client'

const CURRENT_USER_STORAGE_KEY = 'crm.currentUserId'

const ACTIVE_USERS_QUERY = `
  query ActiveUsers {
    activeUsers {
      id
      name
      email
      role
      status
      avatarColor
      initials
    }
  }
`

export const useUserStore = defineStore('user', () => {
  const users = ref([])
  const currentUserId = ref(readStoredCurrentUserId())
  const isLoading = ref(false)

  const currentUser = computed(() => {
    return users.value.find(u => u.id === currentUserId.value) || null
  })

  async function loadUsers() {
    isLoading.value = true
    try {
      const data = await graphqlRequest(ACTIVE_USERS_QUERY)
      users.value = data.activeUsers || []

      if (!currentUser.value && users.value.length > 0) {
        setCurrentUser(users.value[0].id)
      }
    } catch (error) {
      console.error('Failed to load users:', error)
    } finally {
      isLoading.value = false
    }
  }

  function setCurrentUser(userId) {
    currentUserId.value = userId
    storeCurrentUserId(userId)
  }

  return {
    users,
    currentUserId,
    currentUser,
    isLoading,
    loadUsers,
    setCurrentUser
  }
})

function readStoredCurrentUserId() {
  return window.localStorage.getItem(CURRENT_USER_STORAGE_KEY)
}

function storeCurrentUserId(userId) {
  if (userId) {
    window.localStorage.setItem(CURRENT_USER_STORAGE_KEY, userId)
  } else {
    window.localStorage.removeItem(CURRENT_USER_STORAGE_KEY)
  }
}
