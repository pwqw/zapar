import { describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { audioService } from '@/services/audioService'
import { playbackManager } from '@/services/playbackManager'
import { playbackService as queuePlaybackService } from '@/services/QueuePlaybackService'
import { playbackService as radioPlaybackService } from '@/services/RadioPlaybackService'
import { eventBus } from '@/utils/eventBus'
import Component from './VisualizerScreen.vue'

const { initMock } = vi.hoisted(() => ({
  initMock: vi.fn().mockResolvedValue(vi.fn()),
}))

vi.mock('@/stores/visualizerStore', () => ({
  visualizerStore: {
    all: [{ id: 'default', name: 'Default Visualizer', init: initMock }],
    getVisualizerById: () => ({ id: 'default', name: 'Default Visualizer', init: initMock }),
  },
}))

describe('visualizerScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.createAudioPlayer()
      initMock.mockClear()
      playbackManager._currentService = null
      audioService.initialized = false
      audioService.analyzer = null as never
      audioService.context = null as never
      audioService.source = null as never
      audioService.element = null as never
    },
  })

  it('renders visualizer selector', () => {
    h.render(Component)
    screen.getByText('Default Visualizer')
  })

  it('does not initialize the visualizer while radio playback is active', async () => {
    playbackManager._currentService = radioPlaybackService
    audioService.initialized = true
    audioService.analyzer = {} as AnalyserNode
    audioService.context = {} as AudioContext
    audioService.source = {} as MediaElementAudioSourceNode
    audioService.element = document.querySelector('#audio-player')!

    h.render(Component)
    await h.tick(2)

    expect(initMock).not.toHaveBeenCalled()
  })

  it('retries initialization when processed queue audio becomes available', async () => {
    playbackManager._currentService = radioPlaybackService

    h.render(Component)
    await h.tick(2)

    expect(initMock).not.toHaveBeenCalled()

    playbackManager._currentService = queuePlaybackService
    audioService.initialized = true
    audioService.analyzer = {} as AnalyserNode
    audioService.context = {} as AudioContext
    audioService.source = {} as MediaElementAudioSourceNode
    audioService.element = document.querySelector('#audio-player')!

    eventBus.emit('SOCKET_STREAMABLE', h.factory('song'))

    await waitFor(() => expect(initMock).toHaveBeenCalledTimes(1))
  })
})
