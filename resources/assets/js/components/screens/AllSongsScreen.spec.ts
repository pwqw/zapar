import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { routes } from '@/config/routes'
import Router from '@/router'
import type { Route } from '@/router'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './AllSongsScreen.vue'

describe('allSongsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.song_count = 420
      commonStore.state.song_length = 123_456
      playableStore.state.playables = h.factory('song', 20)
      h.actingAsUser()
    },
  })

  const renderComponent = async () => {
    const fetchMock = h.mock(playableStore, 'paginateSongs').mockImplementation(async () => {
      playableStore.state.playables = h.factory('song', 20)
      return 2
    })

    h.router.$currentRoute.value = {
      screen: 'Songs',
      path: '/songs',
    }

    const rendered = h.render(Component, {
      global: {
        stubs: {
          SongList: h.stub('song-list'),
        },
      },
    })

    await waitFor(() =>
      expect(fetchMock).toHaveBeenCalledWith({
        sort: 'title',
        order: 'asc',
        page: 1,
      }),
    )
    await waitFor(() => expect(playableStore.state.playables.length).toBeGreaterThan(0))

    return [rendered, fetchMock] as const
  }

  const getRouteByName = (name: string) => routes.find(route => route.name === name)! as Route

  it('renders', async () => {
    const [{ html }] = await renderComponent()
    expect(html()).toMatchSnapshot()
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const queueMock = h.mock(queueStore, 'fetchRandom')
    const playMock = h.mock(playbackService, 'playFirstInQueue')
    const goMock = h.mock(Router, 'go')
    await renderComponent()

    await h.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))

    await waitFor(() => {
      expect(queueMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalled()
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('re-fetches all songs when route is re-activated', async () => {
    const fetchMock = h.mock(playableStore, 'paginateSongs').mockResolvedValue(2)

    h.router.activateRoute(getRouteByName('songs.index'))
    h.render(Component)

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1))

    h.router.activateRoute(getRouteByName('my-songs'))
    h.router.activateRoute(getRouteByName('songs.index'))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(2))
    expect(fetchMock).toHaveBeenLastCalledWith({
      sort: 'title',
      order: 'asc',
      page: 1,
    })
  })
})
