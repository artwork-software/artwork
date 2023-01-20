<template>
    <jet-dialog-modal :show="show" @close="$emit('closeModal')">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Kostenträger & Urheberrecht
                </div>
                <XIcon @click="$emit('closeModal')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary w-full mt-2">Lege einen Kostenträger und Urheberregelungen für dein Projekt
                    fest.
                </div>
                <input :placeholder="copyright.name || 'Name des Kostenträgers'"
                       id="title"
                       v-model="copyright.name"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <div class="flex items-center mb-3 mt-4">
                    <input type="checkbox" v-model="copyright.own_copyright"
                           class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:border-none"/>
                    <div class="text-md ml-2" :class="[copyright.own_copyright ? 'text-primary' : 'text-secondary']">
                        Urheberrecht
                    </div>
                </div>
                <div class="flex items-center my-3">
                    <input type="checkbox" v-model="copyright.live_music"
                           class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:border-none"/>
                    <div class="text-md ml-2" :class="[copyright.live_music ? 'text-primary' : 'text-secondary']">
                        Livemusik
                    </div>
                </div>

                <ProjectCollectingSocietiesMenu
                    :copyright="copyright"
                    :collecting-societies="collectingSocieties"
                    @update-collecting-society="updateCollectingSociety"
                />

                <div class="flex items-center w-full my-3">
                    <div class="flex items-center w-1/2">
                        <input type="checkbox"
                               v-model="isBigLaw"
                               class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300
                               bg-darkGrayBg focus:border-none rounded-3xl"
                        />
                        <div class="text-md ml-2"
                             :class="[isBigLaw ? 'text-primary' : 'text-secondary']">
                            Großes Recht
                        </div>
                    </div>
                    <div class="flex items-center w-1/2">
                        <input type="checkbox"
                               v-model="isSmallLaw"
                               class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300
                               bg-darkGrayBg focus:border-none rounded-3xl"
                        />
                        <div class="text-md ml-2" :class="[isSmallLaw ? 'text-primary' : 'text-secondary']">
                            Kleines Recht
                        </div>
                    </div>
                </div>

                <textarea placeholder="Kommentar / Notiz"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="mt-4 border-gray-300 border-2 h-40 w-full text-sm focus:outline-none
                           focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <div class="w-full flex justify-center my-6">
                    <AddButton
                        text="Speichern"
                        mode="modal"
                        class="px-6 py-3"
                        :disabled="copyright.collecting_society === null"
                        @click="updateCopyrightData"
                    />
                </div>

            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import ProjectCollectingSocietiesMenu from "@/Layouts/Components/ProjectCollectingSocietiesMenu.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";

export default {
    name: "ProjectCopyrightModal",
    props: {
        show: Boolean,
        project: Object,
        copyright: Object,
        costCenter: Object
    },
    components: {
        JetDialogModal,
        XIcon,
        ProjectCollectingSocietiesMenu,
        AddButton
    },
    data() {
        return {
            // fake data, needs to be replaced by a prop later
            collectingSocieties: [
                {
                    id: 1,
                    name: "Verwertungsgesellschaft 1"
                },
                {
                    id: 2,
                    name: "Verwertungsgesellschaft 2"
                }
            ],
            isBigLaw: this.copyright.law_size === 'big',
            isSmallLaw: this.copyright.law_size === 'small',
            comment: this.copyright.description
        }
    },
    methods: {
        updateCollectingSociety() {
            console.log('updated')
            this.$emit('closeModal')
        },
        updateCopyrightData() {
            console.log("update data")
        }
    }
}
</script>

<style scoped>

</style>
