<template>
    <ArtworkBaseModal @close="$emit('close')"  :title="$t('Select print layout')"
                      :description="$t('Select a print layout to print the project')">


        <div v-if="printLayouts.length > 0">
            <Listbox as="div" v-model="selectedLayout">
                <ListboxLabel class="block text-sm/6 font-medium text-gray-900">{{ $t('Select print layout')}}</ListboxLabel>
                <div class="relative mt-2">
                    <ListboxButton class="menu-button">
                        <span class="col-start-1 row-start-1 truncate pr-6 !xsDark">{{ selectedLayout?.name }}</span>
                        <component :is="IconChevronDown" class="h-5 w-5 text-gray-500 col-start-1 row-start-1 -mt-1 -mr-1" aria-hidden="true" />
                    </ListboxButton>

                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                            <ListboxOption as="template" v-for="layout in printLayouts" :key="layout.id" :value="layout" v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-artwork-buttons-create text-white outline-none' : 'text-gray-900', 'relative select-none py-2 pl-3 pr-9 cursor-pointer']">
                                    <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ layout.name }}</span>

                                    <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                        <component :is="IconCheck" class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </div>
            </Listbox>

            <div class="flex items-center justify-center mt-10">
                <BaseUIButton
                    @click="openPrintDialog"
                    :label="$t('Print')"
                    is-add-button
                />
            </div>
        </div>
        <div v-else>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="flex">
                    <div class="shrink-0">
                        <component :is="IconExclamationCircle" class="size-5 text-red-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            {{ $t('No print layouts available') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {IconCheck, IconChevronDown, IconExclamationCircle} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    printLayouts: {
        type: Object,
        required: true,
    },
})

const selectedLayout = ref(props.printLayouts[0]);

const emit = defineEmits(['close']);

const openPrintDialog = () => {
    const printWindow = window.open(route('project-print-layout.show', {
        project: props.project.id,
        projectPrintLayout: selectedLayout.value.id,
    }), '_blank', 'width=1000,height=1000');

    if (!printWindow) {
        alert("Popup-Blocker aktiv? Bitte deaktivieren!");
        return;
    }

    // Warte darauf, dass das Fenster vollständig geladen ist
    printWindow.onload = () => {
        const checkReady = setInterval(() => {
            if (printWindow.document.readyState === 'complete') {
                clearInterval(checkReady);
                setTimeout(() => {
                    printWindow.print();
                }, 500); // Sicherheitsverzögerung für das Rendering
            }
        }, 100);
    };

    // Fenster nach dem Drucken oder Abbrechen schließen
    printWindow.onafterprint = () => {
        printWindow.close();
    };

    // Falls der Benutzer das Fenster vorher schließt
    printWindow.onbeforeunload = () => {
        printWindow.close();
    };
}

</script>

<style scoped>

</style>
