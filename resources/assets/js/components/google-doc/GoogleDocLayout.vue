<template>
  <section class="h-screen w-screen flex flex-col overflow-hidden bg-k-bg-primary">
    <!-- Navbar -->
    <header class="flex-shrink-0 h-k-header-height bg-k-bg-secondary border-b border-k-border flex items-center px-6 gap-4">
      <button
        v-if="!loading"
        title="Volver"
        class="back-button flex items-center justify-center w-10 h-10 rounded-full hover:bg-k-bg-hover transition-colors"
        @click="goBack"
      >
        <Icon :icon="faArrowLeft" class="text-k-text-primary" />
      </button>

      <h1 class="text-xl md:text-2xl font-thin md:font-bold text-k-text-primary flex-1 overflow-hidden whitespace-nowrap text-ellipsis">
        {{ page?.title || 'Cargando...' }}
      </h1>
    </header>

    <!-- Contenido principal -->
    <main class="flex-1 overflow-hidden relative">
      <div v-if="loading" class="flex items-center justify-center h-full">
        <p class="text-k-text-secondary">Cargando documento...</p>
      </div>

      <div v-else-if="error" class="flex items-center justify-center h-full">
        <div class="text-center">
          <p class="text-red-500 mb-4">{{ error }}</p>
        </div>
      </div>

      <div v-else-if="iframeSrc" class="w-full h-full">
        <iframe
          :src="iframeSrc"
          class="w-full h-full border-0"
          allowfullscreen
        />
      </div>
    </main>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons'
import { useRouter } from '@/composables/useRouter'
import { type GoogleDocPage, settingStore } from '@/stores/settingStore'

const { getRouteParam, go } = useRouter()

const page = ref<GoogleDocPage | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const iframeSrc = ref<string | null>(null)

const slug = computed(() => getRouteParam('slug'))

const loadPage = async () => {
  if (!slug.value) {
    error.value = 'Slug no especificado'
    loading.value = false
    return
  }

  try {
    loading.value = true
    error.value = null

    page.value = await settingStore.getGoogleDocPageBySlug(slug.value)

    if (page.value?.embed_url) {
      // La URL ya debe venir con embedded=true desde la BD
      iframeSrc.value = page.value.embed_url
    } else {
      error.value = 'URL de embed no disponible'
      console.error('No embed_url in page:', page.value)
    }
  } catch (e: any) {
    error.value = e.response?.status === 404
      ? 'Página no encontrada'
      : 'Error al cargar el documento'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  // Intentar usar el historial del navegador
  if (window.history.length > 1) {
    go(-1)
  } else if (page.value?.default_back_url) {
    // Si no hay historial, usar default_back_url
    go(page.value.default_back_url)
  } else {
    // Fallback a home
    go('/home')
  }
}

onMounted(() => {
  loadPage()
})
</script>
