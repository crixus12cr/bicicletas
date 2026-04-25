<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'activo'
    ];

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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // Relación muchos a muchos con roles (tabla pivote: rol_user)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'rol_user', 'user_id', 'role_id')
                    ->withTimestamps();
    }

    // Verificar si tiene un rol
    public function hasRole($rolNombre)
    {
        return $this->roles()->where('nombre', $rolNombre)->exists();
    }

    // Asignar rol por nombre
    public function assignRole($rolNombre)
    {
        $role = Role::where('nombre', $rolNombre)->first();
        if ($role && !$this->hasRole($rolNombre)) {
            $this->roles()->attach($role);
        }
    }

    // Servicios donde es cliente
    public function serviciosComoCliente()
    {
        return $this->hasMany(Service::class, 'cliente_user_id');
    }

    // Servicios donde es mecánico asignado
    public function serviciosComoMecanico()
    {
        return $this->hasMany(Service::class, 'mecanico_user_id');
    }
}
