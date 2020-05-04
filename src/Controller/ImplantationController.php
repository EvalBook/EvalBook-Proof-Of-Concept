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
use App\Form\ImplantationType;
use App\Repository\ImplantationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @param ImplantationRepository $repository
     * @return Response
     */
    public function index(ImplantationRepository $repository)
    {
        return $this->render('implantation/index.html.twig', [
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

        return $this->render('implantation/form.html.twig', [
            'book' => $implantation,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/edit/{id}", name="implantation_edit")
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

        return $this->render('implantation/form.html.twig', [
            'implantation' => $implantation,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/implantation/delete/{id}", name="implantation_delete", methods={"POST"})
     * @param Implantation $implantation
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteImplantation(Implantation $implantation, Request $request)
    {
        $jsonRequest = json_decode($request->getContent(), true);
        if( !isset($jsonRequest['csrf']) || !$this->isCsrfTokenValid('implantation_delete'.$implantation->getId(), $jsonRequest['csrf'])) {
            return $this->json(['message' => 'Invalid csrf token'], 201);
        }

        $entityManager = $this->getDoctrine()->getManager();

        // Deleting implantation, attached classes and attached periods, making others orphan.
        $attached = array_merge($implantation->getClasses()->toArray(), $implantation->getPeriodes()->toArray());
        foreach($attached as $attachedEntity) {
            $entityManager->remove($attachedEntity);
        }
        $entityManager->remove($implantation);
        $entityManager->flush();

        return $this->json(['message' => 'Implantation deleted'], 200);
    }
}