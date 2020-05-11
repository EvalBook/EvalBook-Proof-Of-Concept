<?php

namespace App\Controller;

use App\Entity\Eleve;
use App\Form\EleveType;
use App\Repository\EleveRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class EleveController extends AbstractController
{
    /**
     * @Route("/eleves", name="eleves", methods={"GET"})
     * @IsGranted("ROLE_STUDENT_LIST_ALL", statusCode=404, message="Not found")
     *
     * @param EleveRepository $eleveRepository
     * @return Response
     */
    public function index(EleveRepository $eleveRepository): Response
    {
        return $this->render('eleve/index.html.twig', [
            'eleves' => $eleveRepository->findAll(),
        ]);
    }


    /**
     * @Route("/eleve/view/{id}", name="eleve_view")
     *
     * @param Eleve $eleve
     * @return Response
     */
    public function view(Eleve $eleve): Response
    {
        // TODO add student infos as notes and activities...
        return $this->render('eleve/show.html.twig', [
            'eleve' => $eleve,
        ]);
    }


    /**
     * @Route("/eleve/add", name="eleve_add")
     * @IsGranted("ROLE_STUDENT_CREATE", statusCode=404, message="Not found")
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $eleve = new Eleve();
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($eleve);
            $entityManager->flush();

            return $this->redirectToRoute('eleves');
        }

        return $this->render('eleve/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/eleve/edit/{id}/{redirect}", name="eleve_edit", defaults={"redirect"=null})
     * @IsGranted("ROLE_STUDENT_EDIT", statusCode=404, message="Not found")
     *
     * @param Request $request
     * @param Eleve $eleve
     * @param String|null $redirect
     * @return Response
     */
    public function edit(Request $request, Eleve $eleve, ?String $redirect): Response
    {
        $form = $this->createForm(EleveType::class, $eleve);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            if(!is_null($redirect)) {
                $redirect = json_decode(base64_decode($redirect), true);
                return $this->redirectToRoute($redirect['route'], $redirect["params"]);
            }

            return $this->redirectToRoute('eleves');
        }

        return $this->render('eleve/form.html.twig', [
            'eleve' => $eleve,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/eleve/delete/{id}", name="eleve_delete", methods={"POST"})
     * @IsGranted("ROLE_STUDENT_DELETE", statusCode=404, message="Not found")
     *
     * @param Request $request
     * @param Eleve $eleve
     * @return Response
     */
    public function delete(Request $request, Eleve $eleve): Response
    {
        $jsonRequest = json_decode($request->getContent(), true);
        if( !isset($jsonRequest['csrf']) || !$this->isCsrfTokenValid('eleve_delete'.$eleve->getId(), $jsonRequest['csrf'])) {
            return $this->json(['message' => 'Invalid csrf token'], 201);
        }

        $entityManager = $this->getDoctrine()->getManager();
        // Deleting all student notes too.
        foreach($eleve->getNotes() as $note) {
            $entityManager->remove($note);
        }
        $entityManager->flush();

        // Removing student.
        $entityManager->remove($eleve);
        $entityManager->flush();

        return $this->json(['message' => 'Student deleted'], 200);
    }


    /**
     * @Route("/eleve/view/classes/{id}", name="student_view_classes")
     *
     * @param Eleve $eleve
     * @return Response
     */
    public function viewClasses(Eleve $eleve)
    {
        return $this->render('classe/index.html.twig', [
            'classes' => $eleve->getClasses(),
            'redirect' => base64_encode(json_encode([
                'route'  => 'student_view_classes',
                'params' => ['id' => $eleve->getId()],
            ])),
        ]);
    }
}
