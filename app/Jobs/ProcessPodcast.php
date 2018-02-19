<?php

namespace App\Jobs;

use App\Podcast;
use App\AudioProcessor;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Controllers\TranslationController;
//use Illuminate\Contracts\Queue\QueueableCollection

class ProcessPodcast implements ShouldQueue
{
  //  use InteractsWithQueue, Queueable, SerializesModels;

    protected $post, $textToTranslate, $sourceLanguage;

    /**
     * Create a new job instance.
     *
     * @param  Podcast  $podcast
     * @return void
     */
    public function __construct($post, $textToTranslate, $sourceLanguage)
    {
        $this->post = $post;
        $this->textToTranslate = $textToTranslate;
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * Execute the job.
     *
     * @param  AudioProcessor  $processor
     * @return void
     */
    public function handle()
    {
        // Process uploaded podcast...
        //echo $this->post .'  '. $this->textToTranslate.'  '. $this->sourceLanguage;
        TranslationController::sendTranslation1($this->post, $this->textToTranslate, $this->sourceLanguage);
        
    }
}