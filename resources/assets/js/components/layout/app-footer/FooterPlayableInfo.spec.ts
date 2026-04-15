import { describe, expect, it } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import { cache } from '@/services/cache'
import Router from '@/router'
import Component from './FooterPlayableInfo.vue'

describe('footerPlayableInfo.vue', () => {
  const h = createHarness()

  it('renders with no current playable', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('renders with current playable', () => {
    const song = h.factory('song', {
      title: 'Fahrstuhl zum Mond',
      album_cover: 'https://via.placeholder.com/150',
      playback_state: 'Playing',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
    })

    expect(
      h
        .render(Component, {
          global: {
            provide: {
              [CurrentStreamableKey]: ref(song),
            },
          },
        })
        .html(),
    ).toMatchSnapshot()
  })

  it('does not navigate or set scroll intent when no playable', async () => {
    const goMock = h.mock(Router, 'go')
    const setMock = h.mock(cache, 'set')

    const { container } = h.render(Component)

    const thumb = container.querySelector('.album-thumb') as HTMLElement
    await h.user.click(thumb)

    expect(goMock).not.toHaveBeenCalled()
    expect(setMock).not.toHaveBeenCalled()
  })
})
