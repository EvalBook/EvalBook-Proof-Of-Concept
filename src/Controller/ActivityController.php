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

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\ActivityThemeDomain;
use App\Entity\Classroom;
use App\Entity\Note;
use App\Entity\NoteType;
use App\Entity\UserConfiguration;
use App\Form\ActivityNotesType;
use App\Form\ActivityType;
use App\Service\ConfigurationService;
use DateTime;
use DeepCopy\DeepCopy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class ActivityController extends AbstractController
{
    /**
     * @Route("/activities", name="activities")
     *
     * @return Response
     */
    public function index(): Response
    {
        /*
        $activities = array();
        foreach($this->getUser()->getClassrooms() as $classroom) {
            $activities = array_merge($activities, $classroom->getActivities()->toArray());
        }
        // Setting activities with the right order ( by date descending ).
        usort($activities, function(Activity $one, Activity $two) {
            return $one->getDateAdded() < $two->getDateAdded();
        });
*/
        // Getting the user activities.
        return $this->render('activities/index.html.twig', [
            'classrooms' => $this->getUser()->getClassrooms(),
            //'activities' => $activities,
        ]);
    }

    /**
     * @Route("/activity/add/{classroom}", defaults={"classroom"=null}, name="activity_add")
     *
     * @param Classroom|null $classroom
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ConfigurationService $configuration
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function add(?Classroom $classroom, Request $request, TranslatorInterface $translator, ConfigurationService $configuration): Response
    {
        // If user is not allowed to use the classroom, then return a 405
        $this->checkClassroomAccesses($classroom);

        // If no implantation was specified, then go to the implantation / school pair selection.
        if(is_null($classroom)) {
            return $this->render('activities/select-classroom.html.twig', [
                'classrooms' => $this->getUser()->getClassrooms(),
            ]);
        }

        // Populate notes types.
        if($this->getDoctrine()->getRepository(NoteType::class)->count([]) === 0) {
            // No note type found, then populating database with the default ones.
            $this->getDoctrine()->getRepository(NoteType::class)->populate();
        }

        // Populate activities themes.
        if($this->getDoctrine()->getRepository(\App\Entity\ActivityTheme::class)->count([]) === 0) {
            // No activity type found, then populating database with the default ones.
            $this->getDoctrine()->getRepository(\App\Entity\ActivityTheme::class)->populate($translator);
        }

        // Populate activities theme domains.
        if($this->getDoctrine()->getRepository(ActivityThemeDomain::class)->count([]) === 0) {
            // No activity theme domain found, then populating database with the default ones.
            $this->getDoctrine()->getRepository(ActivityThemeDomain::class)->populate($translator);
        }

        $activity = new Activity();
        $periods = array();

        foreach( $classroom->getImplantation()->getPeriods() as $period) {
            if($period->getDateEnd() > new DateTime('now')) {
                $periods[] = $period;
            }
        }

        $form = $this->createForm(ActivityType::class, $activity, [
            'periods' => $periods,
            'activity_theme_domains' => $this->getAvailableActivityThemeDomains($classroom, $configuration),
        ]);

        $form->handleRequest($request);
        // Make sur to override any other values sent by request.
        $activity->setClassroom($classroom);
        $activity->setUser($this->getUser());

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('activities');
        }

        return $this->render('activities/form-add.html.twig', [
            'activity' => $activity,
            'classroomId' => $classroom->getId(),
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/activity/edit/{id}", name="activity_edit")
     *
     * @param Request $request
     * @param Activity $activity
     * @param ConfigurationService $configurationService
     * @return Response
     */
    public function edit(Request $request, Activity $activity, ConfigurationService $configurationService): Response
    {
        // Throw a 403 error if user can't edit the activity.
        $this->checkActivityAccesses($activity);
        $classroom = $this->getDoctrine()->getRepository(Classroom::class)->findOneBy([
            'id' => $activity->getClassroom(),
        ]);

        // If activity period target is passed, then redirect to activities list.
        if($activity->getPeriod()->getDateEnd() < new DateTime())
            return $this->redirectToRoute('activities');

        // Fetching available periods.
        $periods = array();
        foreach( $classroom->getImplantation()->getPeriods() as $period) {

            if($period->getDateEnd() > new DateTime('now')) {
                $periods[] = $period;
            }
        }

        // Creating activity form with known activity data.
        $form = $this->createForm(ActivityType::class, $activity, [
            'periods' => $periods,
            'activity_theme_domains' => $this->getAvailableActivityThemeDomains($classroom, $configurationService),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Checking the note type attributed to the activity if notes were provided.
            if( count($activity->getNotes()) > 0) {
                // Then checking agin notes and redirect if the pattern does not match the previously provided notes.
                foreach ($activity->getNotes() as $note) {

                    if(!$note->isValid($activity->getNoteType())) {
                        $this->addFlash('error', 'Please, update the notes you provided as you changed the activity note type');
                        return $this->redirectToRoute('activity_note_add', [
                            'id' => $activity->getId(),
                        ]);
                    }
                }
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('activities');
        }

        return $this->render('activities/form-edit-duplicate.html.twig', [
            'activity' => $activity,
            'classroomId' => $activity->getClassroom()->getId(),
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/activity/duplicate/{id}", name="activity_duplicate")
     *
     * @param Request $request
     * @param Activity $activity
     * @param ConfigurationService $configurationService
     * @return Response
     */
    public function duplicate(Request $request, Activity $activity, ConfigurationService $configurationService): Response
    {
        // Clone activity.
        $activity = clone $activity;

        // Getting activity classroom.
        $classroom = $this->getDoctrine()->getRepository(Classroom::class)->findOneBy([
            'id' => $activity->getClassroom(),
        ]);

        // Getting available periods.
        $periods = array();
        foreach( $activity->getClassroom()->getImplantation()->getPeriods() as $period) {
            if($period->getDateEnd() > new DateTime('now')) {
                $periods[] = $period;
            }
        }

        // Create the form with values of cloned activity.
        $form = $this->createForm(ActivityType::class, $activity, [
            'periods' => $periods,
            'activity_theme_domains' => $this->getAvailableActivityThemeDomains($classroom, $configurationService),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($activity);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Activity duplicated');
            return $this->redirectToRoute('activities');
        }

        return $this->render('activities/form-edit-duplicate.html.twig', [
            'activity' => $activity,
            'classroomId' => $activity->getClassroom()->getId(),
            'form' => $form->createView(),
        ]);
    }



    /**
     * @Route("/activity/delete/{id}", name="activity_delete", methods={"POST"})
     *
     * @param Request $request
     * @param Activity $activity
     * @return Response
     */
    public function delete(Request $request, Activity $activity): Response
    {
        // Throw a 403 error if user can't delete this activity ( not the owner ).
        $this->checkActivityAccesses($activity);

        // If activity period target is passed, then redirect to activities list.
        if($activity->getPeriod()->getDateEnd() < new DateTime())
            return $this->redirectToRoute('activities');

        $jsonRequest = json_decode($request->getContent(), true);
        if( !isset($jsonRequest['csrf']) || !$this->isCsrfTokenValid('activity_delete'.$activity->getId(), $jsonRequest['csrf'])) {
            return $this->json(['message' => 'Invalid csrf token'], 201);
        }

        $entityManager = $this->getDoctrine()->getManager();

        // Deleting notes attached to this activity.
        foreach($activity->getNotes() as $note) {
            $entityManager->remove($note);
        }

        $entityManager->remove($activity);
        $entityManager->flush();

        return $this->json(['message' => 'Activity deleted'], 200);
    }


    /**
     * @Route("/activity/note/add/{id}", name="activity_note_add")
     *
     * @param Activity $activity
     * @param Request $request
     * @return Response
     */
    public function addNotes(Activity $activity, Request $request)
    {
        // Throw a 403 error if user can't add notes to this activity ( not the owner ).
        $this->checkActivityAccesses($activity);

        // If activity period target is passed, then redirect to activities list.
        if($activity->getPeriod()->getDateEnd() < new DateTime())
            return $this->redirectToRoute('activities');

        // To make sure the user how is inserting notes is the activity owner.
        if($activity->getUser() !== $this->getUser())
            return $this->redirectToRoute('activities');

        foreach($activity->getClassroom()->getStudents() as $student) {
            // If student was not be noted for this activity, then setting a new note for him.
            if(!$student->hasNoteFor($activity)) {
                $note = new Note();
                $note->setStudent($student);
                $activity->addNote($note);
            }
        }

        $form = $this->createForm(ActivityNotesType::class, $activity);
        $em = $this->getDoctrine()->getManager();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($activity);
            // Checking provided notes format.
            foreach($activity->getNotes() as $note) {
                if(!$note->isValid($activity->getNoteType())) {
                    $this->addFlash('error', 'The notes you provided does not match the note type pattern of activity !');

                    return $this->render('activities/notes-add.html.twig', [
                        'activity' => $activity,
                        'students' => $activity->getClassroom()->getStudents(),
                        'form' => $form->createView(),
                    ]);
                }
                $em->persist($note);
            }

            // Then register updated information.
            $em->flush();

            return $this->redirectToRoute('activities', [
                'id' => $activity->getId(),
            ]);
        }

        return $this->render('activities/notes-add.html.twig', [
            'activity' => $activity,
            'students' => $activity->getClassroom()->getStudents(),
            'form' => $form->createView(),
        ]);
    }


    /**
     * Check the provided class user acces and throw access denied if user does not have rights to see it.
     * @param Classroom|null $classroom
     */
    private function checkClassroomAccesses(?Classroom $classroom)
    {
        // If user is not allowed to use the class, then return a 405
        if(!is_null($classroom)) {
            if (!$this->getUser() === $classroom->getOwner() || !in_array($this->getUser(), $classroom->getUsers()->toArray())) {
                throw $this->createAccessDeniedException();
            }
        }
    }


    /**
     * Check activity accesses and throw 403 error if an other user is trying to edit an activity.
     * @param Activity|null $activity
     */
    private function checkActivityAccesses(?Activity $activity)
    {
        if($activity->getUser() !== $this->getUser())
            throw $this->createAccessDeniedException();
    }


    /**
     * Return available activity theme domains for a provided classroom.
     * @param Classroom $classroom
     * @param ConfigurationService $configuration
     * @return array|int|mixed|string
     */
    private function getAvailableActivityThemeDomains(Classroom $classroom, ConfigurationService $configuration)
    {
        // Getting available ActivityThemeDomain.
        $atcRepository = $this->getDoctrine()->getRepository(ActivityThemeDomain::class);
        if(!is_null($classroom->getOwner())) {
            $activityThemeDomains = [];
            if($configuration->load($this->getUser())->getUsePredefinedActivitiesValues()) {
                $activityThemeDomains = $atcRepository->findBy([
                    'type' => ActivityThemeDomain::TYPE_GENERIC_DEFAULT,
                ]);
            }

            $activityThemeDomains = array_merge(
                $activityThemeDomains,
                $activityThemeDomains = $atcRepository->findByTypeAndClassroom(ActivityThemeDomain::TYPE_GENERIC, $classroom, true)
            );
        }
        else {
            $activityThemeDomains = $atcRepository->findByTypeAndClassroom(ActivityThemeDomain::TYPE_SPECIAL_CLASSROOM, $classroom, true);
        }

        return $activityThemeDomains;
    }

}

