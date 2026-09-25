<template>
    <div v-if="externals.length" class="flex items-center gap-1.5">
        <button
            v-for="external in externals"
            :key="external.scope_id"
            type="button"
            class="relative inline-flex size-8 items-center justify-center rounded-full ring-1 ring-inset transition-colors"
            :class="statusMeta(external.status).classes"
            :title="tooltip(external)"
            :aria-label="tooltip(external)"
            @click="showModal = true"
        >
            <component :is="statusMeta(external.status).icon" class="size-4" stroke-width="1.5" />
        </button>
    </div>

    <ArtworkBaseModal
        v-if="showModal"
        :title="$t('External persons in this tab')"
        :description="$t('Submitted data can be confirmed or returned for revision. The external person is informed by email in both cases.')"
        modal-size="max-w-2xl"
        @close="closeModal"
    >
        <ul class="mt-4 divide-y divide-border-subtle">
            <li v-for="external in externals" :key="external.scope_id" class="py-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-2">
                        <span class="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full ring-1 ring-inset" :class="statusMeta(external.status).classes">
                            <component :is="statusMeta(external.status).icon" class="size-4" stroke-width="1.5" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-text">{{ external.name }}</p>
                            <p class="truncate text-xs text-text-subtle">{{ external.email }}</p>
                            <p class="mt-1 text-xs text-text-muted">{{ statusText(external) }}</p>
                            <p v-if="external.review_comment" class="mt-1 whitespace-pre-line text-xs text-text-subtle">„{{ external.review_comment }}“</p>
                        </div>
                    </div>
                    <div v-if="external.can_review" class="flex shrink-0 gap-2">
                        <BaseUIButton
                            v-if="external.status === 'submitted' || external.status === 'confirmed'"
                            type="button"
                            variant="secondary"
                            hide-icon
                            :disabled="busy"
                            @click="startReturn(external)"
                        >
                            {{ $t('Return for revision') }}
                        </BaseUIButton>
                        <BaseUIButton
                            v-if="external.status === 'submitted'"
                            type="button"
                            variant="primary"
                            hide-icon
                            :disabled="busy"
                            @click="confirm(external)"
                        >
                            {{ $t('Confirm') }}
                        </BaseUIButton>
                    </div>
                </div>

                <div v-if="returning?.scope_id === external.scope_id" class="mt-3 space-y-2 pl-9">
                    <BaseTextarea
                        :id="`return-comment-${external.scope_id}`"
                        v-model="returnComment"
                        label="Comment for the external person (optional)"
                        :rows="3"
                    />
                    <div class="flex justify-end gap-2">
                        <BaseUIButton type="button" variant="secondary" hide-icon @click="returning = null">{{ $t('Cancel') }}</BaseUIButton>
                        <BaseUIButton type="button" variant="primary" hide-icon :disabled="busy" @click="submitReturn(external)">
                            {{ $t('Return for revision') }}
                        </BaseUIButton>
                    </div>
                </div>
            </li>
        </ul>
        <p v-if="error" class="mt-2 text-xs text-danger">{{ error }}</p>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { IconUser, IconUserCheck, IconUserEdit, IconUserUp } from '@tabler/icons-vue'

defineOptions({ name: 'ExternalTabStatus' })

const props = defineProps({
    projectId: { type: [Number, String], required: true },
    tabId: { type: [Number, String], required: true },
})

const emit = defineEmits(['update:openWriters', 'reviewed'])
const $t = useTranslation()

const externals = ref([])
const showModal = ref(false)
const busy = ref(false)
const error = ref('')
const returning = ref(null)
const returnComment = ref('')

// Externe mit Schreibzugriff, die schon im Tab waren und noch nicht abgesendet haben
const openWriters = computed(() => externals.value.filter((e) => e.can_write && (e.status === 'open' || e.status === 'returned') && e.last_login_at))
watch(openWriters, (writers) => emit('update:openWriters', writers), { immediate: true })

const STATUS = {
    open: { icon: IconUserEdit, classes: 'bg-surface-sunken text-text-muted ring-border' },
    returned: { icon: IconUserEdit, classes: 'bg-warning-surface text-warning ring-warning-border' },
    submitted: { icon: IconUserUp, classes: 'bg-info-surface text-info ring-info-border' },
    confirmed: { icon: IconUserCheck, classes: 'bg-success-surface text-success ring-success-border' },
}

function statusMeta(status) {
    return STATUS[status] ?? { icon: IconUser, classes: 'bg-surface-sunken text-text-muted ring-border' }
}

function formatDateTime(iso) {
    return iso ? new Date(iso).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' }) : ''
}

function statusText(external) {
    switch (external.status) {
        case 'submitted':
            return $t('Submitted on {date} – waiting for confirmation', { date: formatDateTime(external.last_submitted_at) })
        case 'confirmed':
            return $t('Confirmed by {name} on {date}', { name: external.reviewed_by ?? '', date: formatDateTime(external.reviewed_at) })
        case 'returned':
            return $t('Returned for revision by {name} on {date}', { name: external.reviewed_by ?? '', date: formatDateTime(external.reviewed_at) })
        default:
            return external.last_login_at
                ? $t('In progress – not submitted yet')
                : $t('Invited – not logged in yet')
    }
}

function tooltip(external) {
    return `${external.name}: ${statusText(external)}`
}

async function load() {
    try {
        const { data } = await axios.get(route('projects.tabs.externals.index', { project: props.projectId, projectTab: props.tabId }))
        externals.value = data.externals ?? []
    } catch (e) {
        externals.value = []
    }
}

function replace(updated) {
    const index = externals.value.findIndex((e) => e.scope_id === updated.scope_id)
    if (index >= 0) externals.value.splice(index, 1, updated)
}

async function confirm(external) {
    busy.value = true
    error.value = ''
    try {
        const { data } = await axios.post(route('projects.tabs.externals.confirm', { project: props.projectId, projectTab: props.tabId, scope: external.scope_id }))
        replace(data.external)
        emit('reviewed')
    } catch (e) {
        error.value = e?.response?.data?.message ?? $t('Failed to save')
    } finally {
        busy.value = false
    }
}

function startReturn(external) {
    returning.value = external
    returnComment.value = ''
}

async function submitReturn(external) {
    busy.value = true
    error.value = ''
    try {
        const { data } = await axios.post(
            route('projects.tabs.externals.return', { project: props.projectId, projectTab: props.tabId, scope: external.scope_id }),
            { comment: returnComment.value },
        )
        replace(data.external)
        emit('reviewed')
        returning.value = null
    } catch (e) {
        error.value = e?.response?.data?.message ?? $t('Failed to save')
    } finally {
        busy.value = false
    }
}

function closeModal() {
    showModal.value = false
    returning.value = null
}

defineExpose({ reload: load })

onMounted(load)
</script>
