<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function chatbotInstance()
    {
        return $this->belongsToMany(ChatbotInstance::class)
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
