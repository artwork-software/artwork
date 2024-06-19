<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_user_invite.svg" class="-ml-6 -mt-6 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 pl-4">
                                <div class="mt-8 headline1">
                                    {{dayServiceToEdit ? $t('Day Service edit') :  $t('Day Service create') }}
                                </div>
                                <div class="xsLight my-6">
                                    {{
                                        dayServiceToEdit ?
                                            $t('Here you can edit the day service "{0}".', [this.dayServiceToEdit?.name]):
                                            $t('You can create a new day service here.')
                                    }}
                                </div>


                                <div class="flex mb-3">
                                    <Menu as="div" class="relative">
                                        <div>
                                            <MenuButton :class="[this.dayServiceForm.icon === '' ? 'border border-gray-400' : '']"
                                                        class="items-center rounded-full focus:outline-none h-12 w-12">
                                                <label v-if="this.dayServiceForm.icon === null"
                                                       class="cursor-pointer text-gray-400 text-xs">
                                                    {{$t('Icon')}}*
                                                </label>
                                                <ChevronDownIcon v-if="this.dayServiceForm.icon === null"
                                                                 class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"/>
                                                <Component :is="dayServiceForm.icon" v-if="dayServiceForm.icon !== null"
                                                           class="h-12 w-12 mx-auto cursor-pointer flex items-center justify-center"
                                                           alt="Qualifikation-Icon" stroke-width="1.5"
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
                                                <MenuItem v-for="icon in this.iconList" v-slot="{ active }">
                                                    <div @click="this.dayServiceForm.icon = icon.iconName"
                                                         :class="[active ?
                                             'bg-primaryHover text-secondaryHover' :
                                             'text-secondary',
                                             'group px-3 py-2 text-sm subpixel-antialiased flex items-center justify-center']">
                                                        <Component :is="icon.iconName"
                                                                     class="h-12 w-12 mx-auto cursor-pointer flex items-center justify-center"
                                                                     alt="Qualifikation-Icon" stroke-width="1.5"
                                                        />
                                                    </div>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                    <div class="relative my-auto w-full ml-8 mr-12">
                                        <input id="name"
                                               v-model="this.dayServiceForm.name"
                                               type="text"
                                               class="border-gray-300 focus:border-primary mb-3 peer pl-0 h-12 w-full focus:border-t-transparent focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 text-primary placeholder-secondary placeholder-transparent"
                                               placeholder="placeholder"
                                        />
                                        <label for="name"
                                               class="text-secondary absolute left-0 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-base peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm">
                                            {{$t('Name of the day service')}}
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex w-full mb-3">
                                        <div>
                                            <ColorPickerComponent @updateColor="addColor" :color="dayServiceForm.hex_color" />
                                        </div>
                                        <div class="relative my-auto w-full ml-8 mr-12">
                                            <input id="color"
                                                   v-model="this.dayServiceForm.hex_color"
                                                   type="text"
                                                   class="border-gray-300 bg-gray-200 focus:border-primary mb-3 peer pl-0 h-12 w-full focus:border-t-transparent focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 text-primary placeholder-secondary placeholder-transparent"
                                                   placeholder="placeholder"
                                                   disabled
                                            />
                                            <label for="color"
                                                   class="text-secondary absolute left-0 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-base peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm">
                                                {{$t('Color')}}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full text-center mb-6">
                                    <FormButton
                                        @click="createOrUpdate"
                                        :disabled="this.dayServiceForm.icon === null || this.dayServiceForm.name === null || this.dayServiceForm.hex_color === null"
                                        :text="dayServiceToEdit ? $t('Save') : $t('Create')"
                                        class="mt-8 inline-flex items-center"
                                    />
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot
} from '@headlessui/vue'
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Label from "@/Jetstream/Label.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/vue3";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";

export default {
    name: "AddEditDayServiceModal",
    mixins: [Permissions, IconLib],
    components: {
        ColorPickerComponent,
        ShiftQualificationIconCollection, Label,
        FormButton,
        Dialog,
        DialogTitle,
        DialogPanel,
        TransitionChild,
        TransitionRoot,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        ChevronDownIcon,
    },
    data(){
        return {
            open: true,
            dayServiceForm: useForm({
                id: this.dayServiceToEdit ? this.dayServiceToEdit.id : null,
                name: this.dayServiceToEdit ? this.dayServiceToEdit.name : '',
                icon: this.dayServiceToEdit ? this.dayServiceToEdit.icon : '',
                hex_color: this.dayServiceToEdit ? this.dayServiceToEdit.hex_color : ''
            }),
            selectedColor: '#cccc'
        }
    },
    props: ['iconList', 'dayServiceToEdit'],
    emits: ['closed'],
    methods: {
        addColor(color){
            this.dayServiceForm.hex_color = color;
        },
        closeModal(bool){
            this.$emit('closed', bool)
        },
        openColorPicker(){
            this.$refs.colorPicker.click();
        },
        createOrUpdate(){
            if (this.dayServiceToEdit?.id){
                this.dayServiceForm.patch(route('day-service.update', {dayService: this.dayServiceToEdit.id}), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false)
                    }
                })
            } else {
                this.dayServiceForm.post(route('day-service.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false)
                    }
                })
            }
        }
    }
}
</script>
