<template>
    <div class="w-full mt-24">
        <div class="text-secondary text-md">Kostenträger: {{costCenter.name}}</div>
        <div class="text-secondary text-md">Urheberrecht: {{copyright.own_copyright ? 'Ja' : 'Nein'}}</div>
        <div class="text-secondary text-sm mt-2">
            {{copyright.collecting_society}},
            {{copyright.law_size === "small" ? 'kleines Recht' : 'großes Recht'}},
            {{copyright.live_music ? 'mit Livemusik': 'ohne Livemusik'}}
        </div>
        <div class="text-secondary text-sm">{{costCenter.description}}</div>

        <hr class="my-10 border-darkGray">
    </div>
</template>

<script>

export default {
    name: "ProjectSidenav",
    props: {
        costCenter: Object,
        copyright: Object,
        documents: Array,
        contracts: Array,
        moneySources: Array
    }
}
</script>

<style scoped>

</style>
