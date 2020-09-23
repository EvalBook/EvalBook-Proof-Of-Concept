<?php

/**
 * Copyleft (c) 2020 EvalBook
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the European Union Public Licence (EUPL V 1.2),
 * version 1.2 (or any later version).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * European Union Public Licence for more details.
 *
 * You should have received a copy of the European Union Public Licence
 * along with this program. If not, see
 * https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 **/

namespace App\Controller\Api;

header("Access-Control-Allow-Origin: *");

use App\Entity\Activity;
use App\Entity\ActivityTheme;
use App\Entity\ActivityThemeDomain;
use App\Entity\ActivityThemeDomainSkill;
use App\Entity\Implantation;
use App\Entity\Note;
use App\Entity\Period;
use App\Entity\SchoolReportTheme;
use App\Entity\Student;
use App\Repository\PeriodRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class SchoolReportApi extends AbstractController
{
    /**
     * @Route("/school/report/individual/{student}/{implantation}", name="school_report_individual")
     * @ParamConverter("student", class="App\Entity\Student")
     * @ParamConverter("implantation", class="App\Entity\Implantation")
     *
     * @param Student $student
     * @param Implantation $implantation
     * @param PeriodRepository $periodRepository
     * @return JsonResponse
     */
    public function getIndividualSchoolReport(Student $student, Implantation $implantation, PeriodRepository $periodRepository){
        $notes = [];

        // Getting closed periods.
        $periods = $implantation->getPeriods();
        $periods = array_filter($periods->toArray(), function(Period $period) {
            return $period->getDateStart() < new \DateTime('now');
        });

        // Filtering notes by periods.
        foreach($student->getNotes() as $note) {
            if(in_array($note->getActivity()->getPeriod()->getId(), array_map(function(Period $period){return $period->getId();}, $periods))) {
                $notes[] = $note;
            }
        }

        // Fetching school report theme to use.
        $theme = $this->getDoctrine()->getRepository(SchoolReportTheme::class)->findOneBy([
            'id' => $implantation->getSchoolReportTheme(),
        ]);

        // Searching main classroom.
        $studentClassroom = null;
        foreach($student->getClassrooms() as $classroom) {
            if($classroom->getImplantation() === $implantation && $classroom->getOwner() !== null) {
                $studentClassroom = $classroom;
            }
        }

        // Fetching school year.
        $periods = $implantation->getPeriods();
        $start = new \DateTime();
        $end = new \DateTime('1970-1-1');

        foreach($periods->toArray() as $period) {
            /* @var $period Period */
            if($period->getDateStart() < $start)
                $start = $period->getDateStart();
            if($period->getDateEnd() > $end)
                $end = $period->getDateEnd();
        }

        $year = $start->format('Y') . ' - ' . $end->format('Y');

        $result = [];
        if(count($notes) > 0) {
            $result = $this->compute($notes, $studentClassroom->getImplantation()->getPeriods()->toArray());
        }

        $view = $this->renderView('@SchoolReportThemes/' . $theme->getUuid() . '/view.twig', [
            'student' => $student,
            'report' => $result,
            'css' => '/themes/school_report_themes/' . $theme->getUuid() . '/theme.css',
            'logo' => '/uploads/' . $implantation->getLogo(),
            'classroom' => $studentClassroom,
            'year' => $year,
        ]);


        return new JsonResponse([
            'html' => $view,
        ], 200);
    }


    /**
     * Compute the student notes.
     * @param array $notes
     * @param array $implantationPeriods
     * @return array
     */
    private function compute(array $notes, array $implantationPeriods)
    {
        $periods = $this->getStudentSchoolReportPeriods($notes, $implantationPeriods);
        $sortedNotes = $this->sortNotesByPeriods($notes, $periods);
        $this->getAveragesByPeriods($sortedNotes);

        // TODO Group array items by Theme, then by domain, dissociate special classrooms domain from items.
        $report = [];
        $themeRepository  = $this->getDoctrine()->getRepository(ActivityTheme::class);
        $domainRepository = $this->getDoctrine()->getRepository(ActivityThemeDomain::class);

        foreach($sortedNotes as $skillId => $data) {
            /* @var $skill ActivityThemeDomainSkill */
            $skill = $data['skill'];
            $theme  = '';
            $domain = '';
        }

        return $report;
    }


    /**
     * Round a number.
     * @param $value
     * @param $precision
     */
    private function round_up( &$value, $precision ) {
        $pow = pow ( 10, $precision );
        $value = ( ceil ( $pow * $value ) + ceil ( $pow * $value - ceil ( $pow * $value ) ) ) / $pow;
    }


    /**
     * Return available school report period for the student, it also include periods from other schools student learned.
     * @param array $notes
     * @param array $implantationPeriods
     * @return array
     */
    private function getStudentSchoolReportPeriods(array $notes, array $implantationPeriods)
    {
        $periods = array_map(function($period){ return $period->getName(); }, $implantationPeriods);
        foreach($notes as $note) {
            /* @var $note Note */
            if(!in_array($note->getActivity()->getPeriod()->getName(), $periods)) {
                $periods[] = $note->getActivity()->getPeriod()->getName();
            }
        }

        return $periods;
    }


    /**
     * Sort and return an array of periods/notes pairs.
     * @param array $notes
     * @param array $periodsArray
     * @return array
     */
    private function sortNotesByPeriods(array $notes, array $periodsArray)
    {
        // Transform periods as key => array
        array_map(function($periodName) use(&$periods) {$periods[$periodName] = [];}, $periodsArray);

        $sortedNotes = [];
        foreach($notes as $note) {
            /* @var $note Note */
            // Getting target skill.
            $skill = $this->getDoctrine()->getRepository(ActivityThemeDomainSkill::class)->findOneBy([
                'id' => $note->getActivity()->getActivityThemeDomainSkill()
            ]);

            // Prepare output array.
            if(!isset($sortedNotes[$skill->getId()])) {
                $sortedNotes[$skill->getId()] = [
                    'skill' => $skill,
                    'periods' => $periods,
                ];
            }

            array_push($sortedNotes[$skill->getId()]['periods'][$note->getActivity()->getPeriod()->getName()], $note);

        }

        /**
         * The returned array form is
         * [
         *   'skill_key' => [
         *       'skill'   => ActivityThemeDomainSkill::class,
         *       'periods' => [
         *          'First period name' => [
         *              Note::class,
         *              Note::class,
         *              Note::class,
         *          ],
         *          'Second period name' => [
         *              Note::class,
         *              Note::class,
         *              Note::class,
         *              Note::class,
         *          ]
         *       ]
         *   ],
         *   'Skill key 2' => [
         *       ......
         *   ]
         * ]
         */

        return $sortedNotes;
    }


    /**
     * Compute the average for each period ans each skill.
     * @param array $skills
     */
    private function getAveragesByPeriods(array &$skills)
    { // $period
        // For each skill id entry ( skill_key ... ).
        foreach($skills as $i => $val) {

            // Foreach named period ( First period name, ... )
            foreach($skills[$i]['periods'] as $j => $value) {

                $average = 0;
                $supp = 0;

                foreach($skills[$i]['periods'][$j] as $note) {
                    // m = (2 × 1 + 9 × 2 + 27 × 3) / (10 × 1 + 15 × 2 + 30 × 3) × 20

                    // Do not compute not attributed notes.
                    if(strtolower($note->getNote() === 'abs')) {
                        continue;
                    }

                    // Fetching needle information.
                    $notesIntervals = $note->getActivity()->getNoteType()->getIntervals();
                    array_push($notesIntervals, $note->getActivity()->getNoteType()->getMaximum());
                    array_unshift($notesIntervals, $note->getActivity()->getNoteType()->getMinimum());

                    // Transform note so n/m is position/count instead of 4/20.
                    $n = array_search($note->getNote(), $notesIntervals);
                    $m = count($notesIntervals) - 1;

                    $average += $n * $note->getActivity()->getCoefficient();
                    $supp += $m * $note->getActivity()->getCoefficient();
                }

                if($supp > 0) {

                    // Fetching skill in case of no note.
                    if ($skills[$i]['skill']->getNoteType()->isNumeric()) {
                        $low = ($average / $supp) * $skills[$i]['skill']->getNoteType()->getMaximum();
                        $this->round_up($low, 1);
                    } else {
                        $intervals = $skills[$i]['skill']->getNoteType()->getIntervals();
                        array_push($intervals, $skills[$i]['skill']->getNoteType()->getMaximum());
                        array_unshift($intervals, $skills[$i]['skill']->getNoteType()->getMinimum());

                        $low = ($average / $supp) * count($intervals);
                        if ($low > count($intervals) - 1)
                            $low = count($intervals) - 1;

                        $this->round_up($low, 0);
                        $low = $intervals[$low];
                    }
                }

                // Storing the result.
                $skills[$i]['periods'][$j] = $supp !== 0 ? $low : '-';
            }
        }
    }
}