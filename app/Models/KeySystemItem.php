<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeySystemItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_activation',
    ];

    /**
     * Si deseas definir relaciones, por ejemplo con Company:
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
