<template>
  <div v-show="!showingForgotPasswordForm" class="flex min-h-screen flex-col sm:flex-row items-center justify-center">
    <!-- Welcome Message Section (Desktop: 70%, Mobile: Full Width) -->
    <div
      v-if="hasWelcomeMessage"
      class="w-full sm:w-7/12 px-6 py-12 sm:py-0 sm:px-8 flex flex-col justify-center"
    >
      <div class="prose dark:prose-invert max-w-none">
        <div class="welcome-message-content space-y-4">
          <div class="text-lg leading-relaxed" v-html="renderedMessage" />
        </div>
      </div>
    </div>

    <!-- Login Form Section (Desktop: 30%, Mobile: Full Width) -->
    <div
      class="w-full px-6 py-12 flex items-center justify-center"
      :class="hasWelcomeMessage ? 'sm:w-5/12 sm:py-0 sm:border-l sm:border-k-fg-10' : ''"
    >
      <div class="w-full sm:w-[288px] p-7 rounded-xl sm:border sm:border-transparent sm:bg-k-fg-10 relative">
        <div class="text-center mb-8">
          <img alt="Logo" class="inline-block" :src="logo" width="156">
        </div>

        <!-- Initial Buttons View -->
        <Transition name="fade" mode="out-in">
          <div v-if="!showInternalForm && !showAnonymousConsent" key="buttons" class="space-y-3">
            <FormRow v-if="ssoProviders.includes('Google')">
              <Btn
                class="w-full flex items-center justify-center gap-2"
                highlight
                data-testid="google-login"
                type="button"
                @click="handleGoogleLogin"
              >
                <img :src="googleLogo" alt="Google Logo" height="20" width="20">
                {{ t('auth.loginWithGoogle') }}
              </Btn>
            </FormRow>

            <FormRow v-if="allowAnonymous">
              <Btn
                class="w-full"
                danger
                data-testid="anonymous-login"
                type="button"
                @click="showAnonymousConsent = true"
              >
                {{ t('auth.noWantAccount') }}
              </Btn>
            </FormRow>

            <FormRow>
              <Btn class="w-full" data-testid="internal-account" type="button" @click="showInternalForm = true">
                {{ t('auth.internalAccount') }}
              </Btn>
            </FormRow>
          </div>

          <!-- Anonymous Consent View -->
          <div v-else-if="showAnonymousConsent" key="consent" class="space-y-3">
            <p class="text-sm text-k-fg-70">
              {{ t('auth.anonymousConsentIntro') }}
            </p>
            <FormRow>
              <LegalCheckboxes
                v-model:terms-accepted="termsAccepted"
                v-model:privacy-accepted="privacyAccepted"
                v-model:age-verified="ageVerified"
                :terms-url="consentTermsUrl"
                :privacy-url="consentPrivacyUrl"
              />
            </FormRow>
            <FormRow>
              <Btn
                class="w-full"
                data-testid="anonymous-consent-submit"
                :disabled="!allConsentsAccepted"
                type="button"
                @click="submitAnonymousConsent"
              >
                {{ t('auth.acceptAndContinue') }}
              </Btn>
            </FormRow>
            <FormRow>
              <button
                class="text-center text-[.95rem] text-k-fg-70 hover:text-k-fg-90 w-full"
                type="button"
                @click="showAnonymousConsent = false"
              >
                ← {{ t('auth.back') }}
              </button>
            </FormRow>
          </div>

          <!-- Email/Password Form View -->
          <form
            v-else
            key="form"
            :class="{ error: failed }"
            class="space-y-3"
            data-testid="login-form"
            @submit.prevent="handleSubmit"
          >
            <FormRow>
              <TextInput v-model="data.email" autofocus :placeholder="emailPlaceholder" required type="email" />
            </FormRow>

            <FormRow>
              <PasswordField v-model="data.password" :placeholder="passwordPlaceholder" required />
            </FormRow>

            <FormRow>
              <Btn class="w-full" data-testid="submit" type="submit">{{ t('auth.logIn') }}</Btn>
            </FormRow>

            <FormRow v-if="canResetPassword">
              <a class="text-right text-[.95rem] text-k-fg-70" role="button" @click.prevent="showForgotPasswordForm">
                {{ t('auth.forgotPassword') }}
              </a>
            </FormRow>

            <FormRow>
              <button
                class="text-center text-[.95rem] text-k-fg-70 hover:text-k-fg-90 w-full"
                type="button"
                @click="showInternalForm = false"
              >
                ← {{ t('auth.back') }}
              </button>
            </FormRow>
          </form>
        </Transition>
      </div>
    </div>
  </div>

  <ForgotPasswordForm v-if="showingForgotPasswordForm" @cancel="showingForgotPasswordForm = false" />
</template>

<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'
import { useBranding } from '@/composables/useBranding'
import { useWelcomeMessage } from '@/composables/useWelcomeMessage'
import { sanitizeUrl } from '@/utils/sanitizeHtml'
import DOMPurify from 'dompurify'

