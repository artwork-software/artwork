<template>
    <!-- Sticky / Glass Header -->
    <!-- bg-white als solider Fallback: ohne Hintergrund scheinen beim Scrollen die
         darunterliegenden Event-Zeilen durch die (transparente) Headerzeile durch.
         backdrop-blur wird nicht in allen Browsern unterstützt → deshalb voll deckend. -->
    <div
        class="sticky top-0 z-20 print:static w-full min-w-max bg-white border-b border-border-subtle/70"
    >
        <div
            class="px-6 sm:px-4 mt-2 mb-3"
        >
            <div class="flex items-center gap-3 sm:gap-4 py-2 sm:py-3 text-[11px] sm:text-xs text-text-muted print:text-sm/5 font-semibold text-text">
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
            <span class="uppercase tracking-wider font-semibold text-text-muted ">
              {{ $t('Event Status') }}
            </span>
                    </div>

                    <!-- Type -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(2)">
            <span class="uppercase tracking-wider font-semibold text-text-muted ">
              {{ $t('Event type') }}
            </span>
                    </div>

                    <!-- Name -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(3)">
            <span class="uppercase tracking-wider font-semibold text-text-muted ">
              {{ $t('Event name') }}
            </span>
                    </div>

                    <!-- Room -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(4)">
            <span class="uppercase tracking-wider font-semibold text-text-muted ">
              {{ $t('Room') }}
            </span>
                    </div>

                    <!-- Day -->
                    <div class="shrink-0 min-w-0 print:col-span-2" :style="getColumnSize(5)">
            <div class="flex items-center gap-2">
              <span class="uppercase tracking-wider font-semibold text-text-muted ">
                {{ $t('Start date') }}
              </span>
              <!-- Toggle to show End date column -->
              <Switch
                  v-model="localShowEndDate"
                  @change="$emit('update:showEndDate', localShowEndDate)"
                  :class="[
                    localShowEndDate ? 'bg-accent-700' : 'bg-border-subtle',
                    'relative inline-flex h-4 w-8 flex-shrink-0 cursor-pointer rounded-full border border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-offset-0 focus:ring-accent-600/50'
                  ]"
                  :aria-label="$t('Toggle end date column')"
              >
                <span
                    aria-hidden="true"
                    :class="[
                      localShowEndDate ? 'translate-x-4' : 'translate-x-0',
                      'pointer-events-none inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                />
              </Switch>
            </div>
                    </div>

                    <!-- Start time -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(6)">
                        <div>
                          <span class="uppercase tracking-wider font-semibold text-text-muted ">{{ $t('Start time') }}</span>
                        </div>
                    </div>

                    <!-- End date (optional) -->
                    <div v-if="localShowEndDate" class="shrink-0 min-w-0 print:col-span-2" :style="getColumnSize(5)">
                        <span class="uppercase tracking-wider font-semibold text-text-muted ">{{ $t('End date') }}</span>
                    </div>

                    <!-- End time -->
                    <div class="shrink-0 min-w-0" :style="getColumnSize(6)">
                        <div class="flex items-center gap-2">
                            <span class="uppercase tracking-wider font-semibold text-text-muted ">{{ $t('End time') }}</span>
                            <ToolTipComponent
                                :icon="IconExclamationCircle"
                                icon-size="h-5 w-5"
                                direction="bottom"
                                :tooltip-text="$t('If the start and end times are identical or the end time is before the start time, the end date is set to the next day; if no time is specified, the event is categorised as a full day.')"
                                tooltipCssClass="w-64"
                            />
                        </div>
                    </div>

                    <!-- Einlass (nur wenn instanzweit aktiv) -->
                    <div v-if="usePage().props.event_admission_module" class="shrink-0 min-w-0" :style="getColumnSize(6)">
                        <span class="uppercase tracking-wider font-semibold text-text-muted ">{{ $t('Admission') }}</span>
                    </div>

                    <!-- Spacer in Breite der Aktionsspalte der Zeilen: Header und Zeilen sind
                         getrennte Flex-Container — nur mit identischem Fixanteil verteilt
                         flex-grow den Restplatz in beiden gleich und die Spalten bleiben bündig. -->
                    <div v-if="showActionsSpacer" :class="isInModal ? 'w-16' : 'w-56'" class="shrink-0" aria-hidden="true"></div>
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
import {IconExclamationCircle} from "@tabler/icons-vue";
import {getBulkColumnSize as getColumnSize} from "@/Pages/Projects/Components/BulkComponents/bulkColumnSizing.js";

// Emit Event
const emit = defineEmits(['update:modelValue', 'update:showEndDate']);

// Props für v-model Unterstützung
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true
    },
    showEndDate: {
        type: Boolean,
        required: false,
        default: false
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
    },
    showActionsSpacer: {
        type: Boolean,
        required: false,
        default: false
    }
});

// Spalten-Maße kommen aus bulkColumnSizing.js (geteilt mit BulkSingleEvent —
// Header und Zeilen sind getrennte Flex-Container und müssen identisch rechnen).

// Lokaler Wert, um den Zustand des Switches zu halten
const localValue = ref(props.modelValue);
const localShowEndDate = ref(props.showEndDate);

// Watcher um Änderungen an modelValue zu berücksichtigen
watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});
watch(() => props.showEndDate, (v) => {
    localShowEndDate.value = v;
});

// Wenn lokale Werte geändert werden, Events emittieren
watch(localValue, (newValue) => {
    emit('update:modelValue', newValue);
});
watch(localShowEndDate, (v) => emit('update:showEndDate', v));
</script>

<style scoped>

</style>
