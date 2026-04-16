<template>
  <ul>
    <MenuItem @click="applyTheme">{{ $t('menu.theme.apply') }}</MenuItem>

    <template v-if="theme.is_custom">
      <Separator />
      <MenuItem @click="destroy">{{ $t('menu.theme.delete') }}</MenuItem>
    </template>
  </ul>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { themeStore } from '@/stores/themeStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useDialogBox } from '@/composables/useDialogBox'
import { useContextMenu } from '@/composables/useContextMenu'

const props = defineProps<{ theme: Theme }>()
const { theme } = toRefs(props)

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler()
const { showConfirmDialog } = useDialogBox()
const { Separator, MenuItem, trigger } = useContextMenu()

const applyTheme = () => trigger(() => themeStore.setTheme(theme.value))

const destroy = () =>
  trigger(async () => {
    if (!(await showConfirmDialog(t('menu.theme.deleteConfirm')))) {
      return
    }

    try {
      await themeStore.destroy(theme.value)
      toastSuccess(t('menu.theme.deleted'))
    } catch (e: unknown) {
      handleHttpError(e)
    }
  })
</script>
