<template>
  <fieldset>
    <h4 class="text-k-fg">
      <slot name="label" />
    </h4>

    <span class="w-16 h-16 my-4 aspect-square relative block rounded-md">
      <img :src="model || props.default" alt="" class="rounded object-cover">
      <button
        v-if="hasCustomValue"
        class="absolute top-2 right-2 w-9 active:scale-95 bg-black/50 hover:bg-black/70 aspect-square border border-k-fg-10 rounded"
        type="button"
        @click.prevent="removeCustomValue"
      >
        <Icon :icon="faTrashCan" />
        <span class="sr-only">{{ t('ui.buttons.remove') }}</span>
      </button>
    </span>

    <FormRow v-if="!hasCustomValue">
      <FileInput accept=".ico,image/x-icon,image/vnd.microsoft.icon,image/*" :name @change="onFileInputChange">
        {{ t('form.placeholders.selectFavicon') }}
      </FileInput>
    </FormRow>

    <p class="text-[.95rem]">
      <slot name="help" />
    </p>
  </fieldset>
</template>

<script setup lang="ts">
import { faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFileReader } from '@/composables/useFileReader'

import FileInput from '@/components/ui/form/FileInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const props = defineProps<{ default: string, name: string }>()

const { t } = useI18n()

const model = defineModel<string>()
let initialValue: typeof model.value

const hasCustomValue = computed(() => model.value && model.value !== props.default)

const removeCustomValue = () => {
  // First reset the model to the initial value (current settings), then to the default fallback.
  model.value = model.value === initialValue ? props.default : initialValue
}

const { readAsDataUrl } = useFileReader()

const onFileInputChange = (e: InputEvent) => {
  const target = e.target as HTMLInputElement

  if (!target.files || !target.files.length) {
    return
  }

  const file = target.files[0]
  
  // Check if it's an ICO file
  const isIco = file.name.toLowerCase().endsWith('.ico') || 
                file.type === 'image/x-icon' || 
                file.type === 'image/vnd.microsoft.icon'

  readAsDataUrl(file, dataUrl => {
    // If it's an ICO, ensure the data URI has the correct MIME type
    if (isIco && !dataUrl.includes('image/x-icon') && !dataUrl.includes('image/vnd.microsoft.icon')) {
      // Replace the MIME type in the data URL if needed
      dataUrl = dataUrl.replace(/^data:image\/[^;]+/, 'data:image/x-icon')
    }
    model.value = dataUrl
  })

  // reset the value so that, if the user removes the favicon, they can re-pick the same one
  target.value = ''
}

onMounted(() => (initialValue = model.value))
</script>
