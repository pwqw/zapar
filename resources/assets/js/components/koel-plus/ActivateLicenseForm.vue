<template>
  <div class="flex flex-col gap-2">
    <input v-model="licenseKey" type="text" role="textbox" class="input" />
    <Btn success @click.prevent="submit">{{ t('koelPlus.activate') }}</Btn>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Btn from '@/components/ui/form/Btn.vue'

const { t } = useI18n()
import { plusService } from '@/services/plusService'

const licenseKey = ref('')

const submit = async (): Promise<void> => {
  await plusService.activateLicense(licenseKey.value)
}
</script>
