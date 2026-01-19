<template>
  <button
    class="relative before:absolute before:w-[28px] before:aspect-square before:top-[-6px] before:left-[-6px] before:cursor-pointer"
    :title="t('ui.tooltips.createNewPlaylistOrFolder')"
    type="button"
    @click.stop.prevent="requestContextMenu"
  >
    <Icon :icon="faCirclePlus" />
  </button>
</template>

<script lang="ts" setup>
import { faCirclePlus } from '@fortawesome/free-solid-svg-icons'
import { useI18n } from 'vue-i18n'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

const { t } = useI18n()

const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistContextMenu.vue'))

const { openContextMenu } = useContextMenu()

const requestContextMenu = (e: MouseEvent) => {
  const { bottom, right } = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()

  openContextMenu(ContextMenu, {
    top: bottom,
    left: right,
  })
}
</script>
