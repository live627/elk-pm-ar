<?php

/**
 * @package PM Auto Respond
 * @version 1.0-beta.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */
class PMAutoResponder_Controller extends Action_Controller
{
    /**
     * @var PMAutoResponder
     */
    private $loader = '';

    public function action_index()
    {
        global $context, $txt, $scripturl;

        require_once(SUBSDIR.'/Action.class.php');
        $action = new Action('pm_ar');
        $subActions = array(
            'general' => array($this, 'actionGeneral', 'permission' => 'pm_ar'),
            'filters' => array($this, 'actionFilters', 'permission' => 'pm_ar'),
        );

        // db functions are here
        require_once(SUBSDIR.'/PMAutoResponder.subs.php');
        $this->loader = new PMAutoResponder(currentMemberID());

        // Default to sub action 'general'
        $subAction = $action->initialize($subActions, 'general');

        // Create the tabs for the template.
        $context[$context['profile_menu_name']]['tab_data'] = array(
            'title' => $txt['pm_ar_profile_area'],
            'description' => $txt['pm_ar_general_desc'],
            'icon' => 'profile_sm.gif',
            'tabs' => array(
                'general' => array(
                    'title' => $txt['pm_ar_general'],
                    'description' => $txt['pm_ar_general_desc'],
                ),
                'filters' => array(
                    'title' => $txt['pm_ar_filters'],
                    'description' => $txt['pm_ar_filters_desc'],
                ),
            ),
        );
        loadTemplate('PMAutoResponder');
        loadTemplate('ProfileOptions');

        // Calls a function based on the sub-action
        $action->dispatch($subAction);
    }

    function actionGeneral()
    {
        global $context, $txt;

        $enabled = !empty($_POST['pm_ar_enabled']);
        if (isset($_POST['pm_ar_subject'])) {
            $subject = $_POST['pm_ar_subject'];
        } else {
            $subject = '';
        }
        if (isset($_POST['pm_ar_body'])) {
            $body = $_POST['pm_ar_body'];
        } else {
            $body = '';
        }
        $options = array(
            'pm_ar_enabled' => (int)$enabled,
            'pm_ar_subject' => $subject,
            'pm_ar_body' => $body,
        );
        require_once(SUBSDIR.'/Themes.subs.php');
        $options = loadThemeOptionsInto(1, currentMemberID(), $options, array_keys($options));
        $context['pm_ar_body'] = $options['pm_ar_body'];

        $context['profile_fields'] = array(
            'pm_ar_enabled' => array(
                'label' => $txt['pm_ar_enabled'],
                'type' => 'check',
                'input_attr' => '',
                'value' => $options['pm_ar_enabled'],
            ),
            'pm_ar_subject' => array(
                'label' => $txt['pm_ar_subject'],
                'subtext' => $txt['pm_ar_subject_desc'],
                'type' => 'text',
                'input_attr' => 'style="width:90%"',
                'value' => $options['pm_ar_subject'],
                'is_error' => isset($context['modify_error']['pm_ar_subject']),
            ),
            'pm_ar_body' => array(
                'type' => 'callback',
                'callback_func' => 'pm_ar_body',
            ),
        );

        $context['sub_template'] = 'edit_options';
        $context['profile_header_text'] = $txt['pm_ar_profile_area'];
        $context['page_title'] = $txt['pm_ar_profile_area'];
        $context['page_desc'] = $txt['pm_ar_profile_area'];
        $context['submit_button_text'] = $txt['save'];
        //~ add_integration_function('integrate_profile_save', 'pm_ar_profile_save');
    }

