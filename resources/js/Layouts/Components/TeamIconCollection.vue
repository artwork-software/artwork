<template>
    <img v-if="iconName === 'icon_ausstellung'" src="/TeamIconSvgs/icon_ausstellung.svg" alt="ausstellung">
    <img v-else-if="iconName === 'icon_ausstellung_foto'" src="/TeamIconSvgs/icon_ausstellung_foto.svg" alt="ausstellung_foto">
    <img v-else-if="iconName === 'icon_bildung_bibliothek'" src="/TeamIconSvgs/icon_bildung_bibliothek.svg" alt="bildung_bibliothek">
    <img v-else-if="iconName === 'icon_bildung_kulturell'" src="/TeamIconSvgs/icon_bildung_kulturell.svg" alt="bildung_kulturell">
    <img v-else-if="iconName === 'icon_dienst_abend'" src="/TeamIconSvgs/icon_dienst_abend.svg" alt="dienst_abend">
    <img v-else-if="iconName === 'icon_dienst_kasse'" src="/TeamIconSvgs/icon_dienst_kasse.svg" alt="dienst_kasse">
    <img v-else-if="iconName === 'icon_dienst_reinigung'" src="/TeamIconSvgs/icon_dienst_reinigung.svg" alt="dienst_reinigung">
    <img v-else-if="iconName === 'icon_dienst_sicherheit'" src="/TeamIconSvgs/icon_dienst_sicherheit.svg" alt="dienst_sicherheit">
    <img v-else-if="iconName === 'icon_dramaturgie'" src="/TeamIconSvgs/icon_dramaturgie.svg" alt="dramaturgie">
    <img v-else-if="iconName === 'icon_dramaturgie_kurator'" src="/TeamIconSvgs/icon_dramaturgie_kurator.svg" alt="dramaturgie_kurator">
    <img v-else-if="iconName === 'icon_dramaturgie_tanz'" src="/TeamIconSvgs/icon_dramaturgie_tanz.svg" alt="dramaturgie_tanz">
    <img v-else-if="iconName === 'icon_einhorn'" src="/TeamIconSvgs/icon_einhorn.svg" alt="einhorn">
    <img v-else-if="iconName === 'icon_festival'" src="/TeamIconSvgs/icon_festival.svg" alt="festival">
    <img v-else-if="iconName === 'icon_kommunikation_marketing'" src="/TeamIconSvgs/icon_kommunikation_marketing.svg" alt="kommunikation_marketing">
    <img v-else-if="iconName === 'icon_kommunikation_vertrieb'" src="/TeamIconSvgs/icon_kommunikation_vertrieb.svg" alt="kommunikation_vertrieb">
    <img v-else-if="iconName === 'icon_orga_finanzen'" src="/TeamIconSvgs/icon_orga_finanzen.svg" alt="orga_finanzen">
    <img v-else-if="iconName === 'icon_orga_kuenstlerischesbuero'" src="/TeamIconSvgs/icon_orga_kuenstlerischesbuero.svg" alt="orga_kuenstlerischesbuero">
    <img v-else-if="iconName === 'icon_orga_leitung'" src="/TeamIconSvgs/icon_orga_leitung.svg" alt="orga_leitung">
    <img v-else-if="iconName === 'icon_orga_personal'" src="/TeamIconSvgs/icon_orga_personal.svg" alt="orga_personal">
    <img v-else-if="iconName === 'icon_orga_sekretariat'" src="/TeamIconSvgs/icon_orga_sekretariat.svg" alt="orga_sekretariat">
    <img v-else-if="iconName === 'icon_orga_verwaltung'" src="/TeamIconSvgs/icon_orga_verwaltung.svg" alt="orga_verwaltung">
    <img v-else-if="iconName === 'icon_technik'" src="/TeamIconSvgs/icon_technik.svg" alt="technik">
    <img v-else-if="iconName === 'icon_technik_audiovideo'" src="/TeamIconSvgs/icon_technik_audiovideo.svg" alt="technik_audiovideo">
    <img v-else-if="iconName === 'icon_technik_buehne'" src="/TeamIconSvgs/icon_technik_buehne.svg" alt="technik_buehne">
    <img v-else-if="iconName === 'icon_technik_haus'" src="/TeamIconSvgs/icon_technik_haus.svg" alt="technik_haus">
    <img v-else-if="iconName === 'icon_technik_licht'" src="/TeamIconSvgs/icon_technik_licht.svg" alt="technik_licht">
    <img v-else-if="iconName === 'icon_technik_veranstaltung'" src="/TeamIconSvgs/icon_technik_veranstaltung.svg" alt="technik_verwaltung">
    <img v-else-if="iconName === 'icon_vermietung'" src="/TeamIconSvgs/icon_vermietung.svg" alt="vermietung">

</template>

<script>

export default {
    name: "TeamIconCollection",
    components: {

    },
    props:['iconName'],
}
</script>

<style scoped>

</style>
