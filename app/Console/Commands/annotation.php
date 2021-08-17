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
        shell_exec('apt-get install php-imagick');
        $this->info('php-imagick installed successfully'); 
        shell_exec('php -m | grep imagick');
        shell_exec('service apache2 restart');
        $this->info('apache restart successfully'); 
        shell_exec('composer remove '.base_path().'Vendor/spatie/pdf-to-image');
        $this->info('Vendor/spatie/pdf-to-image removed successfully'); 
        shell_exec('composer require spatie/pdf-to-image');
        $this->info('spatie/pdf-to-image installed successfully');
        shell_exec('composer dump-autoload');
        $this->info('dump-autoload successfully');
        shell_exec('ln -s '.base_path().'/usr/local/bin/gs '.base_path().'/usr/bin/gs');
        $this->info('symbolic link successfully');
        shell_exec('cp '.base_path().'/etc/ImageMagick-6/policy.xml '.base_path().'/etc/ImageMagick-6/policy.xml.bak');
        $this->info('copied /etc/ImageMagick-6/policy.xml successfully');
        shell_exec('sed -i "s/rights\=\"none\" pattern\=\"PS\"/rights\=\"read\|write\" pattern\=\"PS\"/"
        '.base_path().'/etc/ImageMagick-6/policy.xml');
        $this->info('sed  PS successfully');
        shell_exec('sed -i "s/rights\=\"none\" pattern\=\"EPI\"/rights\=\"read\|write\" pattern\=\"EPI\"/"
        '.base_path().'/etc/ImageMagick-6/policy.xm');
        $this->info('sed  EPI successfully');
        shell_exec('sed -i "s/rights\=\"none\" pattern\=\"PDF\"/rights\=\"read\|write\"
        pattern\=\"PDF\"/" '.base_path().'/etc/ImageMagick-6/policy.xml');
        $this->info('sed  PDF successfully');
        shell_exec('sed -i "s/rights\=\"none\" pattern\=\"XPS\"/rights\=\"read\|write\"
        pattern\=\"XPS\"/" '.base_path().'/etc/ImageMagick-6/policy.xml');
        $this->info('sed  XPS successfully');
        shell_exec('service php7.3-fpm restart');
        $this->info('PHP  restart successfully');
        shell_exec('service apache2 restart');
        $this->info('apache2  restart successfully');

    }
}
