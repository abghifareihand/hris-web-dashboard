<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Daftar 25 Menu RBAC Mobile secara kanonikal (13 Menu Home + 12 Tab Perusahaan)
     */
    public const CANONICAL_MOBILE_KEYS = [
        // 1. FITUR HOME KARYAWAN (13 Menu Home)
        'schedules',
        'swap_schedules',
        'agendas',
        'attendances',
        'daily_reports',
        'leaves',
        'leave_balances',
        'overtimes',
        'reimbursements',
        'loans',
        'payslips',
        'feedbacks',
        'announcements',

        // 2. FITUR TAB PERUSAHAAN (12 Menu Kantor)
        'company_profile',
        'company_employees',
        'company_branches',
        'company_divisions',
        'company_positions',
        'company_shifts',
        'company_schedules',
        'company_leave_categories',
        'company_holidays',
        'company_payrolls',
        'company_announcements',
        'company_settings',
    ];

    /**
     * Daftar 21 Menu RBAC Web Dashboard (12 Master Perusahaan + 9 Pengawasan & Approval)
     */
    public const CANONICAL_WEB_KEYS = [
        'company_profile',
        'employees',
        'branches',
        'divisions',
        'positions',
        'shifts',
        'schedules',
        'leaves',
        'leave_balances',
        'leave_categories',
        'holidays',
        'payrolls',
        'announcements',
        'settings',
        'swap_schedules',
        'overtimes',
        'reimbursements',
        'loans',
        'attendances',
        'daily_reports',
        'feedbacks',
    ];

    /**
     * Alias kanonikal untuk backwards compatibility
     */
    public const CANONICAL_MENU_KEYS = self::CANONICAL_MOBILE_KEYS;

    /**
     * Format dan urutkan izin RBAC terpisah untuk Mobile (25 keys) dan Web (21 keys).
     * Secara otomatis memigrasikan struktur flat / legacy menjadi format { mobile: {...}, web: {...} }.
     */
    public static function formatPermissions(?array $rawPermissions): array
    {
        $raw = is_array($rawPermissions) ? $rawPermissions : [];

        $hasPlatformSeparation = isset($raw['mobile']) || isset($raw['web']);

        if ($hasPlatformSeparation) {
            $rawMobile = is_array($raw['mobile'] ?? null) ? $raw['mobile'] : [];
            $rawWeb = is_array($raw['web'] ?? null) ? $raw['web'] : [];

            // Jika ada key yang di-merge di root level (seperti array_merge($position->permissions, ['payslips' => ...]))
            foreach ($raw as $k => $v) {
                if ($k === 'mobile' || $k === 'web' || !is_array($v)) {
                    continue;
                }
                $rawMobile[$k] = $v;
                $rawWeb[$k] = $v;
            }
        } else {
            // Pemetaan key legacy ke key kanonikal mobile
            $legacyKeyMap = [
                'employee_data' => 'company_employees',
                'branches' => 'company_branches',
                'divisions' => 'company_divisions',
                'positions' => 'company_positions',
                'shifts' => 'company_shifts',
                'employee_schedules' => 'company_schedules',
                'leave_categories' => 'company_leave_categories',
                'holidays' => 'company_holidays',
                'system_settings' => 'company_settings',
                'payrolls' => 'company_payrolls',
            ];

            $isLegacy = isset($raw['employee_data']) || isset($raw['branches']) || isset($raw['shifts']) || isset($raw['employee_schedules']) || isset($raw['all_attendances']) || isset($raw['all_daily_reports']);

            $normalized = [];
            foreach ($raw as $key => $actions) {
                if (!is_array($actions)) {
                    continue;
                }
                $targetKey = $legacyKeyMap[$key] ?? $key;
                if (isset($legacyKeyMap[$key])) {
                    if (!isset($normalized[$targetKey])) {
                        $normalized[$targetKey] = $actions;
                    }
                } else {
                    $normalized[$key] = $actions;
                }
            }

            // Migrasi legacy swap_personal / swap_team ke swap_schedules
            if (!isset($normalized['swap_schedules'])) {
                if (isset($raw['swap_personal']) || isset($raw['swap_team'])) {
                    $p = is_array($raw['swap_personal'] ?? null) ? $raw['swap_personal'] : [];
                    $t = is_array($raw['swap_team'] ?? null) ? $raw['swap_team'] : [];
                    $normalized['swap_schedules'] = [
                        'view' => !empty($p['view']) || !empty($t['view']),
                        'create' => !empty($p['create']) || !empty($t['create']),
                        'edit' => !empty($p['edit']) || !empty($t['edit']),
                        'delete' => !empty($p['delete']) || !empty($t['delete']),
                    ];
                }
            }

            // Migrasi legacy all_attendances ke attendances
            if (!isset($normalized['attendances']) && isset($raw['all_attendances'])) {
                $normalized['attendances'] = $raw['all_attendances'];
            }

            // Migrasi legacy all_daily_reports ke daily_reports
            if (!isset($normalized['daily_reports']) && isset($raw['all_daily_reports'])) {
                $normalized['daily_reports'] = $raw['all_daily_reports'];
            }

            // Migrasi announcements legacy
            if ($isLegacy && isset($raw['announcements']) && !isset($raw['company_announcements'])) {
                $normalized['company_announcements'] = $raw['announcements'];
                $normalized['announcements'] = [
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ];
            }

            $rawMobile = $normalized;
            $rawWeb = [];

            // Mapping flat normalized ke 21 clean web keys
            $webKeyMap = [
                'company_profile' => 'company_profile',
                'company_employees' => 'employees',
                'company_branches' => 'branches',
                'company_divisions' => 'divisions',
                'company_positions' => 'positions',
                'company_shifts' => 'shifts',
                'company_schedules' => 'schedules',
                'company_leave_categories' => 'leave_categories',
                'company_holidays' => 'holidays',
                'company_payrolls' => 'payrolls',
                'company_announcements' => 'announcements',
                'company_settings' => 'settings',
            ];

            foreach ($webKeyMap as $companyKey => $cleanKey) {
                if (isset($normalized[$companyKey])) {
                    $rawWeb[$cleanKey] = $normalized[$companyKey];
                } elseif (isset($normalized[$cleanKey])) {
                    $rawWeb[$cleanKey] = $normalized[$cleanKey];
                }
            }

            // 9 modul pengawasan & approval di Web
            $webApprovalKeys = [
                'swap_schedules', 'leaves', 'leave_balances', 'overtimes',
                'reimbursements', 'loans', 'attendances', 'daily_reports', 'feedbacks'
            ];
            foreach ($webApprovalKeys as $appKey) {
                if (isset($normalized[$appKey])) {
                    $rawWeb[$appKey] = $normalized[$appKey];
                }
            }
        }

        // 1. ORDER & SANITIZE MOBILE (25 Keys)
        $orderedMobile = [];
        foreach (self::CANONICAL_MOBILE_KEYS as $key) {
            $actions = is_array($rawMobile[$key] ?? null) ? $rawMobile[$key] : [];
            $orderedMobile[$key] = [
                'view' => !empty($actions['view']),
                'create' => !empty($actions['create']),
                'edit' => !empty($actions['edit']),
                'delete' => !empty($actions['delete']),
            ];
        }

        // Mobile security guards & disabled constraints
        if (isset($orderedMobile['company_positions'])) {
            $orderedMobile['company_positions']['create'] = false;
            $orderedMobile['company_positions']['edit'] = false;
            $orderedMobile['company_positions']['delete'] = false;
        }
        if (isset($orderedMobile['company_profile'])) {
            $orderedMobile['company_profile']['create'] = false;
            $orderedMobile['company_profile']['delete'] = false;
        }
        if (isset($orderedMobile['company_settings'])) {
            $orderedMobile['company_settings']['create'] = false;
            $orderedMobile['company_settings']['delete'] = false;
        }
        if (isset($orderedMobile['company_payrolls'])) {
            $orderedMobile['company_payrolls']['edit'] = false;
        }
        if (isset($orderedMobile['leave_balances'])) {
            $orderedMobile['leave_balances']['delete'] = false;
        }

        // 2. ORDER & SANITIZE WEB (21 Clean Keys)
        $orderedWeb = [];
        foreach (self::CANONICAL_WEB_KEYS as $key) {
            $actions = is_array($rawWeb[$key] ?? null)
                ? $rawWeb[$key]
                : (is_array($rawWeb['company_' . $key] ?? null) ? $rawWeb['company_' . $key] : []);

            $orderedWeb[$key] = [
                'view' => !empty($actions['view']),
                'create' => !empty($actions['create']),
                'edit' => !empty($actions['edit']),
                'delete' => !empty($actions['delete']),
            ];
        }

        // Web security guards & disabled constraints
        if (isset($orderedWeb['positions'])) {
            $orderedWeb['positions']['create'] = false;
            $orderedWeb['positions']['edit'] = false;
            $orderedWeb['positions']['delete'] = false;
        }
        if (isset($orderedWeb['company_profile'])) {
            $orderedWeb['company_profile']['create'] = false;
            $orderedWeb['company_profile']['delete'] = false;
        }
        if (isset($orderedWeb['settings'])) {
            $orderedWeb['settings']['create'] = false;
            $orderedWeb['settings']['delete'] = false;
        }
        if (isset($orderedWeb['payrolls'])) {
            $orderedWeb['payrolls']['edit'] = false;
        }
        if (isset($orderedWeb['leave_balances'])) {
            $orderedWeb['leave_balances']['create'] = false;
            $orderedWeb['leave_balances']['delete'] = false;
        }

        return [
            'mobile' => $orderedMobile,
            'web' => $orderedWeb,
        ];
    }

    /**
     * Mutator to ensure permissions are always sanitized into { mobile, web } and keys are ordered.
     */
    public function setPermissionsAttribute($value): void
    {
        if (is_array($value)) {
            $value = self::formatPermissions($value);
            $this->attributes['permissions'] = json_encode($value);
        } else {
            $this->attributes['permissions'] = $value;
        }
    }

    /**
     * Accessor to ensure permissions are always returned as { mobile: {...}, web: {...} }.
     */
    public function getPermissionsAttribute($value): array
    {
        if (is_null($value)) {
            return self::defaultPermissions();
        }
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return self::formatPermissions(is_array($decoded) ? $decoded : []);
    }

    /**
     * Hak akses default bawaan sistem untuk jabatan baru
     */
    public static function defaultPermissions(): array
    {
        return self::formatPermissions([]);
    }

    public static function defaultMobilePermissions(): array
    {
        $ordered = [];
        foreach (self::CANONICAL_MOBILE_KEYS as $key) {
            $ordered[$key] = [
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            ];
        }
        return $ordered;
    }

    public static function defaultWebPermissions(): array
    {
        $ordered = [];
        foreach (self::CANONICAL_WEB_KEYS as $key) {
            $ordered[$key] = [
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            ];
        }
        return $ordered;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
