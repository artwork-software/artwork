<template>
    <BaseModal @closed="close(false)" v-if="show" modal-image="/Svgs/Overlays/illu_user_invite.svg">
            <div class="mx-4">
                <div class="mt-8 headline1">
                    {{this.mode === 'create' ? $t('Create qualification') : $t('Edit qualification')}}
                </div>
                <div class="xsLight my-6">
                    {{
                        this.mode === 'create' ?
                            $t('You can create a qualification here.') : $t('Here you can edit the qualification "{0}".', [this.shiftQualification.name])
                    }}
                </div>
                <div class="flex">
                    <Menu as="div" class="relative">
                        <div>
                            <MenuButton :class="[this.shiftQualificationForm.icon === '' ? 'border border-gray-400' : '']"
                                        class="items-center rounded-full focus:outline-none h-12 w-12">
                                <label v-if="this.shiftQualificationForm.icon === null"
                                       class="cursor-pointer text-gray-400 text-xs">
                                    {{$t('Icon')}}*
                                </label>
                                <ChevronDownIcon v-if="this.shiftQualificationForm.icon === null"
                                                 class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"/>
                                <ShiftQualificationIconCollection
                                    v-if="this.shiftQualificationForm.icon !== null"
                                    class="h-12 w-12"
                                    :iconName=this.shiftQualificationForm.icon
                                    alt="Qualifikation-Icon"
                                />
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="z-40 origin-top-right absolute h-56 w-24 overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
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
                    </Menu>
                    <div class="relative my-auto w-full ml-8 mr-12">
                        <input id="name"
                               v-model="this.shiftQualificationForm.name"
                               type="text"
                               class="border-gray-300 focus:border-primary mb-3 peer pl-0 h-12 w-full focus:border-t-transparent focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"
                        />
                        <label for="name"
                               class="text-secondary absolute left-0 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-base peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm">
                            {{$t('Name of the qualification')}}
                        </label>
                    </div>
                </div>
                <div class="w-full flex mx-20 mt-5">
                    <input type="checkbox"
                           v-model="this.shiftQualificationForm.available"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"
                    />
                    <p :class="[this.shiftQualificationForm.available ? 'xsDark' : 'xsLight']"
                       class="ml-3 my-auto text-sm">
                        {{ $t('Is taken into account for new shifts')}}
                    </p>
                </div>
                <div class="w-full text-center mb-6">
                    <FormButton
                        @click="save"
                        :disabled="this.shiftQualificationForm.icon === null || this.shiftQualificationForm.name === null"
                        :text="this.mode === 'create' ? $t('Create') : $t('Save')"
                        class="mt-8 inline-flex items-center"
                        />
                </div>
            </div>
    </BaseModal>
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
            shiftQualificationForm: useForm({
                icon: this.mode === 'edit' ? this.shiftQualification.icon : null,
                name: this.mode === 'edit' ? this.shiftQualification.name : null,
                available: this.mode === 'edit' ? this.shiftQualification.available : false
            })
        }
    },
    methods: {
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
