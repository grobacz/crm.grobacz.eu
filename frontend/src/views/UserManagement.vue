<template>
  <div class="users-page">
    <section class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Administration</p>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">
          Create and manage user accounts, assign roles, and control access to the CRM.
        </p>
      </div>

      <div class="hero-stats">
        <div class="stat-chip">
          <span class="stat-label">Total users</span>
          <strong class="stat-value">{{ users.length }}</strong>
        </div>
        <router-link to="/settings" class="btn btn-secondary">Back to Settings</router-link>
      </div>
    </section>

    <div class="content-grid">
      <section class="panel form-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">{{ isEditing ? 'Edit user' : 'New user' }}</p>
            <h2>{{ isEditing ? 'Update account' : 'Create account' }}</h2>
          </div>
        </div>

        <form @submit.prevent="submitForm">
          <div class="field-grid">
            <label class="field field-full" :class="{ 'field-has-error': formErrors.name }">
              <span>Name</span>
              <input v-model.trim="form.name" type="text" placeholder="Jane Smith" maxlength="255">
              <small v-if="formErrors.name" class="field-error">{{ formErrors.name }}</small>
            </label>

            <label class="field field-full" :class="{ 'field-has-error': formErrors.email }">
              <span>Email</span>
              <input v-model.trim="form.email" type="email" placeholder="jane@example.com" maxlength="255">
              <small v-if="formErrors.email" class="field-error">{{ formErrors.email }}</small>
            </label>

            <label class="field">
              <span>Role</span>
              <select v-model="form.role" class="field-select">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="user">User</option>
              </select>
            </label>

            <label class="field">
              <span>Status</span>
              <select v-model="form.status" class="field-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </label>

            <label class="field field-full">
              <span>Avatar Color</span>
              <div class="color-input-row">
                <input v-model="form.avatarColor" type="color" class="color-picker">
                <span class="color-value">{{ form.avatarColor || '#667eea' }}</span>
              </div>
            </label>
          </div>

          <div v-if="submitError" class="alert alert-error">{{ submitError }}</div>
          <div v-if="submitSuccess" class="alert alert-success">{{ submitSuccess }}</div>

          <div class="form-actions">
            <button v-if="isEditing" type="button" class="btn btn-tertiary" @click="cancelEdit">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? (isEditing ? 'Saving...' : 'Creating...') : (isEditing ? 'Save User' : 'Create User') }}
            </button>
          </div>
        </form>
      </section>

      <section class="panel list-panel">
        <div class="panel-header">
          <div>
            <p class="panel-kicker">User list</p>
            <h2>Team Members</h2>
          </div>
          <span class="panel-note">{{ users.length }} account{{ users.length === 1 ? '' : 's' }}</span>
        </div>

        <div v-if="isLoading" class="list-state">Loading users...</div>

        <div v-else-if="users.length === 0" class="list-state">
          <strong>No users yet.</strong>
          <span>Create the first user using the form.</span>
        </div>

        <div v-else class="user-list">
          <article v-for="user in users" :key="user.id" class="user-card">
            <div class="user-identity">
              <div class="user-avatar-large" :style="{ background: user.avatarColor || '#667eea' }">
                {{ user.initials }}
              </div>
              <div class="user-copy">
                <div class="user-title-row">
                  <h3>{{ user.name }}</h3>
                  <span class="role-badge" :class="`role-${user.role}`">{{ user.role }}</span>
                  <span class="status-badge" :class="`status-${user.status}`">{{ user.status }}</span>
                </div>
                <p class="user-email">{{ user.email }}</p>
              </div>
            </div>

            <div class="user-actions">
              <button class="action-btn edit-btn" @click="startEdit(user)" :disabled="isSubmitting && editingId === user.id">
                {{ editingId === user.id ? 'Editing' : 'Edit' }}
              </button>
              <button class="action-btn delete-btn" @click="deleteUser(user)" :disabled="isSubmitting">
                Delete
              </button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { graphqlRequest } from '../graphql/client'

const USERS_QUERY = `
  query Users {
    users {
      id
      name
      email
      role
      status
      avatarColor
      initials
      createdAt
    }
  }
`

const CREATE_USER_MUTATION = `
  mutation CreateUser($name: String!, $email: String!, $role: String, $status: String, $avatarColor: String) {
    createUser(name: $name, email: $email, role: $role, status: $status, avatarColor: $avatarColor) {
      id name email role status avatarColor initials createdAt
    }
  }
`

