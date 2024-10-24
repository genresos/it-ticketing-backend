<?php

namespace App\Modules;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
class PaginationArr
{
    public static function arr_pagination($myArray,$page,$perPage,$url,$query){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $total = count($myArray);
        $currentpage = $page;
        $offset = ($currentpage * $perPage) - $perPage ;
        $itemstoshow = array_slice($myArray , $offset , $perPage);
        $dataArray = new LengthAwarePaginator($itemstoshow ,$total ,$perPage,$page,
            ['path' => $url, 'query' => $query]
    
        );
        
        return response()->json($dataArray,200);
    }

    public static function arr_new_pagination($myArray,$total,$page,$perPage,$url,$query){
        $dataArray = new LengthAwarePaginator($myArray ,$total ,$perPage,$page,
            ['path' => $url, 'query' => $query]
    
        );
        
        return response()->json($dataArray,200);
    }
    
}