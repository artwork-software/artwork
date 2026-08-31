<template>
    <ArtworkBaseModal
        @close="handleClose"
        modal-size="max-w-7xl 2xl:max-w-[104rem]"
        :title="issueOfMaterial?.id ?  $t('Edit issue of material') : $t('New issue of material')"
        :description="issueOfMaterial?.id ? $t('Edit the details of the issue of material') : $t('Create a new issue of material')"
        classes-in-white-background="!p-0"
    >

        <div class="w-full mb-5 bg-surface-sunken rounded-t-lg p-5" v-if="!checkIfEditMode">
            <div class="w-fit px-5">
                <SwitchGroup as="div" class="flex items-center justify-between gap-x-8" >
                    <span class="flex grow flex-col">
                      <SwitchLabel as="span" class="text-sm/6 font-medium text-text" passive>
                          {{ $t('Internal material issue') }}
                      </SwitchLabel>
                      <SwitchDescription as="span" class="text-xs text-text-subtle">
                          {{ $t('Create an internal material issue for employees') }}
                      </SwitchDescription>
                    </span>
                <Switch v-model="internOrExternal" :disabled="checkIfEditMode" :class="[internOrExternal ? 'bg-accent-600' : 'bg-border-subtle', 'relative inline-flex h-6 w-16 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors disabled:bg-text-subtle duration-200 ease-in-out focus:ring-2 focus:ring-accent-600 focus:ring-offset-2 focus:outline-hidden']">
                    <span aria-hidden="true" :class="[internOrExternal ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-8 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <span class="flex grow flex-col">
                      <SwitchLabel as="span" class="text-sm/6 font-medium text-text" passive>{{ $t('Borrowing slip') }}</SwitchLabel>
                      <SwitchDescription as="span" class="text-xs text-text-subtle">
                          {{ $t('Create a borrowing slip for external material issues') }}
                      </SwitchDescription>
                    </span>
                </SwitchGroup>
            </div>
        </div>

        <div class="px-5 pb-5 pt-2">
            <div v-if="internOrExternal" class="flex flex-col gap-y-4">
                <ExternMaterialIssueModal ref="externFormRef" :load-article-form-basket="loadArticleFormBasket && !checkIfEditMode" :extern-material-issue="externMaterialIssue" :planning-date="planningDate" :project="isInProjectComponent ? project : null" :first-event="firstEvent" :last-event="lastEvent" @close="$emit('close')" @saved="handleSaved" />
            </div>
            <div v-else>
                <CreateInternMaterialIssueModul ref="internFormRef" :load-article-form-basket="loadArticleFormBasket && !checkIfEditMode" :project="project" :issue-of-material="issueOfMaterial" :is-in-project-component="isInProjectComponent" :first-event="firstEvent" :last-event="lastEvent" :planning-date="planningDate" :project-tab-id="projectTabId" @close="$emit('close')" @saved="handleSaved" />
            </div>
        </div>

    </ArtworkBaseModal>

    <!-- Bestätigungsdialog beim Schließen: erscheint nur bei ungespeicherten Änderungen
         und bietet Speichern direkt an (Abnahme Ref. 3.7) -->
    <ArtworkBaseModal
        v-if="showDiscardConfirmation"
        @close="showDiscardConfirmation = false"
        modal-size="sm:max-w-md"
        :title="$t('Discard data')"
        :description="$t('Should the entered data be discarded?')"
    >
        <div class="flex items-center justify-between gap-3 mt-4">
            <button
                type="button"
                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-text-muted hover:bg-surface-sunken transition"
                @click="showDiscardConfirmation = false"
            >
                {{ $t('No, continue editing') }}
            </button>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white hover:bg-danger transition"
                    @click="confirmDiscard"
                >
                    {{ $t('Discard') }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-accent-600 px-4 py-2 text-sm font-semibold text-white hover:bg-accent-700 transition"
                    @click="saveAndClose"
                >
                    {{ $t('Save') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>
<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {computed, defineAsyncComponent, ref} from "vue";
import {Switch, SwitchDescription, SwitchGroup, SwitchLabel} from "@headlessui/vue";


const props = defineProps({
    issueOfMaterial: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            project_id: null,
            project: null,
            start_date: '',
            start_time: '00:00',
            end_date: '',
            end_time: '23:59',
            room_id: null,
            notes: '',
            responsible_user_ids: [],
            special_items_done: false,
            files: [],
            articles: [],
            special_items: []
        })
    },
    externMaterialIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
        })
    },
    isExternOrIntern: {
        type: Boolean,
        required: false,
    },
    project: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    isInProjectComponent: {
        type: Boolean,
        required: false,
        default: false,
    },
    loadArticleFormBasket: {
        type: Boolean,
        required: false,
        default: false,
    },
    firstEvent: {
        type: Object,
        required: false,
        default: null,
    },
    lastEvent: {
        type: Object,
        required: false,
        default: null,
    },
    planningDate: {
        type: String,
        required: false,
        default: null,
    },
    projectTabId: {
        type: Number,
        required: false,
        default: 1,
    },
})


const internOrExternal = ref(props.isExternOrIntern)
const showDiscardConfirmation = ref(false)

const internFormRef = ref(null)
const externFormRef = ref(null)
const activeFormRef = () => (internOrExternal.value ? externFormRef.value : internFormRef.value)

// Backdrop-Klick/Escape: nur bei ungespeicherten Änderungen nachfragen (Abnahme Ref. 3.7).
// Vorher fragte ausgerechnet der Edit-Modus NIE nach und verwarf Änderungen kommentarlos.
const handleClose = () => {
    const form = activeFormRef()
    // Fallback: solange die async geladene Formular-Komponente nicht bereit ist, sicherheitshalber fragen
    const dirty = typeof form?.isDirty === 'function' ? form.isDirty() : true
    if (dirty) {
        showDiscardConfirmation.value = true
        return
    }
    emit('close')
}

const confirmDiscard = () => {
    showDiscardConfirmation.value = false
    emit('close')
}

// Speichern aus der Rückfrage: Submit der Formular-Komponente — bei Erfolg emittet sie
// saved+close, bei Validierungsfehlern bleibt das Modal mit den Fehlermeldungen offen
const saveAndClose = () => {
    showDiscardConfirmation.value = false
    activeFormRef()?.submit?.()
}

const CreateInternMaterialIssueModul = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/Components/CreateInternMaterialIssueModul.vue'),
    delay: 0,
    timeout: 3000,
})

const ExternMaterialIssueModal = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/Components/ExternMaterialIssueModal.vue'),
    delay: 0,
    timeout: 3000,
})

const emit = defineEmits(['close', 'saved']);

const handleSaved = (quantityData) => {
    emit('saved', quantityData);
};

const checkIfEditMode = computed(() => {
    return !!(props.issueOfMaterial?.id || props.externMaterialIssue?.id);


});
</script>
