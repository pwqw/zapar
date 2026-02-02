<template>
  <FormRow>
    <template #label>{{ t('embeds.theme') }}</template>
    <SelectBox v-model="model">
      <option v-for="theme in themesWithId" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
    </SelectBox>
  </FormRow>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { themeStore } from '@/stores/themeStore'

import SelectBox from '@/components/ui/form/SelectBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { t } = useI18n()

const model = defineModel<Theme>()

const themes = ref<Theme[]>([])

const themesWithId = computed(() => (themes.value ?? []).filter((theme): theme is Theme => theme != null && theme.id != null))

onMounted(async () => {
  await themeStore.fetchCustomThemes()
  themes.value = themeStore.all
})
</script>
