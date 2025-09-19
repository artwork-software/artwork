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
                        <div class="sm:col-span-3 flex items-center gap-x-3">
                            <div @click="openChangePictureModal" class="mt-2">
                                <img :src="this.user_to_edit.profile_photo_url" :alt="this.user_to_edit.first_name"
                                     class="rounded-full h-20 w-20 min-w-20 min-h-20 object-cover cursor-pointer">
                            </div>
                            <BaseInput id="first_name"
                                       v-model="userForm.first_name"
                                       type="text"
                                       label="First Name"
                                       @focusout="this.editUser()"
                            />
                        </div>
                        <div class="sm:col-span-3 flex items-center">
                            <BaseInput id="first_name"
                                       v-model="userForm.last_name"
                                       type="text"
                                       label="Last Name"
                                       @focusout="this.editUser()"
                            />
                        </div>
                    </div>
                    <div v-if="hasNameError" class="text-error mt-1">{{ nameError }}</div>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <BaseInput
                disabled="disabled"
                type="text"
                v-model="$page.props.businessName"
                :label="$page.props.businessName"
                @focusout="this.editUser()"
                id="business"
            />
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 mt-4">
                <div class="sm:col-span-3">
                    <div class="">
                        <BaseInput
                            :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                            :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                            type="text"
                            v-model="userForm.pronouns"
                            label="Pronouns"
                            @focusout="this.editUser()"
                            id="pronouns"
                        />
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="">
                        <BaseInput
                            :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                            :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                            type="text"
                            v-model="userForm.position"
                            label="Position"
                            @focusout="this.editUser()"
                            id="position"
                        />
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="">
                        <BaseInput
                            type="text"
                            v-model="this.user_to_edit.email"
                            :disabled="!this.hasAdminRole()"
                            :class="this.hasAdminRole() ? '' : 'bg-gray-100'"
                            @focusout="this.editUser()"
                            label="Email"
                            id="email"
                        />
                        <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                    </div>
                </div>
                <div class="sm:col-span-3">
                    <div class="">
                        <BaseInput
                            :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                            :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                            type="text"
                            v-model="userForm.phone_number"
                            label="Phone number"
                            @focusout="this.editUser()"
                            id="phone_number"
                        />
                    </div>
                </div>
                <div class="col-span-2" v-if="this.isSignedInUser() || hasAdminRole">
                    <Listbox as="div" class="w-52" v-model="selectedLanguage" @update:modelValue="this.editUser()">
                        <ListboxLabel class="block text-sm font-bold leading-6 text-gray-900">{{ $t('Application language')}}</ListboxLabel>
                        <div class="relative mt-2">
                            <ListboxButton class="menu-button">
                                <span class="block truncate">{{ selectedLanguage?.name }}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                  <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="language in languages" :key="language.id" :value="language" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ language.name }}</span>

                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>
                <div class="col-span-1">
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="high_contrast" v-model="userForm.high_contrast" @change="editUser" aria-describedby="high_contrast-description" name="high_contrast" type="checkbox" class="input-checklist" />
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="high_contrast" class="font-medium text-gray-900">{{ $t('High contrast')}}</label>
                            <p id="high_contrast-description" class="text-gray-500">
                                {{ $t('Enable high contrast mode in the application for the event type colors.')}}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-span-1">
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="email_private" v-model="userForm.email_private" @change="editUser" aria-describedby="email_private-description" name="email_private" type="checkbox" class="input-checklist" />
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="email_private" class="font-medium text-gray-900">
                                {{ $t('Email private')}}
                            </label>
                            <p id="email_private-description" class="text-gray-500">
                                {{ $t('Hide your email address from other users.')}}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-span-1">
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="phone_private" v-model="userForm.phone_private" @change="editUser" aria-describedby="phone_private-description" name="phone_private" type="checkbox" class="input-checklist" />
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="phone_private" class="font-medium text-gray-900">
                                {{ $t('Phone private')}}
                            </label>
                            <p id="phone_private-description" class="text-gray-500">
                                {{ $t('Hide your phone number from other users.')}}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-span-3 mt-4">
                    <div class="relative flex items-start">
                        <div class="flex h-6 items-center">
                            <input id="use_chat" v-model="userForm.use_chat" @change="editUser" aria-describedby="use_chat-description" name="use_chat" type="checkbox" class="input-checklist" />
                        </div>
                        <div class="ml-3 text-sm leading-6">
                            <label for="use_chat" class="font-medium text-gray-900">
                                {{ $t('Use Artwork Chat')}}
                            </label>
                            <p id="use_chat-description" class="text-gray-500">
                                {{ $t('Use the chat function in the application.')}}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="sm:col-span-6">
                    <div class="mt-1">
                        <BaseTextarea
                            :disabled="!this.isSignedInUser() && !this.$can('can manage workers')"
                            :class="this.isSignedInUser() || this.$can('can manage workers') ? '' : 'bg-gray-100'"
                            label="What should other artwork users know?"
                            v-model="userForm.description"
                            rows="5"
                            @focusout="this.editUser()"
                            id="description"
                        />
                    </div>
                </div>

            </div>
            <div class="mt-8 flex items-center justify-between w-full">
                <div class="flex items-center">
                    <div v-if="userForm.departments.length === 0" class="text-secondary subpixel-antialiased my-auto mr-4">{{ $t('Not in any team') }}</div>
                    <div class="flex -space-x-4" v-else>
                        <img v-for="( team, index ) in userForm.departments" class="inline-block size-10 min-w-10 min-h-10 rounded-full ring-2 ring-white" :src="'/Svgs/TeamIconSvgs/' + team.svg_name + '.svg'" alt="" />
                    </div>
                    <BaseMenu has-no-offset v-show="this.$can('teammanagement')" class="ml-5 mt-2" :right="true" menu-width="w-88">
                        <BaseMenuItem :icon="IconEdit" @click="openChangeTeamsModal" title="Edit team membership" />
                        <BaseMenuItem :icon="IconTrash" @click="deleteFromAllDepartments" title="Remove user from all teams" />
                    </BaseMenu>
                </div>
                <div class="max-w-xl">
                    <VisualFeedback :show-save-success="successSaved" />
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
    <BaseModal @closed="closeChangeTeamsModal" v-if="showChangeTeamsModal" modal-image="/Svgs/Overlays/illu_team_user.svg">
        <div class="mx-3">
            <div class="headline1 my-2">
                {{$t('Team membership')}}
            </div>
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
                                   class="mr-3 input-checklist"/>
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
                <FormButton @click="saveNewTeams" :text="$t('Save')" :disabled="userForm.processing"/>
            </div>
        </div>
    </BaseModal>
    <BaseModal @closed="closeChangePictureModal" v-if="showChangePictureModal" modal-image="/Svgs/Overlays/illu_team_user.svg" :show-image="false">
        <div class="mx-4">
            <div class="font-bold font-lexend text-primary text-2xl my-2">
                {{ $t('Change profile picture')}}
            </div>
            <span class="text-secondary my-auto">
                    {{ $t('Select your profile picture here. It should not exceed the size of 3072 KB.')}}
                </span>
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
                <div class="flex mt-4 gap-3" :class="photoPreview ? 'ml-3' : ''">
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
    </BaseModal>
    <SuccessModal
        v-if="showSuccessModal"
        :title="$t('User successfully edited')"
        :description="$t('The changes have been saved successfully.')"
        :button="$t('Ok')"
        @closed="closeSuccessModal"
    />


