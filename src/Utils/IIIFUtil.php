<?php

namespace App\Utils;

use App\Entity\IIIFManifest;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Imagick;

class IIIFUtil
{
    public static $thumbnailWidth = 150;

    public static function isPublicImage($imageUrl)
    {
        return strpos($imageUrl, '/public@') !== false;
    }

    public static function filterPublicImages($imageUrl)
    {
        if(self::isPublicImage($imageUrl)) {
            return $imageUrl;
        } else {
            return '';
        }
    }

    public static function generateIIIFThumbnail($imageUrl, $onlyPublic = true)
    {
        if($onlyPublic) {
            $imageUrl = self::filterPublicImages($imageUrl);
            if($imageUrl === '') {
                return '';
            }
        }
        return $imageUrl . '/full/' . self::$thumbnailWidth . ',/0/default.jpg';
    }

    public static function generateThumbnail($file, $thumbnail)
    {
        if (is_file($file)) {
            $imagick = new Imagick(realpath($file));
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(100);
            $imagick->thumbnailImage(self::$thumbnailWidth, 500, true, false);
            if (file_put_contents($thumbnail, $imagick) === false) {
                throw new Exception("Could not store thumbnail.");
            }
            return true;
        }
        else {
            throw new Exception("No valid image provided with {$file}.");
        }
    }

    public static function generateManifest($em, $reportId, $reportData, $images, $annotationData, $serviceUrl,
                                            $validate, $validatorUrl, $authenticationUrl, $authenticationServiceDescription)
    {
        $data['metadata'] = array();
        $data['label'] = '';
        $data['required_statement'] = '';
        $data['description'] = '';
        $data['sourceinvnr'] = '';
        foreach($reportData as $key => $value) {
            switch($key) {
                case 'title_nl':
                    if(array_key_exists('title_en', $reportData)) {
                        $data['label'] = array(
                            'nl' => array($value),
                            'en' => array($reportData['title_en'])
                        );
                        $data['metadata'][] = array(
                            'label' => array(
                                'nl' => array('Titel'),
                                'en' => array('Title')
                            ),
                            'value' => array(
                                'nl' => array($value),
                                'en' => array($reportData['title_en'])
                            )
                        );
                    } else {
                        $data['label'] = array(
                            'nl' => array($value),
                            'en' => array($value)
                        );
                        $data['metadata'][] = array(
                            'label' => array(
                                'nl' => array('Titel'),
                                'en' => array('NL - Title')
                            ),
                            'value' => array(
                                'nl' => array($value),
                                'en' => array($value)
                            )
                        );
                    }
                    break;
                case 'publisher':
                    $data['required_statement'] = array(
                        'label' => array(
                            'nl' => array('Attributie'),
                            'en' => array('Attribution')
                        ),
                        'value' => array(
                            'nl' => array($value),
                            'en' => array($value)
                        )
                    );
                    $data['metadata'][] = array(
                        'label' => array(
                            'nl' => array('Publisher'),
                            'en' => array('Publisher')
                        ),
                        'value' => array(
                            'nl' => array($value),
                            'en' => array($value)
                        )
                    );
                    break;
                case 'inventory_number':
                    $data['sourceinvnr'] = $value;
                    $data['metadata'][] = array(
                        'label' => array(
                            'nl' => array('Inventarisnummer'),
                            'en' => array('Object ID')
                        ),
                        'value' => array(
                            'nl' => array($value),
                            'en' => array($value)
                        )
                    );
                    break;
            }
        }

        // Generate the canvases
        $canvases = array();
        $index = 0;
        $startCanvas = null;
        $publicUse = true;
        $annotations = array();

        // Loop through all resources related to this resource (including itself)
        foreach($images as $image) {
            $imageData = self::getImageData($image->image);
            $isIIIF = StringUtil::endsWith($image->image, '/info.json');

            $index++;
            $canvasId = $serviceUrl . $reportId . '/canvas/' . $index;
            if($index == 1) {
                $publicUse = $imageData['public_use'];
            }
            $body = array(
                'id'      => $imageData['image_url'],
                'type'    => 'Image',
                'format'  => 'image/jpeg',
                'height'  => $imageData['height'],
                'width'   => $imageData['width'],
            );
            if($isIIIF) {
                $body['service'] = array(
                    'id'      => $imageData['service_id'],
                    'profile' => 'http://iiif.io/api/image/2/level2.json',
                    'type'    => 'ImageService2'
                );
            }
            $painting = array(
                'id'         => $serviceUrl . $reportId . '/annotation/' . $index . '-image',
                'type'       => 'Annotation',
                'motivation' => 'painting',
                'body'       => $body,
                'target'     => $canvasId
            );
            $annotationPage = array(
                'id'    => $canvasId . '/1',
                'type'  => 'AnnotationPage',
                'items' => array($painting)
            );
            $canvases[] = array(
                'id'     => $canvasId,
                'type'   => 'Canvas',
                'label'  => $data['label'],
                'height' => $imageData['height'],
                'width'  => $imageData['width'],
                'items'  => array($annotationPage)
            );
            if(array_key_exists($image->hash, $annotationData)) {
                foreach($annotationData->{$image->hash} as $id => $annotation) {
                    $anno = clone $annotation;
                    $anno->id = $serviceUrl . $reportId . '/annotation/p1/' . substr($anno->id, 1);
                    $anno->target->source = $imageData['image_url'];
                    $annotations[] = $anno;
                }
            }
        }

        $manifestId = $serviceUrl . $reportId . '/manifest.json';
        // Generate the whole manifest
        $manifest = array(
            '@context'          => 'http://iiif.io/api/presentation/3/context.json',
            'id'                => $manifestId,
            'type'              => 'Manifest',
            'label'             => $data['label'],
            'metadata'          => $data['metadata'],
            'summary'           => $data['label'],
            'requiredStatement' => $data['required_statement'],
            'viewingDirection'  => 'left-to-right',
            'items'             => $canvases
        );
        if(!empty($annotations)) {
            $manifest['annotations'] = array(array('id' => $serviceUrl . $reportId . '/annotations/p1', 'type' => 'AnnotationPage', 'items' => $annotations));
        }

        // This image is not for public use, therefore we also don't want this manifest to be public
        if(!$publicUse) {
            $manifest['service'] = self::getAuthenticationService($authenticationUrl, $authenticationServiceDescription);
        }

        $manifestDocument = self::storeManifest($em, $manifest, $manifestId);

        // Validate the manifest
        // We can only pass a URL to the validator, so the manifest needs to be stored and served already before validation
        // If it does not pass validation, remove from the database
        $valid = true;
        if($validate) {
            $valid = self::validateManifest($validatorUrl, $manifestId);
            if (!$valid) {
                echo 'Manifest ' . $manifestId . ' is not valid.' . PHP_EOL;
//                $this->logger->error('Manifest ' . $manifestId . ' is not valid.');
                $em->remove($manifestDocument);
                $em->flush();
                $em->clear();
            }
        }
        return $manifestId;
    }

