<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (DataSetColumnTypeFactory.php)
 */


namespace Xibo\Factory;


use Xibo\Entity\DataSetColumnType;

class DataSetColumnTypeFactory extends BaseFactory
{
    /**
     * @param null $sortOrder
     * @param null $filterBy
     * @return array[DataSetColumnType]
     */
    public function query($sortOrder = null, $filterBy = null)
    {
        $entries = [];

        foreach ($this->getStore()->select('SELECT dataSetColumnTypeId, dataSetColumnType FROM `datasetcolumntype` ', []) as $row) {
            $entries[] = (new DataSetColumnType())->hydrate($row)->setContainer($this->getContainer());
        }

        return $entries;
    }
}