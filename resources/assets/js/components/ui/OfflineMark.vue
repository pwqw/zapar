<template>
  <span class="!opacity-50" :title="$t('sidebar.availableOffline')">
    <Icon :icon="faCloudArrowDown" />
  </span>
</template>

<script lang="ts" setup>
import { faCloudArrowDown } from '@fortawesome/free-solid-svg-icons'
</script>
