<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\SliderCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function sliders()
    {
        return new SliderCollection(get_setting('home_slider_images') != null ? json_decode(get_setting('home_slider_images'), true) : [], 'home_slider_links');
    }

    public function bannerOne()
    {
        return new SliderCollection(get_setting('home_banner1_images') != null ? json_decode(get_setting('home_banner1_images'), true) : []);
    }

    public function bannerTwo()
    {
        return new SliderCollection(get_setting('home_banner2_images') != null ? json_decode(get_setting('home_banner2_images'), true) : []);
    }

    public function bannerThree(Request $request)
    {
        $lang = get_system_language()->code;
        $banner_images = json_decode(get_setting('home_banner3_images', null, $lang));
        $banner_links = json_decode(get_setting('home_banner3_links', null, $lang));

        // Convert arrays to collections for easier handling
        $banner_images = collect($banner_images);
        $banner_links = collect($banner_links);

        $perPage = 10; // Items per page
        $currentPage = $request->input('page', 1);

        // Paginate images and links
        $paginatedImages = $banner_images->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedLinks = $banner_links->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Prepare the data array with 'photo' and 'keyword' keys
        $data = $paginatedImages->map(function ($image, $index) use ($paginatedLinks) {
            $url = $paginatedLinks[$index] ?? null;

            if ($url) {
                // Extract the brand slug from the URL
                preg_match('/\/brand\/([^\/]+)/', $url, $matches);
                $keyword = isset($matches[1]) ? urldecode($matches[1]) : null;

                // Check if the slug corresponds to a Brand
                $brand = \App\Models\Brand::where('slug', $keyword)->first();

                return [
                    'photo' => uploaded_asset($image),
                    'keyword' => $brand ? $brand->id : $url,
                ];
            } else {
                return [
                    'photo' => uploaded_asset($image),
                    'keyword' => null,
                ];
            }
        });

        // Create a LengthAwarePaginator instance
        $paginator = new LengthAwarePaginator(
            $data,
            $banner_images->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }
}
