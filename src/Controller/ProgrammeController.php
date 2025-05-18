<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Form\ProgrammeForm;
use App\Repository\CoachRepository;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/programme')]
final class ProgrammeController extends AbstractController
{
    #[Route(name: 'app_programme_index', methods: ['GET'])]
    public function index(ProgrammeRepository $programmeRepository): Response
    {
        return $this->render('programme/index.html.twig', [
            'programmes' => $programmeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_programme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,CoachRepository $coachRepository): Response
    {   $this->denyIfBanned();
         $user = $this->getUser();
            if (
                !$this->isGranted('ROLE_ADMIN')
                && !($user && method_exists($user, 'isCoach') && $user->isCoach())
            ) {
                throw $this->createAccessDeniedException('Access denied.');
            }
        $programme = new Programme();

        if ($user && method_exists($user, 'isCoach') && $user->isCoach()) {
            $coach = $coachRepository->findOneBy(['User' => $user]);
            if ($coach) {
                $programme->setCoach($coach);
            } else {
                throw $this->createNotFoundException('Coach introuvable pour cet utilisateur.');
            }
    }

        $form = $this->createForm(ProgrammeForm::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($programme);
            $entityManager->flush();

            return $this->redirectToRoute('app_programme_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('programme/new.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_programme_show', methods: ['GET'])]
    public function show(Programme $programme): Response
    {   $this->denyIfBanned();
        return $this->render('programme/show.html.twig', [
            'programme' => $programme,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_programme_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {   $this->denyIfBanned();
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isCoachOwner = $user && method_exists($user, 'isCoach') && $user->isCoach()
        && $programme->getCoach() && $programme->getCoach()->getUser()->getId() === $user->getId();

    if (!$isAdmin && !$isCoachOwner) {
        throw $this->createAccessDeniedException('Access denied.');
    }
        $form = $this->createForm(ProgrammeForm::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_programme_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('programme/edit.html.twig', [
            'programme' => $programme,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Programme $programme, EntityManagerInterface $entityManager): Response
    {   $this->denyIfBanned();
        $user = $this->getUser();
            if (
                !$this->isGranted('ROLE_ADMIN')
                && !($user && method_exists($user, 'isCoach') && $user->isCoach())
            ) {
                throw $this->createAccessDeniedException('Access denied.');
            }
        if ($this->isCsrfTokenValid('delete'.$programme->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($programme);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_programme_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/seances', name: 'app_programme_seances', methods: ['GET'])]
    public function showSeances( Programme $programme): Response
    {   $this->denyIfBanned();
        $seances = $programme->getSeances();
        $user = $this->getUser();

        return $this->render('programme/seances.html.twig', [
            'programme' => $programme,
            'seances' => $seances,
            'user' => $user,
        ]);
    }

          private function denyIfBanned()
{
    if ($this->isGranted('ROLE_BANNED')) {
        throw $this->createAccessDeniedException('Vous êtes banni et ne pouvez pas accéder à cette page.');
    }
}

}
