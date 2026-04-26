import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '../store/user'
import { canAccessRoute, firstAccessibleRoute } from './permissions'
import Dashboard from '../views/Dashboard.vue'
import Customers from '../views/Customers.vue'
import Contacts from '../views/Contacts.vue'
import Leads from '../views/Leads.vue'
import Categories from '../views/Categories.vue'
import Inventory from '../views/Inventory.vue'
import PricingLists from '../views/PricingLists.vue'
import CampaignCalls from '../views/CampaignCalls.vue'
import CampaignEmail from '../views/CampaignEmail.vue'
import Settings from '../views/Settings.vue'
import UserManagement from '../views/UserManagement.vue'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', name: 'Dashboard', component: Dashboard, meta: { section: 'dashboard' } },
  { path: '/customers', name: 'Customers', component: Customers, meta: { section: 'customers' } },
  { path: '/contacts', name: 'Contacts', component: Contacts, meta: { section: 'contacts' } },
  { path: '/leads', name: 'Leads', component: Leads, meta: { section: 'leads' } },
  { path: '/inventory', name: 'Inventory', component: Inventory, meta: { section: 'products' } },
  { path: '/categories', name: 'Categories', component: Categories, meta: { section: 'products' } },
  { path: '/pricing-lists', name: 'PricingLists', component: PricingLists, meta: { section: 'products' } },
  { path: '/campaigns/email', name: 'CampaignEmail', component: CampaignEmail, meta: { section: 'campaigns' } },
  { path: '/campaigns/calls', name: 'CampaignCalls', component: CampaignCalls, meta: { section: 'campaigns' } },
  { path: '/settings', name: 'Settings', component: Settings, meta: { section: 'settings' } },
  { path: '/settings/users', name: 'UserManagement', component: UserManagement, meta: { section: 'settings' } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to) => {
  const userStore = useUserStore()

  if (!userStore.currentUser && !userStore.isLoading) {
    await userStore.loadUsers()
  }

  const role = userStore.currentUser?.role || 'user'

  if (canAccessRoute(role, to)) {
    return true
  }

  return firstAccessibleRoute(role)
})

export default router
