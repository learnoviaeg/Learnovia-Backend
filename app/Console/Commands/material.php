<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Course;
use Illuminate\Support\Str;

class material extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'material:extract';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract materials and zips them by course';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $courses = Course::select(['id', 'level_id', 'short_name'])->with(['materials' => function ($q) {
            $q->whereIn('type', ['file', 'media']);
            $q->with('file');
            $q->with('media');
        }, 'level'])->get();
        // dd($courses);

        foreach ($courses as $course) {
            // dd($course->short_name);
            $name = Str::snake($course->short_name);
            $name = str_replace("(", "_", $name);
            $name = str_replace(")", "_", $name);

            if(!File::isDirectory('/var/www/html/learnovia-backend/public/extracts')){
                File::makeDirectory('/var/www/html/learnovia-backend/public/extracts', 0777, true, true);
            }
            $zipCommand = "zip /var/www/html/learnovia-backend/public/extracts/{$name}.zip";

            foreach ($course->materials as $material) {
                if ($material->mime_type == 'media link' || $material->mime_type == Null) continue;

                if ($material->type == "media") {
                    // dd($material->link);
                    $path = public_path('/') . substr($material->getOriginal()['link'], 33);
                    $zipCommand .= " {$path}";
                }

                if ($material->type == "file") {
                    $path = public_path('/storage') . substr($material->getOriginal()['link'], 75);
                    $zipCommand .= " {$path}.";
                }
                // dd($zipCommand);

            }

            $this->info("calling :=> {$zipCommand}");
            $reslut = shell_exec("sudo {$zipCommand}");
            $this->info("reslut :=>{$reslut}");
        }
    }
}
