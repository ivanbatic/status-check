<?php

/**
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Library;

use ivanbatic\StatusCheckBundle\Model\CheckBatch;
use ivanbatic\StatusCheckBundle\Document\Check;
use Doctrine\ODM\MongoDB\DocumentManager;

class MongoRouter
{

    /** @var DocumentManager */
    protected $dmon;

    public function __construct(DocumentManager $doctrineMongo)
    {
        $this->dmon = $doctrineMongo;
    }

    public function insertBatch(CheckBatch $batch)
    {
        $hostDocs = [];
        foreach ($batch as $i => $host) {
            $hostDocs[$i] = new Check();
            $hostDocs[$i]->setRequestClient($batch->getRequestClient())
                ->setPageIndex($batch->getPageIndex())
                ->setRequestUrl($host->getOriginal());
            $this->dmon->persist($hostDocs[$i]);
        }
        $this->dmon->flush();
        return $hostDocs;
    }

    public function deleteClientPage($pageIndex, $clientIp)
    {
        if (!is_numeric($pageIndex) || empty($clientIp)) {
            return false;
        }

        $qb = $this->dmon->createQueryBuilder();
        $qb = $qb->remove('ivanbatic\StatusCheckBundle\Document\Check')
                ->field('request_client')->equals($clientIp)
                ->field('page_index')->in([(int)$pageIndex]);
        $qb->getQuery()->execute();
        return true;
    }

}
