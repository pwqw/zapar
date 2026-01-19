import { computed } from 'vue'

export const useWelcomeMessage = () => {
  const welcomeMessageData = window.WELCOME_MESSAGE || null

  const hasWelcomeMessage = computed(() => !!welcomeMessageData)

  const processedMessage = computed(() => {
    if (!welcomeMessageData?.message) {
      return ''
    }

    let message = welcomeMessageData.message

    // Replace each variable with an HTML link
    welcomeMessageData.variables?.forEach(variable => {
      const placeholder = `{${variable.name}}`
      const regex = new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g')
      message = message.replace(regex, variable.url)
    })

    return message
  })

  const variables = computed(() => welcomeMessageData?.variables || [])

  return {
    welcomeMessageData,
    hasWelcomeMessage,
    processedMessage,
    variables,
  }
}
