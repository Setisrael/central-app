<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'embedding_id',
        'prompt_tokens',
        'completion_tokens',
        'temperature',
        'model',
        'latency_ms',
        'status',
        'student_id_hash',
        'duration_ms',
        'timestamp',
    ];

    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
