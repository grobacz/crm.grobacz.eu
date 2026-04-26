<template>
  <div class="page-shell">
    <section class="hero-card">
      <div>
        <p class="eyebrow">Product Building Blocks</p>
        <h1>Pricing Lists</h1>
        <p class="hero-copy">
          Publish multiple pricing strategies against the same inventory catalog without forking products.
        </p>
      </div>

      <div class="hero-metrics">
        <div class="metric-card">
          <span class="metric-label">Lists</span>
          <strong>{{ pricingLists.length }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Active</span>
          <strong>{{ activeListCount }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Priced items</span>
          <strong>{{ pricedItemCount }}</strong>
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
            <p class="panel-kicker">{{ isEditingList ? 'Update pricing list' : 'New pricing list' }}</p>
            <h2>{{ isEditingList ? 'Edit list settings' : 'Create pricing list' }}</h2>
          </div>
          <button v-if="isEditingList" class="btn btn-ghost" type="button" @click="resetListForm">
            Clear
          </button>
        </div>

        <form class="form-stack" @submit.prevent="submitPricingList">
          <div class="field-grid">
            <label class="field" :class="{ 'field-error': listFormErrors.name }">
              <span>Name *</span>
              <input v-model.trim="listForm.name" type="text" maxlength="120" placeholder="Retail Europe">
              <small v-if="listFormErrors.name">{{ listFormErrors.name }}</small>
            </label>

            <label class="field" :class="{ 'field-error': listForm.code }">
              <span>Code *</span>
              <input v-model.trim="listForm.code" type="text" maxlength="80" placeholder="RETAIL_EU">
              <small v-if="listFormErrors.code">{{ listFormErrors.code }}</small>
            </label>
          </div>

          <div class="field-grid">
            <label class="field" :class="{ 'field-error': listFormErrors.currency }">
              <span>Currency</span>
              <input v-model.trim="listForm.currency" type="text" maxlength="3" placeholder="EUR">
              <small v-if="listFormErrors.currency">{{ listFormErrors.currency }}</small>
            </label>

            <div class="checkbox-column">
              <label class="checkbox-field">
                <input v-model="listForm.isDefault" type="checkbox">
                <span>Default list</span>
              </label>
              <label class="checkbox-field">
                <input v-model="listForm.isActive" type="checkbox">
                <span>List is active</span>
              </label>
            </div>
          </div>

          <label class="field" :class="{ 'field-error': listFormErrors.description }">
            <span>Description</span>
            <textarea v-model.trim="listForm.description" rows="4" maxlength="4000" placeholder="Who this pricing list is for and when it should be used."></textarea>
            <small v-if="listFormErrors.description">{{ listFormErrors.description }}</small>
          </label>

          <div v-if="listSubmitError" class="alert alert-error">{{ listSubmitError }}</div>
          <div v-if="listSubmitSuccess" class="alert alert-success">{{ listSubmitSuccess }}</div>

          <div class="form-actions">
            <p class="helper-copy">
              Use list metadata for strategy. Actual item prices stay attached through the selected pricing list below.
            </p>
            <div class="action-row">
              <button class="btn btn-primary" type="submit" :disabled="isSubmittingList">
                {{ isSubmittingList ? 'Saving...' : isEditingList ? 'Update list' : 'Create list' }}
              </button>
              <button
                v-if="isEditingList"
                class="btn btn-danger"
                type="button"
                :disabled="isDeletingList"
                @click="deletePricingList(listForm.id)"
              >
                {{ isDeletingList ? 'Deleting...' : 'Delete list' }}
              </button>
            </div>
          </div>
        </form>

        <div class="list-stack">
          <article
            v-for="pricingList in pricingLists"
            :key="pricingList.id"
            class="resource-card"
            :class="{ 'resource-card-selected': selectedPricingListId === pricingList.id }"
          >
            <button class="resource-select" type="button" @click="selectPricingList(pricingList.id)">
              <div class="resource-head">
                <div>
                  <h3>{{ pricingList.name }}</h3>
                  <p class="resource-subtitle">
                    <span class="token">{{ pricingList.code }}</span>
                    <span>{{ pricingList.currency }}</span>
                  </p>
                </div>
                <div class="resource-statuses">
                  <span v-if="pricingList.isDefault" class="status-pill status-default">Default</span>
                  <span class="status-pill" :class="pricingList.isActive ? 'status-active' : 'status-inactive'">
                    {{ pricingList.isActive ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>
              <p class="resource-description">
                {{ pricingList.description || 'No pricing list description yet.' }}
              </p>
              <div class="resource-metrics">
                <span class="metric-pill">{{ pricingList.items.length }} priced item{{ pricingList.items.length === 1 ? '' : 's' }}</span>
              </div>
            </button>
            <button class="btn btn-ghost" type="button" @click="startEditingList(pricingList)">Edit</button>
          </article>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Entries</p>
            <h2>{{ selectedPricingList ? `${selectedPricingList.name} prices` : 'Select a pricing list' }}</h2>
          </div>
          <span class="panel-note" v-if="selectedPricingList">{{ selectedPricingList.currency }} list pricing</span>
        </div>

        <div v-if="!selectedPricingList" class="empty-state">
          Create or select a pricing list to start assigning prices to inventory items.
        </div>

        <template v-else>
          <form class="form-stack compact-form" @submit.prevent="submitPricingEntry">
            <div class="field-grid">
              <label class="field" :class="{ 'field-error': entryFormErrors.inventoryItemId }">
                <span>Inventory item *</span>
                <select v-model="entryForm.inventoryItemId">
                  <option value="">Choose item</option>
                  <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                    {{ item.name }} ({{ item.sku }})
                  </option>
                </select>
                <small v-if="entryFormErrors.inventoryItemId">{{ entryFormErrors.inventoryItemId }}</small>
              </label>

              <label class="field" :class="{ 'field-error': entryFormErrors.price }">
                <span>Price *</span>
                <input v-model.number="entryForm.price" type="number" min="0" step="0.01" placeholder="99.00">
                <small v-if="entryFormErrors.price">{{ entryFormErrors.price }}</small>
              </label>
            </div>

            <label class="field" :class="{ 'field-error': entryFormErrors.compareAtPrice }">
              <span>Compare-at price</span>
              <input v-model.number="entryForm.compareAtPrice" type="number" min="0" step="0.01" placeholder="129.00">
              <small v-if="entryFormErrors.compareAtPrice">{{ entryFormErrors.compareAtPrice }}</small>
            </label>

            <div v-if="entrySubmitError" class="alert alert-error">{{ entrySubmitError }}</div>
            <div v-if="entrySubmitSuccess" class="alert alert-success">{{ entrySubmitSuccess }}</div>

            <div class="action-row">
              <button class="btn btn-primary" type="submit" :disabled="isSubmittingEntry">
                {{ isSubmittingEntry ? 'Saving...' : entryForm.id ? 'Update price' : 'Add price' }}
              </button>
              <button v-if="entryForm.id" class="btn btn-ghost" type="button" @click="resetEntryForm">
                Clear
              </button>
              <button
                v-if="entryForm.id"
                class="btn btn-danger"
                type="button"
                :disabled="isDeletingEntry"
                @click="deletePricingEntry(entryForm.id)"
              >
                {{ isDeletingEntry ? 'Deleting...' : 'Delete price' }}
              </button>
            </div>
          </form>

          <div v-if="selectedPricingEntries.length === 0" class="empty-state">
            No prices yet for this list.
          </div>

          <div v-else class="table-shell">
            <table class="price-table">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Price</th>
                  <th>Compare-at</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in selectedPricingEntries" :key="entry.id">
                  <td>
                    <div class="table-title">{{ entry.inventoryItem.name }}</div>
                    <div class="table-subtitle">{{ entry.inventoryItem.sku }}</div>
                  </td>
                  <td>{{ formatCurrency(entry.price, selectedPricingList.currency) }}</td>
                  <td>{{ entry.compareAtPrice === null ? '—' : formatCurrency(entry.compareAtPrice, selectedPricingList.currency) }}</td>
                  <td class="table-actions">
                    <button class="btn btn-ghost btn-small" type="button" @click="startEditingEntry(entry)">
                      Edit
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { graphqlRequest } from '../graphql/client'

const PRICING_WORKSPACE_QUERY = /* GraphQL */ `
  query PricingWorkspace {
    inventoryItems {
      id
      sku
      name
      isActive
    }
    pricingLists {
      id
      name
      code
      currency
      description
      isDefault
      isActive
      items {
        id
        price
        compareAtPrice
        inventoryItem {
          id
          sku
          name
        }
      }
    }
  }
`

const CREATE_PRICING_LIST_MUTATION = /* GraphQL */ `
  mutation CreatePricingList($name: String!, $code: String!, $currency: String, $description: String, $isDefault: Boolean, $isActive: Boolean) {
    createPricingList(name: $name, code: $code, currency: $currency, description: $description, isDefault: $isDefault, isActive: $isActive) {
      id
    }
  }
`

const UPDATE_PRICING_LIST_MUTATION = /* GraphQL */ `
  mutation UpdatePricingList($id: String!, $name: String, $code: String, $currency: String, $description: String, $isDefault: Boolean, $isActive: Boolean) {
    updatePricingList(id: $id, name: $name, code: $code, currency: $currency, description: $description, isDefault: $isDefault, isActive: $isActive) {
      id
    }
  }
`

const DELETE_PRICING_LIST_MUTATION = /* GraphQL */ `
  mutation DeletePricingList($id: String!) {
    deletePricingList(id: $id)
  }
`

const UPSERT_PRICING_ENTRY_MUTATION = /* GraphQL */ `
  mutation UpsertPricingListItem($pricingListId: String!, $inventoryItemId: String!, $price: Float!, $compareAtPrice: Float) {
    upsertPricingListItem(pricingListId: $pricingListId, inventoryItemId: $inventoryItemId, price: $price, compareAtPrice: $compareAtPrice) {
      id
    }
  }
`

const DELETE_PRICING_ENTRY_MUTATION = /* GraphQL */ `
  mutation DeletePricingListItem($id: String!) {
    deletePricingListItem(id: $id)
  }
`

const inventoryItems = ref([])
const pricingLists = ref([])
const selectedPricingListId = ref('')
const isLoading = ref(false)
const loadError = ref('')

const isSubmittingList = ref(false)
const isDeletingList = ref(false)
const listSubmitError = ref('')
const listSubmitSuccess = ref('')
const listFormErrors = ref({})
const listForm = ref(createEmptyListForm())

const isSubmittingEntry = ref(false)
const isDeletingEntry = ref(false)
const entrySubmitError = ref('')
const entrySubmitSuccess = ref('')
const entryFormErrors = ref({})
const entryForm = ref(createEmptyEntryForm())

const isEditingList = computed(() => Boolean(listForm.value.id))
const selectedPricingList = computed(() => pricingLists.value.find((pricingList) => pricingList.id === selectedPricingListId.value) ?? null)
const selectedPricingEntries = computed(() => {
  if (!selectedPricingList.value) {
    return []
  }

  return [...selectedPricingList.value.items].sort((left, right) =>
    left.inventoryItem.name.localeCompare(right.inventoryItem.name)
  )
})
const activeListCount = computed(() => pricingLists.value.filter((pricingList) => pricingList.isActive).length)
const pricedItemCount = computed(() => pricingLists.value.reduce((total, pricingList) => total + pricingList.items.length, 0))

function createEmptyListForm() {
  return {
    id: '',
    name: '',
    code: '',
    currency: 'USD',
    description: '',
    isDefault: false,
    isActive: true,
  }
}

function createEmptyEntryForm() {
  return {
    id: '',
    inventoryItemId: '',
    price: null,
    compareAtPrice: null,
  }
}

function readErrorMessage(error, fallback) {
  return error?.response?.errors?.[0]?.message ?? fallback
}

function readFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors ?? {}
}

function resetListForm() {
  listForm.value = createEmptyListForm()
  listFormErrors.value = {}
  listSubmitError.value = ''
  listSubmitSuccess.value = ''
}

function resetEntryForm() {
  entryForm.value = createEmptyEntryForm()
  entryFormErrors.value = {}
  entrySubmitError.value = ''
  entrySubmitSuccess.value = ''
}

function selectPricingList(pricingListId) {
  selectedPricingListId.value = pricingListId
  resetEntryForm()
}

function startEditingList(pricingList) {
  listForm.value = {
    id: pricingList.id,
    name: pricingList.name,
    code: pricingList.code,
    currency: pricingList.currency,
    description: pricingList.description ?? '',
    isDefault: pricingList.isDefault,
    isActive: pricingList.isActive,
  }
  selectPricingList(pricingList.id)
  listFormErrors.value = {}
  listSubmitError.value = ''
  listSubmitSuccess.value = ''
}

function startEditingEntry(entry) {
  entryForm.value = {
    id: entry.id,
    inventoryItemId: entry.inventoryItem.id,
    price: entry.price,
    compareAtPrice: entry.compareAtPrice,
  }
  entryFormErrors.value = {}
  entrySubmitError.value = ''
  entrySubmitSuccess.value = ''
}

function formatCurrency(amount, currency) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(amount)
}

async function loadWorkspace() {
  isLoading.value = true
  loadError.value = ''

  try {
    const data = await graphqlRequest(PRICING_WORKSPACE_QUERY)
    inventoryItems.value = (data.inventoryItems ?? []).filter((item) => item.isActive)
    pricingLists.value = data.pricingLists ?? []

    if (!selectedPricingListId.value && pricingLists.value.length > 0) {
      selectedPricingListId.value = pricingLists.value[0].id
    }

    if (selectedPricingListId.value && !pricingLists.value.some((pricingList) => pricingList.id === selectedPricingListId.value)) {
      selectedPricingListId.value = pricingLists.value[0]?.id ?? ''
    }
  } catch (error) {
    loadError.value = readErrorMessage(error, 'Unable to load pricing lists right now.')
  } finally {
    isLoading.value = false
  }
}

async function submitPricingList() {
  isSubmittingList.value = true
  listSubmitError.value = ''
  listSubmitSuccess.value = ''
  listFormErrors.value = {}

  const variables = {
    name: listForm.value.name,
    code: listForm.value.code,
    currency: listForm.value.currency,
    description: listForm.value.description || null,
    isDefault: Boolean(listForm.value.isDefault),
    isActive: Boolean(listForm.value.isActive),
  }

  try {
    if (isEditingList.value) {
      await graphqlRequest(UPDATE_PRICING_LIST_MUTATION, { id: listForm.value.id, ...variables })
      listSubmitSuccess.value = `Pricing list "${listForm.value.name}" was updated.`
      selectedPricingListId.value = listForm.value.id
    } else {
      await graphqlRequest(CREATE_PRICING_LIST_MUTATION, variables)
      listSubmitSuccess.value = `Pricing list "${listForm.value.name}" was created.`
      resetListForm()
    }

    await loadWorkspace()
  } catch (error) {
    listFormErrors.value = readFieldErrors(error)
    listSubmitError.value = readErrorMessage(error, 'Unable to save the pricing list.')
  } finally {
    isSubmittingList.value = false
  }
}

async function deletePricingList(pricingListId) {
  if (!window.confirm('Delete this pricing list? All prices in the list will be removed.')) {
    return
  }

  isDeletingList.value = true
  listSubmitError.value = ''
  listSubmitSuccess.value = ''

  try {
    await graphqlRequest(DELETE_PRICING_LIST_MUTATION, { id: pricingListId })
    listSubmitSuccess.value = 'Pricing list deleted.'
    if (selectedPricingListId.value === pricingListId) {
      selectedPricingListId.value = ''
    }
    resetListForm()
    resetEntryForm()
    await loadWorkspace()
  } catch (error) {
    listSubmitError.value = readErrorMessage(error, 'Unable to delete the pricing list.')
  } finally {
    isDeletingList.value = false
  }
}

async function submitPricingEntry() {
  if (!selectedPricingList.value) {
    return
  }

  isSubmittingEntry.value = true
  entrySubmitError.value = ''
  entrySubmitSuccess.value = ''
  entryFormErrors.value = {}

  if (entryForm.value.inventoryItemId === '') {
    entryFormErrors.value = {
      inventoryItemId: 'Select an inventory item.',
    }
    entrySubmitError.value = 'Choose an inventory item before saving the price.'
    isSubmittingEntry.value = false
    return
  }

  if (entryForm.value.price === null || entryForm.value.price === '' || Number.isNaN(Number(entryForm.value.price))) {
    entryFormErrors.value = {
      price: 'Enter a valid price.',
    }
    entrySubmitError.value = 'Enter a valid price before saving the entry.'
    isSubmittingEntry.value = false
    return
  }

  try {
    await graphqlRequest(UPSERT_PRICING_ENTRY_MUTATION, {
      pricingListId: selectedPricingList.value.id,
      inventoryItemId: entryForm.value.inventoryItemId,
      price: Number(entryForm.value.price),
      compareAtPrice: entryForm.value.compareAtPrice === null || entryForm.value.compareAtPrice === ''
        ? null
        : Number(entryForm.value.compareAtPrice),
    })

    entrySubmitSuccess.value = entryForm.value.id ? 'Price updated.' : 'Price added.'
    resetEntryForm()
    await loadWorkspace()
  } catch (error) {
    entryFormErrors.value = readFieldErrors(error)
    entrySubmitError.value = readErrorMessage(error, 'Unable to save the pricing entry.')
  } finally {
    isSubmittingEntry.value = false
  }
}

async function deletePricingEntry(entryId) {
  if (!window.confirm('Delete this price entry?')) {
    return
  }

  isDeletingEntry.value = true
  entrySubmitError.value = ''
  entrySubmitSuccess.value = ''

  try {
    await graphqlRequest(DELETE_PRICING_ENTRY_MUTATION, { id: entryId })
    entrySubmitSuccess.value = 'Price entry deleted.'
    resetEntryForm()
    await loadWorkspace()
  } catch (error) {
    entrySubmitError.value = readErrorMessage(error, 'Unable to delete the pricing entry.')
  } finally {
    isDeletingEntry.value = false
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
  background: linear-gradient(135deg, #f8fff7 0%, #e7f6e7 100%);
}

.eyebrow,
.panel-kicker {
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.72rem;
  color: #4f7b53;
  margin-bottom: 10px;
}

h1,
h2,
h3 {
  color: #173d1f;
}

.hero-copy,
.resource-description,
.helper-copy,
.resource-subtitle,
.panel-note,
.table-subtitle {
  color: #4d6e52;
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
  color: #5f8a64;
  font-size: 0.85rem;
  margin-bottom: 8px;
}

.page-grid {
  display: grid;
  grid-template-columns: minmax(360px, 430px) minmax(0, 1fr);
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
.list-stack {
  display: grid;
  gap: 16px;
}

.compact-form {
  margin-bottom: 18px;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.field {
  display: grid;
  gap: 8px;
  color: #24472a;
  font-size: 0.95rem;
}

.field input,
.field textarea,
.field select {
  width: 100%;
  border: 1px solid #cadecf;
  border-radius: 14px;
  padding: 12px 14px;
  font: inherit;
  color: #173d1f;
  background: #fcfefb;
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

.checkbox-column {
  display: grid;
  gap: 12px;
}

.checkbox-field {
  display: flex;
  gap: 10px;
  align-items: center;
  color: #24472a;
}

.resource-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  padding: 18px;
}

.resource-card-selected {
  border-color: #2f7a3f;
  box-shadow: 0 20px 44px rgba(47, 122, 63, 0.12);
}

.resource-select {
  display: grid;
  gap: 12px;
  border: none;
  background: transparent;
  padding: 0;
  text-align: left;
  min-width: 0;
  width: 100%;
  cursor: pointer;
}

.resource-head {
  align-items: flex-start;
}

.resource-subtitle,
.resource-metrics,
.resource-statuses {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.resource-statuses {
  justify-content: flex-end;
  flex-shrink: 0;
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
  background: #e8f5e8;
  color: #255a32;
}

.status-pill {
  color: white;
}

.status-default {
  background: #166534;
}

.status-active {
  background: #2b8a3e;
}

.status-inactive {
  background: #7c8799;
}

.table-shell {
  overflow-x: auto;
  border: 1px solid #d6e7d7;
  border-radius: 18px;
}

.price-table {
  width: 100%;
  border-collapse: collapse;
}

.price-table th,
.price-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e6efe6;
  text-align: left;
}

.table-title {
  color: #173d1f;
  font-weight: 600;
}

.table-actions {
  text-align: right;
}

.btn {
  border: none;
  border-radius: 14px;
  padding: 11px 16px;
  font: inherit;
  cursor: pointer;
}

.btn-small {
  padding: 8px 12px;
}

.btn-primary {
  background: #2f7a3f;
  color: white;
}

.btn-secondary,
.btn-ghost {
  background: #e8f5e8;
  color: #173d1f;
}

.resource-card > .btn-ghost {
  flex-shrink: 0;
  align-self: flex-start;
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
  color: #5f8a64;
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

@media (max-width: 780px) {
  .field-grid,
  .hero-metrics {
    grid-template-columns: 1fr;
  }

  .panel-header,
  .resource-head,
  .form-actions,
  .action-row,
  .resource-card {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
