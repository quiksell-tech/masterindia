<?php
// app/Services/OrderActionService.php
namespace App\Services;

class OrderActionService
{
    // Action constants (merged here)
    public const CREATE_IRN   = 'create_irn';
    public const CANCEL_IRN   = 'cancel_irn';

    public const CREATE_EWAY  = 'create_eway';
    public const UPDATE_EWAY  = 'update_eway';
    public const CANCEL_EWAY  = 'cancel_eway';

    /**
     * Get all allowed actions for given statuses
     */
    public static function allowed(string $irnStatus, string $ewayStatus): array
    {
        $actions = [];

        /* ---------- IRN RULES ---------- */

        // IRN New / Error → Create
        if (in_array($irnStatus, ['N','E'])) {
            $actions[] = self::CREATE_IRN;
        }

        // IRN Created → Cancel
        if ($irnStatus === 'C') {
            $actions[] = self::CANCEL_IRN;
        }

        /* ---------- E-WAY RULES ---------- */

        // E-Way depends on IRN existence
        if ($irnStatus === 'C') {

            // Create E-Way (NEW / ERROR / CANCELLED)
            if (in_array($ewayStatus, ['N','E','X'])) {
                $actions[] = self::CREATE_EWAY;
            }

            // Update & Cancel only when CREATED
            if ($ewayStatus === 'C') {
                $actions[] = self::UPDATE_EWAY;
                $actions[] = self::CANCEL_EWAY;
            }
        }
        // IRN cancelled but E-Way exists → allow cancel
        if ($irnStatus === 'X' && $ewayStatus === 'C') {
            $actions[] = self::CANCEL_EWAY;
        }
        // IRN cancelled And E-Way cancelled → allow IRN Create
        if ($irnStatus === 'X' && $ewayStatus === 'X') {
            $actions[] = self::CREATE_IRN;
        }

        return array_values(array_unique($actions));
    }

    /**
     * Check if a single action is allowed
     */
    public static function can(
        string $action,
        string $irnStatus,
        string $ewayStatus
    ): bool {
        return in_array($action, self::allowed($irnStatus, $ewayStatus), true);
    }
}
