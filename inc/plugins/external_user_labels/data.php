<?php

namespace externalUserLabels;

function getUserDataById(int $userId): ?array
{
    global $cache;

    return $cache->read('external_user_labels')['users'][$userId] ?? null;
}

function getLabelDataByName(string $name): ?array
{
    global $cache;

    return $cache->read('external_user_labels')['labels'][$name] ?? null;
}

function getLabelsData(array $names): ?array
{
    $data = [];

    foreach ($names as $name) {
        $labelData = externalUserLabels\getLabelData($name);

        if ($labelData !== null) {
            $data[$labelData['name']] = $labelData;
        }
    }
}

function update()
{
    global $cache;

    $labels = \externalUserLabels\getArrayFromJsonFile(\externalUserLabels\getSettingValue('labels_source_url'));
    $users = \externalUserLabels\getArrayFromJsonFile(\externalUserLabels\getSettingValue('users_source_url'));

    if ($labels !== null && $users !== null) {
        $cacheContent = $cache->read('external_user_labels');

        $cacheContent['labels'] = \externalUserLabels\getArrayWithColumnAsKey($labels, 'name');
        $cacheContent['users'] = \externalUserLabels\getArrayWithColumnAsKey($users, 'uid');

        $cache->update('external_user_labels', $cacheContent);
    }
}

function getArrayFromJsonFile($url): ?array
{
    $responseBody = \fetch_remote_file($url);

    if ($responseBody) {
        $decoded = json_decode($responseBody, true);

        if ($decoded !== null) {
            return $decoded;
        }
    }

    return null;
}
