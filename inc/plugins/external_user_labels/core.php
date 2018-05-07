<?php

namespace externalUserLabels;

function hasLabelGroup(array $user): bool
{
    $userGroups = \externalUserLabels\getCsvSettingValues('user_groups');

    return (
        in_array(-1, $userGroups) ||
        count(\is_member($userGroups, $user)) != 0
    );
}

function getRenderedUserLabelsByUserId(int $userId): ?string
{
    $userLabelsByMembershipType = \externalUserLabels\getUserLabelsByMembershipType($userId);

    if ($userLabelsByMembershipType) {
        $userLabelsRendered = \externalUserLabels\getRenderedLabelsByMembershipTypes($userLabelsByMembershipType);
    } else {
        $userLabelsRendered = null;
    }

    return $userLabelsRendered;
}

function getUserLabelsByMembershipType(int $userId): array
{
    return \externalUserLabels\getUserDataById($userId)['role_memberships'] ?? null;
}

function getRenderedLabelsByMembershipTypes(array $labelsByMembershipTypes): string
{
    $renderedLabels = '';

    foreach ($labelsByMembershipTypes as $membershipType => $labels) {
        foreach ($labels as $label) {
            $labelData = \externalUserLabels\getLabelDataByName($label);
            $renderedLabels .= \externalUserLabels\getRenderedLabelByMembershipType($labelData, $membershipType) . ' ';
        }
    }

    return $renderedLabels;
}

function getRenderedLabelByMembershipType(array $labelData, string $membershipType): string
{
    $color = \htmlspecialchars_uni($labelData['color']);

    $attributes = 'class="team-role team-role--' . \htmlspecialchars_uni($membershipType) . '"';

    $output = '<p ' . $attributes . '>' . \htmlspecialchars_uni($labelData['name']) . '</p>';

    return $output;
}

// common
function addHooks(array $hooks, string $namespace = null)
{
    global $plugins;

    if ($namespace) {
        $prefix = $namespace . '\\';
    } else {
        $prefix = null;
    }

    foreach ($hooks as $hook) {
        $plugins->add_hook($hook, $prefix . $hook);
    }
}

function addHooksNamespace(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;
        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $plugins->add_hook($hookName, $namespace . '\\' . $hookName);
        }
    }
}

function getSettingValue(string $name): string
{
    global $mybb;
    return $mybb->settings['external_user_labels_' . $name];
}

function getCsvSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(explode(',', getSettingValue($name)));
    }

    return $values[$name];
}

function getDelimitedSettingValues(string $name): array
{
    static $values;

    if (!isset($values[$name])) {
        $values[$name] = array_filter(preg_split("/\\r\\n|\\r|\\n/", getSettingValue($name)));
    }

    return $values[$name];
}

function getArrayWithColumnAsKey(array $array, string $column): array
{
    return array_combine(array_column($array, $column), $array);
}
