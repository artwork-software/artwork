<template>
    <div>
        <div v-if="this.isSignedInUser() || this.hasAdminRole()" class="mb-8">
            <div>
                <div class="col-span-6 sm:col-span-4">
                    <!-- Profile Photo File Input -->
                    <input type="file" class="hidden"
                           ref="photo"
                           @change="updatePhotoPreview">
                    <div class="mt-1 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3 flex items-end">
                            <div @click="openChangePictureModal" class="mt-2">
                                <img :src="this.user_to_edit.profile_photo_url" :alt="this.user_to_edit.first_name"
                                     class="rounded-full h-20 w-20 object-cover cursor-pointer">
                            </div>
                            <div class="mt-1 ml-5 flex-grow relative">
                                <input id="first_name"
                                       v-model="userForm.first_name"
                                       type="text"
                                       class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-black focus:ring-black focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"
                                       @focusout="this.editUser()"
                                />
                                <label for="first_name"
                                       class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{ $t('First name') }}</label>
                            </div>
                        </div>
                        <div class="sm:col-span-3 flex items-end">
                            <div class="relative mt-1 w-full">
                                <input id="last_name"
                                       v-model="userForm.last_name"
                                       type="text"
                                       class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-black focus:ring-black focus:ring-0 border-l-0 border-t-0 border-r-0
                                               border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"
                                       @focusout="this.editUser()"
                                />
                                <label for="last_name"
                                       class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased
                                               focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{ $t('Last name')}}</label>
                            </div>
                        </div>
                    </div>
                    <div v-if="hasNameError" class="text-error mt-1">{{ nameError }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input disabled="disabled"
                               type="text"
                               :value="$page.props.businessName"
                               class="bg-gray-100 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"
                               @focusout="this.editUser()"
                        />
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                               :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                               type="text"
                               v-model="userForm.position"
                               :placeholder="this.$t('Position')"
                               class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"
                               @focusout="this.editUser()"
                        />
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input type="text" v-model="this.user_to_edit.email"
                               :disabled="!this.hasAdminRole()"
                               :class="this.hasAdminRole() ? '' : 'bg-gray-100'"
                               class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"
                               @focusout="this.editUser()"
                        />
                        <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="mt-1">
                        <input :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                               :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                               type="text"
                               v-model="userForm.phone_number"
                               :placeholder="$t('Phone number')"
                               class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"
                               @focusout="this.editUser()"
                        />
                    </div>
                </div>
                <div class="col-span-full">
                    <Listbox as="div" class="w-44" v-model="selectedLanguage" @update:modelValue="this.editUser()">
                        <ListboxLabel class="block text-sm font-bold leading-6 text-gray-900">{{ $t('Application language')}}</ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="relative w-full cursor-default shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block border-gray-300 text-start py-2 px-3">
                                <span class="block truncate">{{ selectedLanguage?.name }}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                  <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="language in languages" :key="language.id" :value="language" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ language.name }}</span>

                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="sm:col-span-6">
                    <div class="mt-1">
                        <textarea :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                                  :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                                  :placeholder="$t('What should other artwork users know?')"
                                  v-model="userForm.description"
                                  rows="5"
                                  class="resize-none shadow-sm placeholder-secondary p-4 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"
                                  @focusout="this.editUser()"
                        />
                    </div>
                </div>
                <div class="sm:col-span-6 mt-4 flex">
                    <span v-if="userForm.departments.length === 0"
                          class="text-secondary subpixel-antialiased my-auto mr-4">{{ $t('Not in any team') }}</span>
                    <span v-else class="flex -mr-3"
                          v-for="(team,index) in userForm.departments">
                        <TeamIconCollection class="h-10 w-10 rounded-full ring-2 ring-white"
                                            :iconName="team.svg_name"
                        />
                    </span>
                    <Menu v-show="this.$can('teammanagement')" as="div" class="my-auto relative ml-5">
                        <div>
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                    aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute p-4 mr-4 mt-2 w-80 shadow-lg bg-primary focus:outline-none">
                                <div>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="openChangeTeamsModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit team membership')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="deleteFromAllDepartments"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Remove user from all teams') }}
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>
        <div class="">
            <div class="flex mt-6" v-if="this.hasAdminRole()">
                <span @click="resetPassword()" class="xsLight cursor-pointer">{{ $t('Reset Password')}}</span>
            </div>
            <div v-if="password_reset_status" class="mb-4 font-medium text-sm text-green-600">
                {{ password_reset_status }}
            </div>
        </div>
    </div>
    <jet-dialog-modal :show="showChangeTeamsModal" @close="closeChangeTeamsModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_team_user.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="headline1 my-2">
                     {{$t('Team membership')}}
                </div>
                <XIcon @click="closeChangeTeamsModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4 xsLight">
                    {{ $t('Specify which teams the user is in. Note: He/she has authorization to view all projects assigned to the teams. Projects assigned to the teams.')}}
                </div>
                <div class="mt-8 mb-8">
                    <span v-if="departments.length === 0"
                          class="xsLight flex mb-6 mt-8 my-auto">{{ $t('No teams have been created in the tool yet.')}}</span>
                    <div v-for="team in departments">
                        <span class=" flex items-center pr-4 py-2 text-md">
                            <input :key="team.name" type="checkbox" :value="team" :id="team.id"
                                   v-model="team.checked"
                                   @change="teamChecked(team)"
                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                            <TeamIconCollection class="h-9 w-9 rounded-full ring-2 ring-white"
                                                :iconName="team.svg_name"/>
                            <span :class="[team.checked ? 'xsDark' : 'xsLight']"
                                  class="ml-2">
                            {{ team.name }}
                            </span>
                        </span>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton @click="saveNewTeams" :text="$t('Save')"/>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <jet-dialog-modal :show="showChangePictureModal" @close="closeChangePictureModal">
        <template #content>
            <div class="mx-4">
                <div class="font-bold font-lexend text-primary text-2xl my-2">
                    {{ $t('Change profile picture')}}
                </div>
                <span class="text-secondary my-auto">
                    {{ $t('Select your profile picture here. It should not exceed the size of 1024 KB.')}}
                </span>
                <XIcon @click="closeChangePictureModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <!-- New Profile Photo Preview -->
                <h2 class="" v-show="photoPreview">{{ $t('Preview new profile picture:')}}</h2>
                <div class="flex">
                    <div class="mt-1 flex items-center">
                        <div class="mt-2" v-show="photoPreview">
                            <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                  :style="'background-image: url(\'' + photoPreview + '\');'">
                            </span>
                        </div>
                    </div>
                    <div class="flex mt-4" :class="photoPreview ? 'ml-3' : ''">
                        <BaseButton :text="$t('Select file')" @click.prevent="selectNewPhoto" />
                        <BaseButton @click.prevent="deletePhoto"
                                    :text="$t('Delete current profile picture')"
                                    v-if="this.user_to_edit.profile_photo_url" />
                    </div>
                </div>
                <jet-input-error :message="updateProfilePictureFeedback" class="mt-2"/>
                <jet-input-error :message="userForm.errors.photo" class="mt-2"/>
                <div class="mt-6">
                    <BaseButton
                        :text="$t('Save new profile picture')"
                        @click="validateTypeAndChange" />
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <SuccessModal v-if="showSuccessModal"
                  :title="$t('User successfully edited')"
                  :description="$t('The changes have been saved successfully.')"
                  :button="$t('Ok')"
                  @closed="closeSuccessModal"
    />
