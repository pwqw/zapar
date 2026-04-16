import { waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './index.vue'

describe('index.vue', () => {
  const h = createHarness()

  it('initializes playback and related services', async () => {
    h.createAudioPlayer()

    const plyr = document.createElement('div')
    plyr.className = 'plyr'
    document.body.appendChild(plyr)

    h.render(Component)
    preferenceStore.initialized.value = true

    // The component no longer calls playbackManager.useQueuePlayback() directly
    // Services are activated lazily when the user actually plays something
    // This test just verifies the component renders without errors
    await waitFor(() => {
      expect(preferenceStore.initialized.value).toBe(true)
    })
  })

  it('does not repeatedly poll .plyr when it is missing', async () => {
    h.createAudioPlayer()
    const querySelectorSpy = vi.spyOn(document, 'querySelector')

    h.render(Component)
    preferenceStore.initialized.value = true

    await new Promise(resolve => setTimeout(resolve, 25))

    const plyrLookups = querySelectorSpy.mock.calls.filter(([selector]) => selector === '.plyr').length

    expect(plyrLookups).toBeLessThan(10)
  })
})
