<?php

namespace App\Controller;

use App\Entity\User;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use \Swift_Mailer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        try{        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        }
        catch(Exception $e){        return $this->render('user/index.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void

    {   
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
  
    #[Route(path: '/forgot-password', name: 'forgot_password')]

    public function forgotPassword(Request $request, UserPasswordEncoderInterface $encoder, \Swift_Mailer $mailer)
    {
        
        $form = $this->createFormBuilder()
        ->add('email', EmailType::class)
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $email = $data['email'];

        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getRepository(User::class)->findOneByEmail($email);

        if ($user) {
            // Generate a random token
            $token = bin2hex(random_bytes(16));
            $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            // Set the token expiration date to 1 hour from now
            $expiresAt = new \DateTime();
            $expiresAt->add(new \DateInterval('PT1H'));

            // Store the token and expiration date in the database
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expiresAt);
            $entityManager->flush();

            // Send a password reset email to the user
            $message = (new \Swift_Message('Password Reset'))
                ->setFrom('ferjaniwael20@gmail.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'emails/reset_password.html.twig',
                        ['url' => $url]
                    ),
                    'text/html'
                );

            $mailer->send($message);

            $this->addFlash('success', 'Check your email for instructions to reset your password.');

            return $this->redirectToRoute('app_login');
        } else {
            $this->addFlash('error', 'Email not found.');
        }
    }

    return $this->render('security/forgot_password.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route(path: '/reset-password/{token}', name: 'reset_password')]

    public function resetPassword(Request $request, string $token, UserPasswordEncoderInterface $encoder): Response
    {   
        $entityManager = $this->getDoctrine()->getManager();
        $tempUser = new User();
        $tempUser->setResetToken($token);
        
        $user = $entityManager->getRepository(User::class)->findOneByResetToken($token);
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Password reset token invalid or expired.');
            return $this->redirectToRoute('forgot_password');
        }

        $form = $this->createFormBuilder()
            ->add('password', PasswordType::class)
            ->add('confirm_password', PasswordType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['password'] === $data['confirm_password']) {
                $user->setPassword($encoder->encodePassword($user, $data['password']));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $entityManager->flush();

                $this->addFlash('success', 'Your password has been reset.');

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Passwords do not match.');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}      

