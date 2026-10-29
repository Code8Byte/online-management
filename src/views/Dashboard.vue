<template>
  <div>
    <!-- TAMPILAN OPERATOR -->
    <dashboardoperatorView v-if="authState.role === 'operator'" />

    <!-- TAMPILAN VIEWER -->
    <dashboardviewerView v-else-if="authState.role === 'viewer'" />

    <!-- TAMPILAN ADMIN (DEFAULT) -->
    <dashboardadminView v-else />
  </div>
</template>

<script setup>
import { authState } from '../utils/api'
import dashboardoperatorView from './dashboard/dashboardoperatorView.vue'
import dashboardviewerView from './dashboard/dashboardviewerView.vue'
import dashboardadminView from './dashboard/dashboardadminView.vue'
</script>
