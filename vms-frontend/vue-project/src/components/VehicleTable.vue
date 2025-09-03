<template>
  <div>
    <!-- Filter Dropdown -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">Vehicles</h2>
      <select v-model="filter" class="border px-3 py-2 rounded text-sm">
        <option value="">All</option>
        <option value="organization">Organization</option>
        <optgroup label="Individual">
          <option value="individual:staff">Staff</option>
          <option value="individual:visitor">Visitor</option>
          <option value="individual:vehicle_owner">Vehicle Owner</option>
        </optgroup>
      </select>
    </div>

    <!-- Vehicles Table -->
    <div class="overflow-x-auto">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-gray-100 text-left text-sm font-medium">
          <tr>
            <th class="p-3 border">Plate Number</th>
            <th class="p-3 border">Model</th>
            <th class="p-3 border">Manufacturer</th>
            <th class="p-3 border">Year</th>
            <th class="p-3 border">Ownership</th>
            <th class="p-3 border">Owner / Driver</th>
            <th class="p-3 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="vehicle in filteredVehicles"
            :key="vehicle.id"
            class="hover:bg-gray-50 text-sm"
          >
            <td class="p-3 border">{{ vehicle.plate_number }}</td>
            <td class="p-3 border">{{ vehicle.model }}</td>
            <td class="p-3 border">{{ vehicle.manufacturer }}</td>
            <td class="p-3 border">{{ vehicle.year }}</td>
            <td class="p-3 border capitalize">{{ vehicle.ownership_type }}</td>

            <!-- Owner / Driver logic -->
            <td class="p-3 border">
              <!-- Organization Driver -->
              <template v-if="vehicle.ownership_type === 'organization'">
                {{ vehicle.driver?.user?.name || 'Unassigned' }}
              </template>

              <!-- Individual Owner (Staff / Visitor / Vehicle Owner) -->
              <template v-else-if="vehicle.ownership_type === 'individual'">
                <div>
                  <div>
                    {{ vehicle.owner?.name || 'Unknown' }}
                    <span v-if="vehicle.owner?.roles?.length">
                      ({{ roleLabel(vehicle.owner.roles[0]?.name) }})
                    </span>
                  </div>

                  <!-- If owner is a vehicle_owner, show drivers under them -->
                  <div v-if="vehicle.owner?.roles?.some(r => r.name === 'vehicle_owner')">
                    <div class="text-xs text-gray-500">Drivers:</div>
                    <ul class="ml-2 list-disc text-xs text-gray-700">
                      <li v-for="driver in vehicle.drivers || []" :key="driver.id">
                        {{ driver.user?.name || 'Unnamed' }}
                      </li>
                    </ul>
                  </div>
                </div>
              </template>
            </td>

            <td class="p-3 border">
              <router-link
                :to="`/vehicles/${vehicle.id}/edit`"
                class="text-blue-600 hover:underline"
              >
                Edit
              </router-link>
            </td>
          </tr>

          <tr v-if="filteredVehicles.length === 0">
            <td colspan="7" class="text-center p-4 text-gray-500">
              No vehicles found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: "VehicleTable",
  props: {
    vehicles: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      filter: "",
    };
  },
  computed: {
    filteredVehicles() {
      if (!this.filter) return this.vehicles;

      if (this.filter === "organization") {
        return this.vehicles.filter(v => v.ownership_type === "organization");
      }

      if (this.filter.startsWith("individual")) {
        const role = this.filter.split(":")[1];
        return this.vehicles.filter(
          v =>
            v.ownership_type === "individual" &&
            v.owner?.roles?.some(r => r.name === role)
        );
      }

      return this.vehicles;
    },
  },
  methods: {
    roleLabel(roleName) {
      if (!roleName) return "";
      return roleName.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase());
    },
  },
};
</script>

<style scoped>
select {
  min-width: 200px;
}
</style>
