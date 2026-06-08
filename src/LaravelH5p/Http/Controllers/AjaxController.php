<?php

namespace Transisi\LaravelH5P\Http\Controllers;

use App\Http\Controllers\Controller;
use Transisi\LaravelH5P\Eloquents\H5pLibrary;
use Transisi\LaravelH5P\Events\H5pEvent;
use H5PEditorEndpoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Log;

class AjaxController extends Controller
{
    public function libraries(Request $request)
    {
        $machineName = $request->get('machineName');
        $major_version = $request->get('majorVersion');
        $minor_version = $request->get('minorVersion');

        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;

        //log($machineName);
        Log::debug('An informational message.' . $machineName . '=====' . $h5p->get_language());
        if ($machineName) {
            // If version params are invalid (e.g. "undefined") or placeholder (0.0), resolve to latest local version
            $useLatest = !is_numeric($major_version) || !is_numeric($minor_version)
                || (intval($major_version) === 0 && intval($minor_version) === 0);
            // Editor needs library data for both runnable content types and dependency libraries (e.g. H5P.Text)
            $libraryQuery = function () use ($machineName) {
                return H5pLibrary::where('name', $machineName)
                    ->orderBy('major_version', 'desc')
                    ->orderBy('minor_version', 'desc');
            };
            if ($useLatest) {
                $latest = $libraryQuery()->first();
                if ($latest) {
                    $major_version = $latest->major_version;
                    $minor_version = $latest->minor_version;
                } else {
                    \H5PCore::ajaxError('Library not installed.', 'LIBRARY_NOT_FOUND', 404);
                    return;
                }
            }
            // Ensure requested version exists; otherwise use latest installed version for this machineName
            $exists = H5pLibrary::where('name', $machineName)
                ->where('major_version', $major_version)
                ->where('minor_version', $minor_version)
                ->exists();
            if (!$exists) {
                $latest = $libraryQuery()->first();
                if ($latest) {
                    $major_version = $latest->major_version;
                    $minor_version = $latest->minor_version;
                } else {
                    \H5PCore::ajaxError('Library not installed.', 'LIBRARY_NOT_FOUND', 404);
                    return;
                }
            }
            $defaultLanguag = $editor->getLibraryLanguage($machineName, $major_version, $minor_version, $h5p->get_language());
            Log::debug('An informational message.' . $machineName . '=====' . $h5p->get_language() . '=====' . $defaultLanguag);

            //   public function getLibraryData($machineName, $majorVersion, $minorVersion, $languageCode, $prefix = '', $fileDir = '', $defaultLanguage) {

            $editor->ajax->action(H5PEditorEndpoints::SINGLE_LIBRARY, $machineName, $major_version, $minor_version, $h5p->get_language(), '', $h5p->get_h5plibrary_url('', true), $defaultLanguag);  //$defaultLanguage
            // Log library load
            event(new H5pEvent('library', null, null, null, $machineName, $major_version . '.' . $minor_version));
        } else {
            // Otherwise retrieve all libraries
            $editor->ajax->action(H5PEditorEndpoints::LIBRARIES);
        }
    }

    public function singleLibrary(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::SINGLE_LIBRARY, $request->get('_token'));
    }

    public function contentTypeCache(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $core = $h5p::$core;
        $editor = $h5p::$h5peditor;
        // If hub cache table is empty, force refresh from H5P.org so content types appear
        if (DB::table('h5p_libraries_hub_cache')->count() === 0) {
            $lang = $h5p->get_language() ?: 'en';
            Cache::forget('h5p_content_hub_metadata_checked_' . $lang);
            $core->getUpdatedContentHubMetadataCache($lang);
        }
        $editor->ajax->action(H5PEditorEndpoints::CONTENT_TYPE_CACHE, $request->get('_token'));
    }

    public function contentHubMetadataCache(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::CONTENT_HUB_METADATA_CACHE, $request->get('_token'));
    }

    public function libraryInstall(Request $request)
    {
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        // Accept both 'machineName' and 'id' (H5P hub client may send id=contentType.machineName)
        $machineName = $request->get('machineName') ?? $request->get('id');
        $editor->ajax->action(H5PEditorEndpoints::LIBRARY_INSTALL, $request->get('_token'), $machineName);
    }

    public function libraryUpload(Request $request)
    {
        $filePath = $request->file('h5p')->getPathName();
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::LIBRARY_UPLOAD, $request->get('_token'), $filePath, $request->get('contentId'));
    }

    public function files(Request $request)
    {
        $filePath = $request->file('file');
        $h5p = App::make('LaravelH5p');
        $editor = $h5p::$h5peditor;
        $editor->ajax->action(H5PEditorEndpoints::FILES, $request->get('_token'), $request->get('contentId'));
    }

    public function __invoke(Request $request)
    {
        return response()->json($request->all());
    }

    public function finish(Request $request)
    {
        return response()->json($request->all());
    }

    public function contentUserData(Request $request)
    {
        return response()->json($request->all());
    }
}
