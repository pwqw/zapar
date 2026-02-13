import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarManageSection.vue'

describe('sidebarManageSection.vue', () => {
  const h = createHarness()

  it('shows all menu items if current user is an admin', () => {
    h.actingAsAdmin().render(Component)
    screen.getByText('My Songs')
    screen.getByText('Settings')
    screen.getByText('Users')
    screen.getByText('Upload')
  })

  it('shows My Songs for all users', () => {
    h.actingAsUser().render(Component)
    screen.getByText('My Songs')
    expect(screen.queryByText('Settings')).toBeNull()
    expect(screen.queryByText('Upload')).toBeNull()
    expect(screen.queryByText('Users')).toBeNull()
  })
})
