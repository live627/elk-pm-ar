<?php

/**
 * @package PM Auto Respond
 * @version 1.0-beta.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

function template_profile_pm_ar_body()
{
    global $context, $txt;

    echo '
                        <dt>
                            <strong', !empty($context['modify_error']['pm_ar_body']) ? ' class="error"' : '', '>
                                ', $txt['pm_ar_body'], '</strong>
                            <br />
                            <span class="smalltext">', $txt['pm_ar_body_desc'], '</span>
                        </dt>
                        <dd>
                            <textarea id="pm_ar_body" name="pm_ar_body" cols="8" rows="40" style="width:90%; height: 300px;">', $context['pm_ar_body'], '</textarea>
                        </dd>';
}

function template_profile_pm_ar_body2()
{
    global $context, $txt;

    echo '
                        <dt>
                            <strong>', $txt['pm_ar_body'], '</strong>
                            <br />
                            <span class="smalltext">', $txt['pm_ar_body_desc'], '</span>
                        </dt>
                        <dd>
                            <textarea id="body" name="body" style="width:90%; height: 300px;">', isset($context['rule']['body']) ? $context['rule']['body'] : '', '</textarea>
                        </dd>';
}

// Manage rules.
// !!! TODO: Convert this to use the generic list.
function template_rules2()
{
    global $context, $settings, $options, $txt, $scripturl;

    echo '
    <form action="', $scripturl, '?action=profile;area=pm_ar;sa=filters;u=' . $context['id_member'] . '" method="post" accept-charset="', $context['character_set'], '" name="manRules" id="manrules">
        <div class="cat_bar">
            <h3 class="catbg">', $txt['pm_manage_rules'], '</h3>
        </div>
        <div class="description">
            ', $txt['pm_manage_rules_desc'], '
        </div>
        <table width="100%" class="table_grid">
        <thead>
            <tr class="catbg">
                <th class="lefttext first_th">
                    ', $txt['pm_rule_title'], '
                </th>
                <th width="4%" class="centertext last_th">';

    if (!empty($context['rules']))
        echo '
                    <input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />';

    echo '
                </th>
            </tr>
        </thead>
        <tbody>';

    if (empty($context['rules']))
        echo '
            <tr class="windowbg2">
                <td colspan="2" align="center">
                    ', $txt['pm_rules_none'], '
                </td>
            </tr>';

    $alternate = false;
    foreach ($context['rules'] as $rule)
    {
        echo '
            <tr class="', $alternate ? 'windowbg' : 'windowbg2', '">
                <td>
                    <a href="', $scripturl, '?action=profile;area=pm_ar;sa=filters;add;in=', $rule['id'], ';u=' . $context['id_member'] . '">', $rule['name'], '</a>
                </td>
                <td width="4%" align="center">
                    <input type="checkbox" name="delrule[', $rule['id'], ']" class="input_check" />
                </td>
            </tr>';
        $alternate = !$alternate;
    }

    echo '
        </tbody>
        </table>
        <div class="righttext">
            [<a class="linkbutton" href="', $scripturl, '?action=profile;area=pm_ar;sa=filters;add;in=0;u=' . $context['id_member'] . '">', $txt['pm_add_rule'], '</a>]';

    if (!empty($context['rules']))
        echo '
            <input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
            <input type="submit" name="delselected" value="', $txt['pm_delete_selected_rule'], '" onclick="return confirm(\'', $txt['pm_js_delete_rule_confirm'], '\');" class="button_submit smalltext" />';

    echo '
        </div>
    </form>';

}

// Template for adding/editing a rule.
function template_profile_pm_ar_add_rule()
{
    global $context, $settings, $options, $txt, $scripturl;

	echo '
	<script>
            var criteriaNum = 0;
            var actionNum = 0;
            var groups = [];';

    foreach ($context['groups'] as $id => $title)
        echo '
            groups[' . $id . '] = "' . addslashes($title) . '";';

    echo '
            function addCriteriaOption()
            {
                if (criteriaNum == 0)
                {
                    for (var i = 0; i < document.forms.creator.elements.length; i++)
                        if (document.forms.creator.elements[i].id.substr(0, 8) == "ruletype")
                            criteriaNum++;
                }
                criteriaNum++

                setOuterHTML(document.getElementById("criteriaAddHere"), \'<br /><select name="ruletype[\' + criteriaNum + \']" id="ruletype\' + criteriaNum + \'" onchange="updateRuleDef(\' + criteriaNum + \'); rebuildRuleDesc();"><option value="">' . addslashes($txt['pm_rule_criteria_pick']) . ':<\' + \'/option><option value="mid">' . addslashes($txt['pm_rule_mid']) . '<\' + \'/option><option value="gid">' . addslashes($txt['pm_rule_gid']) . '<\' + \'/option><option value="sub">' . addslashes($txt['pm_rule_sub']) . '<\' + \'/option><option value="msg">' . addslashes($txt['pm_rule_msg']) . '<\' + \'/option><option value="bud">' . addslashes($txt['pm_rule_bud']) . '<\' + \'/option><\' + \'/select>&nbsp;<span id="defdiv\' + criteriaNum + \'" style="display: none;"><input type="text" name="ruledef[\' + criteriaNum + \']" id="ruledef\' + criteriaNum + \'" onkeyup="rebuildRuleDesc();" value="" class="input_text" /><\' + \'/span><span id="defseldiv\' + criteriaNum + \'" style="display: none;"><select name="ruledefgroup[\' + criteriaNum + \']" id="ruledefgroup\' + criteriaNum + \'" onchange="rebuildRuleDesc();"><option value="">' . addslashes($txt['pm_rule_sel_group']) . '<\' + \'/option>';

    foreach ($context['groups'] as $id => $group)
        echo '<option value="' . $id . '">' . strtr($group, array("'" => "\'")) . '<\' + \'/option>';

    echo '<\' + \'/select><\' + \'/span><span id="criteriaAddHere"><\' + \'/span>\');
            }

            function updateRuleDef(optNum)
            {
                if (document.getElementById("ruletype" + optNum).value == "gid")
                {
                    document.getElementById("defdiv" + optNum).style.display = "none";
                    document.getElementById("defseldiv" + optNum).style.display = "";
                }
                else if (document.getElementById("ruletype" + optNum).value == "bud" || document.getElementById("ruletype" + optNum).value == "")
                {
                    document.getElementById("defdiv" + optNum).style.display = "none";
                    document.getElementById("defseldiv" + optNum).style.display = "none";
                }
                else
                {
                    document.getElementById("defdiv" + optNum).style.display = "";
                    document.getElementById("defseldiv" + optNum).style.display = "none";
                }
            }

            // Rebuild the rule description!
            function rebuildRuleDesc()
            {
                // Start with... nothing. D\'OH!
                var text = "";
                var joinText = "";
                var actionText = "";
                var hadBuddy = false;
                var foundCriteria = false;
                var foundAction = false;
                var curNum, curVal, curDef;

                for (var i = 0; i < document.forms.creator.elements.length; i++)
                {
                    if (document.forms.creator.elements[i].id.substr(0, 8) == "ruletype")
                    {
                        if (foundCriteria)
                            joinText = document.getElementById("logic").value == \'and\' ? ' . JavaScriptEscape(' ' . $txt['pm_readable_and'] . ' ') . ' : ' . JavaScriptEscape(' ' . $txt['pm_readable_or'] . ' ') . ';
                        else
                            joinText = \'\';
                        foundCriteria = true;

                        curNum = document.forms.creator.elements[i].id.match(/\d+/);
                        curVal = document.forms.creator.elements[i].value;
                        if (curVal == "gid")
                            curDef = document.getElementById("ruledefgroup" + curNum).value.php_htmlspecialchars();
                        else if (curVal != "bud")
                            curDef = document.getElementById("ruledef" + curNum).value.php_htmlspecialchars();
                        else
                            curDef = "";

                        // What type of test is this?
                        if (curVal == "mid" && curDef)
                            text += joinText + ' . JavaScriptEscape($txt['pm_readable_member']) . '.replace("{MEMBER}", curDef);
                        else if (curVal == "gid" && curDef && groups[curDef])
                            text += joinText + ' . JavaScriptEscape($txt['pm_readable_group']) . '.replace("{GROUP}", groups[curDef]);
                        else if (curVal == "sub" && curDef)
                            text += joinText + ' . JavaScriptEscape($txt['pm_readable_subject']) . '.replace("{SUBJECT}", curDef);
                        else if (curVal == "msg" && curDef)
                            text += joinText + ' . JavaScriptEscape($txt['pm_readable_body']) . '.replace("{BODY}", curDef);
                        else if (curVal == "bud" && !hadBuddy)
                        {
                            text += joinText + ' . JavaScriptEscape($txt['pm_readable_buddy']) . ';
                            hadBuddy = true;
                        }
                    }
                }

                // If still nothing make it default!
                if (text == "" || !foundCriteria)
                    text = "' . $txt['pm_rule_not_defined'] . '";
                else
                {
                    if (actionText != "")
                        text += ' . JavaScriptEscape(' ' . $txt['pm_readable_then'] . ' ') . ' + actionText;
                    text = ' . JavaScriptEscape($txt['pm_readable_start']) . ' + text + ' . JavaScriptEscape($txt['pm_readable_end']) . ';
                }

                // Set the actual HTML!
                //setInnerHTML(document.getElementById("ruletext"), text);
            }
		</script>';

    echo '
                <dt><strong>', $txt['pm_rule_criteria'], '</strong></dt><dd>';

    // Add a dummy criteria to allow expansion for none js users.
    $context['rule']['criteria'][] = array('t' => '', 'v' => '');

    // For each criteria print it out.
    $isFirst = true;
    foreach ($context['rule']['criteria'] as $k => $criteria)
    {
        if (!$isFirst && $criteria['t'] == '')
            echo '<div id="removeonjs1">';
        elseif (!$isFirst)
            echo '<br />';

        echo '
                    <select name="ruletype[', $k, ']" id="ruletype', $k, '" onchange="updateRuleDef(', $k, '); rebuildRuleDesc();">
                        <option value="">', $txt['pm_rule_criteria_pick'], ':</option>
                        <option value="mid" ', $criteria['t'] == 'mid' ? 'selected="selected"' : '', '>', $txt['pm_rule_mid'], ' or ID</option>
                        <option value="gid" ', $criteria['t'] == 'gid' ? 'selected="selected"' : '', '>', $txt['pm_rule_gid'], '</option>
                        <option value="sub" ', $criteria['t'] == 'sub' ? 'selected="selected"' : '', '>', $txt['pm_rule_sub'], '</option>
                        <option value="msg" ', $criteria['t'] == 'msg' ? 'selected="selected"' : '', '>', $txt['pm_rule_msg'], '</option>
                        <option value="bud" ', $criteria['t'] == 'bud' ? 'selected="selected"' : '', '>', $txt['pm_rule_bud'], '</option>
                    </select>
                    <span id="defdiv', $k, '" ', !in_array($criteria['t'], array('gid', 'bud')) ? '' : 'style="display: none;"', '>
                        <input type="text" name="ruledef[', $k, ']" id="ruledef', $k, '" onkeyup="rebuildRuleDesc();" value="', in_array($criteria['t'], array('mid', 'sub', 'msg')) ? $criteria['v'] : '', '" class="input_text" />
                    </span>
                    <span id="defseldiv', $k, '" ', $criteria['t'] == 'gid' ? '' : 'style="display: none;"', '>
                        <select name="ruledefgroup[', $k, ']" id="ruledefgroup', $k, '" onchange="rebuildRuleDesc();">
                            <option value="">', $txt['pm_rule_sel_group'], '</option>';

        foreach ($context['groups'] as $id => $group)
            echo '
                            <option value="', $id, '" ', $criteria['t'] == 'gid' && $criteria['v'] == $id ? 'selected="selected"' : '', '>', $group, '</option>';
        echo '
                        </select>
                    </span>';

        // If this is the dummy we add a means to hide for non js users.
        if ($isFirst)
            $isFirst = false;
        elseif ($criteria['t'] == '')
            echo '</div>';
    }

    echo '
                    <span id="criteriaAddHere"></span><br />
                    <a href="#" onclick="addCriteriaOption(); return false;" id="addonjs1" style="display: none;">(', $txt['pm_rule_criteria_add'], ')</a>
                    </dd>
                    <dt><strong>', $txt['pm_rule_logic'], ':</strong></dt><dd>
                    <select name="rule_logic" id="logic" onchange="rebuildRuleDesc();">
                        <option value="and" ', $context['rule']['logic'] == 'and' ? 'selected="selected"' : '', '>', $txt['pm_rule_logic_and'], '</option>
                        <option value="or" ', $context['rule']['logic'] == 'or' ? 'selected="selected"' : '', '>', $txt['pm_rule_logic_or'], '</option>
                    </select>
                </dd>';

	echo '
	<script>';

    foreach ($context['rule']['criteria'] as $k => $c)
        echo '
            updateRuleDef(' . $k . ');';

    echo '
            rebuildRuleDesc();';

    // If this isn't a new rule and we have JS enabled remove the JS compatibility stuff.
    if ($context['in'])
        echo '
            document.getElementById("removeonjs1").style.display = "none";';

    echo '
            document.getElementById("addonjs1").style.display = "";
		</script>';
}

