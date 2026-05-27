<?php

namespace App\Command;

use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-villes',
    description: 'Importe les villes de France et leurs codes postaux depuis un fichier CSV.',
)]
class ImportVillesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Chemin vers ton fichier CSV (dans /data/villes.csv)
        $csvPath = $this->params->get('kernel.project_dir').'/data/villes.csv';

        if (!file_exists($csvPath)) {
            $io->error(sprintf('Le fichier n\'existe pas à l\'emplacement : %s', $csvPath));

            return Command::FAILURE;
        }

        $io->title('Début de l\'importation des villes françaises...');

        if (($handle = fopen($csvPath, 'r')) !== false) {
            // On ignore la première ligne d'en-tête (name;zipCode)
            fgetcsv($handle, 1000, ';');

            $count = 0;

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                // $data[0] = Nom de la ville (ex: AMBERIEU EN BUGEY)
                // $data[1] = Code postal (ex: 01500)
                if (isset($data[0]) && isset($data[1])) {
                    // Nettoyage des espaces blancs autour du texte
                    $cityName = trim($data[0]);
                    $zipCode = trim($data[1]);

                    $city = new City();
                    $city->setName($cityName);
                    $city->setZipCode($zipCode);

                    $this->em->persist($city);
                    ++$count;

                    // Batch processing : On envoie en BDD toutes les 500 lignes
                    if (0 === $count % 500) {
                        $this->em->flush();
                        $this->em->clear(); // Vide la mémoire vive (RAM) de Symfony pour éviter les surcharges
                        $io->text(sprintf('%d villes analysées...', $count));
                    }
                }
            }

            // On vide les dernières lignes restantes qui n'ont pas atteint le palier de 500
            $this->em->flush();
            fclose($handle);

            $io->success(sprintf('Succès ! %d lignes de villes ont été importées avec succès !', $count));

            return Command::SUCCESS;
        }

        $io->error('Impossible d\'ouvrir le fichier CSV.');

        return Command::FAILURE;
    }
}
