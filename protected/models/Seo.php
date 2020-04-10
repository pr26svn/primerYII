<?php

class Seo extends CActiveRecord {

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'seo';
    }

    protected function getAction() {
        return 'getSeo';
    }

    public function findByUrl($url) {
        return $this->find('url = :url', array(
            ':url' => $url
        ));
    }

    public function attributeApiNames(){
        return array(
            'id',
            'url',
            'title',
            'keywords',
            'description',
            'canonical',
            'redirect',
        );
    }

}