    private static function storeManifest(EntityManagerInterface $em, $manifest, $manifestId)
    {
        // Store the manifest in mongodb
        $manifestDocument = new IIIFManifest();
        $manifestDocument->setManifestId($manifestId);
        $manifestDocument->setData(json_encode($manifest));
        $em->persist($manifestDocument);
        $em->flush();
        $em->clear();
        return $manifestDocument;
    }

    private static function getImageData($imageUrl)
    {
        $isPublic = self::filterPublicImages($imageUrl);
        $baseImage = $imageUrl;
        $image = $imageUrl;
        $width = 0;
        $height = 0;
        if(StringUtil::endsWith($imageUrl, '/info.json')) {
            $baseImage = substr($imageUrl, 0, -10);
            $image = $baseImage . '/full/max/0/default.jpg';
            $imageDataJSON = CurlUtil::get($imageUrl);
            if($imageDataJSON) {
                $imageData = json_decode($imageDataJSON);
                $width = $imageData->width;
                $height = $imageData->height;
            }
        } else {
            if(strpos($imageUrl, '../') === 0) {
                while (strpos($imageUrl, '../') === 0) {
                    $imageUrl = substr($imageUrl, 3);
                }
                $imageUrl = '/' . $imageUrl;
            }
            if(strpos($imageUrl, '/') === 0) {
                $imageUrl = '../public' . $imageUrl;
            }
            $imageSize = getimagesize($imageUrl);
            if($imageSize) {
                if(array_key_exists('width', $imageSize) && array_key_exists('height', $imageSize)) {
                    $width = $imageSize['width'];
                    $height = $imageSize['height'];
                } else if(array_key_exists(0, $imageSize) && array_key_exists(1, $imageSize)) {
                    $width = $imageSize[0];
                    $height = $imageSize[1];
                }
            }
        }
        $imageData = array();
        $imageData['service_id'] = $baseImage;
        $imageData['image_url'] = $image;
        $imageData['public_use'] = $isPublic;
        $imageData['width'] = $width;
        $imageData['height'] = $height;
        return $imageData;
    }

    private static function getAuthenticationService($authenticationUrl, $authenticationServiceDescription)
    {
        $arr = array(
            '@context' => 'http://iiif.io/api/auth/1/context.json',
            '@id'      => $authenticationUrl,
        );
        foreach($authenticationServiceDescription as $key => $value) {
            $arr[$key] = $value;
        }
        return $arr;
    }

    private static function validateManifest($validatorUrl, $manifestId)
    {
        $valid = true;
        try {
            $validatorJsonResult = file_get_contents($validatorUrl . $manifestId);
            $validatorResult = json_decode($validatorJsonResult);
            $valid = $validatorResult->okay == 1;
            if (!empty($validatorResult->warnings)) {
                foreach ($validatorResult->warnings as $warning) {
                    echo 'Manifest ' . $manifestId . ' warning: ' . $warning . PHP_EOL;
//                    $this->logger->warning('Manifest ' . $manifestId . ' warning: ' . $warning);
                }
            }
            if (!empty($validatorResult->error)) {
                if ($validatorResult->error != 'None') {
                    $valid = false;
                    echo 'Manifest ' . $manifestId . ' error: ' . $validatorResult->error . PHP_EOL;
//                    $this->logger->error('Manifest ' . $manifestId . ' error: ' . $validatorResult->error);
                }
            }
        } catch (Exception $e) {
            echo 'Error validating manifest ' . $manifestId . ': ' . $e . PHP_EOL;
//            $this->logger->error('Error validating manifest ' . $manifestId . ': ' . $e);
        }
        return $valid;
    }
}
