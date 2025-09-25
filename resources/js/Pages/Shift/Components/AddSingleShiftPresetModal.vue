<script lang="ts" setup>
import { ref, reactive, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

// Artwork / UI
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseModalButton from '@/Artwork/Buttons/ArtworkBaseModalButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import SelectComponent from '@/Components/Inputs/SelectComponent.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'

// i18n
const { t: $t } = useI18n()

type Qualification = {
    id: number
    name: string
    icon?: string
    available?: boolean
    pivot?: { single_shift_preset_id: number; shift_qualification_id: number; quantity: number }
    quantity?: number
}

const props = defineProps<{
    edit?: boolean
    preset?: any
    crafts: Array<any>
    shiftQualifications: Array<Qualification>
}>()

const emit = defineEmits(['closed', 'saved'])
const open = ref(true)
const validationMessages = reactive<{ errors: Record<string, string[]> }>({ errors: {} })

/**
 * Normalisiert die Qualifikationen aus dem Preset:
 * - Unterstützt sowohl `shifts_qualifications` (dein JSON) als auch `shift_qualifications`
 * - Zieht quantity bevorzugt aus `pivot.quantity`, sonst aus `quantity`, sonst 0
 */
function normalizePresetQualifications(preset: any): Array<{ id: number; quantity: number }> {
    const raw =
        (preset?.shifts_qualifications as any[]) ??
        (preset?.shift_qualifications as any[]) ??
        []

    return raw.map((q: any) => {
        if (typeof q === 'number') return { id: q, quantity: 0 }
        if (q && typeof q === 'object') {
            const id = typeof q.id === 'number' ? q.id : Number(q.id)
            const qty =
                (q.pivot && typeof q.pivot.quantity === 'number' && q.pivot.quantity) ||
                (typeof q.quantity === 'number' && q.quantity) ||
                0
            return { id, quantity: qty }
        }
        return { id: Number(q), quantity: 0 }
    })
}

const selectedCraft = ref(props.crafts.find(c => c.id === props.preset?.craft_id) || null)

const form = useForm({
    name: props.preset?.name ?? '',
    start_time: props.preset?.start_time ?? '',
    end_time: props.preset?.end_time ?? '',
    break_duration: props.preset?.break_duration ?? 0,
    craft_id: props.preset?.craft_id ?? '',
    description: props.preset?.description ?? '',
    shift_qualifications: normalizePresetQualifications(props.preset),
})

// Falls das Preset (z. B. asynchron) später kommt/ändert: nachziehen
watch(
    () => props.preset,
    (val) => {
        if (!val) return
        form.name = val?.name ?? ''
        form.start_time = val?.start_time ?? ''
        form.end_time = val?.end_time ?? ''
        form.break_duration = val?.break_duration ?? 0
        form.craft_id = val?.craft_id ?? ''
        form.description = val?.description ?? ''
        form.shift_qualifications = normalizePresetQualifications(val)
        selectedCraft.value = props.crafts.find(c => c.id === val?.craft_id) || null
    },
    { immediate: false }
)

const selectedCount = computed(() => form.shift_qualifications.length)

function isQualificationSelected(id: number) {
    return form.shift_qualifications.some((q: any) => q.id === id)
}

function toggleQualification(id: number) {
    if (isQualificationSelected(id)) {
        form.shift_qualifications = form.shift_qualifications.filter((q: any) => q.id !== id)
    } else {
        form.shift_qualifications.push({ id, quantity: 0 })
    }
}

function getQualificationQuantity(id: number) {
    const q = form.shift_qualifications.find((q: any) => q.id === id)
    return q ? q.quantity : 0
}

function setQualificationQuantity(id: number, value: number) {
    const v = Number.isFinite(value) ? Math.max(0, Math.floor(value)) : 0
    const q = form.shift_qualifications.find((q: any) => q.id === id)
    if (q) q.quantity = v
}

function incQuantity(id: number) {
    const q = form.shift_qualifications.find((q: any) => q.id === id)
    if (q) q.quantity = Math.max(0, (q.quantity ?? 0) + 1)
}

function decQuantity(id: number) {
    const q = form.shift_qualifications.find((q: any) => q.id === id)
    if (q) q.quantity = Math.max(0, (q.quantity ?? 0) - 1)
}

function handleSubmit() {
    form.craft_id = selectedCraft.value ? selectedCraft.value.id : null

    const routeName = props.edit ? 'single-shift-presets.update' : 'single-shift-presets.store'
    const method = props.edit ? 'put' : 'post'
    // @ts-ignore – Inertia fügt dynamische Methoden an form an
    form[method](route(routeName, props.edit ? { id: props.preset.id } : {}), {
        onSuccess: () => {
            emit('saved')
            open.value = false
        },
        onError: (errors: any) => {
            validationMessages.errors = errors
        },
    })
}

function closeModal() {
    open.value = false
    emit('closed')
}
</script>

<template>
    <ArtworkBaseModal :open="open" @close="closeModal" :title="props.edit ? $t('Edit shift preset') : $t('Create shift preset')" :description="$t('Define names, times, trades, and required qualifications.')">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Basisdaten -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <BaseInput v-model="form.name" :label="$t('Name')" id="name" required class="w-full" />

                    <div class="grid grid-cols-2 gap-4">
                        <BaseInput v-model="form.start_time" :label="$t('Start-Time')" type="time" id="start_time" required />
                        <BaseInput v-model="form.end_time" :label="$t('End-Time')" type="time" id="end_time" required />
                    </div>

                    <BaseInput
                        v-model="form.break_duration"
                        :label="$t('Break duration (minutes)')"
                        id="break_duration"
                        type="number"
                        min="0"
                        class="w-full"
                    />

                    <SelectComponent
                        id="addShiftCraftSelectComponent"
                        :label="$t('Craft') + '*'"
                        v-model="selectedCraft"
                        :options="crafts"
                        selected-property-to-display="name"
                        :getter-for-options-to-display="(option) => option.name + ' ' + (option.abbreviation ?? '')"
                    />
                </div>

                <div class="mt-4">
                    <BaseTextarea v-model="form.description" :label="$t('Description')" id="description" class="w-full" />
                </div>
            </div>

            <!-- Qualifikationen -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">
                        {{ $t('Qualifications') }}
                    </label>
                    <div class="text-xs text-gray-500">
                        {{ $t('Click to select, adjust quantity on the right') }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <div
                        v-for="q in props.shiftQualifications"
                        :key="q.id"
                        class="flex items-center justify-between rounded-xl border p-3 transition-shadow"
                        :class="isQualificationSelected(q.id) ? 'border-blue-500/50 shadow-sm' : 'border-gray-200 hover:shadow-sm'"
                    >
                        <!-- Toggle + Label -->
                        <button
                            type="button"
                            class="flex items-center gap-3"
                            @click="toggleQualification(q.id)"
                        >
              <span
                  class="inline-flex h-6 w-10 items-center rounded-full transition-colors"
                  :class="isQualificationSelected(q.id) ? 'bg-blue-600' : 'bg-gray-200'"
              >
                <span
                    class="h-5 w-5 translate-x-0 rounded-full bg-white shadow transition-transform"
                    :class="isQualificationSelected(q.id) ? 'translate-x-4' : 'translate-x-1'"
                />
              </span>
                            <span class="text-sm font-medium text-gray-900">
                {{ q.name }}
              </span>
                            <span
                                v-if="q.available === false"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600"
                            >
                {{ $t('nicht verfügbar') }}
              </span>
                        </button>

                        <!-- Menge -->
                        <div v-if="isQualificationSelected(q.id)" class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-2 py-1 text-sm font-semibold hover:bg-gray-50"
                                @click="decQuantity(q.id)"
                                aria-label="decrease"
                            >−</button>

                            <input
                                :value="getQualificationQuantity(q.id)"
                                @input="setQualificationQuantity(q.id, Number(($event.target as HTMLInputElement).value))"
                                type="number"
                                min="0"
                                class="w-20 rounded-lg border border-gray-200 px-2 py-1 text-center text-sm"
                                :placeholder="$t('Count')"
                            />

                            <button
                                type="button"
                                class="rounded-lg border border-gray-200 px-2 py-1 text-sm font-semibold hover:bg-gray-50"
                                @click="incQuantity(q.id)"
                                aria-label="increase"
                            >+</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fehler -->
            <AlertComponent
                v-if="Object.keys(validationMessages.errors).length"
                :errors="validationMessages.errors"
            />

            <!-- Aktionen -->
            <div class="flex justify-end gap-2">
                <ArtworkBaseModalButton type="button" @click="closeModal" class="bg-gray-100 text-gray-800">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>
                <ArtworkBaseModalButton type="submit" class="bg-blue-600 text-white">
                    {{ props.edit ? $t('Save') : $t('Create') }}
                </ArtworkBaseModalButton>
            </div>
        </form>

        <!-- Debug optional:
        <pre class="mt-4 text-xs text-gray-500">
          {{ form }}
        </pre>
        -->
    </ArtworkBaseModal>
</template>

<style scoped>
/* Tailwind 4 Utilities werden projektweit geladen – hier keine Custom-CSS nötig */
</style>
