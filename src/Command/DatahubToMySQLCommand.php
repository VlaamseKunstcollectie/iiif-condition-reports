<?php

namespace App\Command;

use App\Entity\DatahubData;
use App\Utils\StringUtil;
use DOMDocument;
use DOMXPath;
use Phpoaipmh\Endpoint;
use Phpoaipmh\Exception\HttpException;
use Phpoaipmh\Exception\OaipmhException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatahubToMySQLCommand extends Command implements ContainerAwareInterface, LoggerAwareInterface
{
    private $datahubUrl;
    private $datahubLanguage;
    private $namespace;
    private $metadataPrefix;
    private $dataDefinition;

    private $verbose;

    protected function configure()
    {
        $this
            ->setName('app:datahub-to-mysql')
            ->addArgument('url', InputArgument::OPTIONAL, 'The URL of the Datahub')
            ->setDescription('')
            ->setHelp('');
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verbose = $input->getOption('verbose');

        $this->datahubUrl = $input->getArgument('url');
        if (!$this->datahubUrl) {
            $this->datahubUrl = $this->container->getParameter('datahub_url');
        }

        $this->datahubLanguage = $this->container->getParameter('datahub_language');
        $this->namespace = $this->container->getParameter('datahub_namespace');
        $this->metadataPrefix = $this->container->getParameter('datahub_metadataprefix');
        $this->dataDefinition = $this->container->getParameter('datahub_data_definition');

        $em = $this->container->get('doctrine')->getManager();
        //Disable SQL logging to improve performance
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->storeAllDatahubData($em);

        return 0;
    }

    function storeAllDatahubData($em)
    {
        $qb = $em->createQueryBuilder();
        $qb->delete(DatahubData::class, 'data')->getQuery()->execute();
        $em->flush();

        try {
            $datahubEndpoint = Endpoint::build($this->datahubUrl . '/oai');
            $records = $datahubEndpoint->listRecords($this->metadataPrefix);
            $n = 0;
            foreach($records as $record) {
                $id = null;
                $datahubData = array();

                $data = $record->metadata->children($this->namespace, true);
                $recordId = trim($record->header->identifier);

                if($this->verbose) {
                    $n++;
                    if($n % 1000 == 0) {
//                        echo 'At ' . $n . ' datahub records.' . PHP_EOL;
                        $this->logger->info('At ' . $n . ' datahub records.');
                    }
                }

                $domDoc = new DOMDocument;
                $domDoc->loadXML($data->asXML());
                $xpath = new DOMXPath($domDoc);

                foreach ($this->dataDefinition as $key => $dataDef) {
                    if(!array_key_exists('field', $dataDef)) {
                        continue;
                    }
                    $xpaths = array();
                    if(array_key_exists('xpaths', $dataDef)) {
                        $xpaths = $dataDef['xpaths'];
                    } else if(array_key_exists('xpath', $dataDef)) {
                        $xpaths[] = $dataDef['xpath'];
                    }
                    $value = null;
                    foreach($xpaths as $xpath_) {
                        $query = $this->buildXpath($xpath_, $this->datahubLanguage, $this->namespace);
                        $extracted = $xpath->query($query);
                        if ($extracted) {
                            if (count($extracted) > 0) {
                                foreach ($extracted as $extr) {
                                    if ($extr->nodeValue !== 'n/a') {
                                        if($value == null) {
                                            $value = $extr->nodeValue;
                                        }
                                        else if($key != 'keywords' || !in_array($extr->nodeValue, explode(",", $value))) {
                                            $value .= ', ' . $extr->nodeValue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($value != null) {
                        $value = trim($value);
                        if($dataDef['field'] == 'id') {
                            $id = $value;
                        } else {
                            $datahubData[$dataDef['field']] = $value;
                        }
                    }
                }

                if($id != null) {
                    // Combine earliest and latest date into one
                    if(array_key_exists('earliestdate', $datahubData)) {
                        if(array_key_exists('latestdate', $datahubData)) {
                            $datahubData['datecreatedofartwork'] = StringUtil::getDateRange($datahubData['earliestdate'], $datahubData['latestdate']);
                            unset($datahubData['latestdate']);
                        } else {
                            $datahubData['datecreatedofartwork'] = StringUtil::getDateRange($datahubData['earliestdate'], $datahubData['earliestdate']);
                        }
                        unset($datahubData['earliestdate']);
                    } else if(array_key_exists('latestdate', $datahubData)) {
                        $datahubData['datecreatedofartwork'] = StringUtil::getDateRange($datahubData['latestdate'], $datahubData['latestdate']);
                        unset($datahubData['latestdate']);
                    }
                    // Combine role and creator name
                    if(array_key_exists('roleofcreatorofartworkobje', $datahubData)) {
                        if(array_key_exists('creatorofartworkobje', $datahubData)) {
                            $datahubData['creatorofartworkobje'] = ucfirst($datahubData['roleofcreatorofartworkobje']) . ': ' . $datahubData['creatorofartworkobje'];
                        }
                        unset($datahubData['roleofcreatorofartworkobje']);
                    }
                    if(!array_key_exists('creatorofartworkobje', $datahubData)) {
                        $datahubData['creatorofartworkobje'] = '';
                    }
                    // Delete any data that might already exist for this inventory number
                    $query = $qb->delete(DatahubData::class, 'data')
                        ->where('data.id = :id')
                        ->setParameter('id', $id)
                        ->getQuery();
                    $query->execute();
                    $em->flush();

                    $datahubData['dh_record_id'] = $recordId;
                    //Store all relevant Datahub data in mysql
                    foreach($datahubData as $key => $value) {
                        $data = new DatahubData();
                        $data->setId($id);
                        $data->setName($key);
                        $data->setValue($value);
                        $em->persist($data);
                    }
                    $em->flush();
                    $em->clear();
                }
            }
//            var_dump($relations);
        }
        catch(OaipmhException $e) {
//            echo 'OAI-PMH error: ' . $e . PHP_EOL;
            $this->logger->error('OAI-PMH error: ' . $e);
        }
        catch(HttpException $e) {
//            echo 'OAI-PMH error: ' . $e . PHP_EOL;
            $this->logger->error('OAI-PMH error: ' . $e);
        }
    }

    // Build the xpath based on the provided namespace
    private function buildXPath($xpath, $language, $namespace)
    {
        $prepend = '';
        if(strpos($xpath, '(') === 0) {
            $prepend = '(';
            $xpath = substr($xpath, 1);
        }
        $xpath = str_replace('{language}', $language, $xpath);
        $xpath = preg_replace('/\[@(?!xml)/', '[@' . $namespace . ':${1}', $xpath);
        $xpath = preg_replace('/\(@(?!xml)/', '(@' . $namespace . ':${1}', $xpath);
        $xpath = preg_replace('/\[(?![@0-9]|not\()/', '[' . $namespace . ':${1}', $xpath);
        $xpath = preg_replace('/\/([^\/])/', '/' . $namespace . ':${1}', $xpath);
        $xpath = preg_replace('/ and (?!@xml)/', ' and ' . $namespace . ':${1}', $xpath);
        if(strpos($xpath, '/') !== 0) {
            $xpath = $namespace . ':' . $xpath;
        }
        $xpath = 'descendant::' . $xpath;
        $xpath = $prepend . $xpath;
        return $xpath;
    }
}
