<template>
    <ArtworkBaseModal
        modal-size="max-w-7xl"
        :title="$t('Configure column width')"
        :description="$t('Configure the width of the columns in the bulk view.')"
        @close="$emit('close')"
    >
        <!-- Controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="col in columns"
                :key="col.index"
                class="rounded-2xl border border-zinc-200 bg-white/70 backdrop-blur p-4 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <label
                            class="text-sm font-medium text-zinc-900"
                            :for="`col-width-${col.index}`"
                        >
                            {{ $t('Column') }} {{ col.index }}
                        </label>
                        <p class="text-xs text-zinc-500">
                            {{ $t('Set the width in pixels') }}
                        </p>
                    </div>

                    <span
                        class="inline-flex items-center rounded-full border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-700"
                        :aria-live="'polite'"
                    >
            {{ columnSizeForm.bulk_column_size[col.index] }} {{ $t('px') }}
          </span>
                </div>

                <!-- Slider -->
                <div class="mt-4">
                    <input
                        class="w-full appearance-none cursor-pointer"
                        type="range"
                        :id="`col-width-${col.index}`"
                        :min="columnSizeMinMax[col.index].min"
                        :max="columnSizeMinMax[col.index].max"
                        step="1"
                        v-model.number="columnSizeForm.bulk_column_size[col.index]"
                        aria-valuemin="0"
                        :aria-valuenow="columnSizeForm.bulk_column_size[col.index]"
                        :aria-valuemax="columnSizeMinMax[col.index].max"
                    />
                    <!-- Slider track styling (webkit + moz) -->


                    <!-- Min/Max helper -->
                    <div class="mt-2 flex items-center justify-between text-[11px] text-zinc-500">
                        <span>{{ $t('Min') }}: {{ columnSizeMinMax[col.index].min }} {{ $t('px') }}</span>
                        <button
                            type="button"
                            class="rounded-full border border-zinc-200 px-2 py-0.5 hover:bg-zinc-50"
                            @click="setTo(col.index, suggested[col.index])"
                        >
                            {{ $t('Suggest') }}: {{ suggested[col.index] }} {{ $t('px') }}
                        </button>
                        <span>{{ $t('Max') }}: {{ columnSizeMinMax[col.index].max }} {{ $t('px') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="mt-6">
            <BasePageTitle :title="$t('Preview')" description="" />
            <div class="mt-4 overflow-x-auto">
                <div class="min-w-max">
                    <!-- Header preview -->
                    <div class="flex items-center gap-3 mb-2 text-[13px] text-zinc-600">
                        <div class="font-semibold" :style="getColumnSize(1)">{{ $t('Event Status') }}</div>
                        <div class="font-semibold" :style="getColumnSize(2)">{{ $t('Event type') }}</div>
                        <div class="font-semibold" :style="getColumnSize(3)">{{ $t('Event name') }}</div>
                        <div class="font-semibold" :style="getColumnSize(4)">{{ $t('Room') }}</div>
                        <div class="font-semibold" :style="getColumnSize(5)">{{ $t('Day') }}</div>
                        <div class="font-semibold" :style="getColumnSize(6)">{{ $t('Period') }}</div>
                    </div>

                    <!-- Size blocks -->
                    <div class="flex items-center gap-3">
                        <div
                            v-for="(val, idx) in columnSizeForm.bulk_column_size"
                            :key="`preview-${idx}`"
                            class="rounded-xl border-2 border-dashed border-zinc-200 bg-zinc-50"
                            :style="getColumnSize(idx)"
                        >
                            <div class="px-3 py-2 text-xs text-zinc-500 select-none">
                                {{ val }} {{ $t('px') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hint -->
            <p class="mt-3 text-xs text-zinc-500">
                {{ $t('These widths affect only the bulk view table. You can adjust them anytime.') }}
            </p>
        </div>

        <!-- Actions -->
        <form class="mt-6" @submit.prevent="submit">
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <BaseUIButton
                        type="button"
                        :label="$t('Reset to default')"
                        icon="IconRestore"
                        @click="setColumnSizeToDefault"
                    />
                    <BaseUIButton
                        type="button"
                        :label="$t('Cancel')"
                        icon="IconX"
                        is-delete-button
                        @click="$emit('close')"
                    />
                </div>
                <div>
                    <BaseUIButton
                        is-add-button
                        type="submit"
                        :label="$t('Save')"
                        icon="IconCircleCheck"
                    />
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'

defineEmits(['close'])

const columnSizeForm = useForm({
    bulk_column_size: { ...usePage().props.auth.user.bulk_column_size }
})

const columnSizeMinMax = {
    1: { min: 100, max: 450 },
    2: { min: 100, max: 450 },
    3: { min: 100, max: 450 },
    4: { min: 100, max: 450 },
    5: { min: 146, max: 450 },
    6: { min: 195, max: 450 }
}

// sinnvolle, harmonische Vorschläge (Mitte zwischen min/max bzw. Domain-Wissen)
const suggested = {
    1: 144,
    2: 160,
    3: 220,
    4: 144,
    5: 180,
    6: 250
}

const columns = computed(() =>
    Object.keys(columnSizeForm.bulk_column_size)
        .map(key => ({ index: Number(key) }))
        .sort((a, b) => a.index - b.index)
)

const getColumnSize = (colIndex) => {
    const px = columnSizeForm.bulk_column_size[colIndex]
    return {
        minWidth: px + 'px',
        width: px + 'px',
        maxWidth: px + 'px'
    }
}

const setTo = (colIndex, value) => {
    const { min, max } = columnSizeMinMax[colIndex]
    const clamped = Math.min(max, Math.max(min, value))
    columnSizeForm.bulk_column_size[colIndex] = clamped
}

const setColumnSizeToDefault = () => {
    columnSizeForm.bulk_column_size = {
        1: 144,
        2: 144,
        3: 144,
        4: 144,
        5: 144,
        6: 250
    }
    submit()
}

const submit = () => {
    columnSizeForm.patch(
        route('user.bulk-column-size.update', usePage().props.auth.user.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                // optional: Toast einblenden
                // useToast().success($t('Saved'))
                // close modal
                // Emit hier, damit der Parent den Refresh/Close steuert
                // (du hast das bereits so gemacht)
                // eslint-disable-next-line no-undef
                emit('close')
            }
        }
    )
}
</script>
<style>
input[type="range"]::-webkit-slider-runnable-track { height: 8px; border-radius: 9999px; background: #e5e7eb; }
input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; height: 18px; width: 18px; border-radius: 9999px; background: #2563eb; margin-top: -5px; box-shadow: 0 1px 2px rgba(0,0,0,.1); }
input[type="range"]::-moz-range-track { height: 8px; border-radius: 9999px; background: #e5e7eb; }
input[type="range"]::-moz-range-thumb { height: 18px; width: 18px; border-radius: 9999px; background: #2563eb; border: none; }
</style>
