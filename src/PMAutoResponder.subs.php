<?php

/**
 * @package PM Auto Respond
 * @version 1.0-beta.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */
class PMAutoResponder
{
    /**
     * @var array
     */
    private $rules = array();

    /**
     * @var array
     */
    private $groups = array();

    /**
     * @var int
     */
    private $current_member = 0;

    /**
     * @var string
     */
    private $subject = '';

    /**
     * @var string
     */
    private $body = '';

    /**
     * The database object
     *
     * @var database
     */
    protected $_db = null;

    /**
     * PMAutoResponder constructor.
     *
     * @param int $current_member
     * @param string $subject
     * @param string $body
     */
    public function __construct($current_member)
    {
        $this->current_member = $current_member;
        $this->_db = database();

        $this->fetchRules();
    }

    /**
     * @return int
     */
    public function getIdMemberSender()
    {
        return $this->current_member;
    }

    /**
     * @param int $current_member
     */
    public function setIdMemberSender($current_member)
    {
        $this->current_member = $current_member;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @param int|string $rule
     *
     * @return bool
     */
    public function ruleExists($rule)
    {
        return isset($this->rules[$rule]);
    }

    /**
     * @param int|string $rule
     *
     * @return array
     */
    public function getRule($rule)
    {
        return $this->rules[$rule];
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param int|string $group
     *
     * @return bool
     */
    public function groupExists($group)
    {
        $this->fetchGgoups();

        return isset($this->groups[$group]);
    }

    /**
     * Load up all the groups the current user hdas.
     *
     * @return array
     */
    public function fetchGgoups()
    {
        if (count($this->groups) != 0) {
            $request = $this->_db->query(
                '',
                '
                SELECT mg.id_group, mg.group_name, IFNULL(gm.id_member, 0) AS can_moderate, mg.hidden
                FROM {db_prefix}membergroups AS mg
                    LEFT JOIN {db_prefix}group_moderators AS gm ON (gm.id_group = mg.id_group AND gm.id_member = {int:current_member})
                WHERE mg.min_posts = {int:min_posts}
                    AND mg.id_group != {int:moderator_group}
                    AND mg.hidden = {int:not_hidden}
                ORDER BY mg.group_name',
                array(
                    'current_member' => $this->current_member,
                    'min_posts' => -1,
                    'moderator_group' => 3,
                    'not_hidden' => 0,
                )
            );
            while ($row = $this->_db->fetch_assoc($request)) {
                // Hide hidden groups!
                if ($row['hidden'] && !$row['can_moderate'] && !allowedTo('manage_membergroups')) {
                    continue;
                }

                $this->groups[$row['id_group']] = $row['group_name'];
            }
            $this->_db->free_result($request);
        }

        return $this->groups;
    }

    /**
     * Load up all the rules for the current user.
     */
    public function fetchRules()
    {
        if (count($this->rules) != 0) {
            $request = $this->_db->query(
                '',
                '
                SELECT
                    id_rule, rule_name, criteria, subject, body, is_or
                FROM {db_prefix}pm_ar_rules
                WHERE id_member = {int:current_member}',
                array(
                    'current_member' => $this->current_member,
                )
            );
            while ($row = $this->_db->fetch_assoc($request)) {
                $this->rules[$row['id_rule']] = array(
                    'id' => $row['id_rule'],
                    'name' => $row['rule_name'],
                    'criteria' => json_decode($row['criteria']),
                    'subject' => $row['subject'],
                    'body' => $row['body'],
                    'logic' => $row['is_or'] ? 'or' : 'and',
                );
            }

            $this->_db->free_result($request);
        }
    }

    public function applyRules()
    {
        global $user_info;

        foreach ($this->rules as $rule) {
            // Loop through all the criteria hoping to make a match.
            foreach ($rule['criteria'] as $criterium) {
                if (
                    ($criterium['t'] == 'mid' && $criterium['V'] == $this->current_member)
                    || ($criterium['t'] == 'gid' && in_array($criterium['V'], $user_info['groups']))
                    || ($criterium['t'] == 'bud' && in_array($criterium['V'], $user_info['buddies']))
                    || ($criterium['t'] == 'sub' && strpos($this->subject, $criterium['V']) !== false)
                    || ($criterium['t'] == 'msg' && strpos($this->body, $criterium['V']) !== false)
                ) {
                    $this->subject = $rule['subject'];
                    $this->body = $rule['body'];
                    // If we're adding and one criteria doesn't match then we stop!
                } elseif ($rule['logic'] == 'and') {
                    break;
                }
            }
        }
    }

    public function fetchMemId($name)
    {
        $request = $this->_db->query(
            '',
            '
            SELECT id_member
            FROM {db_prefix}members
            WHERE real_name = {string:member_name}
                OR member_name = {string:member_name}
                OR id_member = {string:member_name}',
            array(
                'member_name' => $name,
            )
        );
        if ($this->_db->num_rows($request) == 0) {
            continue;
        }
        list ($memID) = $this->_db->fetch_row($request);
        $this->_db->free_result($request);

        return $memID;
    }

    public function fetchMembers($ids)
    {
        $members = array();
        $request = $this->_db->query(
            '',
            '
            SELECT id_member, member_name
            FROM {db_prefix}members
            WHERE id_member IN ({array_int:member_list})',
            array(
                'member_list' => $ids,
            )
        );
        while ($row = $this->_db->fetch_row($request)) {
            $members[$ids[$row[0]]]['v'] = $row[1];
        }
        $this->_db->free_result($request);

        return $members;
    }

    public function insertRule(
        $rule_name,
        $criteria,
        $subject,
        $body,
        $is_or
    ) {
        $this->_db->insert(
            '',
            '{db_prefix}pm_ar_rules',
            array(
                'id_member' => 'int',
                'rule_name' => 'string',
                'criteria' => 'string',
                'subject' => 'string',
                'body' => 'string',
                'is_or' => 'int',
            ),
            array(
                $this->current_member,
                $rule_name,
                $criteria,
                $subject,
                $body,
                $is_or,
            ),
            array('id_rule')
        );
    }

    public function updateRule(
        $rule_id,
        $rule_name,
        $criteria,
        $subject,
        $body,
        $is_or
    ) {
        $this->_db->query(
            '',
            '
            UPDATE {db_prefix}pm_ar_rules
            SET rule_name = {string:rule_name}, criteria = {string:criteria},
            subject = {string:subject}, body = {string:body}, is_or = {int:is_or}
            WHERE id_rule = {int:id_rule}
                AND id_member = {int:current_member}',
            array(
                'current_member' => $this->current_member,
                'is_or' => $is_or,
                'id_rule' => $rule_id,
                'rule_name' => $rule_name,
                'criteria' => $criteria,
                'subject' => $subject,
                'body' => $body,
            )
        );
    }

    public function deleteRules($delete_list)
    {
        $this->_db->query(
            '',
            '
            DELETE FROM {db_prefix}pm_ar_rules
            WHERE id_rule IN ({array_int:delete_list})
                AND id_member = {int:current_member}',
            array(
                'current_member' => $this->current_member,
                'delete_list' => $delete_list,
            )
        );
    }
}
