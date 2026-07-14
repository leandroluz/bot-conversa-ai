<?php

/**
 * Integration helper for Evolution API
 */
class EvolutionService
{
    public static function getConnectionSnapshot(AppBot $bot)
    {
        $baseUrl = self::getBaseUrl($bot);
        $apiKey = self::getApiKey($bot);
        $instance = trim((string) $bot->evolution_instance);

        if ($instance === '') {
            throw new Exception('Instância Evolution não configurada no bot');
        }

        $instanceItem = null;
        $fetchError = null;
        try {
            $instanceItem = self::findInstanceByName($baseUrl, $apiKey, $instance);
        } catch (Exception $e) {
            $fetchError = $e->getMessage();
        }

        if (empty($instanceItem)) {
            return [
                'exists' => false,
                'state' => 'NOT_CREATED',
                'connected' => false,
                'qr_payload' => null,
                'state_raw' => [],
                'qr_raw' => [],
                'base_url' => $baseUrl,
                'instance' => $instance,
                'instance_id' => null,
                'manager_url' => null,
                'warning' => $fetchError,
            ];
        }

        $initialState = self::extractState($instanceItem);
        $stateResponse = $instanceItem;
        $state = $initialState;

        try {
            $stateResponse = self::attemptState($baseUrl, $apiKey, $instance);
            $state = self::extractState($stateResponse);
        } catch (Exception $e) {
            // Keep state from fetchInstances when connectionState endpoint is unavailable
        }

        $isConnected = self::isConnectedState($state);
        $qrResponse = null;

        if (!$isConnected) {
            $qrResponse = self::attemptQrCode($baseUrl, $apiKey, $instance);

            if (!self::extractQrPayload($qrResponse)) {
                self::attemptConnect($baseUrl, $apiKey, $instance);
                $stateResponse = self::attemptState($baseUrl, $apiKey, $instance);
                $state = self::extractState($stateResponse);
                $isConnected = self::isConnectedState($state);

                if (!$isConnected) {
                    $qrResponse = self::attemptQrCode($baseUrl, $apiKey, $instance);
                }
            }
        }

        $qrPayload = self::extractQrPayload($qrResponse);

        return [
            'exists' => true,
            'state' => $state,
            'connected' => $isConnected,
            'qr_payload' => $qrPayload,
            'state_raw' => $stateResponse,
            'qr_raw' => $qrResponse,
            'base_url' => $baseUrl,
            'instance' => $instance,
            'instance_id' => self::extractInstanceId($instanceItem),
            'manager_url' => self::getManagerUrl($baseUrl, self::extractInstanceId($instanceItem)),
            'warning' => $fetchError,
        ];
    }

    public static function createInstance(AppBot $bot)
    {
        $baseUrl = self::getBaseUrl($bot);
        $apiKey = self::getApiKey($bot);
        $instance = trim((string) $bot->evolution_instance);

        if ($instance === '') {
            throw new Exception('Instância Evolution não configurada no bot');
        }

        if (self::findInstanceByName($baseUrl, $apiKey, $instance)) {
            return ['already_exists' => true];
        }

        return self::request(
            $baseUrl,
            '/instance/create',
            'POST',
            [
                'instanceName' => $instance,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
            ],
            $apiKey
        );
    }

    private static function findInstanceByName($baseUrl, $apiKey, $instance)
    {
        $response = self::request(
            $baseUrl,
            '/instance/fetchInstances',
            'GET',
            [],
            $apiKey
        );

        $instances = self::extractInstanceList($response);
        foreach ($instances as $item) {
            $name = strtolower((string) ($item['name'] ?? $item['instanceName'] ?? $item['instance']['instanceName'] ?? ''));
            if ($name === strtolower($instance)) {
                return $item;
            }
        }

        return null;
    }

    public static function disconnect(AppBot $bot)
    {
        $baseUrl = self::getBaseUrl($bot);
        $apiKey = self::getApiKey($bot);
        $instance = trim((string) $bot->evolution_instance);

        if ($instance === '') {
            throw new Exception('Instância Evolution não configurada no bot');
        }

        $endpoints = [
            ['DELETE', "/instance/logout/{$instance}"],
            ['POST', "/instance/logout/{$instance}"],
            ['DELETE', "/instance/disconnect/{$instance}"],
            ['POST', "/instance/disconnect/{$instance}"],
        ];

        foreach ($endpoints as $endpoint) {
            list($method, $path) = $endpoint;
            try {
                return self::request($baseUrl, $path, $method, [], $apiKey);
            } catch (Exception $e) {
            }
        }

        throw new Exception('Não foi possível desconectar a instância na Evolution API');
    }

    private static function attemptState($baseUrl, $apiKey, $instance)
    {
        $endpoints = [
            ['GET', "/instance/connectionState/{$instance}"],
            ['POST', "/instance/connectionState/{$instance}"],
            ['GET', "/instance/state/{$instance}"],
            ['GET', "/chat/connectionState/{$instance}"],
        ];

        return self::firstSuccessfulRequest($baseUrl, $apiKey, $endpoints, false);
    }

    private static function attemptQrCode($baseUrl, $apiKey, $instance)
    {
        $endpoints = [
            ['GET', "/instance/connect/{$instance}"],
            ['POST', "/instance/connect/{$instance}"],
            ['GET', "/instance/qrCode/{$instance}"],
            ['GET', "/instance/qrcode/{$instance}"],
            ['GET', "/instance/qr/{$instance}"],
        ];

        return self::firstSuccessfulRequest($baseUrl, $apiKey, $endpoints, false);
    }

