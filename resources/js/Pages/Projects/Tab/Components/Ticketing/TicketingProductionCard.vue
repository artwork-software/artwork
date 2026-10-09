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
            <BaseChip v-else-if="production.linked" variant="neutral">{{ $t('Draft in Artwork-Tickets') }}</BaseChip>
            <BaseChip v-else variant="neutral">{{ $t('Not yet in Artwork-Tickets') }}</BaseChip>
            <a v-if="production.shopUrl" :href="production.shopUrl" target="_blank" rel="noopener" class="ui-button-small">
                <IconExternalLink class="size-3.5" />{{ $t('Open shop page') }}
            </a>
        </div>

        <div v-if="open" class="border-t border-border-hairline">
            <div class="grid gap-x-6 gap-y-5 px-5 py-5 lg:grid-cols-[260px_minmax(0,1fr)]">
                <!-- Pictures first: they are what the audience sees first on the shop page. 3:2 is the shop's crop. -->
                <div @dragover.prevent="dragging = room > 0" @dragleave="dragging = $event.currentTarget.contains($event.relatedTarget)" @drop.prevent="dropFiles">
                    <span class="font-lexend mb-1.5 block text-xs font-medium text-[#3F424A]">{{ $t('Pictures') }}</span>

                    <template v-if="cover">
                        <div class="group relative aspect-[3/2] overflow-hidden rounded-lg border border-border-subtle bg-surface-sunken" :class="dragging ? 'ring-2 ring-accent-600 ring-offset-2' : ''">
                            <img :src="cover.url" alt="" class="size-full object-cover" />
                            <span class="absolute bottom-2 left-2 rounded-md bg-surface/90 px-1.5 py-0.5 text-[11px] font-medium text-text">{{ $t('Cover') }}</span>
                            <button type="button" :class="['absolute top-2 right-2', overlayButton]" :aria-label="$t('Remove')" :title="$t('Remove')" @click="removePicture(cover)">
                                <IconX class="size-3.5" />
                            </button>
                        </div>

                        <ul class="mt-2 grid grid-cols-3 gap-2">
                            <li v-for="picture in further" :key="picture.key" class="group relative aspect-[3/2] overflow-hidden rounded-md border border-border-subtle bg-surface-sunken">
                                <img :src="picture.url" alt="" class="size-full object-cover" />
                                <button type="button" :class="['absolute top-1 left-1', overlayButton]" :aria-label="$t('Use as cover')" :title="$t('Use as cover')" @click="coverKey = picture.key">
                                    <IconStar class="size-3.5" />
                                </button>
                                <button type="button" :class="['absolute top-1 right-1', overlayButton]" :aria-label="$t('Remove')" :title="$t('Remove')" @click="removePicture(picture)">
                                    <IconX class="size-3.5" />
                                </button>
                            </li>
                            <li v-if="room > 0">
                                <label class="flex aspect-[3/2] cursor-pointer items-center justify-center rounded-md border border-dashed border-border-strong text-text-subtle transition-colors hover:bg-surface-sunken hover:text-text focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-600" :title="$t('Add pictures')">
                                    <IconPlus class="size-4" />
                                    <input type="file" accept="image/*" multiple class="sr-only" :aria-label="$t('Add pictures')" @change="pickFiles" />
                                </label>
                            </li>
                        </ul>
                    </template>

                    <template v-else>
                        <label class="flex aspect-[3/2] cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed transition-colors focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent-600"
                               :class="dragging ? 'border-accent-600 bg-accent-50' : 'border-border-strong bg-surface-sunken hover:text-text'">
                            <IconPhoto class="mb-1 size-7 text-text-subtle" stroke-width="1.5" />
                            <span class="text-sm font-medium text-text">{{ $t('Choose pictures') }}</span>
                            <span class="text-xs text-text-subtle">{{ $t('or drag them here') }}</span>
                            <input type="file" accept="image/*" multiple class="sr-only" @change="pickFiles" />
                        </label>
                        <button v-if="production.fallback.keyVisualUrl" type="button" class="ui-button-small mt-2.5" @click="useKeyVisual">{{ $t('Use key visual') }}</button>
                    </template>

                    <p class="mt-2 text-xs leading-[18px] text-text-subtle">{{ $t('The cover appears large on the shop page and in link previews, up to {max} more next to it. JPG or PNG, up to 8 MB each.', { max: production.maxImages }) }}</p>
                </div>

                <div class="flex flex-col gap-4">
                    <BaseInput id="ticketing-production-title" v-model="form.title" :label="$t('Name in the ticket shop')" :placeholder="production.fallback.title" />
                    <BaseRichTextEditor id="ticketing-production-description" v-model="form.description" :label="$t('Description')" :placeholder="production.fallback.description || $t('What the audience should know.')" />

                    <div v-if="reductions.length">
                        <span class="font-lexend mb-1.5 block text-xs font-medium text-[#3F424A]">{{ $t('Reductions granted') }}</span>
                        <TicketingReductionChips v-model="grantedIds" :reductions="reductions" :label="$t('Reductions granted')" />
                        <p class="mt-2 text-xs leading-[18px] text-text-subtle">{{ $t('Reductions are defined house-wide in the ticketing settings; here you choose which ones this production grants.') }}</p>
                    </div>
                </div>
            </div>

            <p v-if="error" class="px-5 pb-3 text-sm text-danger">{{ error }}</p>

            <div class="flex items-center justify-between gap-3 rounded-b-lg border-t border-border-hairline bg-surface-sunken px-5 py-3">
                <span class="text-xs text-text-subtle">{{ production.linked ? $t('Changes are written to Artwork-Tickets right away.') : $t('Applied when the first date is released.') }}</span>
                <button type="button" class="ui-button-add" :disabled="saving || !dirty" @click="save">{{ saving ? $t('Saving…') : $t('Save') }}</button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconChevronRight, IconExternalLink, IconPhoto, IconPlus, IconStar, IconX } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseRichTextEditor from '@/Artwork/Inputs/BaseRichTextEditor.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import TicketingReductionChips from '@/Pages/Projects/Tab/Components/Ticketing/TicketingReductionChips.vue'
