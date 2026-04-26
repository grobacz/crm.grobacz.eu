<template>
  <div class="page-shell">
    <section class="hero-card">
      <div>
        <p class="eyebrow">Product Building Blocks</p>
        <h1>Categories</h1>
        <p class="hero-copy">
          Shape a reusable category tree that inventory and pricing can share without duplicating product structure.
        </p>
      </div>

      <div class="hero-metrics">
        <div class="metric-card">
          <span class="metric-label">Categories</span>
          <strong>{{ categories.length }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Root groups</span>
          <strong>{{ rootCategoryCount }}</strong>
        </div>
        <div class="metric-card">
          <span class="metric-label">Assigned items</span>
          <strong>{{ assignedInventoryCount }}</strong>
        </div>
        <button class="btn btn-secondary" type="button" :disabled="isLoading" @click="loadCategories">
          {{ isLoading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>
    </section>

    <div v-if="loadError" class="alert alert-error">{{ loadError }}</div>

    <div class="page-grid">
      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">{{ isEditing ? 'Update category' : 'New category' }}</p>
            <h2>{{ isEditing ? 'Edit structure' : 'Create category' }}</h2>
          </div>
          <button v-if="isEditing" class="btn btn-ghost" type="button" @click="resetForm">
            Clear
          </button>
        </div>

        <form class="form-stack" @submit.prevent="submitCategory">
          <label class="field" :class="{ 'field-error': formErrors.name }">
            <span>Name *</span>
            <input v-model.trim="form.name" type="text" maxlength="120" placeholder="Hardware" @input="handleNameInput">
            <small v-if="formErrors.name">{{ formErrors.name }}</small>
          </label>

          <label class="field" :class="{ 'field-error': formErrors.slug }">
            <span>Slug *</span>
            <input v-model.trim="form.slug" type="text" maxlength="160" placeholder="hardware" @input="slugManuallyEdited = true">
            <small v-if="formErrors.slug">{{ formErrors.slug }}</small>
          </label>

          <div class="field-grid">
            <label class="field" :class="{ 'field-error': formErrors.parentId }">
              <span>Parent category</span>
              <select v-model="form.parentId">
                <option value="">Top level</option>
                <option v-for="category in availableParentCategories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
              <small v-if="formErrors.parentId">{{ formErrors.parentId }}</small>
            </label>

            <label class="field" :class="{ 'field-error': formErrors.sortOrder }">
              <span>Sort order</span>
              <input v-model.number="form.sortOrder" type="number" min="0" step="1">
              <small v-if="formErrors.sortOrder">{{ formErrors.sortOrder }}</small>
            </label>
          </div>

          <label class="field" :class="{ 'field-error': formErrors.description }">
            <span>Description</span>
            <textarea v-model.trim="form.description" rows="5" maxlength="2000" placeholder="Used for browsing and reporting."></textarea>
            <small v-if="formErrors.description">{{ formErrors.description }}</small>
          </label>

          <div v-if="submitError" class="alert alert-error">{{ submitError }}</div>
          <div v-if="submitSuccess" class="alert alert-success">{{ submitSuccess }}</div>

          <div class="form-actions">
            <p class="helper-copy">
              Keep the tree small and stable. Inventory items should move between categories without breaking pricing lists.
            </p>
            <div class="action-row">
              <button class="btn btn-primary" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Saving...' : isEditing ? 'Update category' : 'Create category' }}
              </button>
              <button
                v-if="isEditing"
                class="btn btn-danger"
                type="button"
                :disabled="isDeleting"
                @click="deleteCategory(form.id)"
              >
                {{ isDeleting ? 'Deleting...' : 'Delete category' }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">Structure</p>
            <h2>Category map</h2>
          </div>

          <label class="search-field">
            <input v-model.trim="searchTerm" type="text" placeholder="Search categories">
          </label>
        </div>

        <div v-if="isLoading" class="empty-state">Loading categories...</div>
        <div v-else-if="filteredCategories.length === 0" class="empty-state">
          No categories match the current filter.
        </div>

        <div v-else class="card-list">
          <article v-for="category in filteredCategories" :key="category.id" class="resource-card">
            <div class="resource-head">
              <div>
                <h3>{{ category.name }}</h3>
                <p class="resource-subtitle">
                  <span class="token">{{ category.slug }}</span>
                  <span v-if="category.parent">Under {{ category.parent.name }}</span>
                  <span v-else>Top-level category</span>
                </p>
              </div>
              <button class="btn btn-ghost" type="button" @click="startEditing(category)">Edit</button>
            </div>

            <p class="resource-description">
              {{ category.description || 'No category description yet.' }}
            </p>

            <div class="resource-metrics">
              <span class="metric-pill">Children {{ category.children.length }}</span>
              <span class="metric-pill">Items {{ category.inventoryItems.length }}</span>
              <span class="metric-pill">Order {{ category.sortOrder }}</span>
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

const CATEGORIES_QUERY = /* GraphQL */ `
  query CategoriesWorkspace {
    categories {
      id
      name
      slug
      description
      sortOrder
      updatedAt
      parent {
        id
        name
      }
      children {
        id
      }
      inventoryItems {
        id
      }
    }
  }
`

const CREATE_CATEGORY_MUTATION = /* GraphQL */ `
  mutation CreateCategory($name: String!, $slug: String!, $description: String, $sortOrder: Int, $parentId: String) {
    createCategory(name: $name, slug: $slug, description: $description, sortOrder: $sortOrder, parentId: $parentId) {
      id
    }
  }
`

const UPDATE_CATEGORY_MUTATION = /* GraphQL */ `
  mutation UpdateCategory($id: String!, $name: String, $slug: String, $description: String, $sortOrder: Int, $parentId: String) {
    updateCategory(id: $id, name: $name, slug: $slug, description: $description, sortOrder: $sortOrder, parentId: $parentId) {
      id
    }
  }
`

const DELETE_CATEGORY_MUTATION = /* GraphQL */ `
  mutation DeleteCategory($id: String!) {
    deleteCategory(id: $id)
  }
`

const categories = ref([])
const searchTerm = ref('')
const isLoading = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const loadError = ref('')
const submitError = ref('')
const submitSuccess = ref('')
const formErrors = ref({})
const slugManuallyEdited = ref(false)
const form = ref(createEmptyForm())

const isEditing = computed(() => Boolean(form.value.id))
const rootCategoryCount = computed(() => categories.value.filter((category) => !category.parent).length)
const assignedInventoryCount = computed(() => categories.value.reduce((total, category) => total + category.inventoryItems.length, 0))
const availableParentCategories = computed(() => categories.value.filter((category) => category.id !== form.value.id))
const filteredCategories = computed(() => {
  if (searchTerm.value === '') {
    return categories.value
  }

  const query = searchTerm.value.toLowerCase()

  return categories.value.filter((category) =>
    category.name.toLowerCase().includes(query)
    || category.slug.toLowerCase().includes(query)
    || (category.description ?? '').toLowerCase().includes(query)
    || (category.parent?.name ?? '').toLowerCase().includes(query)
  )
})

function createEmptyForm() {
  return {
    id: '',
    name: '',
    slug: '',
    parentId: '',
    sortOrder: 0,
    description: '',
  }
}

function normalizeCategory(category) {
  return {
    ...category,
    children: category.children ?? [],
    inventoryItems: category.inventoryItems ?? [],
  }
}

function slugify(value) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function handleNameInput() {
  if (!slugManuallyEdited.value && !isEditing.value) {
    form.value.slug = slugify(form.value.name)
  }
}

function startEditing(category) {
  form.value = {
    id: category.id,
    name: category.name,
    slug: category.slug,
    parentId: category.parent?.id ?? '',
    sortOrder: category.sortOrder,
    description: category.description ?? '',
  }
  slugManuallyEdited.value = true
  formErrors.value = {}
  submitError.value = ''
  submitSuccess.value = ''
}

function resetForm() {
  form.value = createEmptyForm()
  formErrors.value = {}
  submitError.value = ''
  submitSuccess.value = ''
  slugManuallyEdited.value = false
}

function toMutationVariables() {
  return {
    name: form.value.name,
    slug: form.value.slug,
    parentId: form.value.parentId || null,
    sortOrder: Number.isFinite(form.value.sortOrder) ? form.value.sortOrder : 0,
    description: form.value.description || null,
  }
}

function readErrorMessage(error, fallback) {
  return error?.response?.errors?.[0]?.message ?? fallback
}

function readFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors ?? {}
}

async function loadCategories() {
  isLoading.value = true
  loadError.value = ''

  try {
    const data = await graphqlRequest(CATEGORIES_QUERY)
    categories.value = (data.categories ?? []).map(normalizeCategory)
  } catch (error) {
    loadError.value = readErrorMessage(error, 'Unable to load categories right now.')
  } finally {
    isLoading.value = false
  }
}

async function submitCategory() {
  isSubmitting.value = true
  submitError.value = ''
  submitSuccess.value = ''
  formErrors.value = {}

  const variables = toMutationVariables()

  try {
    if (isEditing.value) {
      await graphqlRequest(UPDATE_CATEGORY_MUTATION, { id: form.value.id, ...variables })
      submitSuccess.value = `Category "${form.value.name}" was updated.`
    } else {
      await graphqlRequest(CREATE_CATEGORY_MUTATION, variables)
      submitSuccess.value = `Category "${form.value.name}" was created.`
      resetForm()
    }

    await loadCategories()
  } catch (error) {
    formErrors.value = readFieldErrors(error)
    submitError.value = readErrorMessage(error, 'Unable to save the category.')
  } finally {
    isSubmitting.value = false
  }
}

async function deleteCategory(categoryId) {
  if (!window.confirm('Delete this category? Inventory items will lose the category assignment.')) {
    return
  }

  isDeleting.value = true
  submitError.value = ''
  submitSuccess.value = ''

  try {
    await graphqlRequest(DELETE_CATEGORY_MUTATION, { id: categoryId })
    submitSuccess.value = 'Category deleted.'
    resetForm()
    await loadCategories()
  } catch (error) {
    submitError.value = readErrorMessage(error, 'Unable to delete the category.')
  } finally {
    isDeleting.value = false
  }
}

onMounted(loadCategories)
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
  background: linear-gradient(135deg, #f7fbff 0%, #edf4ff 100%);
}

.eyebrow,
.panel-kicker {
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.72rem;
  color: #5d759a;
  margin-bottom: 10px;
}

h1,
h2,
h3 {
  color: #163153;
}

.hero-copy,
.resource-description,
.helper-copy,
.resource-subtitle {
  color: #53657f;
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
  color: #60799f;
  font-size: 0.85rem;
  margin-bottom: 8px;
}

.page-grid {
  display: grid;
  grid-template-columns: minmax(320px, 420px) minmax(0, 1fr);
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

.field {
  display: grid;
  gap: 8px;
  color: #284363;
  font-size: 0.95rem;
}

.field input,
.field textarea,
.field select,
.search-field input {
  width: 100%;
  border: 1px solid #c9d7ea;
  border-radius: 14px;
  padding: 12px 14px;
  font: inherit;
  color: #163153;
  background: #fbfdff;
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

.resource-card {
  padding: 20px;
}

.resource-subtitle {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 6px;
}

.resource-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.metric-pill,
.token {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 7px 12px;
  background: #edf4ff;
  color: #20456f;
  font-size: 0.82rem;
}

.btn {
  border: none;
  border-radius: 14px;
  padding: 11px 16px;
  font: inherit;
  cursor: pointer;
}

.btn-primary {
  background: #1f6feb;
  color: white;
}

.btn-secondary,
.btn-ghost {
  background: #edf4ff;
  color: #163153;
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
  color: #60799f;
}

@media (max-width: 1100px) {
  .hero-card,
  .page-grid {
    grid-template-columns: 1fr;
  }

  .hero-card {
    flex-direction: column;
  }

  .hero-metrics {
    min-width: 0;
  }
}

@media (max-width: 720px) {
  .field-grid,
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
