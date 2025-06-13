<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\PointsTransaction;
use App\Entity\Prize;
use App\Entity\SpinResult;
use App\Repository\CustomerRepository;
use App\Repository\PointsTransactionRepository;
use App\Repository\PrizeRepository;
use App\Repository\SpinResultRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private PrizeRepository $prizeRepository,
        private SpinResultRepository $spinResultRepository,
        private PointsTransactionRepository $pointsTransactionRepository
    ) {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $customersCount = $this->customerRepository->count([]);
        $activePrizesCount = $this->prizeRepository->count(['isActive' => true]);
        $spinsTodayCount = $this->spinResultRepository->count([
            'spunAt' => [
                'gte' => $today,
                'lt' => $tomorrow
            ]
        ]);

        $pointsAwardedToday = $this->pointsTransactionRepository->createQueryBuilder('pt')
            ->select('SUM(pt.amount)')
            ->where('pt.createdAt >= :today')
            ->andWhere('pt.createdAt < :tomorrow')
            ->andWhere('pt.amount > 0')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $recentSpins = $this->spinResultRepository->findBy(
            [],
            ['spunAt' => 'DESC'],
            10
        );

        $recentTransactions = $this->pointsTransactionRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        return $this->render('admin/dashboard.html.twig', [
            'customers_count' => $customersCount,
            'active_prizes_count' => $activePrizesCount,
            'spins_today_count' => $spinsTodayCount,
            'points_awarded_today' => $pointsAwardedToday,
            'recent_spins' => $recentSpins,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Loyalty Reward Engine')
            ->setFaviconPath('favicon.svg');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Customers');
        yield MenuItem::linkToCrud('Customers', 'fa fa-users', Customer::class);

        yield MenuItem::section('Prizes');
        yield MenuItem::linkToCrud('Prizes', 'fa fa-gift', Prize::class);

        yield MenuItem::section('Activity');
        yield MenuItem::linkToCrud('Spin Results', 'fa fa-random', SpinResult::class);
        yield MenuItem::linkToCrud('Points Transactions', 'fa fa-exchange', PointsTransaction::class);
    }
} 