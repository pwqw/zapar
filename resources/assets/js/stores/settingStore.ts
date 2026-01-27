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
    terms_url: null,
    privacy_url: null,
  }),

  init (settings: Settings) {
    merge(this.state, settings)
  },

  async updateConsentLegalUrls (termsUrl: string | null, privacyUrl: string | null) {
    await http.put('settings/consent-legal-urls', {
      terms_url: termsUrl || null,
      privacy_url: privacyUrl || null,
    })
    this.state.terms_url = termsUrl
    this.state.privacy_url = privacyUrl
  },

  async updateMediaPath (path: string) {
    await http.put('settings/media-path', {
      path,
    })

    this.state.media_path = path
  },

  async updateBranding (data: Partial<Branding & { description?: string | null, og_image?: string | null }>) {
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