import { grantedIdsOf } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

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
const grantedIds = ref(grantedIdsOf(props.production, props.reductions))

const form = ref({ title: props.production.title ?? '', description: props.production.description ?? '' })

const heroKept = ref(true)
const removedImageIds = ref([])
const newImages = ref([])
/* The picture chosen as cover; null means the first one. */
const coverKey = ref(null)
const dragging = ref(false)

const pictures = computed(() => {
    const hero = props.production.heroUrl && heroKept.value ? [{ key: 'hero', kind: 'hero', url: props.production.heroUrl }] : []
    const saved = props.production.images
        .filter((image) => !removedImageIds.value.includes(image.id))
        .map((image) => ({ key: `saved-${image.id}`, kind: 'saved', id: image.id, url: image.url }))
    const picked = newImages.value.map((image) => ({ ...image, kind: 'new' }))
    const cover = [...hero, ...saved, ...picked].find((picture) => picture.key === coverKey.value) ?? [...hero, ...saved, ...picked][0] ?? null

    /* A replaced cover lands behind the saved pictures on the server, so the preview puts it there too. */
    return { cover, further: [...saved, ...hero, ...picked].filter((picture) => picture !== cover) }
})
const cover = computed(() => pictures.value.cover)
const further = computed(() => pictures.value.further)
const room = computed(() => props.production.maxImages + 1 - (cover.value ? 1 : 0) - further.value.length)

const overlayButton = 'flex size-6 items-center justify-center rounded-md bg-surface/90 text-text-muted opacity-0 transition-opacity hover:text-text group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-accent-600 [@media(hover:none)]:opacity-100'

const dirty = computed(() => form.value.title !== (props.production.title ?? '')
    || form.value.description !== (props.production.description ?? '')
    || JSON.stringify([...grantedIds.value].sort()) !== JSON.stringify([...grantedIdsOf(props.production, props.reductions)].sort())
    || !heroKept.value
    || (cover.value !== null && cover.value.kind !== 'hero')
    || newImages.value.length > 0
    || removedImageIds.value.length > 0)

watch(() => props.production, (production) => {
    form.value = { title: production.title ?? '', description: production.description ?? '' }
    grantedIds.value = grantedIdsOf(production, props.reductions)
    heroKept.value = true
    removedImageIds.value = []
    newImages.value.forEach((image) => URL.revokeObjectURL(image.url))
    newImages.value = []
    coverKey.value = null
})

function addFiles(files) {
    const added = files.filter((file) => file.type.startsWith('image/')).slice(0, room.value)
    newImages.value = [...newImages.value, ...added.map((file) => {
        const url = URL.createObjectURL(file)
        return { key: url, file, url }
    })]
}

function pickFiles(changeEvent) {
    addFiles(Array.from(changeEvent.target.files ?? []))
    changeEvent.target.value = ''
}

function dropFiles(dropEvent) {
    dragging.value = false
    addFiles(Array.from(dropEvent.dataTransfer?.files ?? []))
}

function removePicture(picture) {
    if (picture.kind === 'hero') heroKept.value = false
    if (picture.kind === 'saved') removedImageIds.value.push(picture.id)
    if (picture.kind === 'new') {
        URL.revokeObjectURL(picture.url)
        newImages.value = newImages.value.filter((image) => image.key !== picture.key)
    }
    if (coverKey.value === picture.key) coverKey.value = null
}

/* The project's key visual, fetched from this installation and sent like an upload. */
async function useKeyVisual() {
    const response = await fetch(props.production.fallback.keyVisualUrl)
    const blob = await response.blob()
    addFiles([new File([blob], props.production.fallback.keyVisualUrl.split('/').pop(), { type: blob.type })])
}

async function save() {
    saving.value = true
    error.value = ''
    const body = new FormData()
    body.append('title', form.value.title)
    body.append('description', form.value.description)
    body.append('reduction_type_ids', JSON.stringify(grantedIds.value))
    /* A new cover moves the saved one into the further pictures unless it was removed. */
    if (props.production.heroUrl && !heroKept.value) body.append('remove_hero', '1')
    if (cover.value?.kind === 'saved') body.append('cover_image_id', cover.value.id)
    if (cover.value?.kind === 'new') body.append('hero', cover.value.file)
    newImages.value.filter((image) => image.key !== cover.value?.key).forEach((image) => body.append('images[]', image.file))
    removedImageIds.value.forEach((id) => body.append('remove_image_ids[]', id))
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
