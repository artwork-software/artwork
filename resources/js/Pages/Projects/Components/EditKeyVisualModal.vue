<template>
    <jet-dialog-modal :show="this.show" @close="this.close">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    {{ $t('Key Visual') }}
                </div>
                <XIcon @click="this.close"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="group">
                    <div
                        class=" flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                        @click="selectNewKeyVisual"
                        v-if="this.project.key_visual_path === null">
                        <div class="space-y-1 text-center">
                            <div class="xsLight flex my-auto h-40 items-center"
                                 v-if="this.project.key_visual_path === null">
                                <span v-html="$t('Drag your key visual here')"></span>
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center relative w-full">
                        <div
                            class="absolute !gap-4 w-full text-center flex items-center justify-center hidden group-hover:block">
                            <button @click="downloadKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <IconDownload class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <button @click="selectNewKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <IconEdit
                                    class="h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                            </button>
                            <button @click="deleteKeyVisual" type="button"
                                    class="inline-flex rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                <IconX class="h-5 w-5 text-primaryText group-hover:text-white"
                                       aria-hidden="true"/>
                            </button>
                        </div>
                        <div class="text-center">
                            <div class="cursor-pointer">
                                <img src="">
                                <img :src="'/storage/keyVisual/' + this.project.key_visual_path"
                                     alt="Aktuelles Key-Visual"
                                     class="rounded-md w-full h-48">
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <jet-input-error :message="this.uploadKeyVisualFeedback"/>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import {defineComponent} from "vue";
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import IconLib from "@/mixins/IconLib.vue";
import Permissions from "@/mixins/Permissions.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default defineComponent({
    components: {
        JetInputError,
        JetDialogModal,
        XIcon
    },
    mixins: [Permissions, IconLib],
    emits: ['closed'],
    props: [
        'show',
        'project'
    ],
    data() {
        return {
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            uploadKeyVisualFeedback: "",
        };
    },
    methods: {
        close() {
            this.$emit('closed');
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0]);
        },
        uploadDraggedKeyVisual(event) {
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0]);
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
            this.$inertia.delete(route('project.delete.keyVisual', this.project.id))
        },
    }
})
</script>
