<?php
// src/Command/MqttListenerCommand.php
namespace App\Command;

use App\Service\MqttService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MqttListenerCommand extends Command
{
    protected static $defaultName = 'app:mqtt:listen';
    
    private $mqttService;
    private $entityManager;
    private $logger;
    
    public function __construct(
        MqttService $mqttService, 
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->mqttService = $mqttService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setDescription('Listens to MQTT messages for parking updates');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting MQTT listener...');
        
        $mqtt = new \Bluerhinos\phpMQTT(' 172.20.10.4', 1883, 'SymfonyParkingListener');
        
        if (!$mqtt->connect(true, null, null, null)) {
            $output->writeln('Could not connect to MQTT broker');
            $this->logger->error('Could not connect to MQTT broker');
            return Command::FAILURE;
        }
        
        $topics = [
            'placeparking/status' => [
                'qos' => 0,
                'function' => function($topic, $message) use ($output) {
                    $output->writeln("Message received: $topic - $message");
                    $this->mqttService->processMessage($topic, $message, $this->entityManager);
                }
            ]
        ];
        
        $mqtt->subscribe($topics, 0);
        
        while ($mqtt->proc()) {
            // Keep processing messages
            // Add a small delay to prevent high CPU usage
            usleep(100000); // 100ms
        }
        
        $mqtt->close();
        return Command::SUCCESS;
    }
}