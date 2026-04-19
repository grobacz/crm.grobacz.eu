<template>
  <div class="dashboard">
    <h1>CRM Dashboard</h1>
    <div class="stats">
      <div class="stat-card">
        <h3>Total Customers</h3>
        <p>{{ stats.customers }}</p>
      </div>
      <div class="stat-card">
        <h3>Total Contacts</h3>
        <p>{{ stats.contacts }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { graphqlRequest } from '../graphql/client'

const stats = ref({ customers: 0, contacts: 0 })

onMounted(async () => {
  // Fetch dashboard stats
})
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.stats {
  display: flex;
  gap: 20px;
  margin-top: 20px;
}

.stat-card {
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  flex: 1;
}
</style>
