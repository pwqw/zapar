<template>
  <div
    v-if="isAnonymous"
    class="bg-yellow-100 dark:bg-yellow-900 px-4 py-3 text-center text-sm flex items-center justify-center gap-2 border-b border-yellow-300 dark:border-yellow-800"
  >
    <Icon :icon="faUserSecret" class="text-yellow-700 dark:text-yellow-300 flex-shrink-0" />
    <span class="text-yellow-900 dark:text-yellow-100">{{ t('auth.anonymousModeActive') }}</span>
    <button
      type="button"
      class="ml-auto underline font-semibold text-yellow-700 dark:text-yellow-300 hover:text-yellow-800 dark:hover:text-yellow-200 transition"
      @click="handleCreateAccount"
    >
      {{ t('auth.createAccount') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { faUserSecret } from '@fortawesome/free-solid-svg-icons'
import { authService } from '@/services/authService'

const { t } = useI18n()

const isAnonymous = computed(() => authService.isAnonymous())

const handleCreateAccount = async () => {
  await authService.logout()
  window.location.href = '/'
}
</script>
