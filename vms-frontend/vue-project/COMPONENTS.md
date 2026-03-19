# Frontend Component Reference

This covers every reusable component in `src/components/`. All components use Vue 3 `<script setup>` syntax.

---

## Modal

**File:** `src/components/Modal.vue`

A simple confirmation dialog overlay, used whenever a destructive action needs a second confirmation (delete vehicle, remove driver, etc.).

### Props

| Prop | Type | Required | Description |
|---|---|---|---|
| `title` | `String` | yes | Heading shown at the top of the modal |
| `message` | `String` | yes | Body text. Supports HTML via `v-html`, so you can bold a name or plate number in the message |

### Emits

| Event | When |
|---|---|
| `close` | User clicks Cancel |
| `confirm` | User clicks Delete |

### Usage

```vue
<Modal
  v-if="showDeleteModal"
  title="Delete vehicle"
  :message="`Are you sure you want to remove <strong>${vehicle.plate_number}</strong>? This cannot be undone.`"
  @close="showDeleteModal = false"
  @confirm="handleDelete"
/>
```

### Notes

- The modal always shows a Cancel and a Delete button. If you need a modal with different button labels, use `ModalNotification` instead, which has a `showConfirm` prop and emits the same events.
- The backdrop covers the full screen with a semi-transparent black overlay. Clicking it does nothing — the user must use one of the buttons.

---

## ModalNotification

**File:** `src/components/ModalNotification.vue`

A more flexible modal that can function as either a read-only notification or a confirm/cancel dialog, depending on the `showConfirm` prop.

### Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `show` | `Boolean` | `false` | Controls whether the modal is visible |
| `title` | `String` | `'Notification'` | Modal heading |
| `message` | `String` | `''` | Body content. Supports HTML via `v-html` |
| `showConfirm` | `Boolean` | `false` | When `true`, shows Cancel and Confirm buttons instead of just a close button |

### Emits

| Event | When |
|---|---|
| `close` | Modal is dismissed (close button or Cancel) |
| `confirm` | User clicks Confirm (only relevant when `showConfirm` is true) |

### Usage — notification only

```vue
<ModalNotification
  :show="showSuccess"
  title="Trip saved"
  message="The trip log has been recorded successfully."
  @close="showSuccess = false"
/>
```

### Usage — with confirmation

```vue
<ModalNotification
  :show="showConfirmModal"
  title="End trip?"
  message="Marking this trip as completed will lock the record."
  :showConfirm="true"
  @close="showConfirmModal = false"
  @confirm="endTrip"
/>
```

---

## Pagination

**File:** `src/components/Pagination.vue`

Renders page number buttons for paginated API responses. Expects the Laravel pagination meta object.

### Props

| Prop | Type | Required | Description |
|---|---|---|---|
| `meta` | `Object` | yes | The pagination metadata from a Laravel API response. Must include `current_page` and `last_page` |

### Emits

| Event | Payload | Description |
|---|---|---|
| `page-changed` | `Number` | The page number the user clicked |

### Usage

```vue
<script setup>
const { data: vehicles, meta } = await fetchVehicles(currentPage)

function onPageChange(page) {
  currentPage.value = page
}
</script>

<template>
  <VehicleTable :vehicles="vehicles" />
  <Pagination :meta="meta" @page-changed="onPageChange" />
</template>
```

### Notes

- The component renders nothing if there is only one page (`pages.length <= 1`).
- The active page button gets a blue background. All other pages are white.
- Prev and Next buttons are disabled when you're at the first or last page.

---

## Toast

**File:** `src/components/Toast.vue`

A quick notification banner in the top-right corner of the screen. It disappears on its own after a short delay. Used for brief confirmations ("Record saved", "Delete failed") where you don't want to block the UI with a modal.

### Exposed method

The component exposes `showToast()` via `defineExpose`. Call it through a template ref.

```ts
showToast(message: string, duration?: number, color?: 'success' | 'error')
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `message` | `string` | — | The text to display |
| `duration` | `number` | `3000` | How long to show it, in milliseconds |
| `color` | `string` | `'success'` | `'success'` shows green, `'error'` shows red |

### Usage

```vue
<script setup>
import { ref } from 'vue'
import Toast from '@/components/Toast.vue'

const toast = ref(null)

