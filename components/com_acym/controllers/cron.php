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

class CronController extends acymController
{
    function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('cron');
        acym_setNoTemplate();
    }

    function cron()
    {
        header("Content-type:text/html; charset=utf-8");
        if (strlen(ACYM_LIVE) < 10) {
            die(acym_translation_sprintf('ACYM_CRON_WRONG_DOMAIN', ACYM_LIVE));
        }

        if (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'www.yourcrontask.com') === false) {
            exit;
        }

        echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title>'.acym_translation('ACYM_CRON').'</title></head><body>';
        $cronHelper = acym_get('helper.cron');
        $cronHelper->report = true;
        $cronHelper->skip = explode(',', acym_getVar('string', 'skip'));
        $emailtypes = acym_getVar('string', 'emailtypes');
        if (!empty($emailtypes)) {
            $cronHelper->emailtypes = explode(',', $emailtypes);
        }
        $cronHelper->cron();
        $cronHelper->report();
        echo '</body></html>';

        exit;
    }
}
