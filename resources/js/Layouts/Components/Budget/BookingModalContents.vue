<template>
    <div class="grid grid-cols-2">
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Creditor') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.kreditor }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Amount') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{
                Number(
                    String(booking.buchungsbetrag).replace(',', '.')
                ).toLocaleString(
                    'de-DE',
                    {
                        minimumFractionDigits: 2
                    }
                )
            }} EUR</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Booking text') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.buchungstext }}</span>
        <span class="text-sm/5 font-bold text-text-subtle mt-4">{{ $t('Document number') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black mt-4">{{ booking.belegnummer }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Document date') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">
                                    {{ this.formatBookingDataDate(booking.belegdatum) }}
                                </span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('General ledger account') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.sa_kto }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Sage period') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.periode }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Debit account') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.kto_soll }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Credit account') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.kto_haben }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Cost bearer') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.kst_traeger }}</span>
        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Cost center') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black">{{ booking.kst_stelle }}</span>
        <span class="text-sm/5 font-bold text-text-subtle mt-4">{{ $t('Booking date') }}</span>
        <span class="text-sm/5 font-bold text-text-subtle text-black mt-4">
                                    {{ this.formatBookingDataDate(booking.buchungsdatum) }}
                                </span>
    </div>
</template>

<script>
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import {formatBookingDataDate} from "@/Layouts/Components/Budget/bookingDate.js";

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
        formatBookingDataDate
    }
};
</script>
