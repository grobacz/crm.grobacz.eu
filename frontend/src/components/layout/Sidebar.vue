<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed }">
    <button class="sidebar-toggle" @click="toggleSidebar">
      {{ isCollapsed ? '▶' : '◀' }}
    </button>
    <div class="sidebar-menu">
      <div v-for="section in visibleMenuSections" :key="section.title" class="menu-section">
        <div class="section-title">{{ section.title }}</div>
        <div
          v-for="item in section.items"
          :key="item.label"
          class="menu-item"
          :class="{ active: isActive(item.route) }"
          @click="navigate(item.route)"
        >
          <span class="menu-icon">{{ item.icon }}</span>
          <span class="menu-text">{{ item.label }}</span>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '../../store/user'
import { canAccessSection } from '../../router/permissions'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const { currentUser } = storeToRefs(userStore)
const isCollapsed = ref(false)

const menuSections = [
  {
    title: 'Overview',
    section: 'dashboard',
    items: [
      { label: 'Dashboard', icon: '📊', route: '/dashboard' }
    ]
  },
  {
    title: 'Customers',
    section: 'customers',
    items: [
      { label: 'All Customers', icon: '👥', route: '/customers' },
      { label: 'Active', icon: '✅', route: '/customers?status=active' },
      { label: 'Inactive', icon: '❌', route: '/customers?status=inactive' },
      { label: 'VIP Customers', icon: '⭐', route: '/customers?status=vip' }
    ]
  },
  {
    title: 'Leads',
    section: 'leads',
    items: [
      { label: 'All Leads', icon: '🧲', route: '/leads' },
      { label: 'New Leads', icon: '🔍', route: '/leads?status=new' },
      { label: 'Qualified', icon: '✅', route: '/leads?status=qualified' },
      { label: 'Converted', icon: '💰', route: '/leads?status=converted' }
    ]
  },
  {
    title: 'Products',
    section: 'products',
    items: [
      { label: 'Inventory', icon: '📦', route: '/inventory' },
      { label: 'Categories', icon: '📁', route: '/categories' },
      { label: 'Pricing Lists', icon: '💵', route: '/pricing-lists' }
    ]
  },
  {
    title: 'Campaigns',
    section: 'campaigns',
    items: [
      { label: 'Email Campaigns', icon: '📧', route: '/campaigns/email' },
      { label: 'Call Logs', icon: '📞', route: '/campaigns/calls' }
    ]
  },
  {
    title: 'Settings',
    section: 'settings',
    items: [
      { label: 'System Settings', icon: '⚙️', route: '/settings' },
      { label: 'User Management', icon: '👥', route: '/settings/users' }
    ]
  }
]

const visibleMenuSections = computed(() => {
  const role = currentUser.value?.role || 'user'
  return menuSections.filter(section => canAccessSection(role, section.section))
})

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const isActive = (routePath) => {
  return route.fullPath === routePath
}

const navigate = (routePath) => {
  router.push(routePath)
}
</script>

<style scoped>
.sidebar {
  width: 250px;
  background: #2c3e50;
  color: white;
  transition: width 0.3s ease;
  overflow-y: auto;
  position: relative;
}

.sidebar.collapsed {
  width: 60px;
}

.sidebar-toggle {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: all 0.3s;
}

.sidebar-toggle:hover {
  background: rgba(255,255,255,0.3);
}

.sidebar-menu {
  padding: 20px 0;
}

.menu-section {
  margin-bottom: 20px;
}

.section-title {
  padding: 10px 20px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.6;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  cursor: pointer;
  transition: background 0.3s;
  border-left: 3px solid transparent;
}

.menu-item:hover {
  background: rgba(255,255,255,0.1);
  border-left-color: #667eea;
}

.menu-item.active {
  background: rgba(102, 126, 234, 0.1);
  border-left-color: #667eea;
  color: #667eea;
}

.menu-icon {
  width: 20px;
  text-align: center;
  margin-right: 12px;
  font-size: 16px;
}

.menu-text {
  transition: opacity 0.3s;
  white-space: nowrap;
}

.sidebar.collapsed .menu-text {
  opacity: 0;
}

.sidebar.collapsed .menu-item {
  justify-content: center;
  padding: 15px 0;
}

.sidebar.collapsed .menu-item .menu-icon {
  margin-right: 0;
}
</style>
