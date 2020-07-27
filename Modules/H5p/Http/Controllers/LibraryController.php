<?php
namespace App\Http\Controllers;

namespace Djoudi\LaravelH5p\Http\Controllers;
namespace Modules\H5p\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;
use Djoudi\LaravelH5p\Eloquents\H5pContent;
use Djoudi\LaravelH5p\Eloquents\H5pLibrary;
use H5PCore;
use Illuminate\Support\Facades\App;
use Log;

class LibraryController extends Controller
{
   
    public function index(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $interface = $h5p::$interface;
        $not_cached = $interface->getNumNotFiltered();
        $entrys = H5pLibrary::paginate(10);
        $settings = $h5p::get_core([
            'libraryList' => [
                'notCached' => $not_cached,
            ],
            'containerSelector' => '#h5p-admin-container',
            'extraTableClasses' => '',
            'l10n'              => [
                'NA'             => trans('laravel-h5p.common.na'),
                'viewLibrary'    => trans('laravel-h5p.library.viewLibrary'),
                'deleteLibrary'  => trans('laravel-h5p.library.deleteLibrary'),
                'upgradeLibrary' => trans('laravel-h5p.library.upgradeLibrary'),
            ],
        ]);
// dd( $settings);

        foreach ($entrys as $library) {
            $usage = $interface->getLibraryUsage($library->id, $not_cached ? true : false);
            $settings['libraryList']['listData'][] = (object) [
                'id'                     => $library->id,
                'title'                  => $library->title.' ('.H5PCore::libraryVersion($library).')',
                'restricted'             => ($library->restricted ? true : false),
                'numContent'             => $interface->getNumContent($library->id),
                'numContentDependencies' => intval($usage['content']),
                'numLibraryDependencies' => intval($usage['libraries']),
            ];
        }

        $last_update = config('laravel-h5p.h5p_content_type_cache_updated_at');

        // $required_files = $this->assets(['js/h5p-library-list.js']);
        $required_files = '../../Resources/assets/js/h5p-library-list.js';

        if ($not_cached) {
            $settings['libraryList']['notCached'] = $this->get_not_cached_settings($not_cached);
        } else {
            $settings['libraryList']['notCached'] = 0;

        }

        return view('h5p.library.index', compact('entrys', 'settings', 'last_update', 'hubOn', 'required_files'));
    }
  
}
