<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\GoogleDriveService;
use Symfony\Component\Process\Process;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('view settings');

        $razorpayPaymentMode   = Setting::getValue('razorpay_payment_mode', 'test');
        $razorpayTestKeyId     = Setting::getValue('razorpay_test_key_id', '');
        $razorpayTestKeySecret = Setting::getValue('razorpay_test_key_secret', '');
        $razorpayLiveKeyId     = Setting::getValue('razorpay_live_key_id', '');
        $razorpayLiveKeySecret = Setting::getValue('razorpay_live_key_secret', '');
        $announcementText      = Setting::getValue('announcement_text', '');
        $paymentMethodCod      = (bool) Setting::getValue('payment_method_cod', true);
        $paymentMethodRazorpay = (bool) Setting::getValue('payment_method_razorpay', true);
        $comingSoon            = (bool) Setting::getValue('coming_soon', false);
        $googleDriveConnected = false;
        $googleDriveEmail = '';
        $token = Setting::getValue('google_drive_oauth_token');
        if ($token) {
            $googleDriveConnected = true;
            $googleDriveEmail = Setting::getValue('google_drive_connected_email', 'Connected Account');
        }

        $prefixOnlineOrder      = Setting::getValue('prefix_online_order', 'OR');
        $prefixOfflineSale      = Setting::getValue('prefix_offline_sale', 'SA');
        $prefixOfflineSaleGst   = Setting::getValue('prefix_offline_sale_gst', 'GS');
        $prefixSupplierPurchase = Setting::getValue('prefix_supplier_purchase', 'PS');
        $prefixSupplierPurchaseGst = Setting::getValue('prefix_supplier_purchase_gst', 'GP');
        $prefixStockTransfer    = Setting::getValue('prefix_stock_transfer', 'ST');
        $purchaseGstRate        = Setting::getValue('purchase_gst_rate', '3');
        $storeState             = Setting::getValue('store_state', 'Gujarat');

        Setting::setValue('instagram_username', 'chetan_imitation');
        Setting::setValue('instagram_profile_url', 'https://www.instagram.com/chetan_imitation?igsh=Zm9lNHNoaTQ3c2t4');
        Setting::setValue('instagram_access_token', '');

        $instagramUsername    = Setting::getValue('instagram_username', 'chetan_imitation');
        $instagramProfileUrl  = Setting::getValue('instagram_profile_url', 'https://www.instagram.com/chetan_imitation?igsh=Zm9lNHNoaTQ3c2t4');
        $instagramAccessToken = Setting::getValue('instagram_access_token', '');

        return view('settings.index', compact(
            'razorpayPaymentMode',
            'razorpayTestKeyId',
            'razorpayTestKeySecret',
            'razorpayLiveKeyId',
            'razorpayLiveKeySecret',
            'announcementText',
            'paymentMethodCod',
            'paymentMethodRazorpay',
            'comingSoon',
            'googleDriveConnected',
            'googleDriveEmail',
            'prefixOnlineOrder',
            'prefixOfflineSale',
            'prefixOfflineSaleGst',
            'prefixSupplierPurchase',
            'prefixSupplierPurchaseGst',
            'prefixStockTransfer',
            'purchaseGstRate',
            'storeState',
            'instagramUsername',
            'instagramProfileUrl',
            'instagramAccessToken'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit settings');

        $validator = Validator::make($request->all(), [
            'razorpay_payment_mode'   => ['nullable', 'string', 'in:test,live'],
            'razorpay_test_key_id'    => ['nullable', 'string', 'max:255'],
            'razorpay_test_key_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_live_key_id'    => ['nullable', 'string', 'max:255'],
            'razorpay_live_key_secret' => ['nullable', 'string', 'max:255'],
            'announcement_text'       => ['nullable', 'string', 'max:500'],
            'payment_method_cod'      => ['nullable', 'boolean'],
            'payment_method_razorpay' => ['nullable', 'boolean'],
            'coming_soon'             => ['nullable', 'boolean'],
            'prefix_online_order'      => ['nullable', 'string', 'max:10'],
            'prefix_offline_sale'      => ['nullable', 'string', 'max:10'],
            'prefix_offline_sale_gst'  => ['nullable', 'string', 'max:10'],
            'prefix_supplier_purchase' => ['nullable', 'string', 'max:10'],
            'prefix_supplier_purchase_gst' => ['nullable', 'string', 'max:10'],
            'prefix_stock_transfer'    => ['nullable', 'string', 'max:10'],
            'purchase_gst_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'store_state'              => ['nullable', 'string', 'max:100'],
            'instagram_username'       => ['nullable', 'string', 'max:255'],
            'instagram_profile_url'    => ['nullable', 'url', 'max:500'],
            'instagram_access_token'   => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors(),
            ], 422);
        }

        $codEnabled      = $request->boolean('payment_method_cod');
        $razorpayEnabled = $request->boolean('payment_method_razorpay');

        if (!$codEnabled && !$razorpayEnabled) {
            return response()->json([
                'status'  => 'error',
                'message' => ['payment_method_cod' => ['At least one payment method must be enabled.']],
            ], 422);
        }

        $sensitiveKeys = ['razorpay_test_key_id', 'razorpay_test_key_secret', 'razorpay_live_key_id', 'razorpay_live_key_secret', 'instagram_access_token'];
        $newValues = [
            'razorpay_payment_mode'   => $request->razorpay_payment_mode ?? 'test',
            'razorpay_test_key_id'    => $request->razorpay_test_key_id ?? '',
            'razorpay_test_key_secret' => $request->razorpay_test_key_secret ?? '',
            'razorpay_live_key_id'    => $request->razorpay_live_key_id ?? '',
            'razorpay_live_key_secret' => $request->razorpay_live_key_secret ?? '',
            'announcement_text'       => $request->announcement_text ?? '',
            'payment_method_cod'      => $codEnabled ? '1' : '0',
            'payment_method_razorpay' => $razorpayEnabled ? '1' : '0',
            'coming_soon'             => $request->boolean('coming_soon') ? '1' : '0',
            'prefix_online_order'      => $request->prefix_online_order ?? 'OR',
            'prefix_offline_sale'      => $request->prefix_offline_sale ?? 'SA',
            'prefix_offline_sale_gst'  => $request->prefix_offline_sale_gst ?? 'GS',
            'prefix_supplier_purchase' => $request->prefix_supplier_purchase ?? 'PS',
            'prefix_supplier_purchase_gst' => $request->prefix_supplier_purchase_gst ?? 'GP',
            'prefix_stock_transfer'    => $request->prefix_stock_transfer ?? 'ST',
            'purchase_gst_rate'        => $request->purchase_gst_rate ?? '3',
            'store_state'              => $request->store_state ?? 'Gujarat',
        ];

        if ($request->has('instagram_username')) {
            $newValues['instagram_username'] = $request->instagram_username ?? '';
        }
        if ($request->has('instagram_profile_url')) {
            $newValues['instagram_profile_url'] = $request->instagram_profile_url ?? '';
        }
        if ($request->has('instagram_access_token')) {
            $newValues['instagram_access_token'] = $request->instagram_access_token ?? '';
        }

        $old = [];
        $new = [];
        foreach ($newValues as $key => $value) {
            $previous = (string) Setting::getValue($key, '');
            if ($previous !== (string) $value) {
                $old[$key] = in_array($key, $sensitiveKeys, true) ? '••••••••' : $previous;
                $new[$key] = in_array($key, $sensitiveKeys, true) ? '••••••••' : $value;
            }
            Setting::setValue($key, $value);
        }

        \Illuminate\Support\Facades\Cache::forget('instagram_feed_posts');

        if (!empty($new)) {
            ActivityLogger::log('Settings', 'update', null, $old, $new, 'Payment/site settings updated');
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Settings updated successfully.',
        ]);
    }

    public function googleDriveConnect()
    {
        $this->authorize('edit settings');

        try {
            $googleService = app(GoogleDriveService::class);
            $client = $googleService->getClient();
            $authUrl = $client->createAuthUrl();
            return redirect()->away($authUrl);
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index')->with('error', 'Google Drive connection setup failed: ' . $e->getMessage());
        }
    }

    public function googleDriveCallback(Request $request)
    {
        $this->authorize('edit settings');

        if ($request->has('error')) {
            return redirect()->route('admin.settings.index')->with('error', 'Google Drive connection cancelled: ' . $request->get('error'));
        }

        if (!$request->has('code')) {
            return redirect()->route('admin.settings.index')->with('error', 'Google Drive connection failed: Authorization code not found.');
        }

        try {
            $googleService = app(GoogleDriveService::class);
            $client = $googleService->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

            if (isset($token['error'])) {
                return redirect()->route('admin.settings.index')->with('error', 'Failed to fetch access token: ' . ($token['error_description'] ?? $token['error']));
            }

            Setting::setValue('google_drive_oauth_token', $token);

            // Fetch the user's email to show on settings page
            $oauth2 = new \Google\Service\Oauth2($client);
            $email = 'Connected Account';
            try {
                $userInfo = $oauth2->userinfo->get();
                $email = $userInfo->email;
                Setting::setValue('google_drive_connected_email', $email);
            } catch (\Throwable $e) {
                // In case API fails or scope isn't granted, set default placeholder
                Setting::setValue('google_drive_connected_email', $email);
            }

            ActivityLogger::log('Settings', 'update', null, null, null, 'Google Drive account connected: ' . $email);

            return redirect()->route('admin.settings.index')->with('success', 'Google Drive connected successfully!');
        } catch (\Throwable $e) {
            Log::error('Google Drive OAuth callback failed: ' . $e->getMessage());
            return redirect()->route('admin.settings.index')->with('error', 'Failed to connect Google Drive: ' . $e->getMessage());
        }
    }

    public function googleDriveDisconnect()
    {
        $this->authorize('edit settings');

        $email = Setting::getValue('google_drive_connected_email', 'Connected Account');

        Setting::setValue('google_drive_oauth_token', null);
        Setting::setValue('google_drive_connected_email', null);

        ActivityLogger::log('Settings', 'update', null, null, null, 'Google Drive account disconnected (was: ' . $email . ')');

        return redirect()->route('admin.settings.index')->with('success', 'Google Drive disconnected successfully.');
    }

    public function runBackup()
    {
        $this->authorize('download backup');

        $connection = config('database.connections.' . config('database.default'));
        $fileName = 'Backup_' . now()->format('Y-m-d_H-i-s') . '.sql';

        $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $defaultMysqldump = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
            : 'mysqldump';
        $mysqldumpBin = env('MYSQLDUMP_PATH', $defaultMysqldump);

        $command = [
            $mysqldumpBin,
            '-h', $connection['host'],
            '-P', (string) ($connection['port'] ?? 3306),
            '-u', $connection['username'],
        ];
        if (!empty($connection['password'])) {
            $command[] = '-p' . $connection['password'];
        }
        $command[] = '--routines';
        $command[] = '--single-transaction';
        $command[] = '--result-file=' . $filePath;
        $command[] = $connection['database'];

        $env = array_merge($_ENV, $_SERVER, [
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: 'C:\\Windows\\system32;C:\\Windows',
        ]);

        $process = new Process($command, null, $env);
        $process->setTimeout(600);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('Database backup failed to run mysqldump: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Backup failed: could not run mysqldump. ' . $e->getMessage(),
            ], 500);
        }

        if (!$process->isSuccessful() || !is_file($filePath) || filesize($filePath) === 0) {
            @unlink($filePath);
            Log::error('Database backup failed: ' . $process->getErrorOutput());
            return response()->json([
                'status'  => 'error',
                'message' => 'Backup failed. Please check that mysqldump is installed and accessible.',
            ], 500);
        }

        // Clean up "don't" / "don\'t" / "Don't" / "Don\'t" to prevent MySQL import issues
        try {
            $sqlContent = file_get_contents($filePath);
            $search = ["don\\'t", "don't", "Don\\'t", "Don't"];
            $replace = ["dont", "dont", "Dont", "Dont"];
            $sqlContent = str_replace($search, $replace, $sqlContent);
            file_put_contents($filePath, $sqlContent);
        } catch (\Throwable $e) {
            Log::error('Database backup content cleanup failed: ' . $e->getMessage());
        }

        $driveUploaded = false;
        $driveError = null;

        try {
            app(GoogleDriveService::class)->uploadFile($filePath, $fileName);
            $driveUploaded = true;
        } catch (\Throwable $e) {
            $driveError = $e->getMessage();
            Log::error('Google Drive backup upload failed: ' . $driveError);
        }

        // Delete the temporary local file immediately
        @unlink($filePath);

        if (!$driveUploaded) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Backup failed to upload to Google Drive: ' . $driveError,
            ], 500);
        }

        ActivityLogger::log('Settings', 'export', null, null, null, 'Database backup uploaded to Google Drive');

        return response()->json([
            'status'  => 'success',
            'message' => 'Database backup successfully generated and stored in Google Drive!',
        ]);
    }
}
