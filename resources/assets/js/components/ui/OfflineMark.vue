<template>
  <span class="!opacity-50" :title="$t('nav.availableOffline')">
    <Icon :icon="faCloudArrowDown" />
  </span>
</template>

<script lang="ts" setup>
import { faCloudArrowDown } from '@fortawesome/free-solid-svg-icons'
</script>
