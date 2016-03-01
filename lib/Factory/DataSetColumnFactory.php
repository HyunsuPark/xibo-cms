<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (DataSetColumnFactory.php)
 */


namespace Xibo\Factory;


use Xibo\Entity\DataSetColumn;
use Xibo\Exception\NotFoundException;

class DataSetColumnFactory extends BaseFactory
{
    /**
     * Get by Id
     * @param int $dataSetColumnId
     * @return DataSetColumn
     * @throws NotFoundException
     */
    public function getById($dataSetColumnId)
    {
        $columns = $this->query(null, ['dataSetColumnId' => $dataSetColumnId]);

        if (count($columns) <= 0)
            throw new NotFoundException();

        return $columns[0];
    }

    /**
     * Get by dataSetId
     * @param $dataSetId
     * @return array[DataSetColumn]
     */
    public function getByDataSetId($dataSetId)
    {
        return $this->query(null, ['dataSetId' => $dataSetId]);
    }

    public function query($sortOrder = null, $filterBy = null)
    {
        $entries = [];
        $params = [];

        if ($sortOrder == null)
            $sortOrder = ['columnOrder'];

        $select = '
            SELECT dataSetColumnId,
                dataSetId,
                heading,
                datatype.dataTypeId,
                datatype.dataType,
                datasetcolumn.dataSetColumnTypeId,
                datasetcolumntype.dataSetColumnType,
                listContent,
                columnOrder,
                formula
            ';

        $body = '
              FROM `datasetcolumn`
               INNER JOIN `datatype`
               ON datatype.DataTypeID = datasetcolumn.DataTypeID
               INNER JOIN `datasetcolumntype`
               ON datasetcolumntype.DataSetColumnTypeID = datasetcolumn.DataSetColumnTypeID
             WHERE 1 = 1 ';

        if ($this->getSanitizer()->getInt('dataSetColumnId', $filterBy) !== null) {
            $body .= ' AND dataSetColumnId = :dataSetColumnId ';
            $params['dataSetColumnId'] = $this->getSanitizer()->getInt('dataSetColumnId', $filterBy);
        }

        if ($this->getSanitizer()->getInt('dataSetId', $filterBy) !== null) {
            $body .= ' AND DataSetID = :dataSetId ';
            $params['dataSetId'] = $this->getSanitizer()->getInt('dataSetId', $filterBy);
        }

        // Sorting?
        $order = '';
        if (is_array($sortOrder))
            $order .= 'ORDER BY ' . implode(',', $sortOrder);

        $limit = '';
        // Paging
        if ($this->getSanitizer()->getInt('start', $filterBy) !== null && $this->getSanitizer()->getInt('length', $filterBy) !== null) {
            $limit = ' LIMIT ' . intval($this->getSanitizer()->getInt('start'), 0) . ', ' . $this->getSanitizer()->getInt('length', 10);
        }

        $sql = $select . $body . $order . $limit;



        foreach ($this->getStore()->select($sql, $params) as $row) {
            $entries[] = (new DataSetColumn())->hydrate($row)->setContainer($this->getContainer());
        }

        // Paging
        if ($limit != '' && count($entries) > 0) {
            $results = $this->getStore()->select('SELECT COUNT(*) AS total ' . $body, $params);
            $this->_countLast = intval($results[0]['total']);
        }

        return $entries;
    }
}