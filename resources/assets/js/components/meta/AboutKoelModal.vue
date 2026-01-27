<template>
  <div
    v-koel-focus
    class="about text-center max-w-[480px] overflow-hidden relative"
    data-testid="about-koel"
    tabindex="0"
    @keydown.esc="close"
  >
    <main class="p-6">
      <div class="mb-4">
        <img alt="Logo" class="inline-block" :src="logo" width="128">
      </div>

      <div class="current-version">
        {{ appName }} {{ currentVersion }}
      </div>

      <p v-if="shouldNotifyNewVersion" data-testid="new-version-about">
        <a :href="latestVersionReleaseUrl" target="_blank">
          {{ t('meta.newVersion', { app: appName, version: latestVersion }) }}
        </a>
      </p>

      <p v-if="!hasCustomBranding" class="author">
        {{ t('meta.madeWith') }}
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        {{ t('meta.andQuiteAFewAwesome') }}
        <a href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank">{{ t('meta.contributors') }}</a>.
      </p>

      <CreditsBlock v-if="isDemo" />
    </main>

    <footer>
      <Btn danger data-testid="close-modal-btn" rounded @click.prevent="close">{{ t('playlists.close') }}</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'

import Btn from '@/components/ui/form/Btn.vue'
import CreditsBlock from '@/components/meta/CreditsBlock.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const { t } = useI18n()
const { name: appName, logo, hasCustomBranding } = useBranding()
const {
  shouldNotifyNewVersion,
  currentVersion,
  latestVersion,
  latestVersionReleaseUrl,
} = useNewVersionNotification()

const { currentUserCan } = usePolicies()

const close = () => emit('close')

const isDemo = window.IS_DEMO
</script>

<style lang="postcss" scoped>
p {
  @apply mx-0 my-3;
}

a {
  @apply text-k-fg hover:text-k-highlight;
}

.plus-badge {
  .key {
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-image: linear-gradient(97.78deg, #c62be8 17.5%, #671ce4 113.39%);
  }
}
</style>
