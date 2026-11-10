<template>
    <ShiftHeader>
        <div class="ml-5">
            <ShiftPresetTabs />
        </div>
        <div class="w-full flex my-auto items-center justify-between mb-3 mt-10">
            <div class="flex items-center justify-between">
                <div class="flex ml-5 mr-4 font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                    {{$t('All shift templates')}}
                </div>
                <button
                    @click="showAddShiftPresetModal = true"
                    type="button"
                    class="rounded-full bg-accent-600 p-1 mr-1 text-white shadow-sm hover:bg-accent-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-700"
                    :aria-label="$t('Create a new shift template')"
                    v-tooltip.bottom="{ value: $t('Create a new shift template'), class: 'aw-tooltip' }"
                >
                    <IconPlus class="h-4 w-4" aria-hidden="true" />
                </button>
                <div v-if="this.$page.props.show_hints" class="flex mt-1">
                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                    <span class="ml-1 my-auto">{{$t('Create a new shift template')}}</span>
                </div>
            </div>
            <div class="mr-3">
                <button
                    v-if="!showSearchbar"
                    type="button"
                    class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 hover:bg-surface-hover hover:text-text motion-reduce:transition-none"
                    :aria-label="$t('Search')"
                    @click="showSearchbar = true"
                >
                    <IconSearch class="size-5" stroke-width="1.5" aria-hidden="true"/>
                </button>
                <div v-else class="flex w-64 items-center gap-x-2">
                    <BaseInput
                        id="shiftPresetSearch"
                        v-model="preset_search"
                        label="Search"
                        :show-label="false"
                        :placeholder="$t('Search for templates')"
                        autocomplete="off"
                        autofocus
                    />
                    <button
                        type="button"
                        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 hover:bg-surface-hover hover:text-text motion-reduce:transition-none"
                        :aria-label="$t('Close')"
                        @click="closeSearchbar"
                    >
                        <IconX class="size-5" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
            </div>
        </div>
        <div class="w-full px-5 min-h-screen">
            <div v-if="filteredShiftPresets.length > 0">
                <div v-for="preset in filteredShiftPresets" :key="preset.id">
                    <SingleShiftPreset :preset="preset"
                                       :crafts="crafts"
                                       :event_types="event_types"
                                       :shift-qualifications="shiftQualifications"
                    />
                </div>
            </div>
            <EmptyState
                v-else
                icon="IconTemplate"
                :title="$t('No shift templates available.')"
            />
        </div>
        <AddShiftPresetModal :event_types="event_types" v-if="showAddShiftPresetModal" @closed="showAddShiftPresetModal = false" />
    </ShiftHeader>
</template>
<script>
import {IconPlus, IconSearch, IconX} from "@tabler/icons-vue";
import {defineComponent} from 'vue'
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import SingleShiftPreset from "@/Pages/Shifts/Components/SingleShiftPreset.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AddShiftPresetModal from "@/Pages/Projects/Components/AddShiftPresetModal.vue";
import ShiftPresetTabs from "@/Pages/Shifts/Components/ShiftPresetTabs.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import EmptyState from "@/Artwork/Feedback/EmptyState.vue";

export default defineComponent({
    name: "ShiftPresets",
    components: {
        ShiftPresetTabs,
        BaseInput,
        EmptyState,
        IconX,
        IconSearch,
        AddShiftPresetModal,
        SvgCollection,
        IconPlus,
        SingleShiftPreset,
        ShiftHeader,
    },
    props: [
        'shiftPresets',
        'crafts',
        'event_types',
        'shiftQualifications'
    ],
    data(){
        return {
            selectedFilter: {
                name: this.$t('All templates'),
                id: 0
            },
            showAddShiftPresetModal: false,
            showSearchbar: false,
            preset_search: ''
        }
    },
    computed: {
        filteredShiftPresets(){
            if (this.selectedFilter.id === 0) {
                if (this.preset_search.length > 0) {
                    return this.shiftPresets.filter(
                        preset => preset.name.toLowerCase().includes(this.preset_search.toLowerCase())
                    )
                } else {
                    return this.shiftPresets
                }
            } else {
                if (this.preset_search.length > 0) {
                    return this.shiftPresets.filter(
                        preset => preset.name.toLowerCase().includes(this.preset_search.toLowerCase()) &&
                            preset.event_type.id === this.selectedFilter.id
                    )
                } else {
                    return this.shiftPresets.filter(preset => preset.event_type.id === this.selectedFilter.id)
                }
            }
        }
    },
    methods: {
        closeSearchbar(){
            this.showSearchbar = false
            this.preset_search = ''
        }
    }
})
</script>