import Btn from '@/components/ui/form/Btn.vue'
import LegalCheckboxes from '@/components/ui/form/LegalCheckboxes.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import googleLogo from '@/../img/logos/google.svg'
import { openPopup } from '@/utils/helpers'

const emit = defineEmits<{ (e: 'loggedin'): void }>()

const { t, locale } = useI18n()
const { toastWarning, toastError } = useMessageToaster()
const { logo } = useBranding()
const { hasWelcomeMessage, welcomeMessageData } = useWelcomeMessage()

const demoAccount = window.DEMO_ACCOUNT || {
  email: 'demo@koel.dev',
  password: 'demo',
}

const failed = ref(false)
const showingForgotPasswordForm = ref(false)
const showInternalForm = ref(false)
const showAnonymousConsent = ref(false)
const termsAccepted = ref(false)
const privacyAccepted = ref(false)
const ageVerified = ref(false)
const canResetPassword = window.MAILER_CONFIGURED && !window.IS_DEMO
const consentTermsUrl = window.CONSENT_LEGAL_URLS?.terms_url ?? undefined
const consentPrivacyUrl = window.CONSENT_LEGAL_URLS?.privacy_url ?? undefined
const allConsentsAccepted = computed(() => termsAccepted.value && privacyAccepted.value && ageVerified.value)
const ssoProviders = window.SSO_PROVIDERS || []
const allowAnonymous = window.ALLOW_ANONYMOUS || false
const emailPlaceholder = window.IS_DEMO ? demoAccount.email : t('auth.yourEmailAddress')
const passwordPlaceholder = window.IS_DEMO ? demoAccount.password : t('auth.yourPassword')

const renderedMessage = computed(() => {
  if (!welcomeMessageData?.message) {
    return ''
  }

  let message = welcomeMessageData.message

  // Replace each variable with an HTML link
  welcomeMessageData.variables?.forEach(variable => {
    const placeholder = `{${variable.name}}`
    // Escape special regex characters in the placeholder
    const escapedPlaceholder = placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const regex = new RegExp(escapedPlaceholder, 'g')
    // Sanitize URL and variable name to prevent XSS
    const safeUrl = sanitizeUrl(variable.url)
    const safeName = DOMPurify.sanitize(variable.name, { ALLOWED_TAGS: [] })
    const linkHtml = `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-500 dark:text-blue-400 hover:underline">${safeName}</a>`
    message = message.replace(regex, linkHtml)
  })

  // Sanitize the entire message HTML to prevent XSS but preserve links
  return DOMPurify.sanitize(message, {
    ALLOWED_TAGS: ['a', 'br', 'p', 'em', 'strong', 'b', 'i'],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'class'],
  })
})

const { data, handleSubmit } = useForm<{ email: string, password: string }>({
  initialValues: window.IS_DEMO
    ? demoAccount
    : {
        email: '',
        password: '',
      },
  onSubmit: async ({ email, password }) => await authService.login(email, password),
  onSuccess: () => {
    failed.value = false
    // Reset the password so that the next login will have this field empty.
    data.password = ''
    emit('loggedin')
  },
  onError: (error: unknown) => {
    failed.value = true
    logger.error(error)
    window.setTimeout(() => (failed.value = false), 2000)
  },
})

const showForgotPasswordForm = () => (showingForgotPasswordForm.value = true)

const handleAnonymousLogin = async (params: { terms_accepted: boolean, privacy_accepted: boolean, age_verified: boolean, locale?: string }) => {
  try {
    const compositeToken = await authService.loginAnonymously(params)
    authService.setTokensUsingCompositeToken(compositeToken)
    emit('loggedin')
  } catch (error) {
    logger.error('Anonymous login failed:', error)
    toastError(t('auth.loginFailed'))
  }
}

const submitAnonymousConsent = async () => {
  if (!allConsentsAccepted.value) {
    return
  }
  await handleAnonymousLogin({
    terms_accepted: termsAccepted.value,
    privacy_accepted: privacyAccepted.value,
    age_verified: ageVerified.value,
    locale: locale.value,
  })
}

const handleGoogleLogin = async () => {
  try {
    window.onmessage = (msg: MessageEvent) => {
      if (msg.data) {
        authService.setTokensUsingCompositeToken(msg.data)
        emit('loggedin')
      }
    }
    openPopup('/auth/google/redirect', 'Google Login', 768, 640, window)
  } catch (error: unknown) {
    logger.error('SSO error: ', error)
    toastError(t('auth.loginFailed'))
  }
}

onMounted(() => {
  if (authService.hasRedirect()) {
    toastWarning(t('auth.pleaseLogInFirst'))
  }
})
</script>

<style lang="postcss" scoped>
/**
 * I like to move it move it
 * I like to move it move it
 * I like to move it move it
 * You like to - move it!
 */
@keyframes shake {
  8%,
  41% {
    transform: translateX(-10px);
  }
  25%,
  58% {
    transform: translateX(10px);
  }
  75% {
    transform: translateX(-5px);
  }
  92% {
    transform: translateX(5px);
  }
  0%,
  100% {
    transform: translateX(0);
  }
}

form {
  &.error {
    @apply border-red-500;
    animation: shake 0.5s;
  }
}

.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