    // List all rules, and allow adding/entering etc....
    function actionFilters()
    {
        global $txt, $context, $user_info, $scripturl;

        loadLanguage('PersonalMessage');
        loadLanguage('PMAutoResponder');

        // Editing a specific one?
        if (isset($_GET['add'])) {
            $context['in'] = isset($_GET['in']) && $this->loader->ruleExists($_GET['in']) ? (int)$_GET['in'] : 0;
            $context['sub_template'] = 'edit_options';

            require_once(SUBSDIR . '/Membergroups.subs.php');
            $context['groups'] = accessibleGroups();

            // Current rule information...
            if ($context['in']) {
                $context['rule'] = $this->loader->getRule($context['in']);
                $members = array();
                // Need to get member names!
                foreach ($context['rule']['criteria'] as $k => $criteria) {
                    if ($criteria['t'] == 'mid' && !empty($criteria['v'])) {
                        $members[(int)$criteria['v']] = $k;
                    }
                }
                if (!empty($members)) {
                    $context['rule']['criteria'] = $this->loader->fetchMembers(array_keys($members));
                }
            } else {
                $context['rule'] = array(
                    'id' => '',
                    'name' => '',
                    'criteria' => array(),
                    'subject' => '',
                    'message' => '',
                    'logic' => 'and',
                );
            }

            $context['profile_fields'] = array(
                'rule_name' => array(
                    'label' => $txt['pm_rule_name'],
                    'subtext' => $txt['pm_rule_name_desc'],
                    'type' => 'text',
                    'input_attr' => 'style="width:90%"',
                    'value' => empty($context['rule']['name']) ? $txt['pm_rule_name_default'] : $context['rule']['name'],
                ),
                'pm_ar_add_rule' => array(
                    'type' => 'callback',
                    'callback_func' => 'pm_ar_add_rule',
                ),
                'subject' => array(
                    'label' => $txt['pm_ar_subject'],
                    'subtext' => $txt['pm_ar_subject_desc'],
                    'type' => 'text',
                    'input_attr' => 'style="width:90%"',
                    'value' => isset($context['rule']['subject']) ? $context['rule']['subject'] : '',
                ),
                'body' => array(
                    'type' => 'callback',
                    'callback_func' => 'pm_ar_body2',
                ),
            );
            $context['profile_header_text'] = $txt['pm_ar_profile_area'];
            $context['page_desc'] = $txt['pm_ar_profile_area'];
            $context['submit_button_text'] = $txt['pm_rule_save'];
            $context['profile_custom_submit_url'] = $scripturl.'?action=profile;area='.$context['menu_item_selected'].';sa=filters;u='.$context['id_member'].';pmarsave';
        } // Saving?
        elseif (isset($_GET['pmarsave'])) {
            checkSession('post');
            validateToken($context['token_check']);
        } // Deleting?
        elseif (isset($_POST['delselected']) && !empty($_POST['remove'])) {
            checkSession('post');
            validateToken($context['token_check']);
            deleteRules(array_map('intval', $_POST['remove']));
            redirectexit('action=profile;area=pm_ar;sa=filters');
        } else {
            $listOptions = array(
                'id' => 'view_likes',
                'items_per_page' => 25,
                'no_items_label' => $txt['pm_rules_none'],
                'base_href' => $scripturl . '?action=profile;area=pm_ar;sa=filters;add;u=' . $context['id_member'],
                'get_items' => array(
                    'function' => function ($start, $length) {
                        return array_slice($this->loader->getRules(), $start, $length);
                    },
                ),
                'get_count' => array(
                    'function' => function () {
                        return count($this->loader->getRules());
                    },
                ),
                'columns' => array(
                    'name' => array(
                        'header' => array(
                            'value' => $txt['pm_rule_title'],
                        ),
                        'data' => array(
                            'db' => 'name',
                        ),
                    ),
                    'actions' => array(
                        'data' => array(
                            'sprintf' => array(
                                'format' => '<a href="' . $scripturl . '?action=profile;area=pm_ar;sa=filters;add;u=' . $context['id_member'] . ';in=%1$d">' . $txt['modify'] . '</a>',
                                'params' => array(
                                    'id' => false,
                                ),
                            ),
                            'class' => 'centertext',
                        ),
                    ),
                    'check' => array(
                        'header' => array(
                            'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
                            'class' => 'centertext',
                        ),
                        'data' => array(
                            'sprintf' => array(
                                'format' => '<input type="checkbox" name="remove[]" value="%1$d" class="input_check" />',
                                'params' => array(
                                    'id' => false,
                                ),
                            ),
                            'class' => 'centertext',
                        ),
                    ),
                ),
                'form' => array(
                    'href' => $scripturl . '?action=profile;area=pm_ar;sa=filters;u=' . $context['id_member'],
                ),
                'additional_rows' => array(
                    array(
                        'position' => 'below_table_data',
                        'value' => '
                            <input type="submit" name="delselected" value="' . $txt['pm_ar_remove_selected'] . '" onclick="return confirm(\'' . $txt['pm_ar_remove_selected_confirm'] . '\');" class="button_submit" />
                            <input type="submit" name="removeAll" value="' . $txt['pm_ar_remove_all'] . '" onclick="return confirm(\'' . $txt['pm_ar_remove_all_confirm'] . '\');" class="button_submit" />
                            <a class="linkbutton" href="' . $scripturl . '?action=profile;area=pm_ar;sa=filters;add;in=0;u=' . $context['id_member'] . '">' . $txt['pm_add_rule'] . '</a>',
                        'class' => 'righttext',
                    ),
                ),
            );
            $context['sub_template'] = 'show_list';
            $context['default_list'] = 'view_likes';
            require_once(SUBSDIR . '/GenericList.class.php');
            createList($listOptions);
        }
    }

