<template>
    <AppLayout :title="$t('Inventory')">
        <div class="flex justify-between items-center p-5">
            <h2 class="text-2xl font-semibold">{{ $t('Material issue book')}}</h2>
            <!-- Platzhalter für deinen +-Button -->
            <div>
                <!-- Suche nach artikeln in DB, mit Auswahl wird Tag-Bubble hinzugefügt und es werden nur noch die MA angezeigt, welche diesen Artikel im Article-Array haben -->
                Suchfeld
            </div>

            <div>
                <PlusButton :button-text="$t('New issue of material')"
                            @click="openIssueOfMaterialModal"/>
            </div>


        </div>
        <div class="px-5 py-3">
            Tag Bubbles pro Artikel nach dem Gefiltert wird
        </div>
        <div class="relative">
            <BaseCard class="p-4">
                <div class="sticky top-0 z-10 w-fit mb-4 rounded-lg">
                    <div class="grid gap-4 px-3 py-3 grid-cols-8" >
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Name')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Time range')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Room')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Files')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Notes')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center" >
                            <h3 class="xsDark">{{ $t('Responsible')}}</h3>
                        </div>
                        <div class="px-3 text-left flex items-center">
                            <h3 class="xsDark">{{ $t('Status')}}</h3>
                        </div>
                    </div>
                </div>
                <!-- Header mit Titel und +-Button -->


                <!-- Tabellenüberschrift -->
                <div class="">
                    <!-- Alle Materialausgaben -->
                    <WhiteInnerCard class="my-3 group/issueOfMaterial" :key="issueOfMaterial.id" v-for="issueOfMaterial in issueOfMaterialArray">
                        <div class="p-4 flex xsDark">
                            <div  class="grid px-3 py-3 grid-cols-8 gap-4">
                                <div @click="openEditIssueOfMaterialManagement" class="flex items-center cursor-pointer group-hover/issueOfMaterial:text-artwork-buttons-create">
                                    {{issueOfMaterial.name}}
                                </div>
                                <div class="flex items-center">
                                    {{issueOfMaterial.zeitraum}}
                                </div>
                                <div class="flex items-center">
                                    {{issueOfMaterial.raum}}
                                </div>
                                <div class="flex items-center">
                                    {{issueOfMaterial.dateien}}
                                </div>
                                <div class="flex items-center">
                                    {{issueOfMaterial.notizen}}
                                </div>
                                <div class="flex items-center">
                                    {{issueOfMaterial.verantwortliche}}
                                </div>
                                <div class="flex items-center">
                                    <div class="flex items-center">
                                        <div v-if="!issueOfMaterial.abgeschlosseneSonderposten && issueOfMaterial.sonderposten.length > 0">
                                            <!-- Hier Warning Icon -->
                                            <span class="text-red-500 font-bold">Offen</span>
                                        </div>
                                        <div v-else>
                                            <!-- Hier Kein Icon oder Haken -->
                                            <span class="text-green-500 font-bold">Abgeschlossen</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                <BaseMenu white-menu-background has-no-offset>
                                    <BaseMenuItem white-menu-background title="Edit" @click="openEditIssueOfMaterialManagement"></BaseMenuItem>
                                </BaseMenu>
                                </div>
                            </div>

                        </div>
                    </WhiteInnerCard>
                </div>

                <!-- Paginator -->
                <div class="px-5 pb-5">
                    Hier noch Pagination
                </div>
            </BaseCard>
        </div>

        <issue-of-material-modal
            :show="showIssueOfMaterialModal"
            @closed="showIssueOfMaterialModal = false"
            :issue-of-material="null"
            :assigned-articles="issueOfMaterialArray[0].assignedArticles"
        />

    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import BaseCard from "@/Artwork/Cards/BaseCard.vue";
import WhiteInnerCard from "@/Artwork/Cards/WhiteInnerCard.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import IconLib from "@/Mixins/IconLib.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import IssueOfMaterialModal from "@/Pages/Inventory/IssueOfMaterial/IssueOfMaterialModal.vue";
import {ref} from "vue";
const props = defineProps({

})
const showIssueOfMaterialModal = ref(false);

const openIssueOfMaterialModal = () => {
    showIssueOfMaterialModal.value = true;
};

const issueOfMaterialArray = [
    {
        id: 1,
        name: 'Walid Raad',
        zeitraum: '01.01.2023 - 31.12.2023',
        raum: 'Raum 1',
        dateien: 'Datei 1, Datei 2',
        notizen: 'Notiz 1',
        verantwortliche: 'Verantwortlicher 1',
        abgeschlosseneSonderposten: true,
        sonderposten: [
            {
                id: 1,
                name: 'Sonderposten 1',
            },
            {
                id: 2,
                name: 'Sonderposten 2',
            }
        ],
        assignedArticles: [
            {
                id: 1,
                name: 'Artikel 1',
                quantity: 10,
                //Hier eventuell mehr Infos über Artikel wenn sinnvoll
            },
            {
                id: 2,
                name: 'Artikel 2',
                quantity: 5,
            }
        ]
    },
    {
        id: 2,
        name: 'Herbstferien draußen',
        zeitraum: '01.01.2023 - 31.12.2023',
        raum: 'Raum 2',
        dateien: 'Datei 3, Datei 4',
        notizen: 'Notiz 2',
        verantwortliche: 'Verantwortlicher 2',
        abgeschlosseneSonderposten: false,
        sonderposten: [
            {
                id: 3,
                name: 'Sonderposten 3',
            },
            {
                id: 4,
                name: 'Sonderposten 4',
            }
        ]
    },
    {
        id: 3,
        name: 'Herbstferien drinnen',
        zeitraum: '01.01.2023 - 31.12.2023',
        raum: 'Raum 3',
        dateien: 'Datei 5, Datei 6',
        notizen: 'Notiz 3',
        verantwortliche: 'Verantwortlicher 3',
        abgeschlosseneSonderposten: false,
        sonderposten: []
    }
]

</script>

<style scoped>

</style>
