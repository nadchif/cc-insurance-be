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
        'fnCT',
        'value1617',
        'value1718',
        'value_current',
        'account'
    ];

    protected $attributes = [
        'value1617'=>0,
        'value1718'=>0,
        'account'=>'',
        'fnCT'=>'',
        'serial'=>'',
        'address'=>'',
        'erf'=>''
    ];
}
