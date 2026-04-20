import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import { playbackService } from '@/services/RadioPlaybackService'
import { acl } from '@/services/acl'
import { radioStationStore } from '@/stores/radioStationStore'
import EditRadioStationForm from '@/components/radio/EditRadioStationForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './RadioStationContextMenu.vue'

describe('radioStationContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  const renderComponent = async (
    station?: RadioStation,
    manageable = true,
    aclByAction?: Partial<Record<'edit' | 'delete' | 'publish', boolean>>,
  ) => {
    h.mock(acl, 'checkResourcePermission').mockImplementation(async (_type, _id, action) => {
      if (action === 'edit' && aclByAction?.edit !== undefined) {
        return aclByAction.edit
      }

      if (action === 'delete' && aclByAction?.delete !== undefined) {
        return aclByAction.delete
      }

      if (action === 'publish' && aclByAction?.publish !== undefined) {
        return aclByAction.publish
      }

      return manageable
    })

    station =
      station ||
      h.factory('radio-station', {
        favorite: false,
      })

    const rendered = h.render(Component, {
      props: {
        station,
      },
    })

    // For all menu items (including Delete and Edit, which require permission checks) to be rendered
    await h.tick(7)

    return {
      ...rendered,
      station,
    }
  }

  it('renders with Edit/Delete items', async () => {
    await renderComponent()

    screen.getByText('Edit…')
    screen.getByText('Delete')
    screen.getByText('Play')
    screen.getByText('Favorite')
  })

  it('renders without Edit/Delete items', async () => {
    await renderComponent(undefined, false)

    expect(screen.queryByText('Edit…')).toBeNull()
    expect(screen.queryByText('Delete')).toBeNull()
    screen.getByText('Play')
    screen.getByText('Favorite')
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')

    const { station } = await renderComponent()
    await h.user.click(screen.getByText('Play'))

    expect(playMock).toHaveBeenCalledWith(station)
  })

  it('stops', async () => {
    h.createAudioPlayer()

    const stopMock = h.mock(playbackService, 'stop')

    await renderComponent(h.factory('radio-station', { playback_state: 'Playing' }))
    await h.user.click(screen.getByText('Stop'))

    expect(stopMock).toHaveBeenCalled()
  })

  it('favorites', async () => {
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    const { station } = await renderComponent(h.factory('radio-station', { favorite: false }))

    await h.user.click(screen.getByText('Favorite'))
    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('undoes favorite', async () => {
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    const { station } = await renderComponent(h.factory('radio-station', { favorite: true }))

    await h.user.click(screen.getByText('Undo Favorite'))
    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('requests edit form', async () => {
    const { station } = await renderComponent()

    await h.user.click(screen.getByText('Edit…'))

    await assertOpenModal(openModalMock, EditRadioStationForm, { station })
  })

  it('deletes', async () => {
    const deleteMock = h.mock(radioStationStore, 'delete')
    const { station } = await renderComponent()

    await h.user.click(screen.getByText('Delete'))
    expect(deleteMock).toHaveBeenCalledWith(station)
  })

  it('offers to publicize a private station when ACL publish is allowed', async () => {
    await renderComponent(h.factory('radio-station', { is_public: false, favorite: false }), true, {
      publish: true,
      edit: true,
      delete: true,
    })

    screen.getByText('Unmark as Private')
  })

  it('offers to privatize a public station when ACL edit is allowed', async () => {
    await renderComponent(h.factory('radio-station', { is_public: true, favorite: false }), true, {
      publish: true,
      edit: true,
      delete: true,
    })

    screen.getByText('Mark as Private')
  })

  it('publicizes via API', async () => {
    const updateMock = h.mock(radioStationStore, 'update').mockImplementation(async (s, data) => {
      Object.assign(s, data)
      return s
    })

    const st = h.factory('radio-station', { is_public: false, favorite: false })
    await renderComponent(st, true, { publish: true, edit: true, delete: true })

    await h.user.click(screen.getByText('Unmark as Private'))

    expect(updateMock).toHaveBeenCalled()
    const [, payload] = updateMock.mock.calls[0]!
    expect(payload.is_public).toBe(true)
  })
})
