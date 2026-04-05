<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        {{ $t('screens.favorites') }}

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'item') }}</span>
          <span>{{ duration }}</span>

          <a
            v-if="downloadable"
            class="download"
            role="button"
            :title="$t('ui.tooltips.downloadAllFavorites')"
            @click.prevent="download"
          >
            {{ $t('ui.buttons.downloadAll') }}
          </a>
          <a v-if="canToggleOffline" role="button" @click.prevent="toggleOffline">
            {{ allCached ? $t('offline.removeOffline') : $t('offline.makeOffline') }}
          </a>
        </template>

        <template #controls>
          <PlayableListControls
            v-if="playables.length"
            :config
            @filter="applyFilter"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <PlayableListSkeleton v-if="loading" class="-m-6" />
    <PlayableList
      v-if="playables.length"
      ref="playableList"
      class="-m-6"
      @reorder="onReorder"
      @sort="sort"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @swipe="onSwipe"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faHeartBroken" />
      </template>
      {{ $t('misc.noFavoritesYet') }}
      <span class="secondary block">
        {{ $t('screens.favoritesEmptyHint') }}
      </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faStar } from '@fortawesome/free-regular-svg-icons'
import { computed, ref } from 'vue'
import { pluralize } from '@/utils/formatters'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useLocalStorage } from '@/composables/useLocalStorage'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const allPlayables = ref<Playable[]>([])

const {
  PlayableList,
  ThumbnailStack,
  headerLayout,
  playables,
  playableList,
  duration,
  downloadable,
  thumbnails,
  selectedPlayables,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onSwipe,
  sort: baseSort,
  config: listConfig,
} = usePlayableList(allPlayables, { type: 'Favorites' })

listConfig.reorderable = true
listConfig.hasCustomOrderSort = true

const { PlayableListControls, config } = usePlayableListControls('Favorites')
const { get: lsGet, set: lsSet } = useLocalStorage()

const { fromFavorites } = useDownload()
const download = () => fromFavorites()
const removeSelected = () => selectedPlayables.value.length && playableStore.undoFavorite(selectedPlayables.value)

const { swReady, makePlayablesAvailableOffline, removePlayablesOfflineCache, allPlayablesCached } = useOfflinePlayback()
const canToggleOffline = computed(() => swReady.value && playables.value.length > 0)
const allCached = computed(() => allPlayablesCached(playables.value))

const toggleOffline = () => {
  if (allCached.value) {
    removePlayablesOfflineCache(playables.value)
    useMessageToaster().toastSuccess('Removed offline versions for favorites.')
  } else {
    makePlayablesAvailableOffline(playables.value)
    useMessageToaster().toastSuccess(`Making ${pluralize(playables.value, 'song')} available offline…`)
  }
}

let initialized = false
const loading = ref(false)

const fetchFavorites = async () => {
  try {
    loading.value = true
    await playableStore.fetchFavorites()
    // Keep a direct reference to the store's favorites array so in-place reorder mutations are reflected in the UI
    allPlayables.value = playableStore.state.favorites

    const restoredField = lsGet<PlayableListSortField>('favorites-sort-field', 'position')!
    const restoredOrder = lsGet<SortOrder>('favorites-sort-order', 'asc')!
    sort(restoredField, restoredOrder)
  } finally {
    loading.value = false
  }
}

const sort = (field: MaybeArray<PlayableListSortField> | null, order: SortOrder) => {
  listConfig.reorderable = field === 'position'

  lsSet('favorites-sort-field', field)
  lsSet('favorites-sort-order', order)

  baseSort(field, order)

  if (field === 'position') {
    // Point directly to the store's array so in-place reorder mutations are reflected
    allPlayables.value = playableStore.state.favorites
  }
}

const onReorder = (target: Playable, placement: Placement) => {
  playableStore.moveFavoritesInList(selectedPlayables.value, target, placement)
}

useRouter().onScreenActivated('Favorites', async () => {
  if (!initialized) {
    initialized = true
    await fetchFavorites()
  }
})
</script>
