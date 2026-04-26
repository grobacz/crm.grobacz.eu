<template>
  <div class="layout">
    <TopBar />
    <div class="main-container">
      <Sidebar />
      <main class="content">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import TopBar from './TopBar.vue'
import Sidebar from './Sidebar.vue'
import { useUserStore } from '../../store/user'
import { useNotificationStore } from '../../store/notification'

const userStore = useUserStore()
const notificationStore = useNotificationStore()

onMounted(async () => {
  await userStore.loadUsers()
  if (userStore.currentUserId) {
    notificationStore.loadUnreadCount()
  }
})
</script>

<style scoped>
.layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-container {
  display: flex;
  height: calc(100vh - 60px);
}

.content {
  flex: 1;
  background: #f5f5f5;
  overflow-y: auto;
  padding: 30px;
}
</style>
