import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SettingsScreen.vue'

describe('settingsScreen.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          MediaPathSettingGroup: h.stub('media-path-setting-group'),
          BrandingSettingGroup: h.stub('branding-setting-group'),
        },
      },
    })
  }

  it('does not show branding settings without manage settings permission', async () => {
    renderComponent()
    screen.getByTestId('media-path-setting-group')
    await h.user.click(screen.getByRole('tab', { name: 'Branding' }))
    expect(screen.queryByTestId('branding-setting-group')).toBeNull()
  })

  it('shows branding settings for admins in Community edition', async () => {
    h.actingAsAdmin()
    renderComponent()
    screen.getByTestId('media-path-setting-group')
    await h.user.click(screen.getByRole('tab', { name: 'Branding' }))
    screen.getByTestId('branding-setting-group')
  })
})