const UPDATE_USER_MUTATION = `
  mutation UpdateUser($id: String!, $name: String, $email: String, $role: String, $status: String, $avatarColor: String) {
    updateUser(id: $id, name: $name, email: $email, role: $role, status: $status, avatarColor: $avatarColor) {
      id name email role status avatarColor initials createdAt
    }
  }
`

const DELETE_USER_MUTATION = `
  mutation DeleteUser($id: String!) {
    deleteUser(id: $id)
  }
`

const users = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const editingId = ref(null)
const submitError = ref('')
const submitSuccess = ref('')

const form = reactive({
  name: '',
  email: '',
  role: 'user',
  status: 'active',
  avatarColor: '#667eea'
})

const formErrors = reactive({
  name: '',
  email: ''
})

const isEditing = computed(() => editingId.value !== null)

async function loadUsers() {
  isLoading.value = true
  try {
    const data = await graphqlRequest(USERS_QUERY)
    users.value = data.users || []
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    isLoading.value = false
  }
}

function startEdit(user) {
  editingId.value = user.id
  form.name = user.name
  form.email = user.email
  form.role = user.role
  form.status = user.status
  form.avatarColor = user.avatarColor || '#667eea'
  formErrors.name = ''
  formErrors.email = ''
  submitError.value = ''
  submitSuccess.value = ''
}

function cancelEdit() {
  editingId.value = null
  form.name = ''
  form.email = ''
  form.role = 'user'
  form.status = 'active'
  form.avatarColor = '#667eea'
  formErrors.name = ''
  formErrors.email = ''
  submitError.value = ''
  submitSuccess.value = ''
}

function getGraphqlFieldErrors(error) {
  return error?.response?.errors?.[0]?.extensions?.fieldErrors || {}
}

async function submitForm() {
  formErrors.name = ''
  formErrors.email = ''
  submitError.value = ''
  submitSuccess.value = ''

  isSubmitting.value = true
  try {
    if (isEditing.value) {
      const data = await graphqlRequest(UPDATE_USER_MUTATION, {
        id: editingId.value,
        name: form.name,
        email: form.email,
        role: form.role,
        status: form.status,
        avatarColor: form.avatarColor
      })
      users.value = users.value.map(u => u.id === editingId.value ? data.updateUser : u)
      submitSuccess.value = `User "${form.name}" was updated.`
      cancelEdit()
    } else {
      const data = await graphqlRequest(CREATE_USER_MUTATION, {
        name: form.name,
        email: form.email,
        role: form.role,
        status: form.status,
        avatarColor: form.avatarColor
      })
      users.value = [...users.value, data.createUser]
      submitSuccess.value = `User "${form.name}" was created.`
      form.name = ''
      form.email = ''
      form.role = 'user'
      form.status = 'active'
      form.avatarColor = '#667eea'
    }
  } catch (error) {
    const fieldErrors = getGraphqlFieldErrors(error)
    if (fieldErrors.name) formErrors.name = fieldErrors.name
    if (fieldErrors.email) formErrors.email = fieldErrors.email
    submitError.value = Object.values(fieldErrors).some(Boolean)
      ? 'Please review the highlighted fields.'
      : (error?.response?.errors?.[0]?.message || 'Failed to save user.')
  } finally {
    isSubmitting.value = false
  }
}

async function deleteUser(user) {
  if (!confirm(`Delete user "${user.name}"?`)) return
  try {
    await graphqlRequest(DELETE_USER_MUTATION, { id: user.id })
    users.value = users.value.filter(u => u.id !== user.id)
  } catch (error) {
    submitError.value = 'Failed to delete user.'
  }
}

onMounted(loadUsers)
</script>

<style scoped>
.users-page {
  display: flex;
  flex-direction: column;
  gap: 26px;
}

.hero-card {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  padding: 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top left, rgba(255, 255, 255, 0.86), transparent 34%),
    linear-gradient(135deg, #2d3748 0%, #4a5568 44%, #718096 100%);
  color: #fff;
  box-shadow: 0 22px 48px rgba(45, 55, 72, 0.22);
}

.hero-copy { max-width: 640px; }

.eyebrow, .panel-kicker {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.page-title {
  font-size: 34px;
  line-height: 1.15;
  margin-bottom: 10px;
}

.page-subtitle {
  font-size: 15px;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.86);
}

