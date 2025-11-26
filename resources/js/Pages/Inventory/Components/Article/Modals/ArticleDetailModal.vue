<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-6xl">
        <div class="">
            <!-- Product -->
            <div class="mb-5">
                <div class="card flex justify-center">
                    <Galleria
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
                                style="width: 100%; display: block"
                                @error="(e) => (e.target.src = usePage().props.big_logo)"
                            />
                        </template>
                        <template #thumbnail="slotProps">
                            <img
                                :src="'/storage/' + slotProps.item.image"
                                :alt="slotProps.item.alt"
                                style="display: block"
                                @error="(e) => (e.target.src = usePage().props.big_logo)"
                                class="w-20 max-w-20"
                            />
                        </template>
                    </Galleria>

                    <div
                        v-if="article.images"
                        class="grid grid-cols-12 gap-4"
                        style="max-width: 400px"
                    >
                        <div
                            v-for="(image, index) of article.images"
                            :key="index"
                            class="col-span-4 border border-gray-300 rounded-lg shadow relative hover:shadow-lg transition duration-200 ease-in-out group"
                        >
                            <!-- lupe icon -->
                            <div
                                class="absolute inset-0 bg-black/50 z-10 rounded-lg group-hover:opacity-100 opacity-0 cursor-pointer transition duration-200 ease-in-out"
                                @click="imageClick(index)"
                            >
                                <div class="flex items-center justify-center w-full h-full">
                                    <component :is="IconWindowMaximize" class="w-5 h-5 text-white" />
                                </div>
                            </div>
                            <img
                                :src="'/storage/' + image.image"
                                alt=""
                                style="cursor: pointer"
                                class="cursor-pointer rounded-lg"
                                @click="imageClick(index)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product info -->
            <div>
                <div class="px-4 sm:mt-16 sm:px-2 lg:mt-0 w-full">
                    <div class="w-full flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-3xl font-lexend font-bold tracking-tight text-primary line-clamp-3">
                                {{ article.name }}
                            </h1>
                            <div
                                v-if="article.category"
                                class="font-lexend text-xs text-secondary mt-0.5 font-semibold"
                            >
                                {{ article.category?.name }}
                                <span v-if="article.sub_category">
                                    > {{ article.sub_category?.name }}
                                </span>
                            </div>

                            <!-- 🔹 Tags unter Titel/Kategorie -->
                            <div
                                v-if="hasTags"
                                class="mt-2 flex flex-wrap gap-1.5"
                            >
                                <span
                                    v-for="tag in article.tags"
                                    :key="tag.id"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium border max-w-[130px] bg-gray-50"
                                    :style="tagStyle(tag)"
                                >
                                    <span
                                        class="inline-block h-2 w-2 rounded-full border border-white/70 shrink-0"
                                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                    />
                                    <span class="truncate">
                                        {{ tag.name }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- 🔒 Edit/Delete: zusätzlich Tag-Berechtigungen -->
                        <div
                            class="flex gap-x-2 px-2"
                            v-if="showButtonForEditAndDelete"
                        >
                            <component
                                :is="IconEdit"
                                class="w-5 h-5 rounded-full cursor-pointer hover:text-artwork-buttons-create duration-200 ease-in-out"
                                @click="openArticleEditModal"
                                v-if="canEditArticle"
                            />
                            <component
                                :is="IconTrash"
                                class="w-5 h-5 rounded-full cursor-pointer hover:text-red-500 duration-200 ease-in-out"
                                @click="showConfirmDelete = true"
                                v-if="canDeleteArticle"
                            />
                        </div>
                    </div>

                    <div class="flex w-full">
                        <div class="mt-4">
                            <div
                                class="space-y-6 text-sm text-secondary font-lexend"
                                v-html="article.description"
                            />
                        </div>
                    </div>
                </div>

                <!-- einfache Artikel (nicht detailliert) -->
                <section
                    aria-labelledby="details-heading"
                    class="mt-2 px-2"
                    v-if="!article.is_detailed_quantity"
                >
                    <div>
                        <Disclosure v-slot="{ open }">
                            <DisclosureButton class="w-full">
                                <div class="border-b border-gray-100">
                                    <div class="pr-2 py-4 flex items-center justify-between">
                                        <dt
                                            class="text-sm font-bold text-primary font-lexend flex items-center gap-x-2"
                                        >
                                            {{ $t('Total quantity') }}
                                            <component
                                                :is="IconChevronDown"
                                                :class="[open ? 'rotate-180' : '']"
                                                class="size-5 text-gray-400 group-hover:text-gray-500"
                                                aria-hidden="true"
                                            />
                                        </dt>
                                        <p
                                            class="font-lexend text-sm pl-2"
                                            :class="(article.total_quantity || article.quantity) === 0 ? 'text-error' : 'text-artwork-buttons-create'"
                                        >
                                            {{ formatQuantity(article.total_quantity || article.quantity) }}
                                        </p>
                                    </div>
                                </div>
                            </DisclosureButton>
                            <DisclosurePanel class="relative pl-4 pb-2 pt-2 text-sm text-gray-500">
                                <div
                                    v-for="status in article.status_values"
                                    :key="status.id"
                                    class=""
                                >
                                    <div
                                        class="border-b border-gray-100"
                                        v-if="status.id !== 5"
                                    >
                                        <div class="pr-2 py-4 flex items-center justify-between">
                                            <div class="absolute top-0 left-0 w-px h-[85%] bg-gray-300"></div>
                                            <div class="flex items-center">
                                                <div class="w-5 -ml-4 h-px bg-gray-300" />
                                                <dt
                                                    class="text-sm font-bold ml-2 text-primary font-lexend"
                                                >
                                                    {{ status.name }}
                                                </dt>
                                            </div>
                                            <p
                                                class="font-lexend text-sm pl-2"
                                                :class="status.pivot.value === 0 ? 'text-error' : 'text-artwork-buttons-create'"
                                            >
                                                {{ formatQuantity(status.pivot.value) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </DisclosurePanel>
                        </Disclosure>

                        <div>
                            <dl
                                class="divide-y divide-gray-100"
                                v-if="article.properties?.length > 0"
                            >
                                <div
                                    class="pr-2 py-4 flex items-center justify-between"
                                    v-for="property in article.properties"
                                    :key="property.id"
                                >
                                    <dt class="text-sm font-bold text-primary font-lexend">
                                        {{ property.name }}
                                    </dt>
                                    <dd class="font-lexend text-sm text-artwork-buttons-create">
                                        <span>{{ formatProperty(article, property) }}</span>
                                    </dd>
                                </div>
                            </dl>

                            <div v-else>
                                <div class="rounded-md bg-blue-50 p-4">
                                    <div class="flex">
                                        <div class="shrink-0">
                                            <component
                                                :is="IconAlertSquareRoundedFilled"
                                                class="size-5 text-blue-400"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-800">
                                                {{ $t('No properties were specified for this article') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- detaillierte Artikel -->
                <div class="bg-backgroundGray -mx-4">
                    <section
                        aria-labelledby="details-heading"
                        class="mt-8 mb-2 border-t-2 border-gray-100 pt-4 mx-6"
                        v-if="article.is_detailed_quantity"
                    >
                        <div class="flex justify-between mb-4 py-3 border-b-2 border-dashed">
                            <div class="font-lexend font-semibold text-primary">
                                {{ $t('Detailed Articles') }}
                            </div>
                            <div class="flex" v-if="article.is_detailed_quantity">
                                <div>
                                    <h3 class="text-sm font-bold text-primary font-lexend">
                                        {{ $t('Full Quantity') }}
                                    </h3>
                                </div>
                                <p
                                    class="font-lexend text-sm pl-2"
                                    :class="(article.total_quantity || article.quantity) === 0 ? 'text-error' : 'text-artwork-buttons-create'"
                                >
                                    {{ formatQuantity(article.total_quantity || article.quantity) }}
                                </p>
                            </div>
                        </div>

                        <div class="pb-10">
                            <Disclosure
                                as="div"
                                class="mb-2"
                                v-slot="{ open }"
                                v-for="detailedArticle in article.detailed_article_quantities"
                                :key="detailedArticle.id"
                                :class="[open ? 'shadow-sm rounded-lg' : 'rounded-xl shadow-lg ', '']"
                                :style="{ backgroundColor: (detailedArticle.status?.color || '#6b7280') + '15' }"
                            >
                                <h3>
                                    <DisclosureButton
                                        class="flex items-center group justify-between w-full px-4 py-3 hover:bg-artwork-buttons-create/10"
                                        :class="open ? 'rounded-t-lg' : 'rounded-lg'"
                                    >
                                        <span
                                            :class="[open ? 'text-sm font-bold' : ' text-sm font-bold', ' font-lexend text-primary']"
                                        >
                                            {{ detailedArticle.name }}
                                        </span>
                                        <span class="ml-6 flex items-center gap-x-3">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-lexend font-medium border"
                                                :style="{
                                                    backgroundColor: (detailedArticle.status?.color || '#6b7280') + '33',
                                                    borderColor: (detailedArticle.status?.color || '#6b7280') + '66',
                                                    color: detailedArticle.status?.color || '#6b7280'
                                                }"
                                            >
                                                {{ detailedArticle.status?.name || $t('Unknown Status') }}
                                                -
                                                {{ $t('Quantity') }}: {{ formatQuantity(detailedArticle.quantity) }}
                                            </span>
                                            <component
                                                :is="IconChevronDown"
                                                v-if="!open"
                                                class="block size-6 text-gray-400 group-hover:text-gray-500"
                                                aria-hidden="true"
                                            />
                                            <component
                                                :is="IconChevronUp"
                                                v-else
                                                class="block size-6 text-artwork-buttons-default group-hover:text-artwork-buttons-hover"
                                                aria-hidden="true"
                                            />
                                        </span>
                                    </DisclosureButton>
                                </h3>
                                <DisclosurePanel
                                    as="div"
                                    class="shadow-lg rounded-b-xl p-4 bg-white"
                                >
                                    <div class="border-b pb-2 border-gray-100">
                                        <div
                                            class="space-y-6 text-sm italic text-gray-500 font-lexend font-extralight"
                                            v-html="detailedArticle.description"
                                        />
                                    </div>

                                    <dl class="border-b border-gray-100">
                                        <div class="py-4 flex items-center justify-between">
                                            <dt class="text-sm font-bold text-primary font-lexend">
                                                {{ $t('Quantity') }}
                                            </dt>
                                            <dd class="font-lexend text-sm text-artwork-buttons-create">
                                                <span>{{ formatQuantity(detailedArticle.quantity) }}</span>
                                            </dd>
                                        </div>
                                    </dl>
                                    <dl
                                        class="divide-y divide-gray-100"
                                        v-if="detailedArticle.properties.length > 0"
                                    >
                                        <div
                                            class="py-4 flex items-center justify-between"
                                            v-for="property in detailedArticle.properties"
                                            :key="property.id"
                                        >
                                            <dt class="text-sm font-bold text-primary font-lexend">
                                                {{ property.name }}
                                            </dt>
                                            <dd class="font-lexend text-sm text-artwork-buttons-create">
                                                <span>{{ formatProperty(detailedArticle, property) }}</span>
                                            </dd>
                                        </div>
                                    </dl>
                                    <div v-else>
                                        <div class="rounded-md bg-red-50 p-4">
                                            <div class="flex">
                                                <div class="shrink-0">
                                                    <component
                                                        :is="IconAlertSquareRoundedFilled"
                                                        class="size-5 text-red-400"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-red-800">
                                                        {{
                                                            $t(
                                                                'No properties were specified for this article'
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <ConfirmDeleteModal
            :title="$t('Delete article')"
            :description="$t('Are you sure you want to delete this article?')"
            z-index="999999"
            @closed="showConfirmDelete = false"
            v-if="showConfirmDelete"
            @delete="confirmDelete"
        />
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/Modals/BaseModal.vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import { ref, computed } from 'vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { can, is } from 'laravel-permission-to-vuejs'
import Galleria from 'primevue/galleria'
import {
    IconAlertSquareRoundedFilled,
    IconChevronDown,
    IconChevronUp,
    IconEdit,
    IconTrash,
    IconWindowMaximize,
} from '@tabler/icons-vue'

const $t = useTranslation()

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

const confirmDelete = () => {
    router.delete(route('articles.destroy', props.article.id), {
        onSuccess: () => {
            emit('close')
        },
        onError: () => {
            showConfirmDelete.value = false
        },
    })
}

const openArticleEditModal = () => {
    emit('openArticleEditModal', props.article)
}

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

    if (property.type === 'datetime') {
        const v = property.pivot.value
        if (!v) return $t('No date set')
        const d = new Date(v)
        return isNaN(d.getTime()) ? v : d.toLocaleString()
    }

    if (property.type === 'checkbox') {
        return property.pivot.value ? $t('Yes') : $t('No')
    }

    if (property.pivot.value === null || property.pivot.value === '') return '-'

    return property.pivot.value
}

const formatQuantity = (quantity) => {
    if (quantity === 0) return $t('Out of stock')
    return quantity.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

const onMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        displayCustom.value = false
    }
}

/**
 * 🔹 Tag-Anzeige & -Berechtigungen
 */
const page = usePage()

const currentUser = computed(() => page.props.auth?.user ?? null)

// Wenn du Departments anders bekommst, hier anpassen:
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
