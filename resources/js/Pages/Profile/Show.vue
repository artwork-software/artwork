<template>
    <app-layout title="Profile">
        <div>
            <div class="max-w-screen-xl py-4 pl-12 pr-4">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="updateProfileInformation">
                        <div>
                            <div>
                                <h2 class="font-bold font-lexend text-2xl my-2">Dein Konto</h2>
                                <div class="col-span-6 sm:col-span-4">
                                    <!-- Profile Photo File Input -->
                                    <input type="file" class="hidden"
                                           ref="photo"
                                           @change="updatePhotoPreview">

                                    <div class="mt-1 flex w-full items-center">
                                        <!-- Current Profile Photo -->

                                        <div @click="openChangePictureModal" class="mt-2">
                                            <img :src="user.profile_photo_url" :alt="user.first_name"
                                                 class="rounded-full h-20 w-20 object-cover cursor-pointer">
                                        </div>
                                        <div class="mt-6 flex flex-grow w-full">
                                            <div class="relative flex w-5/12 ml-12 mr-4">
                                                <input id="first_name" v-model="userForm.first_name" type="text" class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                                <label for="first_name" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                                            </div>
                                            <div class="relative flex-grow w-6/12">
                                                <input id="last_name" v-model="userForm.last_name" type="text" class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                                <label for="last_name" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Nachname</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                            <div>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business" placeholder="Unternehmen"
                                                   class="text-primary focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position" placeholder="Position"
                                                   class=" text-primary  focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.email" placeholder="E-Mail-Adresse"
                                                   class="text-primary focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                   placeholder="Telefonnummer"
                                                   class="text-primary focus:border-primary border-2 w-full font-semibold border-gray-300 "/>
                                        </div>
                                    </div>


                                    <div class="sm:col-span-6">
                                        <div class="mt-1">
                                            <textarea
                                                placeholder="Was sollten die anderen ArtWork.tool-User über dich wissen?"
                                                v-model="userForm.description" rows="3"
                                                class="focus:border-primary border-2 w-full font-semibold border border-gray-300 "/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <div class="sm:col-span-6 ml-3 flex inline-flex">
                                            <span v-if="userForm.departments.length === 0"
                                                  class="text-secondary subpixel-antialiased my-auto">In keinem Team </span>
                                            <span v-else class="flex mt-3 -ml-3"
                                                  v-for="(team,index) in userForm.departments">
                                            <!--TODO: :src="team.logo_url" -->
                                            <img class="h-14 w-14 rounded-full flex justify-start ring-2 ring-white"
                                                 src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                 alt=""/>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-5">
                            <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 items-center sm:grid-cols-6">
                                <button v-if="!showSuccess" type="submit"
                                        class="sm:col-span-3 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                >Profil-Änderungen speichern
                                </button>
                                <button v-else type="submit"
                                        class=" sm:col-span-3 items-center py-1.5 border bg-success focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                >
                                    <CheckIcon class="h-10 w-9 inline-block text-secondaryHover"/>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <h2 class="font-bold font-lexend text-xl my-2 mt-12">Dein Passwort</h2>
                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <div class="mt-1">
                                <input
                                    v-model="passwordForm.current_password"
                                    id="password" name="password" type="password" autocomplete="new-password" required placeholder="Aktuelles Passwort"
                                    class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                            </div>

                        </div>
                        <div class="sm:col-span-3">
                            <!-- TODO: HIER PASSWORTBALKEN + HINWEIS EINBAUEN -->
                        </div>
                        <div class="sm:col-span-3">
                            <div class="mt-1">
                                <input
                                    v-model="passwordForm.password"
                                    id="password_confirmation1" name="password" type="password" autocomplete="new-password" required placeholder="Neues Passwort"
                                    class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <div class="mt-1">
                            <input
                                v-model="passwordForm.password_confirmation"
                                id="password_confirmation2" name="password" type="password" autocomplete="new-password" required placeholder="Neues Passwort wiederholen"
                                class="shadow-sm placeholder-secondary subpixel-antialiased focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-200"/>
                        </div>
                        <jet-input-error :message="passwordForm.errors.password_confirmation" class="mt-2"/>
                    </div>

                    <jet-action-message :on="passwordForm.recentlySuccessful" class="mr-3">
                        Saved.
                    </jet-action-message>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6">
                    <button @click="updatePassword"
                            class="sm:col-span-3 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    > Passwort ändern
                    </button>
                    </div>


                </div>
            </div>
            <div class="flex ml-12 mt-12">
                <span @click="openDeleteUserModal()" class="text-secondary subpixel-antialiased cursor-pointer">Konto endgültig löschen</span>
            </div>
        </div>
            <!-- Change Profile Picture Modal -->

        <jet-dialog-modal :show="showChangePictureModal" @close="closeChangePictureModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Profilbild ändern
                    </div>
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
                        <div class="flex mt-4">
                            <button
                                class="mr-1 my-auto ml-3 inline-flex items-center px-3 py-3 text-base border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-sm uppercase shadow-sm text-secondaryHover"
                                type="button"
                                @click.prevent="selectNewPhoto">
                                Profilbild ändern
                            </button>
                            <button type="button"
                                    class=" ml-3 my-auto inline-flex items-center px-3 py-3 text-base border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-sm uppercase shadow-sm text-secondaryHover"
                                    @click.prevent="deletePhoto"
                                    v-if="user.profile_photo_path">
                                Aktuelles Profilbild löschen
                            </button>
                        </div>
                    </div>

                    <jet-input-error :message="userForm.errors.photo" class="mt-2"/>
                    <div class="mt-6">
                        <button
                            class="inline-flex items-center px-8 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-lg uppercase shadow-sm text-secondaryHover"
                            @click="changeProfilePicture">
                            Neues Profilbild speichern
                        </button>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Konto endgültig löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal" class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-error">
                        Bist du sicher, dass du dein ArtWork-Konto endgültig löschen möchtest? Sämtliche Einstellungen gehen verloren.
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteUser">
                            Konto Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()" class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
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


export default defineComponent({
    components: {
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
    },
    props: ['user', 'all_departments','user_departments'],
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
                password_confirmation: '',
            }),
            photoPreview: null,
            showSuccess: false,
            showChangePictureModal: false,
            deletingUser: false,
        }
    },

    methods: {
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal(){
            this.deletingUser = false;
        },
        deleteUser(){
            Inertia.delete(`/users/${this.user.id}`);
            this.closeDeleteUserModal()
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
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        selectNewPhoto() {
            this.$refs.photo.click();
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
                this.photoPreview = e.target.result;
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
                onSuccess: () => (this.passwordForm.reset(), this.openSuccessModal('password')),
                onError: () => {
                    if (this.passwordForm.errors.password) {
                        this.passwordForm.reset('password', 'password_confirmation')
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
