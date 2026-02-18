<<template>
    <div class="">
        <!-- HERO / HEADER -->
        <header class="relative overflow-hidden rounded-3xl border border-zinc-200 bg-gradient-to-r from-blue-600 to-indigo-600 shadow-sm">
            <div class="absolute inset-0 opacity-10">
                <div class="h-full w-full bg-[radial-gradient(ellipse_at_top_right,white,transparent_55%)]"></div>
            </div>

            <div class="relative z-10 flex flex-col gap-6 px-6 py-8 sm:flex-row sm:items-end sm:justify-between sm:px-10 sm:py-10">
                <div class="flex items-end gap-5">
                    <!-- Avatar -->
                    <button
                        v-if="isSignedInUser() || hasAdminRole()"
                        @click="openChangePictureModal"
                        class="group relative"
                        type="button"
                        aria-label="Change profile picture"
                    >
                        <img
                            :src="user_to_edit.profile_photo_url"
                            :alt="user_to_edit.first_name"
                            class="h-24 w-24 rounded-full object-cover ring-4 ring-white/80 shadow-md"
                        />
                        <span
                            class="absolute -bottom-2 left-1/2 -translate-x-1/2 rounded-full border border-white/60 bg-white/90 px-2.5 py-1 text-[11px] font-medium text-zinc-700 backdrop-blur group-hover:bg-white"
                        >
                          {{ $t('Change') }}
                        </span>
                    </button>

                    <div class="text-white/95">
                        <p class="text-xs uppercase tracking-wider/loose opacity-80">
                            {{ $t('User Profile') }}
                        </p>
                        <h1 class="mt-1 text-2xl font-semibold leading-tight">
                            {{ user_to_edit.first_name }} {{ user_to_edit.last_name }}
                        </h1>
                        <p v-if="user_to_edit.business" class="mt-1 text-sm text-white/80">
                            {{ user_to_edit.business }}
                        </p>
                    </div>
                </div>
                <div class="">
                    <VisualFeedback :show-save-success="successSaved" />
                </div>
            </div>
        </header>

        <!-- NAME CARD (nur eigener Account oder Admin) -->
        <section v-if="isSignedInUser() || hasAdminRole()" class="mt-8">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="mb-5 text-base font-semibold text-zinc-900">
                    {{ $t('Basic information') }}
                </h2>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <BaseInput
                        id="first_name"
                        v-model="userForm.first_name"
                        type="text"
                        :label="$t('First Name')"
                        @focusout="editUser"
                    />
                    <BaseInput
                        id="last_name"
                        v-model="userForm.last_name"
                        type="text"
                        :label="$t('Last Name')"
                        @focusout="editUser"
                    />
                </div>

                <p v-if="hasNameError" class="mt-2 text-sm text-red-600">
                    {{ nameError }}
                </p>
            </div>
        </section>

        <!-- ACCOUNT & KONTAKT -->
        <section class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Account -->
            <div class="lg:col-span-2 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-zinc-900">
                        {{ $t('Account & Contact') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <BaseInput
                        :disabled="!(isSignedInUser() || can('can manage workers'))"
                        :class="isSignedInUser() || can('can manage workers') ? '' : 'bg-zinc-100'"
                        type="text"
                        v-model="userForm.pronouns"
                        :label="$t('Pronouns')"
                        @focusout="editUser"
                        id="pronouns"
                    />

                    <BaseInput
                        :disabled="!(isSignedInUser() || can('can manage workers'))"
                        :class="isSignedInUser() || can('can manage workers') ? '' : 'bg-zinc-100'"
                        type="text"
                        v-model="userForm.business"
                        :label="$t('Company')"
                        @focusout="editUser"
                        id="business"
                    />

                    <BaseInput
                        :disabled="!(isSignedInUser() || can('can manage workers'))"
                        :class="isSignedInUser() || can('can manage workers') ? '' : 'bg-zinc-100'"
                        type="text"
                        v-model="userForm.position"
                        :label="$t('Position')"
                        @focusout="editUser"
                        id="position"
                    />

                    <div>
                        <BaseInput
                            type="text"
                            v-model="user_to_edit.email"
                            :disabled="!hasAdminRole()"
                            :class="hasAdminRole() ? '' : 'bg-zinc-100'"
                            @focusout="editUser"
                            :label="$t('Email')"
                            id="email"
                        />
                        <JetInputError :message="userForm.errors.email" class="mt-2" />
                    </div>

                    <BaseInput
                        :disabled="!(isSignedInUser() || can('can manage workers'))"
                        :class="isSignedInUser() || can('can manage workers') ? '' : 'bg-zinc-100'"
                        type="text"
                        v-model="userForm.phone_number"
                        :label="$t('Phone number')"
                        @focusout="editUser"
                        id="phone_number"
                    />
                </div>

                <div class="mt-6">
                    <BaseTextarea
                        :disabled="!(isSignedInUser() || can('can manage workers'))"
                        :class="isSignedInUser() || can('can manage workers') ? '' : 'bg-zinc-100'"
                        :label="$t('What should other artwork users know?')"
                        v-model="userForm.description"
                        rows="5"
                        @focusout="editUser"
                        id="description"
                    />
                </div>
            </div>

            <!-- Einstellungen -->
            <aside class="space-y-8">
                <!-- Sprache -->
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-7">
                    <h3 class="mb-4 text-base font-semibold text-zinc-900">
                        {{ $t('Preferences') }}
                    </h3>

                    <div v-if="isSignedInUser() || hasAdminRole()" class="mb-5">
                        <Listbox as="div" v-model="selectedLanguage" @update:modelValue="editUser">
                            <ListboxLabel class="block text-sm font-medium text-zinc-800">
                                {{ $t('Application language') }}
                            </ListboxLabel>
                            <div class="relative mt-2">
                                <ListboxButton
                                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 pr-9 text-left text-sm text-zinc-900 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-blue-600"
                                >
                                    <span class="block truncate">{{ selectedLanguage?.name }}</span>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                  </span>
                                </ListboxButton>

                                <transition
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions
                                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl bg-white py-1 text-base shadow-lg ring-1 ring-black/10 focus:outline-none sm:text-sm"
                                    >
                                        <ListboxOption
                                            as="template"
                                            v-for="language in languages"
                                            :key="language.id"
                                            :value="language"
                                            v-slot="{ active, selected }"
                                        >
                                            <li
                                                :class="[
                          active ? 'bg-blue-600 text-white' : 'text-zinc-900',
                          'relative cursor-default select-none py-2 pl-3 pr-9'
                        ]"
                                            >
                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                          {{ language.name }}
                        </span>
                                                <span
                                                    v-if="selected"
                                                    :class="[active ? 'text-white' : 'text-blue-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                                >
                          <CheckIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>

                    <!-- Toggles/Checkboxes -->
                    <div class="space-y-5">
                        <label class="flex gap-3">
                            <input
                                id="high_contrast"
                                v-model="userForm.high_contrast"
                                @change="editUser"
                                name="high_contrast"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                            />
                            <span>
                <span class="block text-sm font-medium text-zinc-900">{{ $t('High contrast') }}</span>
                <span class="block text-sm text-zinc-600">
                  {{ $t('Enable high contrast mode in the application for the event type colors.') }}
                </span>
              </span>
                        </label>

                        <label class="flex gap-3">
                            <input
                                id="email_private"
                                v-model="userForm.email_private"
                                @change="editUser"
                                name="email_private"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                            />
                            <span>
                <span class="block text-sm font-medium text-zinc-900">{{ $t('Email private') }}</span>
                <span class="block text-sm text-zinc-600">
                  {{ $t('Hide your email address from other users.') }}
                </span>
              </span>
                        </label>

                        <label class="flex gap-3">
                            <input
                                id="phone_private"
                                v-model="userForm.phone_private"
                                @change="editUser"
                                name="phone_private"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                            />
                            <span>
                            <span class="block text-sm font-medium text-zinc-900">{{ $t('Phone private') }}</span>
                            <span class="block text-sm text-zinc-600">
                              {{ $t('Hide your phone number from other users.') }}
                            </span>
                          </span>
                        </label>

                        <label class="flex gap-3">
                            <input
                                id="use_chat"
                                v-model="userForm.use_chat"
                                @change="editUser"
                                name="use_chat"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                            />
                            <span>
                            <span class="block text-sm font-medium text-zinc-900">{{ $t('Use Artwork Chat') }}</span>
                            <span class="block text-sm text-zinc-600">
                              {{ $t('Use the chat function in the application.') }}
                            </span>
                          </span>
                        </label>
                    </div>
                </div>

                <!-- Passwort / Admin -->
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-7">
                    <h3 class="mb-3 text-base font-semibold text-zinc-900">
                        {{ $t('Security') }}
                    </h3>
                    <div class="flex items-center justify-between gap-x-8">
                        <div class="text-sm text-zinc-600">
                            {{ $t('Reset your password or send a reset email.') }}
                        </div>
                        <button
                            v-if="hasAdminRole()"
                            @click="resetPassword"
                            class="ui-button"
                        >
                            {{ $t('Reset Password') }}
                        </button>
                    </div>
                    <div v-if="password_reset_status" class="mt-3 text-sm font-medium text-green-600">
                        {{ password_reset_status }}
                    </div>
                </div>
            </aside>
        </section>

        <!-- TEAMS -->
        <section class="mt-8 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">{{ $t('Teams') }}</h3>
                    <p class="mt-1 text-sm text-zinc-600" v-if="userForm.departments.length === 0">
                        {{ $t('Not in any team') }}
                    </p>
                </div>

            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <template v-for="(team, index) in userForm.departments" :key="team.id || index">
                    <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5">
                        <img
                            class="h-8 w-8 rounded-full ring-2 ring-white"
                            :src="`/Svgs/TeamIconSvgs/${team.svg_name}.svg`"
                            :alt="team.name"
                        />
                        <span class="text-sm text-zinc-800">{{ team.name }}</span>
                    </div>
                </template>

                <button
                    v-if="can('teammanagement') || hasAdminRole()"
                    @click="openChangeTeamsModal"
                    class="ui-button"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('Edit team membership') }}
                </button>

                <button
                    v-if="can('teammanagement') && userForm.departments.length || hasAdminRole()"
                    @click="deleteFromAllDepartments"
                    class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                >
                    <TrashIcon class="h-4 w-4" />
                    {{ $t('Remove user from all teams') }}
                </button>
            </div>
        </section>

        <!-- MODALS -->
        <BaseModal
            @closed="closeChangeTeamsModal"
            v-if="showChangeTeamsModal"
            modal-image="/Svgs/Overlays/illu_team_user.svg"
        >
            <div class="mx-3">
                <div class="text-2xl font-bold text-zinc-900 my-2">
                    {{ $t('Team membership') }}
                </div>
                <div class="mt-2 text-sm text-zinc-600">
                    {{ $t('Specify which teams the user is in. Note: He/she has authorization to view all projects assigned to the teams. Projects assigned to the teams.') }}
                </div>

                <div class="mt-6 mb-8 space-y-2">
          <span v-if="deptLocal.length === 0" class="text-zinc-600 flex my-6">
            {{ $t('No teams have been created in the tool yet.') }}
          </span>

                    <div v-for="team in deptLocal" :key="team.id" class="rounded-xl border border-zinc-200 px-3 py-2">
                        <label :for="`dept-${team.id}`" class="flex items-center justify-between cursor-pointer">
                            <div class="flex items-center gap-3">
                                <input
                                    :id="`dept-${team.id}`"
                                    type="checkbox"
                                    v-model="team.checked"
                                    @change="teamChecked(team)"
                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                />
                                <TeamIconCollection class="h-9 w-9 rounded-full ring-2 ring-white" :iconName="team.svg_name" />
                                <span :class="team.checked ? 'text-zinc-900 font-medium' : 'text-zinc-600'">
                  {{ team.name }}
                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="w-full items-center text-center">
                    <BaseUIButton @click="saveNewTeams" :label="$t('Save')" is-add-button :disabled="userForm.processing" />
                </div>
            </div>
        </BaseModal>

        <BaseModal
            @closed="closeChangePictureModal"
            v-if="showChangePictureModal"
            modal-image="/Svgs/Overlays/illu_team_user.svg"
            :show-image="false"
        >
            <div class="mx-4">
                <div class="text-2xl font-bold text-zinc-900 my-2">
                    {{ $t('Change profile picture') }}
                </div>
                <p class="text-sm text-zinc-600">
                    {{ $t('Select your profile picture here. It should not exceed the size of 3072 KB.') }}
                </p>

                <!-- Hidden file input -->
                <input ref="photoRef" type="file" class="hidden" @change="updatePhotoPreview" />

                <!-- Preview + Buttons -->
                <div class="mt-4 flex items-start gap-4">
                    <div v-show="photoPreview" class="rounded-full ring-2 ring-zinc-200">
            <span
                class="block h-20 w-20 rounded-full bg-cover bg-no-repeat bg-center"
                :style="`background-image: url('${photoPreview}');`"
            />
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <BaseButton :text="$t('Select file')" @click.prevent="selectNewPhoto" />
                        <BaseButton
                            v-if="user_to_edit.profile_photo_url"
                            @click.prevent="deletePhoto"
                            :text="$t('Delete current profile picture')"
                        />
                    </div>
                </div>

                <JetInputError :message="updateProfilePictureFeedback" class="mt-2" />
                <JetInputError :message="userForm.errors.photo" class="mt-2" />

                <div class="mt-6">
                    <BaseButton :text="$t('Save new profile picture')" @click="validateTypeAndChange" />
                </div>
            </div>
        </BaseModal>

        <!-- SUCCESS DIALOG -->
        <SuccessModal
            v-if="showSuccessModal"
            :title="$t('User successfully edited')"
            :description="$t('The changes have been saved successfully.')"
            :button="$t('Ok')"
            @closed="closeSuccessModal"
        />
    </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted, getCurrentInstance } from 'vue'
