<?php

namespace App\Utility;

use App\Mail\InvoiceEmailManager;
use App\Models\User;
use App\Models\SmsTemplate;
use App\Http\Controllers\OTPVerificationController;
use Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderNotification;
use Google\Auth\CredentialsLoader;
use Google\Auth\HttpHandler\Guzzle6HttpHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class NotificationUtility
{
    public static function sendOrderPlacedNotification($order, $request = null)
    {
        //sends email to customer with the invoice pdf attached
        $array['view'] = 'emails.invoice';
        $array['subject'] = translate('A new order has been placed') . ' - ' . $order->code;
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['order'] = $order;
        try {
            if ($order->user->email != null) {
                Mail::to($order->user->email)->queue(new InvoiceEmailManager($array));
            }
            Mail::to($order->orderDetails->first()->product->user->email)->queue(new InvoiceEmailManager($array));
        } catch (\Exception $e) {
        }

        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'order_placement')->first()->status == 1) {
            try {
                $otpController = new OTPVerificationController;
                $otpController->send_order_code($order);
            } catch (\Exception $e) {
            }
        }

        //sends Notifications to user
        self::sendNotification($order, 'placed');
        if ($request != null && get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order placed !";
            $request->text = "An order {$order->code} has been placed";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            self::sendFirebaseNotification($request);
        }
    }

    public static function sendNotification($order, $order_status)
    {
        if ($order->seller_id == \App\Models\User::where('user_type', 'admin')->first()->id) {
            $users = User::findMany([$order->user->id, $order->seller_id]);
        } else {
            $users = User::findMany([$order->user->id, $order->seller_id, \App\Models\User::where('user_type', 'admin')->first()->id]);
        }

        $order_notification = array();
        $order_notification['order_id'] = $order->id;
        $order_notification['order_code'] = $order->code;
        $order_notification['user_id'] = $order->user_id;
        $order_notification['seller_id'] = $order->seller_id;
        $order_notification['status'] = $order_status;

        Notification::send($users, new OrderNotification($order_notification));
    }

    public static function sendFirebaseNotification($req)
    {
        try {
            // Scopes required for Firebase Cloud Messaging
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            // Path to the service account key file
            $keyFilePath = storage_path('app/private/google-service-account.json'); // Correct path to your service account file

            // Load credentials from the service account JSON file
            $credentials = CredentialsLoader::makeCredentials($scopes, json_decode(file_get_contents($keyFilePath), true));

            // Create a Guzzle HTTP client
            $guzzleClient = new Client();

            // Use Guzzle to create an HTTP handler
            $httpHandler = new Guzzle6HttpHandler($guzzleClient); // Pass the Guzzle client to the Guzzle6HttpHandler

            // Fetch the OAuth2 access token
            $token = $credentials->fetchAuthToken($httpHandler)['access_token'];

            if (!$token) {
                throw new \Exception('Unable to fetch OAuth token.');
            }

            // Prepare FCM HTTP v1 API request URL
            $url = env('FCM_URL'); // Replace with your FCM URL

            // Message fields
            $fields = [
                'message' => [
                    'token' => $req->device_token,
                    'notification' => [
                        'title' => $req->title,
                        'body' => $req->text
                    ],
                    'data' => [
                        'item_type' => (string) $req->type, // Convert to string
                        'item_type_id' => (string) $req->id, // Convert to string
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'route' => (string) $req->route
                    ]
                ]
            ];

            // HTTP headers
            $headers = [
                'Authorization' => 'Bearer ' . $token,  // OAuth2 Bearer token
                'Content-Type' => 'application/json'
            ];

            // Send the request
            $response = $guzzleClient->post($url, [
                'headers' => $headers,
                'json' => $fields,
                'http_errors' => false // Disable exceptions on HTTP errors to handle the response manually
            ]);

            // Check if the response status is 200 (OK)
            if ($response->getStatusCode() !== 200) {
                $errorBody = json_decode($response->getBody()->getContents(), true);
                throw new \Exception('FCM Error: ' . ($errorBody['error']['message'] ?? 'Unknown error'));
            }

            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            // Catch request-specific errors (Guzzle errors)
            \Log::error('FCM RequestException: ' . $e->getMessage());
            throw new \Exception('FCM Request Failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Catch general exceptions
            \Log::error('FCM Exception: ' . $e->getMessage());
            throw new \Exception('FCM Notification Error: ' . $e->getMessage());
        }
    }
}
