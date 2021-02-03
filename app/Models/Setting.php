<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;    
    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
   protected $fillable = [
       'receiving_email',
       'user_signup',
       'monthly_backup',
   ];

   protected $attributes = [
       'monthly_backup'=>1,
       'user_signup'=>1
   ];
}
