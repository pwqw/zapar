import { reactive } from 'vue'
import { merge } from 'lodash'
import { http } from '@/services/http'

export interface GoogleDocPage {
  title: string
  slug: string
  embed_url: string
  default_back_url?: string
}

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

  async updateGoogleDocPages (pages: GoogleDocPage[]) {
    await http.put('settings/google-doc-pages', {
      pages,
    })
  },

  async getGoogleDocPages (): Promise<GoogleDocPage[]> {
    const response = await http.get<{ pages: GoogleDocPage[] }>('settings/google-doc-pages')
    return response.pages
  },

  async getGoogleDocPageBySlug (slug: string): Promise<GoogleDocPage> {
    return await http.get<GoogleDocPage>(`google-doc-pages/${slug}`)
  },
}
