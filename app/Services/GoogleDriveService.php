<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use App\Models\Setting;
use RuntimeException;

class GoogleDriveService
{
    protected GoogleClient $client;
    protected ?GoogleDrive $service = null;

    public function __construct()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri') ?: route('admin.settings.google-drive.callback');

        if (!$clientId || !$clientSecret) {
            throw new RuntimeException('Google OAuth Client ID or Client Secret is not configured in .env file.');
        }

        $this->client = new GoogleClient();
        $this->client->setApplicationName('Chetan Imitation - Database Backup');
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->setScopes([GoogleDrive::DRIVE, 'email']);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        // Check if token exists in settings
        $token = Setting::getValue('google_drive_oauth_token');
        if ($token) {
            $this->client->setAccessToken($token);

            // If the token is expired, refresh it
            if ($this->client->isAccessTokenExpired()) {
                $refreshToken = $this->client->getRefreshToken() ?? ($token['refresh_token'] ?? null);
                if ($refreshToken) {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    if (isset($newToken['error'])) {
                        // Log the error but don't crash instantiation in case we are on settings page.
                        \Illuminate\Support\Facades\Log::error('Google Drive token refresh failed: ' . ($newToken['error_description'] ?? $newToken['error']));
                    } else {
                        // Merge refresh token back if it wasn't returned in the refresh response
                        if (!isset($newToken['refresh_token'])) {
                            $newToken['refresh_token'] = $refreshToken;
                        }
                        Setting::setValue('google_drive_oauth_token', $newToken);
                        $this->client->setAccessToken($newToken);
                        $this->service = new GoogleDrive($this->client);
                    }
                }
            } else {
                $this->service = new GoogleDrive($this->client);
            }
        }
    }

    /**
     * Get the Google Client instance (useful for OAuth flow in Controller).
     */
    public function getClient(): GoogleClient
    {
        return $this->client;
    }

    /**
     * Helper to find or create a folder by name and parent ID.
     */
    protected function findOrCreateFolder(string $folderName, string $parent = 'root'): string
    {
        $query = "name = '" . str_replace("'", "\\'", $folderName) . "' and mimeType = 'application/vnd.google-apps.folder' and '{$parent}' in parents and trashed = false";
        
        $response = $this->service->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ]);

        $files = $response->getFiles();
        if (count($files) > 0) {
            return $files[0]->id;
        }

        // Folder doesn't exist, create it
        $folderMetadata = new DriveFile([
            'name'     => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parent],
        ]);

        $folder = $this->service->files->create($folderMetadata, [
            'fields'            => 'id',
            'supportsAllDrives' => true,
        ]);

        return $folder->id;
    }

    /**
     * Upload a local file to Google Drive and return the created file's ID.
     */
    public function uploadFile(string $filePath, string $fileName): string
    {
        if (!$this->service) {
            throw new RuntimeException('Google Drive is not connected. Please connect to Google Drive in System Settings.');
        }

        if (!is_file($filePath)) {
            throw new RuntimeException('File to upload does not exist: ' . $filePath);
        }

        // 1. Get or create "Database Backup" folder in root
        $rootFolderId = $this->findOrCreateFolder('Database Backup', 'root');

        // 2. Get or create date-wise folder inside "Database Backup"
        $dateFolderName = now()->format('d-m-Y');
        $dateFolderId = $this->findOrCreateFolder($dateFolderName, $rootFolderId);

        // 3. Upload file inside the date folder
        $metadata = new DriveFile([
            'name'    => $fileName,
            'parents' => [$dateFolderId],
        ]);

        $file = $this->service->files->create($metadata, [
            'data'              => file_get_contents($filePath),
            'mimeType'          => 'application/sql',
            'uploadType'        => 'multipart',
            'fields'            => 'id',
            'supportsAllDrives' => true,
        ]);

        return $file->id;
    }
}
