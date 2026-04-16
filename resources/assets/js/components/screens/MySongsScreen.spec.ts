import { waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { routes } from '@/config/routes'
import { playableStore } from '@/stores/playableStore'
import Component from './MySongsScreen.vue'

describe('mySongsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      playableStore.state.playables = h.factory('song', 20)
      h.actingAsUser()
    },
  })

  const getRouteByName = (name: string) => routes.find(route => route.name === name)!

  it('re-fetches owned songs when route is re-activated', async () => {
    const fetchMock = h.mock(playableStore, 'paginateSongs').mockResolvedValue(2)

    h.router.activateRoute(getRouteByName('my-songs'))
    h.render(Component)

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))

    h.router.activateRoute(getRouteByName('songs.index'))
    h.router.activateRoute(getRouteByName('my-songs'))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(2))
    expect(fetchMock).toHaveBeenLastCalledWith({
      sort: 'title',
      order: 'asc',
      page: 1,
      owned: true,
    })
  })
})
