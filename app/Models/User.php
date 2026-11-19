<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Employee;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'birth_date',
        'address',
        'password',
        'role',
        'avatar',
        'fcm_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the user has a registered company.
     */
    protected $appends = [
        'has_company',
    ];

    public function getHasCompanyAttribute(): ?bool
    {
        if ($this->role === 'owner') {
            return $this->company()->exists();
        }
        return null;
    }

    /**
     * Get the company profile associated with the user.
     * Owner -> owns the company (via user_id).
     * Employee -> company of their employee profile.
     */
    public function getCompanyAttribute()
    {
        if ($this->role === 'owner') {
            return $this->getRelationValue('company');
        }

        return $this->employee?->company;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has permission for a specific menu and action.
     */
    public function hasPermission(string $menuKey, string $action = 'view', ?string $platform = null): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }

        if (!$this->isEmployee() || !$this->employee) {
            return false;
        }

        // Security guard: Non-owner/admin can NEVER have create, edit, or delete permissions on 'positions' or 'company_positions'
        if (in_array($menuKey, ['positions', 'company_positions']) && in_array($action, ['create', 'edit', 'delete'])) {
            return false;
        }

        $platform = $platform ?? (request()?->is('api/*') ? 'mobile' : 'web');
        $effective = $this->getEffectivePermissions($platform);

        // Direct check
        if (isset($effective[$menuKey][$action])) {
            return (bool) $effective[$menuKey][$action];
        }

        // Bi-directional alias fallback between company_* and clean web keys
        if (str_starts_with($menuKey, 'company_')) {
            $cleanKey = substr($menuKey, 8);
            if (isset($effective[$cleanKey][$action])) {
                return (bool) $effective[$cleanKey][$action];
            }
        } else {
            $prefixedKey = 'company_' . $menuKey;
            if (isset($effective[$prefixedKey][$action])) {
                return (bool) $effective[$prefixedKey][$action];
            }
        }

        return false;
    }

    /**
     * Get allowed branch IDs for data scoping.
     * Owner/Admin -> null (unrestricted).
     * Employee -> array of IDs (empty array if no branches granted).
     */
    public function getAllowedBranchIds(): ?array
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return null;
        }

        if (!$this->isEmployee() || !$this->employee || !$this->employee->position) {
            return [];
        }

        return $this->employee->position->branches()->pluck('branches.id')->all();
    }

    /**
     * Get allowed division IDs for data scoping.
     * Owner/Admin -> null (unrestricted).
     * Employee -> array of IDs (empty array if no divisions granted).
     */
    public function getAllowedDivisionIds(): ?array
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return null;
        }

        if (!$this->isEmployee() || !$this->employee || !$this->employee->position) {
            return [];
        }

        return $this->employee->position->divisions()->pluck('divisions.id')->all();
    }

    /**
     * Get effective permissions array for Inertia Vue or Mobile API.
     */
    public function getEffectivePermissions(?string $platform = null): array
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return ['*' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true]];
        }

        $platform = $platform ?? (request()?->is('api/*') ? 'mobile' : 'web');

        $raw = $this->employee?->position?->permissions ?? Position::defaultPermissions();
        $formatted = Position::formatPermissions(is_array($raw) ? $raw : []);

        return $formatted[$platform] ?? ($formatted['mobile'] ?? []);
    }

    /**
     * Get the resolved company for this user (both Owner and Employee roles).
     */
    public function getCompany(): ?Company
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return $this->company;
        }

        return $this->employee?->company;
    }

    /**
     * Get the company profile associated with the user (Owner role).
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * Get the employee profile associated with the user (Employee role).
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the agendas associated with the user.
     */
    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }
}


