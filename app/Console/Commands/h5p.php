<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;

class h5p extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'h5p';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'H5P setup on new server';

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

        shell_exec('chmod -R 777 storage/h5p/temp');
        $this->info('Success chmod  storage/h5p/temp');
     
        $vendor_h5p = base_path().'/public/vendor/h5p';
       
        $content = base_path().'/storage/h5p/content';
        if(!File::exists($content)) {
            symlink($vendor_h5p,  $content);
            $this->info('Success symbolic link  content');
        }
        $editor = base_path().'/storage/h5p/editor';
        if(!File::exists($editor)) {
            symlink($vendor_h5p,$editor);
            $this->info('Success symbolic link  editor');
        }
        $libraries = base_path().'/storage/h5p/libraries'; //var/wwww/html
        if(!File::exists($libraries)) {
            symlink($vendor_h5p,  $libraries);
            $this->info('Success symbolic link  libraries');
        }
        
        shell_exec('chmod -R 777 storage/h5p/libraries');
        $this->info('Success chmod  storage/h5p/libraries');
        shell_exec('chmod -R 777 storage/h5p/editor/videos');
        $this->info('Success chmod  storage/h5p/videos');
        shell_exec('chmod -R 777 storage/h5p/editor/images');
        $this->info('Success chmod  storage/h5p/images');

        \Artisan::call('view:clear');
        $this->info('View cleared successfully ');

        \Artisan::call('route:clear');
        $this->info('Route cleared successfully ');
        
       // shell_exec('cp -R /home/yasser/Learnovia/backend/version4/learnovia-backend/storage/h5p /home/yasser/Learnovia/backend/version4/learnovia-backend/storage/app/public ');
       shell_exec('cp -R /'.base_path().'/storage/h5p /'.base_path().'/storage/app/public '); 
       $this->info('Folder h5p copied successfully ');


       $app_public = base_path().'/storage/app/public';
       
       $videos = base_path().'/storage/h5p/editor/videos/';
       if(!File::exists($videos)) {
           symlink($app_public,  $videos);
           $this->info('Success symbolic link  videos');
       }
       $images = base_path().'/storage/h5p/editor/images/';
       if(!File::exists($images)) {
           symlink($app_public,  $images);
           $this->info('Success symbolic link  images');
       }

        \Artisan::call('optimize:clear');
        $this->info('Optimize cleared successfully ');


        shell_exec('cp -R /'.base_path().'/public/vendor/laravel-h5p/ /'.base_path().'/storage/app/public/ ');
        $this->info('Folder laravel-h5p copied successfully ');

        \Artisan::call('optimize:clear');
        $this->info('Compiled views cleared!');
        $this->info('Application cache cleared!');
        $this->info('Route cache cleared!');
        $this->info('Configuration cache cleared!');
        $this->info('Compiled services and packages files removed!');
        $this->info('Caches cleared successfully!');
    
        $this->info('Success installed');
        

      /*
     shell_exec('cd /home/yasser/Learnovia/backend/version4/learnovia-backend/public/vendor/h5p ');
        shell_exec('ln -s ../../../storage/h5p/content');
        shell_exec('ln -s ../../../storage/h5p/editor');
        shell_exec('ln -s ../../../storage/h5p/libraries');
      */    
        









    }
}
