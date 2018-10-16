<?php
/**
 * Copyright (c) 2018, Tomasz 'Devilshakerz' Mlynski [devilshakerz.com] for MyBB Group [mybb.com]
 *
 * Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby
 * granted, provided that the above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN
 * AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 */

// common modules
require MYBB_ROOT . 'inc/plugins/external_user_labels/core.php';
require MYBB_ROOT . 'inc/plugins/external_user_labels/data.php';

// hook files
require MYBB_ROOT . 'inc/plugins/external_user_labels/hooks_frontend.php';

// hooks
\externalUserLabels\addHooksNamespace('externalUserLabels\Hooks');

function external_user_labels_info()
{
    return [
        'name'          => 'External User Labels',
        'description'   => 'Manages user labels basing on external listing for select usergroups.',
        'website'       => 'https://mybb.com/',
        'author'        => 'MyBB Group',
        'authorsite'    => 'https://mybb.com/',
        'version'       => '1.0',
        'codename'      => 'external_user_labels',
        'compatibility' => '18*',
    ];
}

function external_user_labels_install()
{
    global $db, $cache;


    // datacache
    $cache->update('external_user_labels', [
        'labels' => null,
        'users' => null,
        'version' => external_user_labels_info()['version'],
    ]);


    // tasks
    $new_task = [
        'title'       => 'External User Labels: Data synchronization',
        'description' => 'Updates data stored by External User Labels.',
        'file'        => 'external_user_labels_update',
        'minute'      => '0',
        'hour'        => '0',
        'day'         => '*',
        'month'       => '*',
        'weekday'     => '*',
        'enabled'     => '0',
        'logging'     => '0',
    ];

    require_once MYBB_ROOT . '/inc/functions_task.php';
    $new_task['nextrun'] = fetch_next_run($new_task);
    $db->insert_query('tasks', $new_task);
    $cache->update_tasks();
}

function external_user_labels_uninstall()
{
    global $PL, $db, $cache;

    external_user_labels_admin_load_pluginlibrary();

    // settings
    $PL->settings_delete('external_user_labels', true);

    // datacache
    $cache->delete('external_user_labels');

    // tasks
    $db->delete_query('tasks', "file='external_user_labels_update'");
    $cache->update_tasks();
}

function external_user_labels_is_installed()
{
    global $db;

    // manual check to avoid caching issues
    $query = $db->simple_select('settinggroups', 'gid', "name='external_user_labels'");

    return (bool)$db->num_rows($query);
}

function external_user_labels_activate()
{
    global $PL;

    external_user_labels_admin_load_pluginlibrary();

    // settings
    $PL->settings(
        'external_user_labels',
        'External User Labels',
        'Settings for External User Labels.',
        [
            'user_groups' => [
                'title'       => 'Label user groups',
                'description' => 'Select user groups that will have the labels assigned.',
                'optionscode' => 'groupselect',
                'value'       => '',
            ],
            'labels_source_url' => [
                'title'       => 'Labels data URL',
                'description' => 'Address to the JSON-encoded labels data file with an array of labels containing a <code>name</code> field.',
                'optionscode' => 'text',
                'value'       => '',
            ],
            'users_source_url' => [
                'title'       => 'Users data URL',
                'description' => 'Address to the JSON-encoded users data file with an array of users containing a <code>uid</code> and <code>role_membership</code> (array of membership types, each containing role names) fields.',
                'optionscode' => 'text',
                'value'       => '',
            ],
            'webhook_secret' => [
                'title'       => 'Data synchronization webhook secret',
                'description' => 'HMAC secret for validation of incoming webhooks to auto-update the labels data.',
                'optionscode' => 'text',
                'value'       => random_str(40),
            ],
            'legend_url' => [
                'title'       => 'Legend URL',
                'description' => 'An URL where the labels will point to. A <code>#role-</code> identifier with normalized rank name will be appended.',
                'optionscode' => 'text',
                'value'       => '',
            ],
        ]
    );

    // templates
    \externalUserLabels\replaceInTemplate('showteam_usergroup_user', '{$user[\'username\']}</strong></a>', '{$user[\'username\']}</strong></a> {$externalUserLabels}');
}

function external_user_labels_deactivate()
{
    // templates
    \externalUserLabels\replaceInTemplate('showteam_usergroup_user', ' {$externalUserLabels}', '');
}

// helpers
function external_user_labels_admin_load_pluginlibrary()
{
    if (!defined('PLUGINLIBRARY')) {
        define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');
    }

    if (!file_exists(PLUGINLIBRARY)) {

        flash_message('Add <a href="https://mods.mybb.com/view/pluginlibrary">PluginLibrary</a> in order to use the plugin.', 'error');

        admin_redirect('index.php?module=config-plugins');
    } elseif (!$PL) {
        require_once PLUGINLIBRARY;
    }
}
