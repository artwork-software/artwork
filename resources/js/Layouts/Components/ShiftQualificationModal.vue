<template>
    <ArtworkBaseModal @close="close(false)" v-if="show"   :title="mode === 'create' ? $t('Create qualification') : $t('Edit qualification')"
                      :description="descriptionText">
            <div class="mx-4">
                <div class="flex items-center gap-4">
                    <PropertyIcon name="IconSelector" @update:modelValue="addIconToForm" :current-icon="shiftQualificationForm ? shiftQualificationForm.icon : null" />
                    <!--<Menu as="div" class="relative col-span-1">
                        <div>
                            <MenuButton :class="[this.shiftQualificationForm.icon === '' ? 'border border-gray-400' : '']" class="menu-button mt-5">
                                <label v-if="this.shiftQualificationForm.icon === null" class="cursor-pointer text-gray-400 text-xs">
                                    {{$t('Icon')}}*
                                </label>

                                <ShiftQualificationIconCollection v-if="this.shiftQualificationForm.icon !== null" class="h-12 w-12 p-2" :iconName=this.shiftQualificationForm.icon alt="Qualifikation-Icon"/>
                                <ChevronDownIcon class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to">
                            <MenuItems class="z-40 origin-top-right absolute h-56 w-24 overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="shiftQualificationIcon in this.shiftQualificationIcons"
                                          v-slot="{ active }">
                                    <div @click="this.shiftQualificationForm.icon = shiftQualificationIcon.iconName"
                                         :class="[
                                             active ?
                                             'bg-primaryHover text-secondaryHover' :
                                             'text-secondary',
                                             'group px-3 py-2 text-sm subpixel-antialiased flex items-center justify-center'
                                         ]">
                                        <ShiftQualificationIconCollection
                                            class="h-12 w-12 mx-auto cursor-pointer flex items-center justify-center"
                                            :iconName=shiftQualificationIcon.iconName
                                            alt="Qualifikation-Icon"
                                        />
                                    </div>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>-->
                    <div class="w-full">
                        <BaseInput
                            no-margin-top
                            id="name"
                            v-model="this.shiftQualificationForm.name"
                            :label="$t('Name of the qualification')"
                        />
                    </div>
                </div>
                <div class="mt-5 -mx-9 px-10 py-6 bg-lightBackgroundGray">
                    <div class="flex gap-2 items-center">
                        <input type="checkbox"
                               v-model="this.shiftQualificationForm.available"
                               class="input-checklist"
                        />
                        <p :class="[this.shiftQualificationForm.available ? 'xsDark' : 'xsLight']"
                           class="my-auto text-sm">
                            {{ $t('Is taken into account for new shifts')}}
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end mt-5">
                    <BaseUIButton
                        @click="save"
                        :disabled="this.shiftQualificationForm.icon === null || this.shiftQualificationForm.name === null"
                        :label="this.mode === 'create' ? $t('Create') : $t('Save')"
                        is-add-button
                        />
                </div>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import {defineComponent} from "vue";
import {ChevronDownIcon, XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import Label from "@/Jetstream/Label.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
const shiftQualificationIcons = [
    {iconName: 'user-icon'},
    {iconName: 'academic-cap-icon'},
    {iconName: 'bell-icon'},
    {iconName: 'chat-icon'},
    {iconName: 'adjustments-icon'},
    {iconName: 'book-open-icon'},
    {iconName: 'briefcase-icon'},
    {iconName: 'camera-icon'},
    {iconName: 'clipboard-icon'},
    {iconName: 'eye-icon'},
    {iconName: 'film-icon'},
];
export default defineComponent({
    name: 'ShiftQualificationModal',
    components: {
        PropertyIcon,
        BaseUIButton,
        ArtworkBaseModal,
        BaseInput,
        IconSelector,
        ModalHeader,
        TextInputComponent,
        BaseModal,
        FormButton,
        Label,
        ShiftQualificationIconCollection,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        ChevronDownIcon,
        XIcon,
        JetDialogModal
    },
    props: [
        'show',
        'mode',
        'shiftQualification'
    ],
    emits: [
        'close'
    ],
    data () {
        return {
            descriptionText: this.mode === 'create' ? this.$t('You can create a qualification here.') : this.$t('Here you can edit the qualification "{0}".', [this.shiftQualification.name]),
            shiftQualificationForm: useForm({
                icon: this.mode === 'edit' ? this.shiftQualification.icon : null,
                name: this.mode === 'edit' ? this.shiftQualification.name : null,
                available: this.mode === 'edit' ? this.shiftQualification.available : false
            })
        }
    },
    methods: {
        addIconToForm(icon) {
            this.shiftQualificationForm.icon = icon;
        },
        save() {
            const onSuccessCallback = () => this.close();

            if (this.mode === 'edit') {
                this.shiftQualificationForm.patch(
                    route(
                        'shift-qualifications.update',
                        {
                            shift_qualification: this.shiftQualification.id
                        }
                    ),
                    {
                        preserveScroll: true,
                        onSuccess: onSuccessCallback
                    }
                );
            } else {
                this.shiftQualificationForm.post(
                    route('shift-qualifications.store'),
                    {
                        preserveScroll: true,
                        onSuccess: onSuccessCallback
                    }
                );
            }
        },
        close() {
            this.$emit('close');
            this.shiftQualificationForm.reset();
        }
    },
    setup() {
        return {
            shiftQualificationIcons
        }
    }
});
</script>
