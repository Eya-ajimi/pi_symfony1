<?php
namespace App\Controller\maria;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications', name: 'api_notifications')]
    public function getNotifications(EntityManagerInterface $em): JsonResponse
    {

        $notifications = $em->getRepository(Notification::class)
            ->findUnreadByUser($this->getUser(), 10);
        return $this->json(array_map(function ($n) {
            return [
                'id' => $n->getId(),
                'message' => $n->getMessage(),
                'time' => $n->getCreatedAt()->format('H:i')
            ];
        }, $notifications));
    }
    // src/Controller/NotificationController.php
    #[Route('/api/notifications/mark-read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $notificationId = $data['id'] ?? null;

        if (!$notificationId) {
            return $this->json(['error' => 'Notification ID required'], 400);
        }

        $notification = $em->getRepository(Notification::class)->findOneBy([
            'id' => $notificationId,
            'user' => $user
        ]);

        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        $notification->markAsRead();
        $em->flush();

        return $this->json(['status' => 'success']);
    }
}