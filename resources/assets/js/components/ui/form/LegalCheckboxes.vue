<template>
  <div class="space-y-3">
    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="termsAccepted" data-testid="terms-checkbox" required />
      <span class="text-sm">
        {{ t('legal.acceptTerms') }}
        <a
          v-if="termsUrl"
          :href="termsUrl"
          target="_blank"
          rel="noopener"
          class="text-k-highlight hover:underline"
        >
          {{ t('legal.viewDocument') }}
        </a>
      </span>
    </label>

    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="privacyAccepted" data-testid="privacy-checkbox" required />
      <span class="text-sm">
        {{ t('legal.acceptPrivacy') }}
        <a
          v-if="privacyUrl"
          :href="privacyUrl"
          target="_blank"
          rel="noopener"
          class="text-k-highlight hover:underline"
        >
          {{ t('legal.viewDocument') }}
        </a>
      </span>
    </label>

    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="ageVerified" data-testid="age-checkbox" required />
      <span class="text-sm">{{ t('legal.ageVerification') }}</span>
    </label>

    <p v-if="showError" class="text-red-500 text-xs mt-2">
      {{ t('legal.mustAcceptAll') }}
    </p>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CheckBox from '@/components/ui/form/CheckBox.vue'

const { t } = useI18n()

const termsAccepted = defineModel<boolean>('termsAccepted', { default: false })
const privacyAccepted = defineModel<boolean>('privacyAccepted', { default: false })
const ageVerified = defineModel<boolean>('ageVerified', { default: false })

defineProps<{
  termsUrl?: string
  privacyUrl?: string
  showError?: boolean
}>()

const allAccepted = computed(() => termsAccepted.value && privacyAccepted.value && ageVerified.value)

defineExpose({
  allAccepted,
})
</script>
