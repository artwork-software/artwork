<template>
    <BaseModal @closed="close" v-if="show" modal-image="/Svgs/Overlays/illu_user_invite.svg">
            <div class="mx-4">
                <div class="mt-8 font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                    {{this.mode === 'create' ? $t('Create permission preset') : $t('Edit permission preset')}}
                </div>
                <div class="text-sm/5 font-bold text-text-subtle my-6">
                    {{
                        this.mode === 'create' ?
                            $t('You can create a permission preset here. This can be used when creating users.') :
                            $t('Here you can edit the permission preset {presetName}.', {presetName: this.permission_preset.name})
                    }}
                </div>
                <div class="flex items-center">
                    <div class="relative w-full mr-4">
                        <input id="name"
                               v-model="this.permissionPresetForm.name"
                               type="text"
                               :class="[
                                   $page.props.errors.name ?
                                   'border-danger focus:border-danger' :
                                   'border-border focus:border-surface-inverse',
                                   'mb-3 peer pl-0 h-12 w-full focus:border-t-transparent focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 text-text placeholder-text-subtle placeholder-transparent'
                               ]"
                               placeholder="placeholder"
                        />
                        <label for="name"
                               :class="[
                                   $page.props.errors.name ?
                                   'text-sm/5 text-danger' :
                                   'text-text-subtle',
                                   'absolute left-0 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none peer-placeholder-shown:text-base peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm'
                               ]"
                        >
                            {{ $t('Permission preset name') }}
                        </label>
                        <span v-if="$page.props.errors.name" class="text-sm/5 text-danger">
                            {{$page.props.errors.name.charAt(0).toUpperCase() + $page.props.errors.name.slice(1)}}
                        </span>
                    </div>
                </div>
                <div v-for="(permissions, group) in available_permissions">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[10px]/[19px] font-semibold uppercase tracking-wider text-text-subtle mb-2 mt-3">{{ group }}</h3>
                        <div class="text-xs underline text-accent-600 cursor-pointer" @click="checkOrUncheckAllPermissions(permissions)">
                            {{ permissions.some(permission => this.permissionPresetForm.permissions.includes(permission.id)) ? $t('Deselect all') : $t('Select all') }}
                        </div>
                    </div>
                    <ProjectPermissionHierarchyBanner v-if="group === 'Projects'" class="mb-3" />
                    <div class="w-full flex items-center"
                         v-for="(permission) in permissions" :key=permission.id>
                        <div class="w-full flex justify-between items-center my-1">
                            <div class="flex items-center justify-start">
                                <input :key="permission.name"
                                       :value="permission.id"
                                       v-model="this.permissionPresetForm.permissions"
                                       type="checkbox"
                                       class="input-checklist"/>

                                <p :class="[this.permissionPresetForm.permissions.includes(permission.id) ? 'text-sm/5 font-semibold text-text' : 'text-sm/5 font-bold text-text-subtle']"
                                   class="ml-4 my-auto text-sm">{{ $t(permission.translation_key) }}</p>
                            </div>
                            <div v-if="permission.showIcon !== false">
                                <TextToolTip :id="permission.id" :height="6" :width="6" :tooltip-text="permission.tooltipKey" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton class="mt-5"
                                @click="save"
                               :disabled="this.permissionPresetForm.permissions.length === 0 || this.permissionPresetForm.name === ''"
                               :text="this.mode === 'create' ? $t('Create') : $t('Save')"
                    />
                </div>
            </div>
        </BaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import {defineComponent} from "vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {useForm} from "@inertiajs/vue3";
import TextToolTip from "@/Layouts/Components/TextToolTip.vue";
import Label from "@/Jetstream/Label.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ProjectPermissionHierarchyBanner from "@/Artwork/Guide/ProjectPermissionHierarchyBanner.vue";

export default defineComponent({
    components: {
        BaseModal,
        ProjectPermissionHierarchyBanner,
        FormButton,
        Label,
        TextToolTip,
        JetDialogModal,
        IconX,
    },
    props: [
        'show',
        'available_permissions',
        'mode',
        'permission_preset'
    ],
    data() {
        return {
            permissionPresetForm: useForm({
                name: this.permission_preset !== null ? this.permission_preset.name : '',
                permissions: this.permission_preset !== null ? this.permission_preset.permissions : []
            })
        }
    },
    methods: {
        checkOrUncheckAllPermissions(permissions) {
            // check if some permissions are already selected
            if (permissions.some(permission => this.permissionPresetForm.permissions.includes(permission.id))) {
                // deselect all permissions
                this.permissionPresetForm.permissions = this.permissionPresetForm.permissions.filter(permission => !permissions.map(permission => permission.id).includes(permission));
            } else {
                // select all permissions
                this.permissionPresetForm.permissions = this.permissionPresetForm.permissions.concat(permissions.map(permission => permission.id));
            }
        },
        save() {
            switch (this.mode) {
                case 'create':
                    this.permissionPresetForm.post(
                        route('permission-presets.store'),
                        {
                            onSuccess: this.close
                        }
                    );
                    break;
                case 'edit':
                    this.permissionPresetForm.patch(
                        route('permission-presets.update', {permission_preset: this.permission_preset.id}),
                        {
                            onSuccess: this.close
                        }
                    );
                    break;
            }
        },
        close() {
            this.permissionPresetForm.reset();

            //reset errors otherwise they will be shown if the modal is opened again
            if (this.$page.props.errors.name) {
                delete this.$page.props.errors.name;
            }

            this.$emit('close');
        }
    }
})
</script>