import {router, useForm, usePage} from '@inertiajs/vue3'
import {
    Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
} from '@headlessui/vue'
import { CheckIcon, ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon } from '@heroicons/vue/outline'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import BaseButton from '@/Layouts/Components/General/Buttons/BaseButton.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import VisualFeedback from '@/Components/Feedback/VisualFeedback.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import SaveChatKeyButton from '@/Pages/Users/Components/SaveChatKeyButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import { IconEdit, IconTrash } from '@tabler/icons-vue'
import {is, can} from "laravel-permission-to-vuejs";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    user_to_edit: { type: Object, required: true },
    password_reset_status: { type: String, default: '' },
    departments: { type: Array, default: () => [] },
    calendar_settings: { type: Object, default: () => ({}) },
})

/* ----- Permissions helpers (ersetzt mixin) ----- */
const { proxy } = getCurrentInstance()
const page = usePage()
const hasAdminRole = () => (is('artwork admin'))
const isSignedInUser = () => props.user_to_edit?.id === page.props?.auth?.user?.id

/* ----- UI State ----- */
const successSaved = ref(false)
const showChangeTeamsModal = ref(false)
const showSuccessModal = ref(false)
const nameError = ref('')
const hasNameError = ref(false)
const updateProfilePictureFeedback = ref('')
const photoPreview = ref(null)
const showChangePictureModal = ref(false)
const photoRef = ref(null)

