<template>
    <section class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised">
        <div class="flex items-center gap-3 px-5 py-3">
            <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-left" :aria-expanded="open" @click="open = !open">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-surface-sunken text-text-subtle">
                    <IconChevronRight class="size-4 transition-transform" :class="open ? 'rotate-90' : ''" />
                </span>
                <span class="min-w-0">
                    <span class="block font-lexend text-sm font-semibold text-text">{{ $t('Appearance in the ticket shop') }}</span>
                    <span class="block truncate text-xs text-text-subtle">{{ form.title || production.fallback.title }}</span>
                </span>
            </button>
            <BaseChip v-if="production.status === 'published'" variant="success">{{ $t('In the shop') }}</BaseChip>
            <BaseChip v-else-if="production.linked" variant="neutral">{{ $t('Draft in artwork tickets') }}</BaseChip>
            <BaseChip v-else variant="neutral">{{ $t('Not yet in artwork tickets') }}</BaseChip>
            <a v-if="production.shopUrl" :href="production.shopUrl" target="_blank" rel="noopener" class="ui-button-small">
                <IconExternalLink class="size-3.5" />{{ $t('Open shop page') }}
            </a>
        </div>

        <div v-if="open" class="border-t border-border-hairline">
            <div class="grid gap-x-6 gap-y-5 px-5 py-5 lg:grid-cols-[260px_minmax(0,1fr)]">
                <!-- Picture first: it is what the audience sees first on the shop page. -->
                <div>
                    <div class="group relative flex aspect-[16/9] items-center justify-center overflow-hidden rounded-lg border border-border-subtle bg-surface-sunken">
                        <img v-if="previewUrl" :src="previewUrl" alt="" class="h-full w-full object-cover" />
                        <span v-else class="flex flex-col items-center gap-1.5 text-text-subtle">
                            <IconPhoto class="size-7" stroke-width="1.5" />
                            <span class="text-xs">{{ $t('No picture yet') }}</span>
                        </span>
                    </div>
                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                        <label class="ui-button-small cursor-pointer">
                            <IconUpload class="size-3.5" />{{ previewUrl ? $t('Replace') : $t('Choose picture') }}
                            <input type="file" accept="image/*" class="sr-only" @change="pickHero" />
                        </label>
                        <button v-if="production.fallback.keyVisualUrl && !heroFile && !previewUrl" type="button" class="ui-button-small" @click="useKeyVisual">{{ $t('Use key visual') }}</button>
                        <button v-if="previewUrl" type="button" class="text-xs text-text-subtle hover:text-danger" @click="removeHero">{{ $t('Remove') }}</button>
                    </div>
                    <p class="mt-2 text-xs leading-[18px] text-text-subtle">{{ $t('Shown on the shop page and in link previews. JPG or PNG, up to 8 MB.') }}</p>
                </div>

                <div class="flex flex-col gap-4">
                    <BaseInput id="ticketing-production-title" v-model="form.title" :label="$t('Name in the ticket shop')" :placeholder="production.fallback.title" />
                    <BaseTextarea id="ticketing-production-description" v-model="form.description" :rows="3" :label="$t('Description')" :placeholder="production.fallback.description || $t('What the audience should know.')" />

                    <div v-if="reductions.length">
                        <span class="font-lexend mb-1.5 block text-xs font-medium text-[#3F424A]">{{ $t('Reductions granted') }}</span>
                        <div class="flex flex-wrap gap-2" role="group">
                            <button v-for="reduction in reductions" :key="reduction.id" type="button" role="checkbox" :aria-checked="grantedIds.includes(reduction.id)"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-[13px] transition-colors"
                                    :class="grantedIds.includes(reduction.id) ? 'border-accent-600 bg-accent-50 text-accent-700' : 'border-border bg-surface text-text-muted hover:bg-surface-sunken hover:text-text'"
                                    @click="toggleReduction(reduction.id, !grantedIds.includes(reduction.id))">
                                <IconCheck v-if="grantedIds.includes(reduction.id)" class="size-3.5" stroke-width="2.5" />
                                <IconPlus v-else class="size-3.5 opacity-60" stroke-width="2" />
                                {{ reduction.name }}<span class="tabular-nums" :class="grantedIds.includes(reduction.id) ? 'text-accent-700/70' : 'text-text-subtle'">{{ reductionValue(reduction) }}</span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs leading-[18px] text-text-subtle">{{ $t('Reductions are defined house-wide in the ticketing settings; here you choose which ones this production grants.') }}</p>
                    </div>
                </div>
            </div>

            <p v-if="error" class="px-5 pb-3 text-sm text-danger">{{ error }}</p>

            <div class="flex items-center justify-between gap-3 rounded-b-lg border-t border-border-hairline bg-surface-sunken px-5 py-3">
                <span class="text-xs text-text-subtle">{{ production.linked ? $t('Changes are written to artwork tickets right away.') : $t('Applied when the first date is released.') }}</span>
                <button type="button" class="ui-button-add" :disabled="saving || !dirty" @click="save">{{ saving ? $t('Saving…') : $t('Save') }}</button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconCheck, IconChevronRight, IconExternalLink, IconPhoto, IconPlus, IconUpload } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { formatEuro } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

