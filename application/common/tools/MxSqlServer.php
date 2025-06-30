<?php
/**
 * Created by PhpStorm.
 * User: json
 * Date: 2018/8/27
 * Time: 下午3:12
 */

namespace app\common\tools;

use PDO;
class MxSqlServer
{
    private static $instance = null;

    private static $options = [
        "host" => '',
        "port" => 1433,
        "database" => '',
        "username" => '',
        "password" => ''
    ];

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance =  new PDO('odbc:Driver={FreeTDS}; Server='.self::$options['host'].'; Database='.self::$options['database'].
                '; Port='.self::$options['port'], self::$options['username'], self::$options['password']);
        }
        return self::$instance;
    }

    public static function paginate($keyword = '', $page_size = 10, $page_index = 1) {
        $total = self::total();

        if ($total < $page_size * ($page_index - 1)) {
            return false;
        }

        $pdo = self::getInstance();

        $offset = $page_size * ($page_index - 1);
        $start = $offset + 1;
        $end = $offset + $page_size;

        $sql = "SELECT 
                  Name as name,
                  SubName as subname,
                  FlowId as flow_id,
                  Id as id ,
                  Barcode as barcode,
                  Size as size,
                  UnitName as unit_name,
                  ProductPlace as product_place,
                  PurchasePrice as purchase_price,
                  RetailPrice as retail_price,
                  MinRetailPrice as min_retail_price,
                  ItemStatus as item_status,
                  ShelfLife as shelf_life
              FROM (
                      select *, ROW_NUMBER() OVER(Order by FlowId ) AS RowId from Item 
                        where Name like '%$keyword%'
                   ) as temp  
              where RowId between :start and :end";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);

        $stmt->execute();

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result['total'] = $total;
        $result['current_page'] = $page_index;
        $result['per_page'] = $page_size;
        $result['last_page'] = ceil($total / $page_size);
        $result['data'] = $data;

        return $result;
    }

    public static function total() {
        $pdo = self::getInstance();
        $sql = 'SELECT count(*) as num FROM Item';
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data[0]['num'];
    }

    public function selectInfo($item_code,$branch_code){

        $pdo = self::getInstance();
        $sql = "SELECT * FROM vw_item_avail_stock WHERE item_code LIKE '{$item_code}' AND branch_code LIKE '{$branch_code}'";
        $stmt = $pdo->query($sql);
        if($stmt){
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return false;
        }


    }
    public function select_branch_status($branch_code){
        $pdo = self::getInstance();
        $sql = "SELECT * FROM vw_branch_status WHERE  branch_code LIKE '{$branch_code}'";
        $stmt = $pdo->query($sql);
        if($stmt){
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        }else{
            return false;
        }


    }

    public function inserEvenOrder($sql){
        $pdo = self::getInstance();
        $stmt = $pdo->query($sql);
        return $stmt;
    }
}