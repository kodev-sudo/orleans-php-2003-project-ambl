<?php

/**
 * Created by PhpStorm.
 * User: aurelwcs
 * Date: 08/04/19
 * Time: 18:40
 */

namespace App\Controller;

use App\Model\LinkManager;

class LinkController extends AbstractController
{

    /**
     * Display catalog page
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function index()
    {
        $linkManager = new LinkManager();
        $links = $linkManager->selectAll();


        return $this->twig->render('Books/index.html.twig', ['links' => $links]);
    }
}
