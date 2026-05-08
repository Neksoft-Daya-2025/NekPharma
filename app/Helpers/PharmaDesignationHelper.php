<?php

namespace App\Helpers;

use App\Models\Designation;

/**
 * Helper for pharma designation matching.
 * Uses flexible name matching to support both "Area Business Manager" and "Area Business Manager (ABM)" etc.
 */
class PharmaDesignationHelper
{
    public static function isABM($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        return str_contains($name, 'Area Business Manager') || str_contains($name, 'ABM');
    }

    public static function isRBM($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        return str_contains($name, 'Regional') && (str_contains($name, 'Business Manager') || str_contains($name, 'Manager'));
    }

    public static function isZM($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        return str_contains($name, 'Zonal Manager') || str_contains($name, 'ZM');
    }

    /**
     * Area Sales Manager (territory like ABM; not the same as generic / national "Sales Manager").
     */
    public static function isASM($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if (strcasecmp($name, 'ASM') === 0) {
            return true;
        }

        return str_contains($name, 'Area Sales Manager');
    }

    public static function isSalesManager($designation): bool
    {
        if (static::isASM($designation)) {
            return false;
        }
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        return str_contains($name, 'Sales Manager');
    }

    /**
     * Returns true if designation is ABM, RBM, ZM, ASM, or generic Sales Manager (ABM & above for Ex/Out-Station add/edit/delete).
     */
    public static function isABMOrAbove($designation): bool
    {
        return static::isABM($designation) || static::isRBM($designation) || static::isZM($designation) || static::isASM($designation) || static::isSalesManager($designation);
    }

    /**
     * Returns true if designation is ABM, RBM, or ZM (uses Area/Region/Zone allocation, not headquarter_id).
     */
    public static function usesGeographyAllocation($designation): bool
    {
        return static::isABM($designation) || static::isRBM($designation) || static::isZM($designation);
    }

    public static function isMISExecutive($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        return str_contains($name, 'MIS Executive') || str_contains($name, 'MIS');
    }

    /**
     * Medical Representative (field rep): should use assigned headquarter only, not area-wide HQ expansion.
     */
    public static function isMedicalRepresentative($designation): bool
    {
        $name = static::getDesignationName($designation);
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if (strcasecmp($name, 'MR') === 0) {
            return true;
        }

        return str_contains($name, 'Medical Representative')
            || str_contains($name, 'Medical Rep');
    }

    /**
     * Returns true for admin, accountant, fsa-executive, or MIS Executive designation (full CFA access).
     */
    public static function hasFullCFAAccess(): bool
    {
        if (in_array('admin', user_roles()) || in_array('accountant', user_roles()) || in_array('fsa-executive', user_roles())) {
            return true;
        }
        $emp = user()->employeeDetail ?? user()->employeeDetails;
        return $emp && $emp->designation && static::isMISExecutive($emp->designation);
    }

    /**
     * DataTable / row: may edit this CFA stockist invoice (matches InvoiceController policy).
     */
    public static function canEditCfaStockistInvoiceRow($row): bool
    {
        if (static::hasFullCFAAccess()) {
            return true;
        }
        $userId = (int) \App\Helper\UserService::getUserId();
        $uid = (int) (user()->id ?? 0);
        $perm = user()->permission('edit_cfa_stockist_invoices');
        if (in_array($perm, ['all', 'added', 'owned', 'both'], true)) {
            if ($perm === 'all') {
                return true;
            }
            if ($perm === 'added' && ((int) $row->added_by === $userId || (int) $row->added_by === $uid)) {
                return true;
            }
            if ($perm === 'owned' && (int) $row->client_id === $userId) {
                return true;
            }
            if ($perm === 'both' && ((int) $row->client_id === $userId || (int) $row->added_by === $userId || (int) $row->added_by === $uid)) {
                return true;
            }
        }

        return in_array('client', user_roles(), true) && (int) $row->client_id === $uid;
    }

    /**
     * DataTable / row: may delete this CFA stockist invoice (matches InvoiceController policy).
     */
    public static function canDeleteCfaStockistInvoiceRow($row): bool
    {
        if (static::hasFullCFAAccess()) {
            return true;
        }
        $userId = (int) \App\Helper\UserService::getUserId();
        $uid = (int) (user()->id ?? 0);
        $perm = user()->permission('delete_cfa_stockist_invoices');
        if (in_array($perm, ['all', 'added', 'owned', 'both'], true)) {
            if ($perm === 'all') {
                return true;
            }
            if ($perm === 'added' && ((int) $row->added_by === $userId || (int) $row->added_by === $uid)) {
                return true;
            }
            if ($perm === 'owned' && (int) $row->client_id === $userId) {
                return true;
            }
            if ($perm === 'both' && ((int) $row->client_id === $userId || (int) $row->added_by === $userId || (int) $row->added_by === $uid)) {
                return true;
            }
        }

        return in_array('client', user_roles(), true) && (int) $row->client_id === $uid;
    }

    private static function getDesignationName($designation): ?string
    {
        if ($designation === null) {
            return null;
        }
        if ($designation instanceof Designation) {
            return $designation->name ?? null;
        }
        if (is_string($designation)) {
            return $designation;
        }
        if (is_object($designation) && isset($designation->name)) {
            return $designation->name;
        }
        return null;
    }
}
