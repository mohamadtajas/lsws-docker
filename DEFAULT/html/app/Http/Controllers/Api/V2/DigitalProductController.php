<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalProduct\IdRequest;
use App\Models\Product;
use App\Models\Upload;
use App\Models\Order;
use App\Models\OrderDetail;

class DigitalProductController extends Controller
{
    public function download(string $id)
    {
        $product = Product::findOrFail($id);
        $orders = Order::select("id")->where('user_id', auth()->user()->id)->pluck('id');
        $orderDetails = OrderDetail::where("product_id", $id)->whereIn("order_id", $orders)->get();
        if (auth()->user()->user_type == 'admin' || auth()->user()->id == $product->user_id || $orderDetails) {
            $upload = Upload::findOrFail($product->file_name);
            if (env('FILESYSTEM_DRIVER') == "aws") {
                return \Storage::disk('aws')->download($upload->file_name, $upload->file_original_name . "." . $upload->extension);
            } else {
                if (file_exists(base_path('public/' . $upload->file_name))) {
                    $file = public_path() . "/$upload->file_name";
                    return response()->download($file, config('app.name') . "_" . $upload->file_original_name . "." . $upload->extension);
                }
            }
        } else {
            return response()->download(File("dd.pdf"), "failed.jpg");
        }
    }
}