    function actionSave($post_errors)
    {
        global $context, $txt;

        $enabled = !empty($_POST['pm_ar_enabled']);
        $subject = Util::htmlspecialchars(trim($_POST['pm_ar_subject']);
        $body = Util::htmlspecialchars(trim($_POST['pm_ar_body']);

        if ($enabled) {
            if (empty($subject)) {
                $post_errors[] = 'pm_ar_subject';
            }
            if (empty($body)) {
                $post_errors[] = 'pm_ar_body';
            }
            $context['custom_error_title'] = $txt['profile_errors_pm_ar'];
        }
        if (empty($post_errors)) {
            $_POST['default_options'] = array(
                'pm_ar_enabled' => (int)$enabled,
                'pm_ar_subject' => $subject,
                'pm_ar_body' => $body,
            );

            makeThemeChanges(currentMemberID(), 1);
        }

        return $post_errors;
    }

    function actionSaveFilter($post_errors)
    {
        global $context, $txt;

        $rule_id = isset($_GET['in']) && $this->loader->ruleExists($_GET['in']) ? (int)$_GET['in'] : 0;

        // Name is easy!
        $rule_name = Util::htmlspecialchars(trim($_POST['rule_name']));
        if (empty($rule_name)) {
            $post_errors[] = 'pm_rule_no_name';
        }

        // Sanity check...
        if (empty($_POST['ruletype'])) {
            $post_errors[] = 'pm_rule_no_criteria';
        }

        // Let's do the criteria first - it's also hardest!
        $criteria = array();
        foreach ($_POST['ruletype'] as $ind => $type) {
            // Check everything is here...
            if ($type == 'gid' && (!isset($_POST['ruledefgroup'][$ind])
                || !$this->loader->groupExists($_POST['ruledefgroup'][$ind]))) {
                continue;
            } elseif ($type != 'bud' && !isset($_POST['ruledef'][$ind])) {
                continue;
            }

            // Members need to be found.
            if ($type == 'mid') {
                $criteria[] = array('t' => 'mid', 'v' => $this->loader->fetchMemId(trim($_POST['ruledef'][$ind])));
            } elseif ($type == 'bud') {
                $criteria[] = array('t' => 'bud', 'v' => 1);
            } elseif ($type == 'gid') {
                $criteria[] = array('t' => 'gid', 'v' => (int)$_POST['ruledefgroup'][$ind]);
            } elseif (in_array($type, array('sub', 'msg')) && trim($_POST['ruledef'][$ind]) != '') {
                $criteria[] = array('t' => $type, 'v' => Util::htmlspecialchars(trim($_POST['ruledef'][$ind])));
            }
        }
        $is_or = $_POST['rule_logic'] == 'or' ? 1 : 0;

        if (empty($criteria)) {
            $post_errors[] = 'pm_rule_no_criteria';
        }

        // What are we storing?
        $criteria = json_encode($criteria);
        $subject = Util::htmlspecialchars(trim($_POST['subject']));
        $body = Util::htmlspecialchars(trim($_POST['body']));

        // Create the rule?
        if (empty($post_errors)) {
            if (empty($rule_id)) {
                $this->loader->insertRule(
                    $rule_name,
                    $criteria,
                    $subject,
                    $body,
                    $is_or
                );
            } else {
                $this->loader->updateRule(
                    $rule_id,
                    $rule_name,
                    $criteria,
                    $subject,
                    $body,
                    $is_or
                );
            }

            redirectexit('action=profile;area=pm_ar;sa=filters');
        }

        return $post_errors;
    }
}
