<template>
    <div class="my-3 ml-6">
        <div>
            <div class="flex items-center gap-x-1 cursor-pointer"
                 @click="this.projectGroupOpened = !this.projectGroupOpened">
                <h4 class="font-bold">{{ $t('Project-related Sage data') }}</h4>
                <ChevronUpIcon v-if="this.projectGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.projectsGroup?.length > 0 ? 'h-28' : '']" v-if="this.projectGroupOpened">
                <div v-show="sageNotAssigned.projectsGroup.length > 0">
                    <div class="flex flex-row font-bold">
                        <div class="w-28">
                            {{ $t('KTO') }}
                        </div>
                        <div class="w-28">
                            {{ $t('KST') }}
                        </div>
                        <div class="w-64 truncate">
                            {{ $t('Booking text') }}
                        </div>
                        <div class="w-52 text-right">
                            {{ $t('Booking amount') }}
                        </div>
                        <div class="w-40 text-right">
                            {{ $t('Booking date') }}
                        </div>
                    </div>
                    <div v-for="(project) in sageNotAssigned.projectsGroup">
                        <SageDataDragElement :sageData="project"
                                             @remove-sage-not-assigned-data="this.removeSageNotAssignedData"
                        />
                    </div>
                </div>
                <div v-if="!sageNotAssigned.projectsGroup.length" class="italic font-light text-sm mb-3">
                    <p>Keine Daten vorhanden</p>
                </div>
            </div>
        </div>
        <div v-if="this.$can('can view and delete sage100-api-data')">
            <div class="flex items-center gap-x-1 cursor-pointer"
                 @click="this.globalGroupOpened = !this.globalGroupOpened">
                <h4 class="font-bold">{{ $t('Global Sage data') }}</h4>
                <ChevronUpIcon v-if="this.globalGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.globalGroup.length > 0 ? 'h-28' : '']" v-show="this.globalGroupOpened">
                <div v-show="sageNotAssigned.globalGroup.length > 0">
                    <div class="flex flex-row font-bold">
                        <div class="w-28">
                            {{ $t('KTO') }}
                        </div>
                        <div class="w-28">
                            {{ $t('KST') }}
                        </div>
                        <div class="w-64 truncate">
                            {{ $t('Booking text') }}
                        </div>
                        <div class="w-52 text-right">
                            {{ $t('Booking amount') }}
                        </div>
                        <div class="w-40 text-right">
                            {{ $t('Booking date') }}
                        </div>
                        <div class="w-28 text-right">
                            {{ $t('Cost bearer') }}
                        </div>
                    </div>
                    <div v-for="(global) in sageNotAssigned.globalGroup">
                        <SageDataDragElement :sageData="global" type="global"
                                             @remove-sage-not-assigned-data="this.removeSageNotAssignedData"
                        />
                    </div>
                </div>
                <div v-if="!sageNotAssigned.globalGroup.length" class="italic font-light text-sm mb-3">
                    <p>{{ $t('No data available') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import SageDataDragElement from "@/Pages/Projects/Components/SageDataDragElement.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "SageNotAssignedData",
    components: {SageDataDragElement, ChevronDownIcon, ChevronUpIcon},
    props: ['sageNotAssigned'],
    emits: ['removeSageNotAssignedData'],
    mixins: [Permissions],
    data() {
        return {
            globalGroupOpened: false,
            projectGroupOpened: false
        }
    },
    methods: {
        removeSageNotAssignedData(sageNotAssignedData) {
            this.$emit('removeSageNotAssignedData', sageNotAssignedData);
        }
    }
}
</script>
