<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>{{ t('settings.consentLegalUrls') }}</template>

      <template #subtitle>{{ t('settings.consentLegalUrlsHelp') }}</template>

      <div class="space-y-4">
        <FormRow>
          <template #label>{{ t('settings.termsUrl') }}</template>
          <input
            v-model="data.terms_url"
            type="text"
            :placeholder="t('settings.termsUrlPlaceholder')"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
          >
        </FormRow>

        <FormRow>
          <template #label>{{ t('settings.privacyUrl') }}</template>
          <input
            v-model="data.privacy_url"
            type="text"
            :placeholder="t('settings.privacyUrlPlaceholder')"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
          >
        </FormRow>
      </div>

      <template #footer>
        <Btn type="submit" :disabled="loading">{{ t('auth.save') }}</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { settingStore } from '@/stores/settingStore'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'

interface ConsentLegalUrlsData {
  terms_url: string
  privacy_url: string
}

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()

const { data, loading, handleSubmit } = useForm<ConsentLegalUrlsData>({
  initialValues: {
    terms_url: '',
    privacy_url: '',
  },
  onSubmit: async formData => {
    await settingStore.updateConsentLegalUrls(
      formData.terms_url?.trim() || null,
      formData.privacy_url?.trim() || null,
    )
    toastSuccess(t('settings.consentLegalUrlsSaved'))
  },
})

onMounted(() => {
  data.terms_url = settingStore.state.terms_url ?? ''
  data.privacy_url = settingStore.state.privacy_url ?? ''
})
</script>
