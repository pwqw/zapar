<template>
  <ul>
    <MenuItem @click="play">{{ t('ui.buttons.play') }}</MenuItem>
    <MenuItem @click="shuffle">{{ t('albums.shuffle') }}</MenuItem>
    <MenuItem @click="addToQueue">{{ t('playlists.addToQueue') }}</MenuItem>
    <MenuItem>
      {{ t('playlists.share') }}
      <template #subMenuItems>
        <MenuItem @click="showEmbedModal">{{ t('playlists.embed') }}</MenuItem>
        <MenuItem v-if="canShowCollaboration" @click="showCollaborationModal">{{ t('playlists.collaborate') }}</MenuItem>
      </template>
    </MenuItem>
    <template v-if="allowDownload">
      <Separator />
      <MenuItem @click="download">{{ t('playlists.download') }}</MenuItem>
    </template>
    <template v-if="canToggleOffline">
      <Separator />
      <MenuItem @click="toggleOffline">{{
        allCached ? t('offline.removeOfflineVersions') : t('offline.makeAvailableOffline')
      }}</MenuItem>
    </template>
    <template v-if="canEditPlaylist">
      <Separator />
      <MenuItem @click="edit">{{ t('playlists.edit') }}</MenuItem>
      <MenuItem @click="destroy">{{ t('playlists.delete') }}</MenuItem>
    </template>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import { defineAsyncComponent } from '@/utils/helpers'
import { pluralize } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { usePolicies } from '@/composables/usePolicies'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { commonStore } from '@/stores/commonStore'
import { useDownload } from '@/composables/useDownload'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { t } = useI18n()

const EditPlaylistForm = defineAsyncComponent(() => import('@/components/playlist/EditPlaylistForm.vue'))
const EditSmartPlaylistForm = defineAsyncComponent(
  () => import('@/components/playlist/smart-playlist/EditSmartPlaylistForm.vue'),
)
const PlaylistCollaborationModal = defineAsyncComponent(
  () => import('@/components/playlist/PlaylistCollaborationModal.vue'),
)
const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()
const { showConfirmDialog } = useDialogBox()

const allowDownload = toRef(commonStore.state, 'allows_download')

const canEditPlaylist = computed(() => currentUserCan.editPlaylist(playlist.value))
const canShowCollaboration = computed(() => isPlus.value && !playlist.value?.is_smart)

const edit = () =>
  trigger(() => {
    const p = playlist.value
    p.is_smart
      ? openModal<'EDIT_SMART_PLAYLIST_FORM'>(EditSmartPlaylistForm, { playlist: p })
      : openModal<'EDIT_PLAYLIST_FORM'>(EditPlaylistForm, { playlist: p })
  })

const destroy = () =>
  trigger(async () => {
    if (await showConfirmDialog(t('playlists.deleteConfirm', { name: playlist.value.name }))) {
      await playlistStore.delete(playlist.value)
      toastSuccess(t('playlists.deleted', { name: playlist.value.name }))
      eventBus.emit('PLAYLIST_DELETED', playlist.value)
    }
  })

const { fromPlaylist } = useDownload()
const download = () => trigger(() => fromPlaylist(playlist.value))

const play = () =>
  trigger(async () => {
    const songs = await playableStore.fetchForPlaylist(playlist.value)

    if (songs.length) {
      playback().queueAndPlay(songs)
      go(url('queue'))
    } else {
      toastWarning(t('playlists.empty'))
    }
  })

const shuffle = () =>
  trigger(async () => {
    const songs = await playableStore.fetchForPlaylist(playlist.value)

    if (songs.length) {
      playback().queueAndPlay(songs, true)
      go(url('queue'))
    } else {
      toastWarning(t('playlists.empty'))
    }
  })

const addToQueue = () =>
  trigger(async () => {
    const songs = await playableStore.fetchForPlaylist(playlist.value)

    if (songs.length) {
      queueStore.queueAfterCurrent(songs)
      toastSuccess(t('playlists.addedToQueue'))
    } else {
      toastWarning(t('playlists.empty'))
    }
  })

const showCollaborationModal = () =>
  trigger(() => openModal<'PLAYLIST_COLLABORATION'>(PlaylistCollaborationModal, { playlist: playlist.value }))
const showEmbedModal = () =>
  trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: playlist.value }))

const { swReady, makePlayablesAvailableOffline, removePlayablesOfflineCache, allPlayablesCached } = useOfflinePlayback()
const canToggleOffline = computed(() => swReady.value)
const playlistSongs = ref<Playable[]>([])
const allCached = computed(() => allPlayablesCached(playlistSongs.value))

const toggleOffline = () =>
  trigger(async () => {
    if (!playlistSongs.value.length) {
      return
    }

    if (allCached.value) {
      removePlayablesOfflineCache(playlistSongs.value)
      toastSuccess(t('playlists.removedOfflineVersionsFor', { name: playlist.value.name }))
    } else {
      makePlayablesAvailableOffline(playlistSongs.value)
      toastSuccess(
        t('playlists.makingSongsAvailableOffline', { items: pluralize(playlistSongs.value, 'song') }),
      )
    }
  })

onMounted(async () => {
  playlistSongs.value = await playableStore.fetchForPlaylist(playlist.value)
})
</script>
