<?php
/**
 * @package	AcyMailing for Joomla
 * @version	6.1.5
 * @author	acyba.com
 * @copyright	(C) 2009-2019 ACYBA S.A.R.L. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');
?><?php

class plgAcymSubscription extends acymPlugin
{
    var $listunsubscribe = false;
    var $lists = array();
    var $listsowner = array();
    var $listsinfo = array();
    var $campaigns = array();
    var $unsubscribeLink = false;

    function __construct()
    {
        parent::__construct();

        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;
    }

    function dynamicText()
    {
        $onePlugin = new stdClass();
        $onePlugin->name = acym_translation('ACYM_SUBSCRIPTION');
        $onePlugin->plugin = __CLASS__;
        $onePlugin->help = 'plugin-subscription';

        return $onePlugin;
    }

    function textPopup()
    {
        $others = array();
        $others['unsubscribe'] = array('name' => acym_translation('ACYM_UNSUBSCRIBE_LINK'), 'default' => 'ACYM_UNSUBSCRIBE');
        $others['confirm'] = array('name' => acym_translation('ACYM_CONFIRM_SUBSCRIPTION_LINK'), 'default' => 'ACYM_CONFIRM_SUBSCRIPTION');
        $others['subscribe'] = array('name' => acym_translation('ACYM_SUBSCRIBE_LINK'), 'default' => 'ACYM_SUBSCRIBE');

        ?>
		<script language="javascript" type="text/javascript">
            <!--
            var openLists = true;
            var selectedTag = '';

            function changeSubscriptionTag(tagName){
                selectedTag = tagName;
                defaultText = [];
                <?php
                foreach ($others as $tagname => $tag) {
                    echo 'defaultText["'.$tagname.'"] = "'.acym_translation($tag['default'], true).'";';
                }
                ?>
                jQuery('.acym__subscription__subscription').removeClass('selected_row');
                jQuery('#tr_' + tagName).addClass('selected_row');
                jQuery('#acym__popup__subscription__tagtext').val(defaultText[tagName]);
                setSubscriptionTag();
            }

            function setSubscriptionTag(){
                var tag = '{' + selectedTag;
                var lists = jQuery('#acym__popup__subscription__listids');

                if('subscribe' === selectedTag && lists.html() !== ''){
                    tag += "|lists:" + lists.html();
                }else{
                    jQuery('#acym__popup__plugin__subscription__lists__modal').hide();
                    jQuery('#select_lists_zone').hide();
                    lists.html('');
                }

                tag += '}' + jQuery('#acym__popup__subscription__tagtext').val() + '{/' + selectedTag + '}';
                setTag(tag, jQuery('#tr_' + selectedTag));
            }

            function displayLists(){
                jQuery.Modal();
                jQuery('#acym__popup__plugin__subscription__lists__modal').toggle();
                jQuery('#select_lists_zone').toggle();
                openLists = !openLists;
                jQuery('#acym__popup__subscription__change').on('change', function(){
                    var lists = JSON.parse(jQuery('#acym__modal__lists-selected').val());
                    jQuery('#acym__popup__subscription__listids').html(lists.join());
                    changeSubscriptionTag('subscribe');
                });
            }

            //-->
		</script>
        <?php

        $text = '<div class="acym__popup__listing text-center grid-x">
                    <h1 class="acym__popup__plugin__title cell">'.acym_translation('ACYM_SUBSCRIPTION').'</h1>
                    <div class="medium-1"></div>
                    <div class="medium-10 text-left">';
        $text .= acym_modal_pagination_lists('', '', '', '', '', false, 'acym__popup__subscription__change', '', false, 'style="display: none;" id="acym__popup__plugin__subscription__lists__modal"');
        $text .= '  </div>
                    <div class="medium-1"></div>
					<div class="grid-x medium-12 cell acym__listing__row text-left">
                        <div class="grid-x cell medium-5 small-12 acym__listing__title acym__listing__title__dynamics acym__subscription__subscription">
                            <label class="small-3" style="line-height: 40px;" for="acym__popup__subscription__tagtext">'.acym_translation('ACYM_TEXT').': </label>
                            <input class="small-9" type="text" name="tagtext" id="acym__popup__subscription__tagtext" onchange="setSubscriptionTag();">
                        </div>
                        <div class="medium-1"></div>
                        <div style="display: none;" id="select_lists_zone" class="grid-x cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">
                            <p class="shrink" id="acym__popup__subscription__text__list">'.acym_translation('ACYM_LISTS_SELECTED').'</button>
                            <p class="shrink" id="acym__popup__subscription__listids"></p>
                        </div>
                    </div>';
        $text .= '
					<div class="cell grid-x">';

        foreach ($others as $tagname => $tag) {
            $onclick = "changeSubscriptionTag('".$tagname."');";
            if ($tagname == 'subscribe') {
                $onclick .= 'displayLists();return false;';
            }
            $text .= '<div class="grid-x small-12 cell acym__listing__row acym__listing__row__popup text-left"  onclick="'.$onclick.'" id="tr_'.$tagname.'" ><div class="cell small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['name'].'</div></div>';
        }
        $text .= '</div></div>';

        $others = array();
        $others['name'] = acym_translation('ACYM_LIST_NAME');
        $others['names'] = acym_translation('ACYM_LIST_NAMES');
        $others['id'] = acym_translation('ACYM_LIST_ID', true);

        $text .= '<div class="acym__popup__listing text-center grid-x">
					<h1 class="acym__popup__plugin__title cell">'.acym_translation('ACYM_LIST').'</h1>
					<div class="cell grid-x">';

        foreach ($others as $tagname => $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__listing__row acym__listing__row__popup text-left" onclick="setTag(\'{list:'.$tagname.'}\', jQuery(this));" id="tr_'.$tagname.'" >
                        <div class="cell medium-12 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag.'</div>
                      </div>';
        }

        $text .= '</div></div>';

        $text .= '<div class="acym__popup__listing text-center grid-x">
					<span class="acym__popup__plugin__title cell">'.acym_translation('ACYM_CAMPAIGN').'</span>
					<div class="cell grid-x">';
        $othersMail = array('campaignid', 'subject');

        foreach ($othersMail as $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__listing__row acym__listing__row__popup text-left" onclick="setTag(\'{mail:'.$tag.'}\', jQuery(this));" id="tr_'.$tag.'" >
                        <div class="cell medium-12 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag.'</div>
                      </div>';
        }
        $text .= '</div></div>';

        echo $text;
    }

    function replaceUserInformation(&$email, &$user, $send = true)
    {
        $this->_replacelisttags($email, $user, $send);

        if (!$this->unsubscribeLink || $this->listunsubscribe || !method_exists($email, 'addCustomHeader')) {
            return;
        }

        $lang = empty($email->lang) ? '' : '&lang='.$email->lang;
        $myLink = acym_frontendLink('frontusers&subid='.intval($user->id).'&task=unsubscribe&id='.$email->id.'&key='.urlencode($user->key).$lang.'&'.acym_noTemplate());

        $this->listunsubscribe = true;
        if (!empty($email->replyemail)) {
            $mailto = $email->replyemail;
        }
        if (empty($mailto)) {
            $config = acym_config();
            $mailto = $config->get('replyto_email');
        }
        $email->addCustomHeader('List-Unsubscribe: <'.$myLink.'>, <mailto:'.$mailto.'?subject=unsubscribe_user_'.$user->id.'&body=Please%20unsubscribe%20user%20ID%20'.$user->id.'>');
    }

    function replaceContent(&$email, $send = true)
    {
        $this->_replacesubscriptiontags($email);
        $this->_replacemailtags($email);
    }

    private function _replacemailtags(&$email)
    {
        $result = $this->acympluginHelper->extractTags($email, 'mail');
        $tags = array();

        foreach ($result as $key => $oneTag) {
            if (isset($tags[$key])) {
                continue;
            }

            $field = $oneTag->id;
            if (!empty($email) && !empty($email->$field)) {
                $text = $email->$field;
                $this->acympluginHelper->formatString($text, $oneTag);
                $tags[$key] = $text;
            } else {
                $tags[$key] = $oneTag->default;
            }
        }

        $this->acympluginHelper->replaceTags($email, $tags);
    }

    private function _replacelisttags(&$email, &$user, $send)
    {
        $tags = $this->acympluginHelper->extractTags($email, 'list');
        if (empty($tags)) {
            return;
        }

        $replaceTags = array();
        foreach ($tags as $oneTag => $parameter) {
            $method = '_list'.trim(strtolower($parameter->id));

            if (method_exists($this, $method)) {
                $replaceTags[$oneTag] = $this->$method($email, $user, $parameter);
            } else {
                $replaceTags[$oneTag] = 'Method not found: '.$method;
            }
        }

        $this->acympluginHelper->replaceTags($email, $replaceTags, true);
    }

    private function _getattachedlistid($email, $subid)
    {
        $mailid = $email->id;
        $type = strtolower($email->type);

        if (isset($this->lists[$mailid][$subid])) {
            return $this->lists[$mailid][$subid];
        }

        $mailClass = acym_get('class.mail');
        $mailLists = array_keys($mailClass->getAllListsByMailId($mailid));
        $userLists = [];

        if (!empty($subid)) {
            $userClass = acym_get('class.user');
            $userLists = $userClass->getUserSubscriptionById($subid);

            $listid = null;
            foreach ($userLists as $id => $oneList) {
                if ($oneList->status == 1 && in_array($id, $mailLists)) {
                    $this->lists[$mailid][$subid] = $id;

                    return $this->lists[$mailid][$subid];
                }
            }

            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        if (!empty($mailLists)) {
            $this->lists[$mailid][$subid] = array_shift($mailLists);

            return $this->lists[$mailid][$subid];
        }

        if ($type == 'welcome' && !empty($subid)) {
            $listid = acym_loadResult('SELECT list.id FROM #__acym_list AS list JOIN #__acym_user_has_list AS userlist ON list.id = userlist.list_id WHERE list.welcome_id = '.intval($mailid).' AND userlist.user_id = '.intval($subid).' ORDER BY b.subscription_date DESC LIMIT 1');
            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        if ($type == 'unsubscribe' && !empty($subid)) {
            $listid = acym_loadResult('SELECT list.id FROM #__acym_list AS list JOIN #__acym_user_has_list AS userlist ON list.id = userlist.list_id WHERE list.unsubscribe_id = '.intval($mailid).' AND userlist.user_id = '.intval($subid).' ORDER BY b.unsubscribe_date DESC LIMIT 1');
            if (!empty($listid)) {
                $this->lists[$mailid][$subid] = $listid;

                return $listid;
            }
        }

        if (!empty($userLists)) {
            $listIds = array_keys($userLists);
            $this->lists[$mailid][$subid] = array_shift($listIds);

            return $this->lists[$mailid][$subid];
        }

        return 0;
    }

    private function _listnames(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) return '';

        $userClass = acym_get('class.user');
        $usersubscription = $userClass->getUserSubscriptionById($user->id);
        $lists = array();
        if (!empty($usersubscription)) {
            foreach ($usersubscription as $onesub) {
                if ($onesub->status < 1 || empty($onesub->active)) {
                    continue;
                }
                $lists[] = $onesub->name;
            }
        }

        return implode(isset($parameter->separator) ? $parameter->separator : ', ', $lists);
    }

    private function _listowner(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getattachedlistid($email, $user->subid);
        if (empty($listid)) {
            return "";
        }

        if (!isset($this->listsowner[$listid])) {
            $this->listsowner[$listid] = acym_loadObject('SELECT user.* FROM #__acym_list AS list JOIN '.$this->cmsUserVars->table.' AS user ON user.'.$this->cmsUserVars->id.' = list.cms_user_id WHERE list.id = '.intval($listid));
        }

        if (!in_array($parameter->field, array($this->cmsUserVars->username, $this->cmsUserVars->name, $this->cmsUserVars->email))) {
            return 'Field not found : '.$parameter->field;
        }

        return @$this->listsowner[$listid]->{$this->cmsUserVars->{$parameter->field}};
    }

    private function _loadlist($listid)
    {
        if (isset($this->listsinfo[$listid])) {
            return;
        }

        $listClass = acym_get('class.list');
        $this->listsinfo[$listid] = $listClass->getOneById(intval($listid));
    }

    private function _listname(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getattachedlistid($email, $user->id);
        if (empty($listid)) {
            return "";
        }

        $this->_loadlist($listid);

        return @$this->listsinfo[$listid]->name;
    }

    private function _listid(&$email, &$user, &$parameter)
    {
        if (empty($user->id)) {
            return '';
        }
        $listid = $this->_getattachedlistid($email, $user->id);
        if (empty($listid)) {
            return "";
        }

        return $listid;
    }

    private function _replacesubscriptiontags(&$email)
    {
        $match = '#(?:{|%7B)(confirm[^}]*|unsubscribe(?:\|[^}]*)?|subscribe[^}]*)(?:}|%7D)(.*)(?:{|%7B)/(confirm|unsubscribe|subscribe)(?:}|%7D)#Uis';
        $variables = array('subject', 'body');
        $found = false;
        $results = array();
        foreach ($variables as $var) {
            if (empty($email->$var)) {
                continue;
            }
            $found = preg_match_all($match, $email->$var, $results[$var]) || $found;
            if (empty($results[$var][0])) {
                unset($results[$var]);
            }
        }

        if (!$found) {
            return;
        }

        $tags = array();
        $this->listunsubscribe = false;
        foreach ($results as $var => $allresults) {
            foreach ($allresults[0] as $i => $oneTag) {
                if (isset($tags[$oneTag])) {
                    continue;
                }
                $tags[$oneTag] = $this->replaceSubscriptionTag($allresults, $i, $email);
            }
        }

        $this->acympluginHelper->replaceTags($email, $tags);
    }

    function replaceSubscriptionTag(&$allresults, $i, &$email)
    {
        $config = acym_config();
        $lang = empty($email->lang) ? '' : '&lang='.$email->lang;

        $parameters = $this->acympluginHelper->extractTag($allresults[1][$i]);

        if ($parameters->id == 'confirm') {
            $myLink = acym_frontendLink('frontusers&task=confirm&id={subtag:id}&key={subtag:key|urlencode}'.$lang);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a target="_blank" href="'.$myLink.'">'.$allresults[2][$i].'</a>';
        } elseif ($parameters->id == 'subscribe') {
            if (empty($parameters->lists)) {
                return 'You must select at least one list';
            }
            $lists = explode(',', $parameters->lists);
            acym_arrayToInteger($lists);
            $captchaKey = $config->get('captcha_enabled') ? '&seckey='.$config->get('security_key', '') : '';
            $myLink = acym_frontendLink('frontusers&task=subscribe&hiddenlists='.implode(',', $lists).'&user[email]={subtag:email|urlencode}'.$lang.$captchaKey);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_subscribe">'.$allresults[2][$i].'</span></a>';
        } else {
            $this->unsubscribeLink = true;

            $myLink = acym_frontendLink('frontusers&task=unsubscribe&id={subtag:id}&key={subtag:key|urlencode}&'.acym_noTemplate().$lang.'&mail_id='.$email->id);
            if (empty($allresults[2][$i])) {
                return $myLink;
            }

            return '<a style="text-decoration:none;" target="_blank" href="'.$myLink.'"><span class="acym_unsubscribe">'.$allresults[2][$i].'</span></a>';
        }
    }

    function onAcymDeclareConditions(&$conditions)
    {
        $listClass = acym_get('class.list');
        $list = array(
            'type' => array(
                'sub' => acym_translation('ACYM_SUBSCRIBED'),
                'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
                'notsub' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
            ),
            'lists' => $listClass->getAllForSelect(),
            'date' => array(
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'unsubscribe_date' => acym_translation('ACYM_UNSUBSCRIPTION_DATE'),
            ),
        );

        $conditions['user']['acy_list'] = new stdClass();
        $conditions['user']['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $conditions['user']['acy_list']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select($list['type'], 'acym_condition[conditions][__numor__][__numand__][acy_list][action]', null, 'class="intext_select_automation acym__select"');
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select($list['lists'], 'acym_condition[conditions][__numor__][__numand__][acy_list][list]', null, 'class="intext_select_automation acym__select"');
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-min]');
        $conditions['user']['acy_list']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation">';
        $conditions['user']['acy_list']->option .= acym_select($list['date'], 'acym_condition[conditions][__numor__][__numand__][acy_list][date-type]', null, 'class="intext_select_automation acym__select cell"');
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-max]');

        $conditions['classic']['acy_list_all'] = new stdClass();
        $conditions['classic']['acy_list_all']->name = acym_translation('ACYM_NUMBER_USERS_LIST');
        $conditions['classic']['acy_list_all']->option = '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_THERE_IS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(array('>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN'), '=' => acym_translation('ACYM_EXACTLY')), 'acym_condition[conditions][__numor__][__numand__][acy_list_all][operator]', null, 'class="intext_select_automation acym__select"');
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<input type="number" min="0" class="intext_input_automation cell" name="acym_condition[conditions][__numor__][__numand__][acy_list_all][number]">';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_ACYMAILING_USERS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell grid-x grid-margin-x margin-left-0" style="margin-bottom: 0"><div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select($list['type'], 'acym_condition[conditions][__numor__][__numand__][acy_list_all][action]', null, 'class="intext_select_automation acym__select"');
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select($list['lists'], 'acym_condition[conditions][__numor__][__numand__][acy_list_all][list]', null, 'class="intext_select_automation acym__select"');
        $conditions['classic']['acy_list_all']->option .= '</div></div>';
        $conditions['classic']['acy_list_all']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-min]');
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation">';
        $conditions['classic']['acy_list_all']->option .= acym_select($list['date'], 'acym_condition[conditions][__numor__][__numand__][acy_list_all][date-type]', null, 'class="intext_select_automation acym__select cell"');
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-max]');
    }

    function onAcymDeclareFilters(&$filters)
    {
        $listClass = acym_get('class.list');
        $list = array(
            'type' => array(
                'sub' => acym_translation('ACYM_SUBSCRIBED'),
                'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
                'notsub' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
            ),
            'lists' => $listClass->getAllForSelect(),
            'date' => array(
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'unsubscribe_date' => acym_translation('ACYM_UNSUBSCRIPTION_DATE'),
            ),
        );

        $filters['acy_list'] = new stdClass();
        $filters['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $filters['acy_list']->option = '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select($list['type'], 'acym_action[filters][__numor__][__numand__][acy_list][action]', null, 'class="intext_select_automation acym__select"');
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select($list['lists'], 'acym_action[filters][__numor__][__numand__][acy_list][list]', null, 'class="intext_select_automation acym__select"');
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-min]');
        $filters['acy_list']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= '<div class="intext_select_automation">';
        $filters['acy_list']->option .= acym_select($list['date'], 'acym_action[filters][__numor__][__numand__][acy_list][date-type]', null, 'class="intext_select_automation acym__select cell"');
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-max]');
        $filters['acy_list']->option .= '</div>';
    }

    function onAcymDeclareActions(&$actions)
    {
        $listClass = acym_get('class.list');

        $listActions = array(
            'sub' => acym_translation('ACYM_SUBSCRIBE_USERS_TO'),
            'remove' => acym_translation('ACYM_REMOVE_USERS_FROM'),
            'unsub' => acym_translation('ACYM_UNSUBSCRIBE_USERS_TO'),
        );
        $lists = $listClass->getAllForSelect();

        $actions['acy_list'] = new stdClass();
        $actions['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $actions['acy_list']->option = '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select($listActions, 'acym_action[actions][__and__][acy_list][list_actions]', null, 'class="acym__select"');
        $actions['acy_list']->option .= '</div>';
        $actions['acy_list']->option .= '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select($lists, 'acym_action[actions][__and__][acy_list][list_id]', null, 'class="acym__select"');
        $actions['acy_list']->option .= '</div>';
    }

    function onAcymProcessCondition_acy_list(&$query, &$options, $num, &$conditionNotValid)
    {
        $otherConditions = '';
        if (!empty($options['date-min'])) {
            $options['date-min'] = acym_replaceDate($options['date-min']);
            if (!is_numeric($options['date-min'])) {
                $options['date-min'] = strtotime($options['date-min']);
            }
            if (!empty($options['date-min'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' > '.acym_escapeDB(acym_date($options['date-min'], "Y-m-d H:i:s"));
            }
        }
        if (!empty($options['date-max'])) {
            $options['date-max'] = acym_replaceDate($options['date-max']);
            if (!is_numeric($options['date-max'])) {
                $options['date-max'] = strtotime($options['date-max']);
            }
            if (!empty($options['date-max'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' < '.acym_escapeDB(acym_date($options['date-max'], "Y-m-d H:i:s"));
            }
        }

        $query->leftjoin['list'.$num] = '#__acym_user_has_list as userlist'.$num.' ON user.id = userlist'.$num.'.user_id AND userlist'.$num.'.list_id = '.intval($options['list']).$otherConditions;
        if ($options['action'] == 'notsub') {
            $query->where[] = 'userlist'.$num.'.user_id IS NULL';
        } else {
            $status = $options['action'] == 'sub' ? '1' : '0';
            $query->where[] = 'userlist'.$num.'.status = '.intval($status);
        }

        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    function onAcymProcessCondition_acy_list_all(&$query, &$options, $num, &$conditionNotValid)
    {
        $otherConditions = '';
        if (!empty($options['date-min'])) {
            $options['date-min'] = acym_replaceDate($options['date-min']);
            if (!is_numeric($options['date-min'])) {
                $options['date-min'] = strtotime($options['date-min']);
            }
            if (!empty($options['date-min'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' > '.acym_escapeDB(acym_date($options['date-min'], "Y-m-d H:i:s"));
            }
        }
        if (!empty($options['date-max'])) {
            $options['date-max'] = acym_replaceDate($options['date-max']);
            if (!is_numeric($options['date-max'])) {
                $options['date-max'] = strtotime($options['date-max']);
            }
            if (!empty($options['date-max'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' < '.acym_escapeDB(acym_date($options['date-max'], "Y-m-d H:i:s"));
            }
        }

        $query->leftjoin['list'.$num] = '#__acym_user_has_list as userlist'.$num.' ON user.id = userlist'.$num.'.user_id AND userlist'.$num.'.list_id = '.intval($options['list']).$otherConditions;
        if ($options['action'] == 'notsub') {
            $query->where[] = 'userlist'.$num.'.user_id IS NULL';
        } else {
            $status = $options['action'] == 'sub' ? '1' : '0';
            $query->where[] = 'userlist'.$num.'.status = '.intval($status);
        }

        $numberReturn = $query->count();
        $res = false;

        switch ($options['operator']) {
            case '=' :
                $res = $numberReturn == $options['number'];
                break;
            case '>' :
                $res = $numberReturn > $options['number'];
                break;
            case '<' :
                $res = $numberReturn < $options['number'];
                break;
        }

        if (!$res) $conditionNotValid++;
    }

    function onAcymProcessFilter_acy_list(&$query, &$filterOptions, $num)
    {
        $otherConditions = '';
        if (!empty($filterOptions['date-min'])) {
            $filterOptions['date-min'] = acym_replaceDate($filterOptions['date-min']);
            if (!is_numeric($filterOptions['date-min'])) {
                $filterOptions['date-min'] = strtotime($filterOptions['date-min']);
            }
            if (!empty($filterOptions['date-min'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($filterOptions['date-type']).' > '.acym_escapeDB(acym_date($filterOptions['date-min'], "Y-m-d H:i:s"));
            }
        }
        if (!empty($filterOptions['date-max'])) {
            $filterOptions['date-max'] = acym_replaceDate($filterOptions['date-max']);
            if (!is_numeric($filterOptions['date-max'])) {
                $filterOptions['date-max'] = strtotime($filterOptions['date-max']);
            }
            if (!empty($filterOptions['date-max'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($filterOptions['date-type']).' < '.acym_escapeDB(acym_date($filterOptions['date-max'], "Y-m-d H:i:s"));
            }
        }

        $query->leftjoin['list'.$num] = '#__acym_user_has_list as userlist'.$num.' ON user.id = userlist'.$num.'.user_id AND userlist'.$num.'.list_id = '.intval($filterOptions['list']).$otherConditions;
        if ($filterOptions['action'] == 'notsub') {
            $query->where[] = 'userlist'.$num.'.user_id IS NULL';
        } else {
            $status = $filterOptions['action'] == 'sub' ? '1' : '0';
            $query->where[] = 'userlist'.$num.'.status = '.intval($status);
        }
    }

    function onAcymProcessFilterCount_acy_list(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_list($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    function onAcymProcessAction_acy_list(&$query, $action)
    {
        if ($action['list_actions'] == 'sub') {
            $queryToProcess = 'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) ('.$query->getQuery(
                    array(
                        'user.id',
                        $action['list_id'],
                        '1',
                        acym_escapeDB(acym_date(time(), "Y-m-d H:i:s")),
                    )
                ).') ON DUPLICATE KEY UPDATE status = 1';
        } else if ($action['list_actions'] == 'remove') {
            $queryToProcess = 'DELETE FROM #__acym_user_has_list WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(array('user.id')).')';
        } else if ($action['list_actions'] == 'unsub') {
            $queryToProcess = 'UPDATE #__acym_user_has_list SET status = 0 WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(array('user.id')).')';
        }

        $nbAffected = acym_query($queryToProcess);

        return acym_translation_sprintf('ACYM_ACTION_LIST_'.strtoupper($action['list_actions']), $nbAffected);
    }

    function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        if (!empty($automationCondition['acy_list'])) {
            $finalText = '';
            $listClass = acym_get('class.list');
            $automationCondition['acy_list']['list'] = $listClass->getOneById($automationCondition['acy_list']['list']);
            if (empty($automationCondition['acy_list']['list'])) {
                $automationCondition = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automationCondition['acy_list']['action'] == 'sub') $automationCondition['acy_list']['action'] = 'ACYM_IS_SUBSCRIBED';
            if ($automationCondition['acy_list']['action'] == 'unsub') $automationCondition['acy_list']['action'] = 'ACYM_IS_UNSUBSCRIBED';
            if ($automationCondition['acy_list']['action'] == 'notsub') $automationCondition['acy_list']['action'] = 'ACYM_IS_NOT_SUBSCRIBED';
            $finalText .= acym_translation_sprintf('ACYM_CONDITION_ACY_LIST_SUMMARY', acym_translation($automationCondition['acy_list']['action']), $automationCondition['acy_list']['list']->name).' ';
            if (!empty($automationCondition['acy_list']['date-min']) || !empty($automationCondition['acy_list']['date-max'])) {
                $finalText .= acym_translation_sprintf('ACYM_WHERE_DATE_ACY_LIST_SUMMARY', strtolower(acym_translation('ACYM_'.strtoupper($automationCondition['acy_list']['date-type']))));

                $dates = array();
                if (!empty($automationCondition['acy_list']['date-min'])) {
                    $automationCondition['acy_list']['date-min'] = acym_replaceDate($automationCondition['acy_list']['date-min']);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MIN_ACY_LIST_SUMMARY', acym_date($automationCondition['acy_list']['date-min'], 'd M Y H:i'));
                }
                if (!empty($automationCondition['acy_list']['date-max'])) {
                    $automationCondition['acy_list']['date-max'] = acym_replaceDate($automationCondition['acy_list']['date-max']);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MAX_ACY_LIST_SUMMARY', acym_date($automationCondition['acy_list']['date-max'], 'd M Y H:i'));
                }

                $finalText .= ' '.implode(' '.strtolower(acym_translation('ACYM_AND')).' ', $dates);
            }
            $automationCondition = $finalText;
        }


        if (!empty($automationCondition['acy_list_all'])) {
            $operators = array('=' => acym_translation('ACYM_EXACTLY'), '>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN'));
            $finalText = acym_translation('ACYM_THERE_IS').' '.strtolower($operators[$automationCondition['acy_list_all']['operator']]).' '.$automationCondition['acy_list_all']['number'].' '.acym_translation('ACYM_ACYMAILING_USERS');
            $listClass = acym_get('class.list');
            $automationCondition['acy_list_all']['list'] = $listClass->getOneById($automationCondition['acy_list_all']['list']);
            if (empty($automationCondition['acy_list_all']['list'])) {
                $automationCondition = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automationCondition['acy_list_all']['action'] == 'sub') $automationCondition['acy_list_all']['action'] = 'ACYM_SUBSCRIBED';
            if ($automationCondition['acy_list_all']['action'] == 'unsub') $automationCondition['acy_list_all']['action'] = 'ACYM_UNSUBSCRIBED';
            if ($automationCondition['acy_list_all']['action'] == 'notsub') $automationCondition['acy_list_all']['action'] = 'ACYM__NOT_SUBSCRIBED';
            $finalText .= acym_translation_sprintf('ACYM_CONDITION_ACY_LIST_SUMMARY', acym_translation($automationCondition['acy_list_all']['action']), $automationCondition['acy_list_all']['list']->name).' ';
            if (!empty($automationCondition['acy_list_all']['date-min']) || !empty($automationCondition['acy_list_all']['date-max'])) {
                $finalText .= acym_translation_sprintf('ACYM_WHERE_DATE_ACY_LIST_SUMMARY', strtolower(acym_translation('ACYM_'.strtoupper($automationCondition['acy_list_all']['date-type']))));

                $dates = array();
                if (!empty($automationCondition['acy_list_all']['date-min'])) {
                    $automationCondition['acy_list_all']['date-min'] = acym_replaceDate($automationCondition['acy_list_all']['date-min'], true);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MIN_ACY_LIST_SUMMARY', acym_date($automationCondition['acy_list_all']['date-min'], 'd M Y H:i'));
                }
                if (!empty($automationCondition['acy_list_all']['date-max'])) {
                    $automationCondition['acy_list_all']['date-max'] = acym_replaceDate($automationCondition['acy_list_all']['date-max'], true);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MAX_ACY_LIST_SUMMARY', acym_date($automationCondition['acy_list_all']['date-max'], 'd M Y H:i'));
                }

                $finalText .= ' '.implode(' '.strtolower(acym_translation('ACYM_AND')).' ', $dates);
            }
            $automationCondition = $finalText;
        }
    }

    function onAcymDeclareSummary_filters(&$automationFilter)
    {
        if (!empty($automationFilter['acy_list'])) {
            $finalText = '';
            $listClass = acym_get('class.list');
            $automationFilter['acy_list']['list'] = $listClass->getOneById($automationFilter['acy_list']['list']);
            if (empty($automationFilter['acy_list']['list'])) {
                $automationFilter = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automationFilter['acy_list']['action'] == 'sub') $automationFilter['acy_list']['action'] = 'ACYM_SUBSCRIBED';
            if ($automationFilter['acy_list']['action'] == 'unsub') $automationFilter['acy_list']['action'] = 'ACYM_UNSUBSCRIBED';
            if ($automationFilter['acy_list']['action'] == 'notsub') $automationFilter['acy_list']['action'] = 'ACYM_NOT_SUBSCRIBED';
            $finalText .= acym_translation_sprintf('ACYM_FILTER_ACY_LIST_SUMMARY', acym_translation($automationFilter['acy_list']['action']), $automationFilter['acy_list']['list']->name).' ';
            if (!empty($automationFilter['acy_list']['date-min']) || !empty($automationFilter['acy_list']['date-max'])) {
                $finalText .= acym_translation_sprintf('ACYM_WHERE_DATE_ACY_LIST_SUMMARY', strtolower(acym_translation('ACYM_'.strtoupper($automationFilter['acy_list']['date-type']))));

                $dates = array();
                if (!empty($automationFilter['acy_list']['date-min'])) {
                    $automationFilter['acy_list']['date-min'] = acym_replaceDate($automationFilter['acy_list']['date-min']);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MIN_ACY_LIST_SUMMARY', acym_date($automationFilter['acy_list']['date-min'], 'd M Y H:i'));
                }
                if (!empty($automationFilter['acy_list']['date-max'])) {
                    $automationFilter['acy_list']['date-max'] = acym_replaceDate($automationFilter['acy_list']['date-max']);
                    $dates[] = acym_translation_sprintf('ACYM_WHERE_DATE_MAX_ACY_LIST_SUMMARY', acym_date($automationFilter['acy_list']['date-max'], 'd M Y H:i'));
                }

                $finalText .= ' '.implode(' '.strtolower(acym_translation('ACYM_AND')).' ', $dates);
            }
            $automationFilter = $finalText;
        }
    }

    function onAcymDeclareSummary_actions(&$automationAction)
    {
        if (!empty($automationAction['acy_list'])) {
            $listClass = acym_get('class.list');
            $list = $listClass->getOneById($automationAction['acy_list']['list_id']);
            if ($automationAction['acy_list']['list_actions'] == 'sub') $automationAction['acy_list']['list_actions'] = 'ACYM_SUBSCRIBED_TO';
            if ($automationAction['acy_list']['list_actions'] == 'unsub') $automationAction['acy_list']['list_actions'] = 'ACYM_UNSUBSCRIBE_FROM';
            if ($automationAction['acy_list']['list_actions'] == 'remove') $automationAction['acy_list']['list_actions'] = 'ACYM_REMOVE_FROM';
            $automationAction = empty($list) ? '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>' : acym_translation_sprintf('ACYM_ACTION_LIST_SUMMARY', acym_translation($automationAction['acy_list']['list_actions']), $list->name);
        }
    }


    function onAcymAfterUserSubscribe(&$user, $lists)
    {
        $automationClass = acym_get('class.automation');
        $automationClass->triggerUser('user_subscribe', $user->id);
    }
}

