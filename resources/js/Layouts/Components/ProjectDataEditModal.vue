<template>
    <ArtworkBaseModal
        v-if="show"
        title="Edit basic data"
        modal-size="sm:max-w-xl"
        @close="closeModal(false)"
    >
        <form id="projectDataEditForm" class="space-y-4" @submit.prevent="updateProjectData">
            <BaseInput
                id="projectDataEditName"
                v-model="name"
                label="Project name"
                required
            />

            <div>
                <ArtworkBaseListbox
                    v-model="selectedStateOption"
                    :items="states ?? []"
                    by="id"
                    option-label="name"
                    label="Project status"
                    placeholder="Select project status"
                    show-color-indicator
                    color-property="color"
                >
                    <template #label>
                        {{ $t('Project status') }}<span v-if="isStateRequired" class="text-danger"> *</span>
                    </template>
                </ArtworkBaseListbox>
                <p v-if="showStateRequiredError" class="mt-1 text-[11.5px] text-danger">
                    {{ $t('Project status is a required field.') }}
                </p>
            </div>

            <div v-if="createSettings?.managers">
                <UserSearch label="Project management" @user-selected="addUserToProject"/>
                <ul v-if="assignedUsers.length > 0" class="mt-2 divide-y divide-border-subtle rounded-lg border border-border-subtle">
                    <li
                        v-for="(user, index) in assignedUsers"
                        :key="user.id"
                        class="flex items-center justify-between gap-x-3 px-3 py-2"
                    >
                        <div class="flex min-w-0 items-center gap-x-2 text-sm text-text">
                            <img :src="user.profile_photo_url" alt="" class="size-8 shrink-0 rounded-full object-cover">
                            <span class="truncate">{{ user.full_name }}</span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                            :aria-label="$t('Remove user')"
                            @click="removeUserFromProject(index)"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </li>
                </ul>
            </div>

            <BaseInput
                v-if="createSettings?.cost_center"
                id="projectDataEditCostCenter"
                v-model="cost_center"
                label="Name of the cost unit"
            />

            <div v-if="!project.is_group || hasGroup" class="space-y-2">
                <BaseCheckbox
                    v-if="!project.is_group"
                    id="projectDataEditHasGroup"
                    v-model="hasGroup"
                    :label="$t('Belongs to project group')"
                    @change="removeSelectedGroup"
                />
                <ArtworkBaseListbox
                    v-if="hasGroup"
                    v-model="selectedGroup"
                    :items="groupProjects ?? []"
                    by="id"
                    option-label="name"
                    label="Project group"
                    placeholder="Search project group"
                    :empty-text="$t('No project group has been created yet')"
                />
            </div>

            <div>
                <div class="mb-1 flex items-center gap-1.5 font-lexend text-xs font-medium text-text-muted">
                    <label for="projectDataEditBudgetDeadline">{{ $t('Budget deadline') }}</label>
                    <ToolTipComponent
                        :tooltip-text="$t('This date is currently only relevant for the budget export by deadline in the project overview, to determine the point in time for which the budget is relevant.')"
                        direction="right"
                        icon="IconInfoCircle"
                        icon-size="h-4 w-4"
                    />
                </div>
                <BaseInput
                    id="projectDataEditBudgetDeadline"
                    v-model="budgetDeadline"
                    type="date"
                    label="Budget deadline"
                    :show-label="false"
                />
            </div>

            <div>
                <div class="mb-1 font-lexend text-xs font-medium text-text-muted">{{ $t('Key Visual') }}</div>
                <input
                    id="projectDataEditKeyVisual"
                    ref="keyVisual"
                    name="file-upload"
                    type="file"
                    class="sr-only"
                    accept="image/jpeg,image/svg+xml,image/png,image/gif"
                    @change="updateKeyVisual"
                />
                <div
                    v-if="project.key_visual_path === null"
                    role="button"
                    tabindex="0"
                    class="flex min-h-28 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDraggingKeyVisual ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewKeyVisual"
                    @keydown.enter.prevent="selectNewKeyVisual"
                    @keydown.space.prevent="selectNewKeyVisual"
                    @dragenter.prevent="isDraggingKeyVisual = true"
                    @dragover.prevent="isDraggingKeyVisual = true"
                    @dragleave="isDraggingKeyVisual = $event.currentTarget.contains($event.relatedTarget)"
                    @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                >
                    <PropertyIcon name="IconPhotoPlus" class="size-6 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <p class="text-sm text-text-muted">
                        <template v-if="isDraggingKeyVisual">{{ $t('Drop here') }}</template>
                        <template v-else>
                            <span class="font-semibold text-accent-600">{{ $t('Choose file') }}</span>
                            {{ $t('or drag and drop') }}
                        </template>
                    </p>
                </div>
                <div v-else class="space-y-2">
                    <img
                        :src="'/storage/keyVisual/' + project.key_visual_path"
                        :alt="$t('Key Visual')"
                        class="max-h-96 w-full rounded-lg border border-border-subtle bg-surface-sunken object-contain"
                    >
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <BaseUIButton
                            label="Download"
                            variant="ghost"
                            size="sm"
                            icon="IconDownload"
                            @click="downloadKeyVisual"
                        />
                        <BaseUIButton
                            label="Replace"
                            variant="secondary"
                            size="sm"
                            icon="IconEdit"
                            @click="selectNewKeyVisual"
                        />
                        <BaseUIButton
                            label="Delete"
                            variant="danger"
                            size="sm"
                            icon="IconTrash"
                            @click="deleteKeyVisual"
                        />
                    </div>
                </div>
                <p v-if="uploadKeyVisualFeedback" class="mt-1 text-[11.5px] text-danger">
                    {{ uploadKeyVisualFeedback }}
                </p>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="projectDataEditForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {router, useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

