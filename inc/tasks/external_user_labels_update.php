<?php

function task_external_user_labels_update($task)
{
    if (function_exists('\externalUserLabels\update')) {
        \externalUserLabels\update();
    }
}
