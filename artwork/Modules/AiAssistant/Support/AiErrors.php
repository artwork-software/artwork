<?php

namespace Artwork\Modules\AiAssistant\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Fehlerantworten mit Maschinen-Code (ai-scenarios-3): fehlt ein Recht an einem **sichtbaren**
 * Objekt, antwortet der Core mit `{"error": "<code>"}`, damit der Assistent den Grund nennen kann.
 * Unsichtbare Objekte bleiben 404.
 */
final class AiErrors
{
    public const NO_BUDGET_ACCESS = 'no_budget_access';

    public const NO_SHIFT_PLAN_ACCESS = 'no_shift_plan_access';

    public const NO_WORK_TIME_ACCESS = 'no_work_time_access';

    public const NO_WORK_TIME_ACCOUNT = 'no_work_time_account';

    public const NO_CONTRACT_ACCESS = 'no_contract_access';

    public const NO_AVAILABILITY_ACCESS = 'no_availability_access';

    public const SHIFTS_HAVE_ASSIGNMENTS = 'shifts_have_assignments';

    public static function abort(string $code, int $status = Response::HTTP_FORBIDDEN): never
    {
        abort(response()->json(['error' => $code], $status));
    }
}
