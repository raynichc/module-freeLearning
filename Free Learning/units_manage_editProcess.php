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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

require_once '../../gibbon.php';


$freeLearningUnitID = $_GET['freeLearningUnitID'];
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_manage_edit.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=".$_GET['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'];

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_edit.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        if (empty($_POST)) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $name = $_POST['name'];
            $difficulty = $_POST['difficulty'];
            $blurb = $_POST['blurb'];
            $studentReflectionText = $_POST['studentReflectionText'] ?? '';
            $gibbonDepartmentIDList = (!empty($_POST['gibbonDepartmentIDList']) && is_array($_POST['gibbonDepartmentIDList'])) ? implode(",", $_POST['gibbonDepartmentIDList']) : null;
            $course = $_POST['course'] ?? null;
            $license = $_POST['license'];
            $majorEdit = $_POST['majorEdit'] ?? null;
            $availableStudents = $_POST['availableStudents'];
            $availableStaff = $_POST['availableStaff'];
            $availableParents = $_POST['availableParents'];
            $availableOther = $_POST['availableOther'];
            $sharedPublic = $_POST['sharedPublic'] ?? null;
            $active = $_POST['active'] ?? 'N';
            $editLock = $_POST['editLock'] ?? 'N';
            $gibbonYearGroupIDMinimum = !empty($_POST['gibbonYearGroupIDMinimum']) ? $_POST['gibbonYearGroupIDMinimum'] : null;
            $grouping = (!empty($_POST['grouping']) && is_array($_POST['grouping'])) ? implode(",", $_POST['grouping']) : '';
            $freeLearningUnitIDPrerequisiteList = (!empty($_POST['freeLearningUnitIDPrerequisiteList']) && is_array($_POST['freeLearningUnitIDPrerequisiteList'])) ? implode(",", $_POST['freeLearningUnitIDPrerequisiteList']) : null;
            $outline = $_POST['outline'];
            $schoolMentorCompletors = $_POST['schoolMentorCompletors'] ?? null;
            $schoolMentorCustom = (!empty($_POST['schoolMentorCustom']) && is_array($_POST['schoolMentorCustom'])) ? implode(",", $_POST['schoolMentorCustom']) : null;
            $schoolMentorCustomRole = $_POST['schoolMentorCustomRole'] ?? null;

            if ($name == '' or $difficulty == '' or $active == '' or $editLock == '' or $availableStudents == '' or $availableStaff == '' or $availableParents == '' or $availableOther == '') {
                //Fail 3
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                $partialFail = false;

                //Check existence of specified unit
                try {
                    if ($highestAction == 'Manage Units_all') {
                        $data = array('freeLearningUnitID' => $freeLearningUnitID);
                        $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
                    } elseif ($highestAction == 'Manage Units_learningAreas') {
                        $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'freeLearningUnitID' => $freeLearningUnitID);
                        $sql = "SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND freeLearningUnitID=:freeLearningUnitID ORDER BY difficulty, name";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&addReturn=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    //Fail 4
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    if ($highestAction != "Manage Units_all" && $row['editLock'] == "Y") {
                        $URL .= '&return=error1';
                        header("Location: {$URL}");
                        exit;
                    }

                    //Move attached file, if there is one
                    $partialFail = false;
                    $attachment = null;

                    if (!empty($_FILES['file']['tmp_name'])) {
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                        $fileUploader->getFileExtensions('Graphics/Design');

                        $file = $_FILES['file'] ?? null;

                        // Upload the file, return the /uploads relative path
                        $attachment = $fileUploader->uploadFromPost($file, $name);

                        if (empty($attachment)) {
                            $partialFail = true;
                        }

                        if ($attachment != null) {
                            $attachment = $gibbon->session->get('absoluteURL').'/'.$attachment;
                        }
                    } else {
                        if (empty($_POST['logo'])) {
                            $attachment = null;
                        }
                        else {
                            $attachment = $row['logo'];
                        }
                    }

                    //Write to database
                    try {
                        $data = array('name' => $name, 'course' => $course, 'logo' => $attachment, 'difficulty' => $difficulty, 'blurb' => $blurb, 'license' => $license, 'availableStudents'=>$availableStudents, 'availableStaff'=>$availableStaff, 'availableParents'=>$availableParents, 'availableOther' => $availableOther, 'sharedPublic' => $sharedPublic, 'active' => $active, 'editLock' => $editLock, 'gibbonYearGroupIDMinimum' => $gibbonYearGroupIDMinimum, 'grouping' => $grouping, 'gibbonDepartmentIDList' => $gibbonDepartmentIDList, 'freeLearningUnitIDPrerequisiteList' => $freeLearningUnitIDPrerequisiteList, 'schoolMentorCompletors' => $schoolMentorCompletors, 'schoolMentorCustom' => $schoolMentorCustom, 'schoolMentorCustomRole'
                         => $schoolMentorCustomRole, 'outline' => $outline, 'studentReflectionText' => $studentReflectionText, 'freeLearningUnitID' => $freeLearningUnitID);
                        $sql = 'UPDATE freeLearningUnit SET name=:name, course=:course, logo=:logo, difficulty=:difficulty, blurb=:blurb, license=:license, availableStudents=:availableStudents, availableStaff=:availableStaff, availableParents=:availableParents, availableOther=:availableOther, sharedPublic=:sharedPublic, active=:active, editLock=:editLock, gibbonYearGroupIDMinimum=:gibbonYearGroupIDMinimum, `grouping`=:grouping, gibbonDepartmentIDList=:gibbonDepartmentIDList, freeLearningUnitIDPrerequisiteList=:freeLearningUnitIDPrerequisiteList, schoolMentorCompletors=:schoolMentorCompletors, schoolMentorCustom=:schoolMentorCustom, schoolMentorCustomRole=:schoolMentorCustomRole, outline=:outline, studentReflectionText=:studentReflectionText WHERE freeLearningUnitID=:freeLearningUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        //Fail 2
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Write author to database for major edits only
                    if ($majorEdit == 'Y') {
                        try {
                            $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
                            $sql = 'SELECT * FROM freeLearningUnitAuthor WHERE freeLearningUnitID=:freeLearningUnitID AND gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        if ($result->rowCount() < 1) {
                            try {
                                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'surname' => $gibbon->session->get('surname'), 'preferredName' => $gibbon->session->get('preferredName'), 'website' => $gibbon->session->get('website'));
                                $sql = 'INSERT INTO freeLearningUnitAuthor SET freeLearningUnitID=:freeLearningUnitID, gibbonPersonID=:gibbonPersonID, surname=:surname, preferredName=:preferredName, website=:website';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //Delete all outcomes
                    try {
                        $dataDelete = array('freeLearningUnitID' => $freeLearningUnitID);
                        $sqlDelete = 'DELETE FROM freeLearningUnitOutcome WHERE freeLearningUnitID=:freeLearningUnitID';
                        $resultDelete = $connection2->prepare($sqlDelete);
                        $resultDelete->execute($dataDelete);
                    } catch (PDOException $e) {
                        //Fail2
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                    //Insert outcomes
                    $count = 0;
                    if (isset($_POST['outcomeorder'])) {
                        if (count($_POST['outcomeorder']) > 0) {
                            foreach ($_POST['outcomeorder'] as $outcome) {
                                if ($_POST["outcomegibbonOutcomeID$outcome"] != '') {
                                    try {
                                        $dataInsert = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonOutcomeID' => $_POST["outcomegibbonOutcomeID$outcome"], 'content' => $_POST["outcomecontents$outcome"], 'count' => $count);
                                        $sqlInsert = 'INSERT INTO freeLearningUnitOutcome SET freeLearningUnitID=:freeLearningUnitID, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count';
                                        $resultInsert = $connection2->prepare($sqlInsert);
                                        $resultInsert->execute($dataInsert);
                                    } catch (PDOException $e) {
                                        echo $e;
                                        $partialFail = true;
                                    }
                                }
                                ++$count;
                            }
                        }
                    }

                    //Update blocks
                    $order = $_POST['order'] ?? '';
                    $sequenceNumber = 0;
                    $dataRemove = array();
                    $whereRemove = '';
                    if (count($order) < 0) {
                        //Fail 3
                        $URL .= '&addReturn=error3';
                        header("Location: {$URL}");
                    } else {
                        if (is_array($order)) {
                            foreach ($order as $i) {
                                $title = '';
                                if ($_POST["title$i"] != "Block $i") {
                                    $title = $_POST["title$i"];
                                }
                                $type2 = '';
                                if ($_POST["type$i"] != 'type (e.g. discussion, outcome)') {
                                    $type2 = $_POST["type$i"];
                                }

                                $length = isset($_POST["length$i"]) ? intval(trim($_POST["length$i"])) : null;
                                $contents = trim($_POST["contents$i"]);

                                // Remove the <![CDATA that TinyMCE adds to script tags
                                $contents = str_replace(["// <![CDATA[", "// ]]>"], ['', ''], $contents);

                                $teachersNotes = $_POST["teachersNotes$i"];
                                $freeLearningUnitBlockID = @$_POST["freeLearningUnitBlockID$i"];

                                if ($freeLearningUnitBlockID != '') {
                                    try {
                                        $dataBlock = array('freeLearningUnitID' => $freeLearningUnitID, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber, 'freeLearningUnitBlockID' => $freeLearningUnitBlockID);
                                        $sqlBlock = 'UPDATE freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber WHERE freeLearningUnitBlockID=:freeLearningUnitBlockID';
                                        $resultBlock = $connection2->prepare($sqlBlock);
                                        $resultBlock->execute($dataBlock);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                    $dataRemove["freeLearningUnitBlockID$sequenceNumber"] = $freeLearningUnitBlockID;
                                    $whereRemove .= "AND NOT freeLearningUnitBlockID=:freeLearningUnitBlockID$sequenceNumber ";
                                } else {
                                    try {
                                        $dataBlock = array('freeLearningUnitID' => $freeLearningUnitID, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                        $sqlBlock = 'INSERT INTO freeLearningUnitBlock SET freeLearningUnitID=:freeLearningUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';
                                        $resultBlock = $connection2->prepare($sqlBlock);
                                        $resultBlock->execute($dataBlock);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                    $dataRemove["freeLearningUnitBlockID$sequenceNumber"] = $connection2->lastInsertId();
                                    $whereRemove .= "AND NOT freeLearningUnitBlockID=:freeLearningUnitBlockID$sequenceNumber ";
                                }

                                ++$sequenceNumber;
                            }
                        }
                    }

                    //Remove orphaned blocks
                    if ($whereRemove != '(') {
                        try {
                            $dataRemove['freeLearningUnitID'] = $freeLearningUnitID;
                            $sqlRemove = "DELETE FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID $whereRemove";
                            $resultRemove = $connection2->prepare($sqlRemove);
                            $resultRemove->execute($dataRemove);
                        } catch (PDOException $e) {
                            echo $e->getMessage();
                            $partialFail = true;
                        }
                    }

                    if ($partialFail) {
                        //Fail 6
                        $URL .= '&return=error6';
                        header("Location: {$URL}");
                    } else {
                        //Success 0
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
