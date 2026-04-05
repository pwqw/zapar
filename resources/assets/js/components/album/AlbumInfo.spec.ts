import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import Component from './AlbumInfo.vue'

describe('albumInfo.vue', () => {
  const h = createHarness()

  const renderComponent = async (mode: EncyclopediaDisplayMode = 'aside', info?: AlbumInfo) => {
    commonStore.state.uses_last_fm = true

    if (info === undefined) {
      info = h.factory('album-info')
    }

    const album = h.factory('album', { name: 'IV' })
    const fetchMock = h.mock(encyclopediaService, 'fetchForAlbum').mockResolvedValue(info)

    const rendered = h.render(Component, {
      props: {
        album,
        mode,
      },
      global: {
        stubs: {
          TrackList: h.stub('album-info-tracks'),
          AlbumThumbnail: h.stub('thumbnail'),
        },
      },
    })

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(album)
    })

    return {
      ...rendered,
      album,
      fetchMock,
      info,
    }
  }

  it.each<[EncyclopediaDisplayMode]>([['aside'], ['full']])('renders in %s mode', async mode => {
    await renderComponent(mode)

    screen.getByTestId('album-info-tracks')

    if (mode === 'aside') {
      screen.getByTestId('thumbnail')
    } else {
      expect(screen.queryByTestId('thumbnail')).toBeNull()
    }

    expect(screen.getByTestId('album-info').classList.contains(mode)).toBe(true)
  })

  it('fetches album info on mount when Last.fm is enabled', async () => {
    const { fetchMock, album } = await renderComponent('aside')

    expect(fetchMock).toHaveBeenCalledWith(album)
  })
})
