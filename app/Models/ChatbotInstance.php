<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class ChatbotInstance extends Model
{
   // use HasFactory;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens;

    protected $fillable = ['name', 'server_name'];

    public function metricUsages()
    {
        return $this->hasMany(MetricUsage::class);
    }

    public function systemMetrics()
    {
        return $this->hasMany(SystemMetric::class);
    }

    // UPDATED: Use module_code as the foreign key instead of module_id
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'chatbot_instance_module', 'chatbot_instance_id', 'module_code', 'id', 'code')
            ->withTimestamps();
    }
 // to delete token upon chatbot delete
    protected static function booted()
    {
        static::deleting(function ($chatbot) {
            PersonalAccessToken::where('tokenable_id', $chatbot->id)
                ->where('tokenable_type', self::class)
                ->delete();
        });
    }
}

