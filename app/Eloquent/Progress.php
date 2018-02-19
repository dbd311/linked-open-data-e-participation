<?php

namespace App\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Progress of a particular task
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 03 June 2016
 */
class Progress extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'progress';

    /**
     * Get the progress of a particular task
     */
    public static function get_progress($task_name, $lang_code) {
        $row = Progress::where(['task' => $task_name, 'lang_code' => $lang_code])->first();
        if (!empty($row)) {
            return $row->progress;
        }

        return -1;
    }

    /**
     * Set the progress of a particular task
     */
    public static function update_progress($task_name, $lang_code, $progress) {
        Progress::where(['task' => $task_name, 'lang_code' => $lang_code])->update(['progress' => $progress]);
    }

}
