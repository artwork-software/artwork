<template>
    <ShiftSettingsHeader :title="$t('Day Services')">
        <!-- Actions -->
        <template #actions>
            <button class="ui-button-add inline-flex items-center gap-2" @click="openNew">
                <IconPlus class="size-5" stroke-width="1" />
                {{ $t('New Day Service') }}
            </button>
        </template>

        <!-- Liste -->
        <div class="mt-5">
            <div
                v-if="dayServices?.length"
                class="grid grid-cols-1 gap-3"
            >
                <div
                    v-for="ds in dayServices"
                    :key="ds.id ?? ds.name"
                    class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-3 shadow-xs transition hover:shadow-sm"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <!-- Icon Badge mit Farb-Tint -->
                        <span
                            class="inline-flex size-9 items-center justify-center rounded border"
                            :style="{ backgroundColor: ds.hex_color + '50', borderColor: ds.hex_color + '70', color: ds.hex_color }"
                            :title="ds.hex_color"
                        >
                          <PropertyIcon :name="ds.icon" stroke-width="1.5" class="size-5" />
                        </span>

                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-900">
                                {{ ds.name }}
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <span
                                    class="inline-flex size-2.5 rounded-full"
                                    :style="{ backgroundColor: ds.hex_color }"
                                />
                                <code class="text-[11px] text-gray-500">{{ ds.hex_color }}</code>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-x-2">
                            <IconEdit class="h-6 w-6 cursor-pointer flex items-center" @click="editDayService(ds)" />
                            <IconTrash class="h-6 w-6 cursor-pointer flex items-center text-red-500" @click="openDeleteDayServiceModal(ds)" />
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-300"
                            @click="editDayService(ds)"
                        >
                            <IconEdit class="size-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-10 text-center">
                <div class="mx-auto max-w-md">
                    <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-white text-gray-400 shadow-inner">
                        <IconPlus class="size-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800">
                        {{ $t('No day services yet') }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $t('Create your first day service to get started.') }}
                    </p>
                    <button
                        class="ui-button-add mt-4 inline-flex items-center gap-2"
                        @click="openNew"
                    >
                        <IconPlus class="size-5" stroke-width="1" />
                        {{ $t('New Day Service') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <AddEditDayServiceModal
            v-if="showAddEditDayServiceModal"
            :dayServiceToEdit="dayServiceToEdit"
            @closed="closeModal"
        />

        <ConfirmDeleteModal :title="confirmDeleteTitle" :description="confirmDeleteDescription" @closed="closedDeleteDayServiceModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </ShiftSettingsHeader>

</template>

<script setup lang="ts">
import { ref } from 'vue'
import ShiftSettingsHeader from '@/Pages/Settings/Components/ShiftSettingsHeader.vue'
import AddEditDayServiceModal from '@/Pages/Settings/Components/AddEditDayServiceModal.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import { IconPlus, IconEdit, IconTrash } from '@tabler/icons-vue'
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

defineOptions({ name: 'DayServiceIndex' })

const props = defineProps<{
    dayServices: Array<{
        id?: number | string
        name: string
        icon?: unknown
        hex_color: string
    }>
}>()

const showAddEditDayServiceModal = ref(false)
const dayServiceToEdit = ref<typeof props.dayServices[number] | null>(null)

/**
 *  openConfirmDeleteModal: false,
 *             dayServiceToDelete: null,
 *             confirmDeleteTitle: '',
 *             confirmDeleteDescription: ''
 */

const openConfirmDeleteModal = ref(false)
const dayServiceToDelete = ref<typeof props.dayServices[number] | null>(null)
const confirmDeleteTitle = ref('')
const confirmDeleteDescription = ref('')

function openNew() {
    dayServiceToEdit.value = null
    showAddEditDayServiceModal.value = true
}

function editDayService(dayService: typeof props.dayServices[number]) {
    dayServiceToEdit.value = dayService
    showAddEditDayServiceModal.value = true
}

function closeModal() {
    showAddEditDayServiceModal.value = false
    dayServiceToEdit.value = null
}

/**
 * Erzeugt einen transparenten Farb-Tint für das Icon-Badge.
 * opacity 0..1 → mischt Hex mit Weiß.
 */
function hexTint(hex: string, opacity = 0.1): string {
    // Fallbacks & Normalisierung
    if (!hex) return 'rgba(0,0,0,0.05)'
    const h = hex.replace('#', '')
    const bigint = parseInt(h.length === 3 ? h.split('').map(c => c + c).join('') : h, 16)
    const r = (bigint >> 16) & 255
    const g = (bigint >> 8) & 255
    const b = bigint & 255
    const r2 = Math.round(255 * opacity + r * (1 - opacity))
    const g2 = Math.round(255 * opacity + g * (1 - opacity))
    const b2 = Math.round(255 * opacity + b * (1 - opacity))
    return `rgb(${r2}, ${g2}, ${b2})`
}

function openDeleteDayServiceModal(dayService: typeof props.dayServices[number]) {
    dayServiceToDelete.value = dayService
    confirmDeleteTitle.value = 'Delete Day Service'
    confirmDeleteDescription.value = 'Are you sure you want to delete the selected day service? All assignments to this day service will be removed.'
    openConfirmDeleteModal.value = true
}
function closedDeleteDayServiceModal() {
    openConfirmDeleteModal.value = false
    dayServiceToDelete.value = null
}
function submitDelete() {
    if (!dayServiceToDelete.value?.id) return
    // @ts-ignore
    window.$inertia.delete(route('day-service.destroy', dayServiceToDelete.value.id), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            closedDeleteDayServiceModal()
        }
    })

}
</script>

<style scoped>
/* sanftere Shadow-Variante */
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
