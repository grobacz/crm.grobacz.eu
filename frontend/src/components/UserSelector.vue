<template>
  <div class="user-selector" ref="selectorRef">
    <div class="user-selector-trigger" @click="toggleDropdown">
      <div class="user-avatar" :style="avatarStyle">{{ currentUser?.initials || '?' }}</div>
      <span class="user-name">{{ currentUser?.name || 'Select User' }}</span>
      <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M6 9l6 6 6-6"/>
      </svg>
    </div>

    <div v-if="isOpen" class="user-dropdown">
      <div class="dropdown-header">Switch User</div>
      <div
        v-for="user in users"
        :key="user.id"
        class="dropdown-item"
        :class="{ 'dropdown-item-active': user.id === currentUserId }"
        @click="selectUser(user)"
      >
        <div class="dropdown-avatar" :style="{ background: user.avatarColor || '#667eea' }">
          {{ user.initials }}
        </div>
        <div class="dropdown-user-info">
          <span class="dropdown-user-name">{{ user.name }}</span>
          <span class="dropdown-user-role" :class="`role-${user.role}`">{{ user.role }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '../store/user'
import { canAccessRoute, firstAccessibleRoute } from '../router/permissions'

const userStore = useUserStore()
const { users, currentUserId, currentUser } = storeToRefs(userStore)
const route = useRoute()
const router = useRouter()

const isOpen = ref(false)
const selectorRef = ref(null)

const avatarStyle = computed(() => ({
  background: currentUser.value?.avatarColor || '#667eea'
}))

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function selectUser(user) {
  userStore.setCurrentUser(user.id)
  isOpen.value = false

  if (!canAccessRoute(user.role, route)) {
    router.push(firstAccessibleRoute(user.role))
  }
}

function handleClickOutside(event) {
  if (selectorRef.value && !selectorRef.value.contains(event.target)) {
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
.user-selector {
  position: relative;
}

.user-selector-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.2s;
}

.user-selector-trigger:hover {
  background: rgba(255, 255, 255, 0.15);
}

.user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: #fff;
  font-size: 13px;
}

.user-name {
  color: white;
  font-size: 14px;
}

.chevron {
  width: 16px;
  height: 16px;
  opacity: 0.7;
  transition: transform 0.2s;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  min-width: 240px;
  z-index: 1000;
  overflow: hidden;
}

.dropdown-header {
  padding: 10px 16px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6a7f92;
  border-bottom: 1px solid #e8eef4;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  cursor: pointer;
  transition: background 0.15s;
}

.dropdown-item:hover {
  background: #f5f8fb;
}

.dropdown-item-active {
  background: #eff5fb;
}

.dropdown-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.dropdown-user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.dropdown-user-name {
  font-size: 14px;
  color: #173956;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-user-role {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 2px 8px;
  border-radius: 999px;
}

.role-admin {
  background: #fde8e8;
  color: #c53030;
}

.role-manager {
  background: #fef3cd;
  color: #975a16;
}

.role-user {
  background: #e3f1ff;
  color: #1f5f9e;
}
</style>
