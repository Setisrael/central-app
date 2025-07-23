<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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

    // added when startet frontend
    /*public function isChatbot(): bool
    {
        return $this->is_chatbot === true;
    }

    public function isHuman(): bool
    {
        return $this->is_chatbot === false;
    }*/

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

   /* public function chatbotInstance()
    {
        return $this->hasOne(ChatbotInstance::class, 'user_id');
    }

    public function metricUsages()
    {
        return $this->hasMany(MetricUsage::class, 'user_id');
    }

    public function systemMetrics()
    {
        return $this->hasMany(SystemMetric::class, 'user_id');
    }*/
// pivot table
    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }
}
