<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopStatsController extends AbstractController
{


    #[Route('/admin/shop-stats', name: 'admin_shop_stats')]
    public function shopStats(Request $request, CommandeRepository $commandeRepo): Response
    {
        $dateInput = $request->query->get('date');
        $currentDate = $dateInput ? new \DateTime($dateInput) : new \DateTime();

        $weekStart = (clone $currentDate)->modify('monday this week');
        $weekEnd = (clone $weekStart)->modify('sunday this week');

        // Générer les dates de la semaine pour l'affichage
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = (clone $weekStart)->modify("+$i days");
        }

        $stats = $commandeRepo->findWeeklyShopStatistics($currentDate);

        return $this->render('backend/billing.html.twig', [
            'stats' => $stats,
            'currentDate' => $currentDate,
            'weekDates' => $weekDates,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd
        ]);
    }

    #[Route('/admin/shop-details/{id}', name: 'admin_shop_details')]
    public function shopDetails(int $id, Request $request, CommandeRepository $commandeRepo): Response
    {
        $dateInput = $request->query->get('date');
        $currentDate = $dateInput ? new \DateTime($dateInput) : new \DateTime();

        $weekStart = (clone $currentDate)->modify('monday this week');
        $weekEnd = (clone $weekStart)->modify('sunday this week');

        $dailySales = $commandeRepo->findDailyShopSales($id, $currentDate);

        // Préparer les données pour le graphique
        $salesData = [];
        $currentDate = clone $weekStart;
        while ($currentDate <= $weekEnd) {
            $dateStr = $currentDate->format('Y-m-d');
            $salesData[$dateStr] = 0;
            $currentDate->modify('+1 day');
        }

        foreach ($dailySales as $sale) {
            $salesData[$sale['day']] = $sale['salesCount'];
        }

        return $this->render('backend/billinigDetails.html.twig', [
            'salesData' => $salesData,
            'shopId' => $id,
            'currentDate' => $currentDate,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekDates' => array_keys($salesData)
        ]);
    }
}
