<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricUsage extends Model
{
    use HasFactory;

    // added when collecting more metrics
    protected $fillable = [
        'chatbot_instance_id',
        'agent_id',
        'module_code',
        'conversation_id',
        'message_id',
        'embedding_id',
        'document_id',
        'student_id_hash',
        'prompt_tokens',
        'completion_tokens',
        'temperature',
        'model',
        'latency_ms',
        'duration_ms',
        'status',
        'answer_type',
        'helpful',
        'source',
        'chatbot_version',
        'timestamp',
    ];
    protected $casts = [
        'timestamp' => 'datetime',
        'helpful' => 'boolean',
    ];
    // ends here

    // added when startet frontend mvp
    public function chatbotUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //added when removing chatbot as users
    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }

}
