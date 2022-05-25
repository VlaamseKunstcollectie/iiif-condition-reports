<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DisclaimerController extends AbstractController
{
    /**
     * @Route("/{_locale}/disclaimer", name="disclaimer")
     */
    public function disclaimer(Request $request)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('disclaimer', array('_locale' => $locales[0]));
        }

        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('disclaimer', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('disclaimer.html.twig', [
            'current_page' => 'disclaimer',
            'translated_routes' => $translatedRoutes
        ]);
    }
}
