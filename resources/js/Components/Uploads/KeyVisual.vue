<script setup>
import JetInputError from "@/Jetstream/InputError.vue";
import { IconDownload, IconEdit, IconX } from "@tabler/icons-vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const keyVisualForm = useForm({ keyVisual: null });
const uploadKeyVisualFeedback = ref("");
const keyVisual = ref(null);

// Cache-Buster, triggert <img>-Reload trotz identischem Dateinamen
const cacheBuster = ref(0);

// Robust prüfen (null, '', undefined)
const hasKeyVisual = computed(() => !!props.project?.key_visual_path);

// Bildquelle inkl. Cache-Buster
const imageSrc = computed(() => {
    if (!hasKeyVisual.value) return usePage().props.big_logo;
    // WICHTIG: Cache-Buster anhängen
    return `/storage/keyVisual/${props.project.key_visual_path}`;
});

const selectNewKeyVisual = () => keyVisual.value?.click();

const updateKeyVisual = () => {
    const file = keyVisual.value?.files?.[0];
    if (!file) return;
    validateTypeAndUploadKeyVisual(file);
    // Input zurücksetzen, damit derselbe Dateiname erneut gewählt werden kann
    keyVisual.value.value = null;
};

const uploadDraggedKeyVisual = (event) => {
    const file = event?.dataTransfer?.files?.[0];
    if (!file) return;
    validateTypeAndUploadKeyVisual(file);
};

const validateTypeAndUploadKeyVisual = (file) => {
    uploadKeyVisualFeedback.value = "";
    const allowedTypes = ["image/jpeg", "image/svg+xml", "image/png", "image/gif", "image/svg+xml"];

    if (!allowedTypes.includes(file.type)) {
        uploadKeyVisualFeedback.value = $t(
            "Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted."
        );
        return;
    }

    keyVisualForm.keyVisual = file;

    keyVisualForm.post(route("projects_key_visual.update", { project: props.project.id }), {
        preserveState: true, // Modal bleibt offen
        onSuccess: () => {
            // 1) Sofort neuen Cache-Key setzen, damit das <img> neu lädt
            cacheBuster.value = Date.now();
            // 2) Nur die 'project'-Prop vom Server neu laden
            router.reload({
                only: ["project"],
                preserveScroll: true,
                preserveState: true,
                // 3) Nach dem Refresh den Cache-Buster nochmal bumpen, falls Browser doch cached
                onSuccess: () => (cacheBuster.value = Date.now()),
            });
        },
        onError: (error) => {
            uploadKeyVisualFeedback.value = error?.keyVisual ?? $t("Upload failed.");
        },
    });
};

const downloadKeyVisual = () => {
    const link = document.createElement("a");
    link.href = route("project.download.keyVisual", props.project.id);
    link.target = "_blank";
    link.click();
};

const deleteKeyVisual = () => {
    router.delete(route("project.delete.keyVisual", props.project.id), {
        preserveState: true, // Modal bleibt offen
        onSuccess: () => {
            cacheBuster.value = Date.now();
            router.reload({
                only: ["project"],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => (cacheBuster.value = Date.now()),
            });
        },
        onError: (error) => {
            uploadKeyVisualFeedback.value = error?.keyVisual ?? $t("Deletion failed.");
        },
    });
};
</script>

<template>
    <!-- Debug falls nötig -->
    <!-- <pre class="text-xs text-gray-400">{{ project.key_visual_path }}</pre> -->

    <div class="group">
        <!-- Dropzone, wenn kein Key-Visual vorhanden -->
        <div
            v-if="!hasKeyVisual"
            class="flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
            @dragover.prevent
            @drop.stop.prevent="uploadDraggedKeyVisual($event)"
            @click="selectNewKeyVisual"
        >
            <div class="space-y-1 text-center">
                <div class="xsLight flex my-auto h-40 items-center">
                    <span v-html="$t('Drag your key visual here')"></span>
                    <input
                        id="keyVisual-upload"
                        ref="keyVisual"
                        name="file-upload"
                        type="file"
                        class="sr-only"
                        accept=".jpg,.jpeg,.png,.gif,.svg"
                        @change="updateKeyVisual"
                    />
                </div>
            </div>
        </div>

        <!-- Bild + Aktionen, wenn vorhanden -->
        <div v-else class="flex items-center justify-center relative w-full">
            <div class="absolute w-full text-center flex items-center justify-center hidden group-hover:block space-x-4">
                <button
                    v-if="project.key_visual_path !== 'default_keyVisual.png'"
                    @click="downloadKeyVisual"
                    type="button"
                    class="ui-button bg-white hover:text-orange-500"
                >
                    <IconDownload class="h-5 w-5" aria-hidden="true" />
                </button>
                <button @click="selectNewKeyVisual" type="button" class="ui-button bg-white hover:text-blue-500">
                    <IconEdit class="h-5 w-5" aria-hidden="true" />
                </button>
                <button @click="deleteKeyVisual" type="button" class="ui-button bg-white hover:text-red-500">
                    <IconX class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <div class="text-center">
                <div class="cursor-pointer">
                    <img
                        :key="cacheBuster"
                    :src="imageSrc"
                    alt="Aktuelles Key-Visual"
                    @error="(e) => (e.target.src = usePage().props.big_logo)"
                    class="rounded-md w-full h-48 object-cover"
                    />
                    <input
                        id="keyVisual-upload"
                        ref="keyVisual"
                        name="file-upload"
                        type="file"
                        class="sr-only"
                        accept=".jpg,.jpeg,.png,.gif,.svg"
                        @change="updateKeyVisual"
                    />
                </div>
            </div>
        </div>

        <JetInputError :message="uploadKeyVisualFeedback" />
    </div>
</template>

<style scoped>
</style>
