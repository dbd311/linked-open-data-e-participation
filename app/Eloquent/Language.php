<?php



namespace App\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of Language, each of which has a unique URI
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 03 May 2016
 */
class Language extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'languages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['family', 'name', 'native_name', 'code', 'code3', 'notes'];
}