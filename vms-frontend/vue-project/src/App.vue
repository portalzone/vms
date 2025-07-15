<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import axios from '@/axios'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(true)

onMounted(async () => {
  if (auth.token) {
    try {
      await auth.fetchUser()
    } catch (e) {
      console.error('❌ Failed to fetch user:', e)
    }
  }

  loading.value = false
})

const logout = async () => {
  try {
    await axios.post('/logout', {}, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  } catch (error) {
    console.warn('Logout failed:', error)
  } finally {
    auth.logout()
    router.push('/')
  }
}
</script>

<template>
  <div v-if="loading" class="p-6 text-gray-700">Loading...</div>

  <component
    v-else
    :is="auth.user ? AuthenticatedLayout : GuestLayout"
    :user="auth.user"
    :logout="logout"
  >
    <RouterView />
  </component>
</template>

<!-- Leave this empty to avoid local scoping issues -->
<style scoped></style>

<style>
html, body, #app {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f9fafb;
  color: #1f2937;
  line-height: 1.6;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
  color: #1f2937;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
h1 { font-size: 1.875rem; font-weight: 800; }
h2 { font-size: 1.5rem; }
h3 { font-size: 1.25rem; }
h4 { font-size: 1.125rem; }
h5 { font-size: 1rem; }
h6 { font-size: 0.875rem; }

p {
  color: #374151;
  font-size: 1rem;
  margin-bottom: 1rem;
}

/* Tables */
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  text-align: left;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  padding: 0.5rem 1rem;
  background-color: #f3f4f6;
}
td {
  font-size: 0.875rem;
  color: #1f2937;
  padding: 0.5rem 1rem;
  border-top: 1px solid #e5e7eb;
}

/* Labels */
label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  display: block;
  margin-bottom: 0.25rem;
}

/* Inputs and Textareas */
input,
textarea {
  width: 100%; /* Default: full width on mobile */
  padding: 0.625rem 0.75rem;
  border: 1px solid #d1d5db; /* gray-300 */
  border-radius: 0.5rem;
  background-color: white;
  color: #111827;
  font-size: 0.875rem;
  font-family: inherit;
  transition: border-color 0.3s, box-shadow 0.3s;
  outline: none;
}

/* Responsive override: 50% width on tablets and up */
@media (min-width: 768px) {
  input,
  textarea {
    width: 50%;
  }
}

input:focus,
textarea:focus {
  border-color: #4f46e5; /* indigo-600 */
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
  background-color: #ffffff;
}

textarea {
  resize: vertical;
  min-height: 100px;
}

/* Submit Button Styles */
button[type="submit"] {
  background-color: #2563eb;         /* Tailwind blue-600 */
  color: #ffffff;
  padding: 0.625rem 1.25rem;         /* py-2.5 px-5 */
  font-size: 0.875rem;               /* text-sm */
  font-weight: 600;
  border-radius: 0.5rem;             /* rounded-lg */
  border: none;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

button[type="submit"]:hover {
  background-color: #1d4ed8;         /* Tailwind blue-700 */
}

button[type="submit"]:active {
  transform: scale(0.98);
}

button[type="submit"]:disabled {
  background-color: #9ca3af;         /* Tailwind gray-400 */
  cursor: not-allowed;
}

/* ===========================
   🧾 Select Dropdown Styling
   =========================== */
select {
  width: 50%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #d1d5db;         /* gray-300 */
  border-radius: 0.5rem;
  background-color: white;
  color: #111827;                    /* gray-900 */
  font-size: 0.875rem;               /* text-sm */
  font-family: inherit;
  transition: border-color 0.3s, box-shadow 0.3s;
  appearance: none;                  /* Remove default arrow */
  background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
}

select:focus {
  border-color: #4f46e5;             /* indigo-600 */
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
  outline: none;
}

/* Optional: responsive behavior */
@media (max-width: 768px) {
  select {
    width: 100%;
  }
}

/* ===========================
   🎚️ Option Styling
   =========================== */
option {
  color: #111827;
  font-size: 0.875rem;
  background-color: #ffffff;
}

/* ===========================
   🔘 Radio Buttons
   =========================== */
input[type="radio"] {
  accent-color: #4f46e5;  /* Tailwind indigo-600 */
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
}

/* ===========================
   ☑️ Checkboxes
   =========================== */
input[type="checkbox"] {
  accent-color: #4f46e5;
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
}

/* ===========================
   🔎 Search Input (Optional)
   =========================== */
input[type="search"] {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background-color: #fff;
  color: #111827;
  font-size: 0.875rem;
}


</style>
