<template>
  <div class="support-container">
    <h1 class="support-heading">Support & Contact</h1>

    <p class="support-text">
      If you need help using the Vehicle Management System or encounter any issues, please contact the system administrator or reach out via any of the support channels below.
    </p>

    <div class="support-section">
      <div>
        <h2 class="support-subheading">📧 Email Support</h2>
        <p class="support-text">muojekevictor@gmail.com</p>
      </div>

      <div>
        <h2 class="support-subheading">📱 WhatsApp</h2>
        <p class="support-text">+234 813 701 3267</p>
      </div>

      <div>
        <h2 class="support-subheading">📘 User Guide</h2>
        <p class="support-text">
          Visit the
          <a href="#" class="support-link">online documentation</a>
          for walkthroughs, tips, and best practices.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
// No logic needed
</script>

<style scoped>
.support-container {
  max-width: 48rem; /* max-w-3xl */
  margin: 0 auto;
  padding: 2rem 1rem;
  background-color: #ffffff;
  border-radius: 0.5rem;
}

.support-heading {
  font-size: 1.875rem; /* text-3xl */
  font-weight: bold;
  color: #1f2937; /* text-gray-800 */
  margin-bottom: 1.5rem;
}

.support-subheading {
  font-size: 1.125rem; /* text-lg */
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.support-text {
  color: #374151; /* text-gray-700 */
  margin-bottom: 1rem;
  line-height: 1.6;
}

.support-section > div {
  margin-bottom: 1.25rem;
}

.support-link {
  color: #2563eb; /* blue-600 */
  text-decoration: none;
}

.support-link:hover {
  text-decoration: underline;
}
</style>
