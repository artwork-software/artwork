<template>
    <Popover v-slot="{ open }" class="relative w-full">
        <Float auto-placement portal :offset="{ mainAxis: hasNoOffset ? 5 : -10, crossAxis: hasNoOffset ? 25 : 75}">
            <PopoverButton class="w-full focus:ring-0 ring-0 rounded-lg">
                <div @dragover="onDragOver"
                     @drop="onDrop"
                     @dragleave="dragLeave"
                     class="p-5 w-full rounded-lg border-2 cursor-pointer border-dashed flex items-center justify-center hover:border-artwork-buttons-create duration-200 ease-in-out group"
                     :class="[isDragging ? 'border-artwork-buttons-create' : 'border-gray-300', dropOver ? 'bg-artwork-buttons-create/10' : '']">
                    <div class="flex flex-col items-center justify-between">
                        <component :is="IconCircleDashedPlus" class="h-8 w-8 group-hover:text-artwork-buttons-create duration-200 ease-in-out" :class="[isDragging ? 'text-artwork-buttons-create' : 'text-gray-300']" stroke-width="1.5" />
                        <p v-if="isDragging" class="xsDark mt-1">{{ $t('Drop here') }}</p>
                    </div>
                </div>
            </PopoverButton>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-1 opacity-0">
                <PopoverPanel class="absolute left-10 z-10 mt-3 w-screen max-w-sm -translate-x-1/2 transform px-4 sm:px-0 lg:max-w-3xl bg-white shadow-lg border rounded-lg">
                    <div class="p-6">
                        <div class="h-80 overflow-scroll">
                            <div class="grid gird-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="component in components">
                                    <div class="flex p-3 rounded-lg border hover:cursor-pointer h-16 w-full items-center gap-2" @click="addComponent(component)">
                                        <div class="flex items-center justify-center">
                                            <ComponentIcons :type="component.type" />
                                        </div>
                                        <div class="text-sm font-bold">
                                            <div class="w-full">
                                                {{ $t(component.name) }}
                                                <div class="text-[10px] text-gray-500 font-light" v-if="component.data.height">
                                                    {{ component.data.height }} Pixel <span v-if="component.data.showLine === true">| {{ $t('Show a separator line')}}</span>
                                                </div>
                                                <div class="text-[10px] text-gray-500 font-light" v-if="component.data.title_size">
                                                    {{ component.data.title_size }} Pixel
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </PopoverPanel>
            </transition>
        </Float>
    </Popover>

    <SideNotification v-if="showError" type="error" :text="dropFeedback" @close="showError = false" />
</template>

<script setup>
import {EventListenerForDragging} from "@/Composeables/EventListenerForDragging.js";
import {onMounted, onUnmounted, ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import {Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import {Float} from "@headlessui-float/vue";
import {IconCircleDashedPlus} from "@tabler/icons-vue";


const { isDragging, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();

const props = defineProps({
    row: {
        type: Number,
        required: false,
        default: 1,
    },
    col: {
        type: Number,
        required: false,
        default: 1,
    },
    type: {
        type: String,
        required: true,
        default: 'header',
    },
    columnSize: {
        type: Number,
        required: true,
        default: 1,
    },
    projectPrintLayout: {
        type: Object,
        required: true,
    },
    components: {
        type: Object,
        required: true,
        default: [],
    },
    hasNoOffset: {
        type: Boolean,
        required: false,
        default: false,
    }
})

const dropOver = ref(false);
const showError = ref(false);
const dropFeedback = ref('');

const onDragOver = (event) => {
    dropOver.value = true;
    event.preventDefault();
};

const dragLeave = () => {
    dropOver.value = false;
    isDragging.value = true;
};

const onDrop = (event) => {
    event.preventDefault();
    // add check if JSON is valid
    if (!event.dataTransfer?.getData('application/json')) {
        dropOver.value = false;
        showError.value = true;
    }

    const data = JSON?.parse(event.dataTransfer?.getData('application/json'));


    console.log('onDrop', data);

    if (props.type === 'body' && props.columnSize > 1 && !data.sidebar_enabled) {
        dropFeedback.value = "Fehler: Im Hauptteil-Bereich mit einer Spaltenanzahl größer als 1 sind keine speziellen Komponenten erlaubt.";
        showError.value = true;
    }

    if ((props.type === 'header' && !data.sidebar_enabled) || (props.type === 'footer' && !data.sidebar_enabled)) {
        dropFeedback.value = "Fehler: Im \"Kopf\"- oder \"Fuß\"-Bereich sind keine speziellen Komponenten erlaubt.";
        showError.value = true;
    }

    if (data.drop_type !== 'component') {
        dropOver.value = false;
        showError.value = true;
    }

    if (showError.value) {
        dropOver.value = false;
        return;
    }

    router.post(route('project.print.layout.add.component', {projectPrintLayout: props.projectPrintLayout.id}), {
        component_id: data.id,
        row: props.row,
        col: props.col,
        type: props.type,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dropOver.value = false;
        }
    });

};

const addComponent = (component) => {
    if (props.type === 'body' && props.columnSize > 1 && !component.sidebar_enabled) {
        dropFeedback.value = "Fehler: Im Hauptteil-Bereich mit einer Spaltenanzahl größer als 1 sind keine speziellen Komponenten erlaubt.";
        showError.value = true;
    }

    if ((props.type === 'header' && !component.sidebar_enabled) || (props.type === 'footer' && !component.sidebar_enabled)) {
        dropFeedback.value = "Fehler: Im \"Kopf\"- oder \"Fuß\"-Bereich sind keine speziellen Komponenten erlaubt.";
        showError.value = true;
    }

    if (showError.value) {
        dropOver.value = false;
        return;
    }

    router.post(route('project.print.layout.add.component', {projectPrintLayout: props.projectPrintLayout.id}), {
        component_id: component.id,
        row: props.row,
        col: props.col,
        type: props.type,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dropOver.value = false;
        }
    });
};


onMounted(() => {
    const listeners = addEventListenerForDraggingStart();

    onUnmounted(() => {
        removeEventListenerForDraggingStart(listeners);
    });
});

// watch on showError
watch(showError, (value) => {
    if (value) {
        setTimeout(() => {
            showError.value = false;
        }, 2000);
    }
});
</script>

<style scoped>

</style>