.hero-stats {
  min-width: 250px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
}

.stat-chip {
  min-width: 190px;
  padding: 18px 20px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(12px);
}

.stat-label {
  display: block;
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.8;
}

.stat-value {
  display: block;
  margin-top: 10px;
  font-size: 34px;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(320px, 390px) minmax(0, 1fr);
  gap: 24px;
  align-items: start;
}

.panel {
  border-radius: 24px;
  background: #ffffff;
  border: 1px solid #d7e2ee;
  box-shadow: 0 18px 36px rgba(15, 43, 69, 0.08);
}

.form-panel, .list-panel { padding: 24px; }

.form-panel {
  position: sticky;
  top: 24px;
  background:
    linear-gradient(180deg, rgba(240, 242, 255, 0.95), rgba(255, 255, 255, 1) 36%),
    #ffffff;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 20px;
}

.panel-header h2 {
  font-size: 22px;
  color: #12314d;
}

.panel-note {
  max-width: 240px;
  font-size: 12px;
  line-height: 1.5;
  color: #6a7f92;
  text-align: right;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #173956;
}

.field span {
  font-size: 13px;
  font-weight: 600;
}

.field input, .field-select {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #cdd8e5;
  background: #ffffff;
  color: #173956;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field input:focus, .field-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
}

.field-full { grid-column: 1 / -1; }

.field-has-error input, .field-has-error .field-select {
  border-color: #d64d4d;
  box-shadow: 0 0 0 4px rgba(214, 77, 77, 0.1);
}

.field-error {
  font-size: 12px;
  color: #c43f3f;
}

.color-input-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.color-picker {
  width: 48px;
  height: 40px;
  border: 1px solid #cdd8e5;
  border-radius: 10px;
  padding: 2px;
  cursor: pointer;
  background: #fff;
}

.color-value {
  font-size: 14px;
  color: #6a7f92;
  font-family: monospace;
}

.alert {
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  font-size: 14px;
}

.alert-error {
  background: #fff1f1;
  border: 1px solid #f2c4c4;
  color: #9e2f2f;
}

.alert-success {
  background: #eef9f1;
  border: 1px solid #c2e4ca;
  color: #256245;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 18px;
  border-radius: 999px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.15s ease, opacity 0.2s ease;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.btn:hover:not(:disabled) { transform: translateY(-1px); }

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  box-shadow: 0 14px 28px rgba(102, 126, 234, 0.26);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.18);
  color: inherit;
  border: 1px solid rgba(255, 255, 255, 0.26);
}

.btn-tertiary {
  background: #eef3f7;
  color: #45627c;
  border: 1px solid #d5e1ec;
}

.list-state {
  display: grid;
  place-items: center;
  min-height: 180px;
  border-radius: 20px;
  border: 1px dashed #d3deea;
  background: #fbfdff;
  color: #668097;
  text-align: center;
}

.user-list {
  display: grid;
  gap: 14px;
}

.user-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 18px;
  border: 1px solid #e1e8f0;
  background: #fff;
}

.user-identity {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.user-avatar-large {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: 15px;
  flex-shrink: 0;
}

.user-copy { min-width: 0; }

.user-title-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.user-title-row h3 {
  font-size: 16px;
  color: #173956;
  margin: 0;
}

.role-badge, .status-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.role-admin { background: #fde8e8; color: #c53030; }
.role-manager { background: #fef3cd; color: #975a16; }
.role-user { background: #e3f1ff; color: #1f5f9e; }

.status-active { background: #eaf7ec; color: #2c7741; }
.status-inactive { background: #f0f0f0; color: #666; }

.user-email {
  font-size: 13px;
  color: #6a7f92;
  margin: 4px 0 0;
}

.user-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.action-btn {
  padding: 8px 14px;
  border-radius: 999px;
  border: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.edit-btn {
  background: #f0f5ff;
  color: #4a6fa5;
  border: 1px solid #d5e3f5;
}

.edit-btn:hover:not(:disabled) {
  background: #e0ecff;
}

.delete-btn {
  background: #fff1f1;
  color: #c53030;
  border: 1px solid #f5cccc;
}

.delete-btn:hover:not(:disabled) {
  background: #fee2e2;
}

@media (max-width: 900px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
  .form-panel { position: static; }
  .user-card { flex-direction: column; align-items: flex-start; }
}
</style>
