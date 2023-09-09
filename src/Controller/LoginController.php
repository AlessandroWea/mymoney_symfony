<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\User;
use App\Form\LoginFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(Request $request,
                        UserPasswordHasherInterface $userPasswordHasher,
                        Security $security,
                        EntityManagerInterface $em,
                        Session $session): Response
    {
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $em->getRepository(User::class)->findOneBy([
                'email' => $data['email'],
            ]);

            if($user){
                if($userPasswordHasher->isPasswordValid($user, $data['password'])){
                    $security->login($user);
                    // $account = $em->getRepository(Account::class)->findOneBy([
                    //     'email' => $data['email'],
                    // ]);
                    $account = $user->getAccounts()[0];
                    $session->set('active_account_id', $account->getId());
                    return $this->redirectToRoute('app_main');
                }
            }

            $form->get('email')->addError(new FormError('Invalid credentials!'));
        }

        return $this->render('login/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Security $security)
    {
        $response = $security->logout();
    }
}
