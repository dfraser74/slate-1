<?php

namespace Slate\People;

use ActiveRecord;
use Emergence\People\Person;
use Emergence\People\Groups\Group;

use Slate\Term;
use Slate\TermsRequestHandler;
use Slate\Courses\Section;
use Slate\Courses\SectionParticipant;


class PeopleRequestHandler extends \PeopleRequestHandler
{
    public static $userResponseModes = [
        'application/json' => 'json'
        ,'text/csv' => 'csv'
        ,'application/pdf' => 'pdf'
        ,'text/html; display=print' => 'print'
    ];

    public static function handleRecordsRequest($action = false)
    {
        switch ($action ? $action : $action = static::shiftPath()) {
            case '*advisors':
                $GLOBALS['Session']->requireAuthentication();

                return static::respond('advisors', [
                    'data' => Student::getDistinctAdvisors()
                ]);
            case '*graduation-years':
                $GLOBALS['Session']->requireAuthentication();

                return static::respond('graduation-years', [
                    'data' => Student::getDistinctGraduationYears()
                ]);
            case '*student-lists':
                return static::handleStudentListsRequest();
            default:
                return parent::handleRecordsRequest($action);
        }
    }

    public static function handleStudentListsRequest()
    {
        $GLOBALS['Session']->requireAuthentication();

        $lists = [];


        // compile sections for current user, current term, or selected term
        if (
            $GLOBALS['Session']->Person
            && count($GLOBALS['Session']->Person->CurrentCourseSections)
            && (
                empty($_GET['sections']) ||
                $_GET['sections'] != 'all'
            )
        ) {
            foreach ($GLOBALS['Session']->Person->CurrentCourseSections as $Section) {
                $lists[] = [
                    'groupId' => 'sections',
                    'groupLabel' => 'My Sections',
                    'label' => $Section->Code,
                    'value' => "section:{$Section->Code}"
                ];

                foreach ($Section->getCohorts() as $cohort) {
                    $lists[] = [
                        'groupId' => 'sections',
                        'groupLabel' => 'My Sections',
                        'label' => "{$Section->Code} ▸ {$cohort}",
                        'value' => "section:{$Section->Code}:{$cohort}"
                    ];
                }
            }
        } else {
            if (empty($_GET['term'])) {
                if (!$Term = Term::getClosest()) {
                    return static::throwInvalidRequestError('no current term found');
                }
            } else {
                if (!$Term = TermsRequestHandler::getRecordByHandle($_GET['term'])) {
                    return static::throwNotFoundError('requested term not found');
                }
            }

            $sections = Section::getAllByWhere([
                'TermID' => [
                    'values' => $Term->getRelatedTermIDs()
                ]
            ]);

            foreach ($sections as $Section) {
                $lists[] = [
                    'groupId' => 'sections',
                    'groupLabel' => "All sections in {$Term->Title}",
                    'label' => $Section->Code,
                    'value' => "section:{$Section->Code}"
                ];

                foreach ($Section->getCohorts() as $cohort) {
                    $lists[] = [
                        'groupId' => 'sections',
                        'groupLabel' => "All sections in {$Term->Title}",
                        'label' => "{$Section->Code} ▸ {$cohort}",
                        'value' => "section:{$Section->Code}:{$cohort}"
                    ];
                }
            }
        }


        // compile user groups
        foreach (Group::getAll(['order' => ['Left' => 'ASC']]) as $Group) {
            $lists[] = [
                'groupId' => 'groups',
                'groupLabel' => 'User Groups',
                'label' => $Group->getFullPath(' ▸ '),
                'value' => "group:{$Group->Handle}"
            ];
        }


        // build options
        $groupOptions = [];

        if (!isset($Term)) {
            $groupOptions['sections'] = [
                [
                    'label' => 'Show all sections',
                    'query' => [
                        'sections' => 'all'
                    ]
                ]
            ];
        } else {
            $groupOptions['sections'] = [
                [
                    'label' => 'Show only my sections',
                    'query' => [
                        'sections' => 'enrolled'
                    ]
                ]
            ];
        }


        return static::respond('studentLists', [
            'data' => $lists,
            'groupOptions' => $groupOptions
        ]);
    }

    public static function handleRecordRequest(ActiveRecord $Person, $action = false)
    {
        switch ($action ? $action : $action = static::shiftPath()) {
            case 'courses':
                return static::handleCoursesRequest($Person);
            default:
                return parent::handleRecordRequest($Person, $action);
        }
    }

    public static function handleCoursesRequest(Person $Person)
    {
        $GLOBALS['Session']->requireAccountLevel('Staff');

        if (!empty($_REQUEST['termID'])) {
            $Term = Term::getByID($_REQUEST['termID']);
        } else {
            $Term = Term::getClosest();
        }

        if (!$Term) {
            return static::throwNotFoundError('Term not found');
        }

        return static::respond('sections', [
            'data' => Section::getAllByQuery(
                'SELECT sections.* FROM `%s` participants INNER JOIN `%s` sections ON (participants.CourseSectionID = sections.ID) WHERE sections.Status = "Live" AND participants.PersonID = %u AND sections.TermID IN (%s)'
                ,[
                    SectionParticipant::$tableName
                    ,Section::$tableName
                    ,$Person->ID
                    ,implode(',', $Term->getRelatedTermIDs())
                ]
            )
        ]);
    }
}