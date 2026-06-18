<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use  HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'color', // Add this line
        'last_login_at',
        'last_login_ip'

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopeWithDepartment($query)
    {
        return $query->with('department');
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Accessors
    public function getAvatarUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random';
    }

    // Role Methods
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }


    // Helper Methods
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    public function getDepartmentNameAttribute(): string
    {
        return $this->department ? $this->department->name : 'No Department';
    }

    public function getRoleNamesAttribute(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function getPermissionNamesAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

      // Color helper methods
    public function getColorAttribute($value)
    {
        return $value ?: '#6238B3'; // Default color if null
    }

    public static function getDefaultColors(): array
    {
        return [
            '#D63B38', // Valencia Red
            '#F7A614', // Sun Orange
            '#5BC43A', // Apple Green
            '#00B8D6', // Cerulean Blue
            '#0066FE', // Mariner
            '#6238B3', // Purple Heart (default)
            '#EB5393', // French Rose
            '#28a745', // Success Green
            '#17a2b8', // Info Cyan
            '#ffc107', // Warning Yellow
            '#6f42c1', // Purple
            '#e83e8c', // Pink
            '#fd7e14', // Orange
            '#20c997', // Teal
            '#6c757d', // Gray
        ];
    }

    // Generate random color for new users
    public static function getRandomColor(): string
    {
        $colors = self::getDefaultColors();
        return $colors[array_rand($colors)];
    }

    public function tasks()
{
    return $this->belongsToMany(Task::class, 'task_users');
}
}