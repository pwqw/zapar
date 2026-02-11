<template>
  <AlbumArtistInfo :mode="mode" data-testid="artist-info">
    <template #header>{{ artist.name }}</template>

    <template #art>
      <ArtistThumbnail :entity="artist" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <div v-if="!loading && useEncyclopedia && canFetchEncyclopedia && !info" class="flex flex-wrap gap-2">
      <Btn data-testid="artist-info-load" @click="loadInfo">{{ t(loadInfoLabelKey) }}</Btn>
    </div>

    <div v-if="!loading && info?.bio" v-html="info.bio.full" />

    <div v-if="!loading && useEncyclopedia && canFetchEncyclopedia && info" class="mt-2">
      <Btn gray data-testid="artist-info-clear" @click="clearInfo">{{ t(clearDataLabelKey) }}</Btn>
    </div>

    <template v-if="!loading && info?.url" #footer>
      <a :href="info.url" rel="noopener" target="_blank">{{ t('albums.source') }}</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { encyclopediaService } from '@/services/encyclopediaService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{
  artist: Artist
  mode?: EncyclopediaDisplayMode
  canFetchEncyclopedia?: boolean
}>(), { mode: 'aside', canFetchEncyclopedia: false })
const { artist, mode, canFetchEncyclopedia } = toRefs(props)

const { t } = useI18n()
const { useMusicBrainz, useLastfm, useSpotify } = useThirdPartyServices()

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value || useSpotify.value)

/** Backend uses Last.fm first, then MusicBrainz (AppServiceProvider). */
const loadInfoLabelKey = computed(() =>
  useLastfm.value ? 'screens.loadInformationFromLastfm' : 'screens.loadInformationFromMusicBrainz')
const clearDataLabelKey = computed(() =>
  useLastfm.value ? 'screens.clearEncyclopediaDataLastfm' : 'screens.clearEncyclopediaDataMusicBrainz')

const loading = ref(false)
const info = ref<ArtistInfo | null>(null)

watch(artist, () => {
  info.value = null
})

async function loadInfo () {
  if (!useEncyclopedia.value) return
  loading.value = true
  info.value = await encyclopediaService.fetchForArtist(artist.value)
  loading.value = false
}

async function clearInfo () {
  await encyclopediaService.clearArtistEncyclopediaData(artist.value)
  info.value = null
}
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[3];
}
</style>
