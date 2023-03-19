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

        $orders = $this->arrayMerge($data_csv, $data_json, $data_ldif);
        echo '<pre>';
        // print_r($data_csv);
        // print_r($data_json);
        // print_r($data_ldif);
        print_r($orders);
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
                return $this->convertArrCsv($filename);
            case 'json':
                return $this->convertArrJson($filename);
            case 'ldif':
                return $this->convertArrLdif($filename);
            default:
                return [];
        }
    }

    function convertArrCsv($filename) {
        $data = array_map('str_getcsv', file($filename));

        $cols = array_splice($data, 0, 1);
        $result['cols'] = explode('|', $cols[0][0]);

        for ($i = 0; $i < count($data); $i++) {
            if (count($data[$i]) > 1) {
                $data[$i][0] = array_reduce($data[$i], function($carry, $item) {
                    return $carry.$item;
                });
                array_splice($data[$i], 1 , count($data[$i]));
            }
            $result['data'][$i]  = explode('|', $data[$i][0]);
        }

        return $result;
    }

    function convertArrJson($filename) {
        return json_decode(file_get_contents($filename), true);
    }

    function convertArrLdif($filename) {
        $data = file_get_contents($filename);
        $records = explode("\n\n", $data);
        $result = [];

        for ($i = 0; $i < count($records) - 1; $i++) {
            $lines = explode("\n", $records[$i]);
            for ($j = 0; $j < count($lines); $j++) {
                if (empty($result['cols'][$j])) {
                    $result['cols'][$j] = explode(':', $lines[$j])[0];
                }
                $result['data'][$i][$j] = explode(':', $lines[$j])[1];
            }  
        }
        return $result;
    }

    function arrayMerge($arr1, $arr2, $arr3) {
        $result = [];

        $result['cols'] = $arr1['cols'];
        $result['data'] = array_merge($arr1['data'], $arr2['data'], $arr3['data']);

        return $result;
    }
}
