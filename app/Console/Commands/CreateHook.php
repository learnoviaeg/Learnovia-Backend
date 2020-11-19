<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\HooksCreateParameters;
use Log;

class CreateHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:hook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create webhook';

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
        \Artisan::call('config:cache', ['--env' => 'local']);
        \Artisan::call('cache:clear', ['--env' => 'local']);
        \Artisan::call('config:clear', ['--env' => 'local']);

        $url = config('app.url');

        // \Log::info("Cron is working fine!");
        // \Log::info($url."api/callback_function");

        $bbb = new BigBlueButton();
        $hookParameter = new HooksCreateParameters($url."api/callback_function");
        $hookRes = $bbb->hooksCreate($hookParameter);
          

        $this->info('create:hook Cummand Run successfully!');
    }
}
