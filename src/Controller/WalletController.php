<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\AddAccountFormType;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class WalletController extends AbstractController
{
    #[Route('/wallet', name: 'app_wallet')]
    public function index(AccountRepository $ar): Response
    {
        $accounts = $ar->findBy([
            'user' => $this->getUser(),
        ]);
        $net = 0;
        foreach($accounts as $account)
        {
            $net += $account->getValue();
        }

        return $this->render('wallet/index.html.twig', [
            'net' => $net,
            'accounts' => $accounts,
            'controller_name' => 'WalletController',
        ]);
    }

    #[Route('/wallet/edit/{account}', name: 'app_wallet_edit')]
    public function edit(Request $request, Account $account, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AddAccountFormType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($account);
            $em->flush();

            return $this->redirectToRoute('app_wallet');
        }

        return $this->render('wallet/edit.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit account',
        ]);
    }

    #[Route('/wallet/delete/{account}', name: 'app_wallet_delete')]
    public function delete(Session $session, Account $account, EntityManagerInterface $em): Response
    {
        $id = $account->getId();
        // dd($session->get('active_account_id') == $id);
        if($session->get('active_account_id') == $id)
        {
            $session->set('active_account_id',  null);
        }

        $em->remove($account);
        $em->flush();

        if($session->get('active_account_id') == null)
        {
            $acc = $em->getRepository(Account::class)->findOneBy(['user' => $this->getUser()]);
            if($acc)
                $session->set('active_account_id', $acc->getId());
            else
                $session->set('active_account_id', null);

        }
        
        return $this->redirectToRoute('app_wallet');
    }

    #[Route('/wallet/switch/{account}', name: 'app_wallet_switch')]
    public function switch(Session $session, Account $account): Response
    {
        $session->set('active_account_id', $account->getId());
        return $this->redirectToRoute('app_wallet');
    }

    #[Route('/wallet/add', name: 'app_wallet_add')]
    public function add(Session $session, Request $request, EntityManagerInterface $em): Response
    {
        $account = new Account;
        $form = $this->createForm(AddAccountFormType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $account->setUser($this->getUser());
            $em->persist($account);
            $em->flush();

            if($session->get('active_account_id') == null)
            {
                $session->set('active_account_id', $account->getId());
            }

            return $this->redirectToRoute('app_wallet');
        }
        return $this->render('wallet/edit.html.twig', [
            'form' => $form->createView(),
            'title' => 'Add account',
        ]);
    }
}
