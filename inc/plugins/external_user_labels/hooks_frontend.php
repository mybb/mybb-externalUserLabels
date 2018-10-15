<?php

namespace externalUserLabels\Hooks;

// core
function misc_start()
{
    global $mybb;

    if ($mybb->input['action'] == 'external_user_labels_webhook_update' && isset($_SERVER['HTTP_X_GITHUB_EVENT'], $_SERVER['HTTP_X_HUB_SIGNATURE'])) {
        $input = file_get_contents('php://input');
        $bodyData = json_decode($input, true);

        if ($bodyData !== null) {
            $inputHmac = hash_hmac('sha1', $input, \externalUserLabels\getSettingValue('webhook_secret'));

            if ($_SERVER['HTTP_X_HUB_SIGNATURE'] === 'sha1=' . $inputHmac) {
                if ($_SERVER['HTTP_X_GITHUB_EVENT'] != 'ping') {
                    \externalUserLabels\update();
                }
            }
        }

        exit;
    }
}

// rendering
function member_profile_end()
{
    global $memprofile, $groupimage;

    if (\externalUserLabels\hasLabelGroup($memprofile)) {
        $groupimage = \externalUserLabels\getRenderedUserLabelsByUserId($memprofile['uid']);
    }
}

function postbit($post)
{
    if (\externalUserLabels\hasLabelGroup($post)) {
        $post['groupimage'] = \externalUserLabels\getRenderedUserLabelsByUserId($post['uid']);
    }

    return $post;
}

function postbit_announcement($post)
{
    return postbit($post);
}

function postbit_pm($post)
{
    return postbit($post);
}

function postbit_prev($post)
{
    return postbit($post);
}
