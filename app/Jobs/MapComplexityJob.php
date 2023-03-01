<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\BloomCategory;
use Modules\QuestionBank\Entities\Questions;

class MapComplexityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $complexMap;
    public $ids;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($complexMap,$ids)
    {
        $this->complexMap=$complexMap;
        $this->ids=$ids;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->complexMap as $map)
        {
            $newCat=BloomCategory::where('name',$map['new'])->where('current',1)->first();
            Questions::where('complexity',$map['id'])->update(['complexity' => $newCat->id]);
        }
        Questions::whereNotIn('complexity',$this->ids)->whereNotNull('complexity')->update(['complexity' => null]);
    }
}
