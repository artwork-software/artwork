<template>
    <!-- Sticky / Glass Header -->
    <div class="sticky top-0 z-30 print:static">
        <div
            class="rounded-2xl border border-zinc-200/80
             bg-white/70 backdrop-blur-xl
              px-6 sm:px-4 mt-5 mb-3"
        >
            <div class="flex items-center gap-3 sm:gap-4 py-2 sm:py-3 text-[11px] sm:text-xs text-zinc-600 print:xsDark">
                <!-- left spacer when multiEdit -->
                <div v-if="multiEdit" class="w-8 h-4 shrink-0"></div>

                <!-- columns -->
                <div class="flex items-center gap-3 sm:gap-4 w-full overflow-x-auto">
                    <!-- Status -->
                    <div
                        v-if="usePage().props.event_status_module"
                        class="shrink-0 min-w-0"
                        :style="getColumnSize(1)"
                    >
            <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
              {{ $t('Event Status') }}
            </span>
                    </div>

                    <!-- Type -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(2)">
            <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
              {{ $t('Event type') }}
            </span>
                    </div>

                    <!-- Name -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(3)">
            <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
              {{ $t('Event name') }}
            </span>
                    </div>

                    <!-- Room -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(4)">
            <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
              {{ $t('Room') }}
            </span>
                    </div>

                    <!-- Day -->
                    <div class="shrink-0 min-w-0 print:col-span-2" :style="getColumnSize(5)">
            <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
              {{ $t('Day') }}
            </span>
                    </div>

                    <!-- Period -->
                    <div class="shrink-0 min-w-0 col-span-1" :style="getColumnSize(6)">
                        <div v-if="isInModal" class="flex items-center gap-2">
                            <SwitchGroup as="div" class="flex items-center">
                                <Switch
                                    v-model="localValue"
                                    @change="$emit('update:modelValue', localValue)"
                                    :class="[
                    localValue ? 'bg-artwork-buttons-hover' : 'bg-zinc-200 dark:bg-zinc-800',
                    'relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-artwork-buttons-create/50'
                  ]"
                                >
                  <span
                      aria-hidden="true"
                      :class="[
                      localValue ? 'translate-x-5' : 'translate-x-0',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white dark:bg-zinc-100 shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                                </Switch>
                                <SwitchLabel as="span" class="ml-3 select-none uppercase tracking-wider font-semibold text-zinc-700 ">
                                    {{ $t('Period') }}
                                </SwitchLabel>
                            </SwitchGroup>
                        </div>

                        <div v-else class="flex items-center gap-2">
              <span class="uppercase tracking-wider font-semibold text-zinc-700 ">
                {{ $t('Period') }}
              </span>
                            <ToolTipComponent
                                icon="IconExclamationCircle"
                                icon-size="h-5 w-5"
                                direction="bottom"
                                :tooltip-text="$t('If the start and end times are identical or the end time is before the start time, the end date is set to the next day; if no time is specified, the event is categorised as a full day.')"
                                tooltipCssClass="w-64"
                            />
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>



<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {ref, watch} from "vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import {usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

// Emit Event
const emit = defineEmits(['update:modelValue']);

// Props für v-model Unterstützung
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true
    },
    isInModal: {
        type: Boolean,
        required: false,
        default: false
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
});

const getColumnSize = (column) => {
    return {
        minWidth: usePage().props.auth.user.bulk_column_size[column] + 'px',
        width: usePage().props.auth.user.bulk_column_size[column] + 'px',
        maxWidth: usePage().props.auth.user.bulk_column_size[column] + 'px'
    }
}

// Lokaler Wert, um den Zustand des Switches zu halten
const localValue = ref(props.modelValue);

// Watcher um Änderungen an modelValue zu berücksichtigen
watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});

// Wenn localValue geändert wird, das update:modelValue Event emittieren
watch(localValue, (newValue) => {
    emit('update:modelValue', newValue);
});
</script>

<style scoped>

</style>
