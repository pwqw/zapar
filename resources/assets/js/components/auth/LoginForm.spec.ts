import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import Component from './LoginForm.vue'

describe('loginForm.vue', () => {
  const h = createHarness({
    authenticated: false,
  })

  const submitForm = async (loginMock: Mock) => {
    const rendered = h.render(Component)

    // First, click on "Internal account" to show the form
    await h.user.click(screen.getByTestId('internal-account'))
    await h.tick()

    await h.type(screen.getByPlaceholderText('Your email address', { exact: false }), 'john@doe.com')
    await h.type(screen.getByPlaceholderText('Your password', { exact: false }), 'secret')
    await h.user.click(screen.getByTestId('submit'))

    expect(loginMock).toHaveBeenCalledWith('john@doe.com', 'secret')

    return rendered
  }

  it('renders', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('logs in', async () => {
    expect((await submitForm(h.mock(authService, 'login'))).emitted().loggedin).toBeTruthy()
  })

  it('fails to log in', async () => {
    const mock = h.mock(authService, 'login').mockRejectedValue('Unauthenticated')
    const logMock = h.mock(logger, 'error')
    const rendered = h.render(Component)

    // First, click on "Internal account" to show the form
    await h.user.click(screen.getByTestId('internal-account'))
    await h.tick()

    await h.type(screen.getByPlaceholderText('Your email address', { exact: false }), 'john@doe.com')
    await h.type(screen.getByPlaceholderText('Your password', { exact: false }), 'secret')
    await h.user.click(screen.getByTestId('submit'))
    await h.tick()

    expect(mock).toHaveBeenCalledWith('john@doe.com', 'secret')
    expect(rendered.emitted().loggedin).toBeFalsy()
    expect(screen.getByTestId('login-form').classList.contains('error')).toBe(true)
    expect(logMock).toHaveBeenCalledWith('Unauthenticated')
  })

  it('shows forgot password form', async () => {
    h.render(Component)
    // First, click on "Internal account" to show the form
    await h.user.click(screen.getByTestId('internal-account'))
    await h.tick()
    await h.user.click(screen.getByText('Forgot password?', { exact: false }))

    await waitFor(() => screen.getByTestId('forgot-password-form'))
  })

  it('does not show forgot password form if mailer is not configure', async () => {
    window.MAILER_CONFIGURED = false
    h.render(Component)

    expect(screen.queryByText('Forgot password?', { exact: false })).toBeNull()
    window.MAILER_CONFIGURED = true
  })

  it('shows Google login button', async () => {
    window.SSO_PROVIDERS = ['Google']

    h.render(Component)

    screen.getByTestId('google-login')

    window.SSO_PROVIDERS = []
  })

  it('shows anonymous login button when allowed', async () => {
    window.ALLOW_ANONYMOUS = true
    h.render(Component)

    screen.getByTestId('anonymous-login')

    window.ALLOW_ANONYMOUS = false
  })

  it('hides anonymous login button when not allowed', async () => {
    window.ALLOW_ANONYMOUS = false
    h.render(Component)

    expect(screen.queryByTestId('anonymous-login')).toBeNull()
  })

  it('shows consent step when anonymous login is clicked', async () => {
    window.ALLOW_ANONYMOUS = true
    h.render(Component)

    await h.user.click(screen.getByTestId('anonymous-login'))
    await h.tick()

    expect(screen.getByTestId('anonymous-consent-submit')).toBeTruthy()
    expect(screen.getByTestId('terms-checkbox')).toBeTruthy()
    expect(screen.getByTestId('privacy-checkbox')).toBeTruthy()
    expect(screen.getByTestId('age-checkbox')).toBeTruthy()

    window.ALLOW_ANONYMOUS = false
  })

  it('logs in anonymously after accepting consent', async () => {
    window.ALLOW_ANONYMOUS = true
    const loginAnonymouslyMock = h.mock(authService, 'loginAnonymously').mockResolvedValue({
      'token': 'api-token',
      'audio-token': 'audio-token',
    })
    const setTokensMock = h.mock(authService, 'setTokensUsingCompositeToken')

    const rendered = h.render(Component)

    await h.user.click(screen.getByTestId('anonymous-login'))
    await h.tick()

    await h.user.click(screen.getByTestId('terms-checkbox'))
    await h.user.click(screen.getByTestId('privacy-checkbox'))
    await h.user.click(screen.getByTestId('age-checkbox'))
    await h.user.click(screen.getByTestId('anonymous-consent-submit'))
    await h.tick()

    expect(loginAnonymouslyMock).toHaveBeenCalledWith(expect.objectContaining({
      terms_accepted: true,
      privacy_accepted: true,
      age_verified: true,
    }))
    expect(setTokensMock).toHaveBeenCalled()
    expect(rendered.emitted().loggedin).toBeTruthy()

    window.ALLOW_ANONYMOUS = false
  })

  it('handles anonymous login failure', async () => {
    window.ALLOW_ANONYMOUS = true
    const loginAnonymouslyMock = h.mock(authService, 'loginAnonymously').mockRejectedValue('Network error')
    const logMock = h.mock(logger, 'error')

    h.render(Component)

    await h.user.click(screen.getByTestId('anonymous-login'))
    await h.tick()
    await h.user.click(screen.getByTestId('terms-checkbox'))
    await h.user.click(screen.getByTestId('privacy-checkbox'))
    await h.user.click(screen.getByTestId('age-checkbox'))
    await h.user.click(screen.getByTestId('anonymous-consent-submit'))
    await h.tick()

    expect(loginAnonymouslyMock).toHaveBeenCalled()
    expect(logMock).toHaveBeenCalledWith('Anonymous login failed:', 'Network error')

    window.ALLOW_ANONYMOUS = false
  })
})
