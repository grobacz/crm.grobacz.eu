<template>
  <div class="global-search" ref="searchRef">
    <div class="search-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.35-4.35"/>
      </svg>
      <input
        type="text"
        placeholder="Search customers, leads, deals, products..."
        v-model="searchQuery"
        @input="handleInput"
        @keydown.escape="closeDropdown"
        @focus="onFocus"
      >
      <div v-if="isSearching" class="search-spinner"></div>
    </div>

    <div v-if="showDropdown" class="search-dropdown">
      <div v-if="isSearching" class="search-state">
        Searching...
      </div>

      <div v-else-if="searchQuery.length < 2" class="search-state">
        Type at least 2 characters to search.
      </div>

      <div v-else-if="totalResults === 0" class="search-state">
        No results found for "{{ searchQuery }}".
      </div>

      <div v-else class="search-results">
        <div v-if="results.customers.length" class="result-group">
          <div class="result-group-header">Customers</div>
          <div
            v-for="item in results.customers"
            :key="item.id"
            class="result-item"
            @click="navigateTo('/customers')"
          >
            <div class="result-icon customer-icon">C</div>
            <div class="result-info">
              <span class="result-name">{{ item.name }}</span>
              <span class="result-subtitle">{{ item.subtitle || item.email }}</span>
            </div>
          </div>
        </div>

        <div v-if="results.leads.length" class="result-group">
          <div class="result-group-header">Leads</div>
          <div
            v-for="item in results.leads"
            :key="item.id"
            class="result-item"
            @click="navigateTo('/leads')"
          >
            <div class="result-icon lead-icon">L</div>
            <div class="result-info">
              <span class="result-name">{{ item.name }}</span>
              <span class="result-subtitle">{{ item.subtitle || item.email }}</span>
            </div>
          </div>
        </div>

        <div v-if="results.deals.length" class="result-group">
          <div class="result-group-header">Deals</div>
          <div
            v-for="item in results.deals"
            :key="item.id"
            class="result-item"
            @click="navigateTo('/customers')"
          >
            <div class="result-icon deal-icon">D</div>
            <div class="result-info">
              <span class="result-name">{{ item.name }}</span>
              <span class="result-subtitle">{{ item.subtitle }}</span>
            </div>
          </div>
        </div>

        <div v-if="results.inventoryItems.length" class="result-group">
          <div class="result-group-header">Products</div>
          <div
            v-for="item in results.inventoryItems"
            :key="item.id"
            class="result-item"
            @click="navigateTo('/inventory')"
          >
            <div class="result-icon product-icon">P</div>
            <div class="result-info">
              <span class="result-name">{{ item.name }}</span>
              <span class="result-subtitle">{{ item.subtitle }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { graphqlRequest } from '../graphql/client'

const SEARCH_QUERY = `
  query Search($query: String!, $limit: Int) {
    search(query: $query, limit: $limit) {
      customers {
        id
        type
        name
        email
        subtitle
      }
      leads {
        id
        type
        name
        email
        subtitle
      }
      deals {
        id
        type
        name
        email
        subtitle
      }
      inventoryItems {
        id
        type
        name
        email
        subtitle
      }
      totalResults
    }
  }
`

const router = useRouter()
const searchQuery = ref('')
const isSearching = ref(false)
const isFocused = ref(false)
const results = ref({
  customers: [],
  leads: [],
  deals: [],
  inventoryItems: [],
  totalResults: 0
})
const searchRef = ref(null)
let debounceTimer = null

const showDropdown = computed(() => {
  return isFocused.value && searchQuery.value.length >= 1
})

const totalResults = computed(() => results.value.totalResults)

function handleInput() {
  if (debounceTimer) clearTimeout(debounceTimer)

  if (searchQuery.value.length < 2) {
    results.value = { customers: [], leads: [], deals: [], inventoryItems: [], totalResults: 0 }
    return
  }

  debounceTimer = setTimeout(async () => {
    isSearching.value = true
    try {
      const data = await graphqlRequest(SEARCH_QUERY, {
        query: searchQuery.value,
        limit: 5
      })
      results.value = data.search || { customers: [], leads: [], deals: [], inventoryItems: [], totalResults: 0 }
    } catch (error) {
      console.error('Search failed:', error)
    } finally {
      isSearching.value = false
    }
  }, 300)
}

function onFocus() {
  isFocused.value = true
}

function closeDropdown() {
  isFocused.value = false
}

function navigateTo(path) {
  isFocused.value = false
  searchQuery.value = ''
  results.value = { customers: [], leads: [], deals: [], inventoryItems: [], totalResults: 0 }
  router.push(path)
}

function handleClickOutside(event) {
  if (searchRef.value && !searchRef.value.contains(event.target)) {
    isFocused.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<style scoped>
.global-search {
  position: relative;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.2);
  padding: 8px 15px;
  border-radius: 25px;
  width: 300px;
}

.search-box input {
  background: transparent;
  border: none;
  color: white;
  outline: none;
  width: 100%;
  font-size: 14px;
}

.search-box input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.search-box svg {
  width: 18px;
  height: 18px;
  opacity: 0.8;
  flex-shrink: 0;
}

.search-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  flex-shrink: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.search-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 400px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
  max-height: 480px;
  overflow-y: auto;
}

.search-state {
  padding: 24px;
  text-align: center;
  color: #6a7f92;
  font-size: 14px;
}

.result-group {
  border-bottom: 1px solid #f0f4f8;
}

.result-group:last-child {
  border-bottom: none;
}

.result-group-header {
  padding: 10px 16px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6a7f92;
  background: #f8fafc;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  cursor: pointer;
  transition: background 0.15s;
}

.result-item:hover {
  background: #f5f8fb;
}

.result-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.customer-icon { background: #667eea; }
.lead-icon { background: #f59e0b; }
.deal-icon { background: #10b981; }
.product-icon { background: #6366f1; }

.result-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.result-name {
  font-size: 14px;
  color: #173956;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-subtitle {
  font-size: 12px;
  color: #7a90a3;
}

@media (max-width: 768px) {
  .search-box {
    width: 200px;
  }

  .search-dropdown {
    width: 320px;
  }
}
</style>
