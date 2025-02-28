<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CModule::IncludeModule("ilizium.test");

/**
 * Функция-агент для обновления данных по чатам из JSON-файла
 *
 * @return string
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function iliziumUpdateChatInfoAgent()
{
    $jsonFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ilizium.test/install/data/messages.json";
    if(file_exists($jsonFile))
    {
        $jsonData = file_get_contents($jsonFile);
        $messages = json_decode($jsonData, true);
        if(is_array($messages))
        {
            $chats = [];
            foreach($messages as $msg)
            {
                $chatId = $msg['chat_id'];
                if(!isset($chats[$chatId]))
                    $chats[$chatId] = [];
                $chats[$chatId][] = $msg;
            }

            // Получаем хайлоуд-блок по таблице
            $hlBlockName = 'b_ilizium_chat_info';
            $hlBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
                'filter' => ['TABLE_NAME' => $hlBlockName]
            ])->fetch();
            if($hlBlock)
            {
                $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlBlock);
                $entityDataClass = $entity->getDataClass();

                foreach($chats as $chatId => $msgs)
                {
                    // Сортируем по времени сообщения
                    usort($msgs, function($a, $b) {
                        return strtotime($a['time']) - strtotime($b['time']);
                    });
                    $first = reset($msgs);
                    $last = end($msgs);
                    $chatAuthor = $first['user_login'];

                    $data = [
                        "UF_CHAT_ID" => $chatId,
                        "UF_CHAT_AUTHOR" => $chatAuthor,
                        "UF_FIRST_MESSAGE" => $first['text'],
                        "UF_FIRST_MESSAGE_TIME" => new \Bitrix\Main\Type\DateTime($first['time']),
                        "UF_LAST_MESSAGE_AUTHOR" => $last['user_login'],
                        "UF_LAST_MESSAGE" => $last['text'],
                        "UF_LAST_MESSAGE_TIME" => new \Bitrix\Main\Type\DateTime($last['time']),
                    ];

                    // Если запись для данного чата уже существует, обновляем её
                    $res = $entityDataClass::getList([
                        'filter' => ['UF_CHAT_ID' => $chatId]
                    ])->fetch();

                    if($res)
                    {
                        $entityDataClass::update($res["ID"], $data);
                    }
                    else
                    {
                        $entityDataClass::add($data);
                    }
                }
            }
        }
    }

    // Возвращаем имя функции для повторного вызова агента
    return "iliziumUpdateChatInfoAgent();";
}
