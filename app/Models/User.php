<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'mobile', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
                        $hasParent = $this->parent->roles()->where('slug', 'parent')->exists();
                        if ($hasParent) {
                            $ids = $this->parent->roles()->where('slug', '!=', 'parent')->pluck('id')->toArray();
                        }
                    } else {
                        // Remove parent role from the IDs to detach
                        $ids = collect($this->parseIds($ids))->reject(function ($id) use ($parentRoleId) {
                            return $id == $parentRoleId;
                        })->all();
                    }
                }
                return parent::detach($ids, $touch);
            }

            public function sync($ids, $detaching = true)
            {
                $parentRoleId = \App\Models\Role::where('slug', 'parent')->value('id');
                if ($parentRoleId) {
                    $hasParent = $this->parent->roles()->where('slug', 'parent')->exists();
                    if ($hasParent) {
                        $parsedIds = $this->parseIds($ids);
                        // Determine if input is associative or flat
                        $isAssociative = count(array_filter(array_keys($ids), 'is_string')) > 0 || (count($ids) > 0 && is_array(reset($ids)));
                        if ($isAssociative) {
                            if (!isset($ids[$parentRoleId])) {
                                $ids[$parentRoleId] = [];
                            }
                        } else {
                            if (!in_array($parentRoleId, $ids)) {
                                $ids[] = $parentRoleId;
                            }
                        }
                    }
                }
                return parent::sync($ids, $detaching);
            }
        };
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::where('slug', $role)->first();
        if ($roleModel) {
            $this->roles()->syncWithoutDetaching($roleModel->id);
        }
    }
}
