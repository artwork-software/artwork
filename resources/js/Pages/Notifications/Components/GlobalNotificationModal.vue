<template>
    <ArtworkBaseModal
        title="Notification to all"
        description="Share something important with all users - e.g. changes or new functions in the artwork or important messages that affect the whole house. All users can view the message in the notifications (including external users!)."
        modal-size="sm:max-w-2xl"
        @close="closeModal(false)"
    >
        <form
            v-if="canChangeNotification"
            id="globalNotificationForm"
            class="space-y-4"
            @submit.prevent="saveGlobalNotification"
        >
            <div>
                <span class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Image') }}</span>
                <input
                    id="globalNotificationImageInput"
                    ref="notificationImageInput"
                    type="file"
                    accept="image/jpeg,image/svg+xml,image/png,image/gif"
                    class="sr-only"
                    @change="updateNotificationImagePreview"
                />
                <div
                    role="button"
                    tabindex="0"
                    class="flex min-h-40 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewNotificationImage"
                    @keydown.enter.prevent="selectNewNotificationImage"
                    @keydown.space.prevent="selectNewNotificationImage"
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = $event.currentTarget.contains($event.relatedTarget)"
                    @drop.stop.prevent="uploadDraggedImage"
                >
                    <img
                        v-if="shownImage"
                        :src="shownImage"
                        :alt="$t('Current picture')"
                        class="max-h-40 rounded-md object-contain"
                    />
                    <template v-else>
                        <PropertyIcon name="IconPhoto" class="size-6 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <p class="text-sm text-text-muted">
                            <template v-if="isDragging">{{ $t('Drop here') }}</template>
                            <template v-else>
                                <span class="font-semibold text-accent-600">{{ $t('Choose file') }}</span>
                                {{ $t('or drag and drop') }}
                            </template>
                        </p>
                    </template>
                </div>
                <p v-if="uploadDocumentFeedback || form.errors.notificationImage" class="mt-1 text-[11.5px] text-danger">
                    {{ uploadDocumentFeedback || form.errors.notificationImage }}
                </p>
            </div>

            <BaseInput
                id="globalNotificationTitle"
                v-model="form.notificationName"
                label="Title"
                required
                :error="form.errors.notificationName"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <BaseInput
                    id="globalNotificationDeadlineDate"
                    v-model="form.notificationDeadlineDate"
                    type="date"
                    label="Expiration date"
                    required
                    :error="form.errors.notificationDeadlineDate"
                />
                <BaseInput
                    id="globalNotificationDeadlineTime"
                    v-model="form.notificationDeadlineTime"
                    type="time"
                    label="Time"
                    required
                    :error="form.errors.notificationDeadlineTime"
                />
            </div>

            <BaseTextarea
                id="globalNotificationDescription"
                v-model="form.notificationDescription"
                label="Notification"
                :rows="4"
                :error="form.errors.notificationDescription"
            />
        </form>

        <template v-if="canChangeNotification" #footer>
            <BaseUIButton
                v-if="isEdit"
                class="mr-auto"
                label="Delete notification"
                variant="danger"
                icon="IconTrash"
                :processing="deleting"
                :disabled="form.processing"
                @click="deleteGlobalNotification"
            />
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="form.processing || deleting"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="globalNotificationForm"
                :label="isEdit ? 'Edit notification' : 'Share notification'"
                variant="primary"
                :icon="isEdit ? 'IconDeviceFloppy' : 'IconSend'"
                :processing="form.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from 'vue'
import {router, useForm, usePage} from '@inertiajs/vue3'
import {useI18n} from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {usePermission} from '@/Composeables/Permission.js'

defineOptions({name: 'GlobalNotificationModal'})

const props = defineProps({
    // Ohne vorhandene Meldung liefert das Backend {image_url: null}
    globalNotification: {type: Object, default: null},
})

const emit = defineEmits(['closed'])

const {t} = useI18n()
const permission = usePermission(usePage().props)

const canChangeNotification = computed(() =>
    permission.hasAdminRole() || permission.canAny(['change system notification'])
)

const isEdit = computed(() => !!props.globalNotification?.id)

function getDateOfDate(isoDate) {
    return isoDate?.split(' ')[0] || null
}

function getTimeOfDate(isoDate) {
    return isoDate?.split(' ')[1]?.substring(0, 5) || null
}

const form = useForm({
    _method: 'PUT',
    notificationImage: props.globalNotification?.image_url ?? null,
    notificationName: props.globalNotification?.title ?? '',
    notificationDeadlineDate: getDateOfDate(props.globalNotification?.expiration_date),
    notificationDeadlineTime: getTimeOfDate(props.globalNotification?.expiration_date),
    notificationDescription: props.globalNotification?.description ?? '',
})

const notificationImageInput = ref(null)
const notificationImagePreview = ref(null)
const uploadDocumentFeedback = ref('')
const isDragging = ref(false)
const deleting = ref(false)

const shownImage = computed(() =>
    notificationImagePreview.value
    || (typeof form.notificationImage === 'string' ? form.notificationImage : null)
)

// Ohne Ablaufdatum und -zeit legt das Backend die Meldung als sofort abgelaufen an
const canSubmit = computed(() =>
    String(form.notificationName ?? '').trim() !== ''
    && !!form.notificationDeadlineDate
    && !!form.notificationDeadlineTime
    && !form.processing
    && !deleting.value
)

function closeModal(saved) {
    emit('closed', saved)
}

function validateTypeAndSetImage(file) {
    uploadDocumentFeedback.value = ''
    if (!file) {
        return
    }
    const allowedTypes = ['image/jpeg', 'image/svg+xml', 'image/png', 'image/gif']
    if (!allowedTypes.includes(file.type)) {
        uploadDocumentFeedback.value = t('Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.')
        return
    }
    const reader = new FileReader()
    reader.onload = (event) => {
        notificationImagePreview.value = event.target.result
    }
    reader.readAsDataURL(file)
    form.notificationImage = file
}

function uploadDraggedImage(event) {
    isDragging.value = false
    validateTypeAndSetImage(event.dataTransfer?.files?.[0])
}

function selectNewNotificationImage() {
    notificationImageInput.value?.click()
}

function updateNotificationImagePreview() {
    validateTypeAndSetImage(notificationImageInput.value?.files?.[0])
    // gleiche Datei erneut wählen können
    if (notificationImageInput.value) {
        notificationImageInput.value.value = ''
    }
}

function saveGlobalNotification() {
    if (!canSubmit.value) {
        return
    }
    const url = isEdit.value
        ? route('global_notification.update', {globalNotification: props.globalNotification.id})
        : route('global_notification.store')
    form.post(url, {
        preserveScroll: true,
        onSuccess: () => closeModal(true),
    })
}

function deleteGlobalNotification() {
    if (!isEdit.value || deleting.value) {
        return
    }
    deleting.value = true
    router.delete(route('global_notification.destroy', {globalNotification: props.globalNotification.id}), {
        preserveScroll: true,
        onSuccess: () => closeModal(true),
        onFinish: () => {
            deleting.value = false
        },
    })
}
</script>
