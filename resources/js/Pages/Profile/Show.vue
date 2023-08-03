<template>
    <app-layout>
        <div>
            <div class="max-w-screen-lg py-4 pl-20 pr-4">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="updateProfileInformation">
                        <div>
                            <div>
                                <h2 class="headline1 my-2">Dein Konto</h2>
                                <div class="col-span-6 sm:col-span-4">
                                    <!-- Profile Photo File Input -->
                                    <input type="file" class="hidden"
                                        ref="photo"
                                        @change="updatePhotoPreview">
                                    <div class="mt-1 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                        <div class="sm:col-span-3 flex items-end">

                                            <div @click="openChangePictureModal" class="mt-2">
                                                <img :src="user.profile_photo_url" :alt="user.first_name"
                                                    class="rounded-full h-20 w-20 object-cover cursor-pointer">
                                            </div>

                                            <div class="mt-1 ml-5 flex-grow relative">
                                                <input id="first_name" v-model="userForm.first_name" type="text"
                                                    class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-black focus:ring-black focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                                    placeholder="placeholder"/>
                                                <label for="first_name"
                                                    class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-3 flex items-end">
                                            <div class="relative mt-1 w-full">
                                                <input id="last_name" v-model="userForm.last_name" type="text"
                                                    class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-black focus:ring-black focus:ring-0 border-l-0 border-t-0 border-r-0
                                                   border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                                    placeholder="placeholder"/>
                                                <label for="last_name"
                                                    class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased
                                                   focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Nachname</label>
                                            </div>
                                        </div>

                                    </div>
                                    <div v-if="hasNameError" class="text-error mt-1">{{ nameError }}</div>

                                </div>

                            </div>
                            <div>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business" placeholder="Unternehmen"
                                                class="text-primary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border-gray-300 "/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position" placeholder="Position"
                                                class="text-primary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border-gray-300"/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1 relative">
                                            <input type="email" v-model="userForm.email" placeholder="E-Mail-Adresse"
                                                :class="[email_validation_classes,'text-primary border-2 w-full font-semibold focus:outline-none focus:ring-0 focus:border-secondary focus:border-1']"/>

                                            <div v-if="!email_validation.email"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <CheckIcon class="h-5 w-5 text-success" aria-hidden="true"/>
                                            </div>
                                            <div v-if="email_validation.email && email_validation.email.length > 0"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <XIcon class="h-5 w-5 text-error" aria-hidden="true"/>
                                            </div>
                                        </div>
                                        <jet-input-error :message="email_validation.email && email_validation.email[0]"
                                            class="mt-2"/>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                placeholder="Telefonnummer"
                                                class="text-primary border-2 w-full focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 font-semibold border-gray-300 "/>
                                        </div>
                                    </div>


                                    <div class="sm:col-span-6">
                                        <div class="mt-1">
                                            <textarea
                                                placeholder="Was sollten die anderen artwork-User über dich wissen?"
                                                v-model="userForm.description" rows="3"
                                                class="resize-none focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 w-full font-semibold border border-gray-300 "/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <div class="sm:col-span-6 ml-3 flex inline-flex">
                                            <span v-if="userForm.departments.length === 0"
                                                class="text-secondary my-auto -ml-3">In keinem Team </span>
                                            <span v-else class="flex mt-3 -ml-4"
                                                v-for="(team,index) in userForm.departments">
                                            <TeamIconCollection :data-tooltip-target="team.name" class="h-14 w-14 rounded-full ring-2 ring-white"
                                                :iconName="team.svg_name"/>
                                                <TeamTooltip :team="team"/>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5">
                            <div class="mt-2 items-center">
                                <AddButton v-if="!showSuccess" @click="updateProfileInformation()"
                                    class="mt-8 px-10 py-3"
                                    text="Profiländerungen speichern" mode="modal"
                                />
                                <button v-else type="submit"
                                    class="items-center py-1 mt-5 rounded-full px-28 border bg-success"
                                >
                                    <CheckIcon class="h-10 w-9 inline-block text-secondaryHover"/>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <h2 class="font-bold font-lexend text-xl my-2 mt-12">Dein Passwort</h2>

                    <div class="mt-4 flex flex-col md:flex-row">
                        <div class="w-full md:w-1/2">
                            <div class="mt-1 relative rounded-md ">
                                <input
                                    v-model="passwordForm.current_password"
                                    ref="current_password"
                                    id="password" name="password" type="password" autocomplete="new-password"
                                    required
                                    placeholder="Aktuelles Passwort"
                                    :class="[passwordForm.hasErrors && passwordForm.errors.current_password
                                            ? 'border-error'
                                            : passwordForm.current_password.length > 0 && passwordForm.hasErrors
                                            ? 'border-success' : '',
                                    'placeholder-secondary border-gray-200 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full']"/>
                                <div v-if="passwordForm.hasErrors && passwordForm.errors.current_password"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <XIcon class="h-5 w-5 text-error" aria-hidden="true"/>
                                </div>
                                <div
                                    v-if="!passwordForm.errors.current_password
                                        && passwordForm.current_password.length > 0
                                        && passwordForm.hasErrors"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <CheckIcon class="h-5 w-5 text-success" aria-hidden="true"/>
                                </div>
                            </div>
                            <jet-input-error :message="passwordForm.errors.current_password" class="mt-2"/>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col md:flex-row">
                        <div class="w-full md:w-1/2">
                            <div class="mt-1 relative rounded-md ">
                                <input
                                    v-model="passwordForm.password"
                                    ref="password"
                                    id="password" name="password" type="password"
                                    autocomplete="new-password" required placeholder="Neues Passwort"
                                    :class="[passwordForm.hasErrors ? 'border-error' : 'border-gray-200',
                                    'placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full']"/>
                                <div v-if="passwordForm.hasErrors"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <XIcon class="h-5 w-5 text-error" aria-hidden="true"/>
                                </div>
                            </div>
                            <jet-input-error :message="passwordForm.errors.password" class="mt-2"/>
                        </div>
                        <div class="w-full md:w-1/2 flex items-center p-3">
                            <span class="text-xs text-secondary">Schwach</span>

                            <div class="mx-6 mt-1 w-full bg-gray-200 h-1 dark:bg-gray-700">
                                <div :class="[pw_feedback < 1
                                ? 'bg-error'
                                : pw_feedback < 3
                                ? 'bg-amber-400' :
                                'bg-success' ,
                                'h-1']" :style="{width: `${(pw_feedback + 1) / 5 * 100}%`}"></div>
                            </div>

                            <span class="text-xs">Stark</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center text-secondary w-full">
                            <SvgCollection svgName="arrowTopLeft" class="m-3"/>

                            <div class="hind w-full">
                                Das Passwort muss mind. 10 Zeichen lang sein, mind. 1 Ziffer, Groß- und Kleinbuchstaben
                                und
                                Sonderzeichen beinhalten.
                            </div>
                        </div>

                        <jet-action-message :on="passwordForm.recentlySuccessful" class="mr-3">
                            Saved.
                        </jet-action-message>
                    </div>
                    <AddButton @click="updatePassword"
                        class="px-10 py-3 mt-4"
                        text="Passwort ändern"
                        mode="modal"
                    />
                </div>
            </div>
            <div class="flex ml-20 mt-12">
                <span v-if="$role('artwork admin')" @click="openDeleteUserModal()" class="xsLight cursor-pointer">Konto endgültig löschen</span>
            </div>
        </div>
        <!-- Change Profile Picture Modal -->

        <jet-dialog-modal :show="showChangePictureModal" @close="closeChangePictureModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Profilbild ändern
                    </div>
                    <span class="text-secondary my-auto">Wähle hier dein Profilbild aus. Es sollte die Größe von 1024 KB nicht überschreiten. </span>
                    <XIcon @click="closeChangePictureModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <!-- New Profile Photo Preview -->
                    <h2 class="" v-show="photoPreview">Vorschau neues Profilbild:</h2>
                    <div class="flex">
                        <div class="mt-1 flex items-center">
                            <div class="mt-2" v-show="photoPreview">
                            <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                :style="'background-image: url(\'' + photoPreview + '\');'">
                            </span>
                            </div>
                        </div>
                        <div class="flex mt-4" :class="photoPreview ? 'ml-3' : ''">
                            <AddButton
                                class="my-auto px-3 py-3"
                                text="Datei auswählen"
                                mode="modal"
                                @click.prevent="selectNewPhoto"/>
                            <AddButton
                                class=" ml-3 my-auto px-3 py-3"
                                @click.prevent="deletePhoto"
                                text="Aktuelles Profilbild löschen"
                                mode="modal"
                                v-if="user.profile_photo_path" />
                        </div>
                    </div>

                    <jet-input-error :message="updateProfilePictureFeedback" class="mt-2"/>

                    <jet-input-error :message="userForm.errors.photo" class="mt-2"/>
                    <div class="mt-6">
                        <AddButton
                            class="px-8 py-3"
                            text="Neues Profilbild speichern"
                            mode="modal"
                            @click="validateTypeAndChange" />
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Konto endgültig löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal"
                        class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                        aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du dein artwork-Konto endgültig löschen möchtest? Sämtliche Einstellungen
                        gehen verloren.
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="deleteUser">
                            Konto Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                class="text-secondary xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>


        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import AppLayout from "@/Layouts/AppLayout";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm";
