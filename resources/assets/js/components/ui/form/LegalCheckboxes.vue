<template>
  <div class="space-y-3">
    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="termsAccepted" data-testid="terms-checkbox" required />
      <span class="text-sm">
        {{ t('legal.acceptTermsPrefix') }}
        <a
          v-if="termsUrl"
          :href="termsUrl"
          target="_blank"
          rel="noopener"
          class="text-k-highlight hover:underline"
        >
          {{ t('legal.termsDocumentTitle') }}
        </a>
        <template v-else>{{ t('legal.termsDocumentTitle') }}</template>
      </span>
    </label>

    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="privacyAccepted" data-testid="privacy-checkbox" required />
      <span class="text-sm">
        {{ t('legal.acceptPrivacyPrefix') }}
        <a
          v-if="privacyUrl"
          :href="privacyUrl"
          target="_blank"
          rel="noopener"
          class="text-k-highlight hover:underline"
        >
          {{ t('legal.privacyDocumentTitle') }}
        </a>
        <template v-else>{{ t('legal.privacyDocumentTitle') }}</template>
      </span>
    </label>

    <label class="flex items-start gap-2 cursor-pointer">
      <CheckBox v-model="ageVerified" data-testid="age-checkbox" required />
      <span class="text-sm">{{ t('legal.ageVerification') }}</span>
    </label>
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
}>()

const allAccepted = computed(() => termsAccepted.value && privacyAccepted.value && ageVerified.value)

defineExpose({
  allAccepted,
})
</script>
