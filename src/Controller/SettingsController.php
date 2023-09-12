<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings')]
    public function settings(Request $request,
                        EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditUserFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            if($data['email'] != $user->getEmail())
            {
                if($entityManager->getRepository(User::class)->findOneBy([
                    'email' => $data['email'],
                ]))
                {
                    $form->get('email')->addError(new FormError('This email is already taken!'));
                }
                else
                {
                    $user->setEmail($data['email']);
                }
            }

            if(!empty($data['password']) && !empty($data['password_repeat']))
            {
                if($data['password'] == $data['password_repeat'])
                {
                    $user->setPassword($userPasswordHasher->hashPassword($user,$data['password']));               
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('settings/index.html.twig', [
            'form' => $form->createView(),
        ]);    
    }
}
