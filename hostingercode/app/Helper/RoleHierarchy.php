<?php

namespace App\Helper;

use App\Models\EmployeeDetails;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Requirement 2.1 & 2.2: Role hierarchy (1 = MR bottom, 8 = Admin top).
 * Higher hierarchy can view lower hierarchy data; lower cannot view upper.
 */
class RoleHierarchy
{
    /**
     * User IDs that the viewer is allowed to see (viewer's level >= target's level).
     * Use for query scoping (e.g. whereIn('user_id', ...)).
     *
     * @param User|null $viewer
     * @param int|null $companyId
     * @return int[]
     */
    public static function userIdsViewableBy(?User $viewer = null, ?int $companyId = null): array
    {
        if (!$viewer) {
            $viewer = user();
        }
        if (!$viewer) {
            return [];
        }
        $companyId = $companyId ?? ($viewer->company_id ?? company()?->id);
        if (!$companyId) {
            return [$viewer->id];
        }
        $viewerLevel = self::userHierarchyLevel($viewer);
        if ($viewerLevel === 8) {
            return User::where('company_id', $companyId)->pluck('id')->toArray();
        }
        if ($viewerLevel === null) {
            return [$viewer->id];
        }
        $userIds = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.company_id', $companyId)
            ->whereNotNull('roles.hierarchy_level')
            ->where('roles.hierarchy_level', '<=', $viewerLevel)
            ->distinct()
            ->pluck('role_user.user_id')
            ->toArray();
        $ids = array_unique($userIds);
        if (!in_array($viewer->id, $ids)) {
            $ids[] = $viewer->id;
        }
        return array_values($ids);
    }
    /**
     * Get the highest (top) hierarchy level for a user's roles.
     * Higher number = higher in hierarchy. Admin = 8.
     *
     * @param User|null $user
     * @return int|null 1-8 or null if user has no role with hierarchy_level
     */
    public static function userHierarchyLevel(?User $user = null): ?int
    {
        if (!$user) {
            $user = user();
        }
        if (!$user || !$user->relationLoaded('roles')) {
            $user?->load('roles');
        }
        if (!$user || !$user->roles) {
            return null;
        }
        $levels = $user->roles->pluck('hierarchy_level')->filter()->values();
        return $levels->isEmpty() ? null : (int) $levels->max();
    }

    /**
     * Whether the viewer is at or above the target in the role hierarchy.
     * Admin (level 8) can view everyone. Lower level cannot view higher.
     *
     * @param User|null $viewer
     * @param User|null $target
     * @return bool
     */
    public static function canViewUserData(?User $viewer = null, ?User $target = null): bool
    {
        if (!$viewer) {
            $viewer = user();
        }
        if (!$viewer || !$target) {
            return false;
        }
        if ($viewer->id === $target->id) {
            return true;
        }
        $viewerLevel = self::userHierarchyLevel($viewer);
        $targetLevel = self::userHierarchyLevel($target);
        if ($viewerLevel === null && $targetLevel === null) {
            return false;
        }
        if ($viewerLevel === null) {
            return false;
        }
        if ($targetLevel === null) {
            return $viewerLevel === 8;
        }
        return $viewerLevel >= $targetLevel;
    }

    /**
     * Whether the current user can view the given user's data (by hierarchy).
     *
     * @param User|null $target
     * @return bool
     */
    public static function canCurrentUserView(?User $target = null): bool
    {
        return self::canViewUserData(user(), $target);
    }

    /**
     * All user IDs in the reporting subtree under a manager (direct and indirect reports),
     * based on employee_details.reporting_to, for the given company.
     *
     * @return int[]
     */
    public static function reportingDescendantUserIds(int $managerUserId, ?int $companyId = null): array
    {
        $companyId = $companyId ?? company()?->id;
        if (! $companyId) {
            return [];
        }

        $frontier = EmployeeDetails::query()
            ->where('company_id', $companyId)
            ->where('reporting_to', $managerUserId)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if ($frontier === []) {
            return [];
        }

        $all = $frontier;

        while (true) {
            $known = array_flip($all);
            $next = EmployeeDetails::query()
                ->where('company_id', $companyId)
                ->whereIn('reporting_to', $frontier)
                ->pluck('user_id')
                ->filter(static function ($id) use ($known) {
                    return ! isset($known[$id]);
                })
                ->unique()
                ->values()
                ->all();

            if ($next === []) {
                break;
            }

            $all = array_merge($all, $next);
            $frontier = $next;
        }

        return array_values(array_unique($all));
    }
}
