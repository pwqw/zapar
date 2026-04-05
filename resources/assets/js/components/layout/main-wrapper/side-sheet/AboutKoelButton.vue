<template>
  <SideSheetButton
    v-koel-tooltip.left
    :title="aboutTitle"
    @click.prevent="openAboutKoelModal"
  >
    <Icon :icon="faInfoCircle" />
    <span
      v-if="shouldNotifyNewVersion"
      class="absolute w-[10px] aspect-square right-px top-px rounded-full bg-k-highlight"
      data-testid="new-version-indicator"
    />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { faInfoCircle } from '@fortawesome/free-solid-svg-icons'
import { defineAsyncComponent } from '@/utils/helpers'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { useBranding } from '@/composables/useBranding'
import { useModal } from '@/composables/useModal'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const AboutKoelModal = defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue'))
const { openModal } = useModal()

const { t } = useI18n()

const { shouldNotifyNewVersion } = useNewVersionNotification()
const { name: appName } = useBranding()

const aboutTitle = computed(() =>
  shouldNotifyNewVersion.value ? t('meta.newVersionAvailable') : t('meta.about', { app: appName }),
)

const openAboutKoelModal = () => openModal<'ABOUT_KOEL'>(AboutKoelModal)
</script>
