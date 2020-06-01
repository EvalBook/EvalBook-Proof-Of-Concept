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

use App\Entity\Implantation;
use App\Entity\Period;
use App\Form\ImplantationType;
use App\Form\PeriodeType;
use App\Repository\ImplantationRepository;
use App\Repository\PeriodRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class ImplantationController
 * @package App\Controller
 */
class ImplantationController extends AbstractController
{
    /**
     * @Route("/implantations", name="implantations")
     * @IsGranted("ROLE_IMPLANTATION_LIST_ALL", statusCode=404, message="Not found")
     *
     * @param ImplantationRepository $repository
     * @return Response
     */
    public function index(ImplantationRepository $repository)
    {
        return $this->render('implantations/index.html.twig', [
            'implantations' => $repository->findAll()
        ]);
    }


    /**
     * @Route("/implantation/add", name="implantation_add")
     * @IsGranted("ROLE_IMPLANTATION_CREATE", statusCode=404, message="Not found")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function add(Request $request)
    {
        $implantation = new Implantation();
        $form = $this->createForm(ImplantationType::class, $implantation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($implantation);
            $entityManager->flush();
            $this->addFlash('success', 'Successfully added');

            return $this->redirectToRoute('implantations');
        }

        return $this->render('implantations/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/edit/{id}", name="implantation_edit")
     * @IsGranted("ROLE_IMPLANTATION_EDIT", statusCode=404, message="Not found")
     *
     * @param Request $request
     * @param Implantation $implantation
     * @return RedirectResponse|Response
     */
    public function edit(Implantation $implantation, Request $request)
    {
        $form = $this->createForm(ImplantationType::class, $implantation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('implantations');
        }

        return $this->render('implantations/form.html.twig', [
            'implantation' => $implantation,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/delete/{id}", name="implantation_delete", methods={"POST"})
     * @IsGranted("ROLE_IMPLANTATION_DELETE", statusCode=404, message="Not found")
     *
     * @param Implantation $implantation
     * @param Request $request
     * @param bool $checkToken
     * @return JsonResponse
     */
    public function delete(Implantation $implantation, Request $request, $checkToken=true)
    {
        $jsonRequest = json_decode($request->getContent(), true);

        if($checkToken) {
            if (!isset($jsonRequest['csrf']) || !$this->isCsrfTokenValid('implantation_delete' . $implantation->getId(), $jsonRequest['csrf'])) {
                return $this->json(['message' => 'Invalid csrf token'], 201);
            }
        }

        $entityManager = $this->getDoctrine()->getManager();

        // Deleting activities and classes. Detach attributed notes from classes activities.
        foreach($implantation->getClassrooms() as $classroom) {

            foreach($classroom->getActivities() as $activity) {
                $activity->detachNotes();
                $activity->setPeriod(null);
                $entityManager->persist($activity);
                $entityManager->flush();

                $entityManager->remove($activity);
            }
            $entityManager->remove($classroom);
        }
        $entityManager->flush();

        foreach($implantation->getPeriods() as $period) {
            $entityManager->remove($period);
        }

        $entityManager->remove($implantation);
        $entityManager->flush();

        return $this->json(['message' => 'Implantation deleted'], 200);
    }


    /**
     * @Route("/implantation/view/classrooms/{id}", name="implantation_view_classrooms")
     * @IsGranted("ROLE_IMPLANTATION_LIST_ALL", statusCode=404, message="Not found")
     *
     * @param Implantation $implantation
     * @return Response
     */
    public function viewClassrooms(Implantation $implantation)
    {
        return $this->render('classrooms/index.html.twig', [
            'classrooms' => $implantation->getClassrooms(),
            'redirect' => base64_encode(json_encode([
                'route'  => 'implantation_view_classes',
                'params' => ['id' => $implantation->getId()],
            ])),
        ]);
    }


    /**
     * @Route("/implantation/period/list/{id}", name="implantation_period_list")
     * @IsGranted("ROLE_IMPLANTATION_EDIT", statusCode=404, message="Not found")
     *
     * @param Implantation $implantation
     * @param PeriodRepository $repository
     * @return Response
     */
    public function viewPeriods(Implantation $implantation, PeriodRepository $repository)
    {
        return $this->render('implantations/period-index.html.twig', [
            'periods' => $repository->findBy(
                ['implantation' => $implantation->getId()],
                ['dateStart' => 'ASC']
            ),
            'implantation' => $implantation,
        ]);
    }


    /**
     * @Route("/implantation/period/add/{id}", name="implantation_period_add")
     * @IsGranted("ROLE_IMPLANTATION_EDIT", statusCode=404, message="Not found")
     *
     * @param Implantation $implantation
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function addPeriod(Implantation $implantation, Request $request)
    {
        $period = new Period();
        $period->setImplantation($implantation);

        $form = $this->createForm(PeriodeType::class, $period);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($period);
            $entityManager->flush();
            $this->addFlash('success', 'Successfully added');

            return $this->redirectToRoute('implantations');
        }

        return $this->render('implantations/period-form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/period/edit/{id}", name="implantation_period_edit")
     * @IsGranted("ROLE_IMPLANTATION_EDIT", statusCode=404, message="Not found")
     *
     * @param Period $periode
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editPeriod(Period $periode, Request $request)
    {
        $this->canManipulatePeriod($periode);

        $form = $this->createForm(PeriodeType::class, $periode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Successfully updated');

            return $this->redirectToRoute('implantation_period_list', [
                'id' => $periode->getImplantation()->getId(),
            ]);
        }

        return $this->render('implantations/period-form.html.twig', [
            'period' => $periode,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/period/delete/{id}", name="implantation_periode_delete", methods={"POST"})
     * @IsGranted("ROLE_IMPLANTATION_EDIT", statusCode=404, message="Not found")
     *
     * @param Period $period
     * @param Request $request
     * @return JsonResponse
     */
    public function deletePeriod(Period $period, Request $request)
    {
        // Check if period can be deleted.
        $this->canManipulatePeriod($period);

        $jsonRequest = json_decode($request->getContent(), true);
        if( !isset($jsonRequest['csrf']) || !$this->isCsrfTokenValid('implantation_period_delete'.$period->getId(), $jsonRequest['csrf'])) {
            return $this->json(['message' => 'Invalid csrf token'], 201);
        }

        // Only allowing deletion if the period has no activity attached to it.
        if(! count($period->getActivities()) > 0) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($period);
            $entityManager->flush();

            return $this->json(['message' => 'Period deleted'], 200);
        }

        return $this->json(['error' => true], 200);
    }


    /**
     * Check period start date, if period already started or closed, then throwing access denied.
     * @param Period|null $period
     */
    private function canManipulatePeriod(?Period $period)
    {
        if(!is_null($period)) {
            if ($period->getDateStart() < new \DateTime()) {
                throw $this->createAccessDeniedException();
            }
        }
    }

}
