<?php
class Typedata extends \Phalcon\Mvc\Model {
    public function insertall($key,$val){
        $result = Phalcon\Mvc\Model\Query\Builder::createInsertBuilder()
            ->table('Typedata')
            ->columns($key)
            ->values($val)
            ->getQuery()
            ->execute();
        return $result;
    }
}