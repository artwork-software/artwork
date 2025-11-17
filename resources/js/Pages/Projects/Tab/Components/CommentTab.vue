<template>
    <section class="">
        <!-- Wrapper Card -->
        <div class="rounded-2xl border border-gray-200/70 bg-white shadow-sm">
            <!-- Header -->
            <header class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                <BasePageTitle
                    title="Project updates & comments"
                    description="Share context, decisions, and notes with your team."
                />
                <div class="hidden sm:flex items-center gap-2">
                  <span class="inline-flex items-center rounded-full border border-gray-200 px-2 py-0.5 text-[11px] text-gray-600">
                    {{ newCommentList?.length || 0 }} {{ $t('comments') }}
                  </span>
                </div>
            </header>

            <div v-if="loadCommentsError" class="px-6 py-2 text-xs text-rose-600">
                {{ loadCommentsError }}
            </div>
            <div v-else-if="isLoadingComments" class="px-6 py-2 text-xs text-secondary">
                {{ $t('Loading data...') }}
            </div>

            <!-- Composer -->
            <div class="px-6 py-5">
                <div
                    v-if="canEditComponent || (is('artwork admin') || can('write projects') || effectiveProjectWriteIds?.includes($page.props.auth.user.id) || effectiveProjectManagerIds?.includes($page.props.auth.user.id) || isMemberOfADepartment)"
                    class="relative rounded-xl border border-gray-200/80 bg-gray-50/50 p-4"
                >
                    <BaseTextarea
                        :label="$t('What should the other project members know about the project?')"
                        v-model="commentForm.text"
                        id="text"
                        :rows="4"
                        :maxlength="5000"
                    />

                    <!-- Composer Footer / Actions -->
                    <div class="mt-3 flex items-center justify-end">
                        <div class="flex items-center gap-3">
                            <div class="text-[11px] text-gray-500">
                                {{ commentForm.text?.length ?? 0 }} / 5000
                            </div>
                            <button
                                class="ui-button-add"
                                :class="commentForm.text === '' ? 'cursor-not-allowed !bg-gray-100 !text-gray-400 !border-gray-200' : 'cursor-pointer'"
                                @click="addCommentToProject"
                                :disabled="commentForm.text === ''"
                            >
                                <IconCircleCheckFilled class="size-4" />
                                <span class="text-sm">{{ $t('Add comment to project') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments List -->
            <div class="px-2 pb-6 sm:px-6">
                <div v-if="newCommentList?.length > 0" class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-4 top-0 h-full w-px bg-gray-200 sm:left-5"></div>

                    <ul class="space-y-4">
                        <li
                            v-for="comment in newCommentList"
                            :key="comment.id"
                            class="group relative pl-10 sm:pl-12"
                        >
                            <!-- Timeline node -->
                            <span class="absolute left-4 top-6 inline-flex size-2.5 -translate-x-1/2 transform items-center justify-center rounded-full bg-blue-500 ring-8 ring-white sm:left-5"></span>

                            <div class="rounded-xl border border-gray-200/70 bg-white p-4 shadow-xs transition group-hover:shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <UserPopoverTooltip :user="comment.user" height="9" width="9" :id="comment.id" />
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-x-2">
                                                <span class="truncate text-sm font-medium text-gray-900">
                                                  {{ comment.user.full_name }}
                                                </span>
                                                <span class="text-xs text-gray-400">•</span>
                                                <time class="text-xs text-gray-500">{{ comment.created_at }}</time>
                                            </div>
                                            <p class="mt-1 text-sm font-normal leading-6 text-gray-500 subpixel-antialiased"
                                               v-html="comment.text"></p>
                                        </div>
                                    </div>

                                    <!-- Delete button (visible on hover / permission) -->
                                    <div class="shrink-0">
                                        <button
                                            v-if="
                        is('artwork admin') ||
                        can('write projects') ||
                        effectiveProjectWriteIds?.includes($page.props.auth.user.id) ||
                        effectiveProjectManagerIds?.includes($page.props.auth.user.id) ||
                        isMemberOfADepartment ||
                        comment.user?.id === $page.props.auth.user.id ||
                        canEditComponent
                      "
                                            type="button"
                                            class="invisible group-hover:visible inline-flex rounded-full p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-600 focus-visible:outline-none"
                                            @click="deleteCommentFromProject(comment)"
                                        >
                                            <span class="sr-only">{{ $t('Remove comment from project') }}</span>
                                            <IconCircleXFilled class="size-6" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Empty state -->
                <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-10 text-center">
                    <div class="mx-auto max-w-md">
                        <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-white text-gray-400 shadow-inner">
                            <!-- simple dot icon -->
                            <span class="block size-2 rounded-full bg-gray-300"></span>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ $t('No comments yet') }}</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $t('Be the first to share an update to keep everyone aligned.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref, computed, getCurrentInstance, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { useCommentListener } from "@/Composeables/Listener/useCommentListener.js";
import { IconCircleCheckFilled, IconCircleXFilled } from "@tabler/icons-vue";
import { can, is } from "laravel-permission-to-vuejs";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";

defineOptions({
    name: "ProjectComments",
});

const props = defineProps({
    project: { type: Object, required: true },
    isMemberOfADepartment: { type: Boolean, default: false },
    projectWriteIds: { type: Array, default: () => [] },
    projectManagerIds: { type: Array, default: () => [] },
    tab_id: { type: [String, Number, null], default: null },
    canEditComponent: { type: Boolean, default: false },
    component: { type: Object, default: null },
});

const { proxy } = getCurrentInstance();
const initialComments = props.project?.comments ?? [];
const newCommentList = ref([...initialComments]);
const isLoadingComments = ref(false);
const loadCommentsError = ref('');
const remoteProjectWriteIds = ref([...props.projectWriteIds]);
const remoteProjectManagerIds = ref([...props.projectManagerIds]);

const commentForm = useForm({
    text: "",
    user_id: proxy?.$page?.props?.auth?.user?.id,
    project_id: props.project.id,
    tab_id: props.tab_id ? props.tab_id : null,
});

const effectiveProjectWriteIds = computed(
    () => (remoteProjectWriteIds.value.length ? remoteProjectWriteIds.value : (props.projectWriteIds ?? []))
);
const effectiveProjectManagerIds = computed(
    () => (remoteProjectManagerIds.value.length ? remoteProjectManagerIds.value : (props.projectManagerIds ?? []))
);

const sortedComments = computed(() => {
    const copy = Array.isArray(newCommentList.value) ? newCommentList.value.slice() : [];
    return copy.sort((a, b) => {
        if (b.created_at === null) return -1;
        if (a.created_at === null) return 1;
        if (a.created_at < b.created_at) return 1;
        if (a.created_at > b.created_at) return -1;
        return 0;
    });
});

watch(
    () => [props.project?.id, props.component?.id],
    () => {
        fetchComments();
    },
    { immediate: true }
);

async function fetchComments() {
    const projectId = props.project?.id;
    const componentInTabId = props.component?.id ?? (props.component)?.component_in_tab_id;

    if (!projectId || !componentInTabId) {
        return;
    }

    isLoadingComments.value = true;
    loadCommentsError.value = '';

    try {
        const { data } = await axios.get(
            route('projects.tabs.comments', { project: projectId, componentInTab: componentInTabId })
        );
        const fetchedComments = data?.comments ?? [];
        newCommentList.value.splice(0, newCommentList.value.length, ...fetchedComments);

        if (Array.isArray(data?.projectWriteIds)) {
            remoteProjectWriteIds.value = data.projectWriteIds;
        }

        if (Array.isArray(data?.projectManagerIds)) {
            remoteProjectManagerIds.value = data.projectManagerIds;
        }
    } catch (error) {
        console.error(error);
        loadCommentsError.value = 'Unable to load comments.';
    } finally {
        isLoadingComments.value = false;
    }
}

onMounted(() => {
    const listener = useCommentListener(newCommentList, props.project.id);
    listener.init();
});

function addCommentToProject() {
    commentForm.post(route("comments.store"), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            commentForm.text = "";
            fetchComments();
        },
    });
}

function deleteCommentFromProject(comment) {
    if (!comment?.id) return;
    const deleteFn = window?.Inertia
        ? window.Inertia.delete
        : proxy?.$inertia?.delete;

    if (deleteFn) {
        deleteFn(`/comments/${comment.id}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: fetchComments
        });
    }
}
</script>

<style scoped>
/* Mini shadow utility, softer als default shadow-sm */
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
