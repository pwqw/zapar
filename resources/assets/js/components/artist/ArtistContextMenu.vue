<template>
  <ul>
    <MenuItem @click="play">{{ $t('menu.artist.playAll') }}</MenuItem>
    <MenuItem @click="shuffle">{{ $t('menu.artist.shuffleAll') }}</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ artist.favorite ? $t('menu.artist.undoFavorite') : $t('menu.artist.favorite') }}</MenuItem>
    <MenuItem v-if="allowEdit" @click="requestEditForm">{{ $t('playlists.edit') }}</MenuItem>
    <template v-if="isStandardArtist && allowDownload">
      <Separator />
      <MenuItem @click="download">{{ $t('menu.artist.download') }}</MenuItem>
    </template>
    <Separator />
    <MenuItem @click="showEmbedModal">{{ $t('menu.artist.embed') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRefs } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'
import { defineAsyncComponent } from '@/utils/helpers'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const EditArtistForm = defineAsyncComponent(() => import('@/components/artist/EditArtistForm.vue'))

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { currentUserCan, allowDownload } = usePolicies()

const allowEdit = ref(false)

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value)
  && !artistStore.isVarious(artist.value),
)

const play = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value))
})

const shuffle = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value), true)
})

const download = () => trigger(() => downloadService.fromArtist(artist.value))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value))
const requestEditForm = () => trigger(() => openModal<'EDIT_ARTIST_FORM'>(EditArtistForm, { artist: artist.value }))
const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', artist.value))

onMounted(async () => {
  allowEdit.value = await currentUserCan.editArtist(artist.value)
})
</script>
