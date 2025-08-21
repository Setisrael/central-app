<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    // FIXED: Updated pivot table relationship to use module_code
    public function chatbotInstances()
    {
        return $this->belongsToMany(
            ChatbotInstance::class,     // Related model
            'chatbot_instance_module',  // Pivot table name
            'module_code',             // Foreign key on pivot table for current model
            'chatbot_instance_id',     // Foreign key on pivot table for related model
            'code',                    // Local key on current model
            'id'                       // Local key on related model
        )->withTimestamps();
    }

    // FIXED: Updated pivot table relationship to use module_code
    public function users()
    {
        return $this->belongsToMany(
            User::class,        // Related model
            'module_user',      // Pivot table name
            'module_code',      // Foreign key on pivot table for current model
            'user_id',          // Foreign key on pivot table for related model
            'code',             // Local key on current model
            'id'                // Local key on related model
        );
    }
}
