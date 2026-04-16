import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { ref, shallowRef } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey, ModalKey } from '@/config/symbols'
import type { Ref } from 'vue'
import Component from './FooterExtraControls.vue'

let isAudioContextSupported = true

vi.mock('@/utils/supports', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/supports')>()),
  get isAudioContextSupported() {
    return isAudioContextSupported
  },
}))

type RenderOptions = {
  streamable?: Streamable
  isAudioContextSupported?: boolean
}

describe('footerExtraControls.vue', () => {
  const h = createHarness()
  let modalOptions: Ref<{ component: object | null; props?: Record<string, unknown> }>

  const renderComponent = ({ streamable, isAudioContextSupported: supported = true }: RenderOptions = {}) => {
    isAudioContextSupported = supported
    modalOptions = shallowRef({
      component: null,
    })

    return h.render(Component, {
      global: {
        stubs: {
          Equalizer: h.stub('Equalizer'),
          Volume: h.stub('Volume'),
        },
        provide: {
          [CurrentStreamableKey as symbol]: ref(streamable),
          [ModalKey as symbol]: modalOptions,
        },
      },
    })
  }

  afterEach(() => {
    isAudioContextSupported = true
  })

  it('renders', () => {
    h.setReadOnlyProperty(document, 'fullscreenEnabled', undefined)
    expect(renderComponent({ isAudioContextSupported: false }).html()).toMatchSnapshot()
  })

  it('toggles fullscreen mode', async () => {
    const { eventBus } = await import('@/utils/eventBus')
    h.setReadOnlyProperty(document, 'fullscreenEnabled', true)
    renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByTitle('Enter fullscreen'))

    expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
  })

  it('opens the equalizer modal when supported', async () => {
    await renderComponent({
      streamable: h.factory('song'),
    })

    await h.user.click(screen.getByTitle('Show equalizer'))

    expect(modalOptions.value.component).not.toBeNull()
  })

  it('does not open the equalizer modal for radio stations', async () => {
    await renderComponent({
      streamable: h.factory('radio-station'),
    })

    await h.user.click(screen.getByTitle('Equalizer is not available for radio'))

    expect(modalOptions.value.component).toBeNull()
  })

  it('does not render the equalizer button when audio context is unsupported', async () => {
    await renderComponent({
      streamable: h.factory('song'),
      isAudioContextSupported: false,
    })

    expect(screen.queryByTitle('Show equalizer')).toBeNull()
  })
})
