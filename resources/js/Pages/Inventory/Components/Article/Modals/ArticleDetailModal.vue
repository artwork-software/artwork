<template>
    <ArtworkBaseModal
        :title="article.name ?? ''"
        :description="articleSubtitle"
        modal-size="sm:max-w-6xl"
        z-index="150"
        @close="$emit('close')"
    >
        <!-- Bearbeiten/Löschen: zusätzlich Tag-Berechtigungen -->
        <template v-if="showButtonForEditAndDelete && (canEditArticle || canDeleteArticle)" #headerActions>
            <button
                v-if="canEditArticle"
                type="button"
                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md bg-white/8 text-text-inverse transition-colors duration-150 motion-reduce:transition-none hover:bg-white/16"
                :aria-label="$t('Edit article')"
                v-tooltip.bottom="{ value: $t('Edit article'), class: 'aw-tooltip' }"
                @click="openArticleEditModal"
            >
                <component :is="IconEdit" class="size-4" stroke-width="1.5" aria-hidden="true" />
            </button>
            <button
                v-if="canDeleteArticle"
                type="button"
                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md bg-white/8 text-danger-border transition-colors duration-150 motion-reduce:transition-none hover:bg-white/16"
                :aria-label="$t('Delete article')"
                v-tooltip.bottom="{ value: $t('Delete article'), class: 'aw-tooltip' }"
                @click="openDeleteConfirmation"
            >
                <component :is="IconTrash" class="size-4" stroke-width="1.5" aria-hidden="true" />
            </button>
        </template>

        <!-- Bilder -->
        <div v-if="hasImages" class="mb-5 flex justify-center">
            <Galleria
                v-if="displayCustom"
                v-model:activeIndex="activeIndex"
                v-model:visible="displayCustom"
                :value="article.images"
                :responsiveOptions="responsiveOptions"
                :numVisible="7"
                :pt="{ mask: { onClick: onMaskClick } }"
                containerStyle="max-width: 850px"
                :circular="true"
                :fullScreen="true"
                :showItemNavigators="true"
                :showThumbnails="false"
            >
                <template #item="slotProps">
                    <img
                        :src="'/storage/' + slotProps.item.image"
                        :alt="slotProps.item.alt"
                        style="width: 100%; max-height: 75vh; object-fit: contain; display: block"
                        @error="(e) => (e.target.src = usePage().props.big_logo)"
                    />
                </template>
                <template #thumbnail="slotProps">
                    <img
                        :src="'/storage/' + (slotProps.item.thumbnail || slotProps.item.image)"
                        :alt="slotProps.item.alt"
                        style="display: block"
                        @error="(e) => (e.target.src = usePage().props.big_logo)"
                        class="w-20 max-w-20"
                    />
                </template>
            </Galleria>

            <div class="grid max-w-[400px] grid-cols-12 gap-4">
                <button
                    v-for="(image, index) of article.images"
                    :key="image.id ?? index"
                    type="button"
                    class="group relative col-span-4 cursor-pointer rounded-lg border border-border shadow transition duration-200 ease-in-out hover:shadow-lg"
                    :aria-label="$t('Open image')"
                    @click="imageClick(index)"
                >
                    <span class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-surface-inverse/50 opacity-0 transition duration-200 ease-in-out group-hover:opacity-100 group-focus-visible:opacity-100">
                        <component :is="IconWindowMaximize" class="size-5 text-text-inverse" aria-hidden="true" />
                    </span>
                    <img
                        :src="'/storage/' + (image.thumbnail || image.image)"
                        alt=""
                        class="rounded-lg"
                        loading="lazy"
                        decoding="async"
                    />
                </button>
            </div>
        </div>

        <!-- Tags und Beschreibung -->
        <div class="w-full px-2">
            <div v-if="hasTags" class="flex flex-wrap gap-1.5">
                <span
                    v-for="tag in article.tags"
                    :key="tag.id"
                    class="inline-flex max-w-[130px] items-center gap-1 rounded-full border bg-surface-sunken px-2 py-0.5 text-[10px] font-medium"
                    :style="tagStyle(tag)"
                >
                    <span
                        class="inline-block size-2 shrink-0 rounded-full border border-white/70"
                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                    />
                    <span class="truncate">{{ tag.name }}</span>
                </span>
            </div>

            <div
                v-if="article.description"
                class="mt-4 text-sm whitespace-pre-line text-text-muted font-lexend"
            >{{ article.description }}</div>
        </div>

        <!-- einfache Artikel (nicht detailliert) -->
        <section v-if="!article.is_detailed_quantity" class="mt-2 px-2">
            <Disclosure v-slot="{ open }">
                <DisclosureButton class="w-full border-b border-border-subtle">
                    <span class="flex items-center justify-between py-4 pr-2">
                        <span class="flex items-center gap-x-2 text-sm font-bold text-text font-lexend">
                            {{ $t('Total quantity') }}
                            <component
                                :is="IconChevronDown"
                                :class="[open ? 'rotate-180' : '']"
                                class="size-5 text-text-subtle"
                                aria-hidden="true"
                            />
                        </span>
                        <span
                            class="pl-2 text-sm font-lexend"
                            :class="totalQuantity === 0 ? 'text-danger' : 'text-accent-600'"
                        >
                            {{ formatQuantity(totalQuantity) }}
                        </span>
                    </span>
                </DisclosureButton>
                <DisclosurePanel class="relative pt-2 pb-2 pl-4 text-sm text-text-subtle">
                    <template v-for="status in article.status_values" :key="status.id">
                        <div v-if="status.id !== 5" class="border-b border-border-subtle">
                            <div class="flex items-center justify-between py-4 pr-2">
                                <div class="absolute top-0 left-0 h-[85%] w-px bg-border"></div>
                                <div class="flex items-center">
                                    <div class="-ml-4 h-px w-5 bg-border" />
                                    <span class="ml-2 text-sm font-bold text-text font-lexend">
                                        {{ status.name }}
                                    </span>
                                </div>
                                <span
                                    class="pl-2 text-sm font-lexend"
                                    :class="status.pivot?.value === 0 ? 'text-danger' : 'text-accent-600'"
                                >
                                    {{ formatQuantity(status.pivot?.value) }}
                                </span>
                            </div>
                        </div>
                    </template>
                </DisclosurePanel>
            </Disclosure>

            <dl v-if="mergedProperties.length > 0" class="divide-y divide-border-subtle">
                <div
                    v-for="property in mergedProperties"
                    :key="property.id"
                    class="flex items-center justify-between py-4 pr-2"
                >
                    <dt class="text-sm font-bold text-text font-lexend">
                        {{ property.name }}
                    </dt>
                    <dd class="text-sm text-accent-600 font-lexend">
                        <a
                            v-if="property.type === 'manufacturer' && canViewCrm && article.manufacturer?.id"
                            :href="route('crm.contacts.show', article.manufacturer.id)"
                            class="cursor-pointer underline hover:text-accent-700"
                            @click.prevent="router.visit(route('crm.contacts.show', article.manufacturer.id))"
                        >
                            {{ formatProperty(article, property) }}
                        </a>
                        <a
                            v-else-if="property.type === 'file' && property.pivot?.value"
                            :href="route('inventory-management.articles.property-file.download', { path: property.pivot.value })"
                            class="cursor-pointer underline hover:text-accent-700"
                        >
                            {{ fileName(property.pivot.value) }}
                        </a>
                        <span v-else>{{ formatProperty(article, property) }}</span>
                    </dd>
                </div>
            </dl>
            <BaseAlertComponent
                v-else
                class="mt-4"
                type="info"
                message="No properties were specified for this article"
                use-translation
            />
        </section>

        <!-- detaillierte Artikel -->
        <section
            v-if="article.is_detailed_quantity"
            class="mx-2 mt-8 mb-2 border-t-2 border-border-subtle pt-4"
        >
            <div class="mb-4 flex justify-between border-b-2 border-dashed border-border py-3">
                <div class="font-semibold text-text font-lexend">
                    {{ $t('Detailed Articles') }}
                </div>
                <div class="flex">
                    <h3 class="text-sm font-bold text-text font-lexend">
                        {{ $t('Full Quantity') }}
                    </h3>
                    <p
                        class="pl-2 text-sm font-lexend"
                        :class="totalQuantity === 0 ? 'text-danger' : 'text-accent-600'"
                    >
                        {{ formatQuantity(totalQuantity) }}
                    </p>
                </div>
            </div>

            <div class="pb-10">
                <Disclosure
                    v-for="detailedArticle in article.detailed_article_quantities"
                    :key="detailedArticle.id"
                    v-slot="{ open }"
                    as="div"
                    class="mb-2"
                    :class="open ? 'rounded-lg shadow-sm' : 'rounded-xl shadow-lg'"
                    :style="{ backgroundColor: (detailedArticle.status?.color || '#6b7280') + '15' }"
                >
                    <h3>
                        <DisclosureButton
                            class="group flex w-full items-center justify-between px-4 py-3 hover:bg-accent-600/10"
                            :class="open ? 'rounded-t-lg' : 'rounded-lg'"
                        >
                            <span class="flex flex-col items-start text-sm font-bold text-text font-lexend">
                                <template v-if="showInventoryNumberAsName && detailedArticle.inventory_number">
                                    <span>{{ formatInventoryNumber(detailedArticle.inventory_number) }}</span>
                                    <span class="text-xs font-normal text-text-subtle">
                                        {{ detailedArticle.name }}
                                    </span>
                                </template>
                                <template v-else>
                                    <span>{{ detailedArticle.name }}</span>
                                    <span
                                        v-if="detailedArticle.inventory_number"
                                        class="font-mono text-xs font-normal text-text-subtle"
                                    >
                                        {{ formatInventoryNumber(detailedArticle.inventory_number) }}
                                    </span>
                                </template>
                            </span>
                            <span class="ml-6 flex items-center gap-x-3">
                                <span
                                    class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium font-lexend"
                                    :style="{
                                        backgroundColor: (detailedArticle.status?.color || '#6b7280') + '33',
                                        borderColor: (detailedArticle.status?.color || '#6b7280') + '66',
                                        color: detailedArticle.status?.color || '#6b7280'
                                    }"
                                >
                                    {{ detailedArticle.status?.name || $t('Unknown Status') }}
                                    ·
                                    {{ $t('Quantity') }}: {{ formatQuantity(detailedArticle.quantity) }}
                                </span>
                                <component
                                    :is="open ? IconChevronUp : IconChevronDown"
                                    class="block size-6"
                                    :class="open ? 'text-accent-600 group-hover:text-accent-700' : 'text-text-subtle'"
                                    aria-hidden="true"
                                />
                            </span>
                        </DisclosureButton>
                    </h3>
                    <DisclosurePanel as="div" class="rounded-b-xl bg-surface p-4 shadow-lg">
                        <div v-if="detailedArticle.description" class="border-b border-border-subtle pb-2">
                            <div class="text-sm font-extralight whitespace-pre-line text-text-muted italic font-lexend">{{ detailedArticle.description }}</div>
                        </div>

                        <dl class="border-b border-border-subtle">
                            <div class="flex items-center justify-between py-4">
                                <dt class="text-sm font-bold text-text font-lexend">
                                    {{ $t('Quantity') }}
                                </dt>
                                <dd class="text-sm text-accent-600 font-lexend">
                                    {{ formatQuantity(detailedArticle.quantity) }}
                                </dd>
                            </div>
                        </dl>
                        <dl
                            v-if="getMergedDetailedProperties(detailedArticle).length > 0"
                            class="divide-y divide-border-subtle"
                        >
                            <div
                                v-for="property in getMergedDetailedProperties(detailedArticle)"
                                :key="property.id"
                                class="flex items-center justify-between py-4"
                            >
                                <dt class="text-sm font-bold text-text font-lexend">
                                    {{ property.name }}
                                </dt>
                                <dd class="text-sm text-accent-600 font-lexend">
                                    <a
                                        v-if="property.type === 'manufacturer' && canViewCrm && detailedArticle.manufacturer?.id"
                                        :href="route('crm.contacts.show', detailedArticle.manufacturer.id)"
                                        class="cursor-pointer underline hover:text-accent-700"
                                        @click.prevent="router.visit(route('crm.contacts.show', detailedArticle.manufacturer.id))"
                                    >
                                        {{ formatProperty(detailedArticle, property) }}
                                    </a>
                                    <a
                                        v-else-if="property.type === 'file' && property.pivot?.value"
                                        :href="route('inventory-management.articles.property-file.download', { path: property.pivot.value })"
                                        class="cursor-pointer underline hover:text-accent-700"
                                    >
                                        {{ fileName(property.pivot.value) }}
                                    </a>
                                    <span v-else>{{ formatProperty(detailedArticle, property) }}</span>
                                </dd>
                            </div>
                        </dl>
                        <BaseAlertComponent
                            v-else
                            class="mt-4"
                            type="info"
                            message="No properties were specified for this article"
                            use-translation
                        />
                    </DisclosurePanel>
                </Disclosure>
            </div>
        </section>

        <ConfirmDeleteModal
            v-if="showConfirmDelete"
            :title="$t('Delete article')"
            :description="deleteDescription"
            :loading="deleting"
            z-index="999999"
            @closed="showConfirmDelete = false"
            @delete="confirmDelete"
        />
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useTranslation } from '@/Composeables/Translation.js'
import { ref, computed } from 'vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { can, is } from 'laravel-permission-to-vuejs'
import { usePermission } from '@/Composeables/Permission.js'
import { isCheckboxChecked } from '@/Helper/CheckboxValue.js'
import Galleria from 'primevue/galleria'
import {
    IconChevronDown,
    IconChevronUp,
    IconEdit,
    IconTrash,
    IconWindowMaximize,
} from '@tabler/icons-vue'

