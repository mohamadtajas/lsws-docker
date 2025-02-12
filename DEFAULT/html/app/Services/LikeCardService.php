<?php

namespace App\Services;

use App;
use App\Models\Category;
use App\Models\Provider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Response;

class LikeCardService
{
    private $deviceId;
    private $email;
    private $securityCode;
    private $hashKey;
    private $phoneNumber;
    private $secretIV;
    private $secretKey;
    private $baseUrl;
    private $provider;
    private $active;
    private $mainCategory;
    private $commision_rate;
    private $currency_code;
    const ARABIC_LANG_CODE = 2;
    const ENGLISH_LANG_CODE = 1;

    public function __construct()
    {
        $provider = Provider::where('name', 'LikeCard')->first();
        $this->provider = $provider ?: Provider::Create(['name' => 'LikeCard', 'currency_code' => 'USD']);
        $this->active = $this->provider->active;
        $mainCategory = Category::where('provider_id', $this->provider->id)->first();
        if ($mainCategory) {
            if ($mainCategory->external_id === 0) {
                $this->mainCategory = $mainCategory;
            } else {
                $mainCategory->update(['external_id' => 0]);
                $this->mainCategory = $mainCategory;
            }
        } else {
            $this->mainCategory = $this->createMainCategory();
        }

        $this->deviceId = env('LIKE_CARD_DEVICE_ID');
        $this->email = env('LIKE_CARD_EMAIL');
        $this->securityCode = env('LIKE_CARD_SECURITY_CODE');
        $this->hashKey = env('LIKE_CARD_HASH_KEY');
        $this->phoneNumber = env('LIKE_CARD_PHONE_NUMBER');
        $this->secretIV = env('LIKE_CARD_SECRET_IV');
        $this->secretKey = env('LIKE_CARD_SECRET_KEY');
        $this->baseUrl = env('LIKE_CARD_BASE_URL');
        $this->currency_code =  $this->provider->currency_code;
        $this->commision_rate = $this->mainCategory->commision_rate;
    }

    public function index($id)
    {
        return $this->productsDetails($id);
    }

