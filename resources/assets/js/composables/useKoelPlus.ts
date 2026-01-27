import { computed } from 'vue'

export const useKoelPlus = () => {
  return {
    isPlus: computed(() => true),
    license: {
      shortKey: null,
      customerName: null,
      customerEmail: null,
    },
    checkoutUrl: computed(() => ''),
  }
}
