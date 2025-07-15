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
/* ===========================
   🌐 Global Layout Styles
   =========================== */
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

/* ===========================
   ✍️ Typography
   =========================== */
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

/* ===========================
   🧾 Table Styles
   =========================== */
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

/* ===========================
   🏷️ Labels
   =========================== */
label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  display: block;
  margin-bottom: 0.25rem;
}

/* ===========================
   ✏️ Inputs & Textareas
   =========================== */
input,
textarea,
select {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background-color: white;
  color: #111827;
  font-size: 0.875rem;
  font-family: inherit;
  transition: border-color 0.3s, box-shadow 0.3s;
  outline: none;
}

input:focus,
textarea:focus,
select:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

textarea {
  resize: vertical;
  min-height: 100px;
}

/* Tablet and Desktop Inputs/Textareas: reduce width */
@media (min-width: 768px) {
  input,
  textarea,
  select {
    width: 50%;
  }
}

/* ================================
   🔘 Primary Button Styles (Global)
   ================================ */
/* Shared base button style */
.btn {
  display: inline-block;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.btn-link {
  background: none;
  border: none;
  padding: 0;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: color 0.2s ease, text-decoration 0.2s ease;
  text-decoration: none;
}
/* ✅ Submit Button (Green) */
.btn-submit {
  background-color: #16a34a; /* Tailwind green-600 */
  color: #ffffff;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-submit:hover {
  background-color: #15803d; /* Tailwind green-700 */
}

/* 🚫 Cancel Button */
.btn-cancel {
  background-color: transparent;
  border: 1px solid #d1d5db; /* Tailwind gray-300 */
  color: #374151; /* Tailwind gray-700 */
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  transition: background-color 0.2s ease;
  text-align: center;
  display: inline-block;
}

.btn-cancel:hover {
  background-color: #f3f4f6; /* Tailwind gray-100 */
}


/* 🌐 Neutral Secondary Button (e.g., Cancel) */
.btn-secondary {
  display: inline-block;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151; /* Tailwind gray-700 */
  background-color: #f9fafb; /* Tailwind gray-50 */
  border: 1px solid #d1d5db; /* Tailwind gray-300 */
  border-radius: 0.375rem; /* rounded-md */
  text-align: center;
  transition: background-color 0.2s ease, border-color 0.2s ease;
  text-decoration: none;
}

.btn-secondary:hover {
  background-color: #f3f4f6; /* Tailwind gray-100 */
  border-color: #9ca3af;     /* Tailwind gray-400 */
  color: #111827;            /* Tailwind gray-900 */
}


/* ✏️ Edit Button */
.btn-edit {
  color: #2563eb; /* Tailwind blue-600 */
  font-weight: 500;
  font-size: 0.875rem;
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  padding: 0.25rem 0.5rem;
  transition: color 0.2s ease;
}

.btn-edit:hover {
  color: #1d4ed8; /* Tailwind blue-700 */
}

/* 🗑️ Delete Button */
.btn-delete {
  color: #dc2626; /* Tailwind red-600 */
  font-weight: 500;
  font-size: 0.875rem;
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
  padding: 0.25rem 0.5rem;
  transition: color 0.2s ease;
}

.btn-delete:hover {
  color: #b91c1c; /* Tailwind red-700 */
}



/* 🌐 Primary Button (Add / Submit actions) */
.btn-primary {
  display: inline-block;
  background-color: #2563eb; /* Tailwind blue-600 */
  color: #ffffff;
  padding: 0.5rem 1rem;
  font-size: 0.875rem; /* text-sm */
  font-weight: 600;
  border: none;
  border-radius: 0.375rem;
  text-align: center;
  text-decoration: none;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-primary:hover {
  background-color: #1d4ed8; /* Tailwind blue-700 */
}

.btn-primary:active {
  transform: scale(0.98);
}

.btn-primary:disabled {
  background-color: #9ca3af; /* Tailwind gray-400 */
  cursor: not-allowed;
}


/* ===========================
   📤 Submit Button
   =========================== */

button[type="submit"] {
  background-color: #2563eb;
  color: #ffffff;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.2s ease;
}

button[type="submit"]:hover {
  background-color: #1d4ed8;
}

button[type="submit"]:active {
  transform: scale(0.98);
}

button[type="submit"]:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

/* ===========================
   🔽 Select Dropdown
   =========================== */
select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
}

/* ===========================
   🧩 Options (Dropdown Items)
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
  accent-color: #4f46e5;
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
   🔎 Search Inputs
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