import JetSectionBorder from "@/Jetstream/SectionBorder";
import LogoutOtherBrowserSessionsForm from "@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm";
import TwoFactorAuthenticationForm from "@/Pages/Profile/Partials/TwoFactorAuthenticationForm";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm";
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {CheckIcon} from "@heroicons/vue/solid";
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import {Inertia} from "@inertiajs/inertia";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import AddButton from "@/Layouts/Components/AddButton";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import Permissions from "@/mixins/Permissions.vue";

export default defineComponent({
    components: {
        TeamTooltip,
        AddButton,
        JetActionMessage,
        JetButton,
        JetFormSection,
        JetInput,
        JetInputError,
        JetLabel,
        AppLayout,
        DeleteUserForm,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
        JetSecondaryButton,
        CheckIcon,
        JetDialogModal,
        XIcon,
        TeamIconCollection,
        SvgCollection
    },
    mixins: [Permissions],
    props: ['user', 'all_departments', 'user_departments'],
    data() {
        return {
            userForm: this.$inertia.form({
                _method: 'PUT',
                business: this.user.business,
                position: this.user.position,
                first_name: this.user.first_name,
                last_name: this.user.last_name,
                departments: this.user_departments,
                phone_number: this.user.phone_number,
                email: this.user.email,
                description: this.user.description,
                photo: this.user.profile_photo_path,
            }),
            passwordForm: this.$inertia.form({
                current_password: '',
                password: '',
            }),
            updateProfilePictureFeedback: "",
            photoPreview: null,
            showSuccess: false,
            showChangePictureModal: false,
            deletingUser: false,
            pw_feedback: 0,
            email_validation: {
                email: true
            },
            nameError: '',
            hasNameError: false,
        }
    },
    computed: {
        email_validation_classes() {
            if (this.email_validation.email) {
                if (this.email_validation.email.length > 0) {
                    return 'border-error';
                } else {
                    return 'border-gray-300'
                }
            } else {
                return 'border-success';
            }
        }
    },
    watch: {
        'passwordForm.password': {
            handler() {
                if (this.passwordForm.password.length > 0) {
                    this.password_feedback()
                }
            },
            deep: true
        },
    },
    methods: {
        password_feedback() {
            axios.get('/password_feedback', {
                params: {
                    password: this.passwordForm.password
                }
            }).then(response => {
                this.pw_feedback = response.data
            })
        },
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.deletingUser = false;
        },
        deleteUser() {
            Inertia.delete(`/users/${this.user.id}`);
            this.closeDeleteUserModal()
        },
        updateProfileInformation() {
            if (!this.userForm.first_name || !this.userForm.last_name) {
                this.nameError = 'Vorname und Nachname sind notwendig';
                this.hasNameError = true;
                return; // Exit the function without making the API call
            }
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
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
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

            if (forbiddenTypes.includes(this.$refs.photo.files[0].type)
                || this.$refs.photo.files[0].type.match('video.*')
                || this.$refs.photo.files[0].type === "") {
                this.updateProfilePictureFeedback = "Es werden nur .png und .jpeg Dateien unterstützt"
            } else {
                this.changeProfilePicture()
            }

        },
        changeProfilePicture() {
            if (this.$refs.photo) {
                this.userForm.photo = this.$refs.photo.files[0]
            }
            this.userForm.post(route('user-profile-information.update'), {
                errorBag: 'updateProfileInformation',
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput(), this.closeChangePictureModal()),
            });

        },
        updatePhotoPreview() {
            const photo = this.$refs.photo.files[0];

            if (!photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                if (e.target.result.includes("data:image/png") || e.target.result.includes("data:image/jpeg")) {
                    this.photoPreview = e.target.result;
                } else {
                    this.updateProfilePictureFeedback = "Es werden nur .png und .jpeg Dateien unterstützt"
                }
            };

            reader.readAsDataURL(photo);
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
        deleteTeamFromDepartmentsArray(index) {
            this.userForm.departments.splice(index, 1);
        },
        updatePassword() {

            this.passwordForm.put(route('user-password.update'), {
                errorBag: 'updatePassword',
                preserveScroll: true,
                onSuccess: () => {
                    this.passwordForm.reset();
                    this.openSuccessModal('password');
                },
                onError: () => {
                    if (this.passwordForm.errors.password) {
                        this.passwordForm.reset('password')
                        this.$refs.password.focus()
                    }

                    if (this.passwordForm.errors.current_password) {
                        this.passwordForm.reset('current_password')
                        this.$refs.current_password.focus()
                    }
                }
            })
        },
    },
})
</script>
