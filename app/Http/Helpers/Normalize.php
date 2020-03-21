<?php
namespace App\Http\Helpers;



class Normalize {
   public static function normalize($arr){
        $tpm =[];
        $ids = [];
        foreach ($arr as $block){
            foreach ($block as $item){
                if(!in_array($item->id, $ids)){
                    array_push($tpm, $item);
                    array_push($ids, $item->id);
                }
            }
        }
        return $tpm;
    }
}

