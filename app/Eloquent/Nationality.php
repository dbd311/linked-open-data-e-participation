<?php

 
namespace App\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of Nationality, each of which has a unique URI
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 03 May 2016
 */
class Nationality extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'nationalities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['nationality'];

}