/* ----- Forms ----- */
const userForm = useForm({
    first_name: props.user_to_edit.first_name,
    last_name: props.user_to_edit.last_name,
    email: props.user_to_edit.email,
    photo: props.user_to_edit.profile_photo_path,
    position: props.user_to_edit.position,
    business: props.user_to_edit.business,
    pronouns: props.user_to_edit.pronouns,
    departments: props.user_to_edit.departments || [],
    phone_number: props.user_to_edit.phone_number,
    description: props.user_to_edit.description,
    language: props.user_to_edit.language,
    email_private: props.user_to_edit.email_private,
    phone_private: props.user_to_edit.phone_private,
    high_contrast: props.calendar_settings?.high_contrast,
    use_chat: props.user_to_edit.use_chat,
})

const resetPasswordForm = useForm({
    email: props.user_to_edit.email,
})

/* ----- Languages ----- */
const languages = computed(() => ([
    { id: 'en', name: proxy.$t('English') },
    { id: 'de', name: proxy.$t('German') },
]))
const selectedLanguage = ref(null)

onMounted(() => {
    selectedLanguage.value = languages.value.find(l => l.id === props.user_to_edit.language) || languages.value[0]
    if (selectedLanguage.value) {
        document.documentElement.lang = selectedLanguage.value.id
    }
})

