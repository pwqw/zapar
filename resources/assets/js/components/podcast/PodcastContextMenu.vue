<template>
  <ul>
    <MenuItem @click="play">{{ $t('menu.podcast.playAll') }}</MenuItem>
    <MenuItem @click="shuffle">{{ $t('menu.podcast.shuffleAll') }}</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ podcast.favorite ? $t('menu.podcast.undoFavorite') : $t('menu.podcast.favorite') }}</MenuItem>
    <Separator />
    <MenuItem v-if="canChangeVisibility" @click="toggleVisibility">
      {{ podcast.is_public ? $t('menu.podcast.markAsPrivate') : $t('menu.podcast.markAsPublic') }}
    </MenuItem>
    <MenuItem @click="visitWebsite">{{ $t('menu.podcast.visitWebsite') }}</MenuItem>
    <Separator />
    <MenuItem v-if="canDelete" @click="deletePodcast">{{ $t('menu.podcast.delete') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { onMounted, ref, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'
import { playback } from '@/services/playbackManager'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { acl } from '@/services/acl'

const props = defineProps<{ podcast: Podcast }>()
const { podcast } = toRefs(props)

const { t } = useI18n()
const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const canDelete = ref(false)
const canChangeVisibility = ref(false)

onMounted(async () => {
  canDelete.value = await acl.checkResourcePermission('podcast', podcast.value.id, 'delete')
  canChangeVisibility.value = await acl.checkResourcePermission('podcast', podcast.value.id, 'publish')
})

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value), true)
  go(url('queue'))
})

const deletePodcast = async () => {
  if (await showConfirmDialog(t('menu.podcast.deleteConfirm'))) {
    await podcastStore.delete(podcast.value)
    toastSuccess(t('menu.podcast.deleted'))
    eventBus.emit('PODCAST_DELETED', podcast.value)
  }
}

const toggleVisibility = () => trigger(async () => {
  if (podcast.value.is_public) {
    await podcastStore.privatizePodcasts([podcast.value])
    toastSuccess(t('menu.podcast.markedAsPrivate'))
  } else {
    await podcastStore.publicizePodcasts([podcast.value])
    toastSuccess(t('menu.podcast.markedAsPublic'))
  }
})

const visitWebsite = () => trigger(() => window.open(podcast.value?.link))

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value))
</script>
