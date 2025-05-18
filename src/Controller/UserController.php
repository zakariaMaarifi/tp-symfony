<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Coach;
use App\Form\UserForm;
use App\Repository\UserRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {   $this->denyIfBanned();
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
        if ($user->isCoach()){
            $coach = new Coach();
            $coach->setUser($user);
            $coach->setNom($user->getNom());
            $coach->setPrenom($user->getPrenom());
            $coach->setSpecialite("");
            $entityManager->persist($coach);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {   $this->denyIfBanned();
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {   $this->denyIfBanned();
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->isCoach()) {
            $coach = $entityManager->getRepository(Coach::class)->findOneBy(['User' => $user]);
            if ($coach) {
                $coach->setNom($user->getNom());
                $coach->setPrenom($user->getPrenom());
                $entityManager->persist($coach);
            }
                                }
            else{
            $coach = $entityManager->getRepository(Coach::class)->findOneBy(['User' => $user]);
            if ($coach) {
                $entityManager->remove($coach);
            }
                }
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {   $this->denyIfBanned();
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            if ($user->isCoach()) {
                $userId = $user->getId();
                $coach = $entityManager->getRepository(Coach::class)->findOneBy(['User' => $userId]);
            }
                if ($coach) {
                    $entityManager->remove($coach);
                }

            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/seances', name: 'app_user_seances', methods: ['GET'])]
    public function showSeances( User $user): Response
    {   $this->denyIfBanned();
        $seances = $user->getSeances();
        return $this->render('user/seances.html.twig', [
            'user' => $user,
            'seances' => $seances,
        ]);
    }


    #[Route('/{id}/reserver/{seanceId}', name: 'app_user_reserver', methods: ['POST'])]
    public function reserver(Request $request,User $user,SeanceRepository $seanceRepository,EntityManagerInterface $entityManager,int $seanceId): Response
     {  $this->denyIfBanned();
        if ($this->isCsrfTokenValid('reserver' . $seanceId, $request->get('_token'))) {
            $seance = $seanceRepository->find($seanceId);

            if (!$seance) {
                throw $this->createNotFoundException('Séance introuvable');
            }

            $user->addSeance($seance);
            $entityManager->flush();

            $this->addFlash('success', 'Séance réservée avec succès !');
        }

        return $this->redirectToRoute('app_user_seances', ['id' => $user->getId()]);
    }

    private function denyIfBanned()
{
    if ($this->isGranted('ROLE_BANNED')) {
        throw $this->createAccessDeniedException('Vous êtes banni et ne pouvez pas accéder à cette page.');
    }
}

}
