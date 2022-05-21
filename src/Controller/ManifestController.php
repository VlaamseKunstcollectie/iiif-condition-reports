<?php

namespace App\Controller;

use App\Entity\IIIFManifest;
use App\Utils\Authenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ManifestController extends AbstractController
{
    /**
     * @Route("/iiif/3/{manifestId}/manifest.json", name="manifest")
     */
    public function manifestAction(Request $request, $manifestId = '')
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        // Make sure the service URL name ends with a trailing slash
        $baseUrl = rtrim($this->getParameter('service_url'), '/') . '/';

        $em = $this->container->get('doctrine')->getManager();
        if($request->getMethod() == 'HEAD') {
            $ids = $em->createQueryBuilder()
                ->select('m.id')
                ->from(IIIFManifest::class, 'm')
                ->where('m.id = :id')
                ->setParameter('id', $baseUrl . $manifestId . '/manifest.json')
                ->getQuery()
                ->getResult();
            if(count($ids) > 0) {
                return new Response('', 200);
            } else {
                return new Response('', 404);
            }
        } else {
            $manifest = null;
            $manifests = $em->createQueryBuilder()
                ->select('m')
                ->from(IIIFManifest::class, 'm')
                ->where('m.manifestId = :id')
                ->setParameter('id', $baseUrl . $manifestId . '/manifest.json')
                ->getQuery()
                ->getResult();
            foreach($manifests as $mani) {
                $manifest = $mani;
            }
            if ($manifest == null) {
                return new Response('Sorry, the requested document does not exist.', 404);
            } else {
                $headers = array(
                    'Content-Type' => 'application/json',
                    'Access-Control-Allow-Origin' => '*'
                );
                $data = json_decode($manifest->getData(), true);
                return new Response(json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE), 200, $headers);
            }
        }
    }
}
