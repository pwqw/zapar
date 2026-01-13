<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader>{{ t('screens.settings') }}</ScreenHeader>
    </template>

    <Tabs class="-mx-6">
      <TabList>
        <TabButton
          :selected="currentTab === 'general'"
          aria-controls="settingsPaneGeneral"
          @click="currentTab = 'general'"
        >
          {{ t('settings.general') }}
        </TabButton>
        <TabButton
          :selected="currentTab === 'branding'"
          aria-controls="settingsPaneBranding"
          @click="currentTab = 'branding'"
        >
          {{ t('settings.branding') }}
        </TabButton>
        <TabButton
          :selected="currentTab === 'welcome'"
          aria-controls="settingsPaneWelcome"
          @click="currentTab = 'welcome'"
        >
          {{ t('settings.welcomeMessage') }}
        </TabButton>
      </TabList>

      <TabPanelContainer>
        <TabPanel v-show="currentTab === 'general'" id="settingsPaneGeneral" aria-labelledby="settingsPaneGeneral">
          <MediaPathSettingGroup open />
        </TabPanel>

        <TabPanel v-if="currentTab === 'branding'" id="settingsPaneBranding" aria-labelledby="settingsPaneBranding">
          <BrandingSettingGroup v-if="isPlus" :current-branding="currentBranding" />
        </TabPanel>

        <TabPanel v-if="currentTab === 'welcome'" id="settingsPaneWelcome" aria-labelledby="settingsPaneWelcome">
          <WelcomeMessageForm />
        </TabPanel>
      </TabPanelContainer>
    </Tabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { defineAsyncComponent } from '@/utils/helpers'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import MediaPathSettingGroup from '@/components/screens/settings/MediaPathSettingGroup.vue'
import BrandingSettingGroup from '@/components/screens/settings/BrandingSettingGroup.vue'

import { useKoelPlus } from '@/composables/useKoelPlus'
import { useBranding } from '@/composables/useBranding'

const { t } = useI18n()
const { currentBranding } = useBranding()
const { isPlus } = useKoelPlus()

const WelcomeMessageForm = defineAsyncComponent(() => import('@/components/screens/settings/WelcomeMessageForm.vue'))

const { get, set } = useLocalStorage()

const currentTab = ref(get<'general' | 'branding' | 'welcome'>('settingsScreenTab', 'general'))

watch(currentTab, tab => set('settingsScreenTab', tab))
</script>
