<template>
  <div class="outer-wrapper">
    <div class="dashboard-grid">
      <div class="dashboard-item">
        <div class="icon-wrapper">
          <slot name="icon"></slot>
        </div>
        <div class="content">
          <h3 class="heading">
            <slot name="heading"></slot>
          </h3>
          <p class="description">
            <slot></slot>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.outer-wrapper {
  max-width: 1120px; /* Matches max-w-7xl */
  margin-left: auto;
  margin-right: auto;
  padding-left: 1rem;
  padding-right: 1rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

@media (min-width: 768px) {
  .dashboard-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.dashboard-item {
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.3s ease;
}

.dashboard-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.icon-wrapper {
  width: 48px;
  height: 48px;
  background-color: #f3f4f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  font-size: 1.5rem;
  color: #4b5563;
}

.content {
  flex: 1;
}

.heading {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.description {
  font-size: 0.95rem;
  color: #6b7280;
  margin-top: 0.25rem;
}
</style>