/* ----- Departments (lokale Kopie fürs Modal) ----- */
const deptLocal = reactive((props.departments || []).map(d => ({ ...d, checked: false })))

/* ----- Actions ----- */
const openChangeTeamsModal = () => {
    // Synchronisieren: markiere Teams, die der User hat
    deptLocal.forEach(team => {
        team.checked = !!userForm.departments.find(t => t.id === team.id)
    })
    showChangeTeamsModal.value = true
}

const closeChangeTeamsModal = () => {
    showChangeTeamsModal.value = false
}

const teamChecked = (team) => {
    if (team.checked) {
        // hinzufügen falls nicht drin
        if (!userForm.departments.find(t => t.id === team.id)) {
            userForm.departments.push({ id: team.id, name: team.name, svg_name: team.svg_name })
        }
    } else {
        const i = userForm.departments.findIndex(t => t.id === team.id)
        if (i !== -1) userForm.departments.splice(i, 1)
    }
}

const saveNewTeams = () => {
    userForm.patch(route('user.update', props.user_to_edit.id), {
        onSuccess: () => {
            closeChangeTeamsModal()
            openSuccessModal()
        },
    })
}

const deleteFromAllDepartments = () => {
    userForm.departments = []
    userForm.patch(route('user.update', props.user_to_edit.id), {
        onSuccess: openSuccessModal,
    })
}

