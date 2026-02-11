<template>
  <AlbumArtistInfo :mode="mode" data-testid="album-info">
    <template #header>{{ albumStore.isUnknown(album) ? t('screens.unknownAlbum') : album.name }}</template>

    <template #art>
      <AlbumThumbnail :entity="album" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <div v-if="!loading && useEncyclopedia && canFetchEncyclopedia && !info" class="flex flex-wrap gap-2">
      <Btn data-testid="album-info-load" @click="loadInfo">{{ t(loadInfoLabelKey) }}</Btn>
    </div>

    <div v-if="!loading && useEncyclopedia && canFetchEncyclopedia && info" class="mt-2">
      <Btn gray data-testid="album-info-clear" @click="clearInfo">{{ t(clearDataLabelKey) }}</Btn>
    </div>

    <template v-if="!loading && info?.wiki">
      <div v-html="info.wiki.full" />

      <TrackList
        v-if="info.tracks?.length"
        :album="album"
        :tracks="info.tracks"
        class="mt-8"
        data-testid="album-info-tracks"
      />
    </template>

    <template v-if="!loading && info?.url" #footer>
      <a :href="info.url" rel="noopener" target="_blank">{{ t('albums.source') }}</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { albumStore } from '@/stores/albumStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { defineAsyncComponent } from '@/utils/helpers'

import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{
  album: Album
  mode?: EncyclopediaDisplayMode
  canFetchEncyclopedia?: boolean
}>(), { mode: 'aside', canFetchEncyclopedia: false })

const TrackList = defineAsyncComponent(() => import('@/components/album/AlbumTrackList.vue'))

const { t } = useI18n()
const { album, mode, canFetchEncyclopedia } = toRefs(props)

const { useMusicBrainz, useLastfm, useSpotify } = useThirdPartyServices()

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value || useSpotify.value)

/** Backend uses Last.fm first, then MusicBrainz (AppServiceProvider). */
const loadInfoLabelKey = computed(() =>
  useLastfm.value ? 'screens.loadInformationFromLastfm' : 'screens.loadInformationFromMusicBrainz')
const clearDataLabelKey = computed(() =>
  useLastfm.value ? 'screens.clearEncyclopediaDataLastfm' : 'screens.clearEncyclopediaDataMusicBrainz')

const loading = ref(false)
const info = ref<AlbumInfo | null>(null)

watch(album, () => {
  info.value = null
}, { deep: true })

async function loadInfo () {
  if (!useEncyclopedia.value) return
  loading.value = true
  info.value = await encyclopediaService.fetchForAlbum(album.value)
  loading.value = false
}

async function clearInfo () {
  await encyclopediaService.clearAlbumEncyclopediaData(album.value)
  info.value = null
}
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[3];
}
</style>
