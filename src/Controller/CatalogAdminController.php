<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/10/17
 * Time: 16:07
 * PHP version 7
 */

namespace App\Controller;

use App\Model\CatalogManager;
use App\Model\ElementTypeManager;
use App\Model\ToxicityManager;

/**
 * Class CatalogAdminController
 *
 */
class CatalogAdminController extends AbstractController
{

    /**
     * Display catalogAdmin page
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function index()
    {
        $catalogManager = new CatalogManager();
        $elements = $catalogManager->selectAll();

        return $this->twig->render('CatalogAdmin/index.html.twig', ['elements' => $elements]);
    }

    /**
     * Display catalogAdmin creation page
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function add()
    {
        $elementTypeManager = new ElementTypeManager();
        $toxicityManager = new ToxicityManager();
        $elementTypes = $elementTypeManager->selectAll();
        $toxicities = $toxicityManager->selectAll();
        $errorsList = [];
        $dataSend = [];
        $uploadDir = '../public/assets/images/catalog';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataSend = array_map('trim', $_POST);

            /* Verification of form fields */
            $errorsList = $this->checkForm($dataSend);

            /* Checking the field used to upload the file */
            if ($this->checkUploadFieldForm($_FILES) != '') {
                $errorsList[] = $this->checkUploadFieldForm($_FILES);
            }

            if (empty($errorsList)) {
                $tmpFilePath = $_FILES['picture']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
                $newFileName = uniqid() . '.' .$fileExtension;
                $filePath = $uploadDir . "/" . $newFileName;
                move_uploaded_file($tmpFilePath, $filePath);

                if (file_exists($filePath)) {
                    $catalogManager = new CatalogManager();
                    $dataSend['picture'] = $newFileName;
                    $catalogManager->insert($dataSend);

                    header('Location: /catalogAdmin/index');
                } else {
                    $errorsList[] = 'Le fichier n\'a pas été transféré';
                }
            }
        }

        return $this->twig->render('CatalogAdmin/add.html.twig', [
            'elementTypes' => $elementTypes,
            'toxicities' => $toxicities,
            'errors' => $errorsList,
            'dataSend' => $dataSend
        ]);
    }

    private function checkForm(array $dataSend): array
    {
        $errorMessage = [];

        $commonNameLenght = 255;
        if (empty($dataSend['commonName'])) {
            $errorMessage[] = 'Le nom commun doit être renseigné';
        }

        if (strlen($dataSend['commonName']) > $commonNameLenght) {
            $errorMessage[] = 'Le nom commun doit être inférieur a ' . $commonNameLenght . ' caractères';
        }

        $latinNameLenght = 255;
        if (empty($dataSend['latinName'])) {
            $errorMessage[] = 'Le nom latin doit être renseigné';
        }

        if (strlen($dataSend['latinName']) > $latinNameLenght) {
            $errorMessage[] = 'Le nom latin doit être inférieur a ' . $latinNameLenght . ' caractères';
        }

        $colorLenght = 100;
        if (empty($dataSend['color'])) {
            $errorMessage[] = 'Le couleur doit être renseigné';
        }

        if (strlen($dataSend['color']) > $colorLenght) {
            $errorMessage[] = 'Le couleur doit être inférieur a ' . $colorLenght . ' caractères';
        }

        if (empty($dataSend['description'])) {
            $errorMessage[] = 'Le description doit être renseigné';
        }

        $errorMessage = array_merge($errorMessage, $this->checkSelectFieldsForm('type', $dataSend['type']));
        $errorMessage = array_merge($errorMessage, $this->checkSelectFieldsForm('toxicity', $dataSend['toxicity']));

        return $errorMessage;
    }

    private function checkSelectFieldsForm(string $fieldName, string $fieldValue): array
    {
        $errors = [];

        if ($fieldName === 'type') {
            $elementTypeManager = new ElementTypeManager();
            $types = $elementTypeManager->selectAll();

            if (empty($fieldValue) || $fieldValue === '') {
                $errors[] = 'Le type doit être renseigné';
            } else {
                if (!in_array($fieldValue, array_column($types, 'id'))) {
                    $errors[] = 'Le type est inconnue';
                }
            }
        }

        if ($fieldName === 'toxicity') {
            $toxicityManager = new ToxicityManager();
            $toxicities = $toxicityManager->selectAll();

            if (empty($fieldValue) || $fieldValue === '') {
                $errors[] = 'Le toxicity doit être renseigné';
            } else {
                if (!in_array($fieldValue, array_column($toxicities, 'id'))) {
                    $errors[] = 'Le toxicity est inconnue';
                }
            }
        }

        return $errors;
    }

    /**
     * Check the fields used to upload the file is correct
     *
     * @param array $fileSend
     * @return string
     */
    private function checkUploadFieldForm(array $fileSend): string
    {
        $errorMessage = "";

        if ($_FILES['picture']['error'] == 4) {
            $errorMessage = 'Aucun fichier n\'a été transféré';
        } else {
            foreach ($_FILES as $key => $value) {
                $fileSend[$key] = $value;
            }

            $tmpFilePath = $fileSend['picture']['tmp_name'];

            if ($tmpFilePath != "") {
                $errorMessage = $this->checkValidFile($fileSend);
            } else {
                $errorMessage = 'Aucun fichier n\'a été transféré';
            }
        }

        return $errorMessage;
    }

    /**
     * Check that the file to be transferred is correct
     *
     * @param array $fileSend
     * @return string
     */
    private function checkValidFile(array $fileSend): string
    {
        $errorMessage = "";
        $fileExtensionAllowed = ['jpg', 'png', 'gif'];
        $fileSizeMax = 1000000;

        $fileExtension = strtolower(pathinfo($fileSend['picture']['name'], PATHINFO_EXTENSION));
        $fileSize = $fileSend['picture']['size'];

        if (!in_array($fileExtension, $fileExtensionAllowed) && $fileSize > $fileSizeMax) {
            $errorMessage = 'Le fichier doit être une image et sa taille doit être inférieure à 1 MO';
        } elseif (!in_array($fileExtension, $fileExtensionAllowed) && $fileSize < $fileSizeMax) {
            $errorMessage = 'Le fichier doit être une image et sa taille doit être inférieure à 1 MO';
        } elseif (in_array($fileExtension, $fileExtensionAllowed) && $fileSize > $fileSizeMax) {
            $errorMessage = "Le fichier doit être inférieur à 1 MO";
        }

        return $errorMessage;
    }
}
