<?php
// src/Service/MqttService.php
namespace App\Service;

use Bluerhinos\phpMQTT;
use App\Entity\PlaceParking;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MqttService
{
    private $logger;
    private $mqttServer;
    private $mqttPort;
    
    public function __construct(LoggerInterface $logger, string $mqttServer = ' 172.20.10.4', int $mqttPort = 1883)
    {
        $this->logger = $logger;
        $this->mqttServer = $mqttServer;
        $this->mqttPort = $mqttPort;
    }

    public function processMessage(string $topic, string $message, EntityManagerInterface $entityManager): void
    {
        try {
            $this->logger->info(sprintf('Processing MQTT message: %s - %s', $topic, $message));
            
            // Parse the message (format: "spot_id:status")
            $parts = explode(':', $message);
            if (count($parts) !== 2) {
                $this->logger->error('Invalid MQTT message format: ' . $message);
                return;
            }

            $spotId = (int)$parts[0];
            $status = trim(strtolower($parts[1]));

            // Validate status
            $validStatuses = ['free', 'taken', 'reserved'];
            if (!in_array($status, $validStatuses)) {
                $this->logger->error('Invalid parking status: ' . $status);
                return;
            }

            // Update the parking spot
            $spot = $entityManager->getRepository(PlaceParking::class)->find($spotId);
            if (!$spot) {
                $this->logger->error('Parking spot not found: ' . $spotId);
                return;
            }

            // Only update if status has changed
            if ($spot->getStatut() !== $status) {
                $spot->setStatut($status);
                $entityManager->persist($spot);
                $entityManager->flush();
                $this->logger->info(sprintf('Updated spot %d from %s to %s', 
                    $spotId, $spot->getStatut(), $status));
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing MQTT message: ' . $e->getMessage());
        }
    }
}