<template>
    <div class="grid grid-cols-2">
        <span class="xsLight">{{ $t('Creditor') }}</span>
        <span class="xsLight text-black">{{ booking.kreditor }}</span>
        <span class="xsLight">{{ $t('Amount') }}</span>
        <span class="xsLight text-black">{{
                Number(
                    String(booking.buchungsbetrag).replace(',', '.')
                ).toLocaleString(
                    'de-DE',
                    {
                        minimumFractionDigits: 2
                    }
                )
            }} EUR</span>
        <span class="xsLight">{{ $t('Booking text') }}</span>
        <span class="xsLight text-black">{{ booking.buchungstext }}</span>
        <span class="xsLight mt-4">{{ $t('Document number') }}</span>
        <span class="xsLight text-black mt-4">{{ booking.belegnummer }}</span>
        <span class="xsLight">{{ $t('Document date') }}</span>
        <span class="xsLight text-black">
                                    {{ this.formatBookingDataDate(booking.belegdatum) }}
                                </span>
        <span class="xsLight">{{ $t('General ledger account') }}</span>
        <span class="xsLight text-black">{{ booking.sa_kto }}</span>
        <span class="xsLight">{{ $t('Sage period') }}</span>
        <span class="xsLight text-black">{{ booking.periode }}</span>
        <span class="xsLight">{{ $t('Debit account') }}</span>
        <span class="xsLight text-black">{{ booking.kto_soll }}</span>
        <span class="xsLight">{{ $t('Credit account') }}</span>
        <span class="xsLight text-black">{{ booking.kto_haben }}</span>
        <span class="xsLight">{{ $t('Cost bearer') }}</span>
        <span class="xsLight text-black">{{ booking.kst_traeger }}</span>
        <span class="xsLight">{{ $t('Cost center') }}</span>
        <span class="xsLight text-black">{{ booking.kst_stelle }}</span>
        <span class="xsLight mt-4">{{ $t('Booking date') }}</span>
        <span class="xsLight text-black mt-4">
                                    {{ this.formatBookingDataDate(booking.buchungsdatum) }}
                                </span>
    </div>
</template>

<script>
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";

export default {
    name: "BookingModalContents",
    mixins: [CurrencyFloatToStringFormatter],
    props: {
        booking: {
            type: Object,
            default: () => []
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            const [date] = dateString.split('T');
            const [year, month, day] = date.split('-');
            return `${day}.${month}.${year}`;
        }
    }
};
</script>
