<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Cost units & copyright')}}
                </div>
                <div class="text-secondary w-full mt-2">
                    {{$t('Define a cost unit and copyright regulations for your project.')}}
                </div>
                <input :placeholder="[projectRightForm.cost_center_name ? projectRightForm.cost_center_name : $t('Name of the cost unit')]"
                       id="title"
                       v-model="projectRightForm.cost_center_name"
                       class="mt-4 p-4 inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <div class="flex items-center mb-3 mt-4">
                    <input type="checkbox" v-model="projectRightForm.own_copyright"
                           class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:ring-0"/>
                    <div class="text-md ml-2" :class="[projectRightForm.own_copyright ? 'text-primary' : 'text-secondary']">
                        {{ $t('Copyright')}}
                    </div>
                </div>


                <div v-if="projectRightForm.own_copyright">
                    <div class="flex items-center my-3">
                        <input type="checkbox" v-model="projectRightForm.live_music"
                               class="cursor-pointer h-4 w-4 text-success border-2 border-gray-300 bg-darkGrayBg focus:ring-0"/>
                        <div class="text-md ml-2" :class="[projectRightForm.live_music ? 'text-primary' : 'text-secondary']">
                            {{ $t('Live music')}}
                        </div>
                    </div>
                    <Listbox as="div" v-model="collectingSociety" id="collecting_society">
                        <ListboxButton
                            class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4">
                            <div v-if="collectingSociety" class="flex-grow text-left">
                                {{collectingSociety?.name}}
                            </div>
                            <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                                {{ $t('Choose a collecting society')}}*
                            </div>
                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-[85%] bg-primary overflow-y-auto text-sm absolute">
                            <ListboxOption v-for="society in collectingSocieties"
                                           class="hover:bg-artwork-buttons-create text-secondary cursor-pointer p-3 flex justify-between "
                                           :key="society.name"
                                           :value="society"
                                           v-slot="{ active, selected }">
                                <div :class="[selected ? 'text-white' : '']">
                                    {{ society?.name }}
                                </div>
                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>

                    <div>
                        <fieldset class="mt-4">
                            <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                                <div v-for="lawSize in lawSizes" :key="lawSize.id" class="flex items-center">
                                    <input :id="lawSize.id" name="notification-method" type="radio" v-model="projectRightForm.law_size" :value="lawSize.id" :checked="lawSize.id === projectRightForm.law_size" class="h-5 w-5 border-green-300 text-green-600 ring-0 focus:ring-0" />
                                    <label :for="lawSize.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ lawSize.name }}</label>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <textarea :placeholder="$t('Comment / Note')"
                              id="description"
                              v-model="projectRightForm.description"
                              rows="4"
                              class="mt-4 border-gray-300 border-2 h-40 text-sm focus:outline-none
                           focus:ring-0 focus:border-secondary focus:border-1 w-full"/>

                </div>
                <div class="w-full flex justify-center my-6">
                    <FormButton
                        :text="$t('Save')"
                        @click="updateData"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon, ChevronDownIcon, CheckIcon} from "@heroicons/vue/outline";
import ProjectCollectingSocietiesMenu from "@/Layouts/Components/ProjectCollectingSocietiesMenu.vue";
import {useForm} from "@inertiajs/vue3";
import {
    Listbox,
    ListboxOption,
    ListboxOptions,
    ListboxButton
} from "@headlessui/vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "ProjectCopyrightModal",
    props: [
        'show', 'project', 'collectingSocieties'
    ],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon,
        ProjectCollectingSocietiesMenu,
        ChevronDownIcon,
        CheckIcon,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton
    },
    data() {
        return {
            collectingSociety: this.project.collecting_society ? this.project.collecting_society : null,
            projectRightForm: useForm({
                cost_center_id: this.project.cost_center ? this.project.cost_center.id : null,
                cost_center_name: this.project.cost_center ? this.project.cost_center?.name : '',
                description: this.project ? this.project.cost_center_description : '',
                own_copyright: this.project ? this.project?.own_copyright : false,
                live_music: this.project ? this.project?.live_music : false,
                collecting_society_id: this.project.collecting_society ? this.project?.collecting_society?.id : null,
                law_size: this.project ? this.project?.law_size : 'SMALL',
            }),
            lawSizes: [
                {id: 'BIG', name: this.$t('Big law')},
                {id: 'SMALL', name: this.$t('Small law')}
            ],
        }
    },
    methods: {
        updateData() {
            if(this.projectRightForm.own_copyright) {
                this.projectRightForm.collecting_society_id = this.collectingSociety.id;
            }

            this.projectRightForm.post(route('project.copyright.update', this.project.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('closeModal');
                }
            });
        },
    }
}
</script>

<style scoped>

</style>
