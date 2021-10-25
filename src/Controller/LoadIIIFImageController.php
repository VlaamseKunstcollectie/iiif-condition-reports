<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Report;
use App\Entity\ReportHistory;
use App\Utils\CurlUtil;
use App\Utils\IIIFUtil;
use App\Utils\StringUtil;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoadIIIFImageController extends AbstractController
{
    /**
     * @Route("/loadiiifimage", name="loadiiifimage")
     */
    public function loadiiifimage(Request $request)
    {
        $imageUrl = $request->get('image');
        if(!StringUtil::endsWith($imageUrl, '/info.json')) {
            if(StringUtil::endsWith($imageUrl, '/')) {
                $imageUrl .= 'info.json';
            } else {
                $imageUrl .= '/info.json';
            }
        }
        $imageNoJson = substr($imageUrl, 0, -10);
        try {
            $iiifImageData = CurlUtil::get($imageUrl);
            if (strpos($iiifImageData, 'Redirect: ') !== false) {
                $iiifImageData = '';
            }
        } catch(Exception $e) {
            $iiifImageData = '';
        }
        if($iiifImageData == '') {
            $response = new Response(json_encode(array('hash' => null)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        } else {
            $image = new Image();
            $image->setImage($imageUrl);

            //Check if this image doesn't exist already
            $em = $this->container->get('doctrine')->getManager();
            //Disable SQL logging to improve performance
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $images = $em->createQueryBuilder()
                ->select('i')
                ->from(Image::class, 'i')
                ->where('i.hash = :hash')
                ->setParameter('hash', $image->getHash())
                ->getQuery()
                ->getResult();
            $persist = true;
            foreach($images as $img) {
                $persist = false;
                $image = $img;
            }
            if($persist) {
                $image->setThumbnail(IIIFUtil::generateIIIFThumbnail($imageNoJson, false));
                $em->persist($image);
                $em->flush();
            }

            $response = new Response(json_encode(array('hash' => $image->getHash(), 'image' => $image->getImage(), 'thumbnail' => $image->getThumbnail())));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
}
