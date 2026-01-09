<template>
    <div v-if="shouldRender" class="my-2 px-1">
        <div v-if="showLabel" class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500">
            {{ labelText }}
        </div>

        <div v-if="!showTextField">
            <div v-if="!currentText" :class="canEdit ? 'cursor-pointer' : ''" @click="openTextField">
                <PropertyIcon name="IconNote" class="w-4 h-4 text-artwork-buttons-context" :class="canEdit ? 'cursor-pointer' : ''" />
            </div>
            <p v-else class="text-xs" :class="canEdit ? 'cursor-pointer' : ''" @click="openTextField">
                {{ cutText }}
            </p>
        </div>

        <div v-else class="cursor-pointer">
            <BaseTextarea
                ref="descriptionField"
                id="descriptionField"
                v-model="form.short_description"
                :label="inputLabel"
                maxlength="250"
                @focusout="updateDescription"
            />
            <div class="text-xs text-end text-artwork-buttons-context">
                {{ form.short_description.length }} / 250
            </div>
        </div>
    </div>
</template>

<script>
import IconLib from "@/Mixins/IconLib.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "ShiftNoteComponent",
    components: {PropertyIcon, BaseTextarea},
    props: {
        shift: {
            type: Object,
            required: true
        },
        mode: {
            type: String,
            default: 'shift' // 'shift' | 'pivot'
        },
        userToEditId: {
            type: Number,
            default: null
        },
        entityType: {
            type: String,
            default: null // 'user' | 'freelancer' | 'service_provider'
        },
        isPreset: {
            type: Boolean,
            default: false
        }
    },
    mixins: [IconLib, Permissions],
    computed: {
        isPivotMode() {
            return this.mode === 'pivot'
        },
        currentUserId() {
            return usePage().props?.auth?.user?.id ?? null
        },
        canEdit() {
            if (this.$can('can plan shifts') || this.hasAdminRole()) {
                return true
            }

            // Im UserShiftPlan darf der User seine eigene individuelle Notiz bearbeiten
            return this.isPivotMode && this.entityType === 'user' && this.userToEditId === this.currentUserId
        },
        shouldRender() {
            if (this.isPivotMode) {
                return this.canEdit || !!this.currentText
            }

            // Original-Verhalten: Shift-Beschreibung nur für Planer/Admins
            return this.canEdit
        },
        showLabel() {
            return this.isPivotMode
        },
        labelText() {
            return 'Individuelle Schichtnotiz'
        },
        inputLabel() {
            return this.isPivotMode ? 'Individuelle Schichtnotiz' : 'Description'
        },
        pivotEntity() {
            if (!this.isPivotMode) {
                return null
            }

            if (this.entityType === 'user') {
                return (this.shift?.users || []).find(u => u.id === this.userToEditId) ?? null
            }

            if (this.entityType === 'freelancer') {
                return (this.shift?.freelancer || []).find(f => f.id === this.userToEditId) ?? null
            }

            if (this.entityType === 'service_provider') {
                return (this.shift?.service_provider || []).find(sp => sp.id === this.userToEditId) ?? null
            }

            return null
        },
        pivotId() {
            return this.pivotEntity?.pivot?.id ?? null
        },
        currentText() {
            if (this.isPivotMode) {
                return (this.pivotEntity?.pivot?.short_description ?? '')
            }
            return this.shift?.description ?? ''
        },
        cutText() {
            return this.currentText?.length > 70 ? this.currentText.substring(0, 70) + '...' : this.currentText
        }
    },
    data(){
        return {
            showTextField: false,
            form: useForm({
                short_description: this.isPivotMode
                    ? (this.pivotEntity?.pivot?.short_description ?? '')
                    : (this.shift?.description ?? '')
            })
        }
    },
    methods: {
        updateDescription(){
            if (!this.canEdit) {
                this.showTextField = false
                return
            }

            if (!this.form.isDirty) {
                this.showTextField = false
                return
            }

            if (this.isPivotMode) {
                if (!this.pivotId) {
                    this.showTextField = false
                    return
                }

                router.post(
                    route('shifts.updateShortDescription'),
                    {
                        shiftPivotId: this.pivotId,
                        entity: { type: this.entityType },
                        short_description: this.form.short_description
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            this.form.defaults({ short_description: this.form.short_description })
                            this.showTextField = false
                        }
                    }
                )
                return
            }

            if (this.isPreset) {
                this.form.patch(route('preset.shift.update.updateDescription', this.shift.id), {
                    preserveState: true,
                    preserveScroll: true,
                    data: { description: this.form.short_description },
                    onSuccess: () => {
                        this.showTextField = false
                    }
                })
            } else {
                this.form.patch(route('event.shift.update.updateDescription', this.shift.id), {
                    preserveState: true,
                    preserveScroll: true,
                    data: { description: this.form.short_description },
                    onSuccess: () => {
                        this.showTextField = false
                    }
                })
            }
        },
        openTextField(){
            if (!this.canEdit) {
                return
            }
            if (this.isPivotMode && !this.pivotId) {
                return
            }

            // Ensure textarea is prefilled with the current note (e.g. existing pivot short_description)
            // and avoid showing it as dirty immediately.
            this.form.defaults({ short_description: this.currentText })
            this.form.short_description = this.currentText

            this.showTextField = true
            this.$nextTick(() => {
                this.$refs.descriptionField.focus()
            })
        }
    }
}
</script>
