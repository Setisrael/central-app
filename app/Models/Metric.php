<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    //
    protected $fillable = ['instance_id', 'prompt_tokens', 'ram_usage'];

}