    private static function attemptConnect($baseUrl, $apiKey, $instance)
    {
        $endpoints = [
            ['POST', "/instance/connect/{$instance}"],
            ['GET', "/instance/connect/{$instance}"],
        ];

        return self::firstSuccessfulRequest($baseUrl, $apiKey, $endpoints, true);
    }

    private static function firstSuccessfulRequest($baseUrl, $apiKey, array $endpoints, $throwOnFail)
    {
        $lastException = null;

        foreach ($endpoints as $endpoint) {
            list($method, $path) = $endpoint;
            try {
                return self::request($baseUrl, $path, $method, [], $apiKey);
            } catch (Exception $e) {
                $lastException = $e;
            }
        }

        if ($throwOnFail && $lastException) {
            throw $lastException;
        }

        return [];
    }

    private static function extractState($payload)
    {
        $candidates = [
            $payload['state'] ?? null,
            $payload['status'] ?? null,
            $payload['instance']['state'] ?? null,
            $payload['instance']['status'] ?? null,
            $payload['data']['state'] ?? null,
            $payload['data']['status'] ?? null,
            $payload['connectionStatus'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return strtoupper(trim($value));
            }
        }

        return 'UNKNOWN';
    }

    private static function isConnectedState($state)
    {
        return in_array(strtoupper((string) $state), ['OPEN', 'CONNECTED', 'ONLINE'], true);
    }

    private static function extractQrPayload($payload)
    {
        if (!is_array($payload) || empty($payload)) {
            return null;
        }

        $candidates = [
            $payload['base64'] ?? null,
            $payload['qrcode']['base64'] ?? null,
            $payload['qrCode']['base64'] ?? null,
            $payload['data']['base64'] ?? null,
            $payload['data']['qrcode'] ?? null,
            $payload['data']['qrCode'] ?? null,
            $payload['qrcode'] ?? null,
            $payload['qrCode'] ?? null,
            $payload['code'] ?? null,
            $payload['pairingCode'] ?? null,
            $payload['data']['code'] ?? null,
            $payload['data']['pairingCode'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $trimmed = trim($value);
            if (strpos($trimmed, 'data:image') === 0) {
                return ['type' => 'image_data_uri', 'value' => $trimmed];
            }

            if (preg_match('/^[A-Za-z0-9+\/\r\n=]+$/', $trimmed) && strlen($trimmed) > 100) {
                return ['type' => 'image_base64', 'value' => preg_replace('/\s+/', '', $trimmed)];
            }

            return ['type' => 'text', 'value' => $trimmed];
        }

        return null;
    }

    private static function extractInstanceList($response)
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        if (isset($response['instances']) && is_array($response['instances'])) {
            return $response['instances'];
        }

        if (array_is_list($response)) {
            return $response;
        }

        return [];
    }

    private static function getManagerUrl($baseUrl, $instanceId)
    {
        if (empty($instanceId)) {
            return rtrim($baseUrl, '/') . '/manager';
        }

        return rtrim($baseUrl, '/') . '/manager/instance/' . $instanceId . '/dashboard';
    }

    private static function extractInstanceId($instanceItem)
    {
        if (!is_array($instanceItem)) {
            return null;
        }

        return $instanceItem['id']
            ?? $instanceItem['instanceId']
            ?? $instanceItem['instance']['instanceId']
            ?? $instanceItem['instance']['id']
            ?? null;
    }

    private static function getBaseUrl(AppBot $bot)
    {
        $url = trim((string) $bot->evolution_api_url);
        if ($url === '') {
            $url = getenv('EVOLUTION_API_BASE_URL') ?: 'http://evolution-api:8080';
        }

        return rtrim($url, '/');
    }

    private static function getApiKey(AppBot $bot)
    {
        $key = trim((string) $bot->evolution_api_key);
        if ($key === '') {
            $key = getenv('EVOLUTION_API_KEY') ?: '';
        }

        return $key;
    }

    private static function request($baseUrl, $path, $method, array $body = [], $apiKey = null)
    {
        $urlCandidates = [
            $baseUrl . $path,
            $baseUrl . '/api' . $path,
        ];

        $lastError = null;
        $authError = null;

        foreach ($urlCandidates as $url) {
            $ch = curl_init();

            $headers = ['Content-Type: application/json'];
            if (!empty($apiKey)) {
                $headers[] = 'apikey: ' . $apiKey;
            }

            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];

            if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                $options[CURLOPT_POSTFIELDS] = json_encode($body);
            }

            curl_setopt_array($ch, $options);
            $raw = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $lastError = $error;
                continue;
            }

            if ($http === 401 || $http === 403) {
                $authError = "HTTP {$http} em {$url}: {$raw}";
                break;
            }

            if ($http < 200 || $http >= 300) {
                $lastError = "HTTP {$http} em {$url}: {$raw}";

                // Try alternative URL only for not found routes.
                if ($http === 404) {
                    continue;
                }

                break;
            }

            if ($raw === '' || $raw === null) {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return ['raw' => $raw];
        }

        if (!empty($authError) || stripos((string) $lastError, '401') !== false || stripos((string) $lastError, 'apikey') !== false) {
            throw new Exception('Falha ao chamar Evolution API: autenticação inválida. Verifique a Chave Evolution API do bot.');
        }

        throw new Exception('Falha ao chamar Evolution API: ' . $lastError);
    }
}
