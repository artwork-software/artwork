<template>
    <Popover class="relative">
        <PopoverButton id="iconSelectorButton" class="size-10 flex items-center justify-center border border-gray-300 rounded-full">
            <ToolTipComponent
                :icon="selectedIcon"
                icon-size="size-7"
                :tooltip-text="$t('Select an icon')"
                direction="bottom"
            />
        </PopoverButton>

        <transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-1 opacity-0">
            <PopoverPanel ref="iconSelectorPanel" class="absolute z-10 mt-3 w-screen max-w-sm transform px-4 sm:px-0 lg:max-w-3xl bg-white border rounded-lg shadow-md">
                <div class="px-6 py-3 mb-4 flex items-center justify-between mt-2">
                    <div>
                        <h3 class="headline3"> Icons</h3>
                        <p class="text-xs xsLight mt-1" v-if="!loading">
                            {{ $t('Select an icon from {0} different icons.', [computedIcons?.length ?? 0]) }}
                        </p>
                    </div>
                    <div>
                        <div class="grid grid-cols-1 relative">
                            <input v-model="searchInput" type="text" class="col-start-1 row-start-1 block w-full rounded-md bg-white py-1.5 pl-10 pr-3 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:pl-9 sm:text-sm/6" :placeholder="$t('Search')" />
                            <component :is="TablerIcons.IconSearch" class="pointer-events-none col-start-1 row-start-1 ml-3 size-5 self-center text-gray-400 sm:size-4" aria-hidden="true" />
                            <div class="absolute right-2 flex items-center h-full" @click="searchInput = ''" v-if="searchInput.length > 0">
                                <component :is="TablerIcons.IconX" class="h-5 w-5 text-gray-400" aria-hidden="true" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full p-6 max-h-96 overflow-auto">
                    <!-- Ladeanimation -->
                    <div v-if="loading" class="flex justify-center items-center h-32">
                        <svg class="animate-spin h-8 w-8 text-artwork-buttons-create" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </div>
                    <!-- Icons anzeigen, wenn geladen -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div v-for="icon in computedIcons" :key="icon.name">
                            <div @click="selectIcon(icon)" :class="selectedIcon === icon.name ? 'bg-green-300/20' : ''" class="flex flex-col items-center p-3 gap-y-3 hover:bg-gray-50 rounded-lg cursor-pointer">
                                <component v-if="icon.icon" :is="icon.icon" class="h-8 w-8 text-black" stroke-width="1.5"/>
                                <p class="text-center headline4 !text-xs">{{ icon.display_name }}</p>
                            </div>
                        </div>
                    </div>


                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>
import {ref, computed, onMounted} from "vue";
import { Popover, PopoverButton, PopoverPanel } from "@headlessui/vue";
import * as TablerIcons from "@tabler/icons-vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

const icons = ref([]);
const searchInput = ref('');
const loading = ref(true);

const props = defineProps({
    currentIcon: {
        type: String,
        required: false,
    }
});

const selectedIcon = ref(TablerIcons[props.currentIcon] ?? TablerIcons["IconPhotoCircle"]);

const emit = defineEmits(['update:modelValue']);

const computedIcons = computed(() => {
    return icons.value.filter(icon => icon.display_name.toLowerCase().includes(searchInput.value.toLowerCase()));
});

const convertToDisplayName = (iconName) => {
    return iconName
        .replace(/^Icon/, '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b(\d+)/g, match => match + 'D')
        .trim();
}

const loadIconsBatchwise = () => {
    const iconEntries = Object.entries(TablerIcons)
        .filter(([key, value]) =>
            typeof value === 'function' || // Falls das Icon als Funktion exportiert wird
            (typeof value === 'object' && (value?.render || value?.setup)) // Falls es eine Vue 3-Komponente ist
        );

    let index = 0;
    const batchSize = 50;

    function loadBatch() {
        if (index < iconEntries.length) {
            icons.value.push(
                ...iconEntries.slice(index, index + batchSize).map(([key, value]) => ({
                    name: key,
                    icon: value, // Speichert die echte Komponente
                    display_name: convertToDisplayName(key),
                }))
            );
            index += batchSize;

            if ('requestIdleCallback' in window) {
                requestIdleCallback(loadBatch);
            } else {
                requestAnimationFrame(loadBatch);
            }
        } else {
            loading.value = false;
        }
    }

    loadBatch();
};



onMounted(() => {
    loading.value = true; // Setze loading aktiv
    loadIconsBatchwise();
});


const selectIcon = (icon) => {
    selectedIcon.value = icon.name;
    //selectedIconComponent.value = markRaw(icon.icon); // Markiere als nicht-reaktiv

    // Emitte das ausgewählte Icon
    emit('update:modelValue', icon.name);

    // click button to close popover
    document.getElementById('iconSelectorButton').click();
};
</script>
