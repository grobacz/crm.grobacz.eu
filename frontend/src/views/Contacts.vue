<template>
  <div class="contacts">
    <h1>Contacts</h1>
    <div class="contact-list">
      <div v-for="contact in contacts" :key="contact.id" class="contact-card">
        <h3>{{ contact.name }}</h3>
        <p>{{ contact.email }}</p>
        <p>{{ contact.phone }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { graphqlRequest } from '../graphql/client'

const contacts = ref([])

onMounted(async () => {
  // Fetch contacts
})
</script>

<style scoped>
.contacts {
  padding: 20px;
}

.contact-list {
  display: grid;
  gap: 15px;
}

.contact-card {
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
}
</style>
