<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('users.add') }}</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>{{ t('users.name') }}</template>
        <TextInput v-model="data.name" v-koel-focus name="name" required />
      </FormRow>
      <FormRow>
        <template #label>{{ t('users.email') }}</template>
        <TextInput v-model="data.email" name="email" required type="email" />
      </FormRow>
      <FormRow>
        <template #label>{{ t('users.password') }}</template>
        <TextInput
          v-model="data.password"
          autocomplete="new-password"
          name="password"
          required
          :title="t('users.password')"
          type="password"
        />
        <template #help>{{ t('users.passwordRequirements') }}</template>
      </FormRow>
      <RolePicker v-model="data.role" />
      <FormRow v-if="canEditVerified">
        <template #label>{{ t('users.verified') }}</template>
        <CheckBox v-model="data.verified" name="verified" :disabled="!canEditVerified" />
      </FormRow>
    </main>

    <footer>
      <Btn :disabled="loading" class="btn-add" type="submit">{{ t('auth.save') }}</Btn>
      <Btn :disabled="loading" class="btn-cancel" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { CreateUserData } from '@/stores/userStore'
import { userStore } from '@/stores/userStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'
import { cache } from '@/services/cache'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, loading, handleSubmit } = useForm<CreateUserData>({
  initialValues: {
    name: '',
    email: '',
    password: '',
    role: 'user',
    verified: false,
  },
  onSubmit: async data => await userStore.store(data),
  onSuccess: (user: User) => {
    // Clear permission cache for the newly created user so UI updates correctly
    cache.remove(['permission', 'user', user.id, 'edit'])
    cache.remove(['permission', 'user', user.id, 'delete'])
    toastSuccess(t('users.created', { name: user.name }))
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('playlists.discardChanges'))) {
    close()
  }
}

const canEditVerified = computed(() => {
  const currentUser = userStore.current
  const userRole = currentUser.role

  // Admin can always edit verified
  if (userRole === 'admin') {
    return true
  }

  // Moderator can edit verified
  if (userRole === 'moderator') {
    return true
  }

  // Verified manager can create verified artists
  if (userRole === 'manager' && currentUser.verified) {
    return data.role === 'artist'
  }

  return false
})
</script>
