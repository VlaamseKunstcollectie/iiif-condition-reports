<?php

namespace App\Controller;

use App\Entity\DatahubData;
use App\Entity\InventoryNumber;
use App\Entity\Organisation;
use App\Entity\Report;
use App\Entity\Representative;
use App\Entity\User;
use App\Utils\IIIFUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/{_locale}/profile", name="profile")
     */
    public function profile(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('profile', array('_locale' => $locales[0]));
        }

        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $t = $this->translator;
        $form = $this->createFormBuilder($this->getUser())
            ->add('plainPassword', PasswordType::class, ['always_empty' => true, 'mapped' => false, 'required' => false, 'label' => $t->trans('New password')])
            ->add('submit', SubmitType::class, ['label' => $t->trans('Save')])
            ->getForm();
        $form->handleRequest($request);
        $message = null;
        $error = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $plainPassword = $form->get('plainPassword')->getData();
            if(!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $formData,
                    $plainPassword
                );
                $formData->setPassword($hashedPassword);
                $em = $this->container->get('doctrine')->getManager();
                $em->persist($formData);
                $em->flush();
                $message = $t->trans('Password successfully updated.');
            } else {
                $error = $t->trans('Error: password cannot be empty.');
            }
        }

        $translatedRoutes = array();
        foreach($locales as $l) {
            $translatedRoutes[] = array(
                'lang' => $l,
                'url' => $this->generateUrl('profile', array('_locale' => $l)),
                'active' => $l === $locale
            );
        }

        return $this->render('profile.html.twig', [
            'message' => $message,
            'error' => $error,
            'current_page' => 'profile',
            'form' => $form->createView(),
            'translated_routes' => $translatedRoutes
        ]);
    }
}
