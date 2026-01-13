import { reactive } from 'vue'
import { merge } from 'lodash'
import { http } from '@/services/http'

export const settingStore = {
  state: reactive<Settings>({
    media_path: '',
  }),

  init (settings: Settings) {
    merge(this.state, settings)
  },

  async updateMediaPath (path: string) {
    await http.put('settings/media-path', {
      path,
    })

    this.state.media_path = path
  },

  async updateBranding (data: Partial<Branding>) {
    await http.put('settings/branding', data)
  },

  async updateWelcomeMessage (message: string, variables: Array<{ name: string, url: string }>) {
    await http.put('settings/welcome-message', {
      message,
      variables,
    })
  },
}