const props = defineProps({
    projectId: { type: Number, required: true },
    production: { type: Object, required: true },
    reductions: { type: Array, required: true },
    canEdit: { type: Boolean, default: true },
})

const emit = defineEmits(['saved'])

const { t } = useI18n()

const open = ref(!props.production.linked)
const saving = ref(false)
const error = ref('')

/* null reduction ids mean "the house defaults"; the form shows those as granted. */
const defaultIds = computed(() => props.reductions.filter((reduction) => reduction.defaultEnabled).map((reduction) => reduction.id))
const grantedIds = ref([...(props.production.reductionTypeIds ?? defaultIds.value)])

const form = ref({ title: props.production.title ?? '', description: props.production.description ?? '' })
const heroFile = ref(null)
const heroRemoved = ref(false)
const localPreview = ref(null)

const previewUrl = computed(() => localPreview.value ?? (heroRemoved.value ? null : props.production.heroUrl))

const dirty = computed(() => form.value.title !== (props.production.title ?? '')
    || form.value.description !== (props.production.description ?? '')
    || JSON.stringify([...grantedIds.value].sort()) !== JSON.stringify([...(props.production.reductionTypeIds ?? defaultIds.value)].sort())
    || heroFile.value !== null
    || heroRemoved.value)

watch(() => props.production, (production) => {
    form.value = { title: production.title ?? '', description: production.description ?? '' }
    grantedIds.value = [...(production.reductionTypeIds ?? defaultIds.value)]
    heroFile.value = null
    heroRemoved.value = false
    localPreview.value = null
})

function reductionValue(reduction) {
    return reduction.kind === 'percent' ? `−${reduction.value / 100} %` : `−${formatEuro(reduction.value)}`
}

function toggleReduction(id, on) {
    grantedIds.value = on ? [...new Set([...grantedIds.value, id])] : grantedIds.value.filter((granted) => granted !== id)
}

function pickHero(changeEvent) {
    const file = changeEvent.target.files?.[0]
    if (!file) return
    heroFile.value = file
    heroRemoved.value = false
    localPreview.value = URL.createObjectURL(file)
}

/* The project's key visual, fetched from this installation and sent like an upload. */
async function useKeyVisual() {
    const response = await fetch(props.production.fallback.keyVisualUrl)
    const blob = await response.blob()
    heroFile.value = new File([blob], props.production.fallback.keyVisualUrl.split('/').pop(), { type: blob.type })
    heroRemoved.value = false
    localPreview.value = URL.createObjectURL(blob)
}

function removeHero() {
    heroFile.value = null
    localPreview.value = null
    heroRemoved.value = true
}

async function save() {
    saving.value = true
    error.value = ''
    const body = new FormData()
    body.append('title', form.value.title)
    body.append('description', form.value.description)
    body.append('reduction_type_ids', JSON.stringify(grantedIds.value))
    if (heroFile.value) body.append('hero', heroFile.value)
    if (heroRemoved.value) body.append('remove_hero', '1')
    try {
        const { data } = await axios.post(route('projects.tabs.ticketing.production', { project: props.projectId }), body)
        emit('saved', data)
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || Object.values(requestError?.response?.data?.errors ?? {}).flat()[0] || t('The ticketing data could not be loaded.')
    } finally {
        saving.value = false
    }
}
</script>
