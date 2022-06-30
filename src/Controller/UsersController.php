<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    /**
     * @Route("/{_locale}/users", name="users")
     */
    public function users(Request $request)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('users', array('_locale' => $locales[0]));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $em = $this->container->get('doctrine')->getManager();

        $users = [];
        $usrs = $em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.email <> :this_email')
            ->setParameter('this_email', $this->getUser()->getUserIdentifier())
            ->getQuery()
            ->getResult();
        foreach ($usrs as $usr) {
            $users[$usr->getId()] = $usr;
        }

        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('users', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('users.html.twig', [
            'current_page' => 'users',
            'users' => $users,
            'translated_routes' => $translatedRoutes
        ]);

    }
}
