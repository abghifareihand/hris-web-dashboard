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
     * Daftar 24 Menu RBAC secara kanonikal (12 Home ESS + 12 Tab Perusahaan)
     */
    public const CANONICAL_MENU_KEYS = [
        // 1. FITUR HOME KARYAWAN (12 ESS)
        'schedules',
        'swap_schedules',
        'agendas',
        'attendances',
        'daily_reports',
        'leaves',
        'overtimes',
        'reimbursements',
        'loans',
        'payslips',
        'feedbacks',
        'announcements',

        // 2. FITUR MANAJERIAL PERUSAHAAN (12 COMPANY)
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
     * Format dan urutkan aksi izin ke 24 menu kanonikal dengan aksi: view, create, edit, delete.
     * Secara otomatis memigrasikan key legacy ke key canonical baru dan menjamin seluruh 24 key selalu ada.
     */
    public static function formatPermissions(?array $rawPermissions): array
    {
        $raw = is_array($rawPermissions) ? $rawPermissions : [];

        // Pemetaan dari key lama ke key kanonikal baru
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

        // Deteksi apakah data raw menggunakan format legacy
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

        // Penanganan khusus 'announcements':
        // Di format legacy lama, 'announcements' berada di Tab Perusahaan.
        // Jika data lama memiliki 'announcements' dan belum memiliki 'company_announcements',
        // migrasikan izin tersebut ke 'company_announcements', dan matikan 'announcements' Home ESS
        // agar tidak terjadi kebocoran hak akses ke kartu Home karyawan.
        if ($isLegacy && isset($raw['announcements']) && !isset($raw['company_announcements'])) {
            $normalized['company_announcements'] = $raw['announcements'];
            $normalized['announcements'] = [
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            ];
        }

        $ordered = [];

        // Selalu hasilkan SEMUA 25 Canonical Keys secara lengkap dan berurutan
        foreach (self::CANONICAL_MENU_KEYS as $key) {
            $actions = is_array($normalized[$key] ?? null) ? $normalized[$key] : [];
            $ordered[$key] = [
                'view' => !empty($actions['view']),
                'create' => !empty($actions['create']),
                'edit' => !empty($actions['edit']),
                'delete' => !empty($actions['delete']),
            ];
        }

        // Aturan keamanan: aksi create, edit, delete pada menu jabatan selalu false untuk non-owner
        if (isset($ordered['company_positions'])) {
            $ordered['company_positions']['create'] = false;
            $ordered['company_positions']['edit'] = false;
            $ordered['company_positions']['delete'] = false;
        }

        return $ordered;
    }

    /**
     * Mutator to ensure 'positions' mutations are never saved for any employee position and keys are ordered.
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
     * Accessor to ensure permissions are always ordered (view, create, edit, delete).
     */
    public function getPermissionsAttribute($value): array
    {
        if (is_null($value)) {
            return self::formatPermissions([]);
        }
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return self::formatPermissions(is_array($decoded) ? $decoded : []);
    }

    /**
     * Hak akses default bawaan sistem untuk jabatan baru (25 canonical keys, false semua)
     */
    public static function defaultPermissions(): array
    {
        return self::formatPermissions([]);
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
