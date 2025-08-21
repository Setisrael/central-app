<?php

namespace App\Models;

 use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
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
            'password' => 'hashed',
        ];
    }
    public function canAccessPanel(Panel $panel):bool
    {
        return true;
    }
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }
// pivot table
   /* public function modules()
    {
        return $this->belongsToMany(Module::class);
    }*/
     // FIXED: Updated pivot table relationship to use module_code
     public function modules()
     {
         return $this->belongsToMany(
             Module::class,           // Related model
             'module_user',           // Pivot table name
             'user_id',              // Foreign key on pivot table for current model
             'module_code',          // Foreign key on pivot table for related model
             'id',                   // Local key on current model
             'code'                  // Local key on related model
         );
     }

}
