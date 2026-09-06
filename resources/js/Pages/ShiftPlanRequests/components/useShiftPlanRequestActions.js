import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { isoWeekToDateRange, toDmy } from '@/Helper/IsoWeek.js';

/**
 * Aktionen der antragstellenden Person an einer Freigabe-Anfrage:
 * - zurückziehen (nur pending, DELETE shift-plan-requests.destroy)
 * - erneut zur Freigabe einreichen (nur rejected, POST commit-shift-workflow-request.store)
 * - zur KW im Dienstplan springen (Filterdatum setzen wie ShiftPlanWeekShortcut, dann shifts.plan)
 *
 * Wird von Show.vue und MyIndex.vue geteilt. Der Toast-Zustand (toast/toastVisible)
 * wird von der aufrufenden Seite gerendert (NotificationToast übersetzt selbst).
 */
export function useShiftPlanRequestActions() {
    const page = usePage();

    const authUserId = () => Number(page.props?.auth?.user?.id);

    // requested_by_user_id fehlt in manchen Listen-Payloads (my.index) → assumeOwn erlaubt
    // dem Aufrufer, die Zugehörigkeit aus dem Kontext zu setzen (z. B. „nur eigene Anfragen").
    const isRequester = (request, assumeOwn = false) => {
        if (!request) return false;
        if (request.requested_by_user_id !== undefined && request.requested_by_user_id !== null) {
            return Number(request.requested_by_user_id) === authUserId();
        }
        if (request.requested_by?.id !== undefined) {
            return Number(request.requested_by.id) === authUserId();
        }
        return assumeOwn;
    };

    const canWithdraw = (request, assumeOwn = false) => request?.status === 'pending' && isRequester(request, assumeOwn);
    const canResubmit = (request, assumeOwn = false) => request?.status === 'rejected' && isRequester(request, assumeOwn);

    const toast = ref(null);
    const toastVisible = ref(false);
    const showToast = (title, description = '', type = 'success') => {
        toast.value = { title, description, type };
        toastVisible.value = true;
    };

    // 'withdraw' | 'resubmit' | 'goto' | null — für processing-Zustand der Buttons
    const processing = ref(null);

    const weekLabel = (request) => {
        const range = isoWeekToDateRange(request?.week_number, request?.year);
        if (!range) return `KW ${request?.week_number ?? '–'} / ${request?.year ?? '–'}`;
        return `KW ${request.week_number} / ${request.year} (${toDmy(range.monday)} – ${toDmy(range.sunday)})`;
    };

    const withdraw = (request, { onSuccess = null } = {}) => {
        if (!request?.id || processing.value) return;
        processing.value = 'withdraw';
        router.delete(route('shift-plan-requests.destroy', request.id), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Request withdrawn', 'The shifts of this request are released again and can be resubmitted later.');
                if (typeof onSuccess === 'function') onSuccess();
            },
            onError: (errors) => {
                const messages = Object.values(errors || {}).flat().filter(Boolean);
                showToast('The request could not be withdrawn.', messages[0] ?? '', 'error');
            },
            onFinish: () => {
                processing.value = null;
            },
        });
    };

    const resubmit = (request, craftId = null, { onSuccess = null } = {}) => {
        const resolvedCraftId = craftId ?? request?.craft_id ?? request?.craft?.id ?? null;
        if (!request || !resolvedCraftId || processing.value) return;
        processing.value = 'resubmit';
        router.post(
            route('commit-shift-workflow-request.store'),
            {
                week_number: Number(request.week_number),
                year: Number(request.year),
                craft_ids: [Number(resolvedCraftId)],
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Request resubmitted', 'The shift plan was submitted for approval again.');
                    if (typeof onSuccess === 'function') onSuccess();
                },
                onError: (errors) => {
                    const messages = Object.values(errors || {}).flat().filter(Boolean);
                    showToast('The request could not be resubmitted.', messages[0] ?? '', 'error');
                },
                onFinish: () => {
                    processing.value = null;
                },
            }
        );
    };

    // Dienstplan liest den Zeitraum aus dem gespeicherten Nutzerfilter (kein Query-Parameter):
    // erst Filterdatum auf Mo–So der KW setzen, dann shifts.plan öffnen.
    const goToWeekInShiftPlan = async (request) => {
        const range = isoWeekToDateRange(request?.week_number, request?.year);
        if (!range || processing.value) return;
        processing.value = 'goto';
        const user = page.props?.auth?.user;
        try {
            await axios.patch(route('update.user.shift.calendar.filter.dates', user.id), {
                start_date: range.start,
                end_date: range.end,
                isDailyView: !!user.shift_plan_daily_view,
            });
        } catch (e) {
            // Filter konnte nicht gesetzt werden → Dienstplan trotzdem öffnen (aktueller Zeitraum)
        } finally {
            processing.value = null;
            router.visit(route('shifts.plan'));
        }
    };

    return {
        isRequester,
        canWithdraw,
        canResubmit,
        weekLabel,
        toast,
        toastVisible,
        showToast,
        processing,
        withdraw,
        resubmit,
        goToWeekInShiftPlan,
    };
}
