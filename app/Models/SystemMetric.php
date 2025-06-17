<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        //'chatbot_instance_id',
        'user_id',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'uptime_seconds',
        'queue_size',
        'timestamp',
    ];

    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }
}

