<?php

namespace App\Controller\backend;

use App\Entity\Utilisateur;
use App\Enums\Role;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboard1Controller extends AbstractController
{
    #[Route('/admin/dashboard12', name: 'app_admin_dashboard1')]
    public function index(UtilisateurRepository $utilisateurRepo, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $selectedYear = $request->query->get('year', date('Y'));
        $users = $utilisateurRepo->findAll();

        // Calculate statistics
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u->isActive()));

        // Role counts
        $clients = count(array_filter($users, fn($u) => $u->getRole() === Role::CLIENT));
        $shopOwners = count(array_filter($users, fn($u) => $u->getRole() === Role::SHOPOWNER));
        $admins = count(array_filter($users, fn($u) => $u->getRole() === Role::ADMIN));

        // Percentages
        $activePercentage = $totalUsers ? round(($activeUsers / $totalUsers) * 100) : 0;
        $clientPercentage = $totalUsers ? round(($clients / $totalUsers) * 100) : 0;
        $shopOwnerPercentage = $totalUsers ? round(($shopOwners / $totalUsers) * 100) : 0;
        $adminPercentage = $totalUsers ? round(($admins / $totalUsers) * 100) : 0;

        // Monthly stats
        $monthlyStats = $this->getMonthlyStats($users, $selectedYear);
        $availableYears = $this->getRegistrationYears($users);

        return $this->render('backend/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'activePercentage' => $activePercentage,
            'clients' => $clients,
            'shopOwners' => $shopOwners,
            'admins' => $admins,
            'clientPercentage' => $clientPercentage,
            'shopOwnerPercentage' => $shopOwnerPercentage,
            'adminPercentage' => $adminPercentage,
            'monthlyStats' => $monthlyStats,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears
        ]);
    }

    private function getMonthlyStats(array $users, string $year): array
    {
        $stats = [];
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = \DateTimeImmutable::createFromFormat('!Y-m', "$year-$month");
            $monthEnd = $monthStart->modify('last day of this month');

            $clients = count(array_filter($users, fn($u) => 
                $u->getRole() === Role::CLIENT &&
                $u->getDateInscription() >= $monthStart &&
                $u->getDateInscription() <= $monthEnd
            ));

            $shopOwners = count(array_filter($users, fn($u) => 
                $u->getRole() === Role::SHOPOWNER &&
                $u->getDateInscription() >= $monthStart &&
                $u->getDateInscription() <= $monthEnd
            ));

            $stats[] = [
                'month' => $months[$month - 1],
                'clients' => $clients,
                'shopOwners' => $shopOwners
            ];
        }
        return $stats;
    }

    private function getRegistrationYears(array $users): array
    {
        $years = [];
        foreach ($users as $user) {
            if ($user->getDateInscription()) {
                $year = $user->getDateInscription()->format('Y');
                $years[$year] = true;
            }
        }
        
        // Add current year and 2025 by default
        $currentYear = date('Y');
        $years[$currentYear] = true;
        $years['2025'] = true;
        
        // Generate sorted list
        $years = array_keys($years);
        rsort($years);
        
        return $years;
    }
}