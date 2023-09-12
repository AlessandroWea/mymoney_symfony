<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Operation;
use App\Form\AddOperationType;
use App\Repository\AccountRepository;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(Request $request,
                        Session $session,
                        AccountRepository $ar,
                        OperationRepository $or): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $date = $session->get('active_date') ?? date('Y-m-d');
        if($request->request->has('date'))
        {
            $date = $request->request->get('date');
            $session->set('active_date', $date);
        }

        $end_date = date('Y-m-d', strtotime('+ 1 day', strtotime($date)));

        $activeAccountId = $session->get('active_account_id');
        $account = $ar->findOneBy(['id'=>$activeAccountId]);
        $operations = [];
        $total_income = 0;
        $total_expensis = 0;

        if($account)
        {
            $operations = $or->getOperationPaginatorByDateAndAccount($account, $page, $date, $end_date);

            $total_income = $or->getTotalValueByType($account, 1, $date, $end_date ) ?? 0;
            $total_expensis = $or->getTotalValueByType($account, 0, $date, $end_date) ?? 0;
        }

        return $this->render('main/index.html.twig', [
            'date' => $date,
            'operations' => $operations,
            'prev_page' => max(1, $page - 1),
            'next_page' => $page + 1,
            'total_income' => $total_income,
            'total_expensis' => $total_expensis,
        ]);
    }

    #[Route('/add', name: 'app_add_operation')]
    public function add(Request $request,
                        EntityManagerInterface $entityManager): Response
    {
        $account_id = $request->getSession()->get('active_account_id');
        if($account_id == null)
            return $this->redirectToRoute('app_main');

        $operation = new Operation;
        $form = $this->createForm(AddOperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $account = $entityManager->getRepository(Account::class)->findOneBy(['id' => $account_id]);
            $operation->setAccount($account);
            $entityManager->persist($operation);
            $entityManager->flush();

            return $this->redirectToRoute('app_main');
        }

        return $this->render('main/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{operation}', name: 'app_edit_operation')]
    public function edit(Operation $operation, Request $request,
                        EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AddOperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($operation);
            $entityManager->flush();

            return $this->redirectToRoute('app_main');
        }

        return $this->render('main/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{operation}', name: 'app_delete_operation')]
    public function delete(Operation $operation, Request $request,
                        EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($operation);
        $entityManager->flush();
        return $this->redirectToRoute('app_main');
    }
}
