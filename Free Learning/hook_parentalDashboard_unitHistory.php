<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Tables\UnitHistory;

global $session, $container, $page;

$returnInt = null;

//Only include module include if it is not already included (which it may be been on the index page)
require_once './modules/Free Learning/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_byStudent.php') == false) {
    //Acess denied
    $returnInt .= "<div class='error'>";
    $returnInt .= __('You do not have access to this action.');
    $returnInt .= '</div>';
} else {
    $canBrowse = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse.php');
    $disableParentEvidence = ($container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'disableParentEvidence') == "Y");

    $returnInt .= "<p class='text-right mb-4 text-xs'>";
    $returnInt .= sprintf(__m('%1$sView Showcase of Student Work%2$s'), "<a class='button' href='".$session->get('absoluteURL')."/index.php?q=/modules/Free Learning/showcase.php'>", '</a>');
    if ($canBrowse) {
        $returnInt .= sprintf(__m('%1$sBrowse Units%2$s'), "<a class='button ml-2' href='".$session->get('absoluteURL')."/index.php?q=/modules/Free Learning/units_browse.php'>", '</a>');
    }
    $returnInt .= '</p>';
    $returnInt .= "<p style='margin-top: 20px'>";
    $returnInt .= __m('This tab shows recent results and enrolment for Free Learning units studied by your child.');
    if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unitHistory_byStudent.php')) {
        $returnInt .= " ".__m('Complete unit history information can be {link}viewed here{linkEnd}.', ['link' => "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Free Learning/report_unitHistory_byStudent.php&gibbonPersonID=$gibbonPersonID'>", 'linkEnd' => '</a>']);
    }
    $returnInt .= '</p>';

    include_once $session->get('absolutePath').'/modules/Free Learning/src/Tables/UnitHistory.php';
    include_once $session->get('absolutePath').'/modules/Free Learning/src/Domain/UnitStudentGateway.php';

    $page->scripts->add('chart');
    $page->stylesheets->add('module-freeLearning', 'modules/Free Learning/css/module.css');
    $returnInt .= $container->get(UnitHistory::class)->create($gibbonPersonID, true, $canBrowse, $disableParentEvidence);
}

return $returnInt;
