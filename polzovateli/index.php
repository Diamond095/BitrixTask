<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$filter = [
    "ACTIVE" => "Y",
    // здесь вы можете добавить дополнительные фильтры, если необходимо
];
$rsUsers = CUser::GetList(($by = "ID"), ($order = "ASC"), $filter);
$userList = [];
while ($arUser = $rsUsers->Fetch()) {
    $groups = CUser::GetUserGroup($arUser["ID"]);
    $groupList = CGroup::GetList(($by = "c_sort"), ($order = "asc"));
    $userGroups = [];
    while ($group = $groupList->Fetch()) {
        if (in_array($group["ID"], $groups)) {
            $userGroups[] = $group;
        }
    }

    $arUser["GROUPS"] = $userGroups;
    $userList[] = $arUser;
}
?>

    <div class="user-widget">
        <button onclick="showUsers()">Пользователи</button>
        <div id="userList" style="display: none;">
            <?php
            foreach ($userList as $user) {
                echo "<p".(in_array("1", array_column($user["GROUPS"], "ID")) ? ' style="color: red;"' : '').">".$user["NAME"]." ".$user["LAST_NAME"]."</p>";
                echo "<p>Группы: ".implode(", ", array_column($user["GROUPS"], "NAME"))."</p>";
                // здесь вы можете вывести дополнительную информацию о пользователе
            }
            ?>
        </div>
    </div>

    <script>
        function showUsers() {
            var userList = document.getElementById("userList");
            if (userList.style.display === "none") {
                userList.style.display = "block";
            } else {
                userList.style.display = "none";
            }
        }
    </script>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>