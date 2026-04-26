<template>
  <header class="top-bar">
    <div class="logo">
      <div class="logo-icon">CRM</div>
      <span>CustomerHub Pro</span>
    </div>

    <nav class="breadcrumb">
      <template v-for="(crumb, index) in breadcrumbs" :key="index">
        <span>{{ crumb }}</span>
      </template>
    </nav>

    <div class="status-info">
      <GlobalSearch />

      <NotificationDropdown />

      <UserSelector />
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import GlobalSearch from '../GlobalSearch.vue'
import NotificationDropdown from '../NotificationDropdown.vue'
import UserSelector from '../UserSelector.vue'

const route = useRoute()

const breadcrumbs = computed(() => {
  const crumbs = ['Home']
  if (route.name) {
    crumbs.push(route.name)
  }
  return crumbs
})
</script>

<style scoped>
.top-bar {
  background: #495057;
  color: white;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  height: 60px;
}

.logo {
  font-size: 18px;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  background: white;
  color: #667eea;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  opacity: 0.9;
}

.breadcrumb span {
  opacity: 0.7;
}

.breadcrumb span:not(:last-child)::after {
  content: '>';
  margin-left: 8px;
  opacity: 0.5;
}

.status-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

@media (max-width: 768px) {
  .breadcrumb {
    display: none;
  }
}
</style>
