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
            class="w-full min-h-40 rounded border border-k-fg-10 bg-k-bg-input px-3 py-2 font-mono text-sm text-k-fg-input placeholder:text-k-fg-50"
            name="message"
            :placeholder="t('settings.welcomeMessagePlaceholder')"
          />
          <template #help>
            {{ t('settings.welcomeMessageHelpPrefix') }}
            <code class="mx-1 rounded bg-k-bg-20 px-2 py-1 text-xs text-k-fg-80">{variableName}</code>
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
              class="rounded border border-k-fg-10 bg-k-bg-10 p-3"
            >
              <div class="mb-3 flex items-center justify-between">
                <label class="text-sm font-semibold text-k-fg-80">
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
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-2 py-1 text-sm text-k-fg-input placeholder:text-k-fg-50"
                >
                <input
                  v-model="variable.url"
                  type="text"
                  :placeholder="t('settings.variableUrl')"
                  class="w-full rounded border border-k-fg-10 bg-k-bg-input px-2 py-1 text-sm text-k-fg-input placeholder:text-k-fg-50"
                >
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="rounded bg-k-bg-20 p-4 text-center text-sm text-k-fg-70">
            {{ t('settings.noVariablesAdded') }}
          </div>

          <!-- Template Variables Reference -->
          <div v-if="data.variables && data.variables.length > 0" class="mt-4 rounded border border-k-fg-10 bg-k-bg-20 p-3">
            <p class="mb-3 text-sm font-semibold text-k-fg">
              {{ t('settings.templateVariablesReference') }}
            </p>
            <div class="space-y-2">
              <div v-for="variable in data.variables" :key="variable.name" class="space-y-1">
                <div class="text-xs font-semibold text-k-fg-80">
                  {{ variable.name || t('settings.variableName') }}
                </div>
                <div class="flex items-center gap-2">
                  <code class="rounded border border-k-fg-10 bg-k-bg-input px-2 py-1 font-mono text-xs text-k-fg-input" v-text="formatVariable(variable.name)" />
                  <span class="break-all font-mono text-xs text-k-fg-70">{{ variable.url }}</span>
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
