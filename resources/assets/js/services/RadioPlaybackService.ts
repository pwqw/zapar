import { BasePlaybackService } from '@/services/BasePlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import { use } from '@/utils/helpers'
import { socketService } from '@/services/socketService'
import { normalizeVolume, volumeManager } from '@/services/volumeManager'
import { watch } from 'vue'

/**
 * RadioPlaybackService uses a separate audio element that NEVER passes through AudioContext.
 * This allows radio streams to play directly without CORS requirements, even if
 * audioService has already been initialized for queue playback.
 */
export class RadioPlaybackService extends BasePlaybackService {
  private radioAudioElement: HTMLAudioElement | null = null
  private volumeWatcher: (() => void) | null = null
  private boundRadioEvents: Array<[string, EventListener]> = []

  public activate (plyrWrapper: HTMLElement) {
    // For radio, we use a separate audio element that never goes through AudioContext
    // This element is NOT managed by Plyr and is never linked to createMediaElementSource
    if (this.radioAudioElement && !document.body.contains(this.radioAudioElement)) {
      if (this.boundRadioEvents.length) {
        this.boundRadioEvents.forEach(([event, handler]) => {
          this.radioAudioElement!.removeEventListener(event, handler)
        })
        this.boundRadioEvents = []
      }
      if (this.volumeWatcher) {
        this.volumeWatcher()
        this.volumeWatcher = null
      }
      this.radioAudioElement = null
    }

    if (!this.radioAudioElement) {
      // Get or create the dedicated radio audio element
      let radioElement = document.getElementById('audio-radio') as HTMLAudioElement
      if (!radioElement) {
        // Create it if it doesn't exist
        radioElement = document.createElement('audio')
        radioElement.id = 'audio-radio'
        radioElement.className = 'hidden'
        radioElement.controls = true
        document.body.appendChild(radioElement)
      }
      this.radioAudioElement = radioElement

      // Sync volume with volumeManager
      this.volumeWatcher = watch(volumeManager.volume, volume => {
        if (this.radioAudioElement) {
          try {
            this.radioAudioElement.volume = normalizeVolume(volume)
          } catch (error) {
            // Ignore errors if element is not ready yet
            console.warn('Failed to set radio audio volume:', error)
          }
        }
      }, { immediate: true })

      // Add event listeners directly to the radio element
      const errorHandler = this.onError.bind(this)
      const endedHandler = this.onEnded.bind(this)
      const timeUpdateHandler = this.onTimeUpdate.bind(this)

      this.radioAudioElement.addEventListener('error', errorHandler)
      this.radioAudioElement.addEventListener('ended', endedHandler)
      this.radioAudioElement.addEventListener('timeupdate', timeUpdateHandler)

      this.boundRadioEvents = [
        ['error', errorHandler],
        ['ended', endedHandler],
        ['timeupdate', timeUpdateHandler],
      ]
    }

    // We still need to initialize the player for compatibility, but we won't use it for playback
    // The player is needed for the UI and volume management
    if (!this.player) {
      super.activate(plyrWrapper)
    }

    return this
  }

  public async play (station: RadioStation) {
    // Stop any currently playing radio station
    use(radioStationStore.current, station => station.playback_state = 'Stopped')

    // Stop any currently playing queue item to prevent conflicts
    if (this.player?.media && !this.player.media.paused) {
      this.player.media.pause()
      this.player.media.currentTime = 0
      this.player.media.removeAttribute('src')
    }

    station.playback_state = 'Playing'

    // Ensure the radio audio element is available
    if (!this.radioAudioElement) {
      await this.activate(
        (document.querySelector('#audio-player') || document.querySelector('.plyr')) as HTMLElement,
      )
    }

    const url = radioStationStore.getSourceUrl(station)
    this.radioAudioElement!.src = url

    // Explicitly call load() to start loading the stream
    this.radioAudioElement!.load()

    // Wait for the stream to be ready before playing
    return new Promise<void>((resolve, reject) => {
      const tryPlay = async (shouldReject = false) => {
        try {
          if (this.radioAudioElement!.muted) {
            this.radioAudioElement!.muted = false
          }
          await this.radioAudioElement!.play()
          socketService.broadcast('SOCKET_STREAMABLE', station)
          resolve()
        } catch (error) {
          if (shouldReject) {
            reject(error)
          } else {
            console.warn('Play attempt failed, will retry on canplay', error)
          }
        }
      }

      // If already ready, play immediately
      if (this.radioAudioElement!.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
        tryPlay()
      } else {
        // Wait for canplay event (stream is ready to play)
        this.radioAudioElement!.addEventListener('canplay', () => tryPlay(true), { once: true })

        // Also try on loadstart as fallback
        this.radioAudioElement!.addEventListener('loadstart', () => {
          setTimeout(() => tryPlay(), 200)
        }, { once: true })
      }
    })
  }

  public async stop () {
    return this.pause()
  }

  protected onError (): void { // eslint-disable node/handle-callback-err
    // @todo Handle radio playback errors?
  }

  public fastSeek (): void {
    // Not supported for radio playback
  }

  public forward (): void {
    // Not supported for radio playback
  }

  protected onEnded (): void {
    // Not supported for radio playback
  }

  protected onTimeUpdate (): void {
    // Not supported for radio playback
  }

  public async pause () {
    use(radioStationStore.current, station => {
      // Set to 'Stopped' instead of 'Paused' for radio
      // Radio streams are live and don't have a pause state
      station.playback_state = 'Stopped'

      // Broadcast the updated station state.
      socketService.broadcast('SOCKET_STREAMABLE', station)
    })

    // For radio playback, we simply stop the radio audio element and reset the media source.
    if (this.radioAudioElement) {
      this.radioAudioElement.pause()
      this.radioAudioElement.currentTime = 0
      this.radioAudioElement.removeAttribute('src')
    }

    // Also pause the Plyr player if it exists (for UI consistency)
    // This ensures the queue player doesn't resume accidentally
    if (this.player) {
      this.player.media.pause()
      this.player.media.currentTime = 0
      this.player.media.removeAttribute('src')
    }
  }

  public async playNext () {
    // Not supported for radio playback
  }

  public async playPrev () {
    // Not supported for radio playback
  }

  public async resume () {
    if (!radioStationStore.current) {
      throw new Error('Logic exception: no current radio station.')
    }

    return this.play(radioStationStore.current)
  }

  public rewind (): void {
    // Not supported for radio playback
  }

  public seekTo (): void {
    // Not supported for radio playback
  }

  public rotateRepeatMode (): void {
    // Not supported for radio playback
  }

  async toggle () {
    const currentStation = radioStationStore.current
    if (!currentStation) {
      return
    }

    // If radio is playing, stop it
    if (currentStation.playback_state === 'Playing') {
      await this.stop()
    } else {
      // If radio is stopped/paused, play it
      // This ensures we always play the current radio station, not a previous queue item
      await this.play(currentStation)
    }
  }

  public deactivate () {
    // Clean up the radio audio element event listeners
    if (this.radioAudioElement && this.boundRadioEvents.length > 0) {
      this.boundRadioEvents.forEach(([event, handler]) => {
        this.radioAudioElement!.removeEventListener(event, handler)
      })
      this.boundRadioEvents = []
    }

    // Clean up volume watcher
    if (this.volumeWatcher) {
      this.volumeWatcher()
      this.volumeWatcher = null
    }

    // Call parent deactivate to clean up Plyr player event listeners
    super.deactivate()
  }
}

export const playbackService = new RadioPlaybackService()
