<template>
    <ArtworkBaseModal
        v-if="show"
        :title="mode === 'create' ? 'Create qualification' : 'Edit qualification'"
        :description="descriptionText"
        modal-size="sm:max-w-xl"
        @close="close"
    >
        <form id="shiftQualificationForm" class="space-y-4" @submit.prevent="save">
            <div class="flex items-end gap-4">
                <IconSelector
                    :current-icon="shiftQualificationForm.icon"
                    @update:modelValue="addIconToForm"
                />
                <BaseInput
                    id="shiftQualificationName"
                    v-model="shiftQualificationForm.name"
                    label="Name of the qualification"
                    required
                    :error="shiftQualificationForm.errors.name"
                />
            </div>
            <p v-if="shiftQualificationForm.errors.icon" class="text-[11.5px] text-danger">
                {{ shiftQualificationForm.errors.icon }}
            </p>
            <BaseCheckbox
                id="shiftQualificationAvailable"
                v-model="shiftQualificationForm.available"
                :label="$t('Is taken into account for new shifts')"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="shiftQualificationForm.processing"
                @click="close"
            />
            <BaseUIButton
                type="submit"
                form="shiftQualificationForm"
                :label="mode === 'create' ? 'Create' : 'Save'"
                variant="primary"
                :icon="mode === 'create' ? 'IconCirclePlus' : 'IconDeviceFloppy'"
                :processing="shiftQualificationForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import {defineComponent} from "vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    name: 'ShiftQualificationModal',
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        BaseInput,
        BaseCheckbox,
        IconSelector,
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
            descriptionText: this.mode === 'create'
                ? 'You can create a craft functionality here.'
                : this.$t('Here you can edit the craft functionality "{0}".', [this.shiftQualification?.name ?? '']),
            shiftQualificationForm: useForm({
                icon: this.mode === 'edit' ? this.shiftQualification?.icon ?? null : null,
                name: this.mode === 'edit' ? this.shiftQualification?.name ?? null : null,
                available: this.mode === 'edit' ? Boolean(this.shiftQualification?.available) : false
            })
        }
    },
    computed: {
        canSubmit() {
            return this.shiftQualificationForm.icon !== null
                && Boolean(this.shiftQualificationForm.name?.trim())
                && !this.shiftQualificationForm.processing;
        }
    },
    methods: {
        addIconToForm(icon) {
            this.shiftQualificationForm.icon = icon;
        },
        save() {
            if (!this.canSubmit) {
                return;
            }
            const onSuccessCallback = () => this.closeAndReset();

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
            if (this.shiftQualificationForm.processing) {
                return;
            }
            this.closeAndReset();
        },
        closeAndReset() {
            this.$emit('close');
            this.shiftQualificationForm.reset();
        }
    },
});
</script>
