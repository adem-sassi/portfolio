<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProjectType;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class UserController extends AbstractController
{
    #[Route('/create1', name: 'app_user_create')]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine, UserAuthenticatorInterface $userAuthenticator, FormLoginAuthenticator $formLoginAuthenticator): Response
    {
        $user = new User($passwordHasher);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(["ROLE_USER"]);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $userAuthenticator->authenticateUser($user, $formLoginAuthenticator, $request);

            return $this->redirectToRoute('projects');
        }
        return $this->render('security/user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users', name:"users")]
    public function users(ManagerRegistry $doctrine)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $userRepository = $doctrine->getRepository(User::class);
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/{role}')]
    public function setRole(User $user, string $role, ManagerRegistry $doctrine)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user->setRoles([$role]);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('users');
    }
}
