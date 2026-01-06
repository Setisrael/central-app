<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_instance_id',
        'cpu_usage',
        'ram_usage',
        'disk_usage',
        'uptime_seconds',
        'queue_size',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function chatbotUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }
}
