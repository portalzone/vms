<template>
  <div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="sectiontitle">Profile Summary</h2>

    <!-- Profile Display -->
    <div class="flex gap-4 mb-4 items-center">
      <img v-if="avatarUrl" :src="avatarUrl" class="profile-avatar border" />
      <div>
        <p><strong>Name:</strong> {{ form.name }}</p>
        <p><strong>Email:</strong> {{ form.email }}</p>
        <p v-if="form.phone"><strong>Phone:</strong> {{ form.phone }}</p>
      </div>
    </div>


    <!-- Edit Button -->
    <button @click="openModal" class="button">Edit Profile</button>
        <!-- Profile History -->
<!-- Profile History -->
<div v-if="history.length" class="mt-8">
  <h2 class="sectiontitle">Profile History</h2>

  <ul class="text-sm text-gray-700 space-y-3">
    <li v-for="(log, index) in history" :key="index" class="bg-gray-50 p-3 rounded border">
      <p><strong>{{ log.date }}</strong> — {{ log.description }}</p>
      <div v-if="Object.keys(log.changes).length">
        <p class="text-xs text-gray-500">Updated fields:</p>
        <ul class="list-disc ml-5 text-xs">
          <li v-for="(newVal, key) in log.changes" :key="key">
            {{ key }}: <span class="text-green-600">{{ newVal }}</span>
            <span v-if="log.old[key]"> (was <span class="text-red-600">{{ log.old[key] }}</span>)</span>
          </li>
        </ul>
      </div>
    </li>
  </ul>

<!-- Enhanced Pagination -->
<div v-if="totalPages > 1" class="mt-6 flex justify-center items-center gap-2 flex-wrap text-sm">
  <button
    :disabled="page === 1"
    @click="changePage(page - 1)"
    class="btn-pagination"
  >
    Prev
  </button>

  <button
    v-for="p in visiblePages"
    :key="`page-${p}`"
    @click="typeof p === 'number' && changePage(p)"
    :class="[
      'btn-pagination',
      {
        'bg-blue-600 text-white': p === page,
        'pointer-events-none text-gray-500': p === '...'
      }
    ]"
    :disabled="p === '...'"
  >
    {{ p }}
  </button>

  <button
    :disabled="page === totalPages"
    @click="changePage(page + 1)"
    class="btn-pagination"
  >
    Next
  </button>
</div>

</div>



    <!-- Modal -->
<!-- Modal -->
<div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
  <div class="modal-content">
    <button @click="closeModal" class="modal-close">✖</button>
    <h3 class="section-title">Edit Profile</h3>

    <form @submit.prevent="updateProfile" class="space-y-4 mt-4" enctype="multipart/form-data">
          <!-- Name -->
          <div>
            <label class="label">Name</label>
            <input v-model="form.name" type="text" class="input" />
            <p v-if="errors.name" class="input-error">{{ errors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" />
            <p v-if="errors.email" class="input-error">{{ errors.email[0] }}</p>
          </div>

          <!-- Phone -->
          <div>
            <label class="label">Phone</label>
            <input v-model="form.phone" type="text" class="input" />
            <p v-if="errors.phone" class="input-error">{{ errors.phone[0] }}</p>
          </div>

          <!-- Avatar -->
          <div>
            <label class="label">Avatar</label>
            <input type="file" @change="handleAvatarChange" class="input" />
            <p v-if="errors.avatar" class="input-error">{{ errors.avatar[0] }}</p>
            <div v-if="previewUrl || avatarUrl" class="mt-2 w-16 h-16">
              <img :src="previewUrl || avatarUrl" class="profile-avatar" />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="label">New Password</label>
            <input v-model="form.password" type="password" class="input" />
            <p v-if="errors.password" class="input-error">{{ errors.password[0] }}</p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="label">Confirm Password</label>
            <input v-model="form.password_confirmation" type="password" class="input" />
          </div>

          <!-- Submit -->
          <div class="pt-4">
            <button type="submit" class="button" :disabled="loading">
              <span v-if="loading">Updating...</span>
              <span v-else>Update Profile</span>
            </button>

            <p v-if="successMessage" class="toast-success mt-2 text-sm">{{ successMessage }}</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import axios from '@/axios'

const isModalOpen = ref(false)
const avatarFile = ref(null)
const avatarUrl = ref(null)
const previewUrl = ref(null)

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})


const errors = ref({})
const successMessage = ref('')
const loading = ref(false)

const history = ref([])
const historyMeta = ref({
  current_page: 1,
  last_page: 1,
})

// Fetch history with pagination
const fetchHistory = async (pageNumber = 1) => {
  try {
    const { data } = await axios.get(`/profile/history?page=${pageNumber}`)

    history.value = data.data
    historyMeta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
    }

    page.value = data.current_page
    totalPages.value = data.last_page
  } catch (error) {
    console.error('Failed to fetch history:', error)
  }
}


const page = ref(1)
const totalPages = ref(1)
const visiblePages = computed(() => {
  const range = []
  const delta = 2

  const left = Math.max(2, page.value - delta)
  const right = Math.min(totalPages.value - 1, page.value + delta)

  range.push(1)
  if (left > 2) range.push('...')

  for (let i = left; i <= right; i++) {
    range.push(i)
  }

  if (right < totalPages.value - 1) range.push('...')
  if (totalPages.value > 1) range.push(totalPages.value)

  return range
})

const changePage = (newPage) => {
  if (newPage !== page.value && newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage
    fetchHistory(newPage)
  }
}



const openModal = () => {
  isModalOpen.value = true
  document.body.style.overflow = 'hidden' // prevent scroll
  successMessage.value = ''
  errors.value = {}
}

const closeModal = () => {
  isModalOpen.value = false
  document.body.style.overflow = '' // restore scroll
  form.value.password = ''
  form.value.password_confirmation = ''
  previewUrl.value = null
}


const handleEscape = (e) => {
  if (e.key === 'Escape' && isModalOpen.value) {
    closeModal()
  }
}

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleEscape)
})

const fetchProfile = async () => {
  try {
    const { data } = await axios.get('/profile')
    form.value.name = data.name
    form.value.email = data.email
    form.value.phone = data.phone
    avatarUrl.value = data.avatar_url
  } catch (error) {
    console.error('Failed to fetch profile:', error)
  }
}

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    avatarFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

const updateProfile = async () => {
  loading.value = true
  successMessage.value = ''
  errors.value = {}

  const payload = new FormData()
  payload.append('name', form.value.name)
  payload.append('email', form.value.email)
  if (form.value.phone) payload.append('phone', form.value.phone)
  if (form.value.password) {
    payload.append('password', form.value.password)
    payload.append('password_confirmation', form.value.password_confirmation)
  }
  if (avatarFile.value) payload.append('avatar', avatarFile.value)

  try {
    const response = await axios.post('/profile?_method=PUT', payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    successMessage.value = response.data.message
    avatarUrl.value = response.data.avatar_url
    form.value.password = ''
    form.value.password_confirmation = ''
    previewUrl.value = null
    isModalOpen.value = false

    await fetchProfile()  // 👈 Refresh UI after update

  } catch (err) {
    if (err.response && err.response.data && err.response.data.errors) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProfile()
  fetchHistory()
  window.addEventListener('keydown', handleEscape)
})

</script>
