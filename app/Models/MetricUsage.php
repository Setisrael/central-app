<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricUsage extends Model
{
    use HasFactory;

    /*protected $fillable = [
        'chatbot_instance_id',
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
    ];*/

    // added when collecting more metrics
    protected $fillable = [
        'chatbot_instance_id',
        'agent_id',
        'module_id',
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

  /*  public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }*/

  /*  public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }*/

    // added when startet frontend mvp
    public function chatbotUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

  /*  public function chatbotInstance()
    {
        return $this->hasOne(ChatbotInstance::class, 'user_id', 'user_id');
    }*/

    //added when removing chatbot as users
    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }

}
