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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitClassGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitStudent';
    private static $primaryKey = 'freeLearningUnitStudentID';
    private static $searchableColumns = [];

    public function selectUnitsByClass($gibbonCourseClassID, $sort)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols(['gibbonPerson.gibbonPersonID', 'surname', 'preferredName', 'freeLearningUnit.freeLearningUnitID', 'freeLearningUnit.name AS unitName', 'timestampJoined', 'collaborationKey', 'freeLearningUnitStudent.status', 'enrolmentMethod', 'fields'])
            ->join('inner', 'gibbonCourseClassPerson','gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND role=\'Student\'')
            ->leftJoin('freeLearningUnitStudent','freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND (freeLearningUnitStudent.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID OR enrolmentMethod=\'schoolMentor\' OR enrolmentMethod=\'externalMentor\') AND (freeLearningUnitStudent.status=\'Current\' OR freeLearningUnitStudent.status=\'Current - Pending\' OR freeLearningUnitStudent.status=\'Complete - Pending\' OR freeLearningUnitStudent.status=\'Evidence Not Yet Approved\')')
            ->leftJoin('freeLearningUnit','freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->where('gibbonPerson.status=\'Full\'')
            ->where('(dateStart IS NULL OR dateStart<=:date)')
            ->where('(dateEnd IS NULL  OR dateEnd>=:date)')
                ->bindValue('date', date('Y-m-d'))
            ->where('gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID')
                ->bindValue('gibbonCourseClassID', $gibbonCourseClassID);
                
                if ($sort == 'student') {
                    $query->orderBy(['surname', 'preferredName', 'unitName']);
                } else {
                    $query->orderBy(['unitName', 'collaborationKey', 'surname', 'preferredName']);
                }

        return $this->runSelect($query);
    }
    
    public function selectTiming($gibbonCourseClassID, $dateJoined)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPlannerEntry')
            ->cols(['date', 'timeStart', 'timeEnd'])
            ->where('name LIKE \'%Free Learning%\'')
            ->where('gibbonCourseClassID=:gibbonCourseClassID')
                ->bindValue('gibbonCourseClassID', $gibbonCourseClassID)
            ->where('date>=:dateJoined')
                ->bindValue('dateJoined', substr($dateJoined, 0, 10))
            ->where(' date<=:today')
                ->bindValue('today', date('Y-m-d'));
                
        return $this->runSelect($query);
    }
}