<?php

namespace App\Controller;

use App\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdController extends AbstractController
{
    #[Route('/ad', name: 'app_ad')]
   
    public function index(): Response
    {
        return $this->render('ad/index.html.twig', [
            'controller_name' => 'AdController',
        ]);
    }
   
     
    #[Route('/admin/unconfirmed-users', name: 'app_admin_unconfirmed_users')]
    public function unconfirmedUsers(): Response
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findBy([
            'isVerified' => false,
        ]);

        return $this->render('unconfirmed_users.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/admin/confirm-user/{id}',name:"app_admin_confirm_user", methods:("POST"))]
    public function confirmUser(User $user): Response
    {
        // Set the isConfirmed field to true
        $user->setIsVerified(true);

        // Persist the user entity
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // Redirect to a success page
        $this->addFlash('success', sprintf('User %s has been confirmed.', $user->getEmail()));

        return $this->redirectToRoute('app_admin_unconfirmed_users');
    }
   
    #[Route('/admin/reject-user/{id}',name:"app_admin_reject_user", methods:("POST"))]

    public function rejectUser(User $user): Response
    {
        // Delete the user entity
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

        // Redirect to a success page
        $this->addFlash('success', sprintf('User %s has been rejected.', $user->getEmail()));

        return $this->redirectToRoute('app_admin_unconfirmed_users');
    }
}