    public function checkBalance($amount = 0): bool
    {
        if (!$this->active) {
            return false;
        }
        $endPoint = 'check_balance';

        $formData = $this->buildHeaders();

        $responseKeys = [
            'balance'
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        $balance = convert_to_default_currency($response->balance, $this->currency_code);

        return $balance > $amount ? true : false;
    }

    public function getBalance(){
        if (!$this->active) {
            return false;
        }
        $endPoint = 'check_balance';

        $formData = $this->buildHeaders();

        $responseKeys = [
            'balance'
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        $balance = convert_to_default_currency($response->balance, $this->currency_code);

        return $balance;
    }

    public function categories($categoryId): array
    {
        if (!$this->active) {
            return [];
        }
        $data = $this->getAllCategories();

        return $this->getChildrenCategories($data, $categoryId);
    }

    public function category($categoryId): object
    {
        if (!$this->active) {
            return collect([]);
        }

        $data = $this->getAllCategories();

        return $this->findCategory($data, $categoryId);
    }

    public function categoryProducts($categoryId): array
    {
        if (!$this->active) {
            return [];
        }

        $endPoint = 'products';

        $formData = $this->buildHeaders([
            'categoryId' => $this->decryptId($categoryId)
        ]);

        $responseKeys = [
            'data'
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        return $this->transformToProductObjects($response->data ?? []);
    }

    public function productsDetails(array $productIds): array
    {
        if (!$this->active) {
            return [];
        }

        foreach ($productIds as $key => $productId) {
            $decryptedId = $this->decryptId($productId);
            $productIds[$key] = $decryptedId;
            $ids['ids['.$key.']'] = $decryptedId;
        }

        $endPoint = 'products';
        $formData = $this->buildHeaders($ids);

        $responseKeys = [
            'data'
        ];
        $response = $this->getResponse($endPoint, $formData, $responseKeys);
        return $this->transformToProductObjects($response->data ?? [], $productIds);
    }

    public function productDetails(int $productId): object
    {
        if (!$this->active) {
            return [];
        }

        $productId = $this->decryptId($productId);

        $endPoint = 'products';

        $formData = $this->buildHeaders([
            'ids[]' => $productId,
        ]);

        $responseKeys = [
            'data'
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        return collect($this->transformToProductObjects($response->data ?? []))->first() ?: collect([]);
    }

    public function orders($fromTime, $toTime, $page = 1, $orderType = 'asc'): array
    {

        $endPoint = 'orders';

        $fromUnixTime = strtotime($fromTime);
        $toUnixTime = strtotime($toTime);

        $formData = $this->buildHeaders([
            'orderType' => $orderType,
            'page' => $page,
            'fromUnixTime' => $fromUnixTime,
            'toUnixTime' => $toUnixTime
        ]);

        $responseKeys = [
            'data'
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        return $response->data ?? [];
    }

    public function orderDetails($orderCode = null, $referenceId = null): object
    {
        $endPoint = 'orders/details';

        $formData = $this->buildHeaders([
            'orderId' => $orderCode,
            'referenceId' => $referenceId
        ]);

        $responseKeys = [
            'orderNumber',
            'orderReferenceId',
            'orderFinalTotal',
            'currencySymbol',
            'orderCreateDate',
            'orderCurrentStatus',
            'serials',
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        return $response ?? (object) [];
    }

    public function buy($productId, $quantity = 1): object
    {
        if (!$this->active) {
            return collect([]);
        }

        $endPoint = 'create_order';
        $productId = $this->decryptId($productId);

        $referenceId = uniqid(str_replace(' ', '_', env('APP_NAME')) . '_');
        $time = time();
        $hash = $this->generateHash($time);

        $formData = $this->buildHeaders([
            'productId' => $productId,
            'quantity' => $quantity,
            'referenceId' => $referenceId,
            'time' => $time,
            'hash' => $hash
        ]);

        $responseKeys = [
            'orderId',
            'referenceId',
            'productName',
            'productImage',
            'orderDate',
            'orderPrice',
            'serials',
        ];

        $response = $this->getResponse($endPoint, $formData, $responseKeys);

        $response->serials = array_map(fn($serial) => (object) $serial, $response->serials ?? []);

        return $response ?? (object) [];
    }



    /* Private Functions */

    private function buildHeaders($additionalHeaders = []): array
    {
        $baseHeaders = [
            'deviceId' => $this->deviceId,
            'email' => $this->email,
            'securityCode' => $this->securityCode,
            'langId' => App::getLocale() == 'sa' ? self::ARABIC_LANG_CODE : self::ENGLISH_LANG_CODE
        ];

        $headers = array_merge($baseHeaders, $additionalHeaders);

        return $headers;
    }

    private function getResponse(string $endPoint, array $formData = [], array $responseKeys = []): object
    {

        $url = $this->baseUrl . $endPoint;
        try {
            $curl = $this->buildCurlObject($url, $formData);

            $response = curl_exec($curl);
            curl_close($curl);

            $decodedResponse = json_decode($response, true);

            if (!$this->isSuccessfulResponse($decodedResponse)) {
                $this->logErrorResponse($url, $decodedResponse);
            }

            return $this->buildResponse($decodedResponse, $responseKeys);
        } catch (\Exception $e) {
            $this->logException($e, $url, 'getResponse');

            return (object) [
                'success' => false,
                'message' => 'An error occurred while processing the request.'
            ];
        }
    }

    private function buildCurlObject($url, array $formData): object
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $formData,
            CURLOPT_HTTPHEADER => [
                "Content-Type: multipart/form-data"
            ],
        ]);
        return $curl;
    }

    private function isSuccessfulResponse(?array $response): bool
    {
        return isset($response['response']) && $response['response'] === 1;
    }

    private function buildResponse(?array $response, array $responseKeys): object
    {
        $data = ['success' => true];
        foreach ($responseKeys as $key) {
            $data[$key] = $response[$key] ?? null;
        }
        return (object) $data;
    }

    private function logErrorResponse(string $url, ?array $response): void
    {
        Log::error('API response indicates failure.', [
            'url' => $url,
            'response' => $response
        ]);
    }

    private function logException(\Exception $e, string $url, string $functionName): void
    {
        Log::error('LikeCardController::' . $functionName . ' Exception occurred during API request.', [
            'message' => $e->getMessage(),
            'url' => $url,
        ]);
    }

    public function decryptSerial($encrypted_serial = null): string
    {
        $encrypt_method = 'AES-256-CBC';
        $key = hash('sha256', $this->secretKey);
        $iv = substr(hash('sha256', $this->secretIV), 0, 16);
        return openssl_decrypt(base64_decode($encrypted_serial), $encrypt_method, $key, 0, $iv) ?? null;
    }

    private function generateHash($time)
    {
        $email = strtolower($this->email);
        $phone = $this->phoneNumber;
        $key = $this->hashKey;
        return hash('sha256', $time . $email . $phone . $key);
    }

    private function getAllCategories(): array
    {
        $endPoint = 'categories';

        $formData = $this->buildHeaders();

        $responseKeys = [
            'data'
        ];
        $lang = App::getLocale();
        $response = Cache::remember("like_card_{$endPoint}_{$lang}", 86400, function () use ($endPoint, $formData, $responseKeys) {
            $response = $this->getResponse($endPoint, $formData, $responseKeys);
            return $this->convertToCategoryObjects($response->data);
        });

        return $response ?? [];
    }

    private function convertToCategoryObjects($categories, $parentId = 0): array
    {
        $provider_id = $this->provider->id;
        $objects = [];

        foreach ($categories as $category) {
            $encryptedId = $this->encryptId($category['id']);
            $categoryObject = new Category([
                'id' => $encryptedId,
                'parent_id' => $parentId,
                'name' => $category['categoryName'],
                'banner' => $category['amazonImage'] ?? null,
                'icon' => $category['amazonImage'] ?? null,
                'cover_image' => $category['amazonImage'] ?? null,
                'meta_title' => $category['categoryName'],
                'meta_description' => json_encode($category['metaData']),
                'provider_id' => $provider_id,
                'external_id' => $encryptedId,
                'digital' => 1,
                'commision_rate' => $this->commision_rate
            ]);

            $categoryObject->children_categories = isset($category['childs']) && !empty($category['childs'])
                ? $this->convertToCategoryObjects($category['childs'],  $categoryObject->id)
                : [];

            $objects[] = $categoryObject;
        }

        return $objects;
    }

    private function getChildrenCategories(array $categories, $categoryId = 0): array
    {
        if ($categoryId == 0) {
            return $categories;
        }

        foreach ($categories as $category) {
            if ($category->id == $categoryId) {
                return $category->children_categories;
            }

            if (isset($category->children_categories) && !empty($category->children_categories)) {
                $result = $this->getChildrenCategories($category->children_categories, $categoryId);
                if ($result) {
                    return $result;
                }
            }
        }

        return [];
    }

    private function findCategory(array $categories, $categoryId = 0): ?object
    {
        foreach ($categories as $category) {
            if ($category->id == $categoryId) {
                return $category;
            }

            if (isset($category->children_categories) && !empty($category->children_categories)) {
                $result = $this->findCategory($category->children_categories, $categoryId);
                if ($result != null) {
                    return $result;
                }
            }
        }

        return null;
    }

    private function transformToProductObjects(array $products, array $productIds = []): array
    {
        $objects = [];

        if (count($productIds) > 0) {
            foreach ($productIds as $key => $productId) {
                $product = collect($products)->firstWhere('productId', $productId);
                if ($product) {
                    $base_price = convert_to_default_currency($product['productPrice'], $this->currency_code);
                    $unit_price = convert_to_default_currency($product['productPrice'] * $this->commision_rate + $product['productPrice'], $this->currency_code);
                    $new_price = convert_to_default_currency($product['productPrice'] * $this->commision_rate + $product['productPrice'], $this->currency_code);
                    $discount = round(100 - ($new_price * 100) / $unit_price, 0);

                    $productObject = collect([
                        'id' => $this->encryptId($product['productId']),
                        'name' => $product['productName'],
                        'base_price' => $base_price,
                        'unit_price' => $unit_price,
                        'new_price' => $new_price,
                        'thumbnail' => $product['productImage'],
                        'photos' => (array) $product['productImage'],
                        'discount_price' => $discount,
                        'categoryName' => $this->mainCategory->name,
                        'brandName' => '',
                        'auction_product' => 0,
                        'digital' => 1,
                        'currency'  => $this->currency_code,
                        'provider' => strtolower($this->provider->name),
                        'provider_id' => $this->provider->id,
                        'stock' => 10,
                        'contRating' => 0,
                        'rating' => 0,
                        'wholesale_product' => 0,
                        'descriptions' => (array) [],
                        'attributes' => (array) [],
                        'min_qty' => 1,
                        'added_by' => 'admin',
                        'user_id'  => get_admin()->id,
                    ]);

                    $objects[$key] = $productObject;
                }
            }
        } else {
            foreach ($products as $key => $product) {
                $base_price = convert_to_default_currency($product['productPrice'], $this->currency_code);
                $unit_price = convert_to_default_currency($product['productPrice'] * $this->commision_rate + $product['productPrice'], $this->currency_code);
                $new_price =  convert_to_default_currency($product['productPrice'] * $this->commision_rate + $product['productPrice'], $this->currency_code);
                $discount = round(100 - ($new_price * 100) / $unit_price, 0);
                $productObject = collect([
                    'id' => $this->encryptId($product['productId']),
                    'name' => $product['productName'],
                    'base_price' => $base_price,
                    'unit_price' => $unit_price,
                    'new_price' => $new_price,
                    'thumbnail' => $product['productImage'],
                    'photos' => (array) $product['productImage'],
                    'discount_price' => $discount,
                    'categoryId' => $this->encryptId($product['categoryId']),
                    'categoryName' => $this->mainCategory->name,
                    'brandId' => '',
                    'brandName' => '',
                    'auction_product' => 0,
                    'digital' => 1,
                    'currency'  => $this->currency_code,
                    'provider' => strtolower($this->provider->name),
                    'provider_id' => $this->provider->id,
                    'stock' => 10,
                    'contRating' => 0,
                    'rating' => 0,
                    'wholesale_product' => 0,
                    'descriptions' => (array) [],
                    'attributes' => (array) [],
                    'min_qty' => 1,
                    'added_by' => 'admin',
                    'user_id'  => get_admin()->id,
                ]);
                $objects[$key] = $productObject;
            }
        }
        return $objects;
    }

    private function createMainCategory()
    {
        return Category::create([
            'provider_id' => $this->provider->id,
            'external_id' => 0,
            'parent_id' => 0,
            'name' => 'منتجات رقمية',
            'order_level' => 25,
            'commision_rate' => 0.20,
            'featured' => 1,
            'top' => 0,
            'digital' => 1,
            'slug' => '-likecard',
            'meta_description' => 'منتجات رقمية',
            'meta_title' => 'منتجات رقمية',
        ]);
    }

    private function encryptId(float $number): int
    {
        if ($number === 0) {
            return 0;
        }
        $key = $this->hashKey;
        $key = intval(substr(hash('sha256', $key), 0, 3), 16);
        $encrypted = ($number * $key) ^ $key;
        return abs($encrypted);
    }

    private function decryptId(float $encrypted): int
    {
        if ($encrypted === 0) {
            return 0;
        }
        $key = $this->hashKey;
        $key = intval(substr(hash('sha256', $key), 0, 3), 16);
        return ($encrypted ^ $key) / $key;
    }
}
