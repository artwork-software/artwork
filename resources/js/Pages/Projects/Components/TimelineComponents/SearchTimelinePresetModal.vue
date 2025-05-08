<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="$t('Search timeline preset')"
                :description="$t('Search for a timeline preset to import.')"
            />
        </div>

        <div class="relative">
            <div class="my-auto w-full relative">
                <BaseInput
                    id="userSearch"
                    v-model="searchTimeline"
                    label="Search for timeline preset"
                    class="w-full"
                    @focus="searchTimeline = ''"/>
            </div>
            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="timelinePresets.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-artwork-navigation-background shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                    <div class="border-gray-200">
                        <div v-for="(timeline, index) in timelinePresets" :key="index" class="flex items-center cursor-pointer">
                            <div >
                                <div class="flex-1 text-sm py-4" @click="selectTimeline(timeline)">
                                    <p class="font-bold px-4 flex text-white items-center hover:border-l-4 hover:border-l-success">
                                        <span class="ml-2 truncate">{{ timeline.name }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <div v-if="selectedTimeline">
            <div class="flex items-center justify-between mt-4">
                <div>
                    {{ selectedTimeline.name }}
                </div>
                <div>
                    <IconX class="h-5 w-5 cursor-pointer" @click="selectedTimeline = null"/>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center">
            <FormButton
                :text="$t('Import timeline preset')"
                @click="importTimelinePreset"
                class="mt-4"
            />
        </div>

    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {ref, watch} from "vue";
import {IconX} from "@tabler/icons-vue";
import {router, useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})


const emit = defineEmits([
    'close'
])

const searchTimeline = ref('')
const timelinePresets = ref([])
const selectedTimeline = ref(null)

const selectTimeline = (timeline) => {
    selectedTimeline.value = timeline
    searchTimeline.value = ''
}

const importTimelinePreset = () => {
    router.post(route('timeline-preset.import', {
        shiftPresetTimeline: selectedTimeline.value.id,
        event: props.event.id
    }), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emit('close');
        }
    })
}

// watch for searchTimeline changes
watch(searchTimeline, async (value) => {
    if (value.length > 0) {
        const response = await axios.get(route('timeline-preset.search', {search: value}))
        timelinePresets.value = response.data
    } else {
        timelinePresets.value = []
    }
})

</script>

<style scoped>

</style>