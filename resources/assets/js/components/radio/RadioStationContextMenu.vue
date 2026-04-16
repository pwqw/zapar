<template>
  <ul>
    <MenuItem @click="togglePlayback">
      {{ station.playback_state === 'Playing' ? $t('radio.stop') : $t('radio.play') }}
    </MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">
      {{ station.favorite ? $t('radio.undoFavorite') : $t('radio.favorite') }}
    </MenuItem>
    <Separator />
    <MenuItem v-if="allowEdit" @click="requestEditForm">{{ $t('menu.playable.edit') }}</MenuItem>
    <MenuItem v-if="allowDelete" @click="maybeDelete">{{ $t('ui.buttons.delete') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { onMounted, ref, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

const props = defineProps<{ station: RadioStation }>()
const { station } = toRefs(props)

const EditRadioStationForm = defineAsyncComponent(() => import('@/components/radio/EditRadioStationForm.vue'))

const { t } = useI18n()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { currentUserCan } = usePolicies()

const allowEdit = ref(false)
const allowDelete = ref(false)

const togglePlayback = () =>
  trigger(async () => {
    const playbackService = playback('radio')

    if (station.value.playback_state === 'Playing') {
      await playbackService.stop()
    } else {
      await playbackService.play(station.value)
    }
  })

const toggleFavorite = () => trigger(() => radioStationStore.toggleFavorite(station.value))

const requestEditForm = () =>
  trigger(() => openModal<'EDIT_RADIO_STATION_FORM'>(EditRadioStationForm, { station: station.value }))

const maybeDelete = () =>
  trigger(async () => {
    if (await showConfirmDialog(t('radio.deleteConfirm'))) {
      await radioStationStore.delete(station.value)
      toastSuccess(t('radio.deleted'))
    }
  })

onMounted(async () => {
  allowEdit.value = await currentUserCan.editRadioStation(station.value)
  allowDelete.value = await currentUserCan.deleteRadioStation(station.value)
})
</script>
