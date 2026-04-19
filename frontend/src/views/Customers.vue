<template>
  <div class="customers">
    <h1>Customers</h1>
    <div class="customer-list">
      <div v-for="customer in customers" :key="customer.id" class="customer-card">
        <h3>{{ customer.name }}</h3>
        <p>{{ customer.email }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { graphqlRequest } from '../graphql/client'

const customers = ref([])

onMounted(async () => {
  // Fetch customers
})
</script>

<style scoped>
.customers {
  padding: 20px;
}

.customer-list {
  display: grid;
  gap: 15px;
}

.customer-card {
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
}
</style>
