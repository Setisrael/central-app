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

   /* public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }*/
  /*  public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }*/

    public function chatbotUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
   /* public function chatbotInstance()
    {
        return $this->hasOne(ChatbotInstance::class, 'user_id', 'user_id');
    }*/
   /* public function chatbotInstance()
    {
        return $this->belongsTo(\App\Models\ChatbotInstance::class, 'user_id', 'user_id');
    }
    */
    //added when removing chatbots as users
    public function chatbotInstance()
    {
        return $this->belongsTo(ChatbotInstance::class);
    }


}

