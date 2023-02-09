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
                <input :placeholder="[costCenterName ? costCenterName : 'Name des Kostenträgers']"
                       id="title"
                       v-model="costCenterName"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <div class="flex items-center mb-3 mt-4">
                    <input type="checkbox" v-model="ownCopyright"
                           class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:border-none"/>
                    <div class="text-md ml-2" :class="[ownCopyright ? 'text-primary' : 'text-secondary']">
                        Urheberrecht
                    </div>
                </div>
                <div class="flex items-center my-3">
                    <input type="checkbox" v-model="liveMusic"
                           class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:border-none"/>
                    <div class="text-md ml-2" :class="[liveMusic ? 'text-primary' : 'text-secondary']">
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
                          v-model="description"
                          rows="4"
                          class="mt-4 border-gray-300 border-2 h-40 w-full text-sm focus:outline-none
                           focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <div class="w-full flex justify-center my-6">
                    <AddButton
                        text="Speichern"
                        mode="modal"
                        class="px-6 py-3"
                        :disabled="copyright.collecting_society === null"
                        @click="updateData"
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
import {useForm} from "@inertiajs/inertia-vue3";

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
            collectingSociety: this.copyright.collecting_society,
            costCenterName: this.costCenter.name,
            isBigLaw: this.copyright.law_size === 'big',
            isSmallLaw: this.copyright.law_size === 'small',
            ownCopyright: this.copyright.own_copyright,
            liveMusic: this.copyright.live_music,
            description: this.copyright.description,
            costCenterForm: useForm({
                name: this.costCenter.name,
                description: this.copyright.description
            }),
            copyrightForm: useForm({
                ownCopyright: this.ownCopyright,
                liveMusic: this.liveMusic,
                collectingSociety: this.collectingSociety,
                lawSize: this.copyright.law_size
            })
        }
    },
    methods: {
        updateCollectingSociety() {

        },
        updateData() {
            this.costCenterForm.name = this.costCenterName
            this.costCenterForm.description = this.description
            this.costCenterForm.patch(this.route('costCenter.update', this.costCenter.id));

            this.updateCopyright()

            this.$emit('closeModal')
        },
        updateCopyright() {
            this.copyrightForm.ownCopyright = this.ownCopyright
            this.copyrightForm.liveMusic = this.liveMusic
            this.copyrightForm.collectingSociety = this.collectingSociety
            this.copyrightForm.lawSize = this.isBigLaw ? 'big' : 'small'

            console.log(this.copyrightForm)

            this.copyrightForm.patch(this.route('copyright.update', this.copyright.id));
        }
    }
}
</script>

<style scoped>

</style>
