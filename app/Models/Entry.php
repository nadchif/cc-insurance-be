<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date_insured',
        'entity',
        'erf',
        'address',
        'type',
        'description',
        'serial',
        'building_value',
        'contents_value',
    ];

    protected $attributes = [
        'building_value'=>0,
        'contents_value'=>0,
        'serial'=>'',
        'address'=>'',
        'erf'=>''
    ];
}