</template>

<script>

import {CheckIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon, ChevronDownIcon } from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Permissions from "@/mixins/Permissions.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue';
import SecondaryButton from "@/Jetstream/SecondaryButton.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    components: {
        BaseButton,
        SecondaryButton,
        FormButton,
        SuccessModal,
        CheckIcon,
        JetDialogModal, XIcon,
        PencilAltIcon,
        JetInputError,
        DotsVerticalIcon,
        TeamIconCollection,
        TrashIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon
    },
    mixins: [Permissions],
    props: [
        'user_to_edit',
        'password_reset_status',
        'departments'
    ],
    watch: {
        selectedLanguage: {
            handler() {
                document.documentElement.lang = this.selectedLanguage.id;
            },
            deep: true
        }
    },
    data() {
        return {
            showChangeTeamsModal: false,
            showSuccessModal: false,
            nameError: false,
            hasNameError: false,
            updateProfilePictureFeedback: "",
            photoPreview: null,
            showChangePictureModal: false,
            userForm: useForm({
                first_name: this.user_to_edit.first_name,
                last_name: this.user_to_edit.last_name,
                email: this.user_to_edit.email,
                photo:this.user_to_edit.profile_photo_path,
                position: this.user_to_edit.position,
                departments: this.user_to_edit.departments,
                phone_number: this.user_to_edit.phone_number,
                description: this.user_to_edit.description,
                language: this.user_to_edit.language
            }),
            resetPasswordForm: this.$inertia.form({
                email: this.user_to_edit.email
            }),
            statusbar: {
                show: true,
                status: "success",
                text: this.$t('User has been successfully edited')
            },
            languages: [
                { id: 'en', name: 'English' },
                { id: 'de', name: 'German' },

            ],
            // set the default selected language to the user's language
            selectedLanguage: null
        }
    },
    mounted() {
        this.selectedLanguage = this.languages.find(language => language.id === this.user_to_edit.language);
    },
    methods: {
        isSignedInUser() {
            return this.user_to_edit.id === this.$page.props.user.id;
        },
        openChangeTeamsModal() {
            this.departments.forEach((team) => {
                this.userForm.departments.forEach((userTeam) => {
                    if (userTeam.id === team.id) {
                        team.checked = true;
                    }
                })
            })
            this.showChangeTeamsModal = true;
        },
        closeChangeTeamsModal() {
            this.showChangeTeamsModal = false;
        },
        deleteFromAllDepartments() {
            this.userForm.departments = [];
            this.userForm.patch(route('user.update', this.user_to_edit.id));
            this.openSuccessModal();
        },
        editUser() {
            this.userForm.language = this.selectedLanguage.id;
            this.$updateLocale(this.selectedLanguage.id);
            if (this.hasAdminRole()) {
                this.userForm.email = this.user_to_edit.email;
            }
            if (!this.userForm.first_name || !this.userForm.last_name) {
                this.nameError = this.$t('First name and surname are required');
                this.hasNameError = true;
                return; // Exit the function without making the API call
            }
            this.userForm.patch(
                route('user.update', {user: this.user_to_edit.id}),
                {
                    preserveScroll: true,
                    preserveState: false,
                    onSuccess: () => this.openSuccessModal()
                }
            );
            this.nameError = false;
            this.hasNameError = false;
        },
        resetPassword() {
            this.resetPasswordForm.post(route('user.reset.password'));
        },
        saveNewTeams() {
            this.userForm.patch(route('user.update', this.user_to_edit.id));
            this.closeChangeTeamsModal();
            this.openSuccessModal()
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
        teamChecked(team) {
            if (team.checked) {
                this.userForm.departments.push(team);
            } else {
                const spliceIndex = this.userForm.departments.findIndex(teamToSplice => {
                    return team.id === teamToSplice.id
                })
                this.userForm.departments.splice(spliceIndex, 1);
            }
        },
        updateProfileInformation() {
            if (this.$refs.photo) {
                this.userForm.photo = this.$refs.photo.files[0]
            }

            this.userForm.post(route('user-profile-information.update'), {
                errorBag: 'updateProfileInformation',
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput(), this.showSuccessButton()),
            });
        },
        openChangePictureModal() {
            this.showChangePictureModal = true;
        },
        closeChangePictureModal() {
            this.showChangePictureModal = false;
        },
        updatePhotoPreview() {
            const photo = this.$refs.photo.files[0];

            if (!photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                if (e.target.result.includes("data:image/png") || e.target.result.includes("data:image/jpeg")) {
                    this.photoPreview = e.target.result;
                } else {
                    this.updateProfilePictureFeedback = this.$t('Only .png and .jpeg files are supported')
                }
            };

            reader.readAsDataURL(photo);
        },
        selectNewPhoto() {
            this.$refs.photo.click();
        },
        validateTypeAndChange() {
            this.updateProfilePictureFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]

            if (this.$refs.photo.files[0]) {
                if (forbiddenTypes.includes(this.$refs.photo.files[0]?.type)
                    || this.$refs.photo.files[0].type.match('video.*')
                    || this.$refs.photo.files[0].type === "") {
                    this.updateProfilePictureFeedback = this.$t('Only .png and .jpeg files are supported')
                } else {
                    this.changeProfilePicture()
                }
            } else {
                this.closeChangePictureModal()
            }
        },
        changeProfilePicture() {
            if (this.$refs.photo) {
                this.userForm.photo = this.$refs.photo.files[0]
            }
            this.userForm.post(route('user.update.photo', this.user_to_edit.id), {
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput(), this.closeChangePictureModal()),
            });
        },
        deletePhoto() {
            this.$inertia.delete(route('current-user-photo.destroy'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.photoPreview = null;
                    this.clearPhotoFileInput();
                },
            });
        },
        clearPhotoFileInput() {
            if (this.$refs.photo?.value) {
                this.$refs.photo.value = null;
            }
        },
    }
}
</script>
