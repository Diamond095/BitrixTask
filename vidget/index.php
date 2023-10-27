
<?php
// Подключение файла с конфигурациями Битрикс24
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

// Проверка авторизации пользователя
global $USER;
if (!$USER->IsAuthorized()) {
    return;
}

// Проверка доступа на чтение пользователей
if (!Loader::includeModule('intranet')) {
    return;
}

if (!\Bitrix\Main\Loader::includeModule("socialnetwork")) {
    return;
}

// Создание HTTP клиента для отправки запросов к REST API
$httpClient = new HttpClient();

// Получение текущего URL и кодирование его в BASE64
$currentUrl = Context::getCurrent()->getRequest()->getRequestUri();
$encodedUrl = base64_encode($currentUrl);

// Создание URL для авторизации виджета
$authUrl = "https://www.bitrix24.net/oauth/authorize/?" . http_build_query([
        "client_id" => "your_client_id", // Замените на ваш CLIENT_ID
        "redirect_uri" => "https://your_domain.com/widget.php", // Замените на URL виджета
        "response_type" => "code",
        "state" => $encodedUrl,
    ]);

// Функция для получения списка пользователей
function getUsersList()
{
    $usersList = CUser::GetList(
        ($by = "id"),
        ($order = "asc"),
        ["ACTIVE" => "Y"],
        ["SELECT" => ["ID", "NAME", "LAST_NAME", "EMAIL", "UF_DEPARTMENT", "IS_ADMIN"]]
    )->fetchall();

    return $usersList;
}

// Процесс авторизации виджета
if (isset($_GET["code"])) {
// Получение access_token для доступа к REST API
    $response = $httpClient->post("https://www.bitrix24.net/oauth/token/", [
        "grant_type" => "authorization_code",
        "client_id" => "your_client_id", // Замените на ваш CLIENT_ID
        "client_secret" => "your_client_secret", // Замените на ваш CLIENT_SECRET
        "redirect_uri" => "https://your_domain.com/widget.php", // Замените на URL виджета
        "code" => $_GET["code"],
    ]);

    $result = Json::decode($response);

    if (isset($result["access_token"])) {
        $accessToken = $result["access_token"];

// Установка access_token в сессию
        $_SESSION["access_token"] = $accessToken;

// Перенаправление обратно на виджет после успешной авторизации
        header("Location: " . urldecode($_GET["state"]));
        exit();
    } else {
// Если access_token не был получен, показать ошибку
        echo "Authorization failed!";
        exit();
    }
}

// Получение access_token из сессии, если он был сохранен при авторизации
if (isset($_SESSION["access_token"])) {
    $accessToken = $_SESSION["access_token"];

// Получение списка пользователей с помощью REST API
    $response = $httpClient->get("https://your_domain.bitrix24.com/rest/user.get.json", [
        "auth" => $accessToken,
    ]);

    $result = Json::decode($response);

    if (isset($result["result"])) {
        $users = $result["result"];

// Вывод списка пользователей
        echo "<h1>Список пользователей:</h1>";
        echo "<ul>";

        foreach ($users as $user) {
            $userId = $user["ID"];
            $userName = $user["NAME"];
            $userLastName = $user["LAST_NAME"];
            $userEmail = $user["EMAIL"];
            $userDepartments = $user["UF_DEPARTMENT"];

            $isAdmin = ($user["IS_ADMIN"] === "1") ? " (Администратор)" : "";

            // Получение имени отдела пользователя
            $departmentName = "";
            if (!empty($userDepartments)) {
                $departmentId = $userDepartments[0];
                $department = CIntranetUtils::getDepartmentByID($departmentId);
                $departmentName = $department["NAME"];
            }

            echo "<li>{$userName} {$userLastName} ({$userEmail}) - {$departmentName}{$isAdmin}</li>";
        }

        echo "</ul>";
    } else {
// Если произошла ошибка при получении списка пользователей, показать ошибку
        echo "Failed to get users list!";
    }
} else {
// Если access_token не был получен, выводим кнопку для авторизации виджета
    echo "<a href='{$authUrl}'>Список пользователей</a>";
}