<template>
    <ArtworkBaseModal :title="group ? group.name : $t('New group')"
                      :description="$t('Passes, subscriptions and discount codes with this group count for every production in it.')"
                      modal-size="sm:max-w-2xl" @close="$emit('close')">
        <div class="flex flex-col gap-5 text-[13px]">
            <p v-if="group?.sold" class="rounded-md bg-surface-sunken px-3.5 py-3 text-[13px] leading-5 text-text-muted">
                {{ $t('Sold passes count for this group: productions can be added, but not taken out.') }}
            </p>

            <BaseInput id="group-name" v-model="draft.name" label="Name" required :error="form.errors.name" maxlength="60"
                       :placeholder="$t('e.g. Workshops')" />

            <div>
                <div class="mb-1 flex items-baseline justify-between gap-3">
                    <span class="font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Productions') }}</span>
                    <span v-if="productions.length > 0" class="text-xs text-text-subtle tabular-nums">
                        {{ $t('{count} of {total} selected', { count: draft.eventIds.length, total: productions.length }) }}
                    </span>
                </div>
                <p v-if="productions.length === 0" class="text-xs text-text-subtle">{{ $t('Artwork-Tickets has no productions yet. Release one from a project first.') }}</p>
                <ProductionPicker v-else v-model="draft.eventIds" :productions="productions" :fixed-ids="group?.sold ? group.eventIds : []"
                                  id-prefix="group-production" />
                <p class="mt-1.5 text-xs text-text-subtle">{{ $t('Add new productions here: everything with the group counts for them right away.') }}</p>
            </div>

            <ul v-if="Object.keys(form.errors).length" class="rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
            </ul>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="form.processing" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="form.processing || draft.name.trim().length < 2" @click="submit">
                {{ form.processing ? $t('Saving…') : group ? $t('Save') : $t('Create production group') }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ProductionPicker from '@/Pages/Settings/Tickets/ProductionPicker.vue'

/* One group of productions, new or existing. While a sold pass counts on it, its productions
   stay — tickets refuses the same. */
const props = defineProps({
    /** The group as tickets sends it; null for a new one. */
    group: { type: Object, default: null },
    productions: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const draft = reactive({ name: props.group?.name ?? '', eventIds: [...(props.group?.eventIds ?? [])] })
const form = useForm({})

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('close') }
    const request = form.transform(() => ({ name: draft.name.trim(), eventIds: draft.eventIds }))

    if (props.group) {
        request.put(route('settings.tickets.groups.update', props.group.id), options)
    } else {
        request.post(route('settings.tickets.groups.store'), options)
    }
}
</script>
