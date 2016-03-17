<?php

namespace App\Http\Controllers\Api;

use abeautifulsite\SimpleImage;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Upload;
use Auth;
use Cache;
use Exception;
use Helpers;
use Input;
use Storage;
use Teapot\StatusCode;

class ApiController extends Controller
{
    public function postUpload()
    {
        if (!Input::hasFile('file')) {
            return response()->json([trans('errors.upload_file_not_found')], StatusCode::BAD_REQUEST);
        }

        $file = Input::file('file');
        if (!$file->isValid()) {
            return response()->json([trans('errors.invalid_file_upload')], StatusCode::BAD_REQUEST);
        }

        if ($file->getSize() >= config('upste.upload_limit')) {
            return response()->json([trans('errors.upload_too_large')], StatusCode::REQUEST_ENTITY_TOO_LARGE);
        }

        // If this upload would hit the quota defined in .env, reject it.
        if (config('upste.user_storage_quota') > 0 && !Auth::user()->admin &&
            (Cache::get('uploads_size:' . Auth::user()->id) + $file->getSize()) >= config('upste.user_storage_quota')) {
            return response()->json([trans('errors.reached_upload_limit')], StatusCode::FORBIDDEN);
        }

        $ext = $file->getClientOriginalExtension();
        if (empty($ext)) {
            $ext = 'txt';
        }

        // Strip EXIF tags
        if (Helpers::shouldStripExif($file)) {
            try {
                $img = new SimpleImage($file->getRealPath());
                $img->save($file->getRealPath(), 100, Helpers::getImageType($file));
            } catch (Exception $e) {
                return response()->json([$e->getMessage()], StatusCode::INTERNAL_SERVER_ERROR);
            }
        }

        $fileHash = sha1_file($file);
        $originalName = $file->getClientOriginalName();

        // Check to see if we already have this file for this user.
        $existing = Upload::whereHash($fileHash)->whereUserId(Auth::user()->id)->first();
        if ($existing) {
            $result = [
                'url'  => config('upste.upload_url') . $existing->name
            ];

            $existing->original_name = $originalName;
            // Force-update updated_at to move $existing to the top of /u/uploads
            $existing->touch();
            $existing->save();

            return response()->json($result, StatusCode::CREATED, [], JSON_UNESCAPED_SLASHES);
        }

        $randomLen = config('upste.upload_slug_length');
        do {
            $newName = str_random($randomLen++) . ".$ext";
        } while (Storage::exists("uploads/$newName"));

        $upload = Upload::create([
            'user_id'       => Auth::user()->id,
            'hash'          => $fileHash,
            'name'          => $newName,
            'size'          => $file->getSize(),
            'original_name' => $originalName
        ]);

        $upload->save();
        Storage::put(
            "uploads/$newName",
            file_get_contents($file->getRealPath())
        );

        $result = [
            'url'  => config('upste.upload_url') . $newName
        ];

        return response()->json($result, StatusCode::CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function getUpload()
    {
        $user = Auth::user();

        if (Cache::get('uploads_count:' . $user->id) !== 0) {
            $uploads = $user->uploads->slice(0, Input::get('limit', $user->uploads->count()));
            return response()->json($uploads, StatusCode::CREATED, [], JSON_UNESCAPED_SLASHES);
        }
        return response()->json(['error' => 'no_uploads'], StatusCode::NOT_FOUND, [], JSON_UNESCAPED_SLASHES);
    }
}
