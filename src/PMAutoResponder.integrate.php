<?php

/**
 * @package PM Auto Respond
 * @version 1.0-beta.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

function pm_ar_personal_message($recipients, $from_name, $subject, $message)
{
    global $context, $user_info;

    if (isset($context['pm_ar'])) {
        return;
    }

    $db = database();
    require_once(SUBSDIR.'/PMAutoResponder.subs.php');
    $loader = new PMAutoResponder($user_info['id']);
    loadLanguage('PMAutoResponder');
    if (!empty($recipients['to']) || !empty($recipients['bcc'])) {
        $auto_recipients = array_merge($recipients['to'], $recipients['bcc']);
        $request = $db->query(
            '',
            '
            SELECT m.id_member, m.real_name, m.member_name, t.value, t.variable
            FROM {db_prefix}members AS m
                INNER JOIN {db_prefix}themes AS t ON (m.id_member = t.id_member AND t.variable LIKE {string:auto_recipients_var})
            WHERE m.id_member IN ({array_int:auto_recipients})
                AND t.value != ""',
            array(
                'auto_recipients' => $auto_recipients,
                'auto_recipients_var' => '%pm_ar_%',
            )
        );
        $members = array();
        $theme_members = array();
        while ($row = $db->fetch_assoc($request)) {
            $members[$row['id_member']] = array(
                'name' => $row['real_name'],
                'username' => $row['member_name'],
            );
            $theme_members[$row['id_member']][$row['variable']] = $row['value'];
        }

        foreach ($members as $id_member => $member) {
            if (isset($theme_members[$id_member]['pm_ar_enabled'], $theme_members[$id_member]['pm_ar_subject'], $theme_members[$id_member]['pm_ar_body'])) {
                $context['pm_ar'] = true;
                $loader->setIdMemberSender($id_member);
                $loader->setSubject($theme_members[$id_member]['pm_ar_subject']);
                $loader->setBody($theme_members[$id_member]['pm_ar_body']);
                $loader->applyRules();
                sendpm(
                    array(
                        'to' => array($user_info['id']),
                        'bcc' => array(),
                    ),
                    $loader->getSubject(),
                    $loader->getBody(),
                    false,
                    array(
                        'id' => $id_member,
                        'name' => $member['name'],
                        'username' => $member['username'],
                    )
                );
            }
        }
    }
}

function pm_ar_profile_areas(&$profile_areas)
{
    global $txt;

    if (!allowedTo('pm_ar')) {
        return $profile_areas;
    }

    loadLanguage('PMAutoResponder');
    $profile_areas['edit_profile']['areas']['pm_ar'] = array(
        'label' => $txt['pm_ar_profile_area'],
        'file' => 'PMAutoResponder.controller.php',
        'controller' => 'PMAutoResponder_Controller',
        'function' => 'action_index',
        'enabled' => allowedTo(array('profile_extra_own', 'profile_extra_any')),
        'sc' => 'post',
        'token' => 'pm-ar-%u',
        'permission' => array(
            'own' => array('profile_extra_own'),
            'any' => array('profile_extra_any'),
        ),
        'subsections' => array(
            'general' => array($txt['pm_ar_general']),
            'filters' => array($txt['pm_ar_filters']),
        ),
    );
}

function pm_ar_load_permissions(
    &$permissionGroups,
    &$permissionList,
    &$leftPermissionGroups,
    &$hiddenPermissions,
    &$relabelPermissions
) {
    global $context;

    loadLanguage('PMAutoResponder');
    $permissionList['membergroup'] += array(
        'pm_ar' => array(false, 'pm', 'use_pm_system'),
    );

    $context['non_guest_permissions'] = array_unshift(
        $context['non_guest_permissions'],
        'pm_ar'
    );
}

function pm_ar_profile_save(&$profile_vars, &$post_errors, $memID)
{
    global $context, $txt;

    if ($context['menu_item_selected'] == 'pm_ar') {
        $subAction = isset($_GET['sa']) ? $_GET['sa'] : 'actionSave';

        switch ($subAction) {
            case 'filters':
                $subAction = 'actionSaveFilter';
                break;
            default:
                $subAction = 'actionSave';
                break;
        }

        require_once(CONTROLLERDIR.'/PMAutoResponder.controller.php');
        $controller = new PMAutoResponder_Controller();
        $post_errors = $controller->{$subAction}($post_errors);
    }
}
