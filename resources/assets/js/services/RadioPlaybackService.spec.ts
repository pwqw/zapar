import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { socketService } from '@/services/socketService'
import { playbackService } from '@/services/RadioPlaybackService'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      h.createAudioPlayer()
      playbackService.activate(document.querySelector('#audio-player')!)
    },
  })

  it('only initializes once', () => {
    const firstPlayer = playbackService.player

    playbackService.activate(document.querySelector('#audio-player')!)

    expect(playbackService.player).toBe(firstPlayer)
  })

  it('plays a radio station', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    const toBePlayedStation = h.factory('radio-station')
    toBePlayedStation.playback_state = 'Stopped'

    radioStationStore.state.stations = [currentStation, toBePlayedStation]

    const broadcastMock = h.mock(socketService, 'broadcast')
    h.mock(radioStationStore, 'getSourceUrl', 'https://station.com/stream.mp3')

    const radioElement = document.getElementById('audio-radio') as HTMLAudioElement
    const radioPlayMock = vi.fn().mockResolvedValue(undefined)
    Object.defineProperty(radioElement, 'readyState', {
      configurable: true,
      get: () => HTMLMediaElement.HAVE_CURRENT_DATA,
    })
    radioElement.play = radioPlayMock

    await playbackService.play(toBePlayedStation)

    expect(radioPlayMock).toHaveBeenCalled()
    expect(radioElement.src).toContain('https://station.com/stream.mp3')
    expect(currentStation.playback_state).toBe('Stopped')
    expect(toBePlayedStation.playback_state).toBe('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
  })

  it('pauses a radio station playback', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    radioStationStore.state.stations = [currentStation]

    const pauseMock = h.mock(playbackService.player.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')
    await playbackService.stop()

    expect(pauseMock).toHaveBeenCalled()
    expect(playbackService.player.media.src).toBe('')
    // Radio stations use 'Stopped' instead of 'Paused' since radio streams are live
    expect(currentStation.playback_state).toBe('Stopped')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', currentStation)
  })
})
