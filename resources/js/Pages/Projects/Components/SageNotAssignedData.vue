<script>
import {ChevronDownIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import SageDataDragElement from "@/Pages/Projects/Components/SageDataDragElement.vue";

export default {
    name: "SageNotAssignedData",
    components: {SageDataDragElement, ChevronDownIcon, ChevronUpIcon},
    props: ['sageNotAssigned'],

}
</script>

<template>
    <div class="my-3 ml-6">
        <div>
            <div class="flex items-center gap-x-1 cursor-pointer" @click="sageNotAssigned.projectGroupOpened = !sageNotAssigned.projectGroupOpened">
                <h4 class="font-bold">Projektbezogene Sage-Daten</h4>
                <ChevronUpIcon v-if="sageNotAssigned.projectGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else  class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.projectsGroup?.length > 0 ? 'h-28' : '']" v-if="sageNotAssigned.projectGroupOpened">
                <div v-if="sageNotAssigned.projectsGroup?.length > 0" class="flex flex-row font-bold">
                    <div class="w-28">
                        KTO
                    </div>
                    <div class="w-28">
                        KST
                    </div>
                    <div class="w-64 truncate">
                        Buchungstext
                    </div>
                    <div class="w-52 text-right">
                        Buchungsbetrag
                    </div>
                    <div class="w-40 text-right">
                        Buchungsdatum
                    </div>
                </div>
                <div v-if="sageNotAssigned.projectsGroup?.length > 0" v-for="(project, index) in sageNotAssigned.projectsGroup">
                    <SageDataDragElement :sageData="project" />
                </div>
                <div v-else class="italic font-light text-sm mb-3">
                    <p>Keine Daten vorhanden</p>
                </div>
            </div>
        </div>
        <div>
            <div class="flex items-center gap-x-1 cursor-pointer" @click="sageNotAssigned.globalGroupOpened = !sageNotAssigned.globalGroupOpened">
                <h4 class="font-bold">Globale Sage-Daten</h4>
                <ChevronUpIcon v-if="sageNotAssigned.globalGroupOpened" class="h-6 w-6 text-primary my-auto" />
                <ChevronDownIcon v-else  class="h-6 w-6 text-primary my-auto" />
            </div>
            <div class="overflow-x-hidden overflow-y-scroll" :class="[sageNotAssigned.globalGroup.length > 0 ? 'h-28' : '']" v-if="sageNotAssigned.globalGroupOpened">
                <div v-if="sageNotAssigned.globalGroup?.length > 0" class="flex flex-row font-bold">
                    <div class="w-28">
                        KTO
                    </div>
                    <div class="w-28">
                        KST
                    </div>
                    <div class="w-64 truncate">
                        Buchungstext
                    </div>
                    <div class="w-52 text-right">
                        Buchungsbetrag
                    </div>
                    <div class="w-40 text-right">
                        Buchungsdatum
                    </div>
                </div>
                <div v-if="sageNotAssigned.globalGroup?.length > 0" v-for="(global, index) in sageNotAssigned.globalGroup">
                    <SageDataDragElement :sageData="global" />
                </div>
                <div v-else class="italic font-light text-sm mb-3">
                    <p>Keine Daten vorhanden</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
