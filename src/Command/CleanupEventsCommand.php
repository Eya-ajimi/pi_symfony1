<?php
namespace App\Command;

use App\Repository\EventRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanupEventsCommand extends Command
{
    protected static $defaultName = 'app:cleanup-events';
    
    private $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Deletes events that have already ended');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $deletedCount = $this->eventRepository->deletePastEvents();
        
        $io->success(sprintf('Deleted %d past events', $deletedCount));
        
        return Command::SUCCESS;
    }
}