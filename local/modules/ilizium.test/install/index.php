<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Highloadblock\HighloadBlockTable;

Loc::loadMessages(__FILE__);

class ilizium_test extends CModule
{

    /**
     * @var string
     */
    public $MODULE_ID = "ilizium.test";

    /**
     * @var mixed
     */
    public $MODULE_VERSION;
    /**
     * @var mixed
     */
    public $MODULE_VERSION_DATE;
    /**
     * @var string|null
     */
    public $MODULE_NAME;
    /**
     * @var string|null
     */
    public $MODULE_DESCRIPTION;
    /**
     * @var string
     */
    public $PARTNER_NAME;
    /**
     * @var string
     */
    public $PARTNER_URI;
    /**
     * @var string
     */
    public string $JSON_FILE;

    public function __construct()
    {
        include __DIR__."/version.php";

        if (isset($arModuleVersion) && is_array($arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = Loc::getMessage("ILIZIUM_TEST_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("ILIZIUM_TEST_MODULE_DESCRIPTION");

        $this->PARTNER_NAME = "Имя или название компании";
        $this->PARTNER_URI = "http://www.example.com";

        $this->JSON_FILE = $_SERVER["DOCUMENT_ROOT"]."/local/modules/".$this->MODULE_ID."/install/data/messages.json";
    }

    /**
     * @return true
     * @throws \Bitrix\Main\ObjectException
     */
    public function DoInstall(): true
    {
        RegisterModule($this->MODULE_ID);
        $this->InstallHBlock();
        $this->InstallAgent();
        return true;
    }

    /**
     * @return true
     */
    public function DoUninstall(): true
    {
        $this->UnInstallAgent();
        $this->UnInstallHBlock();
        UnRegisterModule($this->MODULE_ID);
        return true;
    }

    /**
     * @return void
     */
    private function InstallAgent(): void
    {
        CAgent::AddAgent(
            "iliziumUpdateChatInfoAgent();",
            $this->MODULE_ID,
            "N",
            600
        );
    }

    /**
     * @return void
     */
    private function UnInstallAgent(): void
    {
        CAgent::RemoveAgent("iliziumUpdateChatInfoAgent();", $this->MODULE_ID);
    }

    /**
     * @return void
     * @throws \Bitrix\Main\ObjectException
     */
    private function InstallHBlock(): void
    {
        $jsonFile = $this->JSON_FILE;
        if (file_exists($jsonFile)) {
            $jsonData = file_get_contents($jsonFile);
            $messages = json_decode($jsonData, true);
            if (is_array($messages)) {
                $userLogins = [];
                foreach ($messages as $msg) {
                    if (!in_array($msg['user_login'], $userLogins)) {
                        $userLogins[] = $msg['user_login'];
                    }
                }
                // Подключаем модуль пользователей
                CModule::IncludeModule("main");
                foreach ($userLogins as $login) {
                    // Поиск пользователя по логину
                    $rsUser = CUser::GetList(($by = "ID"), ($order = "ASC"), ["LOGIN" => $login]);
                    if (!$rsUser->Fetch()) {
                        // Если пользователь не найден, создаём его
                        $user = new CUser;
                        $fields = [
                            "LOGIN" => $login,
                            "NAME" => $login,
                            "EMAIL" => $login."@example.com",
                            "LID" => "ru",
                            "ACTIVE" => "Y",
                            "PASSWORD" => "123456",
                            "CONFIRM_PASSWORD" => "123456",
                        ];
                        $user->Add($fields);
                    }
                }
            }
        }

        $hlBlockName = 'b_ilizium_chat_info';
        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['TABLE_NAME' => $hlBlockName],
        ])->fetch();

        if (!$hlBlock) {
            $result = HighloadBlockTable::add([
                'NAME' => 'ChatInfo',
                'TABLE_NAME' => $hlBlockName,
            ]);

            if ($result->isSuccess()) {
                $hlBlockId = $result->getId();
                CModule::IncludeModule("highloadblock");
                $arUserFields = [
                    [
                        "FIELD_NAME" => "UF_CHAT_ID",
                        "USER_TYPE_ID" => "integer",
                        "XML_ID" => "UF_CHAT_ID",
                        "SORT" => 100,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "Y",
                        "EDIT_FORM_LABEL" => ["ru" => "ID чата"],
                        "LIST_COLUMN_LABEL" => ["ru" => "ID чата"],
                    ],
                    [
                        "FIELD_NAME" => "UF_CHAT_AUTHOR",
                        "USER_TYPE_ID" => "string",
                        "XML_ID" => "UF_CHAT_AUTHOR",
                        "SORT" => 200,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Автор чата"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Автор чата"],
                    ],
                    [
                        "FIELD_NAME" => "UF_FIRST_MESSAGE",
                        "USER_TYPE_ID" => "string",
                        "XML_ID" => "UF_FIRST_MESSAGE",
                        "SORT" => 300,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Первое сообщение"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Первое сообщение"],
                    ],
                    [
                        "FIELD_NAME" => "UF_FIRST_MESSAGE_TIME",
                        "USER_TYPE_ID" => "datetime",
                        "XML_ID" => "UF_FIRST_MESSAGE_TIME",
                        "SORT" => 400,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Время первого сообщения"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Время первого сообщения"],
                    ],
                    [
                        "FIELD_NAME" => "UF_LAST_MESSAGE_AUTHOR",
                        "USER_TYPE_ID" => "string",
                        "XML_ID" => "UF_LAST_MESSAGE_AUTHOR",
                        "SORT" => 500,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Автор последнего сообщения"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Автор последнего сообщения"],
                    ],
                    [
                        "FIELD_NAME" => "UF_LAST_MESSAGE",
                        "USER_TYPE_ID" => "string",
                        "XML_ID" => "UF_LAST_MESSAGE",
                        "SORT" => 600,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Последнее сообщение"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Последнее сообщение"],
                    ],
                    [
                        "FIELD_NAME" => "UF_LAST_MESSAGE_TIME",
                        "USER_TYPE_ID" => "datetime",
                        "XML_ID" => "UF_LAST_MESSAGE_TIME",
                        "SORT" => 700,
                        "MULTIPLE" => "N",
                        "MANDATORY" => "N",
                        "EDIT_FORM_LABEL" => ["ru" => "Время последнего сообщения"],
                        "LIST_COLUMN_LABEL" => ["ru" => "Время последнего сообщения"],
                    ],
                ];

                $userType = new CUserTypeEntity;
                foreach ($arUserFields as $field) {
                    $field["ENTITY_ID"] = "HLBLOCK_".$hlBlockId;
                    $userType->Add($field);
                }
            }
        }

        if (isset($messages) && is_array($messages)) {
            $chats = [];
            foreach ($messages as $msg) {
                $chatId = $msg['chat_id'];
                if (!isset($chats[$chatId])) {
                    $chats[$chatId] = [];
                }
                $chats[$chatId][] = $msg;
            }

            $hlBlock = HighloadBlockTable::getList([
                'filter' => ['TABLE_NAME' => $hlBlockName],
            ])->fetch();
            if ($hlBlock) {
                $entity = HighloadBlockTable::compileEntity($hlBlock);
                $entityDataClass = $entity->getDataClass();

                foreach ($chats as $chatId => $msgs) {
                    // Сортируем сообщения по времени
                    usort($msgs, function ($a, $b) {
                        return strtotime($a['time']) - strtotime($b['time']);
                    });
                    $first = reset($msgs);
                    $last = end($msgs);

                    $chatAuthor = $first['user_login'];

                    $res = $entityDataClass::getList([
                        'filter' => ['UF_CHAT_ID' => $chatId],
                    ])->fetch();

                    $data = [
                        "UF_CHAT_ID" => $chatId,
                        "UF_CHAT_AUTHOR" => $chatAuthor,
                        "UF_FIRST_MESSAGE" => $first['text'],
                        "UF_FIRST_MESSAGE_TIME" => new \Bitrix\Main\Type\DateTime($first['time'], 'Y-m-d H:i:s'),
                        "UF_LAST_MESSAGE_AUTHOR" => $last['user_login'],
                        "UF_LAST_MESSAGE" => $last['text'],
                        "UF_LAST_MESSAGE_TIME" => new \Bitrix\Main\Type\DateTime($last['time'], 'Y-m-d H:i:s'),
                    ];

                    if ($res) {
                        // Обновляем существующую
                        $entityDataClass::update($res["ID"], $data);
                    } else {
                        // Создаем новую
                        $entityDataClass::add($data);
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    private function UnInstallHBlock(): void
    {
        if (CModule::IncludeModule("highloadblock"))
        {
            $hlBlockName = 'b_ilizium_chat_info';
            $hlBlock = HighloadBlockTable::getList([
                'filter' => ['TABLE_NAME' => $hlBlockName]
            ])->fetch();

            if ($hlBlock)
            {
                $hlBlockId = $hlBlock["ID"];

                // Удаляем все поля хайлоуд-блока
                $rsUserFields = CUserTypeEntity::GetList([], ['ENTITY_ID' => "HLBLOCK_" . $hlBlockId]);
                while ($field = $rsUserFields->Fetch())
                {
                    $obUserField = new CUserTypeEntity();
                    $obUserField->Delete($field["ID"]);
                }

                // Удаляем сам хайлоуд-блок
                HighloadBlockTable::delete($hlBlockId);
            }
        }
    }
}