const openSuccessModal = () => {
    showSuccessModal.value = true
    setTimeout(() => closeSuccessModal(), 2000)
}
const closeSuccessModal = () => (showSuccessModal.value = false)

/* ----- Edit User ----- */
const editUser = () => {
    userForm.language = selectedLanguage.value?.id
    if (proxy?.$updateLocale && userForm.language) {
        proxy.$updateLocale(userForm.language)
    }
    if (hasAdminRole()) {
        // Email bleibt aus Eingabefeld (bereits in user_to_edit), aber Patch erwartet userForm.email
        userForm.email = props.user_to_edit.email
    }

    if (!userForm.first_name || !userForm.last_name) {
        nameError.value = proxy.$t('First name and surname are required')
        hasNameError.value = true
        return
    }

    userForm.patch(route('user.update', { user: props.user_to_edit.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            successSaved.value = true
            setTimeout(() => (successSaved.value = false), 2000)
        },
    })
    nameError.value = ''
    hasNameError.value = false
}

/* ----- Reset password ----- */
const resetPassword = () => {
    resetPasswordForm.post(route('user.reset.password'))
}

/* ----- Photo handling ----- */
const openChangePictureModal = () => (showChangePictureModal.value = true)
const closeChangePictureModal = () => (showChangePictureModal.value = false)

const selectNewPhoto = () => photoRef.value?.click()

const updatePhotoPreview = () => {
    const file = photoRef.value?.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = (e) => {
        const result = e.target?.result || ''
        if (typeof result === 'string' && (result.includes('data:image/png') || result.includes('data:image/jpeg'))) {
            photoPreview.value = result
        } else {
            updateProfilePictureFeedback.value = proxy.$t('Only .png and .jpeg files are supported')
        }
    }
    reader.readAsDataURL(file)
}

const validateTypeAndChange = () => {
    updateProfilePictureFeedback.value = ''
    if (photoRef.value?.files?.[0]) {
        changeProfilePicture()
    } else {
        closeChangePictureModal()
    }
}

const changeProfilePicture = () => {
    if (photoRef.value?.files?.[0]) {
        userForm.photo = photoRef.value.files[0]
    }
    userForm.post(route('user.update.photo', props.user_to_edit.id), {
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput()
            closeChangePictureModal()
        },
    })
}

const deletePhoto = () => {
    // Jetstream-Route
    router.delete
        ? router.delete(route('current-user-photo.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                photoPreview.value = null
                clearPhotoFileInput()
            },
        })
        : null
}

const clearPhotoFileInput = () => {
    if (photoRef.value?.value != null) photoRef.value.value = null
}

</script>
