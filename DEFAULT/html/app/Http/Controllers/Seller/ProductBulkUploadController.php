<?php

namespace App\Http\Controllers\Seller;

use App\Http\Requests\Product\BulkFileRequest;
use App\Http\Requests\Product\TrendyolBulkUploadRequest;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use Auth;
use App\Models\ProductsImport;
use App\Models\ProductStock;
use App\Models\Upload;
use PDF;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductBulkUploadController extends Controller
{
    public function index()
    {
        if (Auth::user()->shop->verification_status) {
            return view('seller.product.product_bulk_upload.index');
        } else {
            flash(translate('Your shop is not verified yet!'))->warning();
            return back();
        }
    }

    public function pdf_download_category()
    {
        $categories = Category::all();

        return PDF::loadView('backend.downloads.category', [
            'categories' => $categories,
        ], [], [])->download('category.pdf');
    }

    public function pdf_download_brand()
    {
        $brands = Brand::all();

        return PDF::loadView('backend.downloads.brand', [
            'brands' => $brands,
        ], [], [])->download('brands.pdf');
    }

    public function bulk_upload(BulkFileRequest $request)
    {
        if ($request->hasFile('bulk_file')) {
            $import = new ProductsImport;
            Excel::import($import, request()->file('bulk_file'));
        }

        return back();
    }

    public function bulk_upload_trendyol_products()
    {
        return view('seller.product.product_bulk_upload.trendyol');
    }

    public function bulk_upload_trendyol_get_products(TrendyolBulkUploadRequest $request)
    {
        $products = trendyol_seller_products($request->supplier_id, $request->api_user_name, $request->api_password, $request->page ?? 0);
        return view('seller.product.product_bulk_upload.trendyol', compact('products'));
    }

    public function bulk_import_trendyol_get_products(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $product) {
                $product = json_decode($product, true);
                $user = Auth::user();

                $productId = Product::create([
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'added_by' => 'seller',
                    'user_id' => $user->id,
                    'approved' => 0,
                    'category_id' => $product['category_id'],
                    'unit_price' => $product['unit_price'],
                    'unit' => $product['unit'],
                    'meta_title' => $product['name'],
                    'meta_description' => $product['description'],
                    'colors' => json_encode(array()),
                    'choice_options' => json_encode(array()),
                    'variations' => json_encode(array()),
                    'slug' => preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($product['name']))) . '-' . Str::random(5),
                    'thumbnail_img' => $this->downloadThumbnail($product['photos'][0]),
                    'photos' => $this->downloadGalleryImages($product['photos']),
                ]);
                ProductStock::create([
                    'product_id' => $productId->id,
                    'qty' => $product['current_stock'],
                    'price' => $product['unit_price'],
                    'sku' => $productId->slug,
                    'variant' => '',
                ]);
            }
        }
        flash(translate('Products has been imported successfully'))->success();
        return 1;
    }

    private function downloadThumbnail($url)
    {
        try {
            $upload = new Upload;
            $upload->external_link = $url;
            $upload->type = 'image';
            $upload->save();

            return $upload->id;
        } catch (\Exception $e) {
        }
        return null;
    }

    private function downloadGalleryImages($urls)
    {
        $data = array();
        foreach ($urls as $url) {
            $data[] = $this->downloadThumbnail($url);
        }
        return implode(',', $data);
    }
}
