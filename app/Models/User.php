<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Support\PhoneNumber;

#[Fillable(['name', 'email', 'mobile', 'password', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }
        });

        static::created(function ($user) {
            $user->linkUnlinkedStudents();
        });

        static::deleting(function ($user) {
            if ($user->hasRole('saas_admin')) {
                throw new \Exception('SaaS Admin accounts cannot be deleted.');
            }
        });
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function linkUnlinkedStudents(): int
    {
        $normalizedMobile = PhoneNumber::normalize($this->mobile);
        if (!$normalizedMobile) {
            return 0;
        }

        $linked = 0;

        Student::whereNull('user_id')->chunkById(200, function ($students) use (&$linked, $normalizedMobile) {
            foreach ($students as $student) {
                if (PhoneNumber::normalize($student->contact_number) === $normalizedMobile) {
                    $student->update(['user_id' => $this->id]);
                    $linked++;
                }
            }
        });

        return $linked;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return new class($this->newRelatedInstance(Role::class)->newQuery(), $this, 'role_user', 'user_id', 'role_id', 'id', 'id', 'roles') extends \Illuminate\Database\Eloquent\Relations\BelongsToMany {
            public function detach($ids = null, $touch = true)
            {
                $parentRoleId = \App\Models\Role::where('slug', 'parent')->value('id');
                if ($parentRoleId) {
                    if (is_null($ids)) {
                        // If detaching all roles, keep parent role if they have it
                        $hasParent = $this->parent->roles()->where('roles.slug', 'parent')->exists();
                        if ($hasParent) {
                            $ids = $this->parent->roles()->where('roles.slug', '!=', 'parent')->pluck('roles.id')->toArray();
                        }
                    } else {
                        // Remove parent role from the IDs to detach
                        $parsedIds = array_keys($this->parseIds($ids));
                        $ids = array_diff($parsedIds, [$parentRoleId]);
                    }
                }
                return parent::detach($ids, $touch);
            }

            public function sync($ids, $detaching = true)
            {
                $parentRoleId = \App\Models\Role::where('slug', 'parent')->value('id');
                if ($parentRoleId) {
                    $hasParent = $this->parent->roles()->where('roles.slug', 'parent')->exists();
                    if ($hasParent) {
                        $parsed = $this->parseIds($ids);
                        if (!isset($parsed[$parentRoleId])) {
                            $parsed[$parentRoleId] = [];
                        }
                        $ids = $parsed;
                    }
                }
                return parent::sync($ids, $detaching);
            }
        };
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('roles.slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('roles.slug', $roles)->exists();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if ($roleModel) {
            $this->roles()->syncWithoutDetaching($roleModel->id);
        }
    }
}