async function saveDriver() {
  try {
    await api.post('/drivers', form)
    toast.value.showToast('Driver saved successfully')
  } catch {
    toast.value.showToast('Failed to save driver', 3000, 'error')
  }
}
</script>

<template>
  <Toast ref="toast" />
  <button @click="saveDriver">Save</button>
</template>
```

---

## Notification

**File:** `src/components/Notification.vue`

Similar to `Toast` but renders multi-line messages with `<pre>` formatting. Useful when you want to display a list of validation errors or a formatted API message. Stays visible for 4 seconds.

### Exposed method

```ts
showNotification(message: string, type?: 'success' | 'error')
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `message` | `string` | — | The message to show. Newlines are preserved |
| `type` | `string` | `'success'` | `'success'` = green, anything else = red |

### Usage

```vue
<script setup>
import { ref } from 'vue'
import Notification from '@/components/Notification.vue'

const notification = ref(null)

function handleErrors(errors) {
  const message = Object.values(errors).flat().join('\n')
  notification.value.showNotification(message, 'error')
}
</script>

<template>
  <Notification ref="notification" />
</template>
```

### Difference from Toast

`Toast` is for short single-line messages. `Notification` is for anything multi-line — validation error lists, detailed API messages, formatted output.

---

## Card

**File:** `src/components/Card.vue`

A white rounded container used on dashboards to show a single stat with an optional subtitle label.

### Props

| Prop | Type | Required | Description |
|---|---|---|---|
| `title` | `String` | yes | The card heading, shown in medium weight above the value |
| `subtitle` | `String` | no | Small muted label below the value, useful for context like "as of today" |

### Slots

| Slot | Description |
|---|---|
| default | The main content of the card — typically a large number or value |

### Usage

```vue
<Card title="Total vehicles" subtitle="fleet registered">
  {{ stats.vehicles }}
</Card>

<Card title="Active trips">
  {{ stats.active_trips }}
</Card>
```

---

## SideBar

**File:** `src/components/SideBar.vue`

The main application shell. Wraps all authenticated pages with a fixed left sidebar and a scrollable main content area.

### Slots

| Slot | Description |
|---|---|
| default | The page content, rendered in the `<main>` area to the right of the sidebar |

### What it does

- Renders the VMS navigation links (Dashboard, Vehicles, Drivers, Check-Ins, Maintenances, Expenses, Users).
- Highlights the active link using Vue Router's `active-class`.
- Provides a Logout button that calls `POST /api/logout`, clears `localStorage`, and redirects to `/login`.

### Notes

This component is not meant to be used directly in views. It's included inside `AuthenticatedLayout.vue`, which wraps all protected routes. If you need to add a new nav link, add it here.

---

## VehicleTable

**File:** `src/components/VehicleTable.vue`

A reusable table for displaying a list of vehicles. Used on the main Vehicles page and anywhere else vehicles need to be listed in a consistent format.

---

## StatsChart

**File:** `src/components/StatsChart.vue`

A bar or pie chart showing aggregate stats (vehicle count, driver count, expense totals, etc.) on the admin dashboard.

---

## TrendsChart

**File:** `src/components/TrendsChart.vue`

A line chart showing monthly income and expense trends for the current year. Used on the main dashboard and the vehicle owner dashboard.

---

## RecentActivity

**File:** `src/components/RecentActivity.vue`

A feed component showing the latest events across the system — recent check-ins, new trips, maintenance updates. Reads from `GET /api/dashboard/recent`.

---

## Icon components

All icon components are in `src/components/icons/`. They're simple SVG wrappers with no props and are used inline in the sidebar and navigation.

| Component | Used for |
|---|---|
| `IconDashboard.vue` | Dashboard nav link |
| `IconVehicle.vue` | Vehicles nav link |
| `IconDriver.vue` | Drivers nav link |
| `IconCheckInOut.vue` | Check-In/Out nav link |
| `IconMaintenance.vue` | Maintenance nav link |
| `IconExpense.vue` | Expenses nav link |
| `IconAbout.vue` | About page |
| `IconCommunity.vue` | General use |
| `IconDocumentation.vue` | General use |
| `IconEcosystem.vue` | General use |
| `IconSupport.vue` | Support page |
| `IconTooling.vue` | General use |
