<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Entity\Representative;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RepresentativesController extends AbstractController
{
    /**
     * @Route("/{_locale}/representatives", name="representatives")
     */
    public function representatives(Request $request)
    {
        $em = $this->container->get('doctrine')->getManager();

        $organisationNames = [];
        $orgs = $em->createQueryBuilder()
            ->select('o')
            ->from(Organisation::class, 'o')
            ->getQuery()
            ->getResult();
        foreach ($orgs as $org) {
            $organisationNames[$org->id] = $org->alias;
        }

        $searchResults = array();
        $representatives = $em->createQueryBuilder()
            ->select('r')
            ->from(Representative::class, 'r')
            ->orderBy('r.organisation, r.alias')
            ->getQuery()
            ->getResult();
        foreach ($representatives as $representative) {
            $searchResults[] = $representative;
        }

        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('representatives', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('representatives.html.twig', [
            'current_page' => 'representatives',
            'representatives' => $searchResults,
            'organisation_names' => $organisationNames,
            'translated_routes' => $translatedRoutes
        ]);

    }
}
