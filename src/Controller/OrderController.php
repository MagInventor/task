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

        $orders = array_merge($data_csv, $data_json, $data_ldif);

        $top30Orders = $this->getTop30Orders($orders);

        $clientGroup = $this->getClientGroups($orders);

        $statusGroup = $this->getStatusCounts($data_csv, $data_json, $data_ldif);

        $consonantCount = $this->getConsonantCount($orders);

        return $this->render('order/index.html.twig', [
            'controller_name' => 'OrderController',
            'top30Orders' => $top30Orders,
            'clientGroup' => $clientGroup,
            'statusGroup' => $statusGroup,
            'consonantCount' => $consonantCount,
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
        $result['cols']= explode('|', $cols[0][0]);

        for ($i = 0; $i < count($data); $i++) {
            if (count($data[$i]) > 1) {
                $data[$i][0] = array_reduce($data[$i], fn($carry, $item) => $carry.$item);
                array_splice($data[$i], 1 , count($data[$i]));
            }
            $result['data'][$i]  = explode('|', $data[$i][0]);
        }

        return $this->transformArray($result);
    }

    function convertArrJson($filename) {
        $result = json_decode(file_get_contents($filename), true);
        return $this->transformArray($result);
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
        return $this->transformArray($result);
    }

    function transformArray($arr) {
        for ($i = 0; $i < count($arr['data']); $i++) {
            for ($j = 0; $j < count($arr['data'][$i]); $j++) {
                $result[$i][$arr['cols'][$j]] = $arr['data'][$i][$j];
            }
        }
        return $result;
    }

    function getTop30Orders($orders) {
        $salesCount = [];

        for ($i = 0; $i < count($orders); $i++) {
            $medicine_name = $orders[$i]['Order'];
            if (empty($salesCount[$medicine_name])) {
                $salesCount[$medicine_name] = 1;
            } else {
                $salesCount[$medicine_name]++;
            } 
        }

        arsort($salesCount);
        $result = array_slice($salesCount, 0, 30);

        $resultHTML = [];
        foreach ($result as $key => $value) {
            array_push($resultHTML, array('title' => $key, 'quantity' => $value));
        }
        return $resultHTML; 
    }

    function getClientGroups($orders) {
        $ordersCount = [];

        for ($i = 0; $i < count($orders); $i++) {
            $client_group = $orders[$i]['Country'];
            if (empty($ordersCount[$client_group])) {
                $ordersCount[$client_group] = 1;
            } else {
                $ordersCount[$client_group]++;
            } 
        }
        arsort($ordersCount);

        $resultHTML = [];
        foreach ($ordersCount as $key => $value) {
            array_push($resultHTML, array('country' => $key, 'quantity' => $value));
        }
        return $resultHTML[0]; 
    }

    function getStatusCounts($data_csv, $data_json, $data_ldif) {
        $statusCounts = [];
        $statusClients = [];

        function getBigStatus($data) {
            for ($i = 0; $i < count($data); $i++) {
                $status = $data[$i]['Status'];
                if (empty($statusClients[$status])) {
                    $statusClients[$status] = 1;
                } else {
                    $statusClients[$status]++;
                } 
            }
            arsort($statusClients);
            return array_slice($statusClients, 0, 1);
        }

        $bigStatus['CSV'] = getBigStatus($data_csv);
        $bigStatus['JSON'] = getBigStatus($data_json);
        $bigStatus['LDIF'] = getBigStatus($data_ldif);

        $resultHTML = [];
        foreach ($bigStatus as $format => $value) {
            foreach ($value as $key => $value2) {
                array_push($resultHTML, array('format' => $format, 'status' => $key, 'quantity' => $value2));
            }
        }
        return $resultHTML; 
    }

    function getConsonantCount($orders) {
        $consonants = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z');
        $count = 0;

        for ($i = 0; $i < count($orders); $i++) {
            $customer = trim($orders[$i]['Customer']);
            for ($j = 0; $j < strlen($customer); $j++) {
                if (in_array($customer[$j], $consonants)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