</template>

<script>

import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import SecondaryButton from "@/Jetstream/SecondaryButton.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SaveChatKeyButton from "@/Pages/Users/Components/SaveChatKeyButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

export default {
    components: {
        BaseTextarea,
        BaseInput,
        SaveChatKeyButton,
        BaseMenuItem,
        VisualFeedback,
        TextareaComponent,
        TextInputComponent,
        BaseModal,
        BaseMenu,
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
        'departments',
        'calendar_settings'
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
            successSaved: false,
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
                pronouns: this.user_to_edit.pronouns,
                departments: this.user_to_edit.departments,
                phone_number: this.user_to_edit.phone_number,
                description: this.user_to_edit.description,
                language: this.user_to_edit.language,
                email_private: this.user_to_edit.email_private,
                phone_private: this.user_to_edit.phone_private,
                high_contrast: this.calendar_settings.high_contrast,
                use_chat: this.user_to_edit.use_chat,
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
                { id: 'en', name: this.$t('English') },
                { id: 'de', name: this.$t('German') },

            ],
            // set the default selected language to the user's language
            selectedLanguage: null
        }
    },
    mounted() {
        this.selectedLanguage = this.languages.find(language => language.id === this.user_to_edit.language);
    },
    methods: {
        IconTrash,
        IconEdit,
        isSignedInUser() {
            return this.user_to_edit.id === this.$page.props.auth.user.id;
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
                    preserveState: true,
                    onSuccess: () => {
                        this.successSaved = true;
                        setTimeout(() => this.successSaved = false, 2000);
                        //this.openSuccessModal()
                    }
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
            if (this.$refs.photo.files[0]) {
              this.changeProfilePicture()
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
