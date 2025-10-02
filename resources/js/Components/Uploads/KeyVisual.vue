<script setup>

import JetInputError from "@/Jetstream/InputError.vue";
import Button from "@/Jetstream/Button.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import {IconDownload, IconEdit, IconX} from "@tabler/icons-vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {ref} from "vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
});
const keyVisualForm = useForm({
    keyVisual: null
});
const uploadKeyVisualFeedback = ref('')
const keyVisual = ref(null);

const selectNewKeyVisual = () => {
    keyVisual.value.click();
}
const updateKeyVisual = () => {
    validateTypeAndUploadKeyVisual(keyVisual.value.files[0]);
}
const uploadDraggedKeyVisual = (event) => {
    validateTypeAndUploadKeyVisual(event.dataTransfer.files[0]);
}
const validateTypeAndUploadKeyVisual = (file) => {
    uploadKeyVisualFeedback.value = "";
    const allowedTypes = [
        "image/jpeg",
        "image/svg+xml",
        "image/png",
        "image/gif"
    ]

    if (allowedTypes.includes(file.type)) {
        keyVisualForm.keyVisual = file;
        keyVisualForm.post(
            route('projects_key_visual.update', {project: props.project.id}),
            {
                onError: error => {
                    uploadKeyVisualFeedback.value = error.keyVisual;
                }
            }
        );
    } else {
        uploadKeyVisualFeedback.value = $t(
            'Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.'
        );
    }
}
const downloadKeyVisual = () => {
    let link = document.createElement('a');
    link.href = route('project.download.keyVisual', props.project.id);
    link.target = '_blank';
    link.click();
}
const deleteKeyVisual = () => {
    router.delete(route('project.delete.keyVisual', props.project.id))
}
</script>

<template>
    <div class="group">
        <div
            class=" flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
            @dragover.prevent
            @drop.stop.prevent="uploadDraggedKeyVisual($event)"
            @click="selectNewKeyVisual"
            v-if="project.key_visual_path === null">
            <div class="space-y-1 text-center">
                <div class="xsLight flex my-auto h-40 items-center"
                     v-if="project.key_visual_path === null">
                    <span v-html="$t('Drag your key visual here')"></span>
                    <input id="keyVisual-upload" ref="keyVisual"
                           name="file-upload" type="file" class="sr-only"
                           @change="updateKeyVisual"/>
                </div>
            </div>
        </div>
        <div v-else class="flex items-center justify-center relative w-full">
            <div class="absolute w-full text-center flex items-center justify-center hidden group-hover:block space-x-4">
                <button @click="downloadKeyVisual" type="button" v-if="project.key_visual_path !== 'default_keyVisual.png'"
                        class="ui-button bg-white hover:text-orange-500">
                    <IconDownload class="h-5 w-5" aria-hidden="true"/>
                </button>
                <button @click="selectNewKeyVisual" type="button"
                        class="ui-button bg-white hover:text-blue-500">
                    <IconEdit
                        class="h-5 w-5"
                        aria-hidden="true"/>
                </button>
                <button @click="deleteKeyVisual" type="button"
                        class="ui-button bg-white hover:text-red-500">
                    <IconX class="h-5 w-5"
                           aria-hidden="true"/>
                </button>
            </div>
            <div class="text-center">
                <div class="cursor-pointer">
                    <img :src="'/storage/keyVisual/' + project.key_visual_path"
                         alt="Aktuelles Key-Visual"
                         @error="(e) => e.target.src = usePage().props.big_logo"
                         class="rounded-md w-full h-48">
                    <input id="keyVisual-upload" ref="keyVisual"
                           name="file-upload" type="file" class="sr-only"
                           @change="updateKeyVisual"/>
                </div>
            </div>
        </div>

        <jet-input-error :message="uploadKeyVisualFeedback"/>
    </div>
</template>

<style scoped>

</style>
