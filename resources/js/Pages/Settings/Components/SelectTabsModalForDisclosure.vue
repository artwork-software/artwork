<template>
    <ArtworkBaseModal
        title="Select tabs"
        description="Which tabs should be included in this component?"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <div class="space-y-4">
            <SwitchDualLabel
                v-model="enabled"
                :left-label="$t('Only include current tab')"
                :right-label="$t('Select tabs')"
            />
            <ArtworkBaseListbox
                v-if="enabled"
                v-model="selected"
                :items="tabOptions"
                by="id"
                option-label="name"
                label="Selected tabs"
                multiple
            />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="!canSubmit"
                @click="addComponentWithScope"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue'

defineOptions({name: 'SelectTabsModalForDisclosure'})

const props = defineProps({
    tabs: {type: [Array, Object], default: () => []},
    currentTab: {type: Object, required: true},
    componentData: {type: Object, required: true},
    position: {type: [Number, String], default: null},
    disclosureId: {type: [Number, String], required: true},
})

const emit = defineEmits(['close'])

// enabled = false: nur aktueller Tab; true: Tabs frei wählen
const enabled = ref(false)
const selected = ref(props.currentTab ? [props.currentTab] : [])
const processing = ref(false)

const tabOptions = computed(() => Object.values(props.tabs ?? {}))
const canSubmit = computed(() => !processing.value && (!enabled.value || selected.value.length > 0))

function closeModal(saved) {
    emit('close', saved)
}

function addComponentWithScope() {
    if (!canSubmit.value) {
        return
    }
    const scope = enabled.value ? selected.value : [props.currentTab]
    processing.value = true
    router.post(route('project-management-builder.add.disclosure.component.with.scopes'), {
        component_id: props.componentData.id,
        order: props.position,
        disclosure_id: props.disclosureId,
        scope: scope.map((tab) => tab.id),
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => closeModal(false),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
