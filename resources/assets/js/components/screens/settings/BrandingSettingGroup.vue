<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>{{ t('settings.branding') }}</template>

      <div class="space-y-4">
        <FormRow>
          <template #label>{{ t('settings.appName') }}</template>
          <TextInput v-model="data.name" class="md:w-2/3" name="name" :placeholder="t('settings.appNameDefault')" />
        </FormRow>
        <BrandingImageField v-model="data.logo" :default="koelBirdLogo" name="logo">
          <template #label>{{ t('settings.appLogo') }}</template>
          <template #help>{{ t('settings.appLogoDescription') }}</template>
        </BrandingImageField>
        <BrandingImageField v-model="data.cover" :default="koelBirdCover" name="cover">
          <template #label>{{ t('settings.appCover') }}</template>
          <template #help>{{ t('settings.appCoverDescription') }}</template>
        </BrandingImageField>
        <FaviconField v-model="faviconValue" :default="defaultFavicon" name="favicon">
          <template #label>{{ t('settings.appFavicon') }}</template>
          <template #help>{{ t('settings.appFaviconDescription') }}</template>
        </FaviconField>

        <FormRow>
          <template #label>{{ t('settings.appDescription') }}</template>
          <textarea
            v-model="data.description"
            class="w-full min-h-24 rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 md:w-2/3"
            name="description"
            :placeholder="t('settings.appDescriptionPlaceholder')"
          />
          <template #help>{{ t('settings.appDescriptionHelp') }}</template>
        </FormRow>

        <BrandingImageField v-model="ogImageValue" :default="defaultImage" name="og_image">
          <template #label>{{ t('settings.shareImage') }}</template>
          <template #help>{{ t('settings.shareImageHelp') }}</template>
        </BrandingImageField>
      </div>
      <template #footer>
        <Btn type="submit" :disabled="loading">{{ t('auth.save') }}</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from '@/composables/useForm'
import { useBranding } from '@/composables/useBranding'
import { settingStore } from '@/stores/settingStore'
import { forceReloadWindow } from '@/utils/helpers'
import { useDialogBox } from '@/composables/useDialogBox'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BrandingImageField from '@/components/screens/settings/BrandingImageField.vue'
import FaviconField from '@/components/screens/settings/FaviconField.vue'

const props = defineProps<{ currentBranding: Branding }>()

const { t } = useI18n()

const { showConfirmDialog } = useDialogBox()
const {
  koelBirdCover,
  koelBirdLogo,
  isKoelBirdCover,
  isKoelBirdLogo,
  currentBranding,
} = useBranding()

const defaultFavicon = '/img/favicon.ico'
const defaultImage = currentBranding.logo || ''

const opengraph = settingStore.state.opengraph || {}
const faviconValue = ref<string | undefined>((props.currentBranding as any).favicon || undefined)
const ogImageValue = ref<string | undefined>(opengraph.image || undefined)

const getInitialValues = () => {
  return {
    ...props.currentBranding,
    description: opengraph.description ?? undefined,
  }
}

const { data, loading, handleSubmit } = useForm({
  initialValues: getInitialValues(),
  onSubmit: async (formData: any) => {
    const submittedData: Partial<Branding> = { ...formData }

    if (formData.logo && isKoelBirdLogo(formData.logo)) {
      delete submittedData.logo
    }

    if (formData.cover && isKoelBirdCover(formData.cover)) {
      delete submittedData.cover
    }

    // Handle favicon: if it's the default, send empty string to remove it
    if (submittedData.favicon === defaultFavicon) {
      submittedData.favicon = ''
    }

    // Include OpenGraph fields
    const brandingData: any = {
      ...submittedData,
      description: formData.description ?? null,
      og_image: ogImageValue.value ?? null,
    }

    await settingStore.updateBranding(brandingData)

    if (await showConfirmDialog(t('settings.reloadToApply'))) {
      forceReloadWindow()
    }
  },
})

watch(faviconValue, newValue => {
  (data as any).favicon = newValue || null
})
</script>
