<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organization;
use App\Entity\Report;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationsController extends AbstractController
{
    /**
     * @Route("/organizations", name="organizations")
     */
    public function organizations(Request $request)
    {
        $em = $this->container->get('doctrine')->getManager();

        $searchResults = array();
        $organizations = $em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->orderBy('o.alias')
            ->getQuery()
            ->getResult();
        foreach ($organizations as $organization) {
            $searchResults[] = $organization;
        }

        return $this->render('organizations.html.twig', [
            'current_page' => 'organizations',
            'organizations' => $searchResults
        ]);

    }
}
