<template>
  <ul>
    <MenuItem @click="togglePlayback">
      {{ station.playback_state === 'Playing' ? $t('radio.stop') : $t('radio.play') }}
    </MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">
      {{ station.favorite ? $t('radio.undoFavorite') : $t('radio.favorite') }}
    </MenuItem>
    <template v-if="visibilityActions.length">
      <Separator />
      <MenuItem v-for="{ label, handler } in visibilityActions" :key="label" @click="handler">
        {{ label }}
      </MenuItem>
    </template>
    <Separator />
    <MenuItem v-if="allowEdit" @click="requestEditForm">{{ $t('menu.playable.edit') }}</MenuItem>
    <MenuItem v-if="allowDelete" @click="maybeDelete">{{ $t('ui.buttons.delete') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { pick } from 'lodash'
import { computed, onMounted, ref, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { radioStationStore, type RadioStationData } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { acl } from '@/services/acl'

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
const canPublishRadio = ref(false)
const canPrivatizeRadio = ref(false)

const stationPayload = (): RadioStationData =>
  pick(station.value, 'name', 'url', 'description', 'is_public', 'logo') as RadioStationData

const publicizeStation = () =>
  trigger(async () => {
    await radioStationStore.update(station.value, { ...stationPayload(), is_public: true })
    toastSuccess(t('misc.unmarkedAsPrivate', { item: t('radio.station') }))
  })

const privatizeStation = () =>
  trigger(async () => {
    await radioStationStore.update(station.value, { ...stationPayload(), is_public: false })
    toastSuccess(t('misc.markedAsPrivate', { item: t('radio.station') }))
  })

const visibilityActions = computed(() => {
  const actions: { label: string; handler: () => void }[] = []

  if (!station.value.is_public && canPublishRadio.value) {
    actions.push({
      label: t('menu.playable.unmarkAsPrivate'),
      handler: publicizeStation,
    })
  }

  if (station.value.is_public && canPrivatizeRadio.value) {
    actions.push({
      label: t('menu.playable.markAsPrivate'),
      handler: privatizeStation,
    })
  }

  return actions
})

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
  const [edit, del, pub, priv] = await Promise.all([
    currentUserCan.editRadioStation(station.value),
    currentUserCan.deleteRadioStation(station.value),
    acl.checkResourcePermission('radio-station', station.value.id, 'publish'),
    acl.checkResourcePermission('radio-station', station.value.id, 'edit'),
  ])

  allowEdit.value = edit
  allowDelete.value = del
  canPublishRadio.value = pub
  canPrivatizeRadio.value = priv
})
</script>
