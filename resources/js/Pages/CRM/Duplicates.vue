<template>
    <AppLayout :title="$t('Find duplicates')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconUsers"
                :title="$t('Find duplicates')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :search-enabled="false"
            >
                <template #actions>
                    <Link :href="route('crm.index')" class="ui-button">
                        {{ $t('Back to CRM') }}
                    </Link>
                </template>
            </ToolbarHeader>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="flashSuccess" class="mt-4 rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">{{ flashSuccess }}</p>
                </div>
            </Transition>

            <!-- Explanation -->
            <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
                <div class="flex gap-3">
                    <component :is="IconInfoCircle" class="h-5 w-5 text-indigo-600 shrink-0" />
                    <div class="text-sm text-gray-700 space-y-1">
                        <p>{{ $t('Contacts of the same type with an identical name or identical email address are listed here as potential duplicates.') }}</p>
                        <p>{{ $t('When merging, the main contact keeps its values; empty fields are filled from the duplicates. Project links, residencies and accesses are transferred. The duplicates are moved to the recycle bin.') }}</p>
                        <p class="text-xs text-gray-500">{{ $t('Contacts mirrored from user, freelancer or service provider profiles are not included — clean those up in the respective management area.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!duplicateGroups.length" class="mt-8 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-12 text-center">
                <component :is="IconCircleCheck" class="mx-auto h-10 w-10 text-green-400" />
                <p class="mt-3 text-sm font-medium text-gray-900">{{ $t('No duplicates found') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $t('There are no contacts of the same type with identical names or email addresses.') }}</p>
            </div>

            <!-- Clusters -->
            <div v-else class="mt-8 space-y-6">
                <div v-for="(cluster, index) in duplicateGroups" :key="clusterKey(cluster)" class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-5 py-3 bg-gray-50 rounded-t-lg flex items-center gap-3 flex-wrap">
                        <span
                            v-if="cluster.contact_type"
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :style="cluster.contact_type.color
                                ? { backgroundColor: cluster.contact_type.color + '15', color: cluster.contact_type.color }
                                : { backgroundColor: '#eef2ff', color: '#3730a3' }"
                        >
                            <PropertyIcon v-if="cluster.contact_type.icon" :name="cluster.contact_type.icon" class="h-3.5 w-3.5" />
                            {{ $t(cluster.contact_type.name) }}
                        </span>
                        <span class="text-sm text-gray-600">
                            {{ cluster.match === 'email' ? $t('Same email address') : $t('Same name') }}:
                            <span class="font-medium text-gray-900">{{ cluster.value }}</span>
                        </span>
                        <span class="ml-auto text-xs text-gray-400">{{ cluster.contacts.length }} {{ $t('Contacts') }}</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div v-for="contact in cluster.contacts" :key="contact.id" class="px-5 py-3 flex items-center gap-4">
                            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer shrink-0 w-36">
                                <input
                                    type="radio"
                                    :name="`primary-${index}`"
                                    :checked="selection[index]?.primaryId === contact.id"
                                    class="text-indigo-600 border-gray-300"
                                    @change="setPrimary(index, contact.id)"
                                />
                                {{ $t('Keep as main contact') }}
                            </label>
                            <img :src="contact.profile_photo_url" alt="" class="size-9 rounded-full object-cover shrink-0" />
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate flex items-center gap-2">
                                    <Link :href="route('crm.contacts.show', contact.id)" class="hover:text-indigo-600" target="_blank">
                                        {{ contact.display_name }}
                                    </Link>
                                    <span v-if="contact.has_entity" class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">
                                        {{ $t('Linked to profile') }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 truncate">
                                    <span v-if="contact.email">{{ contact.email }} · </span>{{ $t('Created at') }} {{ contact.created_at }}
                                </div>
                            </div>
                            <label
                                v-if="selection[index]?.primaryId !== contact.id"
                                class="ml-auto flex items-center gap-2 text-xs cursor-pointer shrink-0"
                                :class="contact.has_entity ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600'"
                            >
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600"
                                    :disabled="contact.has_entity"
                                    :checked="selection[index]?.mergeIds.has(contact.id)"
                                    @change="toggleMerge(index, contact.id)"
                                />
                                {{ contact.has_entity ? $t('Cannot be merged (linked)') : $t('Merge into main contact') }}
                            </label>
                            <span v-else class="ml-auto text-xs font-medium text-indigo-600 shrink-0">{{ $t('Main contact') }}</span>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100 flex justify-end">
                        <button
                            class="ui-button-add"
                            :disabled="!(selection[index]?.mergeIds.size > 0) || merging"
                            @click="confirmCluster = index"
                        >
                            {{ $t('Merge {count} contacts', { count: (selection[index]?.mergeIds.size ?? 0) + 1 }) }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm -->
        <ConfirmDeleteModal
            v-if="confirmCluster !== null"
            :title="$t('Merge contacts')"
            :description="$t('The selected duplicates are merged into the main contact and moved to the recycle bin. Values already present on the main contact are kept.')"
            @close="confirmCluster = null"
            @delete="mergeCluster(confirmCluster)"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { IconUsers, IconInfoCircle, IconCircleCheck } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    duplicateGroups: { type: Array, required: true },
})

const flashSuccess = ref(usePage().props.flash?.success ?? '')

const clusterKey = (cluster) =>
    `${cluster.match}-${cluster.contact_type?.id}-${cluster.value}`

// Pro Cluster: gewählter Hauptkontakt + Merge-Auswahl
const selection = reactive({})

const initSelection = () => {
    Object.keys(selection).forEach(k => delete selection[k])
    props.duplicateGroups.forEach((cluster, index) => {
        // Default-Hauptkontakt: entity-gebundener Kontakt, sonst der älteste (erste)
        const entityContact = cluster.contacts.find(c => c.has_entity)
        const primaryId = (entityContact ?? cluster.contacts[0]).id
        selection[index] = {
            primaryId,
            mergeIds: new Set(
                cluster.contacts
                    .filter(c => c.id !== primaryId && !c.has_entity)
                    .map(c => c.id)
            ),
        }
    })
}

initSelection()
watch(() => props.duplicateGroups, initSelection)

const setPrimary = (index, contactId) => {
    const cluster = props.duplicateGroups[index]
    selection[index] = {
        primaryId: contactId,
        mergeIds: new Set(
            cluster.contacts
                .filter(c => c.id !== contactId && !c.has_entity)
                .map(c => c.id)
        ),
    }
}

const toggleMerge = (index, contactId) => {
    const entry = selection[index]
    if (!entry) return
    if (entry.mergeIds.has(contactId)) {
        entry.mergeIds.delete(contactId)
    } else {
        entry.mergeIds.add(contactId)
    }
}

const merging = ref(false)
const confirmCluster = ref(null)

const mergeCluster = (index) => {
    const entry = selection[index]
    if (!entry || entry.mergeIds.size === 0) return

    merging.value = true
    router.post(route('crm.duplicates.merge'), {
        primary_id: entry.primaryId,
        duplicate_ids: [...entry.mergeIds],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            confirmCluster.value = null
            flashSuccess.value = usePage().props.flash?.success ?? ''
            setTimeout(() => { flashSuccess.value = '' }, 4000)
        },
        onFinish: () => { merging.value = false },
    })
}
</script>
