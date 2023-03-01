<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class annotation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'annotation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'install annotation configuration';

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
        shell_exec('apt-get install ghostscript -y');
        $this->info('ghostscript installed successfully'); 
        shell_exec('sudo apt-get install php-imagick');
        $this->info('php-imagick installed successfully'); 
        shell_exec('php -m | grep imagick');
        shell_exec('service apache2 restart');
        $this->info('apache restart successfully');  
        shell_exec('composer update spatie/pdf-to-image');
        $this->info('spatie/pdf-to-image installed successfully');
        shell_exec('composer dump-autoload');
        $this->info('dump-autoload successfully');
        shell_exec('ln -s /usr/local/bin/gs /usr/bin/gs');
        $this->info('symbolic link successfully');
        shell_exec('sudo cp /etc/ImageMagick-6/policy.xml /etc/ImageMagick-6/policy.xml.bak');
        $this->info('copied /etc/ImageMagick-6/policy.xml successfully');
        shell_exec('sudo sed -i "s/rights\=\"none\" pattern\=\"PS\"/rights\=\"read\|write\" pattern\=\"PS\"/" /etc/ImageMagick-6/policy.xml');
        $this->info('sed  PS successfully');
        shell_exec('sudo sed -i "s/rights\=\"none\" pattern\=\"EPI\"/rights\=\"read\|write\" pattern\=\"EPI\"/" /etc/ImageMagick-6/policy.xml');
        $this->info('sed  EPI successfully');
        shell_exec('sudo sed -i "s/rights\=\"none\" pattern\=\"PDF\"/rights\=\"read\|write\" pattern\=\"PDF\"/" /etc/ImageMagick-6/policy.xml');
        $this->info('sed  PDF successfully');
        shell_exec('sudo sed -i "s/rights\=\"none\" pattern\=\"XPS\"/rights\=\"read\|write\" pattern\=\"XPS\"/" /etc/ImageMagick-6/policy.xml');
        $this->info('sed  XPS successfully');
        shell_exec('sudo service php7.4-fpm restart');
        $this->info('PHP  restart successfully');
        shell_exec('service apache2 restart');
        $this->info('apache2  restart successfully');       
    }
}
