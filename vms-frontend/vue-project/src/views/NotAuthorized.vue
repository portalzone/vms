<!-- src/views/NotAuthorized.vue -->
<template>
  <div class="text-center py-20">
    <h1 class="text-4xl font-bold text-red-600 mb-4">Access Denied</h1>
    <p class="text-gray-700">You do not have permission to view this page.</p>
    <RouterLink to="/dashboard" class="mt-6 inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
      Return to Dashboard
    </RouterLink>
  </div>
</template>
