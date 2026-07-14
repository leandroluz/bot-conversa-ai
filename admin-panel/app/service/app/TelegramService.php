<?php

/**
 * Integration helper for Telegram Bot API
 */
class TelegramService
{
    public static function getConnectionSnapshot(AppBot $bot)
    {
        $token = self::getToken($bot);
        if ($token === '') {
            return [
                'token_valid' => false,
                'state' => 'TOKEN_NOT_CONFIGURED',
                'connected' => false,
                'bot_id' => null,
                'bot_username' => null,
                'bot_name' => null,
                'configured_webhook_url' => null,
                'pending_update_count' => 0,
                'last_error_message' => null,
                'warning' => 'Token do bot Telegram não configurado.',
            ];
        }

        $botInfo = null;
        $webhookInfo = null;
        $warning = null;

        try {
            $botInfo = self::request($token, 'getMe');
            $webhookInfo = self::request($token, 'getWebhookInfo');
        } catch (Exception $e) {
            $warning = $e->getMessage();
        }

        if (!$botInfo || !$webhookInfo) {
            return [
                'token_valid' => false,
                'state' => 'TOKEN_INVALID',
                'connected' => false,
                'bot_id' => null,
                'bot_username' => null,
                'bot_name' => null,
                'configured_webhook_url' => null,
                'pending_update_count' => 0,
                'last_error_message' => null,
                'warning' => $warning ?: 'Não foi possível validar token do Telegram.',
            ];
        }

        $webhookUrl = trim((string) ($webhookInfo['url'] ?? ''));
        $pending = (int) ($webhookInfo['pending_update_count'] ?? 0);
        $lastErrorMessage = trim((string) ($webhookInfo['last_error_message'] ?? ''));
        $connected = $webhookUrl !== '';

        return [
            'token_valid' => true,
            'state' => $connected ? 'WEBHOOK_CONFIGURED' : 'WEBHOOK_NOT_CONFIGURED',
            'connected' => $connected,
            'bot_id' => $botInfo['id'] ?? null,
            'bot_username' => $botInfo['username'] ?? null,
            'bot_name' => $botInfo['first_name'] ?? null,
            'configured_webhook_url' => $webhookUrl !== '' ? $webhookUrl : null,
            'pending_update_count' => $pending,
            'last_error_message' => $lastErrorMessage !== '' ? $lastErrorMessage : null,
            'warning' => $warning,
        ];
    }

    public static function setWebhook(AppBot $bot, $webhookUrl = null, $secret = null)
    {
        $token = self::getToken($bot);
        if ($token === '') {
            throw new Exception('Token do bot Telegram não configurado.');
        }

        $url = trim((string) ($webhookUrl ?? $bot->telegram_webhook_url));
        if ($url === '') {
            throw new Exception('Webhook URL do Telegram não configurado.');
        }

        if (!preg_match('#^https://#i', $url)) {
            throw new Exception('Webhook URL do Telegram deve usar HTTPS.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Webhook URL do Telegram inválida.');
        }

        $payload = ['url' => $url];

        $secretValue = trim((string) ($secret ?? $bot->telegram_webhook_secret));
        if ($secretValue !== '') {
            $payload['secret_token'] = $secretValue;
        }

        return self::request($token, 'setWebhook', $payload);
    }

    public static function deleteWebhook(AppBot $bot, $dropPendingUpdates = false)
    {
        $token = self::getToken($bot);
        if ($token === '') {
            throw new Exception('Token do bot Telegram não configurado.');
        }

        return self::request($token, 'deleteWebhook', [
            'drop_pending_updates' => (bool) $dropPendingUpdates,
        ]);
    }

    private static function getToken(AppBot $bot)
    {
        return trim((string) $bot->telegram_bot_token);
    }

    private static function request($token, $method, array $payload = [])
    {
        $url = "https://api.telegram.org/bot{$token}/{$method}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Falha de rede ao chamar Telegram API: ' . $error);
        }

        if ($raw === false || $raw === null || $raw === '') {
            throw new Exception('Resposta vazia da Telegram API.');
        }

        $json = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            throw new Exception('Resposta inválida da Telegram API.');
        }

        if ($http < 200 || $http >= 300) {
            $desc = $json['description'] ?? "HTTP {$http}";
            throw new Exception('Falha ao chamar Telegram API: ' . $desc);
        }

        if (empty($json['ok'])) {
            $desc = $json['description'] ?? 'erro desconhecido';
            throw new Exception('Telegram API retornou erro: ' . $desc);
        }

        return $json['result'] ?? [];
    }
}

