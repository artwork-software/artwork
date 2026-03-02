<template>
    <ArtworkBaseModal @close="$emit('closeModal')" v-if="show" :title="$t('Cost unit & GEMA')"
                      :description="$t('Define a cost unit and GEMA status for your project.')">
            <div class="mt-5">
                <BaseInput
                    :label="$t('Name of the cost unit')"
                    id="title"
                    v-model="projectRightForm.cost_center_name"
                />
                <div class="flex items-center mb-3 mt-4">
                    <input type="checkbox" v-model="projectRightForm.gema"
                           class="input-checklist"/>
                    <div class="text-md ml-2" :class="[projectRightForm.gema ? 'text-primary' : 'text-secondary']">
                        {{ $t('GEMA')}}
                    </div>
                </div>

                <BaseTextarea :label="$t('Comment / Note')"
                          id="description"
                          v-model="projectRightForm.description"
                          rows="4"
                />

                <div class="w-full flex justify-end my-6">
                    <BaseUIButton
                        :label="$t('Save')"
                        @click="updateData"
                        is-add-button
                    />
                </div>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    mixins: [Permissions],
    name: "ProjectCopyrightModal",
    props: [
        'show', 'project'
    ],
    components: {
        BaseUIButton,
        BaseTextarea,
        BaseInput,
        ArtworkBaseModal,
    },
    data() {
        return {
            projectRightForm: useForm({
                cost_center_name: this.project.cost_center ? this.project.cost_center?.name : '',
                gema: this.project ? this.project?.gema : false,
                description: this.project ? this.project.cost_center_description : '',
            }),
        }
    },
    methods: {
        updateData() {
            this.projectRightForm.post(route('project.copyright.update', this.project.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('closeModal');
                }
            });
        },
    }
}
</script>
