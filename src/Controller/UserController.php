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

class UserController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/{_locale}/user/{id}/{action}", name="user", defaults={ "id"="", "action"="" })
     */
    public function user(Request $request, UserPasswordHasherInterface $passwordHasher, $id, $action)
    {
        $locale = $request->get('_locale');
        $locales = $this->getParameter('locales');
        //Set default locale if locale is missing
        if($locale === null || !in_array($locale, $locales)) {
            return $this->redirectToRoute('user', array('_locale' => $locales[0], 'id' => $id, 'action' => $action));
        }
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $em = $this->container->get('doctrine')->getManager();

        $user = new User();
        if (!empty($id)) {
            $user = null;
            $users = $em->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->where('u.id = :id')
                ->setParameter('id', $id)
                ->orderBy('u.fullName')
                ->getQuery()
                ->getResult();
            foreach ($users as $usr) {
                $user = $usr;
            }
        }

        if($action == 'delete' && !empty($id) && $user != null) {
            $em->remove($user);
            $em->flush();
            return $this->redirectToRoute('users');
        } else {
            $t = $this->translator;
            $form = $this->createFormBuilder($user)
                ->add('fullName', TextType::class, ['label' => $t->trans('Name'), 'attr' => ['placeholder' => $t->trans('Name of the user')]])
                ->add('email', TextType::class, ['label' => $t->trans('E-mail'), 'attr' => ['placeholder' => $t->trans('contact@example.com')]])
                ->add('plainPassword', PasswordType::class, ['always_empty' => true, 'mapped' => false, 'required' => false, 'label' => $t->trans(empty($id) ? 'Password' : 'New password')])
                ->add('submit', SubmitType::class, ['label' => $t->trans('Save')])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                $plainPassword = $form->get('plainPassword')->getData();
                if(!empty($plainPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword(
                        $formData,
                        $plainPassword
                    );
                    $formData->setPassword($hashedPassword);
                } else {
                    $formData->setPassword($user->getPassword());
                }
                $em->persist($formData);
                $em->flush();
                return $this->redirectToRoute('users');
            }

            $translatedRoutes = array();
            foreach($locales as $l) {
                $translatedRoutes[] = array(
                    'lang' => $l,
                    'url' => $this->generateUrl('user', array('_locale' => $l, 'id' => $id, 'action' => $action)),
                    'active' => $l === $locale
                );
            }

            return $this->render('user.html.twig', [
                'current_page' => 'users',
                'new' => empty($id),
                'form' => $form->createView(),
                'translated_routes' => $translatedRoutes
            ]);
        }
    }
}
