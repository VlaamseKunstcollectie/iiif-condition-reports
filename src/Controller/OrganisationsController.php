<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganisationsController extends AbstractController
{
    /**
     * @Route("/{_locale}/organisations", name="organisations")
     */
    public function organisations(Request $request)
    {
        $em = $this->container->get('doctrine')->getManager();

        $searchResults = array();
        $organisations = $em->createQueryBuilder()
            ->select('o')
            ->from(Organisation::class, 'o')
            ->orderBy('o.alias')
            ->getQuery()
            ->getResult();
        foreach ($organisations as $organisation) {
            $searchResults[] = $organisation;
        }

        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('organisations', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('organisations.html.twig', [
            'current_page' => 'organisations',
            'organisations' => $searchResults,
            'translated_routes' => $translatedRoutes
        ]);

    }
}
