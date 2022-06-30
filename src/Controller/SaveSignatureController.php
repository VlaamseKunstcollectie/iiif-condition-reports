<?php

namespace App\Controller;

use App\Entity\Signature;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SaveSignatureController extends AbstractController
{
    /**
     * @Route("/{_locale}/save_signature/{reportId}/{name}/{role}", name="save_signature")
     */
    public function saveSignature(Request $request, $reportId, $name, $role)
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('main');
        } else if(!$this->getUser()->getRoles()) {
            return $this->redirectToRoute('main');
        } else if (!in_array('ROLE_USER', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('main');
        }

        $file = $request->files->get('signature-file');
        if($file == null) {
            $response = new Response(json_encode(array('image' => null)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            $extension = $file->guessClientExtension();
            if ($extension == null) {
                $extension = 'png';
            }
            $folder = 'signature_images';
            $filename = round(microtime(true) * 1000) . '.' . $extension;
            if (!is_dir($folder)) {
                mkdir($folder);
            }
            $file->move($folder, $filename);

            $signature = new Signature();
            $signature->setReportId($reportId);
            $timestamp = new DateTime();
            $signature->setTimestamp($timestamp);
            $signature->setName($name);
            $signature->setRole($role);
            $signature->setFilename($folder . '/' . $filename);
            $em = $this->container->get('doctrine')->getManager();
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $em->persist($signature);
            $em->flush();

            $response = new Response(json_encode(array('timestamp' => $timestamp->format('Y-m-d H:i:s'), 'image' => '../../' . $folder . '/' . $filename)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
}
