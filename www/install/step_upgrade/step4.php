<?php
/*
 * Copyright 2005-2019 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

session_start();
define('STEP_NUMBER', 4);

$_SESSION['step'] = STEP_NUMBER;
require_once realpath(dirname(__FILE__) . "/../../../config/centreon.config.php");
require_once '../steps/functions.php';
$template = getTemplate('templates');

include_once _CENTREON_PATH_ . "/www/class/centreonDB.class.php";

/*
** Get and check initial Centreon version.
** Should be >= 2.8.0-beta1.
*/
$db = new CentreonDB();
$res = $db->query("SELECT `value` FROM `informations` WHERE `key` = 'version'");
$row = $res->fetchRow();
$current = $row['value'];
$_SESSION['CURRENT_VERSION'] = $current;
if (version_compare($current, '2.8.0-beta1') < 0) {
    $troubleshootTxt1 = _('Upgrade to this release requires Centreon >= 2.8.0-beta1.');
    $troubleshootTxt2 = sprintf(_('Your current version is %s.'), $current);
    $troubleshootTxt3 = _('Please upgrade to an intermediate release (ie. 2.8.x) first.');
    $contents .= sprintf(
        '<p class="required">%s<br/>%s<br/>%s</p>',
        $troubleshootTxt1,
        $troubleshootTxt2,
        $troubleshootTxt3
    );

/*
** Print upcoming database upgrade steps.
*/
} else {
    $contents = _('<p>Currently upgrading database... please do not interrupt this process.</p>');
    $contents .= "<table cellpadding='0' cellspacing='0' border='0' width='80%' class='StyleDottedHr' align='center'>
                    <thead>
                        <tr>
                            <th>" . _('Step') . "</th>
                            <th>" . _('Status') . "</th>
                        </tr>
                    </thead>
                    <tbody id='step_contents'>
                    </tbody>
                  </table>";

    $troubleshootTxt1 = _('You seem to be having trouble with your upgrade.');
    $troubleshootTxt2 = _('You may refer to the line that causes problem in order to find out more about the issue.');
    $troubleshootTxt3 = sprintf(_('The SQL files are located in %s'), _CENTREON_PATH_ . 'www/install/sql/');
    $troubleshootTxt4 = _('But do not edit the SQL files unless you know what you are doing.'
        . 'Refresh this page when the problem is fixed.');
    $contents .= sprintf(
        '<br/><p id="troubleshoot" style="display:none;">%s<br/>%s<br/>%s<br/>%s</p>',
        $troubleshootTxt1,
        $troubleshootTxt2,
        $troubleshootTxt3,
        $troubleshootTxt4
    );

    /*
    ** To find the next version that we should update to, we will look in
    ** the www/install/php directory where all PHP update scripts are
    ** stored. We will extract the target version from the filename and find
    ** the closest version to the current version.
    */
    $next = '';
    if ($handle = opendir('../php')) {
        while (false !== ($file = readdir($handle))) {
            if (preg_match('/Update-([a-zA-Z0-9\-\.]+)\.php/', $file, $matches)) {
                if ((version_compare($current, $matches[1]) < 0) &&
                    (empty($next) || (version_compare($matches[1], $next) < 0))) {
                    $next = $matches[1];
                }
            }
        }
        closedir($handle);
    }
}

/*
** Generate template.
*/
$title = _('Installation');
$template->assign('step', STEP_NUMBER);
$template->assign('title', $title);
$template->assign('content', $contents);
$template->assign('blockPreview', 1);
$template->display('content.tpl');
?>
<script type='text/javascript'>
    var step = <?php echo STEP_NUMBER;?>;
    var mycurrent;
    var mynext;
    var result = false;

    jQuery(function () {
        mycurrent = '<?php echo $current;?>';
        mynext = '<?php echo $next;?>';
        if (mycurrent != '' && mynext != '') {
            jQuery("input[type=button]").hide();
            nextStep(mycurrent, mynext);
        } else {
            result = true;
        }
    });

    /**
     * Go to next upgrade script
     *
     * @param string current
     * @param string next
     * @return void
     */
    function nextStep(current, next) {
        jQuery('#step_contents').append('<tr>');
        jQuery('#step_contents').append('<td>' + current + ' to ' + next + '</td>');
        jQuery('#step_contents').append('<td style="font-weight: bold;" name="'
            + replaceDot(current) + '"><img src="../img/misc/ajax-loader.gif"></td>');
        jQuery('#step_contents').append('</tr>');
        doProcess(
            true,
            './step_upgrade/process/process_step' + step + '.php'
            , {'current': current, 'next': next},
            function (response) {
                var data = jQuery.parseJSON(response);
                jQuery('td[name=' + replaceDot(current) + ']').html(data['msg']);
                if (data['result'] == "0") {
                    jQuery('#troubleshoot').hide();
                    if (data['next']) {
                        nextStep(data['current'], data['next']);
                    } else {
                        jQuery('#next').show();
                        result = true;
                    }
                } else {
                    jQuery('#troubleshoot').show();
                    jQuery('#refresh').show();
                }
            });
    }

    /**
     * Replace dot with dash characters
     *
     * @param string str
     * @return void
     */
    function replaceDot(str) {
        return str.replace(/\./g, '-');
    }

    /**
     * Validates info
     *
     * @return bool
     */
    function validation() {
        return result;
    }
</script>
