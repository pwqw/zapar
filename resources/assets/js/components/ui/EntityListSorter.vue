<template>
  <BasicListSorter :items :field :order @sort="onSort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

defineProps<{
  items: { label: string, field: string }[]
  field?: string
  order?: SortOrder
}>()

const emit = defineEmits<{ (e: 'sort', field: string, order: SortOrder): void }>()

const onSort = (field: string, order: SortOrder) => emit('sort', field, order)
</script>
