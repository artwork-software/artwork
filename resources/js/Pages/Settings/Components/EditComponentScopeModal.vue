<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'

const props = defineProps({
  allTabs: { type: Array, required: true },
  componentInTabId: { type: [Number, String], required: true },
  initialSelection: { type: Array, required: true },
  currentTab: { type: Object, required: true },
})

const emit = defineEmits(['close'])

const enabled = ref(false) // false => only current tab; true => custom selection
const selectedIds = ref([...props.initialSelection])

// initialize toggle state based on initial selection
const initialIsOnlyCurrent = computed(() => {
  if (!Array.isArray(props.initialSelection) || props.initialSelection.length === 0) return true
  if (props.initialSelection.length === 1) return String(props.initialSelection[0]) === String(props.currentTab.id)
  return false
})

if (!initialIsOnlyCurrent.value) {
  enabled.value = true
} else {
  enabled.value = false
  selectedIds.value = [props.currentTab.id]
}

watch(enabled, (val) => {
  if (!val) {
    // force only current tab
    selectedIds.value = [props.currentTab.id]
  } else {
    // ensure current selection contains something sensible
    if (!Array.isArray(selectedIds.value) || selectedIds.value.length === 0) {
      selectedIds.value = [props.currentTab.id]
    }
  }
})

function close() { emit('close') }

function save() {
  const scope = enabled.value ? selectedIds.value : [props.currentTab.id]
  router.patch(
    route('tab.update.component.scope', { componentInTab: props.componentInTabId }),
    { scope },
    {
      preserveScroll: true,
      onSuccess: () => {
        // Reload just the tabs to reflect scope changes in the list
        try { router.reload({ only: ['tabs'] }) } catch (e) {}
        close()
      },
    }
  )
}
</script>

<template>
  <ArtworkBaseModal
    modal-size="sm:max-w-2xl"
    title="Tabs-Auswahl bearbeiten"
    description="Lege fest, von welchen Tabs die Dokumente angezeigt werden."
    @close="close"
  >
    <div class="space-y-5">
      <div class="flex items-center gap-x-3">
        <span class="text-sm text-gray-900">Nur aktuellen Tab einbeziehen</span>
        <button
          type="button"
          class="ml-auto rounded-md px-2 py-1 text-xs ring-1 ring-inset"
          :class="enabled ? 'ring-emerald-300 text-emerald-700 bg-emerald-50' : 'ring-zinc-300 text-zinc-700 bg-white'"
          @click="enabled = !enabled"
        >
          {{ enabled ? 'Tabs auswählen' : 'Aktueller Tab' }}
        </button>
      </div>

      <div v-if="enabled" class="border rounded-lg p-3">
        <div class="text-xs text-zinc-600 mb-2">Tabs auswählen</div>
        <div class="max-h-56 overflow-auto space-y-2">
          <label v-for="tab in allTabs" :key="tab.id" class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="rounded border-zinc-300" :value="tab.id" v-model="selectedIds" />
            <span>{{ tab.name }}</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" class="ui-button" @click="close">{{ $t('Cancel') }}</button>
        <button type="button" class="ui-button-primary" @click="save">{{ $t('Save') }}</button>
      </div>
    </div>
  </ArtworkBaseModal>
</template>
