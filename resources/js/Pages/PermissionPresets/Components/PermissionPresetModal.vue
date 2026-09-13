<template>
    <ArtworkBaseModal
        v-if="show"
        :title="mode === 'create' ? 'Create permission preset' : 'Edit permission preset'"
        :description="mode === 'create'
            ? 'A preset bundles permissions as a role profile. It can be applied when inviting people and on the user rights page.'
            : 'Changes affect only this preset – people who already received it keep their permissions.'"
        modal-size="sm:max-w-5xl"
        @close="close"
    >
        <div class="space-y-6">
            <BaseInput
                id="permission-preset-name"
                v-model="form.name"
                :label="$t('Permission preset name')"
                :placeholder="$t('e.g. Production management')"
            />
            <p v-if="$page.props.errors?.name" class="-mt-4 text-sm text-danger">{{ $page.props.errors.name }}</p>

            <PermissionCatalogEditor
                v-model="form.permissions"
                :catalog="catalog"
                :presets="[]"
                :show-explainer="false"
                compact
            />
        </div>
        <template #footer>
            <div class="mt-6 flex items-center justify-end gap-3">
                <BaseUIButton variant="ghost" hide-icon label="Cancel" @click="close" />
                <BaseUIButton
                    variant="primary"
                    hide-icon
                    :label="mode === 'create' ? 'Create' : 'Save'"
                    :disabled="form.processing || form.permissions.length === 0 || form.name === ''"
                    @click="save"
                />
            </div>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PermissionCatalogEditor from '@/Artwork/Permissions/PermissionCatalogEditor.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    /** Payload von PermissionCatalogPresenter::present() */
    catalog: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    /** { id, name, permissions: string[] } oder null */
    permission_preset: { type: Object, default: null },
})
const emit = defineEmits(['close'])

const form = useForm({
    name: props.permission_preset?.name ?? '',
    permissions: [...(props.permission_preset?.permissions ?? [])],
})

function save() {
    if (props.mode === 'create') {
        form.post(route('permission-presets.store'), { onSuccess: close })
    } else {
        form.patch(route('permission-presets.update', { permission_preset: props.permission_preset.id }), { onSuccess: close })
    }
}

function close() {
    form.reset()
    const page = usePage()
    if (page.props.errors?.name) delete page.props.errors.name
    emit('close')
}
</script>
