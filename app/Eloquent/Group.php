<?php

 
namespace App\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of Nationality, each of which has a unique URI
 *
 * @author Vivien Touly
 * @date 06 September 2016
 */
class Group extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['group'];

}
