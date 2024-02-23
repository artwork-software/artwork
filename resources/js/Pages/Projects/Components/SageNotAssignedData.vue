<template>
    <div class="my-3 ml-6">
        <div>
            <div class="flex items-center gap-x-1 cursor-pointer" @click="sageNotAssigned.projectGroupOpened = !sageNotAssigned.projectGroupOpened">
                <h4 class="font-bold">{{ $t('Project-related Sage data') }}</h4>
                <ChevronUpIcon v-if="sageNotAssigned.projectGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else  class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.projectsGroup?.length > 0 ? 'h-28' : '']" v-if="sageNotAssigned.projectGroupOpened">
                <div v-if="sageNotAssigned.projectsGroup?.length > 0" class="flex flex-row font-bold">
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
                <div v-if="sageNotAssigned.projectsGroup?.length > 0" v-for="(project, index) in sageNotAssigned.projectsGroup">
                    <SageDataDragElement :sageData="project" />
                </div>
                <div v-else class="italic font-light text-sm mb-3">
                    <p>{{ $t('No data available') }}</p>
                </div>
            </div>
        </div>
        <div>
            <div class="flex items-center gap-x-1 cursor-pointer" @click="sageNotAssigned.globalGroupOpened = !sageNotAssigned.globalGroupOpened">
                <h4 class="font-bold">{{ $t('Global Sage data') }}</h4>
                <ChevronUpIcon v-if="sageNotAssigned.globalGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else  class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.globalGroup.length > 0 ? 'h-28' : '']" v-if="sageNotAssigned.globalGroupOpened">
                <div v-if="sageNotAssigned.globalGroup?.length > 0" class="flex flex-row font-bold">
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
                <div v-if="sageNotAssigned.globalGroup?.length > 0" v-for="(global, index) in sageNotAssigned.globalGroup">
                    <SageDataDragElement :sageData="global" />
                </div>
                <div v-else class="italic font-light text-sm mb-3">
                    <p>{{ $t('No data available') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import SageDataDragElement from "@/Pages/Projects/Components/SageDataDragElement.vue";

export default {
    name: "SageNotAssignedData",
    components: {
        SageDataDragElement,
        ChevronDownIcon,
        ChevronUpIcon
    },
    props: [
        'sageNotAssigned'
    ],
}
</script>
