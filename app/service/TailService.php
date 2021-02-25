<?php


namespace app\service;


use app\model\Tail;
use think\facade\Db;

class TailService
{
    public function getlist($num, $end_point, $order = 'id')
    {
//        $n = Tail::select()->count();
//        if ($num > $n) {
//            $num = $n;
//        }
//        $random_number_array = range(0, $n);
//        shuffle($random_number_array );
//        $random_number_array = array_slice($random_number_array ,0,$num);
//        $tails = Tail::with('book.cate')->where('id','in',$random_number_array)->select();
//
//        return $tails;

        $data = Tail::with('book.cate')->order($order, 'desc')
            ->paginate([
                'list_rows' => $num,
                'query' => request()->param(),
            ]);

        $books = $data->toArray();
        return [
            'tails' => $books['data'],
            'page' => [
                'total' => $books['total'],
                'per_page' => $books['per_page'],
                'current_page' => $books['current_page'],
                'last_page' => $books['last_page'],
                'query' => request()->param()
            ]
        ];
    }
}