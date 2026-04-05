<template>
  <HomeScreenBlock>
    <template #header>
      {{ $t('ui.tooltips.somethingRandom') }}
      <Btn v-if="playables.length" class="float-right" transparent rounded small @click.prevent="refresh">
        <Icon :icon="faRotateRight" />
        <span class="sr-only">{{ $t('ui.tooltips.refresh') }}</span>
      </Btn>
    </template>
    <PlayableCardGridSkeleton v-if="loading" class="-mx-6" />
    <template v-else>
      <PlayableCardGrid v-if="playables.length" :aria-busy="refreshing" class="-mx-6" :playables />
      <p v-else>{{ $t('emptyStates.newSongsEmpty') }}</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import Btn from '@/components/ui/form/Btn.vue'
import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'randomSongs')
const refreshing = ref(false)

const refresh = async () => {
  refreshing.value = true

  try {
    await overviewStore.refreshRandomSongs()
  } finally {
    refreshing.value = false
  }
}
</script>

<style lang="postcss" scoped>
:deep([aria-busy='true']) {
  @apply opacity-70 transition-opacity;
}
</style>
