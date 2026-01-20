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
      <form
        :class="{ error: failed }"
        class="w-full sm:w-[288px] duration-500 p-7 rounded-xl space-y-3 sm:border sm:border-transparent sm:bg-k-fg-10"
        data-testid="login-form"
        @submit.prevent="handleSubmit"
      >
        <div class="text-center mb-8">
          <img alt="Logo" class="inline-block" :src="logo" width="156">
        </div>

        <FormRow>
          <TextInput v-model="data.email" autofocus :placeholder="emailPlaceholder" required type="email" />
        </FormRow>

        <FormRow>
          <PasswordField v-model="data.password" :placeholder="passwordPlaceholder" required />
        </FormRow>

        <FormRow>
          <Btn class="w-full" data-testid="submit" type="submit">{{ t('auth.logIn') }}</Btn>
        </FormRow>

        <FormRow v-if="allowAnonymous">
          <Btn
            class="w-full"
            variant="secondary"
            data-testid="anonymous-login"
            type="button"
            @click="handleAnonymousLogin"
          >
            {{ t('auth.noWantToLogin') }}
          </Btn>
        </FormRow>

        <FormRow v-if="canResetPassword">
          <a class="text-right text-[.95rem] text-k-fg-70" role="button" @click.prevent="showForgotPasswordForm">
            {{ t('auth.forgotPassword') }}
          </a>
        </FormRow>
      </form>
    </div>
  </div>

  <div v-if="ssoProviders.length" v-show="!showingForgotPasswordForm" class="fixed bottom-8 left-0 right-0 flex gap-3 items-center justify-center">
    <GoogleLoginButton v-if="ssoProviders.includes('Google')" @error="onSSOError" @success="onSSOSuccess" />
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
import PasswordField from '@/components/ui/form/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'
import GoogleLoginButton from '@/components/auth/sso/GoogleLoginButton.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'loggedin'): void }>()

const { t } = useI18n()
const { toastWarning, toastError } = useMessageToaster()
const { logo } = useBranding()
const { hasWelcomeMessage, welcomeMessageData } = useWelcomeMessage()

const demoAccount = window.DEMO_ACCOUNT || {
  email: 'demo@koel.dev',
  password: 'demo',
}

const failed = ref(false)
const showingForgotPasswordForm = ref(false)
const canResetPassword = window.MAILER_CONFIGURED && !window.IS_DEMO
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

const handleAnonymousLogin = async () => {
  try {
    const compositeToken = await authService.loginAnonymously()
    authService.setTokensUsingCompositeToken(compositeToken)
    emit('loggedin')
  } catch (error) {
    logger.error('Anonymous login failed:', error)
    toastError(t('auth.loginFailed'))
  }
}

const onSSOError = (error: any) => {
  logger.error('SSO error: ', error)
  toastError(t('auth.loginFailed'))
}

const onSSOSuccess = (token: CompositeToken) => {
  authService.setTokensUsingCompositeToken(token)
  emit('loggedin')
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
</style>