const $t = useTranslation()

const showInventoryNumberAsName = computed(() => usePage().props.inventoryShowInventoryNumberAsName ?? false)
const inventoryNumberPrefix = computed(() => usePage().props.inventoryNumberPrefix ?? '')
const formatInventoryNumber = (num) => num ? inventoryNumberPrefix.value + num : ''

const props = defineProps({
    article: {
        type: Object,
        required: true,
    },
    showButtonForEditAndDelete: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close', 'openArticleEditModal'])

const hasImages = computed(() => Array.isArray(props.article.images) && props.article.images.length > 0)

// Unterzeile im Kopfband: Inventarnummer und Kategoriepfad
const articleSubtitle = computed(() => {
    const categoryPath = [props.article.category?.name, props.article.sub_category?.name]
        .filter(Boolean)
        .join(' > ')
    return [formatInventoryNumber(props.article.inventory_number), categoryPath]
        .filter(Boolean)
        .join(' · ')
})

const totalQuantity = computed(() => props.article.total_quantity || props.article.quantity)

// Ref 1.41: apply the global property order (`order`) so the display reflects the
// drag & drop order from the properties settings, with id as a stable fallback.
const byGlobalOrder = (a, b) => ((a?.order ?? 0) - (b?.order ?? 0)) || ((a?.id ?? 0) - (b?.id ?? 0))

// Merged properties for simple articles (includes new category/subcategory properties)
const mergedProperties = computed(() => {
    const articleProps = props.article.properties || []
    const categoryProps = props.article.category?.properties || []
    const subCategoryProps = props.article.sub_category?.properties || []

    const allCatProps = [...categoryProps, ...subCategoryProps]
    const articlePropIds = new Set(articleProps.map(p => p.id))

    // Add new category properties (with empty value)
    const newProps = allCatProps.filter(cp => !articlePropIds.has(cp.id))
        .map(p => ({ ...p, pivot: { value: '' } }))

    return [...articleProps, ...newProps].sort(byGlobalOrder)
})

// Merged properties for detailed articles
const getMergedDetailedProperties = (detailedArticle) => {
    const daProps = detailedArticle.properties || []
    const categoryProps = props.article.category?.properties || []
    const subCategoryProps = props.article.sub_category?.properties || []

    const allCatProps = [...categoryProps, ...subCategoryProps]
    const daPropIds = new Set(daProps.map(p => p.id))

    const newProps = allCatProps.filter(cp => !daPropIds.has(cp.id))
        .map(p => ({ ...p, pivot: { value: '' } }))

    return [...daProps, ...newProps].sort(byGlobalOrder)
}

const showConfirmDelete = ref(false)
const activeIndex = ref(0)
const displayCustom = ref(false)

const responsiveOptions = ref([
    {
        breakpoint: '1024px',
        numVisible: 5,
    },
    {
        breakpoint: '768px',
        numVisible: 3,
    },
    {
        breakpoint: '560px',
        numVisible: 1,
    },
])

const imageClick = (index) => {
    activeIndex.value = index
    displayCustom.value = true
}

// Künftige Ausgaben behalten den Artikel auch im Papierkorb – darauf vor dem Löschen hinweisen.
const futureIssueCount = ref(0)
const deleteDescription = computed(() => futureIssueCount.value > 0
    ? $t('This article is reserved in {0} upcoming material issue(s). It stays part of these issues, but no longer counts as available. Delete anyway?', [futureIssueCount.value])
    : $t('Are you sure you want to delete this article?'))

const openDeleteConfirmation = async () => {
    futureIssueCount.value = 0
    try {
        const {data} = await axios.get(route('articles.future-issues', props.article.id))
        futureIssueCount.value = Number(data?.count ?? 0)
    } catch (error) {
        // Ohne Zahl trotzdem löschen lassen; der Hinweis ist eine Zusatzinfo
    }
    showConfirmDelete.value = true
}

const deleting = ref(false)

const confirmDelete = () => {
    if (deleting.value) {
        return
    }
    deleting.value = true
    router.delete(route('articles.destroy', props.article.id), {
        onSuccess: () => {
            emit('close')
        },
        onError: () => {
            showConfirmDelete.value = false
        },
        onFinish: () => {
            deleting.value = false
        },
    })
}

const openArticleEditModal = () => {
    emit('openArticleEditModal', props.article)
}

const fileName = (path) => (typeof path === 'string' ? path.split('/').pop() : '')

const formatProperty = (article, property) => {
    if (property.type === 'room') {
        return article.room?.name === 'Room not found' ? $t('No room set') : article.room?.name
    }

    if (property.type === 'manufacturer') {
        return article.manufacturer?.name === 'Manufacturer not found'
            ? $t('No manufacturer set')
            : article.manufacturer?.name
    }

    if (property.type === 'date') {
        const v = property.pivot.value
        if (!v) return $t('No date set')
        let d = new Date(v)
        if (isNaN(d.getTime()) && /^\d{4}-\d{2}-\d{2}$/.test(v)) {
            d = new Date(`${v}T00:00:00`)
        }
        return isNaN(d.getTime()) ? v : d.toLocaleDateString()
    }

    if (property.type === 'time') {
        const v = property.pivot.value
        if (!v) return $t('No time set')

        if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(v)) {
            const parts = v.split(':')
            const h = String(parts[0]).padStart(2, '0')
            const m = String(parts[1]).padStart(2, '0')
            return `${h}:${m}`
        }

        const d = new Date(v)
        if (!isNaN(d.getTime())) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }

        return v
    }

    if (property.type === 'datetime' || property.type === 'datetime-local') {
        const v = property.pivot.value
        if (!v) return $t('No date set')
        const d = new Date(v)
        return isNaN(d.getTime()) ? v : d.toLocaleString()
    }

    if (property.type === 'year') {
        const v = property.pivot.value
        return v === null || v === '' ? $t('No year set') : String(v)
    }

    if (property.type === 'checkbox') {
        return isCheckboxChecked(property.pivot.value) ? $t('Yes') : $t('No')
    }

    const value = property.pivot?.value
    if (value === null || value === undefined || value === '') return '-'

    return value
}

