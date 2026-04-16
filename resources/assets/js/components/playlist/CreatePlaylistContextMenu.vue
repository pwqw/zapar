<template>
  <ul>
    <MenuItem @click="onItemClicked('new-playlist')">{{ $t('menu.newPlaylist') }}</MenuItem>
    <MenuItem @click="onItemClicked('new-smart-playlist')">{{ $t('menu.newSmartPlaylist') }}</MenuItem>
    <MenuItem @click="onItemClicked('new-folder')">{{ $t('menu.newFolder') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'

const CreatePlaylistForm = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistForm.vue'))
const CreateSmartPlaylistForm = defineAsyncComponent(
  () => import('@/components/playlist/smart-playlist/CreateSmartPlaylistForm.vue'),
)
const CreatePlaylistFolderForm = defineAsyncComponent(
  () => import('@/components/playlist/CreatePlaylistFolderForm.vue'),
)

const { MenuItem, trigger } = useContextMenu()
const { openModal } = useModal()

const onItemClicked = (action: 'new-playlist' | 'new-smart-playlist' | 'new-folder') =>
  trigger(() => {
    switch (action) {
      case 'new-playlist':
        return openModal<'CREATE_PLAYLIST_FORM'>(CreatePlaylistForm, { folder: null, playables: [] })
      case 'new-smart-playlist':
        return openModal<'CREATE_SMART_PLAYLIST_FORM'>(CreateSmartPlaylistForm, { folder: null })
      case 'new-folder':
        return openModal<'CREATE_PLAYLIST_FOLDER_FORM'>(CreatePlaylistFolderForm)
    }
  })
</script>
