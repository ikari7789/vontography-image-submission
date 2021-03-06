<?php

namespace App\Http\Controllers\Uploads;

use App\Photo;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use InvalidArgumentException;

class PhotoController extends Controller
{
    /** Number of minutes to cache image files for. */
    const IMAGE_CACHE_TIME = 60 * 24;

    /**
     * Display the specified resource.
     *
     * @param  \App\Photo  $photo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Photo $photo)
    {
        $this->authorize('view', $photo);

        $request->validate([
            'original' => 'boolean|nullable',
            'quality' => 'integer|nullable|min:0|max:100',
            'height' => 'integer|nullable',
            'width' => 'integer|nullable',
        ]);

        $original = $request->input('original');
        $quality = $request->input('quality');
        $height = $request->input('height');
        $width = $request->input('width');

        // Return the original image if $original is set
        if ($original) {
            return response()->file(Storage::path($photo->filepath));
        }

        $key = sprintf(
            "photos_%d_%d_%d_%d",
            $photo->id,
            $width ?? 0,
            $height ?? 0,
            $quality ?? 90
        );

        $image = Image::cache(function ($image) use ($photo, $width, $height) {
            return $image->make(Storage::get($photo->filepath))
                ->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
        }, static::IMAGE_CACHE_TIME, true);

        return $image->response('jpeg', $quality);
    }
}
