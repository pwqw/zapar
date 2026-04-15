<template>
  <span
    class="text-xs uppercase font-semibold bg-k-fg-10 rounded px-[5px] py-[1px] border border-px border-k-fg-10"
  >
    {{ label }}
  </span>
</template>

<script setup lang="ts">
interface Props {
  label?: string
}

withDefaults(defineProps<Props>(), {
  label: 'Privado',
})
</script>
