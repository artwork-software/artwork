<template>
    <div v-if="shouldRender" class="my-2 px-1">
        <div v-if="showLabel" class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-text-subtle">
            {{ labelText }}
        </div>

        <div v-if="!showTextField">
            <div v-if="!currentText" :class="canEdit ? 'cursor-pointer' : ''" @click="openTextField">
                <PropertyIcon name="IconNote" class="w-4 h-4 text-text-muted" :class="canEdit ? 'cursor-pointer' : ''" />
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
                :maxlength="maxLength"
                :error="noteError"
                @focusout="updateDescription"
            />
            <div class="text-xs text-end text-text-muted">
                {{ form.short_description.length }} / {{ maxLength }}
            </div>
        </div>
    </div>
</template>

<script>
import {router, useForm, usePage} from "@inertiajs/vue3";
import axios from "axios";
import { extractSaveErrorMessage } from "@/Composeables/BiSaveFeedback.js";
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
    mixins: [Permissions],
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
                const entity = (this.shift?.users || []).find(u => u.id === this.userToEditId) ?? null
                if (entity) return entity
            }

            if (this.entityType === 'freelancer') {
                const entity = (this.shift?.freelancer || []).find(f => f.id === this.userToEditId) ?? null
                if (entity) return entity
            }

            if (this.entityType === 'service_provider') {
                const entity = (this.shift?.service_provider || []).find(sp => sp.id === this.userToEditId) ?? null
                if (entity) return entity
            }

            // Dashboard-Payload versteckt users/freelancer/serviceProvider und liefert
            // stattdessen eine vereinheitlichte workers-Liste mit type-Tag + Pivot.
            return (this.shift?.workers || []).find(
                w => w.type === this.entityType && w.id === this.userToEditId
            ) ?? null
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
        /** Individuelle Notiz (Pivot) max. 250, Schicht-/Vorlagenbeschreibung max. 10.000 Zeichen */
        maxLength() {
            return this.isPivotMode ? 250 : 10000
        },
        cutText() {
            return this.currentText?.length > 70 ? this.currentText.substring(0, 70) + '...' : this.currentText
        }
    },
    data(){
        return {
            showTextField: false,
            noteError: '',
            form: useForm({
                short_description: this.isPivotMode
                    ? (this.pivotEntity?.pivot?.short_description ?? '')
                    : (this.shift?.description ?? '')
            })
        }
    },
    methods: {
        /** Erste Validierungsmeldung eines Inertia-Fehlers; das Feld bleibt mit dem Entwurf offen */
        showErrors(errors, field) {
            this.noteError = errors?.[field] ?? Object.values(errors ?? {})[0] ?? this.$t('An error has occurred')
        },
        updateDescription(){
            this.noteError = ''
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
                        },
                        onError: (errors) => this.showErrors(errors, 'short_description'),
                    }
                )
                return
            }

            if (this.isPreset) {
                // Inertia ignoriert eine Option "data" – das Feld heißt im Formular short_description,
                // der Endpunkt erwartet description
                this.form
                    .transform((data) => ({ description: data.short_description }))
                    .patch(route('preset.shift.update.updateDescription', this.shift.id), {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            this.form.defaults({ short_description: this.form.short_description })
                            this.showTextField = false
                        },
                        onError: (errors) => this.showErrors(errors, 'description'),
                    })
            } else {
                // JSON-Endpunkt (kein Inertia-Response) → axios, Text lokal übernehmen
                const description = this.form.short_description
                // Fehler steht am Feld – kein zusätzlicher globaler Toast
                axios.patch(route('event.shift.update.updateDescription', this.shift.id), { description }, { skipErrorToast: true })
                    .then(() => {
                        this.shift.description = description
                        this.form.defaults({ short_description: description })
                        this.showTextField = false
                    })
                    .catch((error) => {
                        this.noteError = extractSaveErrorMessage(error) ?? this.$t('An error has occurred')
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
            this.noteError = ''

            this.showTextField = true
            this.$nextTick(() => {
                this.$refs.descriptionField?.focus()
            })
        }
    }
}
</script>
