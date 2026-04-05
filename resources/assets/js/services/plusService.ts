import { http } from '@/services/http'

export const plusService = {
  async activateLicense(key: string): Promise<unknown> {
    return http.post('licenses/activate', { key })
  },
}
