<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module_code',
        'server_name',
        'user_id',
        'api_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
// 'user_id', 'user_id' added below after pie chart wasn't showing
    public function metricUsages()
    {
        return $this->hasMany(MetricUsage::class,'user_id', 'user_id');
    }

    public function systemMetrics()
    {
        return $this->hasMany(SystemMetric::class,'user_id', 'user_id');
    }
}

