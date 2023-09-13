<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Operation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnalyticsController extends AbstractController
{

    public array $date_types = [
        'this-month', 'last-month', 'this-year', 'last-year', 'all'
    ];

    #[Route('/analytics', name: 'app_analytics')]
    public function index(): Response
    {
        return $this->render('analytics/index.html.twig', [
            'controller_name' => 'AnalyticsController',
        ]);
    }
    
    #[Route('/analytics/income', name: 'app_analytics_income')]
    public function income(Request $request, EntityManagerInterface $em): Response
    {
        return $this->overview(1, $request, $em);
    }

    #[Route('/analytics/expensis', name: 'app_analytics_expensis')]
    public function expensis(Request $request, EntityManagerInterface $em): Response
    {
        return $this->overview(0, $request, $em);
    }

    public function overview(int $type, Request $request, EntityManagerInterface $em) : Response
    {
        $date_type = 'this-month';
        $dataPoints = [];
        if($request->request->has('date_type'))
        {
            $new_date_type = $request->request->get('date_type');
            if(in_array($new_date_type, $this->date_types))
            {
                $date_type = $new_date_type;
            }
        }

        if($request->getSession()->has('active_account_id'))
        {
            $range = $this->getDateRangeByType($date_type);
            $account = $em->getRepository(Account::class)->findOneBy([
                'id' => $request->getSession()->get('active_account_id')
            ]);
            $or = $em->getRepository(Operation::class);
            $data = $or->getTotalValueByTypeWithCategory($account, $type, $range['start_date'], $range['end_date']);
        }

        $total = 0;
        if($data)
        {
            foreach($data as $row)
            {
                $total += $row['value'];
            }

            $index = 0;
            foreach($data as $row)
            {
                $dataPoints[$index]['label'] = $row['name'];
                $dataPoints[$index]['y'] = $row['value'] * 100 / $total;
                $index++;
            }
        }

        // dd(json_encode($dataPoints));
        return $this->render('analytics/overview.html.twig', [
            'controller_name' => 'AnalyticsController',
            'data' => $data,
            'type' => 1,
            'dataPoints' => $dataPoints,
            'total' => $total,
            'date_types' => $this->date_types,
            'current_date_type' => $date_type,
        ]);   
    }

    public function getDateRangeByType(string $type)
    {
        $range = [];
        switch($type)
        {
            case 'this-month':
                $range['start_date'] = date('Y-m-1');
                $range['end_date'] = date('Y-m-1', strtotime(' + 1 month'));
                break;
            case 'last-month':
                $range['start_date'] = date('Y-m-1', strtotime(' - 1 month'));
                $range['end_date'] = date('Y-m-1');
                break;
            case 'this-year':
                $range['start_date'] = date('Y-1-1');
                $range['end_date'] = date('Y-1-1', strtotime(' + 1 year'));
                break;
            case 'last-year':
                $range['start_date'] = date('Y-1-1', strtotime(' - 1 year'));
                $range['end_date'] = date('Y-1-1');
                break;
            case 'all':
                $range['start_date'] = '';
                $range['end_date'] = '';
                break;
            default:
                $range['start_date'] = '';
                $range['end_date'] = '';
        }

        return $range;
    }
}