export default {
    mixins: [
        Permissions
    ],
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        project: Object,
        groupProjects: Array,
        currentGroup: Object|String,
        states: Array,
        createSettings: Object
    },
    emits: ['closed'],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        BaseInput,
        BaseCheckbox,
        UserSearch,
        PropertyIcon,
        ToolTipComponent,
    },
    watch: {
        selectedState(state) {
            if (state) {
                this.showStateRequiredError = false;
            }
        },
    },
    data() {
        return {
            name: this.project?.name ?? '',
            description: this.project?.description,
            hasGroup: !!this.currentGroup,
            selectedGroup: this.currentGroup,
            selectedState: this.project?.state?.id,
            budgetDeadline: this.project.budget_deadline,
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            uploadKeyVisualFeedback: "",
            assignedUsers: this.project.users.filter(user => user.pivot.is_manager),
            cost_center: this.project.cost_center ? this.project.cost_center.name : "",
            showStateRequiredError: false,
            processing: false,
            isDraggingKeyVisual: false,
        }
    },
    computed: {
        isStateRequired() {
            return !!(this.createSettings?.state && this.createSettings?.state_required);
        },
        canSubmit() {
            return !this.processing && !!this.name && this.name.length > 0;
        },
        /**
         * Die Listbox arbeitet mit Objekten, gespeichert wird die Status-ID. Eine Auswahl
         * schreibt den Status wie bisher sofort fest.
         */
        selectedStateOption: {
            get() {
                return this.states?.find(state => state.id === this.selectedState) ?? null;
            },
            set(state) {
                const previousStateId = this.selectedState;
                this.selectedState = state?.id ?? null;
                if (state && state.id !== previousStateId) {
                    this.updateProjectState(state);
                }
            },
        },
    },
    methods: {
        updateProjectData() {
            if (!this.canSubmit) {
                return;
            }
            if (this.isStateRequired && !this.selectedState) {
                this.showStateRequiredError = true;
                return;
            }
            this.processing = true;
            router.patch(route('projects.update', {project: this.project.id}), {
                name: this.name,
                selectedGroup: this.selectedGroup,
                budget_deadline: this.budgetDeadline,
                // immer explizit senden (undefined würde den Key strippen und die Backend-Pflicht umgehen)
                state: this.selectedState ?? null,
                assignedUsers: this.assignedUsers.map(user => user.id),
                cost_center: this.cost_center,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal(true);
                },
                onFinish: () => {
                    this.processing = false;
                },
            })
        },
        closeModal(bool) {
            if (this.processing && !bool) {
                return;
            }
            this.$emit('closed', bool);
        },
        addUserToProject(user) {
            if (!this.assignedUsers.some(assignedUser => assignedUser.id === user.id)) {
                this.assignedUsers.push(user);
            }
        },
        removeUserFromProject(index) {
            this.assignedUsers.splice(index, 1);
        },
        removeSelectedGroup() {
            if (!this.hasGroup) {
                this.selectedGroup = null;
            }
        },
        updateProjectState(state) {
            router.patch(route('update.project.state', this.project.id), {
                state: state.id
            }, {
                preserveState: true,
                preserveScroll: true
            })
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual(event) {
            const file = this.$refs.keyVisual.files[0];
            if (file) {
                this.validateTypeAndUploadKeyVisual(file);
            }
            // Gleiche Datei erneut wählbar machen
            event.target.value = '';
        },
        uploadDraggedKeyVisual(event) {
            this.isDraggingKeyVisual = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                this.validateTypeAndUploadKeyVisual(file);
            }
        },
        validateTypeAndUploadKeyVisual(file) {
            this.uploadKeyVisualFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {
                this.keyVisualForm.keyVisual = file;
                this.keyVisualForm.post(
                    route('projects_key_visual.update', {project: this.project.id}),
                    {
                        onError: error => {
                            this.uploadKeyVisualFeedback = error.keyVisual;
                        }
                    }
                );
            } else {
                this.uploadKeyVisualFeedback = this.$t(
                    'Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.'
                );
            }
        },
        downloadKeyVisual() {
            let link = document.createElement('a');
            link.href = route('project.download.keyVisual', this.project.id);
            link.target = '_blank';
            link.click();
        },
        deleteKeyVisual() {
            router.delete(route('project.delete.keyVisual', this.project.id))
        },
    }
}
</script>
