<template>
  <div class="page-shell">
    <section class="hero-card">
      <div>
        <p class="eyebrow">Product Building Blocks</p>
        <h1>Inventory</h1>
        <p class="hero-copy">
          Maintain the shared catalog units that categories organize and pricing lists monetize.
        </p>
      </div>

      <div class="hero-metrics">
        <div class="metric-card">
          <span class="metric-label">SKUs</span>
          <strong>{{ inventoryItems.length }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Active items</span>
          <strong>{{ activeItemCount }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Low stock</span>
          <strong>{{ lowStockCount }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" :disabled="isLoading" @click="loadWorkspace">
          {{ isLoading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>
    </section>

    <div v-if="loadError" class="alert alert-error">{{ loadError }}</div>

    <div class="page-grid">
      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">{{ isEditing ? 'Update SKU' : 'New SKU' }}</p>
            <h2>{{ isEditing ? 'Edit inventory item' : 'Create inventory item' }}</h2>
          </div>
          <button v-if="isEditing" class="btn btn-ghost" type="button" @click="resetForm">
            Clear
          </button>
        </div>

        <form class="form-stack" @submit.prevent="submitInventoryItem">
          <div class="field-grid">
            <label class="field" :class="{ 'field-error': formErrors.name }">
              <span>Name *</span>
              <input v-model.trim="form.name" type="text" maxlength="255" placeholder="M8 mounting bracket">
              <small v-if="formErrors.name">{{ formErrors.name }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.sku }">
              <span>SKU *</span>
              <input v-model.trim="form.sku" type="text" maxlength="120" placeholder="BRACKET-M8">
              <small v-if="formErrors.sku">{{ formErrors.sku }}</small>
            </label>
          </div>

          <div class="field-grid">
            <label class="field" :class="{ 'field-error': formErrors.categoryId }">
              <span>Category</span>
              <select v-model="form.categoryId">
                <option value="">Unassigned</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
              <small v-if="formErrors.categoryId">{{ formErrors.categoryId }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.unit }">
              <span>Unit</span>
              <input v-model.trim="form.unit" type="text" maxlength="50" placeholder="unit">
              <small v-if="formErrors.unit">{{ formErrors.unit }}</small>
            </label>
          </div>

          <div class="field-grid field-grid-quad">
            <label class="field" :class="{ 'field-error': formErrors.stockQuantity }">
              <span>On hand</span>
              <input v-model.number="form.stockQuantity" type="number" min="0" step="1">
              <small v-if="formErrors.stockQuantity">{{ formErrors.stockQuantity }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.reservedQuantity }">
              <span>Reserved</span>
              <input v-model.number="form.reservedQuantity" type="number" min="0" step="1">
              <small v-if="formErrors.reservedQuantity">{{ formErrors.reservedQuantity }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.reorderLevel }">
              <span>Reorder at</span>
              <input v-model.number="form.reorderLevel" type="number" min="0" step="1">
              <small v-if="formErrors.reorderLevel">{{ formErrors.reorderLevel }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.cost }">
              <span>Cost</span>
              <input v-model.number="form.cost" type="number" min="0" step="0.01" placeholder="12.50">
              <small v-if="formErrors.cost">{{ formErrors.cost }}</small>
            </label>
          </div>

          <label class="checkbox-field">
            <input v-model="form.isActive" type="checkbox">
            <span>Item is active and available for pricing.</span>
          </label>

          <label class="field" :class="{ 'field-error': formErrors.description }">
            <span>Description</span>
            <textarea v-model.trim="form.description" rows="5" maxlength="4000" placeholder="Optional internal notes or selling context."></textarea>
            <small v-if="formErrors.description">{{ formErrors.description }}</small>
          </label>

          <div v-if="submitError" class="alert alert-error">{{ submitError }}</div>
          <div v-if="submitSuccess" class="alert alert-success">{{ submitSuccess }}</div>

          <div class="form-actions">
            <p class="helper-copy">
              Availability is computed from on-hand minus reserved. Pricing lists can reuse the same item across multiple markets.
            </p>
            <div class="action-row">
              <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : isEditing ? 'Update item' : 'Create item' }}
              </button>
              <button
                v-if="isEditing"
                class="btn btn-danger"
                type="button"
                :disabled="isDeleting"
                @click="deleteInventoryItem(form.id)"
              >
                {{ isDeleting ? 'Deleting...' : 'Delete item' }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Catalog</p>
            <h2>Inventory items</h2>
          </div>
          <label class="search-field">
            <input v-model.trim="searchTerm" type="text" placeholder="Search SKU, name, or category">
          </label>
        </div>

        <div class="toolbar-pills">
          <button
            v-for="filter in activityFilters"
            :key="filter.value"
            class="filter-pill"
            :class="{ 'filter-pill-active': selectedFilter === filter.value }"
            type="button"
            @click="selectedFilter = filter.value"
          >
            {{ filter.label }}
          </button>
        </div>

        <div v-if="isLoading" class="empty-state">Loading inventory...</div>
        <div v-else-if="filteredInventoryItems.length === 0" class="empty-state">
          No inventory items match the current filter.
        </div>

        <div v-else class="card-list">
          <article v-for="item in filteredInventoryItems" :key="item.id" class="resource-card">
            <div class="resource-head">
              <div>
                <h3>{{ item.name }}</h3>
                <p class="resource-subtitle">
                  <span class="token">{{ item.sku }}</span>
                  <span>{{ item.category?.name || 'No category' }}</span>
                </p>
              </div>
              <button class="btn btn-ghost" type="button" @click="startEditing(item)">Edit</button>
            </div>

            <p class="resource-description">
              {{ item.description || 'No internal notes yet.' }}
            </p>

            <div class="resource-metrics">
              <span class="metric-pill">Available {{ item.availableQuantity }}</span>
              <span class="metric-pill">Reserved {{ item.reservedQuantity }}</span>
              <span class="metric-pill">Reorder {{ item.reorderLevel }}</span>
              <span class="metric-pill">{{ item.pricingListItems.length }} price list{{ item.pricingListItems.length === 1 ? '' : 's' }}</span>
              <span class="status-pill" :class="item.isActive ? 'status-active' : 'status-inactive'">
                {{ item.isActive ? 'Active' : 'Inactive' }}
              </span>
              <span v-if="isLowStock(item)" class="status-pill status-warning">Low stock</span>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { graphqlRequest } from '../graphql/client'

const INVENTORY_QUERY = /* GraphQL */ `
  query InventoryWorkspace {
    categories {
      id
      name
    }
    inventoryItems {
      id
      sku
      name
      description
      unit
      stockQuantity
      reservedQuantity
      availableQuantity
      reorderLevel
      cost
      isActive
      category {
        id
        name
      }
      pricingListItems {
        id
      }
    }
  }
`

const CREATE_INVENTORY_ITEM_MUTATION = /* GraphQL */ `
  mutation CreateInventoryItem(
    $sku: String!
    $name: String!
    $description: String
    $unit: String
    $stockQuantity: Int
    $reservedQuantity: Int
    $reorderLevel: Int
    $cost: Float
    $isActive: Boolean
    $categoryId: String
  ) {
    createInventoryItem(
      sku: $sku
      name: $name
      description: $description
      unit: $unit
      stockQuantity: $stockQuantity
      reservedQuantity: $reservedQuantity
      reorderLevel: $reorderLevel
      cost: $cost
      isActive: $isActive
      categoryId: $categoryId
    ) {
      id
    }
  }
`

const UPDATE_INVENTORY_ITEM_MUTATION = /* GraphQL */ `
  mutation UpdateInventoryItem(
    $id: String!
    $sku: String
    $name: String
    $description: String
    $unit: String
    $stockQuantity: Int
    $reservedQuantity: Int
    $reorderLevel: Int
    $cost: Float
    $isActive: Boolean
    $categoryId: String
  ) {
    updateInventoryItem(
      id: $id
      sku: $sku
      name: $name
      description: $description
      unit: $unit
      stockQuantity: $stockQuantity
      reservedQuantity: $reservedQuantity
      reorderLevel: $reorderLevel
      cost: $cost
      isActive: $isActive
      categoryId: $categoryId
    ) {
      id
    }
  }
`

const DELETE_INVENTORY_ITEM_MUTATION = /* GraphQL */ `
  mutation DeleteInventoryItem($id: String!) {
    deleteInventoryItem(id: $id)
  }
`

const categories = ref([])
const inventoryItems = ref([])
const searchTerm = ref('')
const selectedFilter = ref('all')
const isLoading = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const formErrors = ref({})
const form = ref(createEmptyForm())

const activityFilters = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Low stock', value: 'low-stock' },
]

const isEditing = computed(() => Boolean(form.value.id))
const activeItemCount = computed(() => inventoryItems.value.filter((item) => item.isActive).length)
const lowStockCount = computed(() => inventoryItems.value.filter(isLowStock).length)
const filteredInventoryItems = computed(() => {
  const query = searchTerm.value.toLowerCase()

  return inventoryItems.value.filter((item) => {
    if (selectedFilter.value === 'active' && !item.isActive) {
      return false
    }

    if (selectedFilter.value === 'low-stock' && !isLowStock(item)) {
      return false
    }

    if (query === '') {
      return true
    }

    return item.name.toLowerCase().includes(query)
      || item.sku.toLowerCase().includes(query)
      || (item.category?.name ?? '').toLowerCase().includes(query)
      || (item.description ?? '').toLowerCase().includes(query)
  })
})

function createEmptyForm() {
  return {
    id: '',
    sku: '',
    name: '',
    categoryId: '',
    unit: 'unit',
    stockQuantity: 0,
    reservedQuantity: 0,
    reorderLevel: 0,
    cost: null,
    isActive: true,
    description: '',
  }
}

function normalizeItem(item) {
  return {
    ...item,
    pricingListItems: item.pricingListItems ?? [],
  }
}

function isLowStock(item) {
  return item.availableQuantity <= item.reorderLevel
}

function readErrorMessage(error, fallback) {
  return error?.response?.errors?.[0]?.message ?? fallback
}

function readFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors ?? {}
}

function startEditing(item) {
  form.value = {
    id: item.id,
    sku: item.sku,
    name: item.name,
    categoryId: item.category?.id ?? '',
    unit: item.unit,
    stockQuantity: item.stockQuantity,
    reservedQuantity: item.reservedQuantity,
    reorderLevel: item.reorderLevel,
    cost: item.cost,
    isActive: item.isActive,
    description: item.description ?? '',
  }
  formErrors.value = {}
  submitError.value = ''
  submitSuccess.value = ''
}

function resetForm() {
  form.value = createEmptyForm()
  formErrors.value = {}
  submitError.value = ''
  submitSuccess.value = ''
}

function toMutationVariables() {
  return {
    sku: form.value.sku,
    name: form.value.name,
    categoryId: form.value.categoryId || null,
    unit: form.value.unit,
    stockQuantity: Number.isFinite(form.value.stockQuantity) ? form.value.stockQuantity : 0,
    reservedQuantity: Number.isFinite(form.value.reservedQuantity) ? form.value.reservedQuantity : 0,
    reorderLevel: Number.isFinite(form.value.reorderLevel) ? form.value.reorderLevel : 0,
    cost: form.value.cost === '' || form.value.cost === null ? null : Number(form.value.cost),
    isActive: Boolean(form.value.isActive),
    description: form.value.description || null,
  }
}

async function loadWorkspace() {
  isLoading.value = true
  loadError.value = ''

  try {
    const data = await graphqlRequest(INVENTORY_QUERY)
    categories.value = data.categories ?? []
    inventoryItems.value = (data.inventoryItems ?? []).map(normalizeItem)
  } catch (error) {
    loadError.value = readErrorMessage(error, 'Unable to load inventory right now.')
  } finally {
    isLoading.value = false
  }
}

async function submitInventoryItem() {
  isSubmitting.value = true
  submitError.value = ''
  submitSuccess.value = ''
  formErrors.value = {}

  const variables = toMutationVariables()

  try {
    if (isEditing.value) {
      await graphqlRequest(UPDATE_INVENTORY_ITEM_MUTATION, { id: form.value.id, ...variables })
      submitSuccess.value = `Inventory item "${form.value.name}" was updated.`
    } else {
      await graphqlRequest(CREATE_INVENTORY_ITEM_MUTATION, variables)
      submitSuccess.value = `Inventory item "${form.value.name}" was created.`
      resetForm()
    }

    await loadWorkspace()
  } catch (error) {
    formErrors.value = readFieldErrors(error)
    submitError.value = readErrorMessage(error, 'Unable to save the inventory item.')
  } finally {
    isSubmitting.value = false
  }
}

async function deleteInventoryItem(itemId) {
  if (!window.confirm('Delete this inventory item? Any pricing entries for it will also be removed.')) {
    return
  }

  isDeleting.value = true
  submitError.value = ''
  submitSuccess.value = ''

  try {
    await graphqlRequest(DELETE_INVENTORY_ITEM_MUTATION, { id: itemId })
    submitSuccess.value = 'Inventory item deleted.'
    resetForm()
    await loadWorkspace()
  } catch (error) {
    submitError.value = readErrorMessage(error, 'Unable to delete the inventory item.')
  } finally {
    isDeleting.value = false
  }
}

onMounted(loadWorkspace)
</script>

<style scoped>
.page-shell {
  display: grid;
  gap: 24px;
}

.hero-card,
.panel,
.metric-card,
.resource-card {
  background: #ffffff;
  border: 1px solid #d8e2f0;
  border-radius: 24px;
  box-shadow: 0 18px 40px rgba(20, 46, 86, 0.08);
}

.hero-card {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding: 28px;
  background: linear-gradient(135deg, #fffaf4 0%, #fff2df 100%);
}

.eyebrow,
.panel-kicker {
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.72rem;
  color: #8a6830;
  margin-bottom: 10px;
}

h1,
h2,
h3 {
  color: #3d2f1b;
}

.hero-copy,
.resource-description,
.helper-copy,
.resource-subtitle {
  color: #695942;
}

.hero-metrics {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  min-width: 320px;
}

.metric-card {
  padding: 18px;
}

.metric-label {
  display: block;
  color: #806846;
  font-size: 0.85rem;
  margin-bottom: 8px;
}

.page-grid {
  display: grid;
  grid-template-columns: minmax(340px, 460px) minmax(0, 1fr);
  gap: 24px;
}

.panel {
  padding: 24px;
}

.panel-header,
.resource-head,
.form-actions,
.action-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.form-stack,
.card-list {
  display: grid;
  gap: 16px;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.field-grid-quad {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.field {
  display: grid;
  gap: 8px;
  color: #513d23;
  font-size: 0.95rem;
}

.field input,
.field textarea,
.field select,
.search-field input {
  width: 100%;
  border: 1px solid #dfcfb6;
  border-radius: 14px;
  padding: 12px 14px;
  font: inherit;
  color: #3d2f1b;
  background: #fffdf9;
}

.field textarea {
  resize: vertical;
}

.field-error input,
.field-error textarea,
.field-error select {
  border-color: #d55b64;
}

.field small {
  color: #b73a47;
}

.checkbox-field {
  display: flex;
  gap: 10px;
  align-items: center;
  color: #513d23;
}

.resource-card {
  padding: 20px;
}

.resource-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 6px;
}

.resource-metrics,
.toolbar-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.metric-pill,
.token,
.status-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 0.82rem;
}

.metric-pill,
.token {
  background: #fff1dd;
  color: #6f4f1d;
}

.status-pill {
  color: white;
}

.status-active {
  background: #2c8f52;
}

.status-inactive {
  background: #7c8799;
}

.status-warning {
  background: #cb6f2a;
}

.filter-pill {
  border: none;
  border-radius: 999px;
  padding: 9px 14px;
  background: #f6ebd8;
  color: #5c4522;
  cursor: pointer;
}

.filter-pill-active {
  background: #3d2f1b;
  color: white;
}

.btn {
  border: none;
  border-radius: 14px;
  padding: 11px 16px;
  font: inherit;
  cursor: pointer;
}

.btn-primary {
  background: #d97706;
  color: white;
}

.btn-secondary,
.btn-ghost {
  background: #fff1dd;
  color: #3d2f1b;
}

.btn-danger {
  background: #c94f58;
  color: white;
}

.alert {
  border-radius: 16px;
  padding: 14px 16px;
}

.alert-error {
  background: #fff2f3;
  color: #9f2f3d;
  border: 1px solid #f3c7cc;
}

.alert-success {
  background: #eefbf3;
  color: #1f7a3d;
  border: 1px solid #bfe5cc;
}

.empty-state {
  padding: 28px 12px;
  text-align: center;
  color: #806846;
}

@media (max-width: 1180px) {
  .hero-card,
  .page-grid {
    grid-template-columns: 1fr;
  }

  .hero-card {
    flex-direction: column;
  }
}

@media (max-width: 820px) {
  .field-grid,
  .field-grid-quad,
  .hero-metrics {
    grid-template-columns: 1fr;
  }

  .panel-header,
  .resource-head,
  .form-actions,
  .action-row {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
