<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>{{ t('settings.welcomeMessage') }}</template>

      <div class="space-y-6">
        <!-- Welcome Message Textarea -->
        <FormRow>
          <template #label>{{ t('settings.welcomeMessageText') }}</template>
          <textarea
            v-model="data.message"
            class="w-full min-h-40 rounded border border-gray-300 bg-white px-3 py-2 font-mono text-sm dark:border-gray-600 dark:bg-gray-800"
            name="message"
            :placeholder="t('settings.welcomeMessagePlaceholder')"
          />
          <template #help>
            {{ t('settings.welcomeMessageHelpPrefix') }}
            <code class="mx-1 rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{variableName}</code>
            {{ t('settings.welcomeMessageHelpSuffix') }}
          </template>
        </FormRow>

        <!-- Dynamic URL Variables Section -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="text-sm font-semibold">{{ t('settings.welcomeMessageVariables') }}</label>
            <Btn
              type="button"
              class="px-3 py-1 text-xs"
              @click="addVariable"
            >
              {{ t('settings.addVariable') }}
            </Btn>
          </div>

          <!-- Variables List -->
          <div v-if="data.variables && data.variables.length > 0" class="space-y-3">
            <div
              v-for="(variable, index) in data.variables"
              :key="index"
              class="rounded border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900"
            >
              <div class="mb-3 flex items-center justify-between">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                  {{ variable.name || t('settings.variableName') }}
                </label>
                <button
                  type="button"
                  class="rounded bg-red-500 p-2 text-white hover:bg-red-600"
                  :aria-label="t('settings.removeVariable')"
                  @click="removeVariable(index)"
                >
                  <TrashIcon :size="16" />
                </button>
              </div>
              <div class="space-y-2">
                <input
                  v-model="variable.name"
                  type="text"
                  :placeholder="t('settings.variableName')"
                  class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                >
                <input
                  v-model="variable.url"
                  type="text"
                  :placeholder="t('settings.variableUrl')"
                  class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                >
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="rounded bg-gray-100 p-4 text-center text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            {{ t('settings.noVariablesAdded') }}
          </div>

          <!-- Template Variables Reference -->
          <div v-if="data.variables && data.variables.length > 0" class="mt-4 rounded bg-blue-50 p-3 dark:bg-blue-900/20">
            <p class="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-200">
              {{ t('settings.templateVariablesReference') }}
            </p>
            <div class="space-y-2">
              <div v-for="variable in data.variables" :key="variable.name" class="space-y-1">
                <div class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                  {{ variable.name || t('settings.variableName') }}
                </div>
                <div class="flex items-center gap-2">
                  <code class="rounded bg-blue-100 px-2 py-1 font-mono text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-300" v-text="formatVariable(variable.name)" />
                  <span class="break-all font-mono text-xs text-blue-800 dark:text-blue-300">{{ variable.url }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Btn type="submit" :disabled="loading">{{ t('auth.save') }}</Btn>
      </template>
    </SettingGroup>
  </form>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { TrashIcon } from 'lucide-vue-next'
import { useForm } from '@/composables/useForm'
import { settingStore } from '@/stores/settingStore'
import { useMessageToaster } from '@/composables/useMessageToaster'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'

interface TemplateVariable {
  name: string
  url: string
}

interface WelcomeMessageData {
  message: string
  variables: TemplateVariable[]
}

const { t } = useI18n()
const { toastSuccess, toastError } = useMessageToaster()

// Load initial values from window.WELCOME_MESSAGE if available
const getInitialValues = (): WelcomeMessageData => {
  const welcomeMessage = (window.WELCOME_MESSAGE as any) || null

  if (welcomeMessage && welcomeMessage.message) {
    return {
      message: welcomeMessage.message,
      variables: welcomeMessage.variables || [],
    }
  }

  return {
    message: '',
    variables: [],
  }
}

const { data, loading, handleSubmit } = useForm<WelcomeMessageData>({
  initialValues: getInitialValues(),
  onSubmit: async formData => {
    try {
      await settingStore.updateWelcomeMessage(formData.message, formData.variables)
      toastSuccess(t('settings.welcomeMessageUpdated'))
    } catch {
      toastError(t('settings.saved'))
    }
  },
})

const addVariable = (): void => {
  if (!data.variables) {
    data.variables = []
  }
  data.variables.push({ name: '', url: '' })
}

const removeVariable = (index: number): void => {
  if (data.variables) {
    data.variables.splice(index, 1)
  }
}

const formatVariable = (name: string): string => {
  return `{${name}}`
}
</script>