const formatQuantity = (quantity) => {
    if (quantity === null || quantity === undefined || quantity === '') return '-'
    if (quantity === 0) return $t('Out of stock')
    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

const onMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        displayCustom.value = false
    }
}

/**
 * Tag-Anzeige und Tag-Berechtigungen
 */
const page = usePage()
const { hasAdminRole, can: canPermission } = usePermission(page.props)

const canViewCrm = computed(() => canPermission('can view crm') || hasAdminRole())

const currentUser = computed(() => page.props.auth?.user ?? null)

const currentDepartmentIds = computed(() =>
    (page.props.auth?.user?.department_ids || [])
)

const hasTags = computed(
    () => Array.isArray(props.article.tags) && props.article.tags.length > 0
)

const tagStyle = (tag) => {
    const baseColor = tag?.color || '#4f46e5'
    return {
        backgroundColor: baseColor + '10',
        borderColor: baseColor + '30',
        color: baseColor,
    }
}

// Prüft, ob currentUser für EINEN Tag Berechtigung hat
const userHasPermissionForTag = (tag) => {
    // Wenn keine Restriktion: Tag ist für alle mit Modul-Rechten nutzbar
    if (!tag?.has_restricted_permissions) return true

    const userId = currentUser.value?.id
    if (!userId) return false

    const tagUserIds = (tag.allowed_users || []).map((u) => u.id)
    const tagDeptIds = (tag.allowed_departments || []).map((d) => d.id)

    const hasUser = tagUserIds.includes(userId)
    const hasDept =
        currentDepartmentIds.value?.length &&
        currentDepartmentIds.value.some((id) => tagDeptIds.includes(id))

    return !!(hasUser || hasDept)
}

// Für Artikel: wenn es eingeschränkte Tags gibt, muss der User für alle diese Tags berechtigt sein
const canAccessByTags = computed(() => {
    if (hasAdminRole()) return true

    const tags = props.article.tags || []
    if (!tags.length) return true

    const restricted = tags.filter((t) => t.has_restricted_permissions)
    if (!restricted.length) return true

    return restricted.every((t) => userHasPermissionForTag(t))
})

// Basisrechte aus Laravel Permission:
const baseCanEdit = computed(
    () => can('inventory.create_edit') || is('artwork admin')
)
const baseCanDelete = computed(
    () => can('inventory.delete') || is('artwork admin')
)

// Finale Rechte:
const canEditArticle = computed(() => baseCanEdit.value && canAccessByTags.value)
const canDeleteArticle = computed(
    () => baseCanDelete.value && canAccessByTags.value
)
</script>

<style scoped>
</style>
