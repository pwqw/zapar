<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>{{ t('settings.googleDocPages') }}</template>

      <div class="space-y-6">
        <!-- Add Page Button -->
        <div class="flex items-center justify-between">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ t('settings.googleDocPagesHelp') }}
          </p>
          <Btn
            type="button"
            class="px-3 py-1 text-xs"
            @click="addPage"
          >
            {{ t('settings.addPage') }}
          </Btn>
        </div>

        <!-- Pages List -->
        <div v-if="data.pages && data.pages.length > 0" class="space-y-4">
          <div
            v-for="(page, index) in data.pages"
            :key="index"
            class="rounded border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
          >
            <div class="mb-3 flex items-center justify-between">
              <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ page.title || t('settings.pageName') }}
              </label>
              <button
                type="button"
                class="rounded bg-red-500 p-2 text-white hover:bg-red-600"
                :aria-label="t('settings.removePage')"
                @click="removePage(index)"
              >
                <TrashIcon :size="16" />
              </button>
            </div>
            <div class="space-y-3">
              <FormRow>
                <template #label>{{ t('settings.pageTitle') }}</template>
                <input
                  v-model="page.title"
                  type="text"
                  :placeholder="t('settings.pageTitlePlaceholder')"
                  class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                  required
                >
              </FormRow>

              <FormRow>
                <template #label>{{ t('settings.pageSlug') }}</template>
                <input
                  v-model="page.slug"
                  type="text"
                  :placeholder="t('settings.pageSlugPlaceholder')"
                  pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                  class="w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-sm dark:border-gray-600 dark:bg-gray-800"
                  required
                >
                <template #help>
                  {{ t('settings.pageSlugHelp') }}
                </template>
              </FormRow>

              <FormRow>
                <template #label>{{ t('settings.embedUrl') }}</template>
                <input
                  v-model="page.embed_url"
                  type="url"
                  :placeholder="t('settings.embedUrlPlaceholder')"
                  class="w-full rounded border border-gray-300 bg-white px-3 py-2 font-mono text-sm dark:border-gray-600 dark:bg-gray-800"
                  required
                >
                <template #help>
                  {{ t('settings.embedUrlHelp') }}
                </template>
              </FormRow>

              <FormRow>
                <template #label>{{ t('settings.defaultBackUrl') }}</template>
                <input
                  v-model="page.default_back_url"
                  type="text"
                  :placeholder="t('settings.defaultBackUrlPlaceholder')"
                  class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                >
                <template #help>
                  {{ t('settings.defaultBackUrlHelp') }}
                </template>
              </FormRow>

              <!-- Preview URL -->
              <div class="rounded bg-blue-50 p-3 dark:bg-blue-900/20">
                <p class="mb-1 text-xs font-semibold text-blue-900 dark:text-blue-200">
                  {{ t('settings.pageUrl') }}
                </p>
                <code class="block rounded bg-blue-100 px-2 py-1 font-mono text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                  /#/document/{{ page.slug || 'slug' }}
                </code>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="rounded bg-gray-100 p-6 text-center dark:bg-gray-900">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ t('settings.noPagesAdded') }}
          </p>
        </div>
      </div>

      <template #footer>
        <Btn type="submit" :disabled="loading">{{ t('auth.save') }}</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { TrashIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { type GoogleDocPage, settingStore } from '@/stores/settingStore'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'

interface GoogleDocPagesData {
  pages: GoogleDocPage[]
}

const { t } = useI18n()
const { toastSuccess, toastError } = useMessageToaster()

const { data, loading, handleSubmit } = useForm<GoogleDocPagesData>({
  initialValues: {
    pages: [],
  },
  onSubmit: async formData => {
    try {
      await settingStore.updateGoogleDocPages(formData.pages)
      toastSuccess(t('settings.googleDocPagesSaved'))
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || t('settings.errorSaving')
      toastError(errorMessage)
    }
  },
})

const addPage = (): void => {
  if (!data.pages) {
    data.pages = []
  }
  data.pages.push({
    title: '',
    slug: '',
    embed_url: '',
    default_back_url: '',
  })
}

const removePage = (index: number): void => {
  if (data.pages) {
    data.pages.splice(index, 1)
  }
}

onMounted(async () => {
  try {
    const pages = await settingStore.getGoogleDocPages()
    data.pages = pages.length > 0 ? pages : []
  } catch (error) {
    console.error('Error loading Google Doc pages:', error)
  }
})
</script>
