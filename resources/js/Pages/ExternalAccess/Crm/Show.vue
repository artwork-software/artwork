<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-8 py-10 max-w-4xl">
            <h1 class="text-2xl font-bold text-zinc-900">{{ $t('My data') }}</h1>

            <p class="mt-2 text-sm text-zinc-500">
                {{ $t('Editing will be available soon. For now, you can review the data we have on file.') }}
            </p>

            <section v-for="group in groups" :key="group.id" class="mt-10">
                <h2 class="text-lg font-semibold">{{ group.name }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3">
                    <template v-for="property in group.properties" :key="property.id">
                        <dt class="text-sm font-medium text-zinc-600">{{ property.name }}</dt>
                        <dd class="text-sm text-zinc-900">{{ property.value ?? '—' }}</dd>
                    </template>
                </dl>
            </section>

            <section v-if="!groups.length" class="mt-10 text-sm text-zinc-500">
                {{ $t('You have no shared project tabs at the moment.') }}
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'

defineProps({
    groups: { type: Array, required: true },
})
</script>
