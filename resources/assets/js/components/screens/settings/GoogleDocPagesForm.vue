<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>{{ t('settings.googleDocPages') }}</template>

      <div class="space-y-6">
        <!-- Add Page Button -->
        <div class="flex items-center justify-between">
          <p class="text-sm text-k-fg-70">
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
            class="rounded border border-k-fg-10 bg-k-bg-10 p-4"
          >
            <div class="mb-3 flex items-center justify-between">
              <label class="text-sm font-semibold text-k-fg-80">
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
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-3 py-2 text-sm text-k-fg-input placeholder:text-k-fg-50"
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
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-3 py-2 font-mono text-sm text-k-fg-input placeholder:text-k-fg-50"
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
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-3 py-2 font-mono text-sm text-k-fg-input placeholder:text-k-fg-50"
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
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-3 py-2 text-sm text-k-fg-input placeholder:text-k-fg-50"
                >
                <template #help>
                  {{ t('settings.defaultBackUrlHelp') }}
                </template>
              </FormRow>

              <!-- Preview URL -->
              <div class="rounded border border-k-fg-10 bg-k-bg-20 p-3">
                <p class="mb-1 text-xs font-semibold text-k-fg-80">
                  {{ t('settings.pageUrl') }}
                </p>
                <code class="block rounded border border-k-fg-10 bg-k-bg-input px-2 py-1 font-mono text-xs text-k-fg-input">
                  /#/document/{{ page.slug || 'slug' }}
                </code>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="rounded bg-k-bg-20 p-6 text-center">
          <p class="text-sm text-k-fg-70">
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
