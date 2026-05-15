<?php

namespace App\Services;

class Bitrix24SessionManager
{
    private $domain;
    private $login;
    private $password;
    private $cookieFile;
    private $sessionTTL = 7200; // 2 часа

    public function __construct($domain, $login, $password)
    {
        $this->domain = $domain;
        $this->login = $login;
        $this->password = $password;

        $cookieDir = dirname(__DIR__, 2) . '/storage';

        if (!is_dir($cookieDir)) {
            mkdir($cookieDir, 0755, true);
        }

        $this->cookieFile = $cookieDir . '/bitrix_cookies.txt';
    }

    /**
     * Авторизация и сохранение сессии
     */
    public function login()
    {
        if ($this->isSessionValid()) {
            return true;
        }

        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }

        $ch = curl_init();

        // Сразу отправляем форму без предварительного GET
        $postData = [
            'AUTH_FORM' => 'Y',
            'TYPE' => 'AUTH',
            'backurl' => '/stream/',
            'USER_LOGIN' => $this->login,
            'USER_PASSWORD' => $this->password,
            'USER_REMEMBER' => 'Y',
            'Login' => 'Войти',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => "https://{$this->domain}/auth/?login=yes",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        echo "📊 HTTP Code: $httpCode\n";
        echo "🌐 Final URL: $finalUrl\n";

        curl_close($ch);

        // Проверка cookies
        if (file_exists($this->cookieFile)) {
            $cookieContent = file_get_contents($this->cookieFile);

            $hasLogin = strpos($cookieContent, 'BITRIX_SM_LOGIN') !== false;
            $hasUid = strpos($cookieContent, 'BITRIX_SM_UIDD') !== false;

            if ($hasLogin && $hasUid) {
                echo "✅ Авторизация успешна!\n";
                return true;
            }
        }

        throw new \Exception('Авторизация не прошла');
    }

    /**
     * Проверка актуальности сессии
     */
    public function isSessionValid()
    {
        // Проверка существования и размера файла
        if (!file_exists($this->cookieFile) || filesize($this->cookieFile) === 0) {
            return false;
        }

        // Проверка возраста файла
        $fileAge = time() - filemtime($this->cookieFile);

        // Если файл свежий (< 5 минут) — считаем валидным
        if ($fileAge < 300) {
            return true;
        }

        // Если слишком старый — протух
        if ($fileAge > $this->sessionTTL) {
            return false;
        }

        // Для средних значений делаем тестовый запрос
        $ch = curl_init("https://{$this->domain}/stream/");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        curl_close($ch);

        // Редирект на /auth/ — сессия протухла
        if ($httpCode === 302 && strpos($redirectUrl, '/auth/') !== false) {
            return false;
        }

        return $httpCode === 200;
    }

    /**
     * Скачать файл используя сохранённую сессию
     */
    public function downloadFile($fileUrl, $savePath)
    {
        // ✅ ВАЖНО: Разкомментировал проверку сессии
        if (!$this->isSessionValid()) {
            $this->login();
        }

        $ch = curl_init($fileUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $fileContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Не удалось скачать файл. HTTP код: $httpCode, URL: $fileUrl");
        }

        $result = file_put_contents($savePath, $fileContent);

        if ($result === false) {
            throw new \Exception("Не удалось записать файл по пути: $savePath");
        }

        return $savePath;
    }

    /**
     * Очистить сессию
     */
    public function logout()
    {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }
}
