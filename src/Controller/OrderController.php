<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(): Response
    {
        $data_csv = $this->loadData('../public/data/dataFeb-2-2017.csv');
        $data_json = $this->loadData('../public/data/dataFeb-2-2017.json');
        $data_ldif = $this->loadData('../public/data/dataFeb-2-2017.ldif');
        echo '<pre>';
        print_r($data_csv);
        print_r($data_json);
        print_r($data_ldif);
        echo '</pre>';

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }

    function loadData($filename)
    {
        $ext = file_exists($filename) ? strtolower(pathinfo($filename, PATHINFO_EXTENSION)) : '';

        switch($ext) {
            case 'csv':
                return array_map('str_getcsv', file($filename));
            case 'json':
                return json_decode(file_get_contents($filename), true);
            case 'ldif':
                return file_get_contents($filename);
            default:
                return [];
        }
    }
}
